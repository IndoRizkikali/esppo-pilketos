<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengawas, Kehadiran Pemilih
 * pusminlihdu/pengawas/kehadiran_pemilih.php
 *
 * Halaman ini menampilkan daftar kehadiran pemilih yang telah login ke SSE dan telah memilih.
 * Pengawas dapat melihat statistik kehadiran seperti total DPT, jumlah pemilih yang hadir,
 * dan persentase kehadiran.
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

$attendance_list = [];
$error_message = '';
$stats = ['total_dpt' => 0, 'total_hadir' => 0, 'persen_hadir' => 0.0];

try {
    // Ambil statistik
    $stats['total_dpt'] = $db->query("SELECT COUNT(*) FROM data_pemilih")->fetch_row()[0] ?? 0;
    $stats['total_hadir'] = $db->query("SELECT COUNT(*) FROM data_kehadiran")->fetch_row()[0] ?? 0;
    if ($stats['total_dpt'] > 0) {
        $stats['persen_hadir'] = ($stats['total_hadir'] / $stats['total_dpt']) * 100;
    }

    // Ambil daftar kehadiran
    $query = "
        SELECT 
            h.urutan_kehadiran, 
            h.stempel_waktu_kehadiran, 
            p.nama_pemilih, 
            k.nama_konstituensi 
        FROM 
            data_kehadiran h
        LEFT JOIN 
            data_pemilih p ON h.id_unik_pemilih = p.id_unik_pemilih
        LEFT JOIN 
            data_konstituensi k ON h.kk_pemilih = k.kode_konstituensi
        ORDER BY 
            h.stempel_waktu_kehadiran ASC";
            
    $result = $db->query($query);
    if ($result) {
        $attendance_list = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data kehadiran. Kesalahan: " . $e->getMessage();
}
?>

<!-- Tampilkan pesan error jika ada -->
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger"><i class="icon fas fa-ban"></i> <?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<div id="auto-refresh-container">
    <!-- Statistik Kehadiran -->
    <div class="row">
        <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3><?= number_format($stats['total_dpt']) ?></h3><p>Total DPT</p></div><div class="icon"><i class="fas fa-users"></i></div></div></div>
        <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3><?= number_format($stats['total_hadir']) ?></h3><p>Total Kehadiran (Login)</p></div><div class="icon"><i class="fas fa-user-check"></i></div></div></div>
        <div class="col-md-4"><div class="small-box bg-primary"><div class="inner"><h3><?= number_format($stats['persen_hadir'], 2) ?><sup style="font-size: 20px">%</sup></h3><p>Tingkat Kehadiran</p></div><div class="icon"><i class="fas fa-percentage"></i></div></div></div>
    </div>

    <!-- Tabel Daftar Kehadiran -->
    <div class="card card-primary card-outline">
        <div class="card-header"><h3 class="card-title">Daftar Kehadiran Pemilih</h3></div>
        <div class="card-body">
            <table id="kehadiranTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 10%;">No.</th>
                        <th style="width: 25%;">Waktu Kehadiran</th>
                        <th>Nama Pemilih</th>
                        <th style="width: 25%;">Konstituensi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_list as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['urutan_kehadiran']) ?></td>
                        <td><?= date('d M Y, H:i:s', strtotime($item['stempel_waktu_kehadiran'])) ?></td>
                        <td><?= htmlspecialchars($item['nama_pemilih'] ?? 'Data Pemilih Dihapus') ?></td>
                        <td><?= htmlspecialchars($item['nama_konstituensi'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Skrip Khusus Halaman Ini -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const refreshInterval = 30000; // 30 detik untuk pengawas

    function initializeDataTables() {
        if ($.fn.DataTable.isDataTable('#kehadiranTable')) {
            $('#kehadiranTable').DataTable().destroy();
        }
        $('#kehadiranTable').DataTable({
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
            "order": [[0, "asc"]] // Urutkan berdasarkan nomor urutan kehadiran
        });
    }

    function refreshContent() {
        const container = document.getElementById('auto-refresh-container');
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('auto-refresh-container');
                if (newContent) {
                    container.innerHTML = newContent.innerHTML;
                    initializeDataTables();
                }
            })
            .catch(error => console.error('Gagal memuat ulang konten:', error));
    }

    initializeDataTables();
    setInterval(refreshContent, refreshInterval);
});
</script>
