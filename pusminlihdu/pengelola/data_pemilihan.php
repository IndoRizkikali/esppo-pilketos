<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu)
 *
 * Halaman untuk mengelola Data Kegiatan Pemilihan.
 * Mengingat filosofi one-time-only, halaman ini mengelola satu-satunya
 * record pemilihan yang ada di dalam basis data.
 * Halaman ini mendukung mode INSERT (jika tabel kosong) dan UPDATE (jika data ada).
 *
 * @version 2.0.5
 * @author Tim Pengembang e-SPPO
 * @copyright (c) 2025
 */

// Helper sudah dimuat oleh loader (index.php). $db dan crypto_helper.php sudah tersedia.

// 1. PROSES FORM (INSERT ATAU UPDATE DATA PEMILIHAN)
// -----------------------------------------------------------------------------
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan sanitasi data dari form
    $id_unik_pemilihan = $_POST['id_unik_pemilihan'] ?? '';
    $nama_pemilihan = trim($_POST['nama_pemilihan'] ?? '');
    $tipe_peserta = $_POST['tipe_peserta_pemilihan'] ?? '';
    $masa_bakti = trim($_POST['masa_bakti'] ?? '');
    $status_pemilihan = $_POST['status_pemilihan'] ?? '';
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? ''; // Ambil sebagai string
    $jumlah_tps = filter_var($_POST['jumlah_tps'] ?? 0, FILTER_VALIDATE_INT);
    $jumlah_passe = filter_var($_POST['jumlah_passe'] ?? 0, FILTER_VALIDATE_INT);
    $mode_tampilan_sse = $_POST['mode_tampilan_sse'] ?? '';

    // Validasi dasar
    if (empty($nama_pemilihan) || empty($tipe_peserta) || empty($masa_bakti) || empty($status_pemilihan) || empty($tanggal_mulai) || $jumlah_tps === false || $jumlah_passe === false || empty($mode_tampilan_sse)) {
        $error_message = "Semua field yang ditandai bintang (*) wajib diisi.";
    } else {
        try {
            // PERBAIKAN KRITIS: Konversi string kosong menjadi NULL untuk kolom tanggal
            $tanggal_selesai_db = trim($tanggal_selesai) === '' ? null : $tanggal_selesai;
            
            // Tentukan mode: INSERT jika id_unik_pemilihan kosong, sebaliknya UPDATE
            if (empty($id_unik_pemilihan)) {
                // --- MODE INSERT ---
                $new_id_unik_pemilihan = generate_unique_id(32); // Buat ID baru
                $stmt = $db->prepare(
                    "INSERT INTO data_pemilihan (id_unik_pemilihan, nama_pemilihan, tipe_peserta_pemilihan, masa_bakti, status_pemilihan, tanggal_mulai, tanggal_selesai, jumlah_tps, jumlah_passe, mode_tampilan_sse) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                // PERBAIKAN FINAL: Gunakan 's' (string) untuk semua tipe data.
                // Ini adalah cara paling aman untuk menangani tipe data campuran (terutama NULL dan DECIMAL).
                $stmt->bind_param(
                    'ssssssssss',
                    $new_id_unik_pemilihan,
                    $nama_pemilihan,
                    $tipe_peserta,
                    $masa_bakti,
                    $status_pemilihan,
                    $tanggal_mulai,
                    $tanggal_selesai_db,
                    $jumlah_tps,
                    $jumlah_passe,
                    $mode_tampilan_sse
                );
                $success_message = "Data kegiatan pemilihan berhasil dibuat.";
            } else {
                // --- MODE UPDATE ---
                $stmt = $db->prepare(
                    "UPDATE data_pemilihan SET 
                        nama_pemilihan = ?, tipe_peserta_pemilihan = ?, masa_bakti = ?, 
                        status_pemilihan = ?, tanggal_mulai = ?, tanggal_selesai = ?, 
                        jumlah_tps = ?, jumlah_passe = ?, mode_tampilan_sse = ?
                    WHERE id_unik_pemilihan = ?"
                );
                 // PERBAIKAN FINAL: Gunakan 's' (string) untuk semua tipe data.
                $stmt->bind_param(
                    'ssssssssss',
                    $nama_pemilihan,
                    $tipe_peserta,
                    $masa_bakti,
                    $status_pemilihan,
                    $tanggal_mulai,
                    $tanggal_selesai_db,
                    $jumlah_tps,
                    $jumlah_passe,
                    $mode_tampilan_sse,
                    $id_unik_pemilihan
                );
                $success_message = "Data kegiatan pemilihan berhasil diperbarui.";
            }

            if (!$stmt->execute()) {
                throw new Exception("Eksekusi statement gagal: " . $stmt->error);
            }
            $stmt->close();

        } catch (Exception $e) {
            $error_message = "Gagal memproses data. Kesalahan: " . $e->getMessage();
            error_log("Pusminlihdu data_pemilihan Error: " . $e->getMessage());
        }
    }
}


