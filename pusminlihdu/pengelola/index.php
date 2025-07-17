<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu)
 *
 * Loader Utama untuk Peran PENGELOLA.
 * File ini membangun kerangka halaman (header, sidebar, footer) dan memuat
 * konten halaman spesifik berdasarkan permintaan URL.
 *
 * @version 2.0.1
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// 1. INISIALISASI & PENJAGA KEAMANAN (SECURITY GUARD)
// -----------------------------------------------------------------------------
session_name('eSPPO_PAPT_V2');
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'PENGELOLA') {
    header('Location: ../logout.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../helpers/db_helper.php';
$db = get_db_connection();
require_once __DIR__ . '/../../helpers/schooldata_helper.php';
require_once __DIR__ . '/../../helpers/crypto_helper.php';

// 2. LOGIKA PEMUATAN HALAMAN (PAGE LOADING LOGIC)
// -----------------------------------------------------------------------------
if (!defined('ESPPO_VERSION')) {
    define('ESPPO_VERSION', '2.0.1');
}

// PERBAIKAN: Pemetaan nama halaman ke judul yang lebih deskriptif
$page_titles = [
    'dashboard' => 'Dashboard Utama',
    'data_sekolah' => 'Informasi Data Sekolah',
    'data_pejabat_sekolah' => 'Kelola Data Pejabat Sekolah',
    'data_pemilihan' => 'Konfigurasi Kegiatan Pemilihan',
    'data_akun_admin' => 'Kelola Akun Administrasi',
    'daftar_konstituensi' => 'Lihat Daftar Konstituensi',
    'edit_konstituensi' => 'Kelola Data Konstituensi',
    'daftar_pemilih' => 'Lihat Daftar Pemilih Tetap (DPT)',
    'tambah_pemilih' => 'Tambah Data Pemilih (DPT)',
    'edit_pemilih' => 'Kelola Data Pemilih Individual',
    'daftar_kandidat' => 'Lihat Daftar Kandidat',
    'tambah_kandidat' => 'Tambah Data Kandidat',
    'edit_kandidat' => 'Kelola Data Kandidat',
    'hasil_pemilihan' => 'Laporan Hasil Pemilihan',
    'rincian_hasil_pemilihan' => 'Rincian Hasil per Konstituensi',
    'kehadiran_pemilih' => 'Laporan Kehadiran Pemilih',
    'pratayang_sse' => 'Pratayang Surat Suara Elektronik',
    'cetak_dpt' => 'Cetak Laporan DPT',
    'cetak_kartu_dpt' => 'Cetak Kartu Pemilih',
    'cetak_panduan_akses' => 'Cetak Panduan Akses SSE',
    'cetak_daftar_kandidat' => 'Cetak Daftar Kandidat',
    'cetak_laporan_pemilhan' => 'Cetak Laporan Akhir Pemilihan',
    'cetak_kehadiran' => 'Cetak Laporan Kehadiran',
    'cetak_sse_ljk' => 'Cetak LJK SSE (OMR)',
    'tambah_masal_pemilih' => 'Proses Impor DPT' // Ditambahkan untuk kelengkapan
];

$page = $_GET['page'] ?? 'dashboard';

// Jika halaman yang diminta tidak ada di daftar, set ke '404'.
if (!array_key_exists($page, $page_titles)) {
    $page_title = "Halaman Tidak Ditemukan";
    $content_to_load = '404.php';
} else {
    $page_title = $page_titles[$page];
    $content_to_load = $page . '.php';
}

$admin_name = htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin');
$admin_role = htmlspecialchars($_SESSION['admin_role'] ?? 'Peran');

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $page_title ?> &ndash; Pusminlihdu e-SPPO</title>

  <!-- Aset CSS Global -->
  <link rel="icon" type="image/png" href="../../assets/imgs/esppo/esppo-logo.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../../uis/adminlte-3.2.0/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../../uis/adminlte-3.2.0/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../../uis/adminlte-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../../uis/adminlte-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../../uis/adminlte-3.2.0/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../../uis/adminlte-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="../../uis/adminlte-3.2.0/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><a href="index.php?page=dashboard" class="nav-link">Dashboard</a></li>
      <li class="nav-item d-none d-sm-inline-block"><a href="../../sse/" target="_blank" class="nav-link">Lihat Aplikasi SSE</a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-user-circle"></i>
          <span class="d-none d-md-inline ml-1"><?= $admin_name ?> (<?= $admin_role ?>)</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">Menu Pengguna</span>
          <div class="dropdown-divider"></div>
          <a href="../logout.php" class="dropdown-item dropdown-footer bg-danger"><i class="fas fa-sign-out-alt mr-2"></i> Keluar</a>
        </div>
      </li>
      <li class="nav-item"><a class="nav-link" data-widget="fullscreen" href="#" role="button"><i class="fas fa-expand-arrows-alt"></i></a></li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <?php include_once 'sidebar_pengelola.php'; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><?= $page_title ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php?page=dashboard">Pusminlihdu</a></li>
              <li class="breadcrumb-item active"><?= $page_title ?></li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php
          if (file_exists($content_to_load)) {
              include $content_to_load;
          } else {
              include '404.php';
          }
        ?>
      </div>
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Footer -->
  <footer class="main-footer">
    <strong>Hak Cipta &copy; 2024-<?= date("Y"); ?> <a href="#">Tim Pengembang e-SPPO</a>.</strong>
    Seluruh hak cipta dilindungi.
    <div class="float-right d-none d-sm-inline-block"><b>Versi</b> <?= ESPPO_VERSION ?></div>
  </footer>

</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<script src="../../uis/adminlte-3.2.0/plugins/jquery/jquery.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/moment/moment-with-locales.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/select2/js/select2.full.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script src="../../uis/adminlte-3.2.0/plugins/chart.js/Chart.min.js"></script>
<script src="../../uis/adminlte-3.2.0/dist/js/adminlte.js"></script>
</body>
</html>
