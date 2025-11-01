<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengawas, Dashboard
 * pusminlihdu/pengawas/dashboard.php
 *
 * File ini menangani pemuatan halaman dashboard untuk pengawas,
 * menampilkan statistik pemilihan, grafik perolehan suara,
 * dan informasi penting lainnya.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PENGAMBILAN DATA STATISTIK (HANYA UNTUK PEMUATAN AWAL)
// ----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

// Inisialisasi variabel untuk menyimpan statistik dan data lainnya
$stats = [
    'total_dpt' => 0, 'total_kandidat' => 0, 'total_suara_masuk' => 0,
    'total_kehadiran' => 0, 'partisipasi_suara' => 0.0, 'partisipasi_hadir' => 0.0,
];
$election_data = null;
$vote_distribution = [];
$error_message = '';

try {
    // Ambil data pemilihan
    $result_election = $db->query("SELECT * FROM data_pemilihan LIMIT 1");
    $election_data = $result_election ? $result_election->fetch_assoc() : null;

    // Ambil statistik umum
    $stats['total_dpt'] = $db->query("SELECT COUNT(*) FROM data_pemilih")->fetch_row()[0] ?? 0;
    $stats['total_kandidat'] = $db->query("SELECT COUNT(*) FROM data_kandidat")->fetch_row()[0] ?? 0;
    $stats['total_suara_masuk'] = $db->query("SELECT COUNT(*) FROM data_suara")->fetch_row()[0] ?? 0;
    $stats['total_kehadiran'] = $db->query("SELECT COUNT(*) FROM data_kehadiran")->fetch_row()[0] ?? 0;

    // Hitung persentase
    if ($stats['total_dpt'] > 0) {
        $stats['partisipasi_suara'] = ($stats['total_suara_masuk'] / $stats['total_dpt']) * 100;
        $stats['partisipasi_hadir'] = ($stats['total_kehadiran'] / $stats['total_dpt']) * 100;
    }

    // Ambil data untuk grafik
    $result_votes = $db->query("SELECT k.no_urut_kandidat, k.nama_calon_1, COUNT(s.kode_id_suara) as jumlah_suara FROM data_kandidat k LEFT JOIN data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat GROUP BY k.no_urut_kandidat ORDER BY k.no_urut_kandidat ASC");
    if ($result_votes) $vote_distribution = $result_votes->fetch_all(MYSQLI_ASSOC);

} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data dashboard. Kesalahan: " . $e->getMessage();
}
?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger"><i class="icon fas fa-ban"></i> <?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<!-- Baris Info Box Statistik Utama -->
<div class="row">
  <div class="col-12 col-sm-6 col-md-3"><div class="info-box"><span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users"></i></span><div class="info-box-content"><span class="info-box-text">Total DPT</span><span class="info-box-number" id="stats-total-dpt"><?= number_format($stats['total_dpt']) ?></span></div></div></div>
  <div class="col-12 col-sm-6 col-md-3"><div class="info-box"><span class="info-box-icon bg-info elevation-1"><i class="fas fa-user-tie"></i></span><div class="info-box-content"><span class="info-box-text">Jumlah Kandidat</span><span class="info-box-number" id="stats-total-kandidat"><?= number_format($stats['total_kandidat']) ?></span></div></div></div>
  <div class="clearfix hidden-md-up"></div>
  <div class="col-12 col-sm-6 col-md-3"><div class="info-box"><span class="info-box-icon bg-success elevation-1"><i class="fas fa-vote-yea"></i></span><div class="info-box-content"><span class="info-box-text">Suara Masuk</span><span class="info-box-number" id="stats-total-suara-masuk"><?= number_format($stats['total_suara_masuk']) ?></span></div></div></div>
  <div class="col-12 col-sm-6 col-md-3"><div class="info-box"><span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-user-check"></i></span><div class="info-box-content"><span class="info-box-text">Total Kehadiran</span><span class="info-box-number" id="stats-total-kehadiran"><?= number_format($stats['total_kehadiran']) ?></span></div></div></div>
</div>

