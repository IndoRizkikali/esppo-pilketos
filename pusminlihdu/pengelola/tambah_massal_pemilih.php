<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Tambah Massal Pemilih (NO GUI)
 * pusminlihdu/pengelola/tambah_massal_pemilih.php
 * 
 * Halaman ini menangani proses penambahan pemilih secara massal melalui file spreadsheet.
 * Pengelola dapat mengunggah file yang berisi data pemilih untuk ditambahkan ke dalam sistem.
 * PERHATIAN: Halaman ini tidak memiliki antarmuka pengguna grafis (GUI) dan hanya menangani
 * proses backend untuk mengimpor data pemilih.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. INISIALISASI & VALIDASI AWAL
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

// Pastikan sesi admin sudah ada
session_name('eSPPO_PAPT_V2');
session_start();

// Validasi sesi admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'PENGELOLA') {
    header('Location: ../login.php');
    exit('Akses ditolak.');
}

// Sertakan semua yang dibutuhkan
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../helpers/db_helper.php';
require_once __DIR__ . '/../../helpers/crypto_helper.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Arahkan kembali ke loader utama, bukan ke file langsung.
function redirect_with_message(string $status, string $message) {
    header("Location: index.php?page=tambah_pemilih&status={$status}&message=" . urlencode($message));
    exit();
}

// Validasi file unggahan
if (!isset($_FILES['dpt_file']) || $_FILES['dpt_file']['error'] !== UPLOAD_ERR_OK) {
    redirect_with_message('error', 'Gagal mengunggah file. Kode Error: ' . ($_FILES['dpt_file']['error'] ?? 'Tidak diketahui'));
}

$file = $_FILES['dpt_file'];
$upload_dir = __DIR__ . '/../../assets/spreadsheets/papt-unggah-massal/';
$file_path = $upload_dir . basename($file['name']) . '_' . time();

if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    redirect_with_message('error', 'Gagal memindahkan file yang diunggah.');
}

// -----------------------------------------------------------------------------
// 2. PROSES SPREADSHEET
// -----------------------------------------------------------------------------
$success_count = 0;
$error_count = 0;
$log_errors = [];

try {
    $spreadsheet = IOFactory::load($file_path);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(null, true, true, true);

    $db = get_db_connection();

    // Ambil nomor DPT terakhir sebelum loop
    $result_dpt = $db->query("SELECT MAX(nomor_dpt_pemilih) as max_dpt FROM data_pemilih");
    $next_dpt_number = ($result_dpt->fetch_assoc()['max_dpt'] ?? 0) + 1;

    // Mulai loop dari baris ke-5 (asumsi 4 baris header)
    for ($i = 5; $i <= count($data); $i++) {
        $row = $data[$i];
        
        // Asumsi kolom: B=Nama, C=JK, D=Kode Konstituensi, E=Nama Akun, F=Password
        $nama_pemilih = trim($row['B'] ?? '');
        $jk_input = trim($row['C'] ?? '');
        $kk_pemilih = trim($row['D'] ?? '');
        $nama_akun = trim($row['E'] ?? '');
        $password = trim($row['F'] ?? '');

        // Lewati baris kosong
        if (empty($nama_pemilih) && empty($nama_akun)) {
            continue;
        }

        // Mulai transaksi untuk setiap baris
        $db->begin_transaction();
        try {
            // Validasi data
            if (empty($nama_pemilih) || empty($jk_input) || empty($kk_pemilih) || empty($nama_akun) || empty($password)) {
                throw new Exception("Data tidak lengkap.");
            }

            // Normalisasi input Jenis Kelamin
            $jk_normalized_input = strtoupper(str_replace(' ', '_', $jk_input));
            $jk_pemilih = null;
            switch ($jk_normalized_input) {
                case 'LAKI-LAKI':
                case 'PRIA':
                    $jk_pemilih = 'PRIA';
                    break;
                case 'WANITA':
                case 'PEREMPUAN':
                    $jk_pemilih = 'WANITA';
                    break;
                case 'TIDAK_DIKETAHUI':
                case 'TIDAK_DITENTUKAN':
                    $jk_pemilih = 'TIDAK_DIKETAHUI';
                    break;
            }

            if ($jk_pemilih === null) {
                throw new Exception("Jenis Kelamin tidak valid ('{$jk_input}').");
            }
            
            // Validasi duplikat & konstituensi
            $stmt_check = $db->prepare("SELECT nomor_dpt_pemilih FROM data_pemilih WHERE nama_akun_pemilih = ?");
            $stmt_check->bind_param('s', $nama_akun);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                throw new Exception("Nama Akun '{$nama_akun}' sudah ada.");
            }
            $stmt_check->close();

            $stmt_const = $db->prepare("SELECT kode_konstituensi FROM data_konstituensi WHERE kode_konstituensi = ? AND status_konstituensi = 'DIAKTIFKAN'");
            $stmt_const->bind_param('s', $kk_pemilih);
            $stmt_const->execute();
            if ($stmt_const->get_result()->num_rows === 0) {
                throw new Exception("Kode Konstituensi '{$kk_pemilih}' tidak valid atau tidak aktif.");
            }
            $stmt_const->close();

            // Generate data otomatis
            $id_unik = generate_unique_id(16);
            $iv = generate_unique_id(16);
            $hashed_password = hash_password($password);
            $status = 'BELUM_MEMILIH';
            
            // Insert ke DB
            $stmt_add = $db->prepare("INSERT INTO data_pemilih (id_unik_pemilih, nomor_dpt_pemilih, nama_pemilih, jk_pemilih, kk_pemilih, nama_akun_pemilih, kata_sandi_pemilih, iv_akun_pemilih, status_pemilih) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_add->bind_param('sssssssss', $id_unik, $next_dpt_number, $nama_pemilih, $jk_pemilih, $kk_pemilih, $nama_akun, $hashed_password, $iv, $status);
            $stmt_add->execute();
            $stmt_add->close();

            $db->commit();
            $success_count++;
            $next_dpt_number++;

        } catch (Exception $e) {
            $db->rollback();
            $error_count++;
            $log_errors[] = "Baris {$i}: Gagal mengimpor '{$nama_pemilih}' - " . $e->getMessage();
        }
    }

    // Buat pesan ringkasan
    $message = "Proses impor selesai. Berhasil: {$success_count} data. Gagal: {$error_count} data.";
    if ($error_count > 0) {
        // Simpan log error ke file jika ada
        $log_content = "Log Impor DPT pada " . date('Y-m-d H:i:s') . "\n" . implode("\n", $log_errors);
        file_put_contents(__DIR__ . '/../../logs/import_errors.log', $log_content . "\n\n", FILE_APPEND);
        $message .= " Silakan periksa file log untuk detail kesalahan.";
    }
    
    redirect_with_message('success', $message);

} catch (Exception $e) {
    // Error saat memuat atau membaca file
    redirect_with_message('error', 'Gagal memproses file spreadsheet. Kesalahan: ' . $e->getMessage());
} finally {
    // Hapus file yang diunggah
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}
