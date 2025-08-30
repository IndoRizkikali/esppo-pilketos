<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Daftar Kandidat
 * pusminlihdu/pengelola/daftar_kandidat.php
 * 
 * Halaman ini menampilkan daftar kandidat yang telah ditambahkan ke dalam sistem.
 * Pengelola dapat melihat informasi dasar kandidat seperti nomor urut, nama calon,
 * visi, misi, dan foto kandidat.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PENGAMBILAN DATA
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

$db = get_db_connection();

$candidates = [];
$error_message = '';

try {
    // Query ini menggabungkan data kandidat dengan nama konstituensi untuk kedua calon
    $query = "
        SELECT 
            k.id_unik_kandidat,
            k.no_urut_kandidat, 
            k.nama_calon_1, 
            k.nama_calon_2, 
            k.visi_kandidat, 
            k.misi_kandidat, 
            k.foto_kandidat,
            kon1.nama_konstituensi AS konstituensi_calon_1,
            kon2.nama_konstituensi AS konstituensi_calon_2
        FROM 
            data_kandidat k
        LEFT JOIN 
            data_konstituensi kon1 ON k.kk_calon_1 = kon1.kode_konstituensi
        LEFT JOIN 
            data_konstituensi kon2 ON k.kk_calon_2 = kon2.kode_konstituensi
        ORDER BY 
            k.no_urut_kandidat ASC";
            
    $result = $db->query($query);
    if ($result) {
        $candidates = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data kandidat. Kesalahan: " . $e->getMessage();
}
?>

<!-- Tampilkan pesan error jika ada -->
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="icon fas fa-ban"></i>
    <?= htmlspecialchars($error_message) ?>
</div>
<?php endif; ?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Daftar Kandidat</h3>
        <div class="card-tools">
            <a href="index.php?page=tambah_kandidat" class="btn btn-primary btn-sm mx-1">
                <i class="fas fa-user-plus"></i> Tambah Kandidat Baru
            </a>
            <a href="index.php?page=edit_kandidat" class="btn btn-warning btn-sm mx-1">
                <i class="fas fa-user-edit"></i> Kelola Kandidat
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($candidates)): ?>
            <div class="text-center py-5">
                <p class="text-muted">Belum ada data kandidat yang ditambahkan.</p>
            </div>
        <?php else: ?>
            <?php foreach ($candidates as $candidate): ?>
                <div class="card mb-3">
                    <div class="row g-0">
                        <!-- Kolom Nomor Urut -->
                        <div class="col-md-1 d-flex justify-content-center align-items-center bg-light p-3">
                            <h1 class="display-3 font-weight-bold text-secondary"><?= htmlspecialchars($candidate['no_urut_kandidat']) ?></h1>
                        </div>
                        <!-- Kolom Foto -->
                        <div class="col-md-2 d-flex justify-content-center align-items-center p-3">
                            <img src="../../assets/imgs/sse-foto-kandidat/<?= htmlspecialchars($candidate['foto_kandidat'] ?? 'default.png') ?>" 
                                 class="img-fluid rounded" 
                                 alt="Foto Kandidat"
                                 style="max-height: 180px; object-fit: cover;"
                                 onerror="this.onerror=null; this.src='https://placehold.co/240x240/e0e0e0/757575?text=Foto%20kandidat';">
                        </div>
                        <!-- Kolom Nama & Konstituensi -->
                        <div class="col-md-4">
                            <div class="card-body">
                                <h5 class="card-title font-weight-bold"><?= htmlspecialchars($candidate['nama_calon_1']) ?></h5>
                                <p class="card-text"><small class="text-muted">Calon Ketua (<?= htmlspecialchars($candidate['konstituensi_calon_1'] ?? 'N/A') ?>)</small></p>
                                
                                <?php if (!empty($candidate['nama_calon_2'])): ?>
                                <hr>
                                <h5 class="card-title font-weight-bold"><?= htmlspecialchars($candidate['nama_calon_2']) ?></h5>
                                <p class="card-text"><small class="text-muted">Calon Wakil Ketua (<?= htmlspecialchars($candidate['konstituensi_calon_2'] ?? 'N/A') ?>)</small></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Kolom Visi & Misi -->
                        <div class="col-md-5">
                            <div class="card-body" style="font-size: 0.9rem;">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <strong>Visi:</strong>
                                        <p><?= nl2br(htmlspecialchars($candidate['visi_kandidat'] ?? 'Visi belum diatur.')) ?></p>
                                    </div>
                                    <div class="col-lg-6">
                                        <strong>Misi:</strong>
                                        <p><?= nl2br(htmlspecialchars($candidate['misi_kandidat'] ?? 'Misi belum diatur.')) ?></p>
                                    </div>
                                </div>
                                <a href="index.php?page=edit_kandidat&id=<?= htmlspecialchars($candidate['id_unik_kandidat']) ?>" class="btn btn-sm btn-warning position-absolute" style="bottom: 15px; right: 15px;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
