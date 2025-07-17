<?php
require_once __DIR__ . '/../helpers/db_helper.php';
$db = get_db_connection();
$candidates = [];
$election_settings = null;
try {
    $result_settings = $db->query("SELECT tipe_peserta_pemilihan FROM data_pemilihan LIMIT 1");
    if ($result_settings) $election_settings = $result_settings->fetch_assoc();
    $query = "SELECT k.*, kon1.nama_konstituensi AS konstituensi1, kon2.nama_konstituensi AS konstituensi2 FROM data_kandidat k LEFT JOIN data_konstituensi kon1 ON k.kk_calon_1 = kon1.kode_konstituensi LEFT JOIN data_konstituensi kon2 ON k.kk_calon_2 = kon2.kode_konstituensi ORDER BY k.no_urut_kandidat ASC";
    $result = $db->query($query);
    if ($result) $candidates = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) { /* Abaikan error untuk publik */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Kandidat - Portal e-SPPO</title>
    <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">
    <link href="../uis/bootstrap-5.3.7/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-image: linear-gradient(rgba(248, 249, 250, 0.9), rgba(248, 249, 250, 0.9)), url('../assets/imgs/esppo/webportal.png'); background-size: cover; background-attachment: fixed; }</style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigasi -->
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm sticky-top"><div class="container"><a class="navbar-brand" href="../index.php">e-SPPO Portal</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav ms-auto"><li class="nav-item"><a href="../index.php" class="nav-link">Beranda</a></li><li class="nav-item"><a href="kandidat.php" class="nav-link active">Kandidat</a></li><li class="nav-item"><a href="dpt.php" class="nav-link">DPT</a></li><li class="nav-item"><a href="hasil.php" class="nav-link">Hasil</a></li></ul></div></div></nav>

    <!-- Konten Utama -->
    <main class="flex-grow-1 py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="fw-bold">Daftar Kandidat</h1>
                <p class="lead text-muted">Kenali para kandidat yang akan berkompetisi dalam pemilihan ini.</p>
            </div>
            <?php if (empty($candidates)): ?>
                <div class="card shadow-sm text-center p-5"><p class="text-muted mb-0">Belum ada data kandidat yang dipublikasikan.</p></div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-lg-2 g-4">
                    <?php foreach ($candidates as $candidate): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-dark text-white d-flex align-items-center">
                                <span class="badge bg-light text-dark fs-4 me-3"><?= htmlspecialchars($candidate['no_urut_kandidat']) ?></span>
                                <h5 class="mb-0"><?= htmlspecialchars($candidate['nama_calon_1']) ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center"><img src="../assets/imgs/sse-foto-kandidat/<?= htmlspecialchars($candidate['foto_kandidat']) ?>" class="img-fluid rounded mb-3" alt="Foto Kandidat" onerror="this.onerror=null; this.src='https://placehold.co/200x250/e0e0e0/757575?text=Foto';"></div>
                                    <div class="col-md-8">
                                        <p class="mb-1"><strong>Calon Ketua:</strong> <?= htmlspecialchars($candidate['nama_calon_1']) ?></p>
                                        <p class="text-muted"><small>Asal Konstituensi: <?= htmlspecialchars($candidate['konstituensi1']) ?></small></p>
                                        <?php if (($election_settings['tipe_peserta_pemilihan'] ?? '') === 'BERPASANGAN' && !empty($candidate['nama_calon_2'])): ?>
                                        <hr><p class="mb-1"><strong>Calon Wakil Ketua:</strong> <?= htmlspecialchars($candidate['nama_calon_2']) ?></p>
                                        <p class="text-muted"><small>Asal Konstituensi: <?= htmlspecialchars($candidate['konstituensi2']) ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                </div><hr>
                                <h6><strong>Visi & Misi</strong></h6>
                                <div style="font-size: 0.9rem; max-height: 150px; overflow-y: auto;">
                                    <p><strong>Visi:</strong> <?= htmlspecialchars($candidate['visi_kandidat'] ?? 'Tidak diatur.') ?></p>
                                    <p><strong>Misi:</strong><br><?= nl2br(htmlspecialchars($candidate['misi_kandidat'] ?? 'Tidak diatur.')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white mt-auto py-3 border-top"><div class="container text-center"><p class="text-muted mb-0">&copy; <?= date("Y") ?> Panitia Pemilihan.</p></div></footer>
    <script src="../uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
</body>
</html>
