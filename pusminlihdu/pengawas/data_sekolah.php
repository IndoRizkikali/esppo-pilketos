<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengawas, Informasi Data Sekolah
 * pusminlihdu/pengawas/data_sekolah.php
 *
 * File ini menampilkan informasi lengkap tentang sekolah, termasuk alamat,
 * kepala sekolah, tahun ajaran, dan logo. Data ini diambil dari file konfigurasi
 * `confs/school_config.yml` dan ditampilkan dalam format yang mudah dibaca.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PENGAMBILAN DAN PEMROSESAN DATA KONFIGURASI
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh file helper sudah dimuat sebelumnya oleh index.php

// Ambil konfigurasi sekolah dari file YAML
$school_config = get_school_config();

/**
 * Fungsi internal untuk meratakan array konfigurasi yang bersarang.
 * Contoh: [['key' => 'val']] menjadi ['key' => 'val']
 * @param array|null $arr Array yang akan diratakan.
 * @return array Array yang sudah rata.
 */
function flatten_config_array(?array $arr): array {
    $flat = [];
    if (is_array($arr)) {
        foreach ($arr as $item) {
            if (is_array($item)) {
                $flat[key($item)] = current($item);
            }
        }
    }
    return $flat;
}

// Proses data dari array konfigurasi agar mudah digunakan di HTML
$alamat_data = flatten_config_array($school_config['alamat_sekolah'] ?? []);
$kepsek_data = flatten_config_array($school_config['kepala_sekolah'] ?? []);
$ajaran_data = flatten_config_array($school_config['tahun_ajaran'] ?? []);
$logo_data = flatten_config_array($school_config['logo'] ?? []);

// Gabungkan alamat menjadi satu string yang rapi
$full_address = implode(', ', [
    $alamat_data['alamat'] ?? '',
    $alamat_data['kecamatan'] ?? ''
]);
$full_address .= ', ' . ($alamat_data['dati_2'] ?? '');
$full_address .= ', ' . ($alamat_data['dati_1'] ?? '');
$full_address .= ' ' . ($alamat_data['kode_pos'] ?? '');
?>

<div class="row">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-info"></i> Informasi</h5>
            Halaman ini menampilkan data sekolah yang bersifat <b>hanya-baca (read-only)</b>. Untuk mengubah data ini, silakan edit file konfigurasi <code>confs/school_config.yml</code> di server.
        </div>
    </div>
</div>

<div class="row">
    <!-- Kolom Informasi Umum dan Kontak -->
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-school mr-1"></i>
                    Informasi Umum Sekolah
                </h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Nama Sekolah</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($school_config['nama_sekolah'] ?? 'Tidak diatur') ?></dd>

                    <dt class="col-sm-4">NPSN</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($school_config['npsn_sekolah'] ?? 'Tidak diatur') ?></dd>

                    <dt class="col-sm-4">Alamat Lengkap</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($full_address) ?></dd>

                    <dt class="col-sm-4">Telepon</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($alamat_data['no_telepon'] ?? 'Tidak diatur') ?></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($alamat_data['email_sekolah'] ?? 'Tidak diatur') ?></dd>

                    <dt class="col-sm-4">Website</dt>
                    <dd class="col-sm-8">
                        <a href="<?= htmlspecialchars($alamat_data['website_sekolah'] ?? '#') ?>" target="_blank">
                            <?= htmlspecialchars($alamat_data['website_sekolah'] ?? 'Tidak diatur') ?>
                        </a>
                    </dd>

                    <dt class="col-sm-4">Tahun Ajaran Aktif</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($ajaran_data['tahun_ajaran'] ?? '') ?> - Semester <?= htmlspecialchars($ajaran_data['semester'] ?? '') ?></dd>
                </dl>
            </div>
        </div>

        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-tie mr-1"></i>
                    Kepala Sekolah
                </h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Nama</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($kepsek_data['nama'] ?? 'Tidak diatur') ?></dd>

                    <dt class="col-sm-4">NIP</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($kepsek_data['nip_kepsek'] ?? 'Tidak diatur') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Kolom Logo -->
    <div class="col-md-4">
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-image mr-1"></i>
                    Logo
                </h3>
            </div>
            <div class="card-body text-center">
                <div class="mb-4">
                    <strong>Logo Sekolah</strong><br>
                    <img src="../../assets/imgs/sekolah/<?= htmlspecialchars($logo_data['logo_sekolah'] ?? 'placeholder.png') ?>" 
                         alt="Logo Sekolah" 
                         class="img-fluid my-2" 
                         style="max-height: 150px;"
                         onerror="this.onerror=null; this.src='https://placehold.co/200x150/e0e0e0/757575?text=Logo+Sekolah';">
                    <p class="text-muted small">File: <?= htmlspecialchars($logo_data['logo_sekolah'] ?? 'Tidak ada') ?></p>
                </div>
                <hr>
                <div>
                    <strong>Logo OSIS</strong><br>
                    <img src="../../assets/imgs/sekolah/<?= htmlspecialchars($logo_data['logo_osis'] ?? 'placeholder.png') ?>" 
                         alt="Logo OSIS" 
                         class="img-fluid my-2" 
                         style="max-height: 150px;"
                         onerror="this.onerror=null; this.src='https://placehold.co/200x150/e0e0e0/757575?text=Logo+OSIS';">
                    <p class="text-muted small">File: <?= htmlspecialchars($logo_data['logo_osis'] ?? 'Tidak ada') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
