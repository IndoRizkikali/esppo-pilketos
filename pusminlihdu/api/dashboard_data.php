<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - API Data Dashboard
 * pusminlihdu/api/dashboard_data.php
 *
 * Menyediakan data statistik dan pemilihan untuk dashboard
 * dalam format JSON.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 */

header('Content-Type: application/json');

session_name('eSPPO_PAPT_V2');
session_start();

// Penjaga Keamanan: Pastikan pengguna sudah login sebagai PENGELOLA atau PENGAWAS
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !in_array($_SESSION['admin_role'], ['PENGELOLA', 'PENGAWAS'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../helpers/db_helper.php';

$db = get_db_connection();
$response = [
    'stats' => [
        'total_dpt' => 0, 'total_kandidat' => 0, 'total_suara_masuk' => 0,
        'total_kehadiran' => 0, 'partisipasi_suara' => 0.0, 'partisipasi_hadir' => 0.0,
    ],
    'election_data' => null,
    'vote_distribution' => [],
    'recent_logins' => []
];

try {
    // Statistik umum
    $response['stats']['total_dpt'] = $db->query("SELECT COUNT(*) FROM data_pemilih")->fetch_row()[0] ?? 0;
    $response['stats']['total_kandidat'] = $db->query("SELECT COUNT(*) FROM data_kandidat")->fetch_row()[0] ?? 0;
    $response['stats']['total_suara_masuk'] = $db->query("SELECT COUNT(*) FROM data_suara")->fetch_row()[0] ?? 0;
    $response['stats']['total_kehadiran'] = $db->query("SELECT COUNT(*) FROM data_kehadiran")->fetch_row()[0] ?? 0;

    if ($response['stats']['total_dpt'] > 0) {
        $response['stats']['partisipasi_suara'] = ($response['stats']['total_suara_masuk'] / $response['stats']['total_dpt']) * 100;
        $response['stats']['partisipasi_hadir'] = ($response['stats']['total_kehadiran'] / $response['stats']['total_dpt']) * 100;
    }

    // Data pemilihan
    $result_election = $db->query("SELECT * FROM data_pemilihan LIMIT 1");
    if ($result_election) {
        $response['election_data'] = $result_election->fetch_assoc();
    }

    // Distribusi suara
    $result_votes = $db->query("SELECT k.no_urut_kandidat, k.nama_calon_1, COUNT(s.kode_id_suara) as jumlah_suara FROM data_kandidat k LEFT JOIN data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat GROUP BY k.no_urut_kandidat ORDER BY k.no_urut_kandidat ASC");
    if ($result_votes) {
        $response['vote_distribution'] = $result_votes->fetch_all(MYSQLI_ASSOC);
    }

    // Aktivitas login terakhir (hanya untuk PENGELOLA)
    if ($_SESSION['admin_role'] === 'PENGELOLA') {
        $result_logins = $db->query("SELECT nama_akun_admin, waktu_masuk_terakhir FROM akun_administrasi WHERE waktu_masuk_terakhir IS NOT NULL ORDER BY waktu_masuk_terakhir DESC LIMIT 5");
        if ($result_logins) {
            $response['recent_logins'] = $result_logins->fetch_all(MYSQLI_ASSOC);
        }
    }

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    $response['error'] = "Gagal mengambil data: " . $e->getMessage();
}

echo json_encode($response);
?>
