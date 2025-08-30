<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Data Akun Admin
 * pusminlihdu/pengelola/data_akun_admin.php
 * 
 * Halaman ini mengelola akun administrasi untuk pengelola sistem.
 * Pengelola dapat menambah, mengedit, dan menghapus akun admin dan akun pengawas.
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
    
    // Gunakan try-catch untuk menangani semua kemungkinan error
    try {
        switch ($_POST['action']) {
            // --- KASUS: TAMBAH AKUN BARU ---
            case 'add_admin':
                $nama_akun = trim($_POST['nama_akun_admin']);
                $password = $_POST['kata_sandi_akun_admin'];
                $tingkat_akses = $_POST['tingkat_akses_akun'];
                $status_akun = $_POST['status_akun_admin'];

                if (empty($nama_akun) || empty($password) || empty($tingkat_akses) || empty($status_akun)) {
                    throw new Exception("Semua field untuk menambah akun wajib diisi.");
                }

                // Cek apakah nama akun sudah ada
                $stmt_check = $db->prepare("SELECT id_unik_akun_admin FROM akun_administrasi WHERE nama_akun_admin = ?");
                $stmt_check->bind_param('s', $nama_akun);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    throw new Exception("Nama Akun '{$nama_akun}' sudah digunakan. Harap gunakan nama lain.");
                }
                $stmt_check->close();

                // Buat akun baru
                $id_unik = generate_unique_id(8);
                $hashed_password = hash_password($password);
                
                $stmt_add = $db->prepare("INSERT INTO akun_administrasi (id_unik_akun_admin, nama_akun_admin, status_akun_admin, tingkat_akses_akun, kata_sandi_akun_admin) VALUES (?, ?, ?, ?, ?)");
                $stmt_add->bind_param('sssss', $id_unik, $nama_akun, $status_akun, $tingkat_akses, $hashed_password);
                $stmt_add->execute();
                $stmt_add->close();
                
                $success_message = "Akun '{$nama_akun}' berhasil ditambahkan.";
                break;

            // --- KASUS: EDIT AKUN ---
            case 'edit_admin':
                $id_unik = $_POST['edit_id_unik_akun_admin'];
                $nama_akun = trim($_POST['edit_nama_akun_admin']);
                $tingkat_akses = $_POST['edit_tingkat_akses_akun'];
                $status_akun = $_POST['edit_status_akun_admin'];
                $password_baru = $_POST['edit_kata_sandi_akun_admin'];

                if (empty($id_unik) || empty($nama_akun) || empty($tingkat_akses) || empty($status_akun)) {
                    throw new Exception("Field Nama, Tingkat Akses, dan Status tidak boleh kosong saat mengedit.");
                }
                
                // Cek duplikasi nama akun (kecuali untuk akun itu sendiri)
                $stmt_check = $db->prepare("SELECT id_unik_akun_admin FROM akun_administrasi WHERE nama_akun_admin = ? AND id_unik_akun_admin != ?");
                $stmt_check->bind_param('ss', $nama_akun, $id_unik);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    throw new Exception("Nama Akun '{$nama_akun}' sudah digunakan oleh akun lain.");
                }
                $stmt_check->close();

                if (!empty($password_baru)) {
                    // Jika ada password baru, update password
                    $hashed_password = hash_password($password_baru);
                    $stmt_edit = $db->prepare("UPDATE akun_administrasi SET nama_akun_admin = ?, tingkat_akses_akun = ?, status_akun_admin = ?, kata_sandi_akun_admin = ? WHERE id_unik_akun_admin = ?");
                    $stmt_edit->bind_param('sssss', $nama_akun, $tingkat_akses, $status_akun, $hashed_password, $id_unik);
                } else {
                    // Jika tidak ada password baru, jangan update password
                    $stmt_edit = $db->prepare("UPDATE akun_administrasi SET nama_akun_admin = ?, tingkat_akses_akun = ?, status_akun_admin = ? WHERE id_unik_akun_admin = ?");
                    $stmt_edit->bind_param('ssss', $nama_akun, $tingkat_akses, $status_akun, $id_unik);
                }
                $stmt_edit->execute();
                $stmt_edit->close();

                $success_message = "Akun '{$nama_akun}' berhasil diperbarui.";
                break;

            // --- KASUS: HAPUS AKUN ---
            case 'delete_admin':
                $id_unik = $_POST['delete_id_unik_akun_admin'];
                if (empty($id_unik)) {
                    throw new Exception("ID Akun untuk dihapus tidak valid.");
                }
                
                // Jangan biarkan pengguna menghapus akunnya sendiri
                if ($id_unik === $_SESSION['admin_id_unik']) {
                    throw new Exception("Anda tidak dapat menghapus akun yang sedang Anda gunakan.");
                }

                $stmt_delete = $db->prepare("DELETE FROM akun_administrasi WHERE id_unik_akun_admin = ?");
                $stmt_delete->bind_param('s', $id_unik);
                $stmt_delete->execute();
                $stmt_delete->close();
                
                $success_message = "Akun berhasil dihapus.";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// -----------------------------------------------------------------------------
