<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengawas, Lihat Data Pejabat Sekolah
 * pusminlihdu/pengawas/data_pejabat_sekolah.php
 *
 * File ini menampilkan data pejabat sekolah dalam mode hanya-baca.
 * Pengawas dapat melihat daftar pejabat yang terdaftar di sistem.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PENGAMBILAN DATA UNTUK DITAMPILKAN
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

// Inisialisasi pesan error
$error_message = '';

$school_officials = [];
try {
    $result = $db->query("SELECT * FROM data_pejabat_sekolah ORDER BY nama_pejabat_sekolah ASC");
    if ($result) {
        $school_officials = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat daftar pejabat. Kesalahan: " . $e->getMessage();
}
?>

<!-- Tampilkan pesan error -->
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5><i class="icon fas fa-ban"></i> Gagal!</h5>
    <?= htmlspecialchars($error_message) ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Daftar Pejabat Sekolah</h3>
            </div>
            <div class="card-body">
                <table id="pejabatTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nama Pejabat</th>
                            <th>NIP</th>
                            <th>Jabatan</th>
                            <th>Fungsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($school_officials as $official): ?>
                        <tr>
                            <td><?= htmlspecialchars($official['nama_pejabat_sekolah']) ?></td>
                            <td><?= htmlspecialchars($official['nip_pejabat_sekolah'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($official['jabatan_pejabat_sekolah']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($official['fungsi_pejabat_sekolah']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Skrip Khusus Halaman Ini -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#pejabatTable').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "../../uis/adminlte-3.2.0/plugins/datatables/id.json"
        }
    });
});
</script>
