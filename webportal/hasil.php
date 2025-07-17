<?php
require_once __DIR__ . '/../helpers/db_helper.php';
$db = get_db_connection();
$election_status = null;
$stats = [];
$vote_results = [];
try {
    $result_status = $db->query("SELECT status_pemilihan FROM data_pemilihan LIMIT 1");
    if ($result_status) $election_status = $result_status->fetch_assoc()['status_pemilihan'] ?? null;

    if ($election_status === 'SELESAI_DILAKSANAKAN') {
        $stats['total_dpt'] = $db->query("SELECT COUNT(*) FROM data_pemilih")->fetch_row()[0] ?? 0;
        $stats['total_suara_masuk'] = $db->query("SELECT COUNT(*) FROM data_suara")->fetch_row()[0] ?? 0;
        $stats['partisipasi_persen'] = ($stats['total_dpt'] > 0) ? ($stats['total_suara_masuk'] / $stats['total_dpt']) * 100 : 0;
        $query_votes = "SELECT k.no_urut_kandidat, k.nama_calon_1, COUNT(s.kode_id_suara) AS jumlah_suara FROM data_kandidat k LEFT JOIN data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat GROUP BY k.no_urut_kandidat ORDER BY k.no_urut_kandidat ASC";
        $result_votes = $db->query($query_votes);
        if ($result_votes) $vote_results = $result_votes->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) { /* Abaikan error */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Pemilihan - Portal e-SPPO</title>
    <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">
    <link href="../uis/bootstrap-5.3.7/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-image: linear-gradient(rgba(248, 249, 250, 0.9), rgba(248, 249, 250, 0.9)), url('../assets/imgs/esppo/webportal.png'); background-size: cover; background-attachment: fixed; }</style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigasi -->
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm sticky-top"><div class="container"><a class="navbar-brand" href="../index.php">e-SPPO Portal</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav ms-auto"><li class="nav-item"><a href="../index.php" class="nav-link">Beranda</a></li><li class="nav-item"><a href="kandidat.php" class="nav-link">Kandidat</a></li><li class="nav-item"><a href="dpt.php" class="nav-link">DPT</a></li><li class="nav-item"><a href="hasil.php" class="nav-link active">Hasil</a></li></ul></div></div></nav>

    <!-- Konten Utama -->
    <main class="flex-grow-1 py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="fw-bold">Hasil Pemilihan</h1>
                <p class="lead text-muted">Hasil akhir perolehan suara dari kegiatan pemilihan.</p>
            </div>
            <?php if ($election_status === 'SELESAI_DILAKSANAKAN'): ?>
                <div class="row mb-4 text-center">
                    <div class="col-md-4 mb-3"><div class="card shadow-sm p-3"><h5>Total DPT</h5><p class="h2 fw-bold text-primary"><?= number_format($stats['total_dpt']) ?></p></div></div>
                    <div class="col-md-4 mb-3"><div class="card shadow-sm p-3"><h5>Suara Masuk</h5><p class="h2 fw-bold text-success"><?= number_format($stats['total_suara_masuk']) ?></p></div></div>
                    <div class="col-md-4 mb-3"><div class="card shadow-sm p-3"><h5>Partisipasi</h5><p class="h2 fw-bold text-info"><?= number_format($stats['partisipasi_persen'], 2) ?>%</p></div></div>
                </div>
                <div class="card shadow-sm"><div class="card-header"><h5 class="card-title mb-0">Grafik Perolehan Suara</h5></div><div class="card-body"><canvas id="voteChart"></canvas></div></div>
            <?php else: ?>
                <div class="card shadow-sm text-center p-5"><h3 class="text-warning">Hasil Belum Tersedia</h3><p class="text-muted">Perolehan suara akan ditampilkan secara publik setelah seluruh rangkaian kegiatan pemilihan selesai dilaksanakan.</p></div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white mt-auto py-3 border-top"><div class="container text-center"><p class="text-muted mb-0">&copy; <?= date("Y") ?> Panitia Pemilihan.</p></div></footer>
    
    <script src="../uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
    <script src="../uis/chart.js/chart.umd.js"></script>
    <script>
        <?php if ($election_status === 'SELESAI_DILAKSANAKAN'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('voteChart');
            const voteData = <?= json_encode($vote_results) ?>;
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: voteData.map(row => `No. ${row.no_urut_kandidat}: ${row.nama_calon_1}`),
                    datasets: [{ label: 'Jumlah Suara', data: voteData.map(row => row.jumlah_suara), backgroundColor: 'rgba(0, 123, 255, 0.5)', borderColor: 'rgba(0, 123, 255, 1)', borderWidth: 1 }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
