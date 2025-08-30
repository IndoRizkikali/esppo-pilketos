<?php
/**
 * e-SPPO - Fungsi Helper Basis Data
 * helpers/db_helper.php
 * 
 * File ini berisi fungsi-fungsi helper untuk berinteraksi dengan basis data,
 * seperti mendapatkan koneksi ke basis data MariaDB/MySQL, membaca konfigurasi,
 * dan menangani kesalahan koneksi.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// Sertakan pustaka untuk parsing YAML jika tidak menggunakan ekstensi PECL.
// require_once __DIR__ . '/../vendor/spyc/spyc.php';

/**
 * Fungsi untuk mendapatkan koneksi ke basis data MariaDB.
 * Fungsi ini membaca konfigurasi dari confs/db_config.yml dan menggunakan
 * pola Singleton untuk memastikan hanya ada satu objek koneksi yang dibuat per request.
 * * @return mysqli|null Objek koneksi mysqli jika berhasil, atau program akan berhenti jika gagal.
 */
function get_db_connection() {
    // Variabel statis untuk menyimpan objek koneksi agar tidak dibuat berulang kali.
    static $connection = null;

    // Hanya buat koneksi baru jika belum ada.
    if ($connection === null) {
        $config_path = __DIR__ . '/../confs/db_config.yml';

        if (!file_exists($config_path)) {
            // Catat error ke log server (lebih aman daripada menampilkan ke pengguna).
            error_log("FATAL: File konfigurasi basis data 'db_config.yml' tidak ditemukan.");
            // Tampilkan pesan generik ke pengguna.
            die("Terjadi kesalahan konfigurasi sistem. Silakan hubungi administrator.");
        }

        /**
         * Parse file YAML.
         * CATATAN: Fungsi yaml_parse_file() memerlukan ekstensi PECL 'yaml'.
         * Jika ekstensi ini tidak terpasang di server Anda, Anda dapat menggunakan
         * pustaka pihak ketiga berbasis PHP murni seperti 'spyc/spyc' melalui Composer.
         * Contoh dengan Spyc: $config = Spyc::YAMLLoad($config_path);
         */
        $config = yaml_parse_file($config_path);
        
        // Ekstrak data konfigurasi dari struktur array yang spesifik.
        $db_params = [];
        foreach ($config['database_config'] as $param) {
            $db_params[key($param)] = current($param);
        }

        // Atur mode pelaporan error untuk mysqli.
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            // Buat objek koneksi mysqli baru.
            $connection = new mysqli(
                $db_params['host'],
                $db_params['username'],
                $db_params['password'],
                $db_params['database'],
                $db_params['port']
            );
            // Atur charset koneksi ke utf8mb4 untuk mendukung berbagai karakter.
            $connection->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            error_log("FATAL: Gagal terhubung ke basis data: " . $e->getMessage());
            die("Tidak dapat terhubung ke server basis data. Layanan tidak tersedia.");
        }
    }

    return $connection;
}
