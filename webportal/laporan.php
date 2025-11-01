<?php
/**
 * e-SPPO - Portal Web Publik
 * Halaman Laporan Pemilihan
 * webportal/laporan.php
 * 
 * - Menyediakan akses ke laporan resmi pemilihan, termasuk daftar kandidat, DPT, dan hasil pemilihan.
 * - Menampilkan informasi terbaru tentang laporan yang tersedia untuk diunduh.
 * - Memungkinkan pengguna untuk mengunduh laporan dalam format PDF.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

require_once __DIR__ . '/../helpers/schooldata_helper.php';

$school_config = get_school_config();

// Konfigurasi jenis laporan dan direktori penyimpanan yang baru
$report_types = [
    'laporan_daftar_kandidat' => [
        'title' => 'Laporan Daftar Kandidat',
        'description' => 'Dokumen resmi yang berisi daftar semua calon/pasangan calon yang berpartisipasi.',
        'icon' => 'fas fa-users',
        'color' => 'primary',
        'latest_file' => null,
        'latest_time' => 0
    ],
    'laporan_dpt' => [
        'title' => 'Laporan Daftar Pemilih Tetap',
        'description' => 'Dokumen resmi yang memuat seluruh konstituensi dan daftar pemilih yang berhak memberikan suara.',
        'icon' => 'fas fa-list-ol',
        'color' => 'info',
        'latest_file' => null,
        'latest_time' => 0
    ],
    'laporan_pemilihan' => [
        'title' => 'Laporan Hasil Pemilihan',
        'description' => 'Berita acara resmi dan rincian lengkap perolehan suara serta statistik akhir pemilihan.',
        'icon' => 'fas fa-poll',
        'color' => 'success',
        'latest_file' => null,
        'latest_time' => 0
    ]
];
// Mengarahkan ke direktori yang benar sesuai instruksi
$report_directory = '../assets/pdfs/laporan/';
$error_message = '';

try {
    if (!is_dir($report_directory)) {
        throw new Exception("Direktori laporan ('" . htmlspecialchars($report_directory) . "') tidak ditemukan.");
    }

    // Membuka direktori untuk dibaca
    $files = scandir($report_directory);
    if ($files === false) {
        throw new Exception("Tidak dapat membaca isi direktori laporan.");
    }
    
    foreach ($files as $filename) {
        // Memastikan hanya file PDF yang diproses
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'pdf') {
            continue;
        }

        // Pola Regex untuk mencocokkan format nama file: (tipe_laporan)_(timestamp).pdf
        preg_match('/^(laporan_daftar_kandidat|laporan_dpt|laporan_pemilihan)_(\d+)\.pdf$/', $filename, $matches);

        if (count($matches) === 3) {
            $type = $matches[1];
            $timestamp = (int)$matches[2];

            // Memeriksa apakah file ini adalah yang terbaru untuk jenisnya
            if (isset($report_types[$type]) && $timestamp > $report_types[$type]['latest_time']) {
                $report_types[$type]['latest_time'] = $timestamp;
                $report_types[$type]['latest_file'] = $report_directory . $filename;
            }
        }
    }
} catch (Exception $e) {
    $error_message = "Gagal memuat daftar laporan: " . $e->getMessage();
    error_log($error_message);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pemilihan - Portal e-SPPO</title>
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
                    <li class="nav-item"><a class="nav-link" href="hasil.php">Hasil</a></li>
                    <li class="nav-item"><a class="nav-link active" href="laporan.php">Laporan</a></li>
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
                <h2 class="display-6 fw-bold">Arsip Laporan Resmi</h2>
                <p class="lead text-muted">Unduh dokumen-dokumen resmi terbaru yang dipublikasikan oleh panitia pemilihan.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?></div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($report_types as $type => $details): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center d-flex flex-column">
                                <div class="mb-3 fs-1 text-<?= $details['color'] ?>"><i class="<?= $details['icon'] ?>"></i></div>
                                <h5 class="card-title fw-bold"><?= $details['title'] ?></h5>
                                <p class="card-text text-muted small flex-grow-1"><?= $details['description'] ?></p>
                                <?php if ($details['latest_file']): ?>
                                    <a href="<?= htmlspecialchars($details['latest_file']) ?>" class="btn btn-<?= $details['color'] ?>" target="_blank" download>
                                        <i class="fas fa-download me-2"></i>Unduh Laporan
                                    </a>
                                    <small class="text-muted mt-2">
                                        Diperbarui: <?= date('d F Y, H:i', $details['latest_time']) ?>
                                    </small>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-times-circle me-2"></i>Belum Tersedia
                                    </button>
                                    <small class="text-muted mt-2">Laporan untuk jenis ini belum ditemukan.</small>
                                <?php endif; ?>
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
