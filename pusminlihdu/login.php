<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Login
 * pusminlihdu/login.php
 * 
 * File ini menangani proses login untuk administrator Pusminlihdu.
 * Ini mencakup validasi nama akun dan kata sandi, penanganan sesi,
 * dan pengalihan ke halaman utama berdasarkan peran setelah login
 * berhasil.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. INISIALISASI
// -----------------------------------------------------------------------------

// Menggunakan nama sesi yang konsisten dengan aplikasi Pusminlihdu
session_name('eSPPO_PAPT_V2');
session_start();

// Jika sudah login, langsung arahkan ke perutean utama
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

// Sertakan file konfigurasi dan helper yang diperlukan
require_once __DIR__ . '/../helpers/db_helper.php';
require_once __DIR__ . '/../helpers/schooldata_helper.php';
require_once __DIR__ . '/../helpers/crypto_helper.php';

// Tetapkan versi aplikasi
define('ESPPO_VERSION', '2.0.0');

$error_message = '';

// Ambil nama sekolah dari konfigurasi
$school_name = htmlspecialchars(get_school_data('nama_sekolah', 'Sekolah Penyelenggara'));

// -----------------------------------------------------------------------------
// 2. PROSES LOGIN
// -----------------------------------------------------------------------------

// Jika ada permintaan POST, proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['nama_akun_admin'] ?? '');
    $password = $_POST['kata_sandi_akun_admin'] ?? '';

    // Periksa apakah nama akun dan kata sandi tidak kosong
    if (empty($username) || empty($password)) {
        $error_message = "Nama Akun dan Kata Sandi tidak boleh kosong.";
    } else {
        try {
            $db = get_db_connection();
            $stmt = $db->prepare("SELECT id_unik_akun_admin, nama_akun_admin, tingkat_akses_akun, kata_sandi_akun_admin, status_akun_admin FROM akun_administrasi WHERE nama_akun_admin = ? LIMIT 1");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            $stmt->close();

            if ($admin) {
                // Periksa status akun
                if ($admin['status_akun_admin'] !== 'DIAKTIFKAN') {
                    $error_message = "Akun ini sedang tidak aktif. Hubungi administrator.";
                // Jika akun aktif, verifikasi kata sandi
                } elseif (verify_password($password, $admin['kata_sandi_akun_admin'])) {
                    // Login Berhasil!
                    session_regenerate_id(true); // Mencegah session fixation
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id_unik'] = $admin['id_unik_akun_admin'];
                    $_SESSION['admin_nama'] = $admin['nama_akun_admin'];
                    $_SESSION['admin_role'] = $admin['tingkat_akses_akun']; // PENGELOLA atau PENGAWAS

                    // Update waktu masuk terakhir
                    $stmt_update = $db->prepare("UPDATE akun_administrasi SET waktu_masuk_terakhir = NOW() WHERE id_unik_akun_admin = ?");
                    $stmt_update->bind_param('s', $admin['id_unik_akun_admin']);
                    $stmt_update->execute();
                    $stmt_update->close();

                    header("Location: index.php"); // Arahkan ke perute utama
                    exit();
                } else {
                    // Jika kata sandi salah
                    $error_message = "Nama Akun atau Kata Sandi salah.";
                }
            } else {
                // Jika nama akun tidak ditemukan
                $error_message = "Nama Akun atau Kata Sandi salah.";
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Pusminlihdu Login Error: " . $e->getMessage());
            $error_message = "Terjadi kesalahan pada sistem. Silakan coba lagi nanti.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk &ndash; Pusminlihdu e-SPPO</title>

  <!-- Favicon e-SPPO -->
  <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">
  
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../uis/adminlte-3.2.0/dist/css/adminlte.min.css">
  <style>
    .login-page {
        background-image: url('../assets/imgs/esppo/pusminlihdu-inout-bg.png');
        background-size: cover;
        background-position: center;
    }
  </style>
</head>

<body class="hold-transition login-page">
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>Pusminlihdu</b></a>
      <p class="h6 mb-0"><?= $school_name ?></p>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Masuk untuk memulai sesi Anda</p>

      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger text-center" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['status']) && $_GET['status'] === 'logout_success'): ?>
        <div class="alert alert-success text-center" role="alert">
            Anda telah berhasil keluar.
        </div>
      <?php endif; ?>
      
      <form action="login.php" method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Nama Akun" name="nama_akun_admin" required autofocus>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Kata Sandi" name="kata_sandi_akun_admin" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
          </div>
        </div>
      </form>
    </div>
    <div class="card-footer text-center">
        <p class="mb-0 text-muted small">e-SPPO v<?= ESPPO_VERSION ?></p>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="../uis/adminlte-3.2.0/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../uis/adminlte-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../uis/adminlte-3.2.0/dist/js/adminlte.min.js"></script>
</body>

</html>
