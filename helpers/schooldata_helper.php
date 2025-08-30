<?php
/**
 * e-SPPO - Helper untuk Data Sekolah
 * helpers/schooldata_helper.php
 * 
 * File ini berisi fungsi-fungsi helper untuk mengakses data konfigurasi sekolah,
 * seperti nama sekolah, alamat, dan informasi lainnya yang disimpan dalam file YAML.
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
 * Fungsi untuk memuat dan menyediakan data konfigurasi sekolah.
 * Fungsi ini membaca data dari confs/school_config.yml dan menggunakan
 * pola Singleton untuk memastikan file hanya perlu dibaca dan di-parse sekali.
 *
 * @return array Array asosiatif yang telah dinormalisasi dari konfigurasi sekolah.
 */
function get_school_config() {
    static $normalized_config = null;

    if ($normalized_config === null) {
        $config_path = __DIR__ . '/../confs/school_config.yml';

        if (!file_exists($config_path)) {
            error_log("FATAL: File konfigurasi sekolah 'school_config.yml' tidak ditemukan.");
            die("Terjadi kesalahan konfigurasi sistem. Data sekolah tidak ditemukan.");
        }
        
        // Parse file YAML (memerlukan ekstensi PECL 'yaml' atau pustaka PHP).
        $config_raw = yaml_parse_file($config_path);

        // Normalisasi struktur array agar lebih mudah diakses.
        // Struktur asli: [['key1' => 'value1'], ['key2' => 'value2']]
        // Struktur baru: ['key1' => 'value1', 'key2' => 'value2']
        $normalized_config = [];
        foreach ($config_raw['school_config'] as $item) {
            $normalized_config[key($item)] = current($item);
        }
    }

    return $normalized_config;
}

/**
 * Fungsi pembantu untuk mendapatkan satu nilai spesifik dari data sekolah.
 *
 * @param string $key Kunci data yang ingin diambil (contoh: 'nama_sekolah').
 * @param mixed $default Nilai default yang akan dikembalikan jika kunci tidak ditemukan.
 * @return mixed Nilai dari kunci yang dicari atau nilai default.
 */
function get_school_data(string $key, $default = null) {
    $config = get_school_config();
    return $config[$key] ?? $default;
}
