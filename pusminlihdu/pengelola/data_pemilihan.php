<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Data Kegiatan Pemilihan
 * pusminlihdu/pengelola/data_pemilihan.php
 *
 * Halaman ini menangani konfigurasi kegiatan pemilihan, termasuk nama pemilihan,
 * tipe peserta, masa bakti, tanggal mulai dan selesai, jumlah TPS,
 * jumlah perangkat akses SSE (PASSE), dan mode tampilan SSE.
 * Data disimpan dalam tabel `data_pemilihan` di basis data.
 * Database bersifat "one-time-only" (satu kali pakai) untuk mengelola satu kegiatan pemilihan.
 * Setelah pemilihan selesai, basis data ini tidak akan digunakan dan diarsipkan.
 * Pengguna harus mengganti basis data ini jika ingin mengelola pemilihan baru.
 * Mengganti basis data dapat dilakukan dengan membuat basis data baru dan mengganti
 * konfigurasi di file `confs/db_config.yml`.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. PROSES FORM (INSERT ATAU UPDATE DATA PEMILIHAN)
// -----------------------------------------------------------------------------

// Diasumsikan bahwa seluruh helper dan koneksi basis data sudah dimuat sebelumnya
// oleh index.php.

$db = get_db_connection();

// Inisialisasi pesan sukses dan error
$success_message = '';
$error_message = '';

