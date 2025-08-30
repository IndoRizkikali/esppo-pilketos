<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Tambah Kandidat
 * pusminlihdu/pengelola/tambah_kandidat.php
 * 
 * Halaman ini menangani proses penambahan kandidat baru ke dalam sistem.
 * Pengelola dapat menambahkan kandidat dengan mengisi formulir yang berisi
 * data calon, visi, misi, dan foto kandidat.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PENGAMBILAN DATA PENDUKUNG & PEMROSESAN FORM
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

$db = get_db_connection();

$success_message = '';
$error_message = '';
$election_settings = null;
$next_candidate_number = 1;
$active_constituencies = [];

try {
    // Ambil pengaturan pemilihan untuk menentukan mode form
    $result_settings = $db->query("SELECT tipe_peserta_pemilihan, mode_tampilan_sse FROM data_pemilihan LIMIT 1");
    if ($result_settings) $election_settings = $result_settings->fetch_assoc();

    // Ambil nomor urut kandidat berikutnya
    $result_num = $db->query("SELECT MAX(no_urut_kandidat) as max_num FROM data_kandidat");
    if ($result_num && $row = $result_num->fetch_assoc()) {
        $next_candidate_number = ($row['max_num'] ?? 0) + 1;
    }

    // Ambil konstituensi aktif untuk dropdown
    $result_const = $db->query("SELECT kode_konstituensi, nama_konstituensi FROM data_konstituensi WHERE status_konstituensi = 'DIAKTIFKAN' ORDER BY kode_konstituensi ASC");
    if ($result_const) $active_constituencies = $result_const->fetch_all(MYSQLI_ASSOC);

    // Proses form jika di-submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validasi dasar
        if (empty($_POST['no_urut_kandidat']) || empty($_POST['nama_calon_1']) || empty($_POST['jk_calon_1']) || empty($_POST['kk_calon_1'])) {
            throw new Exception("Data Calon 1 (Ketua) wajib diisi.");
        }
        if ($election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN' && (empty($_POST['nama_calon_2']) || empty($_POST['jk_calon_2']) || empty($_POST['kk_calon_2']))) {
            throw new Exception("Mode berpasangan aktif. Data Calon 2 (Wakil) wajib diisi.");
        }
        if (!isset($_FILES['foto_kandidat']) || $_FILES['foto_kandidat']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Foto kandidat wajib diunggah.");
        }

        // Proses unggah foto
        $foto_file = $_FILES['foto_kandidat'];
        $upload_dir = __DIR__ . '/../../assets/imgs/sse-foto-kandidat/';
        $extension = pathinfo($foto_file['name'], PATHINFO_EXTENSION);
        $new_filename = 'kandidat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        if (!move_uploaded_file($foto_file['tmp_name'], $upload_dir . $new_filename)) {
            throw new Exception("Gagal memindahkan file foto yang diunggah.");
        }
        
        // Siapkan data untuk INSERT
        $id_unik = generate_unique_id(12);
        $no_urut = $_POST['no_urut_kandidat'];
        $nama1 = trim($_POST['nama_calon_1']);
        $jk1 = $_POST['jk_calon_1'];
        $kk1 = $_POST['kk_calon_1'];
        $nama2 = $election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN' ? trim($_POST['nama_calon_2']) : null;
        $jk2 = $election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN' ? $_POST['jk_calon_2'] : null;
        $kk2 = $election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN' ? $_POST['kk_calon_2'] : null;
        $visi = str_contains($election_settings['mode_tampilan_sse'], 'BERTEKSVM') ? trim($_POST['visi_kandidat']) : null;
        $misi = str_contains($election_settings['mode_tampilan_sse'], 'BERTEKSVM') ? trim($_POST['misi_kandidat']) : null;

        $stmt = $db->prepare("INSERT INTO data_kandidat (id_unik_kandidat, no_urut_kandidat, nama_calon_1, nama_calon_2, jk_calon_1, jk_calon_2, kk_calon_1, kk_calon_2, visi_kandidat, misi_kandidat, foto_kandidat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssssssss', $id_unik, $no_urut, $nama1, $nama2, $jk1, $jk2, $kk1, $kk2, $visi, $misi, $new_filename);
        $stmt->execute();
        $stmt->close();
        
        $success_message = "Kandidat No. Urut {$no_urut} berhasil ditambahkan.";
    }

} catch (Exception $e) {
    $error_message = "Terjadi kesalahan: " . $e->getMessage();
}
?>

