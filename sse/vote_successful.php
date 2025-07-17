<?php
/**
 * e-SPPO - Surat Suara Elektronik (SSE)
 *
 * Halaman ini ditampilkan setelah pemilih berhasil mengirimkan suaranya.
 * Sesi pengguna sudah dihancurkan sebelum mencapai halaman ini.
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// 1. INISIALISASI
// -----------------------------------------------------------------------------

// Hanya butuh helper data sekolah untuk menampilkan nama sekolah.
require_once __DIR__ . '/../helpers/schooldata_helper.php';

// Definisikan versi aplikasi
define('ESPPO_VERSION', '2.0.0');

// Ambil nama sekolah untuk ditampilkan
$school_name = htmlspecialchars(get_school_data('nama_sekolah', 'Sekolah Penyelenggara'));

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terima Kasih! &ndash; e-SPPO</title>

    <!-- Favicon e-SPPO -->
    <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">

    <!-- Bootstrap 5 CSS -->
    <link href="../uis/bootstrap-5.3.7/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts: Inter & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bs-body-font-family: 'Inter', sans-serif;
            --bs-success: #198754;
        }

        body {
            background-image: url('../assets/imgs/esppo/sse-vote-successful.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .success-card {
            max-width: 500px;
            width: 100%;
            /* Efek Glassmorphism */
            background: rgba(255, 255, 255, 0.2);
            -webkit-backdrop-filter: blur(15px);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            color: white;
            animation: fadeInZoom 0.5s ease-out;
        }

        @keyframes fadeInZoom {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .success-card .card-body {
            padding: 3rem;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background-color: var(--bs-success);
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            animation: iconPop 0.5s 0.2s ease-out backwards;
        }

        .success-icon svg {
            width: 48px;
            height: 48px;
        }

        @keyframes iconPop {
            0% { transform: scale(0); }
            80% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }
        
        .btn-finish {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            background-color: #0d6efd;
            border-color: #0d6efd;
            transition: all 0.2s ease-in-out;
        }

        .btn-finish:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="card success-card text-center">
        <div class="card-body">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16">
                    <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                </svg>
            </div>
            <h1 class="card-title h2">Suara Telah Direkam!</h1>
            <p class="text-white-50 mt-3 mb-4">
                Terima kasih atas partisipasi Anda dalam menyukseskan kegiatan pemilihan di <?= $school_name ?>. Hak suara Anda telah berhasil disimpan dengan aman oleh sistem.
            </p>
            <a href="login.php" class="btn btn-primary btn-lg btn-finish">Selesai & Kembali ke Login</a>
            <footer class="mt-4">
                <p class="mb-0 text-white-50 small">
                    e-SPPO SSE v<?= ESPPO_VERSION ?>
                </p>
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5 JS (opsional untuk halaman statis ini, tapi baik untuk konsistensi) -->
    <script src="../uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
</body>
</html>
