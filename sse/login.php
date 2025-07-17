<?php
/**
 * e-SPPO - Surat Suara Elektronik (SSE)
 *
 * Halaman Login untuk Pemilih.
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// 1. INISIALISASI & KONFIGURASI
// -----------------------------------------------------------------------------

// Mulai atau lanjutkan sesi dengan nama yang spesifik untuk SSE v2
session_name('eSPPO-SSE-V2');
session_start();

// Sertakan semua helper yang dibutuhkan
require_once __DIR__ . '/../helpers/db_helper.php';
require_once __DIR__ . '/../helpers/schooldata_helper.php';
require_once __DIR__ . '/../helpers/crypto_helper.php';

// Definisikan versi aplikasi
define('ESPPO_VERSION', '2.0.0');

// Variabel untuk menyimpan pesan error dan data pemilihan
$error_message = '';
$election_data = null;
$school_name = htmlspecialchars(get_school_data('nama_sekolah', 'Sekolah Penyelenggara'));

// Jika pemilih sudah login, langsung arahkan ke halaman pemilihan
if (isset($_SESSION['voter_id_unik'])) {
    header('Location: index.php');
    exit();
}

// 2. PENGAMBILAN DATA AWAL
// -----------------------------------------------------------------------------

// Dapatkan koneksi database
$db = get_db_connection();

try {
    // Ambil data pemilihan yang sedang berlangsung
    $stmt = $db->prepare("SELECT nama_pemilihan, tanggal_mulai, status_pemilihan FROM data_pemilihan WHERE status_pemilihan = 'SEDANG_BERLANSUNG' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $election_data = $result->fetch_assoc();
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    error_log("SSE Login - DB Error: " . $e->getMessage());
    // Hentikan eksekusi jika tidak bisa mengambil data pemilihan
    die("Sistem sedang mengalami gangguan teknis. Tidak dapat memuat data pemilihan.");
}


// 3. PROSES LOGIN (SAAT FORM DI-SUBMIT)
// -----------------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan data pemilihan tersedia dan sedang berlangsung
    if (!$election_data) {
        $error_message = "Saat ini tidak ada kegiatan pemilihan yang sedang berlangsung.";
    } else {
        $username = trim($_POST['nama_akun_pemilih'] ?? '');
        $password = $_POST['kata_sandi_pemilih'] ?? '';

        // Validasi input dasar
        if (empty($username) || empty($password)) {
            $error_message = "Nama Akun dan Kata Sandi tidak boleh kosong.";
        } else {
            try {
                // Ambil data pemilih dari database menggunakan prepared statement
                $stmt = $db->prepare("SELECT id_unik_pemilih, nama_pemilih, jk_pemilih, kk_pemilih, kata_sandi_pemilih, iv_akun_pemilih, status_pemilih FROM data_pemilih WHERE nama_akun_pemilih = ? LIMIT 1");
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $voter = $result->fetch_assoc();
                $stmt->close();

                if ($voter) {
                    // Verifikasi kata sandi
                    if (verify_password($password, $voter['kata_sandi_pemilih'])) {
                        // Cek apakah pemilih sudah pernah memilih
                        if ($voter['status_pemilih'] === 'SUDAH_MEMILIH') {
                            $error_message = "Akun Anda sudah digunakan untuk memilih. Hak suara hanya dapat digunakan satu kali.";
                        } else {
                            // Login berhasil!
                            // Simpan data penting ke sesi
                            $_SESSION['voter_id_unik'] = $voter['id_unik_pemilih'];
                            $_SESSION['voter_nama'] = $voter['nama_pemilih'];
                            $_SESSION['voter_jk'] = $voter['jk_pemilih'];
                            $_SESSION['voter_kk'] = $voter['kk_pemilih'];
                            $_SESSION['voter_iv'] = $voter['iv_akun_pemilih'];

                            // Catat kehadiran pemilih di tabel data_kehadiran
                            $stmt_kehadiran = $db->prepare("INSERT INTO data_kehadiran (stempel_waktu_kehadiran, id_unik_pemilih, jk_pemilih, kk_pemilih) VALUES (NOW(), ?, ?, ?)");
                            $stmt_kehadiran->bind_param('ssd', $_SESSION['voter_id_unik'], $_SESSION['voter_jk'], $_SESSION['voter_kk']);
                            $stmt_kehadiran->execute();
                            $stmt_kehadiran->close();

                            // Arahkan ke halaman surat suara
                            header("Location: index.php");
                            exit();
                        }
                    } else {
                        // Kata sandi salah
                        $error_message = "Nama Akun atau Kata Sandi yang Anda masukkan salah.";
                    }
                } else {
                    // Pengguna tidak ditemukan
                    $error_message = "Nama Akun atau Kata Sandi yang Anda masukkan salah.";
                }
            } catch (mysqli_sql_exception $e) {
                error_log("SSE Login - Auth Error: " . $e->getMessage());
                $error_message = "Terjadi kesalahan pada sistem saat mencoba masuk. Silakan coba lagi nanti.";
            }
        }
    }
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk &ndash; Surat Suara Elektronik e-SPPO</title>

    <!-- Favicon e-SPPO -->
    <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="../uis/bootstrap-5.3.7/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bs-body-font-family: 'Inter', sans-serif;
        }
        
        body {
            /* Menggunakan gambar latar belakang yang ditentukan */
            background-image: url('../assets/imgs/esppo/sse-login.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .login-card {
            max-width: 480px;
            width: 100%;
            /* Efek Glassmorphism */
            background: rgba(255, 255, 255, 0.15);
            -webkit-backdrop-filter: blur(15px);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem; /* Sudut lebih melengkung */
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            color: white;
        }

        .login-card .card-body {
            padding: 2.5rem;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.85);
            border: none;
            color: #333;
        }

        .form-control:focus {
            background-color: white;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
            color: #333;
        }
        
        .form-floating > label {
            color: #6c757d;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            font-weight: 600;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-2px);
        }
        
        .logo-img {
            width: 100px;
            height: 100px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }
        
        .election-info-card {
            background-color: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
        }
    </style>
