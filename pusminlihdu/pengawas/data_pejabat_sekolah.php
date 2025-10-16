<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Data Pejabat Sekolah
 * pusminlihdu/pengelola/data_pejabat_sekolah.php
 *
 * File ini menangani pengelolaan data pejabat sekolah, termasuk penambahan, pengeditan,
 * dan penghapusan pejabat. Data diambil dari basis data MySQL/MariaDB dan ditampilkan
 * dalam tabel yang dapat diurutkan dan dicari. Halaman ini juga menyediakan
 * form untuk menambah dan mengedit pejabat sekolah.
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

// Inisialisasi pesan sukses dan error
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            // --- KASUS: TAMBAH PEJABAT BARU ---
            case 'add_pejabat':
                $nama_pejabat = trim($_POST['nama_pejabat_sekolah']);
                $nip_pejabat = trim($_POST['nip_pejabat_sekolah']);
                $jabatan_pejabat = trim($_POST['jabatan_pejabat_sekolah']);
                $fungsi_pejabat = $_POST['fungsi_pejabat_sekolah'];

                if (empty($nama_pejabat) || empty($jabatan_pejabat) || empty($fungsi_pejabat)) {
                    throw new Exception("Field Nama, Jabatan, dan Fungsi wajib diisi.");
                }
                
                $nip_db = empty($nip_pejabat) ? null : $nip_pejabat;
                $id_unik = generate_unique_id(12);

                $stmt_add = $db->prepare("INSERT INTO data_pejabat_sekolah (id_unik_pejabat_sekolah, nama_pejabat_sekolah, nip_pejabat_sekolah, jabatan_pejabat_sekolah, fungsi_pejabat_sekolah) VALUES (?, ?, ?, ?, ?)");
                $stmt_add->bind_param('sssss', $id_unik, $nama_pejabat, $nip_db, $jabatan_pejabat, $fungsi_pejabat);
                $stmt_add->execute();
                $stmt_add->close();
                
                $success_message = "Data pejabat '{$nama_pejabat}' berhasil ditambahkan.";
                break;

            // --- KASUS: EDIT PEJABAT ---
            case 'edit_pejabat':
                $id_unik = $_POST['edit_id_unik_pejabat_sekolah'];
                $nama_pejabat = trim($_POST['edit_nama_pejabat_sekolah']);
                $nip_pejabat = trim($_POST['edit_nip_pejabat_sekolah']);
                $jabatan_pejabat = trim($_POST['edit_jabatan_pejabat_sekolah']);
                $fungsi_pejabat = $_POST['edit_fungsi_pejabat_sekolah'];

                if (empty($id_unik) || empty($nama_pejabat) || empty($jabatan_pejabat) || empty($fungsi_pejabat)) {
                    throw new Exception("Field Nama, Jabatan, dan Fungsi tidak boleh kosong saat mengedit.");
                }
                
                $nip_db = empty($nip_pejabat) ? null : $nip_pejabat;

                $stmt_edit = $db->prepare("UPDATE data_pejabat_sekolah SET nama_pejabat_sekolah = ?, nip_pejabat_sekolah = ?, jabatan_pejabat_sekolah = ?, fungsi_pejabat_sekolah = ? WHERE id_unik_pejabat_sekolah = ?");
                $stmt_edit->bind_param('sssss', $nama_pejabat, $nip_db, $jabatan_pejabat, $fungsi_pejabat, $id_unik);
                $stmt_edit->execute();
                $stmt_edit->close();

                $success_message = "Data pejabat '{$nama_pejabat}' berhasil diperbarui.";
                break;

            // --- KASUS: HAPUS PEJABAT ---
            case 'delete_pejabat':
                $id_unik = $_POST['delete_id_unik_pejabat_sekolah'];
                if (empty($id_unik)) {
                    throw new Exception("ID Pejabat untuk dihapus tidak valid.");
                }

                $stmt_delete = $db->prepare("DELETE FROM data_pejabat_sekolah WHERE id_unik_pejabat_sekolah = ?");
                $stmt_delete->bind_param('s', $id_unik);
                $stmt_delete->execute();
                $stmt_delete->close();
                
                $success_message = "Data pejabat berhasil dihapus.";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// -----------------------------------------------------------------------------
