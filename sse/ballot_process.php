<?php
/**
 * e-SPPO - Surat Suara Elektronik (SSE)
 *
 * KRITIKAL: Skrip ini memproses, mengenkripsi, dan menyimpan suara pemilih.
 * Skrip ini dirancang untuk menjadi atomik dan aman untuk mencegah pemungutan suara ganda.
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// 1. INISIALISASI & PENJAGA KEAMANAN (GUARDS)
// -----------------------------------------------------------------------------

session_name('eSPPO-SSE-V2');
session_start();

require_once __DIR__ . '/../helpers/db_helper.php';
require_once __DIR__ . '/../helpers/crypto_helper.php';

// Guard 1: Hanya izinkan metode POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit('Metode tidak diizinkan.');
}

// Guard 2: Pastikan pemilih sudah login.
if (!isset($_SESSION['voter_id_unik'])) {
    header('Location: login.php');
    exit('Akses ditolak. Sesi tidak valid.');
}

// Guard 3: Pastikan ada pilihan kandidat yang dikirim.
if (empty($_POST['pilihan_kandidat'])) {
    // Redirect kembali ke halaman pemilihan jika tidak ada pilihan.
    header('Location: index.php');
    exit('Tidak ada pilihan yang dibuat.');
}

// 2. PROSES INTI (DALAM TRANSAKSI DATABASE)
// -----------------------------------------------------------------------------

$db = get_db_connection();
// Matikan autocommit untuk memulai mode transaksi.
$db->autocommit(FALSE);

try {
    // --- LANGKAH 1: KUNCI DAN VERIFIKASI ULANG STATUS PEMILIH ---
    // Kunci baris pemilih untuk pembaruan (FOR UPDATE) untuk mencegah race condition.
    // Proses lain yang mencoba mengakses baris ini akan menunggu hingga transaksi ini selesai.
    $stmt = $db->prepare("SELECT status_pemilih FROM data_pemilih WHERE id_unik_pemilih = ? FOR UPDATE");
    $stmt->bind_param('s', $_SESSION['voter_id_unik']);
    $stmt->execute();
    $result = $stmt->get_result();
    $voter_status = $result->fetch_assoc();
    $stmt->close();

    // Jika karena suatu alasan status sudah berubah, batalkan proses.
    if (!$voter_status || $voter_status['status_pemilih'] === 'SUDAH_MEMILIH') {
        throw new Exception("Upaya pemungutan suara ganda terdeteksi.");
    }

    // --- LANGKAH 2: PERSIAPAN DATA DAN KRIPTOGRAFI ---
    $chosen_candidate_id_b64 = $_POST['pilihan_kandidat'];
    
    // Ambil nomor urut kandidat untuk disimpan di tabel suara.
    $stmt_cand = $db->prepare("SELECT no_urut_kandidat FROM data_kandidat WHERE id_unik_kandidat = ?");
    $stmt_cand->bind_param('s', $chosen_candidate_id_b64);
    $stmt_cand->execute();
    $cand_result = $stmt_cand->get_result();
    $candidate_data = $cand_result->fetch_assoc();
    $stmt_cand->close();
    
    if (!$candidate_data) {
        throw new Exception("Kandidat yang dipilih tidak valid.");
    }
    $chosen_candidate_no = $candidate_data['no_urut_kandidat'];

    // Decode semua data Base64 ke format biner untuk operasi kriptografi.
    $voter_id_raw = base64_decode($_SESSION['voter_id_unik']);
    $voter_iv_raw = base64_decode($_SESSION['voter_iv']);
    $candidate_id_raw = base64_decode($chosen_candidate_id_b64);
    
    // Enkripsi ID pemilih menggunakan fungsi helper.
    $encrypted_voter_id_b64 = encrypt_voter_id($voter_id_raw, $voter_iv_raw);
    if ($encrypted_voter_id_b64 === false) {
        throw new Exception("Proses enkripsi ID pemilih gagal.");
    }
    
    // Decode kembali hasil enkripsi ke biner untuk membuat ID suara.
    $encrypted_voter_id_raw = base64_decode($encrypted_voter_id_b64);

    // Hasilkan ID suara yang unik menggunakan fungsi helper.
    $vote_id_b64 = generate_vote_id($encrypted_voter_id_raw, $candidate_id_raw);

    // --- LANGKAH 3: SIMPAN SUARA KE DATABASE ---
    $stmt_insert = $db->prepare(
        "INSERT INTO data_suara (stempel_waktu_suara, kode_id_suara, no_urut_kandidat, kk_pemilih, jk_pemilih, id_pemilih_terenkripsi) VALUES (NOW(), ?, ?, ?, ?, ?)"
    );
    $stmt_insert->bind_param(
        'ssdss',
        $vote_id_b64,
        $chosen_candidate_no,
        $_SESSION['voter_kk'],
        $_SESSION['voter_jk'],
        $encrypted_voter_id_b64
    );
    $stmt_insert->execute();
    $stmt_insert->close();

    // --- LANGKAH 4: PERBARUI STATUS PEMILIH ---
    $stmt_update = $db->prepare("UPDATE data_pemilih SET status_pemilih = 'SUDAH_MEMILIH' WHERE id_unik_pemilih = ?");
    $stmt_update->bind_param('s', $_SESSION['voter_id_unik']);
    $stmt_update->execute();
    $stmt_update->close();

    // --- LANGKAH 5: COMMIT TRANSAKSI ---
    // Jika semua langkah di atas berhasil, buat perubahan menjadi permanen.
    $db->commit();

} catch (Exception $e) {
    // Jika terjadi kesalahan di titik mana pun, batalkan semua perubahan.
    $db->rollback();
    
    // Catat error untuk administrator.
    error_log("KRITIS: Transaksi suara gagal untuk pemilih " . $_SESSION['voter_id_unik'] . ". Pesan: " . $e->getMessage());
    
    // Hancurkan sesi dan arahkan ke halaman login dengan pesan error.
    session_unset();
    session_destroy();
    header('Location: login.php?error=vote_failed');
    exit();
}

// 3. PEMBERSIHAN DAN REDIREKSI
// -----------------------------------------------------------------------------

// Hancurkan sesi setelah suara berhasil dikirim untuk keamanan.
session_unset();
session_destroy();

// Arahkan ke halaman sukses.
header('Location: vote_successful.php');
exit();

?>
