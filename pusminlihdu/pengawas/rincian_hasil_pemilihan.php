<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Rincian Hasil Pemilihan
 * pusminlihdu/pengelola/rincian_hasil_pemilihan.php
 * 
 * Halaman ini menampilkan rincian hasil pemilihan berdasarkan konstituensi yang dipilih.
 * Pengelola dapat melihat statistik pemilihan seperti total DPT, jumlah suara masuk,
 * tingkat partisipasi, dan perolehan suara per kandidat.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PENGAMBILAN DATA & PEMROSESAN FILTER
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

$db = get_db_connection();

$all_constituencies = [];
$stats = [
    'total_dpt' => 0,
    'total_suara_masuk' => 0,
    'partisipasi_persen' => 0.0,
];
$vote_results = [];
$error_message = '';
$selected_constituency_code = $_GET['konstituensi'] ?? null;
$selected_constituency_name = '';

try {
    // Ambil daftar semua konstituensi untuk dropdown filter
    $result_const = $db->query("SELECT kode_konstituensi, nama_konstituensi FROM data_konstituensi ORDER BY kode_konstituensi ASC");
    if ($result_const) $all_constituencies = $result_const->fetch_all(MYSQLI_ASSOC);

    // Jika ada konstituensi yang dipilih, proses datanya
    if ($selected_constituency_code) {
        // Validasi dan dapatkan nama konstituensi yang dipilih
        $stmt_name = $db->prepare("SELECT nama_konstituensi FROM data_konstituensi WHERE kode_konstituensi = ?");
        $stmt_name->bind_param('s', $selected_constituency_code);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        if ($result_name->num_rows > 0) {
            $selected_constituency_name = $result_name->fetch_assoc()['nama_konstituensi'];
        } else {
            throw new Exception("Kode konstituensi tidak valid.");
        }
        $stmt_name->close();

        // Ambil statistik dengan filter
        $stmt_dpt = $db->prepare("SELECT COUNT(*) FROM data_pemilih WHERE kk_pemilih = ?");
        $stmt_dpt->bind_param('s', $selected_constituency_code);
        $stmt_dpt->execute();
        $stats['total_dpt'] = $stmt_dpt->get_result()->fetch_row()[0] ?? 0;
        $stmt_dpt->close();

        $stmt_suara = $db->prepare("SELECT COUNT(*) FROM data_suara WHERE kk_pemilih = ?");
        $stmt_suara->bind_param('s', $selected_constituency_code);
        $stmt_suara->execute();
        $stats['total_suara_masuk'] = $stmt_suara->get_result()->fetch_row()[0] ?? 0;
        $stmt_suara->close();

        if ($stats['total_dpt'] > 0) {
            $stats['partisipasi_persen'] = ($stats['total_suara_masuk'] / $stats['total_dpt']) * 100;
        }

        // Ambil perolehan suara dengan filter
        $query_votes = "
            SELECT k.no_urut_kandidat, k.nama_calon_1, COUNT(s.kode_id_suara) AS jumlah_suara
            FROM data_kandidat k
            LEFT JOIN data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat AND s.kk_pemilih = ?
            GROUP BY k.no_urut_kandidat ORDER BY k.no_urut_kandidat ASC";
        $stmt_votes = $db->prepare($query_votes);
        $stmt_votes->bind_param('s', $selected_constituency_code);
        $stmt_votes->execute();
        $result_votes = $stmt_votes->get_result();
        if ($result_votes) $vote_results = $result_votes->fetch_all(MYSQLI_ASSOC);
        $stmt_votes->close();
    }
} catch (Exception $e) {
    $error_message = "Terjadi kesalahan: " . $e->getMessage();
}
?>

<!-- Kartu Filter -->
<div class="card card-info card-outline">
    <div class="card-header"><h3 class="card-title">Filter Rincian Hasil</h3></div>
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="rincian_hasil_pemilihan">
        <div class="card-body">
            <div class="form-group">
                <label>Pilih Konstituensi</label>
                <select name="konstituensi" class="form-control select2" style="width: 100%;" required>
                    <option value="" disabled selected>-- Pilih konstituensi untuk melihat rincian --</option>
                    <?php foreach($all_constituencies as $c): ?>
                        <option value="<?= htmlspecialchars($c['kode_konstituensi']) ?>" <?= ($selected_constituency_code == $c['kode_konstituensi']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nama_konstituensi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-info">Tampilkan Rincian</button></div>
    </form>
</div>

<!-- Tampilkan hasil hanya jika konstituensi sudah dipilih -->
<?php if ($selected_constituency_code): ?>
<hr>
<h3 class="mb-3">Hasil untuk Konstituensi: <strong><?= htmlspecialchars($selected_constituency_name) ?></strong></h3>

<!-- Statistik Ringkas Terfilter -->
<div class="row">
    <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3><?= number_format($stats['total_dpt']) ?></h3><p>Total DPT di Konstituensi Ini</p></div><div class="icon"><i class="fas fa-users"></i></div></div></div>
    <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3><?= number_format($stats['total_suara_masuk']) ?></h3><p>Suara Masuk dari Konstituensi Ini</p></div><div class="icon"><i class="fas fa-vote-yea"></i></div></div></div>
    <div class="col-md-4"><div class="small-box bg-primary"><div class="inner"><h3><?= number_format($stats['partisipasi_persen'], 2) ?><sup style="font-size: 20px">%</sup></h3><p>Tingkat Partisipasi</p></div><div class="icon"><i class="fas fa-chart-pie"></i></div></div></div>
</div>

<!-- Grafik dan Rincian Terfilter -->
<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title">Grafik & Rincian Perolehan Suara</h3></div>
    <div class="card-body">
        <?php if ($stats['total_suara_masuk'] > 0): ?>
            <div class="chart"><canvas id="filteredVoteChart" style="min-height: 250px; height: 250px; max-height: 250px; width: 100%;"></canvas></div>
        <?php else: ?>
            <div class="text-center text-muted p-4">Tidak ada suara yang masuk dari konstituensi ini.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Skrip untuk Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const voteData = <?= json_encode($vote_results) ?>;
    if (voteData.length > 0 && <?= $stats['total_suara_masuk'] ?> > 0) {
        const labels = voteData.map(item => `No. ${item.no_urut_kandidat}`);
        const data = voteData.map(item => item.jumlah_suara);
        new Chart(document.getElementById('filteredVoteChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor : ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d'],
                }],
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }
});
</script>
<?php endif; ?>

<!-- Skrip untuk inisialisasi Select2 -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi Select2
    $('.select2').select2({ theme: 'bootstrap4' });
    });
</script>
