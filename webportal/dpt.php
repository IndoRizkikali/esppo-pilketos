<?php
require_once __DIR__ . '/../helpers/db_helper.php';
$db = get_db_connection();
$voters = [];
try {
    $query = "SELECT p.nomor_dpt_pemilih, p.nama_pemilih, k.nama_konstituensi FROM data_pemilih p LEFT JOIN data_konstituensi k ON p.kk_pemilih = k.kode_konstituensi ORDER BY p.nomor_dpt_pemilih ASC";
    $result = $db->query($query);
    if ($result) $voters = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) { /* Abaikan error untuk publik */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Pemilih Tetap - Portal e-SPPO</title>
    <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">
    
    <!-- Bootstrap 5 dari folder uis/bootstrap-5.3.7 -->
    <link href="../uis/bootstrap-5.3.7/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- PERBAIKAN: DataTables CSS dari folder AdminLTE -->
    <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../uis/adminlte-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    
    <style>body { background-image: linear-gradient(rgba(248, 249, 250, 0.9), rgba(248, 249, 250, 0.9)), url('../assets/imgs/esppo/webportal.png'); background-size: cover; background-attachment: fixed; }</style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigasi -->
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm sticky-top"><div class="container"><a class="navbar-brand" href="../index.php">e-SPPO Portal</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav ms-auto"><li class="nav-item"><a href="../index.php" class="nav-link">Beranda</a></li><li class="nav-item"><a href="kandidat.php" class="nav-link">Kandidat</a></li><li class="nav-item"><a href="dpt.php" class="nav-link active">DPT</a></li><li class="nav-item"><a href="hasil.php" class="nav-link">Hasil</a></li></ul></div></div></nav>

    <!-- Konten Utama -->
    <main class="flex-grow-1 py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="fw-bold">Daftar Pemilih Tetap (DPT)</h1>
                <p class="lead text-muted">Pastikan nama Anda terdaftar sebagai pemilih dalam kegiatan pemilihan ini.</p>
            </div>
            <div class="card shadow-sm"><div class="card-body">
                <table id="dptTable" class="table table-striped table-hover" style="width:100%">
                    <thead><tr><th>No. DPT</th><th>Nama Lengkap Pemilih</th><th>Konstituensi</th></tr></thead>
                    <tbody>
                        <?php foreach ($voters as $voter): ?>
                        <tr><td><?= htmlspecialchars($voter['nomor_dpt_pemilih']) ?></td><td><?= htmlspecialchars($voter['nama_pemilih']) ?></td><td><?= htmlspecialchars($voter['nama_konstituensi'] ?? 'N/A') ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white mt-auto py-3 border-top"><div class="container text-center"><p class="text-muted mb-0">&copy; <?= date("Y") ?> Panitia Pemilihan.</p></div></footer>
    
    <!-- PERBAIKAN: jQuery dari folder AdminLTE -->
    <script src="../uis/adminlte-3.2.0/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 5 Bundle -->
    <script src="../uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
    <!-- PERBAIKAN: DataTables & Plugins dari folder AdminLTE -->
    <script src="../uis/adminlte-3.2.0/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../uis/adminlte-3.2.0/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="../uis/adminlte-3.2.0/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../uis/adminlte-3.2.0/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() { 
            $('#dptTable').DataTable({
                responsive: true,
                language: {
                    "decimal":        "",
                    "emptyTable":     "Tidak ada data yang tersedia di tabel",
                    "info":           "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    "infoEmpty":      "Menampilkan 0 sampai 0 dari 0 entri",
                    "infoFiltered":   "(difilter dari _MAX_ total entri)",
                    "infoPostFix":    "",
                    "thousands":      ".",
                    "lengthMenu":     "Tampilkan _MENU_ entri",
                    "loadingRecords": "Memuat...",
                    "processing":     "",
                    "search":         "Cari:",
                    "zeroRecords":    "Tidak ada data yang cocok ditemukan",
                    "paginate": {
                        "first":      "Awal",
                        "last":       "Akhir",
                        "next":       "Berikutnya",
                        "previous":   "Sebelumnya"
                    },
                    "aria": {
                        "sortAscending":  ": aktifkan untuk mengurutkan kolom secara menaik",
                        "sortDescending": ": aktifkan untuk mengurutkan kolom secara menurun"
                    }
                }
            }); 
        });
    </script>
</body>
</html>
