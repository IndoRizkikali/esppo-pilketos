<?php
/**
 * e-SPPO - API Data Rincian Hasil Pemilihan
 * pusminlihdu/api/rincian_hasil_pemilihan_data.php
 *
 * Menyediakan data rincian hasil pemilihan per konstituensi
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

// Penjaga Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !in_array($_SESSION['admin_role'], ['PENGELOLA', 'PENGAWAS'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit();
}

require_once __DIR__ . '/../../helpers/db_helper.php';

$db = get_db_connection();
$selected_constituency_code = $_GET['konstituensi'] ?? null;

if (!$selected_constituency_code) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter konstituensi tidak ditemukan.']);
    exit();
}

$response = [
    'stats' => [
        'total_dpt' => 0,
        'total_suara_masuk' => 0,
        'partisipasi_persen' => 0.0,
    ],
    'vote_results' => [],
];

try {
    // Ambil statistik dengan filter
    $stmt_dpt = $db->prepare("SELECT COUNT(*) FROM data_pemilih WHERE kk_pemilih = ?");
    $stmt_dpt->bind_param('s', $selected_constituency_code);
    $stmt_dpt->execute();
    $response['stats']['total_dpt'] = $stmt_dpt->get_result()->fetch_row()[0] ?? 0;
    $stmt_dpt->close();

    $stmt_suara = $db->prepare("SELECT COUNT(*) FROM data_suara WHERE kk_pemilih = ?");
    $stmt_suara->bind_param('s', $selected_constituency_code);
    $stmt_suara->execute();
    $response['stats']['total_suara_masuk'] = $stmt_suara->get_result()->fetch_row()[0] ?? 0;
    $stmt_suara->close();

    if ($response['stats']['total_dpt'] > 0) {
        $response['stats']['partisipasi_persen'] = ($response['stats']['total_suara_masuk'] / $response['stats']['total_dpt']) * 100;
    }

    // Ambil perolehan suara dengan filter
    $query_votes = "
        SELECT k.no_urut_kandidat, k.nama_calon_1, COUNT(s.kode_id_suara) AS jumlah_suara
        FROM data_kandidat k
        LEFT JOIN data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat AND s.kk_pemilih = ?
        GROUP BY k.no_urut_kandidat ORDER BY k.no_urut_kandidat ASC";
    $stmt_votes = $db->prepare($query_votes);
    $stmt_votes->bind_param('s', $selected_constituency_code);
    $stmt_votes->execute();
    $result_votes = $stmt_votes->get_result();
    if ($result_votes) {
        $response['vote_results'] = $result_votes->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_votes->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = "Gagal mengambil data: " . $e->getMessage();
}

echo json_encode($response);
?>
