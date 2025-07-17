<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu)
 *
 * Halaman untuk mengelola (Cari, Ubah, Reset, Hapus) data pemilih individual.
 *
 * @version 2.0.0
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// Helper dan koneksi DB sudah dimuat oleh loader.
$db = get_db_connection();

// 1. Inisialisasi Variabel
// -----------------------------------------------------------------------------
$success_message = '';
$error_message = '';
$voter_data = null;
$search_id = $_GET['id_unik_pemilih'] ?? null;

// 2. Logika Pemrosesan Form (Actions)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action_id = $_POST['id_unik_pemilih'] ?? '';
    // Set search_id agar data pemilih tetap ditampilkan setelah aksi
    $search_id = $action_id; 

    try {
        switch ($_POST['action']) {
            // --- AKSI: UBAH DATA POKOK ---
            case 'update_details':
                $nama = trim($_POST['nama_pemilih']);
                $jk = $_POST['jk_pemilih'];
                $kk = $_POST['kk_pemilih'];
                if (empty($nama) || empty($jk) || empty($kk)) throw new Exception("Data Pokok tidak boleh kosong.");
                
                $stmt = $db->prepare("UPDATE data_pemilih SET nama_pemilih = ?, jk_pemilih = ?, kk_pemilih = ? WHERE id_unik_pemilih = ?");
                $stmt->bind_param('ssss', $nama, $jk, $kk, $action_id);
                $stmt->execute();
                $stmt->close();
                $success_message = "Data pokok pemilih berhasil diperbarui.";
                break;

            // --- AKSI: UBAH DATA AKUN ---
            case 'update_account':
                $nama_akun = trim($_POST['nama_akun_pemilih']);
                $password_baru = $_POST['kata_sandi_pemilih'];
                if (empty($nama_akun)) throw new Exception("Nama Akun tidak boleh kosong.");
                
                // Cek duplikasi nama akun
                $stmt_check = $db->prepare("SELECT id_unik_pemilih FROM data_pemilih WHERE nama_akun_pemilih = ? AND id_unik_pemilih != ?");
                $stmt_check->bind_param('ss', $nama_akun, $action_id);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) throw new Exception("Nama Akun '{$nama_akun}' sudah digunakan.");
                $stmt_check->close();

                if (!empty($password_baru)) {
                    $hashed_password = hash_password($password_baru);
                    $stmt = $db->prepare("UPDATE data_pemilih SET nama_akun_pemilih = ?, kata_sandi_pemilih = ? WHERE id_unik_pemilih = ?");
                    $stmt->bind_param('sss', $nama_akun, $hashed_password, $action_id);
                } else {
                    $stmt = $db->prepare("UPDATE data_pemilih SET nama_akun_pemilih = ? WHERE id_unik_pemilih = ?");
                    $stmt->bind_param('ss', $nama_akun, $action_id);
                }
                $stmt->execute();
                $stmt->close();
                $success_message = "Data akun pemilih berhasil diperbarui.";
                break;

            // --- AKSI: RESET AKUN PEMILIH (KRITIKAL) ---
            case 'reset_voter':
                $db->begin_transaction();
                // Ambil data penting dari pemilih yang akan di-reset
                $stmt_get = $db->prepare("SELECT id_unik_pemilih, iv_akun_pemilih FROM data_pemilih WHERE id_unik_pemilih = ?");
                $stmt_get->bind_param('s', $action_id);
                $stmt_get->execute();
                $voter_to_reset = $stmt_get->get_result()->fetch_assoc();
                $stmt_get->close();
                if (!$voter_to_reset) throw new Exception("Pemilih tidak ditemukan untuk di-reset.");

                // Enkripsi ID unik pemilih untuk mencari di tabel suara
                $encrypted_id_b64 = encrypt_voter_id(base64_decode($voter_to_reset['id_unik_pemilih']), base64_decode($voter_to_reset['iv_akun_pemilih']));
                if ($encrypted_id_b64 === false) throw new Exception("Gagal mengenkripsi ID untuk proses reset.");
                
                // Hapus suara yang cocok
                $stmt_del_vote = $db->prepare("DELETE FROM data_suara WHERE id_pemilih_terenkripsi = ?");
                $stmt_del_vote->bind_param('s', $encrypted_id_b64);
                $stmt_del_vote->execute();
                $stmt_del_vote->close();

                // Hapus kehadiran yang cocok
                $stmt_del_att = $db->prepare("DELETE FROM data_kehadiran WHERE id_unik_pemilih = ?");
                $stmt_del_att->bind_param('s', $action_id);
                $stmt_del_att->execute();
                $stmt_del_att->close();
                
                // Update status pemilih
                $stmt_update = $db->prepare("UPDATE data_pemilih SET status_pemilih = 'BELUM_MEMILIH' WHERE id_unik_pemilih = ?");
                $stmt_update->bind_param('s', $action_id);
                $stmt_update->execute();
                $stmt_update->close();
                
                $db->commit();
                $success_message = "Akun pemilih berhasil di-reset. Suara dan kehadiran telah dihapus.";
                break;

            // --- AKSI: HAPUS PEMILIH ---
            case 'delete_voter':
                // Validasi ulang status sebelum menghapus
                $stmt_get = $db->prepare("SELECT status_pemilih FROM data_pemilih WHERE id_unik_pemilih = ?");
                $stmt_get->bind_param('s', $action_id);
                $stmt_get->execute();
                $voter_status = $stmt_get->get_result()->fetch_assoc()['status_pemilih'] ?? 'SUDAH_MEMILIH';
                $stmt_get->close();
                if ($voter_status === 'SUDAH_MEMILIH') throw new Exception("Tidak dapat menghapus pemilih yang sudah memilih. Reset akun terlebih dahulu.");

                $stmt_del = $db->prepare("DELETE FROM data_pemilih WHERE id_unik_pemilih = ?");
                $stmt_del->bind_param('s', $action_id);
                $stmt_del->execute();
                $stmt_del->close();
                
                $success_message = "Data pemilih berhasil dihapus secara permanen.";
                $search_id = null; // Kosongkan search_id agar form tidak tampil lagi
                break;
        }
    } catch (Exception $e) {
        if ($db->in_transaction) $db->rollback();
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// 3. Pengambilan Data untuk Tampilan
// -----------------------------------------------------------------------------
$all_voters = [];
$active_constituencies = [];
try {
    // Ambil semua pemilih untuk dropdown pencarian
    $result_all = $db->query("SELECT id_unik_pemilih, nama_pemilih, nama_akun_pemilih FROM data_pemilih ORDER BY nama_pemilih ASC");
    if ($result_all) $all_voters = $result_all->fetch_all(MYSQLI_ASSOC);

    // Jika ada ID yang dicari, ambil data lengkapnya
    if ($search_id) {
        $stmt_find = $db->prepare("SELECT * FROM data_pemilih WHERE id_unik_pemilih = ?");
        $stmt_find->bind_param('s', $search_id);
        $stmt_find->execute();
        $voter_data = $stmt_find->get_result()->fetch_assoc();
        $stmt_find->close();
        if (!$voter_data) $error_message = "Pemilih dengan ID yang dipilih tidak ditemukan.";
    }

    // Ambil konstituensi aktif untuk form edit
    $result_const = $db->query("SELECT kode_konstituensi, nama_konstituensi FROM data_konstituensi WHERE status_konstituensi = 'DIAKTIFKAN' ORDER BY kode_konstituensi ASC");
    if ($result_const) $active_constituencies = $result_const->fetch_all(MYSQLI_ASSOC);

} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data. Kesalahan: " . $e->getMessage();
}
?>

