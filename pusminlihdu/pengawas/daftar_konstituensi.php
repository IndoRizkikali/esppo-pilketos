<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengawas, Daftar Konstituensi
 * pusminlihdu/pengawas/daftar_konstituensi.php
 * 
 * Daftar konstituensi pemilih yang dikelola oleh panitia pemilihan.
 * Daftar konstituensi ini menampilkan informasi dasar seperti kode, nama, tipe, dan status 
 * konstituensi. Data konstituensi digunakan untuk mengelompokan pemilih berdasarkan kategori 
 * atau kriteria tertentu, serta menjadi data referensi untuk pengusulan kandidat.
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

$constituencies = [];
$error_message = '';

try {
    $result = $db->query("SELECT * FROM data_konstituensi ORDER BY kode_konstituensi ASC");
    if ($result) {
        $constituencies = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data konstituensi. Kesalahan: " . $e->getMessage();
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
        <h3 class="card-title">Daftar Konstituensi Pemilih</h3>
    </div>
    <div class="card-body">
        <table id="konstituensiTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 15%;">Kode</th>
                    <th>Nama Konstituensi</th>
                    <th style="width: 20%;">Tipe</th>
                    <th style="width: 20%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($constituencies as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['kode_konstituensi']) ?></td>
                    <td><?= htmlspecialchars($item['nama_konstituensi']) ?></td>
                    <td>
                        <?php 
                            $tipe_badge = 'badge-secondary';
                            if ($item['tipe_konstituensi'] === 'KELAS') $tipe_badge = 'badge-info';
                            if ($item['tipe_konstituensi'] === 'KEPEGAWAIAN') $tipe_badge = 'badge-warning';
                            echo "<span class='badge {$tipe_badge}'>" . htmlspecialchars($item['tipe_konstituensi']) . "</span>";
                        ?>
                    </td>
                    <td>
                        <?php 
                            $status_badge = $item['status_konstituensi'] === 'DIAKTIFKAN' ? 'badge-success' : 'badge-danger';
                            echo "<span class='badge {$status_badge}'>" . htmlspecialchars($item['status_konstituensi']) . "</span>";
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Skrip Khusus Halaman Ini -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#konstituensiTable').DataTable({
        "paging": true,
        "lengthChange": true,
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