</head>
<body>

    <div class="card login-card">
        <div class="card-body">
            <div class="text-center mb-4">
                <!-- Logo e-SPPO -->
                <img src="../assets/imgs/esppo/esppo-logo.png" alt="Logo e-SPPO" class="logo-img mb-3">
                <h1 class="h3 mb-1 fw-bold">Surat Suara Elektronik</h1>
                <p class="text-white-50"><?= $school_name ?></p>
            </div>

            <!-- Kartu Informasi Pemilihan -->
            <?php if ($election_data): ?>
            <div class="p-3 mb-4 election-info-card text-center">
                <h5 class="mb-1 fw-semibold"><?= htmlspecialchars($election_data['nama_pemilihan']) ?></h5>
                <p class="mb-2 small">Dimulai pada: <?= date('d F Y', strtotime($election_data['tanggal_mulai'])) ?></p>
                <span class="badge bg-success-subtle text-success-emphasis rounded-pill">SEDANG BERLANGSUNG</span>
            </div>
            <?php else: ?>
            <div class="alert alert-warning text-center" role="alert">
                Saat ini tidak ada pemilihan yang aktif.
            </div>
            <?php endif; ?>

            <!-- Pesan Error -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" action="login.php" id="loginForm" novalidate>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="nama_akun_pemilih" name="nama_akun_pemilih" placeholder="Nama Akun" required autofocus>
                    <label for="nama_akun_pemilih">Nama Akun Pemilih</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="kata_sandi_pemilih" name="kata_sandi_pemilih" placeholder="Kata Sandi" required>
                    <label for="kata_sandi_pemilih">Kata Sandi</label>
                </div>
                
                <button class="btn btn-primary w-100" type="submit" <?= !$election_data ? 'disabled' : '' ?>>
                    Masuk
                </button>
            </form>

            <footer class="mt-4 text-center">
                <p class="mb-0 text-white-50 small">
                    e-SPPO SSE v<?= ESPPO_VERSION ?>
                </p>
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="../uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
</body>
</html>
