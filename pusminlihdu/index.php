<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu)
 *
 * Perute Utama (Routing Stub).
 * File ini memeriksa sesi login dan mengarahkan pengguna ke direktori
 * yang sesuai dengan peran/hak akses mereka.
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// 1. INISIALISASI SESI
// -----------------------------------------------------------------------------
// Menggunakan nama sesi yang konsisten dengan aplikasi Pusminlihdu
session_name('eSPPO_PAPT_V2');
session_start();


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
