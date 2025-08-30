<?php
/**
 * e-SPPO - Portal Web Publik
 *
 * Halaman Daftar Pemilih Tetap (DPT)
 * - Menampilkan daftar pemilih yang terdaftar untuk pemilihan.
 * - Menyediakan statistik pemilih, termasuk total, yang sudah memilih, dan persentase partisipasi.
 * - Menyediakan informasi lengkap tentang setiap pemilih, termasuk nama, jenis kelamin, konstituensi, dan status pemilih.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

require_once __DIR__ . '/../helpers/db_helper.php';
require_once __DIR__ . '/../helpers/schooldata_helper.php';

$school_config = get_school_config();
$db = get_db_connection();
$election_info = ['nama_pemilihan' => 'Pemilihan Umum'];
$stats = ['total' => 0, 'voted' => 0, 'percentage' => 0];
$voters = [];
$error_message = '';

try {
    $election_stmt = $db->query("SELECT nama_pemilihan FROM data_pemilihan LIMIT 1");
    if ($election_stmt) {
        $election_info = $election_stmt->fetch_assoc();
    }

    $stats_query = $db->query("
        SELECT COUNT(*) as total, SUM(CASE WHEN status_pemilih = 'SUDAH_MEMILIH' THEN 1 ELSE 0 END) as voted
        FROM data_pemilih
    ");
    if ($stats_query) {
        $result = $stats_query->fetch_assoc();
        $stats['total'] = $result['total'] ?? 0;
        $stats['voted'] = $result['voted'] ?? 0;
        if ($stats['total'] > 0) {
            $stats['percentage'] = ($stats['voted'] / $stats['total']) * 100;
        }
    }

    $voters_query = $db->query("
        SELECT p.nama_pemilih, p.jk_pemilih, k.nama_konstituensi, p.status_pemilih
        FROM data_pemilih p
        JOIN data_konstituensi k ON p.kk_pemilih = k.kode_konstituensi
        ORDER BY k.nama_konstituensi, p.nama_pemilih
    ");
    if ($voters_query) {
        $voters = $voters_query->fetch_all(MYSQLI_ASSOC);
    }

} catch (Exception $e) {
    $error_message = "Gagal memuat data DPT: " . $e->getMessage();
    error_log($error_message);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Pemilih Tetap - Portal e-SPPO</title>
    <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">

    <link rel="stylesheet" href="../uis/bootstrap-5.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <style>
        body {
            background-image: linear-gradient(rgba(244, 246, 249, 0.85), rgba(244, 246, 249, 0.85)), url('../assets/imgs/esppo/webportal.png');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-color: #f4f6f9;
        }
        .navbar-custom { background-color: #34568B; }
        .navbar-brand img { height: 35px; }
        .school-header { background-color: rgba(255, 255, 255, 0.9); }
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { margin-bottom: 1rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigasi -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php"><img src="../assets/imgs/esppo/esppo-logo.png" alt="Logo" style="height:35px;" class="d-inline-block align-text-top me-2"> Portal e-SPPO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="kandidat.php">Kandidat</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dpt.php">DPT</a></li>
                    <li class="nav-item"><a class="nav-link" href="hasil.php">Hasil</a></li>
                    <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header Sekolah -->
    <header class="py-3 school-header border-bottom">
        <div class="container d-flex align-items-center">
            <img src="../assets/imgs/sekolah/<?= htmlspecialchars($school_config['logo'][0]['logo_sekolah'] ?? 'placeholder_sekolah.png') ?>" 
                 onerror="this.onerror=null;this.src='../assets/imgs/sekolah/placeholder_sekolah.png';"
                 alt="Logo Sekolah" style="height: 50px; max-width: 50px;" class="me-3">
            <div>
                <h1 class="h5 mb-0 fw-bold text-dark"><?= htmlspecialchars($school_config['nama_sekolah'] ?? 'Nama Sekolah') ?></h1>
                <p class="text-muted mb-0 small">Sistem Penyelenggaraan Pemilihan OSIS Elektronik</p>
            </div>
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="flex-grow-1 py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold">Daftar Pemilih Tetap (DPT)</h2>
                <p class="lead text-muted">Data pemilih terdaftar untuk <?= htmlspecialchars($election_info['nama_pemilihan']) ?>.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?></div>
            <?php else: ?>
                <!-- Statistik DPT -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center text-center gy-3">
                            <div class="col-md-4"><h5 class="text-muted mb-1">Total Pemilih</h5><p class="h2 fw-bold mb-0"><?= number_format($stats['total']) ?></p></div>
                            <div class="col-md-4"><h5 class="text-muted mb-1">Sudah Memilih</h5><p class="h2 fw-bold text-success mb-0"><?= number_format($stats['voted']) ?></p></div>
                            <div class="col-md-4"><h5 class="text-muted mb-1">Tingkat Partisipasi</h5><div class="progress" style="height: 25px;"><div class="progress-bar bg-success progress-bar-striped" role="progressbar" style="width: <?= $stats['percentage'] ?>%;" aria-valuenow="<?= $stats['percentage'] ?>" aria-valuemin="0" aria-valuemax="100"><span class="fw-bold"><?= number_format($stats['percentage'], 1) ?>%</span></div></div></div>
                        </div>
                    </div>
                </div>

                <!-- Tabel DPT -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title fw-bold"><i class="fas fa-list me-2"></i>Data Lengkap Pemilih</h3>
                    </div>
                    <div class="card-body">
                        <table id="dptTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nama Pemilih</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Konstituensi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($voters as $index => $voter): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($voter['nama_pemilih']) ?></td>
                                        <td><?= htmlspecialchars($voter['jk_pemilih']) ?></td>
                                        <td><?= htmlspecialchars($voter['nama_konstituensi']) ?></td>
                                        <td>
                                            <span class="badge <?= $voter['status_pemilih'] == 'SUDAH_MEMILIH' ? 'bg-success' : 'bg-warning' ?>">
                                                <?= str_replace('_', ' ', $voter['status_pemilih']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white mt-auto py-3 border-top">
        <div class="container text-center">
            <p class="text-muted mb-0 small">&copy; <?= date("Y") ?> OSIS SMA Negeri 1 Bati-Bati.</p>
        </div>
    </footer>

    <script src="../uis/adminlte-3.2.0/plugins/jquery/jquery.min.js"></script>
    <script src="../uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
    <script src="../uis/adminlte-3.2.0/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../uis/adminlte-3.2.0/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="../uis/adminlte-3.2.0/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../uis/adminlte-3.2.0/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script>
        $(function () {
            $("#dptTable").DataTable({
                "responsive": true, "lengthChange": true, "autoWidth": false,
                "pageLength": 25, "language": { "url": "../uis/adminlte-3.2.0/plugins/datatables/id.json" }
            });
        });
    </script>
</body>
</html>