// Cek apakah form telah disubmit
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
            // Untuk tanggal selesai, jika kosong, kita akan mengkonversinya menjadi NULL
            // Ini akan menghindari masalah dengan tipe data DATE di MySQL.
            $tanggal_selesai_db = trim($tanggal_selesai) === '' ? null : $tanggal_selesai;
            
            // Tentukan mode: INSERT jika id_unik_pemilihan kosong, sebaliknya UPDATE
            if (empty($id_unik_pemilihan)) {
                // --- MODE INSERT ---
                $new_id_unik_pemilihan = generate_unique_id(32); // Buat ID baru
                $stmt = $db->prepare(
                    "INSERT INTO data_pemilihan (id_unik_pemilihan, nama_pemilihan, tipe_peserta_pemilihan, masa_bakti, status_pemilihan, tanggal_mulai, tanggal_selesai, jumlah_tps, jumlah_passe, mode_tampilan_sse) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                // Setel parameter dengan tipe data yang benar
                // Menggunakan 's' (string) untuk semua tipe data
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

                // Setel parameter dengan tipe data yang benar
                // Menggunakan 's' (string) untuk semua tipe data
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

// -----------------------------------------------------------------------------
// 2. PENGAMBILAN DATA AKTUAL DARI DATABASE
// -----------------------------------------------------------------------------

// Ambil data pemilihan yang ada di basis data
// Jika tabel tidak ada, akan ditangani oleh pengecualian di bawah ini.
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

<!-- Tampilkan pesan sukses atau error jika ada -->
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
                Basis data ini bersifat <em>"one-time-only"</em> <b>(satu kali saja)</b> dan hanya untuk mengelola satu kegiatan pemilihan.
                Apabila Anda ingin mengelola pemilihan baru, silakan buat basis data baru dan ganti konfigurasi di file <code>confs/db_config.yml</code>.
                Semua perubahan yang Anda buat di sini akan mengonfigurasi kegiatan pemilihan saat ini.
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <h5><i class="icon fas fa-info-circle"></i> Penyiapan Awal</h5>
                Tabel data pemilihan masih kosong. Silakan isi formulir di bawah ini untuk membuat data kegiatan pemilihan yang baru.
                Pastikan semua field yang ditandai bintang (<span class="text-danger">*</span>) diisi dengan benar.
                Setelah disimpan, Anda dapat mengubah data ini kapan saja sebelum pemilihan dimulai.
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
                            <option value="BERPASANGAN" <?= ($election_data['tipe_peserta_pemilihan'] ?? 'BERPASANGAN') == 'BERPASANGAN' ? 'selected' : '' ?>>Berpasangan (Contoh: Ketua & Wakil Ketua OSIS)</option>
                        </select>
                    </div>
                    <details>
                        <summary>Penjelasan Tipe Peserta Pemilihan</summary>
                        <p>Tipe peserta pemilihan menentukan apakah pemilihan dilakukan untuk posisi tunggal atau berpasangan:</p>
                        <ul>
                            <li><strong>Tunggal:</strong> Hanya ada satu kandidat untuk setiap posisi.</li>
                            <li><strong>Berpasangan:</strong> Ada pasangan kandidat yang mencalonkan diri untuk posisi yang sama (misalnya, Ketua dan Wakil Ketua).</li>
                        </ul>
                    </details>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="masa_bakti">Masa Bakti <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="masa_bakti" name="masa_bakti" placeholder="Contoh: 2025/2026" value="<?= htmlspecialchars($election_data['masa_bakti'] ?? '') ?>" required>
                    </div>
                    <details>
                        <summary>Penjelasan Masa Bakti</summary>
                        <p>Masa bakti adalah periode waktu di mana kandidat yang terpilih akan menjabat. Biasanya dituliskan dalam format tahun, seperti "2025/2026".</p>
                        <ul>
                            <li>Pastikan masa bakti sesuai dengan rencana organisasi.</li>
                            <li>Format yang umum digunakan adalah "Tahun Mulai/Tahun Selesai".</li>
                        </ul>
                    </details>
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
                    <details>
                        <summary>Penjelasan Tanggal Mulai</summary>
                        <p>Tanggal mulai adalah tanggal pertama pemungutan suara dimulai. Pastikan tanggal ini valid dan sesuai dengan rencana pemilihan.</p>
                    </details>
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
                    <details>
                        <summary>Penjelasan Tanggal Selesai</summary>
                        <p>Tanggal selesai adalah tanggal terakhir pemungutan suara. Jika pemilihan hanya berlangsung satu hari, biarkan kosong.</p>
                    </details>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="jumlah_tps">Jumlah Tempat Pemungutan Suara (TPS) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_tps" name="jumlah_tps" min="1" value="<?= htmlspecialchars($election_data['jumlah_tps'] ?? '1') ?>" required>
                    </div>
                    <details>
                        <summary>Penjelasan Jumlah TPS</summary>
                        <p>Jumlah Tempat Pemungutan Suara (TPS) adalah jumlah lokasi fisik di mana pemilih dapat memberikan suara mereka secara langsung.</p>
                        <ul>
                            <li><strong>Untuk pemilihan langsung (<em>offline</em>):</strong> Masukkan jumlah seluruh TPS yang disediakan oleh panitia pemilihan.</li>
                            <li><strong>Untuk pemilihan daring (<em>online</em>):</strong> Masukkan jumlah seluruh perangkat yang dapat digunakan untuk mengakses SSE, karena tidak ada TPS fisik.</li>
                        </ul>
                    </details>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="jumlah_passe">Jumlah Perangkat Akses SSE (PASSE) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_passe" name="jumlah_passe" min="1" value="<?= htmlspecialchars($election_data['jumlah_passe'] ?? '1') ?>" required>
                    </div>
                    <details>
                        <summary>Penjelasan Perangkat Akses SSE (PASSE)</summary>
                        <p>Perangkat Akses Surat Suara Elektronik (PASSE) adalah perangkat yang digunakan oleh pemilih untuk mengakses dan memberikan suara di Surat Suara Elektronik (SSE).</p>
                        <ul>
                            <li><strong>Untuk pemilihan langsung (<em>offline</em>):</strong> Masukkan jumlah seluruh perangkat yang disediakan untuk pemilih oleh panitia pemilihan di seluruh TPS.</li>
                            <li><strong>Untuk pemilihan daring (<em>online</em>):</strong> Masukkan jumlah seluruh perangkat milik pemilih yang dapat digunakan untuk mengakses SSE.</li>
                        </ul>
                    </details>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="mode_tampilan_sse">Mode Tampilan SSE <span class="text-danger">*</span></label>
                        <select class="form-control" id="mode_tampilan_sse" name="mode_tampilan_sse">
                            <option value="BERGAMBAR_BERTEKSVM" <?= ($election_data['mode_tampilan_sse'] ?? '') == 'BERGAMBAR_BERTEKSVM' ? 'selected' : '' ?>>Gambar dan Visi-Misi</option>
                            <option value="BERGAMBAR_NONTEKSVM" <?= ($election_data['mode_tampilan_sse'] ?? '') == 'BERGAMBAR_NONTEKSVM' ? 'selected' : '' ?>>Hanya Gambar</option>
                            <option value="NONGAMBAR_BERTEKSVM" <?= ($election_data['mode_tampilan_sse'] ?? '') == 'NONGAMBAR_BERTEKSVM' ? 'selected' : '' ?>>Hanya Visi-Misi</option>
                            <option value="NONGAMBAR_NONTEKSVM" <?= ($election_data['mode_tampilan_sse'] ?? '') == 'NONGAMBAR_NONTEKSVM' ? 'selected' : '' ?>>Hanya Teks</option>
                        </select>
                    </div>
                    <details>
                        <summary>Penjelasan Mode Tampilan</summary>
                        <p>Mode tampilan ini menentukan bagaimana kandidat akan ditampilkan di halaman pemungutan suara di Surat Suara Elektronik:</p>
                        <ul>
                            <li><strong>Gambar dan Visi-Misi:</strong> Menampilkan nama dan gambar kandidat, serta visi-misi mereka.</li>
                            <li><strong>Hanya Gambar:</strong> Menampilkan hanya nama dan gambar kandidat.</li>
                            <li><strong>Hanya Visi-Misi:</strong> Menampilkan nama kandidat dan visi-misi mereka tanpa gambar.</li>
                            <li><strong>Hanya Teks:</strong> Menampilkan nama kandidat saja tanpa gambar atau visi-misi.</li>
                        </ul>
                    </details>
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
                    <details>
                        <summary>Penjelasan Status Pemilihan</summary>
                        <p>Status pemilihan menentukan tahap kegiatan pemilihan:</p>
                        <ul>
                            <li><strong>Belum Dimulai:</strong> Kegiatan pemilihan belum dimulai.</li>
                            <li><strong>Sedang Berlangsung:</strong> Kegiatan pemilihan sedang berlangsung. Akses SSE akan dibuka untuk pemilih.</li>
                            <li><strong>Selesai Dilaksanakan:</strong> Kegiatan pemilihan telah selesai dilaksanakan.</li>
                        </ul>
                    </details>
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
