<?php
/**
 * e-SPPO - Portal Web Publik
 * Halaman Hasil Pemilihan
 * webportal/hasil.php
 * 
 * - Menampilkan hasil akhir pemilihan, termasuk statistik partisipasi dan perolehan suara.
 * - Menyediakan rincian perolehan suara per kandidat.
 * - Menampilkan grafik perolehan suara dan tingkat partisipasi pemilih.
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
$election_info = ['nama_pemilihan' => 'Pemilihan Umum', 'status_pemilihan' => 'BELUM_DIMULAI'];
$stats = ['total_dpt' => 0, 'total_suara_masuk' => 0, 'partisipasi_persen' => 0];
$vote_results = [];
$error_message = '';

try {
    $election_stmt = $db->query("SELECT nama_pemilihan, status_pemilihan FROM data_pemilihan LIMIT 1");
    if ($election_stmt) {
        $election_info = $election_stmt->fetch_assoc();
    }

    if ($election_info['status_pemilihan'] === 'SELESAI_DILAKSANAKAN') {
        $stats_res = $db->query("SELECT COUNT(*) as total FROM data_pemilih")->fetch_assoc();
        $stats['total_dpt'] = $stats_res['total'] ?? 0;
        
        $vote_res = $db->query("SELECT COUNT(*) as total FROM data_suara")->fetch_assoc();
        $stats['total_suara_masuk'] = $vote_res['total'] ?? 0;

        if ($stats['total_dpt'] > 0) {
            $stats['partisipasi_persen'] = ($stats['total_suara_masuk'] / $stats['total_dpt']) * 100;
        }

        $query_votes = "
            SELECT k.no_urut_kandidat, k.nama_calon_1, k.nama_calon_2, k.foto_kandidat, COUNT(s.kode_id_suara) AS jumlah_suara
            FROM data_kandidat k
            LEFT JOIN data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat
            GROUP BY k.no_urut_kandidat
            ORDER BY jumlah_suara DESC";
        
        $result_votes = $db->query($query_votes);
        if ($result_votes) {
            $vote_results = $result_votes->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    $error_message = "Gagal memuat data hasil pemilihan: " . $e->getMessage();
    error_log($error_message);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Pemilihan - Portal e-SPPO</title>
    <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">

    <link rel="stylesheet" href="../uis/bootstrap-5.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/fontawesome-free/css/all.min.css">
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
        .winner-card { border: 3px solid #28a745; }
        .winner-card .card-header { background-color: #28a745; color: white; }
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
                    <li class="nav-item"><a class="nav-link" href="dpt.php">DPT</a></li>
                    <li class="nav-item"><a class="nav-link active" href="hasil.php">Hasil</a></li>
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
                <h2 class="display-6 fw-bold">Hasil Akhir Pemilihan</h2>
                <p class="lead text-muted">Perolehan suara resmi <?= htmlspecialchars($election_info['nama_pemilihan']) ?>.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?></div>
            <?php elseif ($election_info['status_pemilihan'] !== 'SELESAI_DILAKSANAKAN'): ?>
                <div class="alert alert-info text-center p-4">
                    <h4 class="alert-heading"><i class="fas fa-hourglass-half me-2"></i>Hasil Belum Tersedia</h4>
                    <p class="mb-0">Hasil perolehan suara akan ditampilkan setelah proses pemilihan selesai.</p>
                </div>
            <?php else: ?>
                <!-- Statistik Partisipasi -->
                <div class="card shadow-sm mb-5">
                    <div class="card-body text-center p-4">
                        <h5 class="card-title">Tingkat Partisipasi Pemilih</h5>
                        <p class="display-4 fw-bold text-primary"><?= number_format($stats['partisipasi_persen'], 2) ?>%</p>
                        <p class="text-muted mb-0"><?= number_format($stats['total_suara_masuk']) ?> dari <?= number_format($stats['total_dpt']) ?> pemilih telah memberikan suara.</p>
                    </div>
                </div>

                <!-- Grafik Hasil -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header"><h3 class="card-title fw-bold"><i class="fas fa-chart-bar me-2"></i>Grafik Perolehan Suara</h3></div>
                    <div class="card-body"><canvas id="voteChart" style="min-height: 300px; height: 300px; max-height: 300px; width: 100%;"></canvas></div>
                </div>

                <!-- Rincian per Kandidat -->
                <h3 class="text-center mb-4 fw-bold">Rincian Suara per Kandidat</h3>
                <div class="row g-4 justify-content-center">
                    <?php foreach ($vote_results as $index => $result): 
                        $percentage = $stats['total_suara_masuk'] > 0 ? ($result['jumlah_suara'] / $stats['total_suara_masuk']) * 100 : 0;
                        $card_class = ($index === 0) ? 'winner-card' : '';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm text-center <?= $card_class ?>">
                            <?php if ($index === 0): ?>
                                <div class="card-header fw-bold"><i class="fas fa-trophy me-2"></i>PEROLEHAN SUARA TERTINGGI</div>
                            <?php endif; ?>
                            <div class="card-body p-4">
                                <img src="../assets/imgs/sse-foto-kandidat/<?= htmlspecialchars($result['foto_kandidat'] ?? 'default.png') ?>" class="rounded-circle mb-3 border border-3" alt="Foto" style="width: 120px; height: 120px; object-fit: cover;">
                                <h5 class="card-title">No. Urut <?= htmlspecialchars($result['no_urut_kandidat']) ?></h5>
                                <p class="fw-bold mb-1"><?= htmlspecialchars($result['nama_calon_1']) ?></p>
                                <?php if(!empty($result['nama_calon_2'])): ?><p class="text-muted small">& <?= htmlspecialchars($result['nama_calon_2']) ?></p><?php endif; ?>
                                <h3 class="fw-bold text-primary mt-3"><?= number_format($result['jumlah_suara']) ?> Suara</h3>
                                <p class="text-muted">(<?= number_format($percentage, 2) ?>%)</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
    <script src="../uis/adminlte-3.2.0/plugins/chart.js/Chart.min.js"></script>
    <script>
        <?php if ($election_info['status_pemilihan'] === 'SELESAI_DILAKSANAKAN' && !empty($vote_results)): ?>
        $(function () {
            var voteData = <?= json_encode(array_reverse($vote_results)) ?>;
            var labels = voteData.map(item => `No. ${item.no_urut_kandidat}: ${item.nama_calon_1}`);
            var data = voteData.map(item => item.jumlah_suara);
            new Chart($('#voteChart').get(0).getContext('2d'), { 
                type: 'bar', 
                data: { labels: labels, datasets: [{ label: 'Jumlah Suara', backgroundColor: 'rgba(52, 86, 139, 0.9)', borderColor: 'rgba(52, 86, 139, 1)', borderWidth: 1, data: data }] }, 
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } } 
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