// 2. PENGAMBILAN DATA AKTUAL DARI DATABASE
// -----------------------------------------------------------------------------
$election_data = null;
try {
    $result = $db->query("SELECT * FROM data_pemilihan LIMIT 1");
    if ($result) {
        $election_data = $result->fetch_assoc();
    }
} catch (mysqli_sql_exception $e) {
    if ($db->errno !== 1146) { // 1146 = Table doesn't exist
       $error_message = "Gagal memuat data pemilihan dari database. Kesalahan: " . $e->getMessage();
    }
}

?>

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


<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Formulir Data Kegiatan Pemilihan</h3>
    </div>
    <!-- Formulir selalu ditampilkan -->
    <form action="index.php?page=data_pemilihan" method="POST">
        <!-- Hidden input untuk ID unik pemilihan. Akan kosong jika INSERT, terisi jika UPDATE -->
        <input type="hidden" name="id_unik_pemilihan" value="<?= htmlspecialchars($election_data['id_unik_pemilihan'] ?? '') ?>">

        <div class="card-body">
            <!-- Pesan kontekstual ditampilkan di dalam form -->
            <?php if ($election_data): ?>
            <div class="alert alert-warning">
                <h5><i class="icon fas fa-exclamation-triangle"></i> Perhatian</h5>
                Basis data ini bersifat "one-time-only" dan hanya untuk mengelola satu kegiatan pemilihan. Semua perubahan yang Anda buat di sini akan mengkonfigurasi kegiatan pemilihan saat ini.
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <h5><i class="icon fas fa-info-circle"></i> Penyiapan Awal</h5>
                Tabel data pemilihan masih kosong. Silakan isi formulir di bawah ini untuk membuat data kegiatan pemilihan yang pertama.
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="nama_pemilihan">Nama Kegiatan Pemilihan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_pemilihan" name="nama_pemilihan" value="<?= htmlspecialchars($election_data['nama_pemilihan'] ?? '') ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tipe_peserta_pemilihan">Tipe Peserta Pemilihan <span class="text-danger">*</span></label>
                        <select class="form-control" id="tipe_peserta_pemilihan" name="tipe_peserta_pemilihan">
                            <option value="TUNGGAL" <?= ($election_data['tipe_peserta_pemilihan'] ?? '') == 'TUNGGAL' ? 'selected' : '' ?>>Tunggal (Contoh: Ketua OSIS saja)</option>
                            <option value="BERPASANGAN" <?= ($election_data['tipe_peserta_pemilihan'] ?? 'BERPASANGAN') == 'BERPASANGAN' ? 'selected' : '' ?>>Berpasangan (Contoh: Ketua & Wakil Ketua)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="masa_bakti">Masa Bakti <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="masa_bakti" name="masa_bakti" placeholder="Contoh: 2025/2026" value="<?= htmlspecialchars($election_data['masa_bakti'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Tanggal Mulai <span class="text-danger">*</span></label>
                        <div class="input-group date" id="datetimepicker_mulai" data-target-input="nearest">
                            <input type="text" name="tanggal_mulai" class="form-control datetimepicker-input" data-target="#datetimepicker_mulai" value="<?= htmlspecialchars($election_data['tanggal_mulai'] ?? date('Y-m-d')) ?>" required/>
                            <div class="input-group-append" data-target="#datetimepicker_mulai" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Tanggal Selesai</label>
                        <div class="input-group date" id="datetimepicker_selesai" data-target-input="nearest">
                            <input type="text" name="tanggal_selesai" class="form-control datetimepicker-input" data-target="#datetimepicker_selesai" value="<?= htmlspecialchars($election_data['tanggal_selesai'] ?? '') ?>"/>
                            <div class="input-group-append" data-target="#datetimepicker_selesai" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>
                        <small class="form-text text-muted">Kosongkan jika pemilihan hanya satu hari.</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="jumlah_tps">Jumlah Tempat Pemungutan Suara (TPS) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_tps" name="jumlah_tps" min="1" value="<?= htmlspecialchars($election_data['jumlah_tps'] ?? '1') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="jumlah_passe">Jumlah Perangkat Akses SSE (PASSE) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_passe" name="jumlah_passe" min="1" value="<?= htmlspecialchars($election_data['jumlah_passe'] ?? '1') ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="mode_tampilan_sse">Mode Tampilan SSE <span class="text-danger">*</span></label>
                        <select class="form-control" id="mode_tampilan_sse" name="mode_tampilan_sse">
                            <option value="BERGAMBAR_BERTEKSVM" <?= ($election_data['mode_tampilan_sse'] ?? '') == 'BERGAMBAR_BERTEKSVM' ? 'selected' : '' ?>>Gambar & Visi Misi</option>
                            <option value="BERGAMBAR_NONTEKSVM" <?= ($election_data['mode_tampilan_sse'] ?? '') == 'BERGAMBAR_NONTEKSVM' ? 'selected' : '' ?>>Gambar Saja</option>
                            <option value="NONGAMBAR_BERTEKSVM" <?= ($election_data['mode_tampilan_sse'] ?? '') == 'NONGAMBAR_BERTEKSVM' ? 'selected' : '' ?>>Teks & Visi Misi</option>
                            <option value="NONGAMBAR_NONTEKSVM" <?= ($election_data['mode_tampilan_sse'] ?? '') == 'NONGAMBAR_NONTEKSVM' ? 'selected' : '' ?>>Teks Saja</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status_pemilihan">Status Pemilihan <span class="text-danger">*</span></label>
                        <select class="form-control" id="status_pemilihan" name="status_pemilihan">
                            <option value="BELUM_DIMULAI" <?= ($election_data['status_pemilihan'] ?? 'BELUM_DIMULAI') == 'BELUM_DIMULAI' ? 'selected' : '' ?>>Belum Dimulai</option>
                            <option value="SEDANG_BERLANSUNG" <?= ($election_data['status_pemilihan'] ?? '') == 'SEDANG_BERLANSUNG' ? 'selected' : '' ?>>Sedang Berlangsung</option>
                            <option value="SELESAI_DILAKSANAKAN" <?= ($election_data['status_pemilihan'] ?? '') == 'SELESAI_DILAKSANAKAN' ? 'selected' : '' ?>>Selesai Dilaksanakan</option>
                        </select>
                         <small class="form-text text-muted">Mengubah ke 'Sedang Berlangsung' akan membuka akses SSE bagi pemilih.</small>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
// Pastikan skrip dijalankan setelah dokumen siap
document.addEventListener("DOMContentLoaded", function() {
    // Inisialisasi Tempus Dominus setelah dokumen dimuat sepenuhnya
    // Ini memastikan jQuery ($) sudah tersedia
    $(function () {
        // Inisialisasi pemilih tanggal mulai
        $('#datetimepicker_mulai').datetimepicker({
            locale: 'id',
            format: 'YYYY-MM-DD',
            useCurrent: false
        });

        // Inisialisasi pemilih tanggal selesai
        $('#datetimepicker_selesai').datetimepicker({
            locale: 'id',
            format: 'YYYY-MM-DD',
            useCurrent: false
        });

        // Hubungkan kedua pemilih tanggal agar tanggal selesai tidak bisa sebelum tanggal mulai
        $("#datetimepicker_mulai").on("change.datetimepicker", function (e) {
            if (e.date) {
                $('#datetimepicker_selesai').datetimepicker('minDate', e.date);
            }
        });
        $("#datetimepicker_selesai").on("change.datetimepicker", function (e) {
            if (e.date) {
                $('#datetimepicker_mulai').datetimepicker('maxDate', e.date);
            }
        });
    });
});
</script>
