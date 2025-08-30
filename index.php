<?php
/**
 * e-SPPO - Portal Web Publik
 *
 * Halaman Utama / Landing Page.
 * - Menampilkan informasi pemilihan terkini.
 * - Menyediakan tautan ke halaman kandidat, DPT, hasil pemilihan
 * - Menyediakan tautan untuk login pemilih dan panitia.
 * - Menampilkan status pemilihan saat ini.
 * - Menyediakan informasi tentang sistem e-SPPO.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

require_once __DIR__ . '/helpers/db_helper.php';
require_once __DIR__ . '/helpers/schooldata_helper.php';

$school_config = get_school_config();
$db = get_db_connection();
$election_info = ['nama_pemilihan' => 'Pemilihan Umum'];

// Inisialisasi status default
$status = [
    'type' => 'secondary',
    'icon' => 'fa-question-circle',
    'title' => 'Informasi Tidak Tersedia',
    'message' => 'Saat ini belum ada jadwal pemilihan yang ditetapkan oleh panitia.'
];

try {
    $stmt = $db->query("SELECT nama_pemilihan, tanggal_mulai, status_pemilihan FROM data_pemilihan LIMIT 1");
    $election = $stmt->fetch_assoc();

    if ($election) {
        $election_info['nama_pemilihan'] = $election['nama_pemilihan'];
        $nama_pemilihan = htmlspecialchars($election['nama_pemilihan']);
        $tanggal_mulai_dt = new DateTime($election['tanggal_mulai']);
        $sekarang_dt = new DateTime();
        
        $fmt = new IntlDateFormatter('id_ID', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'Asia/Makassar');
        $tanggal_mulai_str = $fmt->format($tanggal_mulai_dt);

        switch ($election['status_pemilihan']) {
            case 'SEDANG_BERLANSUNG':
                $status = [
                    'type' => 'success',
                    'icon' => 'fa-vote-yea',
                    'title' => 'Pemilihan Sedang Berlangsung!',
                    'message' => "Gunakan hak suara Anda dalam <strong>{$nama_pemilihan}</strong> sekarang juga."
                ];
                break;
            case 'SELESAI_DILAKSANAKAN':
                $status = [
                    'type' => 'info',
                    'icon' => 'fa-check-circle',
                    'title' => 'Pemilihan Telah Selesai',
                    'message' => "Terima kasih atas partisipasi Anda dalam <strong>{$nama_pemilihan}</strong>. Hasil dapat dilihat pada halaman terkait."
                ];
                break;
            case 'BELUM_DIMULAI':
            default:
                if ($sekarang_dt->format('Y-m-d') > $tanggal_mulai_dt->format('Y-m-d')) {
                     $status = [
                        'type' => 'warning',
                        'icon' => 'fa-clock',
                        'title' => 'Pemilihan Ditunda',
                        'message' => "Jadwal <strong>{$nama_pemilihan}</strong> telah terlewat namun pemilihan belum dibuka. Mohon tunggu informasi lebih lanjut dari panitia."
                    ];
                } else {
                    $interval = $sekarang_dt->diff($tanggal_mulai_dt);
                    $hari_lagi = $interval->days;
                    $hari_text = ($hari_lagi == 0) ? "hari ini" : "dalam {$hari_lagi} hari lagi";
                    $status = [
                        'type' => 'primary',
                        'icon' => 'fa-calendar-alt',
                        'title' => 'Pemilihan Akan Datang',
                        'message' => "<strong>{$nama_pemilihan}</strong> akan dilaksanakan pada <strong>{$tanggal_mulai_str}</strong> ({$hari_text})."
                    ];
                }
                break;
        }
    }
} catch (Exception $e) {
    error_log("Portal Index Error: " . $e->getMessage());
     $status = [
        'type' => 'danger',
        'icon' => 'fa-server',
        'title' => 'Kesalahan Sistem',
        'message' => 'Terjadi kesalahan saat memuat data pemilihan. Silakan coba lagi nanti.'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal e-SPPO - <?= htmlspecialchars($school_config['nama_sekolah'] ?? 'Sekolah') ?></title>
    <link rel="icon" type="image/png" href="assets/imgs/esppo/esppo-logo.png">

    <link rel="stylesheet" href="uis/bootstrap-5.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="uis/adminlte-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <style>
        body {
            background-image: linear-gradient(rgba(244, 246, 249, 0.9), rgba(244, 246, 249, 0.9)), url('assets/imgs/esppo/webportal.png');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-color: #f4f6f9;
        }
        .navbar-custom { background-color: #34568B; }
        .navbar-brand img { height: 35px; }
        .school-header { background-color: rgba(255, 255, 255, 0.95); }
        .card-link { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-link:hover { transform: translateY(-5px); text-decoration: none; }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <!-- Navigasi Utama -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><img src="assets/imgs/esppo/esppo-logo.png" alt="Logo" class="d-inline-block align-text-top me-2"> Portal e-SPPO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="webportal/kandidat.php">Kandidat</a></li>
                    <li class="nav-item"><a class="nav-link" href="webportal/dpt.php">DPT</a></li>
                    <li class="nav-item"><a class="nav-link" href="webportal/hasil.php">Hasil</a></li>
                    <li class="nav-item"><a class="nav-link" href="webportal/laporan.php">Laporan</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header Sekolah -->
    <header class="py-3 school-header border-bottom">
        <div class="container d-flex align-items-center">
            <img src="assets/imgs/sekolah/<?= htmlspecialchars($school_config['logo'][0]['logo_sekolah'] ?? 'placeholder_sekolah.png') ?>" 
                 onerror="this.onerror=null;this.src='assets/imgs/sekolah/placeholder_sekolah.png';"
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
            <!-- Hero Utama -->
            <div class="p-5 mb-4 bg-light rounded-3 shadow-sm text-center">
                <h1 class="display-5 fw-bold">Selamat Datang di Portal e-SPPO</h1>
                <p class="col-lg-8 mx-auto lead">Gunakan hak pilih Anda secara bijak untuk masa depan organisasi yang lebih baik. Portal ini menyediakan semua informasi yang Anda butuhkan terkait pemilihan.</p>
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                    <a href="sse/" class="btn btn-primary btn-lg px-4 gap-3"><i class="fas fa-user-check me-2"></i>Masuk sebagai Pemilih</a>
                    <a href="pusminlihdu/" class="btn btn-outline-secondary btn-lg px-4"><i class="fas fa-user-shield me-2"></i>Login Panitia</a>
                </div>
            </div>

            <!-- Status Pemilihan -->
            <div class="card shadow-lg mb-5 border-0 border-start border-5 border-<?= $status['type'] ?>">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="fs-1 text-<?= $status['type'] ?> me-4"><i class="fas <?= $status['icon'] ?>"></i></div>
                        <div>
                            <h4 class="card-title fw-bold"><?= $status['title'] ?></h4>
                            <p class="card-text mb-0"><?= $status['message'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Navigasi -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i>Tentang e-SPPO</h5>
                            <p class="card-text">
                                e-SPPO (Sistem Penyelenggaraan Pemilihan OSIS Elektronik) adalah platform digital yang dirancang untuk memodernisasi dan menyederhanakan proses pemilihan ketua OSIS di sekolah, menjadikannya lebih efisien, transparan, dan akurat.
                            </p>
                            <p class="card-text">
                                e-SPPO terdiri dari 3 komponen utama: Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) untuk pengelolaan kegiatan pemungutan suara oleh panitia, Surat Suara Elektronik (SSE) untuk pemilih memberikan suaranya secara daring,
                                dan Portal Web Publik untuk informasi pemilihan yang dapat diakses oleh semua warga sekolah dan masyarakat umum.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <a href="webportal/kandidat.php" class="card card-link h-100 shadow-sm text-dark text-decoration-none">
                                <div class="card-body text-center"><i class="fas fa-users fa-2x text-primary mb-2"></i><h6 class="card-title fw-bold">Daftar Kandidat</h6></div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="webportal/dpt.php" class="card card-link h-100 shadow-sm text-dark text-decoration-none">
                                <div class="card-body text-center"><i class="fas fa-list-ol fa-2x text-info mb-2"></i><h6 class="card-title fw-bold">Daftar Pemilih</h6></div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="webportal/hasil.php" class="card card-link h-100 shadow-sm text-dark text-decoration-none">
                                <div class="card-body text-center"><i class="fas fa-poll fa-2x text-success mb-2"></i><h6 class="card-title fw-bold">Hasil Pemilihan</h6></div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="webportal/laporan.php" class="card card-link h-100 shadow-sm text-dark text-decoration-none">
                                <div class="card-body text-center"><i class="fas fa-file-pdf fa-2x text-danger mb-2"></i><h6 class="card-title fw-bold">Unduh Laporan</h6></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white mt-auto py-3 border-top">
        <div class="container text-center">
            <p class="text-muted mb-0 small">&copy; <?= date("Y") ?> OSIS SMA Negeri 1 Bati-Bati.</p>
        </div>
    </footer>

    <script src="uis/adminlte-3.2.0/plugins/jquery/jquery.min.js"></script>
    <script src="uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
</body>

</html>
