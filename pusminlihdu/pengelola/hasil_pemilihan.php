<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Hasil Pemilihan
 * pusminlihdu/pengelola/hasil_pemilihan.php
 *
 * Halaman ini menampilkan hasil pemilihan secara ringkas.
 * Pengelola dapat melihat statistik pemilihan seperti total DPT,
 * jumlah suara masuk, tingkat partisipasi, dan perolehan suara per kandidat.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PENGAMBILAN DATA STATISTIK
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

$db = get_db_connection();

$stats = [
    'total_dpt' => 0,
    'total_suara_masuk' => 0,
    'total_kehadiran' => 0,
    'belum_memilih' => 0,
    'partisipasi_persen' => 0.0,
];
$vote_results = [];
$error_message = '';

try {
    // Ambil statistik umum dalam satu query jika memungkinkan, atau beberapa query cepat
    $stats['total_dpt'] = $db->query("SELECT COUNT(*) FROM data_pemilih")->fetch_row()[0] ?? 0;
    $stats['total_suara_masuk'] = $db->query("SELECT COUNT(*) FROM data_suara")->fetch_row()[0] ?? 0;
    $stats['total_kehadiran'] = $db->query("SELECT COUNT(*) FROM data_kehadiran")->fetch_row()[0] ?? 0;
    
    // Hitung sisa statistik
    $stats['belum_memilih'] = $stats['total_dpt'] - $stats['total_suara_masuk'];
    if ($stats['total_dpt'] > 0) {
        $stats['partisipasi_persen'] = ($stats['total_suara_masuk'] / $stats['total_dpt']) * 100;
    }

    // Ambil data perolehan suara per kandidat
    $query_votes = "
        SELECT 
            k.no_urut_kandidat,
            k.nama_calon_1,
            k.nama_calon_2,
            k.foto_kandidat,
            COUNT(s.kode_id_suara) AS jumlah_suara
        FROM 
            data_kandidat k
        LEFT JOIN 
            data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat
        GROUP BY 
            k.no_urut_kandidat
        ORDER BY 
            k.no_urut_kandidat ASC";
    
    $result_votes = $db->query($query_votes);
    if ($result_votes) {
        $vote_results = $result_votes->fetch_all(MYSQLI_ASSOC);
    }

} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data hasil pemilihan. Kesalahan: " . $e->getMessage();
}
?>

