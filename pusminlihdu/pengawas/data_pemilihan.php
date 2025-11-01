<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengawas, Informasi Kegiatan Pemilihan
 * pusminlihdu/pengawas/data_pemilihan.php
 *
 * Halaman ini menampilkan informasi kegiatan pemilihan dalam mode hanya-baca.
 * Pengawas dapat melihat detail konfigurasi pemilihan yang sedang berjalan.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PENGAMBILAN DATA AKTUAL DARI DATABASE
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

$db = get_db_connection();

// Inisialisasi pesan error
$error_message = '';

// Ambil data pemilihan yang ada di basis data
$election_data = null;
try {
    $result = $db->query("SELECT * FROM data_pemilihan LIMIT 1");
    if ($result) {
        $election_data = $result->fetch_assoc();
    }
} catch (mysqli_sql_exception $e) {
    if ($db->errno !== 1146) { // 1146 = Table doesn't exist
       $error_message = "Gagal memuat data pemilihan dari database. Kesalahan: " . $e->getMessage();
    }
}
?>

<!-- Tampilkan pesan error jika ada -->
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5><i class="icon fas fa-ban"></i> Gagal!</h5>
    <?= htmlspecialchars($error_message) ?>
</div>
<?php endif; ?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Informasi Kegiatan Pemilihan</h3>
    </div>
    <div class="card-body">
        <?php if ($election_data): ?>
            <dl class="row">
                <dt class="col-sm-4">Nama Kegiatan Pemilihan</dt>
                <dd class="col-sm-8"><?= htmlspecialchars($election_data['nama_pemilihan'] ?? '-') ?></dd>

                <dt class="col-sm-4">Tipe Peserta</dt>
                <dd class="col-sm-8"><?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $election_data['tipe_peserta_pemilihan'])))) ?></dd>

                <dt class="col-sm-4">Masa Bakti</dt>
                <dd class="col-sm-8"><?= htmlspecialchars($election_data['masa_bakti'] ?? '-') ?></dd>

                <dt class="col-sm-4">Status Pemilihan</dt>
                <dd class="col-sm-8">
                    <?php 
                        $status = ucwords(strtolower(str_replace('_', ' ', $election_data['status_pemilihan'])));
                        $badge_class = 'badge-info';
                        if (str_contains($status, 'Selesai')) $badge_class = 'badge-success';
                        if (str_contains($status, 'Belum')) $badge_class = 'badge-warning';
                        echo "<span class='badge {$badge_class}'>{$status}</span>";
                    ?>
                </dd>

                <dt class="col-sm-4">Tanggal Mulai</dt>
                <dd class="col-sm-8"><?= !empty($election_data['tanggal_mulai']) ? htmlspecialchars(date('d F Y', strtotime($election_data['tanggal_mulai']))) : '-' ?></dd>

                <dt class="col-sm-4">Tanggal Selesai</dt>
                <dd class="col-sm-8"><?= !empty($election_data['tanggal_selesai']) ? htmlspecialchars(date('d F Y', strtotime($election_data['tanggal_selesai']))) : '<em>Hanya satu hari</em>' ?></dd>

                <dt class="col-sm-4">Jumlah TPS</dt>
                <dd class="col-sm-8"><?= htmlspecialchars($election_data['jumlah_tps'] ?? '-') ?></dd>

                <dt class="col-sm-4">Jumlah PASSE</dt>
                <dd class="col-sm-8"><?= htmlspecialchars($election_data['jumlah_passe'] ?? '-') ?></dd>

                <dt class="col-sm-4">Mode Tampilan SSE</dt>
                <dd class="col-sm-8">
                    <?php switch ($election_data['mode_tampilan_sse'] ?? '') {
                     case 'BERGAMBAR_BERTEKSVM': echo '<i class="fas fa-image"></i> & <i class="fas fa-align-left"></i> (Gambar & Visi-Misi)'; break;
                     case 'BERGAMBAR_NONTEKSVM': echo '<i class="fas fa-image"></i> (Hanya Gambar)'; break;
                     case 'NONGAMBAR_BERTEKSVM': echo '<i class="fas fa-align-left"></i> (Hanya Visi-Misi)'; break;
                     case 'NONGAMBAR_NONTEKSVM': echo '<i class="fas fa-align-left"></i> (Hanya Teks)'; break;
                     default: echo '-'; break;
                    } ?>
                </dd>
            </dl>
        <?php else: ?>
            <div class="alert alert-warning">
                <h5><i class="icon fas fa-exclamation-triangle"></i> Data Tidak Ditemukan</h5>
                Belum ada data kegiatan pemilihan yang dikonfigurasi oleh Pengelola.
            </div>
        <?php endif; ?>
    </div>
</div>
