<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Edit Konstituensi
 * pusminlihdu/pengelola/edit_konstituensi.php
 * 
 * Halaman ini digunakan untuk mengelola data konstituensi pemilih,
 * termasuk menambah, mengubah, dan menghapus konstituensi.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. LOGIKA PEMROSESAN FORM (CRUD ACTIONS)
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

$db = get_db_connection();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            // --- KASUS: TAMBAH KONSTITUENSI ---
            case 'add_konstituensi':
                $kode = trim($_POST['kode_konstituensi']);
                $nama = trim($_POST['nama_konstituensi']);
                $tipe = $_POST['tipe_konstituensi'];
                $status = $_POST['status_konstituensi'];

                if (empty($kode) || empty($nama) || empty($tipe) || empty($status)) {
                    throw new Exception("Semua field untuk menambah data wajib diisi.");
                }

                $stmt_check = $db->prepare("SELECT kode_konstituensi FROM data_konstituensi WHERE kode_konstituensi = ?");
                $stmt_check->bind_param('s', $kode);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    throw new Exception("Kode Konstituensi '{$kode}' sudah ada.");
                }
                $stmt_check->close();

                $stmt_add = $db->prepare("INSERT INTO data_konstituensi (kode_konstituensi, nama_konstituensi, tipe_konstituensi, status_konstituensi) VALUES (?, ?, ?, ?)");
                $stmt_add->bind_param('ssss', $kode, $nama, $tipe, $status);
                $stmt_add->execute();
                $stmt_add->close();
                
                $success_message = "Konstituensi '{$nama}' berhasil ditambahkan.";
                break;

            // --- KASUS: UPDATE KONSTITUENSI ---
            case 'update_konstituensi':
                $kode = $_POST['update_kode_konstituensi'];
                $nama = trim($_POST['update_nama_konstituensi']);
                $tipe = $_POST['update_tipe_konstituensi'];
                $status = $_POST['update_status_konstituensi'];

                if (empty($kode) || empty($nama) || empty($tipe) || empty($status)) {
                    throw new Exception("Semua field untuk mengubah data wajib diisi.");
                }

                $stmt_update = $db->prepare("UPDATE data_konstituensi SET nama_konstituensi = ?, tipe_konstituensi = ?, status_konstituensi = ? WHERE kode_konstituensi = ?");
                $stmt_update->bind_param('ssss', $nama, $tipe, $status, $kode);
                $stmt_update->execute();
                $stmt_update->close();

                $success_message = "Konstituensi '{$nama}' berhasil diperbarui.";
                break;

            // --- KASUS: HAPUS KONSTITUENSI ---
            case 'delete_konstituensi':
                $kode = $_POST['delete_kode_konstituensi'];
                if (empty($kode)) {
                    throw new Exception("Pilih konstituensi yang akan dihapus.");
                }
                
                // Cek ketergantungan di tabel lain sebelum menghapus
                $tables_to_check = ['data_pemilih' => 'kk_pemilih', 'data_kandidat' => 'kk_calon_1', 'data_kandidat' => 'kk_calon_2'];
                foreach($tables_to_check as $table => $column) {
                    $stmt_check = $db->prepare("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?");
                    $stmt_check->bind_param('s', $kode);
                    $stmt_check->execute();
                    $count = $stmt_check->get_result()->fetch_assoc()['count'];
                    $stmt_check->close();
                    if ($count > 0) {
                        throw new Exception("Gagal menghapus. Kode Konstituensi '{$kode}' sedang digunakan di tabel '{$table}'.");
                    }
                }

                $stmt_delete = $db->prepare("DELETE FROM data_konstituensi WHERE kode_konstituensi = ?");
                $stmt_delete->bind_param('s', $kode);
                $stmt_delete->execute();
                
                $success_message = "Konstituensi dengan kode '{$kode}' berhasil dihapus.";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// -----------------------------------------------------------------------------
// 2. PENGAMBILAN DATA UNTUK FORM DROPDOWN
// -----------------------------------------------------------------------------

