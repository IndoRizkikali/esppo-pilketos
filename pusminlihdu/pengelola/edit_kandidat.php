<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu)
 *
 * Halaman untuk mengedit atau menghapus data kandidat.
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// 1. Inisialisasi & Pengambilan Data Awal
// -----------------------------------------------------------------------------
$success_message = '';
$error_message = '';
$candidate_data = null;
$all_candidates = [];
$active_constituencies = [];
$election_settings = null;
$search_id = $_GET['id'] ?? null;

try {
    // Ambil semua kandidat untuk dropdown pencarian
    $result_all = $db->query("SELECT id_unik_kandidat, no_urut_kandidat, nama_calon_1 FROM data_kandidat ORDER BY no_urut_kandidat ASC");
    if ($result_all) $all_candidates = $result_all->fetch_all(MYSQLI_ASSOC);

    // Ambil pengaturan pemilihan
    $result_settings = $db->query("SELECT tipe_peserta_pemilihan, mode_tampilan_sse FROM data_pemilihan LIMIT 1");
    if ($result_settings) $election_settings = $result_settings->fetch_assoc();

    // Ambil konstituensi aktif
    $result_const = $db->query("SELECT kode_konstituensi, nama_konstituensi FROM data_konstituensi WHERE status_konstituensi = 'DIAKTIFKAN' ORDER BY kode_konstituensi ASC");
    if ($result_const) $active_constituencies = $result_const->fetch_all(MYSQLI_ASSOC);

    // Proses Form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action_id = $_POST['id_unik_kandidat'] ?? '';
        $search_id = $action_id; // Tetap tampilkan form setelah aksi

        switch ($_POST['action']) {
            case 'update_candidate':
                // Logika Update
                $no_urut = $_POST['no_urut_kandidat'];
                $nama1 = trim($_POST['nama_calon_1']);
                $jk1 = $_POST['jk_calon_1'];
                $kk1 = $_POST['kk_calon_1'];
                $nama2 = $election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN' ? trim($_POST['nama_calon_2']) : null;
                $jk2 = $election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN' ? $_POST['jk_calon_2'] : null;
                $kk2 = $election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN' ? $_POST['kk_calon_2'] : null;
                $visi = str_contains($election_settings['mode_tampilan_sse'], 'BERTEKSVM') ? trim($_POST['visi_kandidat']) : null;
                $misi = str_contains($election_settings['mode_tampilan_sse'], 'BERTEKSVM') ? trim($_POST['misi_kandidat']) : null;
                $current_photo = $_POST['current_photo'];
                $new_photo_filename = $current_photo;

                // Proses foto baru jika diunggah
                if (isset($_FILES['foto_kandidat']) && $_FILES['foto_kandidat']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../../assets/imgs/sse-foto-kandidat/';
                    // Hapus foto lama jika ada
                    if (!empty($current_photo) && file_exists($upload_dir . $current_photo)) {
                        unlink($upload_dir . $current_photo);
                    }
                    // Simpan foto baru
                    $extension = pathinfo($_FILES['foto_kandidat']['name'], PATHINFO_EXTENSION);
                    $new_photo_filename = 'kandidat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    if (!move_uploaded_file($_FILES['foto_kandidat']['tmp_name'], $upload_dir . $new_photo_filename)) {
                        throw new Exception("Gagal mengunggah foto baru.");
                    }
                }

                $stmt = $db->prepare("UPDATE data_kandidat SET no_urut_kandidat=?, nama_calon_1=?, nama_calon_2=?, jk_calon_1=?, jk_calon_2=?, kk_calon_1=?, kk_calon_2=?, visi_kandidat=?, misi_kandidat=?, foto_kandidat=? WHERE id_unik_kandidat=?");
                $stmt->bind_param('sssssssssss', $no_urut, $nama1, $nama2, $jk1, $jk2, $kk1, $kk2, $visi, $misi, $new_photo_filename, $action_id);
                $stmt->execute();
                $stmt->close();
                $success_message = "Data kandidat berhasil diperbarui.";
                break;
            
            case 'delete_candidate':
                 // Logika Hapus
                 $current_photo_to_delete = $_POST['current_photo'];
                 $stmt = $db->prepare("DELETE FROM data_kandidat WHERE id_unik_kandidat = ?");
                 $stmt->bind_param('s', $action_id);
                 $stmt->execute();
                 if ($stmt->affected_rows > 0) {
                    // Hapus file foto terkait
                    $photo_path = __DIR__ . '/../../assets/imgs/sse-foto-kandidat/' . $current_photo_to_delete;
                    if (!empty($current_photo_to_delete) && file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                    $success_message = "Kandidat berhasil dihapus.";
                    $search_id = null; // Sembunyikan form edit setelah hapus
                 } else {
                    throw new Exception("Gagal menghapus kandidat dari database.");
                 }
                 $stmt->close();
                 break;
        }
    }

    // Ambil data kandidat yang dicari untuk ditampilkan di form
    if ($search_id) {
        $stmt_find = $db->prepare("SELECT * FROM data_kandidat WHERE id_unik_kandidat = ?");
        $stmt_find->bind_param('s', $search_id);
        $stmt_find->execute();
        $candidate_data = $stmt_find->get_result()->fetch_assoc();
        $stmt_find->close();
        if (!$candidate_data) $error_message = "Kandidat dengan ID yang dipilih tidak ditemukan.";
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

<!-- Kartu Pencarian -->
<div class="card card-info card-outline">
    <div class="card-header"><h3 class="card-title">1. Cari Kandidat</h3></div>
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="edit_kandidat">
        <div class="card-body"><div class="form-group"><label>Pilih Kandidat untuk Dikelola</label><select name="id" class="form-control select2" style="width: 100%;" required><option value="" disabled selected>-- Ketik nomor urut atau nama --</option><?php foreach($all_candidates as $c): ?><option value="<?= htmlspecialchars($c['id_unik_kandidat']) ?>" <?= ($search_id === $c['id_unik_kandidat']) ? 'selected' : '' ?>><?= htmlspecialchars($c['no_urut_kandidat']) ?> - <?= htmlspecialchars($c['nama_calon_1']) ?></option><?php endforeach; ?></select></div></div>
        <div class="card-footer"><button type="submit" class="btn btn-info">Cari Data Kandidat</button></div>
    </form>
</div>

<!-- Form Edit (hanya tampil jika kandidat ditemukan) -->
<?php if ($candidate_data && $election_settings): ?>
<hr><h4 class="mb-3 mt-4">2. Kelola Data untuk Kandidat No. Urut <strong><?= htmlspecialchars($candidate_data['no_urut_kandidat']) ?></strong></h4>
<form action="index.php?page=edit_kandidat&id=<?= $search_id ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id_unik_kandidat" value="<?= htmlspecialchars($candidate_data['id_unik_kandidat']) ?>">
    <input type="hidden" name="current_photo" value="<?= htmlspecialchars($candidate_data['foto_kandidat']) ?>">
    <div class="card card-warning card-outline">
        <div class="card-header"><h3 class="card-title">Formulir Edit Kandidat</h3></div>
        <div class="card-body">
            <!-- Konten form sama seperti tambah_kandidat.php tapi dengan value -->
            <div class="form-group"><label>Nomor Urut Kandidat</label><input type="number" name="no_urut_kandidat" class="form-control" value="<?= htmlspecialchars($candidate_data['no_urut_kandidat']) ?>" required></div><hr>
            <h4>Data Calon 1 (Ketua)</h4>
            <div class="row">
                <div class="col-md-6 form-group"><label>Nama Lengkap</label><input type="text" name="nama_calon_1" class="form-control" value="<?= htmlspecialchars($candidate_data['nama_calon_1']) ?>" required></div>
                <div class="col-md-3 form-group"><label>Jenis Kelamin</label><select name="jk_calon_1" class="form-control select2" style="width: 100%;"><?php foreach(['PRIA', 'WANITA', 'TIDAK_DIKETAHUI'] as $jk): ?><option value="<?= $jk ?>" <?= $candidate_data['jk_calon_1'] == $jk ? 'selected' : '' ?>><?= $jk ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 form-group"><label>Konstituensi Asal</label><select name="kk_calon_1" class="form-control select2" style="width: 100%;" required><?php foreach($active_constituencies as $c): ?><option value="<?= $c['kode_konstituensi'] ?>" <?= $candidate_data['kk_calon_1'] == $c['kode_konstituensi'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nama_konstituensi']) ?></option><?php endforeach; ?></select></div>
            </div>
            <?php if ($election_settings['tipe_peserta_pemilihan'] === 'BERPASANGAN'): ?>
            <hr><h4>Data Calon 2 (Wakil Ketua)</h4>
            <div class="row">
                <div class="col-md-6 form-group"><label>Nama Lengkap</label><input type="text" name="nama_calon_2" class="form-control" value="<?= htmlspecialchars($candidate_data['nama_calon_2'] ?? '') ?>"></div>
                <div class="col-md-3 form-group"><label>Jenis Kelamin</label><select name="jk_calon_2" class="form-control select2" style="width: 100%;"><?php foreach(['PRIA', 'WANITA', 'TIDAK_DIKETAHUI'] as $jk): ?><option value="<?= $jk ?>" <?= ($candidate_data['jk_calon_2'] ?? '') == $jk ? 'selected' : '' ?>><?= $jk ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 form-group"><label>Konstituensi Asal</label><select name="kk_calon_2" class="form-control select2" style="width: 100%;"><option value="">-- Tidak Ada --</option><?php foreach($active_constituencies as $c): ?><option value="<?= $c['kode_konstituensi'] ?>" <?= ($candidate_data['kk_calon_2'] ?? '') == $c['kode_konstituensi'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nama_konstituensi']) ?></option><?php endforeach; ?></select></div>
            </div>
            <?php endif; ?>
            <?php if (str_contains($election_settings['mode_tampilan_sse'], 'BERTEKSVM')): ?>
            <hr><h4>Visi & Misi</h4>
            <div class="row">
                <div class="col-md-6 form-group"><label>Visi</label><textarea name="visi_kandidat" class="form-control" rows="5"><?= htmlspecialchars($candidate_data['visi_kandidat'] ?? '') ?></textarea></div>
                <div class="col-md-6 form-group"><label>Misi</label><textarea name="misi_kandidat" class="form-control" rows="5"><?= htmlspecialchars($candidate_data['misi_kandidat'] ?? '') ?></textarea></div>
            </div>
            <?php endif; ?>
            <hr><h4>Foto Kandidat</h4>
            <div class="row align-items-center">
                <div class="col-md-3 text-center"><p><strong>Foto Saat Ini:</strong></p><img src="../../assets/imgs/sse-foto-kandidat/<?= htmlspecialchars($candidate_data['foto_kandidat']) ?>" class="img-thumbnail" style="max-height: 150px;"></div>
                <div class="col-md-9 form-group"><label for="fotoKandidatFile">Unggah Foto Baru (Opsional)</label><div class="custom-file"><input type="file" class="custom-file-input" id="fotoKandidatFile" name="foto_kandidat" accept="image/jpeg, image/png"><label class="custom-file-label" for="fotoKandidatFile">Pilih berkas jika ingin mengganti...</label></div></div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button type="submit" name="action" value="update_candidate" class="btn btn-warning">Simpan Perubahan</button>
            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteCandidateModal">Hapus Kandidat Ini</button>
        </div>
    </div>
</form>

<!-- Modal Konfirmasi Hapus Kandidat -->
<div class="modal fade" id="deleteCandidateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content bg-danger">
<form action="index.php?page=edit_kandidat&id=<?= $search_id ?>" method="POST">
    <input type="hidden" name="action" value="delete_candidate"><input type="hidden" name="id_unik_kandidat" value="<?= htmlspecialchars($candidate_data['id_unik_kandidat']) ?>"><input type="hidden" name="current_photo" value="<?= htmlspecialchars($candidate_data['foto_kandidat']) ?>">
    <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus Kandidat</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body"><p>Anda akan menghapus data untuk kandidat <strong><?= htmlspecialchars($candidate_data['nama_calon_1']) ?></strong> secara permanen. Tindakan ini tidak dapat dibatalkan.<strong>Apakah Anda benar-benar yakin?</strong></p></div>
    <div class="modal-footer justify-content-between"><button type="button" class="btn btn-outline-light" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-outline-light">Ya, Hapus Kandidat</button></div>
</form>
</div></div></div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.select2').select2({ theme: 'bootstrap4' });
    bsCustomFileInput.init();
});
</script>