// 2. PENGAMBILAN DATA UNTUK DITAMPILKAN
// -----------------------------------------------------------------------------
$school_officials = [];
try {
    $result = $db->query("SELECT * FROM data_pejabat_sekolah ORDER BY nama_pejabat_sekolah ASC");
    if ($result) {
        $school_officials = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal memuat daftar pejabat. Kesalahan: " . $e->getMessage();
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
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Daftar Pejabat Sekolah</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPejabatModal">
                        <i class="fas fa-plus"></i> Tambah Pejabat Baru
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table id="pejabatTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nama Pejabat</th>
                            <th>NIP</th>
                            <th>Jabatan</th>
                            <th>Fungsi</th>
                            <th style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($school_officials as $official): ?>
                        <tr>
                            <td><?= htmlspecialchars($official['nama_pejabat_sekolah']) ?></td>
                            <td><?= htmlspecialchars($official['nip_pejabat_sekolah'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($official['jabatan_pejabat_sekolah']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($official['fungsi_pejabat_sekolah']) ?></span></td>
                            <td>
                                <button class="btn btn-xs btn-warning edit-btn" 
                                        data-toggle="modal" 
                                        data-target="#editPejabatModal"
                                        data-id="<?= htmlspecialchars($official['id_unik_pejabat_sekolah']) ?>"
                                        data-nama="<?= htmlspecialchars($official['nama_pejabat_sekolah']) ?>"
                                        data-nip="<?= htmlspecialchars($official['nip_pejabat_sekolah'] ?? '') ?>"
                                        data-jabatan="<?= htmlspecialchars($official['jabatan_pejabat_sekolah']) ?>"
                                        data-fungsi="<?= htmlspecialchars($official['fungsi_pejabat_sekolah']) ?>">
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

<!-- Modal Tambah Pejabat -->
<div class="modal fade" id="addPejabatModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="index.php?page=data_pejabat_sekolah" method="POST">
                <input type="hidden" name="action" value="add_pejabat">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pejabat Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Pejabat</label>
                        <input type="text" name="nama_pejabat_sekolah" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>NIP (Nomor Induk Pegawai)</label>
                        <input type="text" name="nip_pejabat_sekolah" class="form-control" placeholder="Kosongkan jika bukan PNS/ASN">
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan_pejabat_sekolah" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Fungsi Terkait Kesiswaan</label>
                        <select name="fungsi_pejabat_sekolah" class="form-control select2" style="width: 100%;">
                            <option value="WAKASEK">WAKASEK</option>
                            <option value="WAKASEKSIS">WAKASEKSIS</option>
                            <option value="PEMBINA">PEMBINA</option>
                            <option value="PENGAWAS">PENGAWAS</option>
                            <option value="LAINNYA">LAINNYA</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Pejabat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pejabat -->
<div class="modal fade" id="editPejabatModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editPejabatForm" action="index.php?page=data_pejabat_sekolah" method="POST">
                <input type="hidden" name="action" value="edit_pejabat">
                <input type="hidden" id="edit_id_unik_pejabat_sekolah" name="edit_id_unik_pejabat_sekolah">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Pejabat</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Pejabat</label>
                        <input type="text" id="edit_nama_pejabat_sekolah" name="edit_nama_pejabat_sekolah" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>NIP</label>
                        <input type="text" id="edit_nip_pejabat_sekolah" name="edit_nip_pejabat_sekolah" class="form-control" placeholder="Kosongkan jika bukan PNS/ASN">
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" id="edit_jabatan_pejabat_sekolah" name="edit_jabatan_pejabat_sekolah" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Fungsi Terkait Kesiswaan</label>
                        <select id="edit_fungsi_pejabat_sekolah" name="edit_fungsi_pejabat_sekolah" class="form-control select2" style="width: 100%;">
                            <option value="WAKASEK">WAKASEK</option>
                            <option value="WAKASEKSIS">WAKASEKSIS</option>
                            <option value="PEMBINA">PEMBINA</option>
                            <option value="PENGAWAS">PENGAWAS</option>
                            <option value="LAINNYA">LAINNYA</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="deleteBtn">Hapus Pejabat</button>
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
            <form id="deletePejabatForm" action="index.php?page=data_pejabat_sekolah" method="POST">
                <input type="hidden" name="action" value="delete_pejabat">
                <input type="hidden" id="delete_id_unik_pejabat_sekolah" name="delete_id_unik_pejabat_sekolah">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data pejabat <strong id="delete_nama_pejabat"></strong> secara permanen?</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-outline-light">Ya, Hapus Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Skrip Khusus Halaman Ini -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#pejabatTable').DataTable({
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

    $('#addPejabatModal .select2').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#addPejabatModal')
    });

    // Inisialisasi Select2 untuk modal EDIT
    $('#editPejabatModal .select2').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#editPejabatModal')
    });

    // Logika untuk mengisi modal edit saat tombol edit diklik
    $('.edit-btn').on('click', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        const nip = $(this).data('nip');
        const jabatan = $(this).data('jabatan');
        const fungsi = $(this).data('fungsi');
        
        $('#edit_id_unik_pejabat_sekolah').val(id);
        $('#edit_nama_pejabat_sekolah').val(nama);
        $('#edit_nip_pejabat_sekolah').val(nip);
        $('#edit_jabatan_pejabat_sekolah').val(jabatan);
        $('#edit_fungsi_pejabat_sekolah').val(fungsi).trigger('change');
    });

    // Logika untuk memicu modal konfirmasi hapus dari dalam modal edit
    $('#deleteBtn').on('click', function() {
        const id = $('#edit_id_unik_pejabat_sekolah').val();
        const nama = $('#edit_nama_pejabat_sekolah').val();

        $('#delete_id_unik_pejabat_sekolah').val(id);
        $('#delete_nama_pejabat').text(nama);
        
        $('#editPejabatModal').modal('hide');
        $('#confirmDeleteModal').modal('show');
    });
});
</script>
