<?php
/**
 * e-SPPO - API Data Hasil Pemilihan
 * pusminlihdu/api/hasil_pemilihan_data.php
 *
 * Menyediakan data statistik dan hasil pemilihan untuk halaman laporan
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

require_once __DIR__ . '/../../helpers/db_helper.php';

$db = get_db_connection();
$response = [
    'stats' => [
        'total_dpt' => 0,
        'total_suara_masuk' => 0,
        'belum_memilih' => 0,
        'partisipasi_persen' => 0.0,
    ],
    'vote_results' => [],
];

try {
    // Ambil statistik
    $response['stats']['total_dpt'] = $db->query("SELECT COUNT(*) FROM data_pemilih")->fetch_row()[0] ?? 0;
    $response['stats']['total_suara_masuk'] = $db->query("SELECT COUNT(*) FROM data_suara")->fetch_row()[0] ?? 0;
    
    $response['stats']['belum_memilih'] = $response['stats']['total_dpt'] - $response['stats']['total_suara_masuk'];
    if ($response['stats']['total_dpt'] > 0) {
        $response['stats']['partisipasi_persen'] = ($response['stats']['total_suara_masuk'] / $response['stats']['total_dpt']) * 100;
    }

    // Ambil hasil suara per kandidat
    $query_votes = "
        SELECT 
            k.no_urut_kandidat,
            k.nama_calon_1,
            k.foto_kandidat,
            COUNT(s.kode_id_suara) AS jumlah_suara
        FROM data_kandidat k
        LEFT JOIN data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat
        GROUP BY k.no_urut_kandidat
        ORDER BY k.no_urut_kandidat ASC";
    
    $result_votes = $db->query($query_votes);
    if ($result_votes) {
        $response['vote_results'] = $result_votes->fetch_all(MYSQLI_ASSOC);
    }

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    $response['error'] = "Gagal mengambil data: " . $e->getMessage();
}

echo json_encode($response);
?>