<!-- Tampilkan pesan status -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><h5><i class="icon fas fa-check"></i> Berhasil!</h5><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><h5><i class="icon fas fa-ban"></i> Gagal!</h5><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<?php if (!$election_settings): ?>
<div class="alert alert-danger"><h5><i class="icon fas fa-ban"></i> Kesalahan Konfigurasi</h5>Harap atur "Data Kegiatan Pemilihan" terlebih dahulu sebelum menambah kandidat.</div>
<?php else: ?>
<form action="index.php?page=tambah_kandidat" method="POST" enctype="multipart/form-data">
    <div class="card card-primary card-outline">
        <div class="card-header"><h3 class="card-title">Formulir Tambah Kandidat Baru</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label>Nomor Urut Kandidat</label>
                <input type="number" name="no_urut_kandidat" class="form-control" value="<?= $next_candidate_number ?>" required>
                <small class="form-text text-muted">Nomor urut berikutnya disarankan. Anda dapat mengubahnya jika perlu.</small>
            </div>
            <hr>
            <!-- Data Calon 1 (Ketua) -->
            <h4>Data Calon 1 (Ketua)</h4>
            <div class="row">
                <div class="col-md-6 form-group"><label>Nama Lengkap</label><input type="text" name="nama_calon_1" class="form-control" required></div>
                <div class="col-md-3 form-group"><label>Jenis Kelamin</label><select name="jk_calon_1" class="form-control select2" style="width: 100%;"><option value="PRIA">PRIA</option><option value="WANITA">WANITA</option><option value="TIDAK_DIKETAHUI">TIDAK DIKETAHUI</option></select></div>
                <div class="col-md-3 form-group"><label>Konstituensi Asal</label><select name="kk_calon_1" class="form-control select2" style="width: 100%;" required><option value="" disabled selected>-- Pilih --</option><?php foreach($active_constituencies as $c): ?><option value="<?= $c['kode_konstituensi'] ?>"><?= htmlspecialchars($c['nama_konstituensi']) ?></option><?php endforeach; ?></select></div>
            </div>
            
            <!-- Data Calon 2 (Wakil) - KONDISIONAL -->
            <?php if ($election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN'): ?>
            <hr>
            <h4>Data Calon 2 (Wakil Ketua)</h4>
            <div class="row">
                <div class="col-md-6 form-group"><label>Nama Lengkap</label><input type="text" name="nama_calon_2" class="form-control"></div>
                <div class="col-md-3 form-group"><label>Jenis Kelamin</label><select name="jk_calon_2" class="form-control select2" style="width: 100%;"><option value="PRIA">PRIA</option><option value="WANITA">WANITA</option><option value="TIDAK_DIKETAHUI">TIDAK DIKETAHUI</option></select></div>
                <div class="col-md-3 form-group"><label>Konstituensi Asal</label><select name="kk_calon_2" class="form-control select2" style="width: 100%;"><option value="" disabled selected>-- Pilih --</option><?php foreach($active_constituencies as $c): ?><option value="<?= $c['kode_konstituensi'] ?>"><?= htmlspecialchars($c['nama_konstituensi']) ?></option><?php endforeach; ?></select></div>
            </div>
            <?php endif; ?>

            <!-- Visi & Misi - KONDISIONAL -->
            <?php if (str_contains($election_settings['mode_tampilan_sse'], 'BERTEKSVM')): ?>
            <hr>
            <h4>Visi & Misi</h4>
            <div class="row">
                <div class="col-md-6 form-group"><label>Visi</label><textarea name="visi_kandidat" class="form-control" rows="5"></textarea></div>
                <div class="col-md-6 form-group"><label>Misi</label><textarea name="misi_kandidat" class="form-control" rows="5"></textarea></div>
            </div>
            <?php endif; ?>

            <hr>
            <h4>Foto Kandidat</h4>
            <div class="form-group">
                <label for="fotoKandidatFile">Unggah Foto</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="fotoKandidatFile" name="foto_kandidat" accept="image/jpeg, image/png" required>
                    <label class="custom-file-label" for="fotoKandidatFile">Pilih berkas foto...</label>
                </div>
                <small class="form-text text-muted">Gunakan foto formal atau foto pasangan jika berpasangan. Format: JPG/PNG.</small>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Tambah Kandidat</button>
        </div>
    </div>
</form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.select2').select2({ theme: 'bootstrap4' });
    bsCustomFileInput.init();
});
</script>
