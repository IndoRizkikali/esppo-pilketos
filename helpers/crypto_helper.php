<?php
// helpers/crypto_helper.php

/**
 * File ini berisi semua fungsi yang terkait dengan keamanan dan kriptografi.
 */

/**
 * Memuat konfigurasi kriptografi dari file YAML.
 * @return array Array yang berisi kunci-kunci enkripsi.
 */
function get_crypto_config() {
    static $crypto_keys = null;

    if ($crypto_keys === null) {
        $config_path = __DIR__ . '/../confs/crypto_config.yml';
        if (!file_exists($config_path)) {
            error_log("KRITIS: File konfigurasi kriptografi 'crypto_config.yml' tidak ditemukan!");
            die("Kesalahan keamanan kritis. Hubungi administrator sistem.");
        }
        $config_raw = yaml_parse_file($config_path);
        
        // Normalisasi struktur array untuk akses yang lebih mudah.
        $keys = [];
        foreach ($config_raw['crypto_config'][0]['aes_keys'] as $item) {
            $keys[key($item)] = current($item);
        }
        $crypto_keys = $keys;
    }
    return $crypto_keys;
}

/**
 * Menghasilkan ID unik yang aman secara kriptografis dalam format Base64.
 *
 * @param int $byte_length Panjang ID dalam byte.
 * @return string ID dalam format Base64.
 */
function generate_unique_id(int $byte_length): string {
    return base64_encode(random_bytes($byte_length));
}

/**
 * Membuat hash kata sandi menggunakan algoritma standar dan aman dari PHP.
 *
 * @param string $password Kata sandi mentah yang akan di-hash.
 * @return string Hash kata sandi yang siap disimpan ke basis data.
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Memverifikasi kata sandi mentah terhadap hash yang ada di basis data.
 *
 * @param string $password Kata sandi mentah yang dimasukkan pengguna.
 * @param string $hash Hash dari basis data.
 * @return bool TRUE jika kata sandi cocok, FALSE jika tidak.
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Mengenkripsi ID pemilih (data biner) menggunakan kunci AES-256-CBC dari file konfigurasi.
 *
 * @param string $raw_voter_id ID pemilih dalam format biner (hasil base64_decode).
 * @param string $raw_iv Initialization Vector (IV) dalam format biner (hasil base64_decode).
 * @return string|false Data terenkripsi dalam format Base64, atau FALSE jika terjadi kegagalan.
 */
function encrypt_voter_id(string $raw_voter_id, string $raw_iv) {
    $config = get_crypto_config();
    $key = base64_decode($config['voter_data_encrypting_key']);
    $cipher_algo = 'aes-256-cbc';

    $encrypted_raw = openssl_encrypt($raw_voter_id, $cipher_algo, $key, OPENSSL_RAW_DATA, $raw_iv);
    
    if ($encrypted_raw === false) {
        error_log("KRITIS: Gagal melakukan enkripsi openssl_encrypt.");
        return false;
    }
    return base64_encode($encrypted_raw);
}

/**
 * Menghasilkan kode_id_suara yang unik dan aman.
 * Dibuat dari hash SHA256 dari gabungan (ID pemilih terenkripsi biner + ID kandidat biner).
 *
 * @param string $raw_encrypted_voter_id Data ID pemilih terenkripsi (format biner, BUKAN Base64).
 * @param string $raw_candidate_id ID kandidat yang dipilih (format biner, hasil base64_decode).
 * @return string Kode ID Suara dalam format Base64.
 */
function generate_vote_id(string $raw_encrypted_voter_id, string $raw_candidate_id): string {
    // Gabungkan payload biner.
    $payload = $raw_encrypted_voter_id . $raw_candidate_id;
    // Hash payload menggunakan SHA256, dapatkan output biner.
    $hash_raw = hash('sha256', $payload, true);
    // Encode hasil hash ke Base64 untuk disimpan di basis data.
    return base64_encode($hash_raw);
}
