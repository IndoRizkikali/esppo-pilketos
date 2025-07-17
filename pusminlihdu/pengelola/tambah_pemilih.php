<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu)
 *
 * Halaman antarmuka untuk menambah pemilih secara manual dan massal.
 *
 * @version 2.0.1
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// Pastikan koneksi DB selalu tersedia di awal.
// Meskipun loader sudah menyertakan helper, pemanggilan ulang ini memastikan
// $db ada bahkan setelah redirect dari skrip lain.
$db = get_db_connection();

// 1. LOGIKA PEMROSESAN (HANYA UNTUK TAMBAH MANUAL)
// -----------------------------------------------------------------------------
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_manual') {
    try {
        $nomor_dpt = filter_var($_POST['nomor_dpt_pemilih'], FILTER_VALIDATE_INT);
        $nama_pemilih = trim($_POST['nama_pemilih']);
        $jk_pemilih = $_POST['jk_pemilih'];
        $kk_pemilih = $_POST['kk_pemilih'];
        $nama_akun = trim($_POST['nama_akun_pemilih']);
        $password = $_POST['kata_sandi_pemilih'];

        if ($nomor_dpt === false || empty($nama_pemilih) || empty($jk_pemilih) || empty($kk_pemilih) || empty($nama_akun) || empty($password)) {
            throw new Exception("Semua field pada form tambah manual wajib diisi.");
        }

        // Validasi duplikat Nomor DPT dan Nama Akun
        $stmt_check = $db->prepare("SELECT nomor_dpt_pemilih FROM data_pemilih WHERE nomor_dpt_pemilih = ? OR nama_akun_pemilih = ?");
        $stmt_check->bind_param('ss', $nomor_dpt, $nama_akun);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            throw new Exception("Nomor DPT atau Nama Akun sudah digunakan.");
        }
        $stmt_check->close();

        // Generate data kriptografi
        $id_unik = generate_unique_id(16);
        $iv = generate_unique_id(16);
        $hashed_password = hash_password($password);
        $status = 'BELUM_MEMILIH';

        $stmt_add = $db->prepare("INSERT INTO data_pemilih (id_unik_pemilih, nomor_dpt_pemilih, nama_pemilih, jk_pemilih, kk_pemilih, nama_akun_pemilih, kata_sandi_pemilih, iv_akun_pemilih, status_pemilih) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_add->bind_param('sssssssss', $id_unik, $nomor_dpt, $nama_pemilih, $jk_pemilih, $kk_pemilih, $nama_akun, $hashed_password, $iv, $status);
        $stmt_add->execute();
        $stmt_add->close();
        
        $success_message = "Pemilih '{$nama_pemilih}' dengan No. DPT {$nomor_dpt} berhasil ditambahkan.";

    } catch (Exception $e) {
        $error_message = "Gagal menambah pemilih: " . $e->getMessage();
    }
}

// 2. PENGAMBILAN DATA UNTUK FORM
// -----------------------------------------------------------------------------
$next_dpt_number = 1;
$active_constituencies = [];
try {
    // Ambil nomor DPT berikutnya
    $result_dpt = $db->query("SELECT MAX(nomor_dpt_pemilih) as max_dpt FROM data_pemilih");
    if ($result_dpt && $row = $result_dpt->fetch_assoc()) {
        $next_dpt_number = ($row['max_dpt'] ?? 0) + 1;
    }

    // Ambil daftar konstituensi yang aktif
    $result_const = $db->query("SELECT kode_konstituensi, nama_konstituensi FROM data_konstituensi WHERE status_konstituensi = 'DIAKTIFKAN' ORDER BY kode_konstituensi ASC");
    if ($result_const) {
        $active_constituencies = $result_const->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data pendukung untuk form. Kesalahan: " . $e->getMessage();
}

// Tampilkan pesan status dari proses unggah massal
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success' && !empty($_GET['message'])) {
        $success_message = htmlspecialchars($_GET['message']);
    } elseif ($_GET['status'] === 'error' && !empty($_GET['message'])) {
        $error_message = htmlspecialchars($_GET['message']);
    }
}

?>

<!-- Tampilkan pesan sukses atau error -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5><i class="icon fas fa-check"></i> Berhasil!</h5>
    <?= $success_message ?>
</div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5><i class="icon fas fa-ban"></i> Gagal!</h5>
    <?= $error_message ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- Kolom Tambah Manual -->
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Tambah Pemilih Manual</h3></div>
            <form action="index.php?page=tambah_pemilih" method="POST">
                <input type="hidden" name="action" value="add_manual">
                <div class="card-body">
                    <div class="form-group">
                        <label>Nomor DPT</label>
                        <input type="number" name="nomor_dpt_pemilih" class="form-control" value="<?= $next_dpt_number ?>" required>
                        <small class="form-text text-muted">Nomor DPT berikutnya disarankan. Anda dapat mengubahnya jika perlu.</small>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap Pemilih</label>
                        <input type="text" name="nama_pemilih" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jk_pemilih" class="form-control select2" style="width: 100%;">
                            <option value="PRIA">PRIA</option>
                            <option value="WANITA">WANITA</option>
                            <option value="TIDAK_DIKETAHUI">TIDAK DIKETAHUI</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Konstituensi</label>
                        <select name="kk_pemilih" class="form-control select2" style="width: 100%;" required>
                            <option value="" disabled selected>-- Pilih Konstituensi --</option>
                            <?php foreach($active_constituencies as $item): ?>
                                <option value="<?= htmlspecialchars($item['kode_konstituensi']) ?>"><?= htmlspecialchars($item['nama_konstituensi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Akun SSE</label>
                        <input type="text" name="nama_akun_pemilih" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kata Sandi SSE</label>
                        <input type="password" name="kata_sandi_pemilih" class="form-control" required>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Tambah Pemilih</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Unggah Massal -->
    <div class="col-md-6">
        <div class="card card-secondary">
            <div class="card-header"><h3 class="card-title">Tambah Pemilih Massal</h3></div>
            <form action="tambah_massal_pemilih.php" method="POST" enctype="multipart/form-data">
                <div class="card-body">
                    <p>Gunakan fitur ini untuk menambah banyak data pemilih sekaligus dari file spreadsheet.</p>
                    <div class="form-group">
                        <label>1. Unduh Templat</label><br>
                        <a href="../../assets/spreadsheets/templat/Templat_Format_Data_Akun_DPT_Pusminlihdu_eSPPO.xltx" class="btn btn-info">
                            <i class="fas fa-file-download"></i> Unduh Templat (XLTX)
                        </a>
                        <small class="form-text text-muted">Pastikan data Anda sesuai dengan format di dalam templat.</small>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label for="dptFile">2. Unggah Berkas</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="dptFile" name="dpt_file" accept=".csv, .xls, .xlsx" required>
                            <label class="custom-file-label" for="dptFile">Pilih berkas...</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-secondary"><i class="fas fa-file-import"></i> Impor Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Select2
    $('.select2').select2({ theme: 'bootstrap4' });
    // Inisialisasi bs-custom-file-input
    bsCustomFileInput.init();
});
</script>
