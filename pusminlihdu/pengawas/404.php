<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengawas, Halaman 404
 * pusminlihdu/pengawas/404.php
 * 
 * Halaman ini ditampilkan ketika halaman yang diminta tidak ditemukan (404 Not Found).
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

http_response_code(404);
?>

<section class="content">
    <div class="error-page
        <h2 class="headline text-warning">404</h2>
        <div class="error-content">
            <h3><i class="fas fa-exclamation-triangle text-warning"></i> Oops! Halaman tidak ditemukan.</h3>
            <p>
                Kami tidak dapat menemukan halaman yang Anda cari.
                Sementara itu, Anda dapat <a href="index.php?page=dashboard">kembali ke dashboard</a> atau menggunakan menu di sebelah kiri untuk menavigasi.
            </p>
        </div>
    </div>
</section>
