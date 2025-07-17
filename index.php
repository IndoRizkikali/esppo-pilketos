<?php
/**
 * e-SPPO - Portal Web
 *
 * Halaman Utama / Landing Page.
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

require_once __DIR__ . '/helpers/db_helper.php';
require_once __DIR__ . '/helpers/schooldata_helper.php';

$school_config = get_school_config();
$db = get_db_connection();
$election_status_info = ['type' => 'warning', 'message' => 'Informasi pemilihan tidak tersedia saat ini.'];

try {
    $stmt = $db->query("SELECT nama_pemilihan, tanggal_mulai, status_pemilihan FROM data_pemilihan LIMIT 1");
    $election = $stmt->fetch_assoc();

    if ($election) {
        $nama_pemilihan = htmlspecialchars($election['nama_pemilihan']);
        $tanggal_mulai_dt = new DateTime($election['tanggal_mulai']);
        $sekarang_dt = new DateTime();
        
        $fmt = new IntlDateFormatter('id_ID', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        $tanggal_mulai_str = $fmt->format($tanggal_mulai_dt);

        switch ($election['status_pemilihan']) {
            case 'SEDANG_BERLANSUNG':
                $election_status_info = ['type' => 'success', 'message' => "<strong>Pemilihan Sedang Berlangsung!</strong> {$nama_pemilihan} sedang dilaksanakan. Silakan masuk untuk memberikan suara Anda."];
                break;
            case 'SELESAI_DILAKSANAKAN':
                $election_status_info = ['type' => 'info', 'message' => "<strong>Pemilihan Telah Selesai.</strong> Terima kasih atas partisipasi Anda dalam {$nama_pemilihan}."];
                break;
            case 'BELUM_DIMULAI':
            default:
                $interval = $sekarang_dt->diff($tanggal_mulai_dt);
                $hari_lagi = $interval->days + 1;
                $election_status_info = ['type' => 'info', 'message' => "<strong>Informasi Pemilihan!</strong> {$nama_pemilihan} akan dilaksanakan pada {$tanggal_mulai_str} ({$hari_lagi} hari lagi)."];
                break;
        }
    }
} catch (Exception $e) {
    error_log("Portal Index Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal e-SPPO - <?= htmlspecialchars($school_config['nama_sekolah'] ?? 'Sekolah') ?></title>
    <link rel="icon" type="image/png" href="assets/imgs/esppo/esppo-logo.png">
    <!-- Aset Lokal -->
    <link href="uis/bootstrap-5.3.7/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: linear-gradient(rgba(248, 249, 250, 0.85), rgba(248, 249, 250, 0.85)), url('assets/imgs/esppo/webportal.png');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
        }
        .navbar-brand img { height: 40px; }
        .header-section { background: rgba(255, 255, 255, 0.9); }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigasi -->
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/imgs/esppo/esppo-logo.png" alt="Logo e-SPPO">
                e-SPPO Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a href="index.php" class="nav-link active">Beranda</a></li>
                    <li class="nav-item"><a href="webportal/kandidat.php" class="nav-link">Kandidat</a></li>
                    <li class="nav-item"><a href="webportal/dpt.php" class="nav-link">DPT</a></li>
                    <li class="nav-item"><a href="webportal/hasil.php" class="nav-link">Hasil</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header Sekolah -->
    <header class="py-4 header-section border-bottom">
        <div class="container">
            <div class="d-flex align-items-center">
                <img src="assets/imgs/sekolah/<?= htmlspecialchars($school_config['logo'][0]['logo_sekolah'] ?? '') ?>" alt="Logo Sekolah" style="height: 60px;" class="me-3">
                <div>
                    <h1 class="h4 mb-0 fw-bold"><?= htmlspecialchars($school_config['nama_sekolah'] ?? 'Nama Sekolah') ?></h1>
                    <p class="text-muted mb-0">Portal Informasi Pemilihan OSIS Elektronik</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="flex-grow-1 py-5">
        <div class="container">
            <!-- Notifikasi Status Pemilihan -->
            <div class="alert alert-<?= $election_status_info['type'] ?> d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill flex-shrink-0 me-2"></i>
                <div><?= $election_status_info['message'] ?></div>
            </div>

            <!-- Konten Hero -->
            <div class="p-5 bg-white rounded-3 shadow-sm text-center">
                <h2 class="display-5 fw-bold text-primary mb-3">Gunakan Hak Pilih Anda</h2>
                <p class="lead mb-4 text-muted">Satu suara Anda menentukan masa depan organisasi. Silakan masuk ke sistem yang sesuai untuk berpartisipasi.</p>
                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                    <a href="sse/" class="btn btn-primary btn-lg px-5">Masuk sebagai Pemilih</a>
                    <a href="pusminlihdu/" class="btn btn-outline-secondary btn-lg px-5">Masuk sebagai Panitia</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white mt-auto py-3 border-top">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?= date("Y") ?> Panitia Pemilihan & Tim Pengembang e-SPPO.</p>
        </div>
    </footer>

    <script src="uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
</body>
</html>