<!-- Tampilkan pesan error jika ada -->
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger"><i class="icon fas fa-ban"></i> <?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<div id="auto-refresh-container">
    <!-- Statistik Ringkas -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info"><div class="inner"><h3 id="stats-total-dpt"><?= number_format($stats['total_dpt']) ?></h3><p>Total DPT</p></div><div class="icon"><i class="fas fa-users"></i></div></div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success"><div class="inner"><h3 id="stats-suara-masuk"><?= number_format($stats['total_suara_masuk']) ?></h3><p>Suara Masuk (Partisipasi)</p></div><div class="icon"><i class="fas fa-vote-yea"></i></div></div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning"><div class="inner"><h3 id="stats-belum-memilih"><?= number_format($stats['belum_memilih']) ?></h3><p>Belum Memilih (Golput)</p></div><div class="icon"><i class="fas fa-user-clock"></i></div></div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary"><div class="inner"><h3 id="stats-partisipasi-persen"><?= number_format($stats['partisipasi_persen'], 2) ?><sup style="font-size: 20px">%</sup></h3><p>Tingkat Partisipasi</p></div><div class="icon"><i class="fas fa-chart-pie"></i></div></div>
        </div>
    </div>

    <div class="row">
        <!-- Grafik Perolehan Suara -->
        <div class="col-md-7">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="far fa-chart-bar"></i> Grafik Perolehan Suara</h3></div>
                <div class="card-body">
                    <div class="chart"><canvas id="voteChart" style="min-height: 350px; height: 350px; max-height: 350px; width: 100%;"></canvas></div>
                </div>
            </div>
        </div>
        <!-- Rincian Perolehan Suara -->
        <div class="col-md-5">
            <div class="card card-info card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-poll"></i> Rincian Suara per Kandidat</h3></div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2" id="vote-results-list">
                        <?php if (empty($vote_results)): ?>
                            <li class="item"><div class="text-center text-muted p-4">Belum ada data.</div></li>
                        <?php else: ?>
                            <?php foreach ($vote_results as $result): 
                                $percentage = $stats['total_suara_masuk'] > 0 ? ($result['jumlah_suara'] / $stats['total_suara_masuk']) * 100 : 0;
                            ?>
                            <li class="item">
                                <div class="product-img">
                                    <img src="../../assets/imgs/sse-foto-kandidat/<?= htmlspecialchars($result['foto_kandidat'] ?? 'default.png') ?>" alt="Foto" class="img-size-50">
                                </div>
                                <div class="product-info">
                                    <span class="product-title">No. Urut <?= htmlspecialchars($result['no_urut_kandidat']) ?>: <?= htmlspecialchars($result['nama_calon_1']) ?></span>
                                    <span class="badge badge-primary float-right"><?= number_format($result['jumlah_suara']) ?> Suara</span>
                                    <span class="product-description">
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <small><?= number_format($percentage, 2) ?>% dari total suara masuk</small>
                                    </span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Skrip untuk Chart.js dan pembaruan dinamis -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const refreshInterval = 15000; // 15 detik untuk pengelola
    let voteChart;
    const initialVoteData = <?= json_encode($vote_results) ?>;

    function initOrUpdateChart(voteData) {
        const chartCanvas = document.getElementById('voteChart');
        if (!chartCanvas) return;
        const ctx = chartCanvas.getContext('2d');

        const labels = voteData.map(item => `No. ${item.no_urut_kandidat}\n${item.nama_calon_1}`);
        const data = voteData.map(item => item.jumlah_suara);

        if (voteChart) {
            voteChart.data.labels = labels;
            voteChart.data.datasets[0].data = data;
            voteChart.update();
        } else {
            voteChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Suara',
                        backgroundColor: 'rgba(0, 123, 255, 0.8)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        data: data
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{ ticks: { beginAtZero: true, callback: function(value) { if (Number.isInteger(value)) { return value; } } } }]
                    },
                    legend: { display: false }
                }
            });
        }
    }

    function updateUI(data) {
        // Update stats
        document.getElementById('stats-total-dpt').textContent = new Intl.NumberFormat('id-ID').format(data.stats.total_dpt);
        document.getElementById('stats-suara-masuk').textContent = new Intl.NumberFormat('id-ID').format(data.stats.total_suara_masuk);
        document.getElementById('stats-belum-memilih').textContent = new Intl.NumberFormat('id-ID').format(data.stats.belum_memilih);
        document.getElementById('stats-partisipasi-persen').innerHTML = `${data.stats.partisipasi_persen.toFixed(2)}<sup style="font-size: 20px">%</sup>`;

        // Update vote results list
        const listContainer = document.getElementById('vote-results-list');
        listContainer.innerHTML = '';
        if (data.vote_results.length > 0) {
            data.vote_results.forEach(result => {
                const totalSuaraMasuk = data.stats.total_suara_masuk;
                const percentage = totalSuaraMasuk > 0 ? (result.jumlah_suara / totalSuaraMasuk) * 100 : 0;
                const foto = result.foto_kandidat || 'default.png';
                listContainer.innerHTML += `
                    <li class="item">
                        <div class="product-img">
                            <img src="../../assets/imgs/sse-foto-kandidat/${foto}" alt="Foto" class="img-size-50">
                        </div>
                        <div class="product-info">
                            <span class="product-title">No. Urut ${result.no_urut_kandidat}: ${result.nama_calon_1}</span>
                            <span class="badge badge-primary float-right">${new Intl.NumberFormat('id-ID').format(result.jumlah_suara)} Suara</span>
                            <span class="product-description">
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-primary" style="width: ${percentage.toFixed(2)}%"></div>
                                </div>
                                <small>${percentage.toFixed(2)}% dari total suara masuk</small>
                            </span>
                        </div>
                    </li>`;
            });
        } else {
            listContainer.innerHTML = '<li class="item"><div class="text-center text-muted p-4">Belum ada data.</div></li>';
        }

        // Update chart
        initOrUpdateChart(data.vote_results);
    }

    function fetchData() {
        fetch('../api/hasil_pemilihan_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Gagal mengambil data:', data.error);
                    return;
                }
                updateUI(data);
            })
            .catch(error => console.error('Gagal memuat ulang konten:', error));
    }

    // Initial load
    if (initialVoteData.length > 0) {
        initOrUpdateChart(initialVoteData);
    }
    
    // Set interval for refreshing
    setInterval(fetchData, refreshInterval);
});
</script>
