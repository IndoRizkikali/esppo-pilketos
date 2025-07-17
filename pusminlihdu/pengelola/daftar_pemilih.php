<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu)
 *
 * Halaman untuk menampilkan Daftar Pemilih Tetap (DPT).
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// 1. PENGAMBILAN DATA
// -----------------------------------------------------------------------------
$voters = [];
$error_message = '';
try {
    // $db sudah tersedia dari loader (index.php)
    // Query untuk mengambil data pemilih dan menggabungkannya dengan nama konstituensi
    $query = "
        SELECT 
            p.nomor_dpt_pemilih, 
            p.nama_pemilih, 
            p.jk_pemilih, 
            k.nama_konstituensi, 
            p.nama_akun_pemilih, 
            p.status_pemilih 
        FROM 
            data_pemilih p
        JOIN 
            data_konstituensi k ON p.kk_pemilih = k.kode_konstituensi
        ORDER BY 
            p.nomor_dpt_pemilih ASC";
            
    $result = $db->query($query);
    if ($result) {
        $voters = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data pemilih. Kesalahan: " . $e->getMessage();
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
        <h3 class="card-title">Daftar Pemilih Tetap (DPT)</h3>
        <div class="card-tools">
            <a href="index.php?page=tambah_pemilih" class="btn btn-primary btn-sm mx-1">
                <i class="fas fa-user-plus"></i> Tambah DPT Baru
            </a>
            <a href="index.php?page=edit_pemilih" class="btn btn-warning btn-sm mx-1">
                <i class="fas fa-user-edit"></i> Kelola DPT
            </a>
        </div>
    </div>
    <div class="card-body">
        <table id="dptTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 10%;">No. DPT</th>
                    <th>Nama Pemilih</th>
                    <th style="width: 15%;">Jenis Kelamin</th>
                    <th style="width: 15%;">Konstituensi</th>
                    <th style="width: 15%;">Nama Akun</th>
                    <th style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($voters as $voter): ?>
                <tr>
                    <td><?= htmlspecialchars($voter['nomor_dpt_pemilih']) ?></td>
                    <td><?= htmlspecialchars($voter['nama_pemilih']) ?></td>
                    <td><?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ',$voter['jk_pemilih'])))) ?></td>
                    <td><?= htmlspecialchars($voter['nama_konstituensi']) ?></td>
                    <td><?= htmlspecialchars($voter['nama_akun_pemilih']) ?></td>
                    <td>
                        <?php 
                            $status_badge = $voter['status_pemilih'] === 'SUDAH_MEMILIH' ? 'badge-success' : 'badge-warning';
                            echo "<span class='badge {$status_badge}'>" . htmlspecialchars(ucwords(strtolower(str_replace('_', ' ',$voter['status_pemilih'])))) . "</span>";
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
    $('#dptTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "../../uis/adminlte-3.2.0/plugins/datatables/id.json"
        },
        "order": [[0, "asc"]] // Urutkan berdasarkan No. DPT
    });
});
</script>
