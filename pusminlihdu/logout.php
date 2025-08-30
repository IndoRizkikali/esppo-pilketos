<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Logout
 * pusminlihdu/logout.php
 *
 * File ini menangani proses logout untuk administrator Pusminlihdu.
 * Ini mencakup konfirmasi logout, penghapusan sesi,
 * dan pengalihan ke halaman login dengan pesan sukses.
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

// Tetapkan versi aplikasi
define('ESPPO_VERSION', '2.0.0');

// -----------------------------------------------------------------------------
// 2. PROSES LOGOUT (JIKA DIKONFIRMASI)
// -----------------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_logout'])) {
    // Hapus semua variabel sesi
    $_SESSION = array();

    // Hancurkan sesi
    session_destroy();

    // Arahkan ke halaman login dengan pesan sukses logout
    header("Location: login.php?status=logout_success");
    exit();
}

// Jika pengguna belum login dan langsung mengakses halaman ini, arahkan ke login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Ambil nama pengguna dari sesi untuk ditampilkan di pesan konfirmasi
$admin_name = htmlspecialchars($_SESSION['admin_nama'] ?? 'Pengguna');
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Konfirmasi Keluar &ndash; Pusminlihdu e-SPPO</title>

  <!-- Favicon e-SPPO -->
  <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">
  
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../uis/adminlte-3.2.0/dist/css/adminlte.min.css">
  <style>
    .logout-page {
        background-image: url('../assets/imgs/esppo/pusminlihdu-inout-bg.png');
        background-size: cover;
        background-position: center;
    }
  </style>
</head>

<body class="hold-transition logout-page">
<div class="login-box">
  <div class="card card-outline card-danger">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>Konfirmasi</b></a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Halo, <strong><?= $admin_name ?></strong>. Apakah Anda yakin ingin keluar dari sesi ini?</p>

      <form action="logout.php" method="post" class="mt-4">
        <div class="row">
          <div class="col-6">
            <button type="button" class="btn btn-secondary btn-block" onclick="history.back()">Batal</button>
          </div>
          <div class="col-6">
            <button type="submit" name="confirm_logout" value="true" class="btn btn-danger btn-block">Ya, Keluar</button>
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
