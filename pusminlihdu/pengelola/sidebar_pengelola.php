<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu)
 *
 * Sidebar menu untuk peran PENGELOLA. (Revisi)
 * File ini dimuat oleh loader (pengelola/index.php).
 * Strukturnya disesuaikan dengan filosofi 1 menu = 1 halaman UI.
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// Sesi sudah dimulai oleh loader, kita bisa langsung menggunakan variabel sesi.
// Variabel $page juga sudah didefinisikan di loader (index.php).
$currentPage = $page ?? '';

/**
 * Fungsi helper untuk memeriksa apakah halaman saat ini aktif.
 * @param string $pageName Nama halaman menu.
 * @param string $current Halaman saat ini.
 * @return string 'active' jika aktif, '' jika tidak.
 */
function isMenuItemActive(string $pageName, string $current): string {
    return $pageName === $current ? 'active' : '';
}

/**
 * Fungsi helper untuk memeriksa apakah salah satu dari halaman dalam grup treeview aktif.
 * Berguna untuk membuka menu treeview secara otomatis.
 * @param array $pagesInTree Array dari nama halaman dalam treeview.
 * @param string $current Halaman saat ini.
 * @return string 'menu-open' jika aktif, '' jika tidak.
 */
function isTreeviewOpen(array $pagesInTree, string $current): string {
    return in_array($current, $pagesInTree) ? 'menu-open' : '';
}

// Definisikan grup halaman untuk setiap treeview agar mudah dikelola
$manajemenDataPemilihanPages = ['daftar_konstituensi', 'edit_konstituensi', 'daftar_pemilih', 'tambah_pemilih', 'edit_pemilih', 'daftar_kandidat', 'tambah_kandidat', 'edit_kandidat'];
$konstituensiPages = ['daftar_konstituensi', 'edit_konstituensi'];
$dptPages = ['daftar_pemilih', 'tambah_pemilih', 'edit_pemilih'];
$kandidatPages = ['daftar_kandidat', 'tambah_kandidat', 'edit_kandidat'];
$laporanPages = ['cetak_dpt', 'cetak_kartu_dpt', 'cetak_panduan_akses', 'cetak_daftar_kandidat', 'cetak_laporan_pemilihan', 'cetak_kehadiran', 'cetak_sse_ljk'];