<div class="row">

    <div class="col-md-8" id="grafik-suara">
        <!-- Grafik Hasil Perolehan Suara Sementara -->
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="far fa-chart-bar"></i> Grafik Perolehan Suara Sementara</h3></div>
            <div class="card-body">
                <?php if (!empty($vote_distribution)): ?>
                    <div class="chart"><canvas id="voteChart" style="min-height: 300px; height: 300px; max-height: 300px; width: 100%;"></canvas></div>
                <?php else: ?>
                    <div class="text-center text-muted py-5" id="vote-chart-placeholder"><p>Belum ada suara yang masuk untuk ditampilkan.</p></div>
                    <div class="chart" style="display:none;"><canvas id="voteChart" style="min-height: 300px; height: 300px; max-height: 300px; width: 100%;"></canvas></div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted text-center">
                Grafik diperbarui setiap 30 detik.
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Informasi Kegiatan Pemilihan -->
        <div class="card card-info card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-alt"></i> Informasi Pemilihan</h3></div>
            <div class="card-body" id="election-info-card">
                <?php if ($election_data): 
                    $fmt = new IntlDateFormatter('id_ID', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, IntlDateFormatter::GREGORIAN);
                    $tanggal_mulai_str = $fmt->format(new DateTime($election_data['tanggal_mulai']));
                    $tanggal_selesai_str = !empty($election_data['tanggal_selesai']) ? $fmt->format(new DateTime($election_data['tanggal_selesai'])) : '<i>Tidak diatur</i>';
                    
                    $status = ucwords(strtolower(str_replace('_', ' ', $election_data['status_pemilihan'])));
                    $badge_class = 'info';
                    if (str_contains($status, 'Selesai')) $badge_class = 'success';
                    if (str_contains($status, 'Belum')) $badge_class = 'warning';
                ?>
                    <h5 class="font-weight-bold"><?= htmlspecialchars($election_data['nama_pemilihan']) ?></h5>
                    <ul class="list-unstyled">
                        <li><strong>Status:</strong> <span class="badge badge-<?= $badge_class ?>"><?= $status ?></span></li>
                        <li><strong>Masa Bakti:</strong> <?= htmlspecialchars($election_data['masa_bakti']) ?></li>
                        <li><strong>Mulai:</strong> <?= $tanggal_mulai_str ?></li>
                        <li><strong>Selesai:</strong> <?= $tanggal_selesai_str ?></li>
                    </ul>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6 border-right">
                            <div class="description-block">
                                <h5 class="description-header text-success" id="stats-partisipasi-suara"><?= number_format($stats['partisipasi_suara'], 2) ?>%</h5>
                                <span class="description-text">PARTISIPASI SUARA</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="description-block">
                                <h5 class="description-header text-info" id="stats-partisipasi-hadir"><?= number_format($stats['partisipasi_hadir'], 2) ?>%</h5>
                                <span class="description-text">TINGKAT KEHADIRAN</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">Belum ada data kegiatan pemilihan yang dibuat.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Skrip untuk Chart.js dan pembaruan dinamis -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    let voteChart;
    const initialVoteData = <?= json_encode($vote_distribution) ?>;

    function initOrUpdateChart(voteData) {
        const chartCanvas = document.getElementById('voteChart');
        const chartPlaceholder = document.getElementById('vote-chart-placeholder');
        
        if (voteData.length > 0) {
            if (chartPlaceholder) chartPlaceholder.style.display = 'none';
            chartCanvas.parentElement.style.display = 'block';

            const labels = voteData.map(item => `No. ${item.no_urut_kandidat}`);
            const data = voteData.map(item => item.jumlah_suara);

            if (voteChart) {
                voteChart.data.labels = labels;
                voteChart.data.datasets[0].data = data;
                voteChart.update();
            } else {
                voteChart = new Chart(chartCanvas.getContext('2d'), {
                    type: 'bar',
                    data: { labels: labels, datasets: [{ label: 'Jumlah Suara', backgroundColor: 'rgba(0, 123, 255, 0.8)', data: data }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
                });
            }
        } else {
            if (chartPlaceholder) chartPlaceholder.style.display = 'block';
            chartCanvas.parentElement.style.display = 'none';
        }
    }

    function updateDashboard() {
        fetch('../api/dashboard_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error fetching dashboard data:', data.error);
                    return;
                }

                // Update info boxes
                document.getElementById('stats-total-dpt').textContent = new Intl.NumberFormat('id-ID').format(data.stats.total_dpt);
                document.getElementById('stats-total-kandidat').textContent = new Intl.NumberFormat('id-ID').format(data.stats.total_kandidat);
                document.getElementById('stats-total-suara-masuk').textContent = new Intl.NumberFormat('id-ID').format(data.stats.total_suara_masuk);
                document.getElementById('stats-total-kehadiran').textContent = new Intl.NumberFormat('id-ID').format(data.stats.total_kehadiran);

                // Update participation stats
                document.getElementById('stats-partisipasi-suara').textContent = data.stats.partisipasi_suara.toFixed(2) + '%';
                document.getElementById('stats-partisipasi-hadir').textContent = data.stats.partisipasi_hadir.toFixed(2) + '%';

                // Update chart
                initOrUpdateChart(data.vote_distribution);
            })
            .catch(error => console.error('Error:', error));
    }

    // Inisialisasi grafik saat halaman dimuat
    initOrUpdateChart(initialVoteData);

    // Atur interval untuk memuat ulang data setiap tiga puluh detik atau setengah menit (30000 ms)
    setInterval(updateDashboard, 30000);
});
</script>
