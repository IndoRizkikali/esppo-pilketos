<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Routing
 * pusminlihdu/index.php
 * 
 * File ini menangani perutean pengguna berdasarkan peran mereka
 * setelah login. Ini memastikan bahwa pengguna diarahkan
 * ke direktori yang sesuai berdasarkan peran mereka
 * (PENGELOLA atau PENGAWAS).
 * Jika pengguna belum login atau sesi tidak valid,
 * mereka akan diarahkan kembali ke halaman login.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. INISIALISASI SESI
// -----------------------------------------------------------------------------

// Menggunakan nama sesi yang konsisten dengan aplikasi Pusminlihdu
session_name('eSPPO_PAPT_V2');
session_start();

// Tetapkan versi aplikasi
define('ESPPO_VERSION', '2.0.0');

// -----------------------------------------------------------------------------
// 2. LOGIKA PERUTEAN BERBASIS PERAN (ROLE-BASED ROUTING)
// -----------------------------------------------------------------------------

// Cek apakah pengguna sudah login dan memiliki peran yang tersimpan di sesi
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['admin_role'])) {
    
    // Arahkan berdasarkan peran
    switch ($_SESSION['admin_role']) {
        case 'PENGELOLA':
            // Jika peran adalah PENGELOLA, arahkan ke direktori pengelola
            header('Location: pengelola/');
            exit();

        case 'PENGAWAS':
            // Jika peran adalah PENGAWAS, arahkan ke direktori pengawas
            header('Location: pengawas/');
            exit();

        default:
            // Jika peran tidak dikenali, ini adalah kondisi tidak valid.
            // Hancurkan sesi dan paksa login ulang untuk keamanan.
            session_unset();
            session_destroy();
            header('Location: login.php?error=invalid_role');
            exit();
    }

} else {
    // Jika pengguna belum login atau sesi tidak lengkap,
    // arahkan kembali ke halaman login.
    header('Location: login.php');
    exit();
}
?>