?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->
  <a href="index.php?page=dashboard" class="brand-link">
    <img src="../../assets/imgs/esppo/esppo-logo.png" alt="e-SPPO Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
    <span class="brand-text font-weight-light">Pusminlihdu e-SPPO</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent nav-legacy" data-widget="treeview" role="menu" data-accordion="false">
        
        <li class="nav-item">
          <a href="index.php?page=dashboard" class="nav-link <?= isMenuItemActive('dashboard', $currentPage) ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <li class="nav-header">MANAJEMEN SISTEM</li>
        <li class="nav-item">
          <a href="index.php?page=data_sekolah" class="nav-link <?= isMenuItemActive('data_sekolah', $currentPage) ?>">
            <i class="nav-icon fas fa-school"></i>
            <p>Data Sekolah</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="index.php?page=data_pejabat_sekolah" class="nav-link <?= isMenuItemActive('data_pejabat_sekolah', $currentPage) ?>">
            <i class="nav-icon fas fa-user-tie"></i>
            <p>Data Pejabat Sekolah</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="index.php?page=data_akun_admin" class="nav-link <?= isMenuItemActive('data_akun_admin', $currentPage) ?>">
            <i class="nav-icon fas fa-user-shield"></i>
            <p>Data Akun Administrasi</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="index.php?page=data_pemilihan" class="nav-link <?= isMenuItemActive('data_pemilihan', $currentPage) ?>">
            <i class="nav-icon fas fa-cogs"></i>
            <p>Data Pemilihan</p>
          </a>
        </li>

        <li class="nav-header">MANAJEMEN DATA PEMILIHAN</li>
        <li class="nav-item <?= isTreeviewOpen($manajemenDataPemilihanPages, $currentPage) ?>">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-landmark"></i>
            <p>Data Konstituensi <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=daftar_konstituensi" class="nav-link <?= isMenuItemActive('daftar_konstituensi', $currentPage) ?>">
                <i class="far fa-circle nav-icon"></i><p>Lihat Data Konstituensi</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=edit_konstituensi" class="nav-link <?= isMenuItemActive('edit_konstituensi', $currentPage) ?>">
                <i class="far fa-circle nav-icon"></i><p>Tambah/Edit Konstituensi</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item <?= isTreeviewOpen($dptPages, $currentPage) ?>">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-users"></i>
            <p>Daftar Pemilih Tetap <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=daftar_pemilih" class="nav-link <?= isMenuItemActive('daftar_pemilih', $currentPage) ?>">
                <i class="far fa-circle nav-icon"></i><p>Lihat DPT</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=tambah_pemilih" class="nav-link <?= isMenuItemActive('tambah_pemilih', $currentPage) ?>">
                <i class="far fa-circle nav-icon"></i><p>Tambah DPT Baru</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=edit_pemilih" class="nav-link <?= isMenuItemActive('edit_pemilih', $currentPage) ?>">
                <i class="far fa-circle nav-icon"></i><p>Edit DPT</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item <?= isTreeviewOpen($kandidatPages, $currentPage) ?>">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-user-friends"></i>
            <p>Daftar Kandidat <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=daftar_kandidat" class="nav-link <?= isMenuItemActive('daftar_kandidat', $currentPage) ?>">
                <i class="far fa-circle nav-icon"></i><p>Daftar Kandidat</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=tambah_kandidat" class="nav-link <?= isMenuItemActive('tambah_kandidat', $currentPage) ?>">
                <i class="far fa-circle nav-icon"></i><p>Tambah Kandidat</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=edit_kandidat" class="nav-link <?= isMenuItemActive('edit_kandidat', $currentPage) ?>">
                <i class="far fa-circle nav-icon"></i><p>Edit Data Kandidat</p>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-header">PELAKSANAAN & HASIL</li>
        <li class="nav-item">
          <a href="index.php?page=hasil_pemilihan" class="nav-link <?= isMenuItemActive('hasil_pemilihan', $currentPage) ?>">
            <i class="nav-icon fas fa-poll-h"></i><p>Hasil Perolehan Suara</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="index.php?page=rincian_hasil_pemilihan" class="nav-link <?= isMenuItemActive('rincian_hasil_pemilihan', $currentPage) ?>">
            <i class="nav-icon fas fa-chart-pie"></i><p>Rincian Hasil</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="index.php?page=kehadiran_pemilih" class="nav-link <?= isMenuItemActive('kehadiran_pemilih', $currentPage) ?>">
            <i class="nav-icon fas fa-user-check"></i><p>Kehadiran Pemilih</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="index.php?page=pratayang_sse" class="nav-link <?= isMenuItemActive('pratayang_sse', $currentPage) ?>">
            <i class="nav-icon fas fa-eye"></i><p>Pratayang Tampilan SSE</p>
          </a>
        </li>

        <li class="nav-header">LAPORAN & CETAK</li>
        <li class="nav-item <?= isTreeviewOpen($laporanPages, $currentPage) ?>">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-print"></i>
            <p>Cetak Dokumen <i class="fas fa-angle-left right"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item"><a href="index.php?page=cetak_dpt" class="nav-link <?= isMenuItemActive('cetak_dpt', $currentPage) ?>"><i class="far fa-circle nav-icon"></i><p>Statistik Pemilih & DPT</p></a></li>
            <li class="nav-item"><a href="index.php?page=cetak_kartu_dpt" class="nav-link <?= isMenuItemActive('cetak_kartu_dpt', $currentPage) ?>"><i class="far fa-circle nav-icon"></i><p>Kartu Pemilih</p></a></li>
            <li class="nav-item"><a href="index.php?page=cetak_panduan_akses" class="nav-link <?= isMenuItemActive('cetak_panduan_akses', $currentPage) ?>"><i class="far fa-circle nav-icon"></i><p>Panduan & QR Code Akses</p></a></li>
            <li class="nav-item"><a href="index.php?page=cetak_daftar_kandidat" class="nav-link <?= isMenuItemActive('cetak_daftar_kandidat', $currentPage) ?>"><i class="far fa-circle nav-icon"></i><p>Daftar Kandidat</p></a></li>
            <li class="nav-item"><a href="index.php?page=cetak_laporan_pemilihan" class="nav-link <?= isMenuItemActive('cetak_laporan_pemilhan', $currentPage) ?>"><i class="far fa-circle nav-icon"></i><p>Laporan & Hasil Pemilihan</p></a></li>
            <li class="nav-item"><a href="index.php?page=cetak_kehadiran" class="nav-link <?= isMenuItemActive('cetak_kehadiran', $currentPage) ?>"><i class="far fa-circle nav-icon"></i><p>Laporan Kehadiran</p></a></li>
            <li class="nav-item"><a href="index.php?page=cetak_sse_ljk" class="nav-link <?= isMenuItemActive('cetak_sse_ljk', $currentPage) ?>"><i class="far fa-circle nav-icon"></i><p>LJK SSE (OMR)</p></a></li>
          </ul>
        </li>
        
        <li class="nav-header">BANTUAN</li>
        <li class="nav-item">
            <a href="../../docs/" target="_blank" class="nav-link">
                <i class="nav-icon fas fa-book"></i>
                <p>Dokumentasi</p>
            </a>
        </li>
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>