// 2. PENGAMBILAN DATA UNTUK DITAMPILKAN
// -----------------------------------------------------------------------------
$admin_accounts = [];
try {
    $result = $db->query("SELECT id_unik_akun_admin, nama_akun_admin, status_akun_admin, tingkat_akses_akun, waktu_masuk_terakhir FROM akun_administrasi ORDER BY nama_akun_admin ASC");
    if ($result) {
        $admin_accounts = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat daftar akun. Kesalahan: " . $e->getMessage();
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
    <!-- Kolom Daftar Akun -->
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Daftar Akun Administrasi</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addAdminModal">
                        <i class="fas fa-plus"></i> Tambah Akun Baru
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table id="adminTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nama Akun</th>
                            <th>Tingkat Akses</th>
                            <th>Status</th>
                            <th>Login Terakhir</th>
                            <th style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admin_accounts as $account): ?>
                        <tr>
                            <td><?= htmlspecialchars($account['nama_akun_admin']) ?></td>
                            <td>
                                <?php 
                                    $role_badge = $account['tingkat_akses_akun'] === 'PENGELOLA' ? 'badge-info' : 'badge-secondary';
                                    echo "<span class='badge {$role_badge}'>" . htmlspecialchars($account['tingkat_akses_akun']) . "</span>";
                                ?>
                            </td>
                            <td>
                                <?php 
                                    $status_badge = $account['status_akun_admin'] === 'DIAKTIFKAN' ? 'badge-success' : 'badge-danger';
                                    echo "<span class='badge {$status_badge}'>" . htmlspecialchars($account['status_akun_admin']) . "</span>";
                                ?>
                            </td>
                            <td><?= $account['waktu_masuk_terakhir'] ? date('d M Y, H:i', strtotime($account['waktu_masuk_terakhir'])) : 'Belum pernah' ?></td>
                            <td>
                                <button class="btn btn-xs btn-warning edit-btn" 
                                        data-toggle="modal" 
                                        data-target="#editAdminModal"
                                        data-id="<?= htmlspecialchars($account['id_unik_akun_admin']) ?>"
                                        data-nama="<?= htmlspecialchars($account['nama_akun_admin']) ?>"
                                        data-akses="<?= htmlspecialchars($account['tingkat_akses_akun']) ?>"
                                        data-status="<?= htmlspecialchars($account['status_akun_admin']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun -->
<div class="modal fade" id="addAdminModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="index.php?page=data_akun_admin" method="POST">
                <input type="hidden" name="action" value="add_admin">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Akun Admin Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Akun</label>
                        <input type="text" name="nama_akun_admin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kata Sandi</label>
                        <input type="password" name="kata_sandi_akun_admin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tingkat Akses</label>
                        <select name="tingkat_akses_akun" class="form-control select2" style="width: 100%;">
                            <option value="PENGELOLA">PENGELOLA</option>
                            <option value="PENGAWAS">PENGAWAS</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status Akun</label>
                        <select name="status_akun_admin" class="form-control select2" style="width: 100%;">
                            <option value="DIAKTIFKAN">DIAKTIFKAN</option>
                            <option value="DINONAKTIFKAN">DINONAKTIFKAN</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Akun -->
<div class="modal fade" id="editAdminModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editAdminForm" action="index.php?page=data_akun_admin" method="POST">
                <input type="hidden" name="action" value="edit_admin">
                <input type="hidden" id="edit_id_unik_akun_admin" name="edit_id_unik_akun_admin">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Akun Admin</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Akun</label>
                        <input type="text" id="edit_nama_akun_admin" name="edit_nama_akun_admin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kata Sandi Baru</label>
                        <input type="password" name="edit_kata_sandi_akun_admin" class="form-control" placeholder="Kosongkan jika tidak diubah">
                    </div>
                    <div class="form-group">
                        <label>Tingkat Akses</label>
                        <select id="edit_tingkat_akses_akun" name="edit_tingkat_akses_akun" class="form-control select2" style="width: 100%;">
                            <option value="PENGELOLA">PENGELOLA</option>
                            <option value="PENGAWAS">PENGAWAS</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status Akun</label>
                        <select id="edit_status_akun_admin" name="edit_status_akun_admin" class="form-control select2" style="width: 100%;">
                            <option value="DIAKTIFKAN">DIAKTIFKAN</option>
                            <option value="DINONAKTIFKAN">DINONAKTIFKAN</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="deleteBtn">Hapus Akun</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-danger">
            <form id="deleteAdminForm" action="index.php?page=data_akun_admin" method="POST">
                <input type="hidden" name="action" value="delete_admin">
                <input type="hidden" id="delete_id_unik_akun_admin" name="delete_id_unik_akun_admin">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus akun <strong id="delete_nama_akun"></strong> secara permanen? Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-outline-light">Ya, Hapus Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Skrip Khusus Halaman Ini -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#adminTable').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "../../uis/adminlte-3.2.0/plugins/datatables/id.json"
        }
    });

    $('#addAdminModal .select2').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#addAdminModal')
    });

    // Inisialisasi Select2 untuk modal EDIT
    $('#editAdminModal .select2').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#editAdminModal')
    });

    // Logika untuk mengisi modal edit saat tombol edit diklik
    $('.edit-btn').on('click', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        const akses = $(this).data('akses');
        const status = $(this).data('status');
        
        $('#edit_id_unik_akun_admin').val(id);
        $('#edit_nama_akun_admin').val(nama);
        $('#edit_tingkat_akses_akun').val(akses).trigger('change');
        $('#edit_status_akun_admin').val(status).trigger('change');
    });

    // Logika untuk memicu modal konfirmasi hapus
    $('#deleteBtn').on('click', function() {
        const id = $('#edit_id_unik_akun_admin').val();
        const nama = $('#edit_nama_akun_admin').val();

        $('#delete_id_unik_akun_admin').val(id);
        $('#delete_nama_akun').text(nama);
        
        $('#editAdminModal').modal('hide');
        $('#confirmDeleteModal').modal('show');
    });
});
</script>
