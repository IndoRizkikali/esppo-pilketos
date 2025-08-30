<?php
/**
 * e-SPPO - Portal Web Publik
 *
 * Halaman Daftar Kandidat
 * - Menampilkan daftar kandidat yang berpartisipasi dalam pemilihan.
 * - Menyediakan informasi lengkap tentang setiap kandidat, termasuk visi, misi, dan foto.
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
$election_info = ['nama_pemilihan' => 'Pemilihan Umum', 'tipe_peserta_pemilihan' => 'BERPASANGAN'];
$candidates = [];
$error_message = '';

try {
    $election_stmt = $db->query("SELECT nama_pemilihan, tipe_peserta_pemilihan FROM data_pemilihan LIMIT 1");
    if ($election_stmt) {
        $election_info = $election_stmt->fetch_assoc();
    }

    $query = "
        SELECT 
            k.no_urut_kandidat, k.nama_calon_1, k.nama_calon_2, 
            k.visi_kandidat, k.misi_kandidat, k.foto_kandidat,
            c1.nama_konstituensi AS konstituensi_calon_1,
            c2.nama_konstituensi AS konstituensi_calon_2
        FROM data_kandidat k
        LEFT JOIN data_konstituensi c1 ON k.kk_calon_1 = c1.kode_konstituensi
        LEFT JOIN data_konstituensi c2 ON k.kk_calon_2 = c2.kode_konstituensi
        ORDER BY k.no_urut_kandidat";
    
    $result = $db->query($query);
    if ($result) {
        $candidates = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $error_message = "Gagal memuat data kandidat: " . $e->getMessage();
    error_log($error_message);
}

function format_misi($misi_text) {
    $misi_items = explode("\n", trim($misi_text ?? ''));
    $output = '<ul class="list-unstyled mb-0">';
    foreach ($misi_items as $item) {
        if (!empty(trim($item))) {
            $output .= '<li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>' . htmlspecialchars(trim($item)) . '</li>';
        }
    }
    $output .= '</ul>';
    return $output;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Kandidat - Portal e-SPPO</title>
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
        .candidate-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .candidate-card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigasi -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php"><img src="../assets/imgs/esppo/esppo-logo.png" alt="Logo" class="d-inline-block align-text-top me-2"> Portal e-SPPO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link active" href="kandidat.php">Kandidat</a></li>
                    <li class="nav-item"><a class="nav-link" href="dpt.php">DPT</a></li>
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
                <h2 class="display-6 fw-bold">Daftar Kandidat</h2>
                <p class="lead text-muted">Kenali para calon pemimpin Anda dalam <?= htmlspecialchars($election_info['nama_pemilihan']) ?>.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?></div>
            <?php elseif (empty($candidates)): ?>
                <div class="alert alert-info text-center"><i class="fas fa-info-circle me-2"></i>Belum ada data kandidat yang tersedia untuk ditampilkan.</div>
            <?php else: ?>
                <div class="row g-4 justify-content-center">
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="col-lg-10">
                            <div class="card h-100 shadow-sm candidate-card">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-user-tie me-2"></i>
                                        Nomor Urut <?= htmlspecialchars($candidate['no_urut_kandidat']) ?>
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-4 align-items-center">
                                        <div class="col-md-3 text-center">
                                            <img src="../assets/imgs/sse-foto-kandidat/<?= htmlspecialchars($candidate['foto_kandidat'] ?? 'default.png') ?>" 
                                                 class="img-fluid rounded shadow-sm border border-3" alt="Foto Kandidat">
                                        </div>
                                        <div class="col-md-9">
                                            <h4 class="fw-bold text-primary">
                                                <?= htmlspecialchars($candidate['nama_calon_1']) ?>
                                            </h4>
                                            <?php if ($election_info['tipe_peserta_pemilihan'] === 'BERPASANGAN' && !empty($candidate['nama_calon_2'])): ?>
                                                <h5 class="fw-bold text-muted">& <?= htmlspecialchars($candidate['nama_calon_2']) ?></h5>
                                            <?php endif; ?>
                                            <p class="text-muted">
                                                <small>
                                                    Asal: <?= htmlspecialchars($candidate['konstituensi_calon_1']) ?>
                                                    <?php if ($election_info['tipe_peserta_pemilihan'] === 'BERPASANGAN' && !empty($candidate['konstituensi_calon_2'])): ?>
                                                        & <?= htmlspecialchars($candidate['konstituensi_calon_2']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </p>
                                            <hr>
                                            <h6 class="fw-bold">Visi:</h6>
                                            <p class="fst-italic">"<?= htmlspecialchars($candidate['visi_kandidat']) ?>"</p>
                                            <h6 class="fw-bold mt-3">Misi:</h6>
                                            <?= format_misi($candidate['misi_kandidat']) ?>
                                        </div>
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
    <footer class="bg-white mt-auto py-3 border-top">
        <div class="container text-center">
            <p class="text-muted mb-0 small">&copy; <?= date("Y") ?> OSIS SMA Negeri 1 Bati-Bati.</p>
        </div>
    </footer>

    <script src="../uis/adminlte-3.2.0/plugins/jquery/jquery.min.js"></script>
    <script src="../uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
</body>
</html>