<!-- Tampilkan pesan status -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><h5><i class="icon fas fa-check"></i> Berhasil!</h5><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><h5><i class="icon fas fa-ban"></i> Gagal!</h5><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<!-- Kartu Pencarian Pemilih -->
<div class="card card-info card-outline">
    <div class="card-header"><h3 class="card-title">1. Cari Pemilih</h3></div>
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="edit_pemilih">
        <div class="card-body">
            <div class="form-group">
                <label>Pilih Pemilih</label>
                <select name="id_unik_pemilih" class="form-control select2" style="width: 100%;" required>
                    <option value="" disabled selected>-- Ketik nama atau nama akun untuk mencari --</option>
                    <?php foreach($all_voters as $v): ?>
                        <option value="<?= htmlspecialchars($v['id_unik_pemilih']) ?>" <?= ($search_id === $v['id_unik_pemilih']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['nama_pemilih']) ?> (<?= htmlspecialchars($v['nama_akun_pemilih']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Cari Data Pemilih</button>
        </div>
    </form>
</div>

<!-- Tampilkan 4 kartu aksi HANYA JIKA pemilih ditemukan -->
<?php if ($voter_data): ?>
<hr>
<h4 class="mb-3 mt-4">2. Kelola Data untuk: <strong><?= htmlspecialchars($voter_data['nama_pemilih']) ?></strong></h4>
<div class="row">
    <!-- Kartu Ubah Data Pokok -->
    <div class="col-lg-6">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Ubah Data Pokok</h3></div>
            <form action="index.php?page=edit_pemilih&id_unik_pemilih=<?= $search_id ?>" method="POST">
                <input type="hidden" name="action" value="update_details">
                <input type="hidden" name="id_unik_pemilih" value="<?= htmlspecialchars($voter_data['id_unik_pemilih']) ?>">
                <div class="card-body">
                    <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_pemilih" class="form-control" value="<?= htmlspecialchars($voter_data['nama_pemilih']) ?>" required></div>
                    <div class="form-group"><label>Jenis Kelamin</label><select name="jk_pemilih" class="form-control"><option value="PRIA" <?= $voter_data['jk_pemilih'] == 'PRIA' ? 'selected' : '' ?>>PRIA</option><option value="WANITA" <?= $voter_data['jk_pemilih'] == 'WANITA' ? 'selected' : '' ?>>WANITA</option><option value="TIDAK_DIKETAHUI" <?= $voter_data['jk_pemilih'] == 'TIDAK_DIKETAHUI' ? 'selected' : '' ?>>TIDAK DIKETAHUI</option></select></div>
                    <div class="form-group"><label>Konstituensi</label><select name="kk_pemilih" class="form-control select2" style="width: 100%;"><?php foreach($active_constituencies as $c): ?><option value="<?= $c['kode_konstituensi'] ?>" <?= $voter_data['kk_pemilih'] == $c['kode_konstituensi'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nama_konstituensi']) ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Simpan Data Pokok</button></div>
            </form>
        </div>
    </div>

    <!-- Kartu Ubah Data Akun -->
    <div class="col-lg-6">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title">Ubah Data Akun</h3></div>
            <form action="index.php?page=edit_pemilih&id_unik_pemilih=<?= $search_id ?>" method="POST">
                <input type="hidden" name="action" value="update_account">
                <input type="hidden" name="id_unik_pemilih" value="<?= htmlspecialchars($voter_data['id_unik_pemilih']) ?>">
                <div class="card-body">
                    <div class="form-group"><label>Nama Akun</label><input type="text" name="nama_akun_pemilih" class="form-control" value="<?= htmlspecialchars($voter_data['nama_akun_pemilih']) ?>" required></div>
                    <div class="form-group"><label>Kata Sandi Baru</label><input type="password" name="kata_sandi_pemilih" class="form-control" placeholder="Kosongkan jika tidak ingin diubah"></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-warning">Simpan Data Akun</button></div>
            </form>
        </div>
    </div>

    <!-- Kartu Reset Akun -->
    <div class="col-lg-6">
        <div class="card card-danger">
            <div class="card-header"><h3 class="card-title">Aksi Berbahaya: Reset Akun</h3></div>
            <div class="card-body">
                <p>Aksi ini akan menghapus suara dan catatan kehadiran pemilih ini, lalu mengubah statusnya menjadi "BELUM MEMILIH".</p>
                <strong>Gunakan hanya jika terjadi kesalahan fatal.</strong>
                <p class="mt-2">Status Saat Ini: <span class="badge <?= $voter_data['status_pemilih'] === 'SUDAH_MEMILIH' ? 'badge-success' : 'badge-warning' ?>"><?= $voter_data['status_pemilih'] === 'SUDAH_MEMILIH' ? 'Sudah Memilih' : 'Belum Memilih' ?></span></p>
            </div>
            <div class="card-footer"><button type="button" class="btn btn-danger" data-toggle="modal" data-target="#resetModal" <?= $voter_data['status_pemilih'] !== 'SUDAH_MEMILIH' ? 'disabled' : '' ?>>Reset Akun Pemilih</button></div>
        </div>
    </div>

    <!-- Kartu Hapus Akun -->
    <div class="col-lg-6">
        <div class="card card-danger">
            <div class="card-header"><h3 class="card-title">Aksi Berbahaya: Hapus Akun</h3></div>
            <div class="card-body"><p>Aksi ini akan menghapus data pemilih secara permanen dari sistem. <strong>Tindakan ini tidak dapat dibatalkan.</strong></p><p>Akun hanya dapat dihapus jika statusnya "BELUM MEMILIH".</p></div>
            <div class="card-footer"><button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#deleteModal" <?= $voter_data['status_pemilih'] === 'SUDAH_MEMILIH' ? 'disabled' : '' ?>>Hapus Akun Pemilih</button></div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Reset -->
<div class="modal fade" id="resetModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form action="index.php?page=edit_pemilih&id_unik_pemilih=<?= $search_id ?>" method="POST">
    <input type="hidden" name="action" value="reset_voter"><input type="hidden" name="id_unik_pemilih" value="<?= htmlspecialchars($voter_data['id_unik_pemilih']) ?>">
    <div class="modal-header bg-danger"><h5 class="modal-title">Konfirmasi Reset Akun</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body">
        <p>Anda akan me-reset akun untuk <strong><?= htmlspecialchars($voter_data['nama_pemilih']) ?></strong>.</p>
        <p>Tindakan ini akan menghapus suara dan kehadiran pemilih ini. Penyalahgunaan fitur ini dapat berakibat pada pembatalan hasil pemilihan.</p>
        <p><strong>Apakah Anda benar-benar yakin?</strong></p>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Saya Yakin. Reset Akun.</button></div>
</form>
</div></div></div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form action="index.php?page=edit_pemilih&id_unik_pemilih=<?= $search_id ?>" method="POST">
    <input type="hidden" name="action" value="delete_voter"><input type="hidden" name="id_unik_pemilih" value="<?= htmlspecialchars($voter_data['id_unik_pemilih']) ?>">
    <div class="modal-header bg-danger"><h5 class="modal-title">Konfirmasi Hapus Akun</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body"><p>Anda akan menghapus akun untuk <strong><?= htmlspecialchars($voter_data['nama_pemilih']) ?></strong> secara permanen. Tindakan ini tidak dapat dibatalkan. <strong>Apakah Anda benar-benar yakin?</strong></p></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Saya Yakin. Hapus Akun.</button></div>
</form>
</div></div></div>

<?php endif; ?>

<!-- Skrip Khusus Halaman Ini -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Select2
    $('.select2').select2({ theme: 'bootstrap4' });
});
</script>