$constituencies_for_form = [];
try {
    $result = $db->query("SELECT kode_konstituensi, nama_konstituensi, tipe_konstituensi, status_konstituensi FROM data_konstituensi ORDER BY kode_konstituensi ASC");
    if ($result) {
        $constituencies_for_form = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat data untuk form. Kesalahan: " . $e->getMessage();
}
?>

<!-- Tampilkan pesan sukses atau error -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5><i class="icon fas fa-check"></i> Berhasil!</h5>
    <?= htmlspecialchars($success_message) ?>
</div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5><i class="icon fas fa-ban"></i> Gagal!</h5>
    <?= htmlspecialchars($error_message) ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- Kolom Tambah -->
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Tambah Konstituensi Baru</h3></div>
            <form action="index.php?page=edit_konstituensi" method="POST">
                <input type="hidden" name="action" value="add_konstituensi">
                <div class="card-body">
                    <div class="form-group">
                        <label>Kode Konstituensi</label>
                        <input type="number" name="kode_konstituensi" class="form-control" placeholder="Contoh: 10 atau 11" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Konstituensi</label>
                        <input type="text" name="nama_konstituensi" class="form-control" placeholder="Contoh: X-A atau Guru" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe</label>
                        <select name="tipe_konstituensi" class="form-control select2" style="width: 100%;">
                            <option value="KELAS">KELAS</option>
                            <option value="KEPEGAWAIAN">KEPEGAWAIAN</option>
                            <option value="LAINNYA">LAINNYA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status_konstituensi" class="form-control select2" style="width: 100%;">
                            <option value="DIAKTIFKAN">DIAKTIFKAN</option>
                            <option value="DINONAKTIFKAN">DINONAKTIFKAN</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Edit & Hapus -->
    <div class="col-md-6">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title">Ubah atau Hapus Konstituensi</h3></div>
            <form id="editForm" action="index.php?page=edit_konstituensi" method="POST">
                <div class="card-body">
                    <div class="form-group">
                        <label>Pilih Konstituensi</label>
                        <select id="select_konstituensi" name="update_kode_konstituensi" class="form-control select2" style="width: 100%;" required>
                            <option value="" disabled selected>-- Pilih untuk diubah/dihapus --</option>
                            <?php foreach($constituencies_for_form as $item): ?>
                                <option value="<?= htmlspecialchars($item['kode_konstituensi']) ?>" 
                                        data-nama="<?= htmlspecialchars($item['nama_konstituensi']) ?>" 
                                        data-tipe="<?= htmlspecialchars($item['tipe_konstituensi']) ?>"
                                        data-status="<?= htmlspecialchars($item['status_konstituensi']) ?>">
                                    <?= htmlspecialchars($item['kode_konstituensi']) ?> - <?= htmlspecialchars($item['nama_konstituensi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Konstituensi</label>
                        <input type="text" id="update_nama_konstituensi" name="update_nama_konstituensi" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe</label>
                        <select id="update_tipe_konstituensi" name="update_tipe_konstituensi" class="form-control select2" style="width: 100%;">
                            <option value="KELAS">KELAS</option>
                            <option value="KEPEGAWAIAN">KEPEGAWAIAN</option>
                            <option value="LAINNYA">LAINNYA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="update_status_konstituensi" name="update_status_konstituensi" class="form-control select2" style="width: 100%;">
                            <option value="DIAKTIFKAN">DIAKTIFKAN</option>
                            <option value="DINONAKTIFKAN">DINONAKTIFKAN</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button type="submit" name="action" value="update_konstituensi" class="btn btn-warning">Simpan Perubahan</button>
                    <button type="button" id="deleteBtn" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-danger">
            <form action="index.php?page=edit_konstituensi" method="POST">
                <input type="hidden" name="action" value="delete_konstituensi">
                <input type="hidden" id="delete_kode_konstituensi" name="delete_kode_konstituensi">
                <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body"><p>Apakah Anda yakin ingin menghapus konstituensi <strong id="delete_nama_konstituensi"></strong>?</p></div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-outline-light">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Skrip Khusus Halaman Ini -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Select2
    $('.select2').select2({ theme: 'bootstrap4' });

    // Logika untuk mengisi form edit saat dropdown berubah
    $('#select_konstituensi').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        $('#update_nama_konstituensi').val(selectedOption.data('nama'));
        $('#update_tipe_konstituensi').val(selectedOption.data('tipe')).trigger('change');
        $('#update_status_konstituensi').val(selectedOption.data('status')).trigger('change');
    });

    // Logika untuk memicu modal konfirmasi hapus
    $('#deleteBtn').on('click', function() {
        const selectedOption = $('#select_konstituensi').find('option:selected');
        const kode = selectedOption.val();
        const nama = selectedOption.data('nama');

        if (!kode) {
            alert('Silakan pilih konstituensi yang akan dihapus terlebih dahulu.');
            return;
        }

        $('#delete_kode_konstituensi').val(kode);
        $('#delete_nama_konstituensi').text(nama + ' (Kode: ' + kode + ')');
        $('#confirmDeleteModal').modal('show');
    });
});
</script>
