<?php
/**
 * e-SPPO - Surat Suara Elektronik (SSE) - Halaman Utama / Surat Suara - Pratayang untuk Pusminlihdu
 * sse/index_preview.php
 * 
 * Halaman pratayang dari tampilan SSE untuk keperluan preview.
 * Halaman ini menampilkan tampilan surat suara elektronik yang akan dilihat oleh pemilih.
 * Pratayang ini membantu memastikan bahwa semua elemen tampilan SSE sudah sesuai dengan yang diinginkan.
 * Halaman ini diakses di pusminlihdu/pengelola/pratayang_sse.php melalui iframe.
 *
 * Dari sse/index_preview.php:
 * Halaman ini menampilkan surat suara elektronik untuk pemilih.
 * Pemilih dapat memilih kandidat dengan mengeklik kartu kandidat.
 * Setelah memilih, pemilih dapat mengirimkan suaranya melalui tombol yang tersedia.
 * Skrip ini juga menangani otentikasi pemilih dan pengambilan data pemilihan serta kandidat.
 * Jika tidak ada pemilihan yang aktif, akan ditampilkan pesan yang sesuai.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. INISIALISASI & OTENTIKASI
// -----------------------------------------------------------------------------

// Mulai atau lanjutkan sesi dengan nama yang spesifik
session_name('eSPPO-SSE-V2');
session_start();

// Sertakan semua helper yang dibutuhkan
require_once __DIR__ . '/../helpers/db_helper.php';
require_once __DIR__ . '/../helpers/schooldata_helper.php';

// Definisikan versi aplikasi
define('ESPPO_VERSION', '2.0.0');

// **Otentikasi**: Cek apakah pemilih sudah login. Jika tidak, redirect ke halaman login.
// if (!isset($_SESSION['voter_id_unik'])) {
//    header('Location: login.php');
//    exit();
// }

// **Otentikasi**: Cek apakah pemilih sudah memilih sebelumnya. Jika sudah, redirect ke halaman sukses.
// if (isset($_SESSION['voter_status']) && $_SESSION['voter_status'] === 'SUDAH_MEMILIH') {
//    header('Location: vote_successful.php');
//    exit();
// }

// -----------------------------------------------------------------------------
// 2. PENGAMBILAN DATA
// -----------------------------------------------------------------------------

// Variabel untuk menyimpan data yang akan ditampilkan
$election_data = null;
$candidates = [];
$school_config = get_school_config(); // Ambil semua data sekolah sekali saja

// Dapatkan koneksi database
$db = get_db_connection();

try {
    // Ambil data pemilihan yang sedang berlangsung
    $stmt_election = $db->prepare("SELECT id_unik_pemilihan, nama_pemilihan, tipe_peserta_pemilihan, mode_tampilan_sse FROM data_pemilihan WHERE status_pemilihan = 'SEDANG_BERLANSUNG' LIMIT 1");
    $stmt_election->execute();
    $result_election = $stmt_election->get_result();
    $election_data = $result_election->fetch_assoc();
    $stmt_election->close();

    // Jika ada pemilihan yang aktif, ambil data kandidat
    if ($election_data) {
        $stmt_candidates = $db->prepare("SELECT id_unik_kandidat, no_urut_kandidat, nama_calon_1, nama_calon_2, visi_kandidat, misi_kandidat, foto_kandidat FROM data_kandidat ORDER BY no_urut_kandidat ASC");
        $stmt_candidates->execute();
        $result_candidates = $stmt_candidates->get_result();
        while ($row = $result_candidates->fetch_assoc()) {
            $candidates[] = $row;
        }
        $stmt_candidates->close();
    }

} catch (mysqli_sql_exception $e) {
    error_log("SSE Index - DB Error: " . $e->getMessage());
    // Hentikan eksekusi jika terjadi error fatal pada database
    die("Sistem sedang mengalami gangguan teknis. Tidak dapat memuat data surat suara.");
}

// Ambil data pemilih dari sesi untuk ditampilkan
$voter_name = "Contoh Pemilih"; // Ganti data pemilih contoh untuk pratayang
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Suara &ndash; e-SPPO</title>
    
    <!-- Favicon e-SPPO -->
    <link rel="icon" type="image/png" href="../assets/imgs/esppo/esppo-logo.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="../uis/bootstrap-5.3.7/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Inter & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bs-body-font-family: 'Inter', sans-serif;
            --bs-body-bg: #f0f2f5;
        }
        
        body {
            background-image: linear-gradient(rgba(240, 242, 245, 0.8), rgba(240, 242, 245, 0.8)), url('../assets/imgs/esppo/sse-index.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
        }

        .ballot-header {
            background-color: white;
            padding: 1.5rem;
            border-bottom: 3px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .ballot-header .school-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ballot-header .logo {
            height: 75px;
            width: 75px;
            object-fit: contain;
        }
        
        .ballot-header .school-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: #343a40;
        }

        .ballot-header .voter-info {
            background-color: #0d6efd;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            text-align: center;
        }

        .candidate-card {
            border: 2px solid transparent;
            border-radius: 1rem;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }
        
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #0d6efd;
        }

        .candidate-card input[type="radio"] {
            position: absolute;
            top: 1rem;
            right: 1rem;
            transform: scale(1.8);
            opacity: 0; /* Sembunyikan radio button asli */
        }
        
        /* Tampilan custom untuk radio button */
        .candidate-card .radio-custom {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 30px;
            height: 30px;
            border: 2px solid #adb5bd;
            border-radius: 50%;
            background-color: white;
            transition: all 0.2s;
        }
        
        .candidate-card input[type="radio"]:checked ~ .radio-custom {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .candidate-card input[type="radio"]:checked ~ .radio-custom::after {
            content: '✔';
            color: white;
            font-size: 18px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .candidate-card.selected {
            border-color: #0d6efd;
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
        }

        .candidate-photo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .candidate-number {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: #6c757d;
        }
        
        .candidate-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #212529;
        }
        
        .vision-mission {
            text-align: left;
            font-size: 0.875rem;
            max-height: 150px;
            overflow-y: auto;
        }
        
        .submit-area {
            position: sticky;
            bottom: 0;
            background: linear-gradient(to top, rgba(255,255,255,1) 70%, rgba(255,255,255,0));
            padding: 2rem 1rem 1rem 1rem;
            text-align: center;
        }
        
        .btn-submit-vote {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.25rem;
            padding: 0.75rem 3rem;
            border-radius: 50px;
        }
    </style>
</head>

<body>
    <header class="ballot-header">
        <div class="container">
            <div class="text-center mb-3">
                <h6 class="text-muted mb-0">Sistem Penyelenggaraan Pemilihan OSIS Elektronik (e-SPPO)</h6>
                <h5 class="fw-bold">APLIKASI SURAT SUARA ELEKTRONIK</h5>
            </div>
            <div class="school-info">
                <img src="../assets/imgs/sekolah/<?= htmlspecialchars($school_config['logo'][0]['logo_sekolah'] ?? 'placeholder_sekolah.png') ?>" onerror="this.onerror=null;this.src='../assets/imgs/sekolah/placeholder_sekolah.png';" alt="Logo Sekolah" class="logo">
                <span class="school-name text-center mx-3"><?= htmlspecialchars($school_config['nama_sekolah'] ?? 'Nama Sekolah') ?></span>
                <img src="../assets/imgs/sekolah/<?= htmlspecialchars($school_config['logo'][1]['logo_osis'] ?? 'placeholder_osis.png') ?>" onerror="this.onerror=null;this.src='../assets/imgs/sekolah/placeholder_osis.png';" alt="Logo OSIS" class="logo">
            </div>
            <div class="voter-info">
                Pemilih: <strong><?= $voter_name ?></strong>
            </div>
        </div>
    </header>

    <main class="container mb-5 pb-5">
        <?php if ($election_data && !empty($candidates)): ?>
            <div class="text-center mb-4">
                <h2 class="fw-bold"><?= htmlspecialchars($election_data['nama_pemilihan']) ?></h2>
                <p class="lead text-muted">Silakan pilih salah satu kandidat dengan mengeklik kartu pilihan Anda.</p>
            </div>

            <form id="voteForm" action="ballot_process.php" method="POST">
                <div class="row justify-content-center g-4">
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="col-md-6 col-lg-4">
                            <label class="candidate-card card h-100 text-center p-3">
                                <input type="radio" name="pilihan_kandidat" value="<?= htmlspecialchars($candidate['id_unik_kandidat']) ?>" required>
                                <div class="radio-custom"></div>
                                
                                <div class="card-body">
                                    <h3 class="candidate-number"><?= htmlspecialchars($candidate['no_urut_kandidat']) ?></h3>
                                    <img src="../assets/imgs/sse-foto-kandidat/<?= htmlspecialchars($candidate['foto_kandidat'] ?? 'default.png') ?>" 
                                         alt="Foto Kandidat <?= htmlspecialchars($candidate['no_urut_kandidat']) ?>" 
                                         class="candidate-photo my-3"
                                         onerror="this.onerror=null; this.src='https://placehold.co/240x240/e0e0e0/757575?text=Foto%20kandidat';">
                                    
                                    <p class="candidate-name mb-1"><?= htmlspecialchars($candidate['nama_calon_1']) ?></p>
                                    <?php if ($election_data['tipe_peserta_pemilihan'] === 'BERPASANGAN' && !empty($candidate['nama_calon_2'])): ?>
                                        <p class="text-muted small">& <?= htmlspecialchars($candidate['nama_calon_2']) ?></p>
                                    <?php endif; ?>

                                    <?php if (str_contains($election_data['mode_tampilan_sse'], 'BERTEKSVM')): ?>
                                        <hr>
                                        <div class="vision-mission p-2 bg-light rounded">
                                            <h6 class="fw-bold">Visi</h6>
                                            <p class="mb-2 small"><?= nl2br(htmlspecialchars($candidate['visi_kandidat'])) ?></p>
                                            <h6 class="fw-bold">Misi</h6>
                                            <div class="small"><?= nl2br(htmlspecialchars($candidate['misi_kandidat'])) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="submit-area">
                    <button type="button" id="submitBtn" class="btn btn-primary btn-lg btn-submit-vote" data-bs-toggle="modal" data-bs-target="#confirmModal" disabled>
                        Kirim Suara Saya
                    </button>
                </div>
            </form>

        <?php else: ?>
            <div class="alert alert-warning text-center" role="alert">
                <h4 class="alert-heading">Surat Suara Tidak Tersedia!</h4>
                <p>Saat ini tidak ada data pemilihan atau kandidat yang dapat ditampilkan. Mohon hubungi panitia pemilihan.</p>
                <hr>
                <p class="mb-0">Pastikan pemilihan sudah dimulai dan kandidat telah ditentukan.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal Konfirmasi -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmModalLabel">Konfirmasi Pilihan Anda</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Apakah Anda yakin dengan pilihan Anda? Suara yang sudah dikirim tidak dapat diubah kembali.
            Jika ada kesalahan dan ingin mereset pilihan, silakan hubungi panitia pemilihan.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak - Kembali ke Surat Suara</button>
            <button type="button" id="confirmVoteBtn" class="btn btn-primary">Ya - Kirim Suara</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Tambahkan pesan pratayang -->
    <div class="alert alert-warning position-fixed bottom-0 start-50 translate-middle-x mb-2" style="z-index: 1050;">
        <i class="fas fa-eye me-2"></i> Mode Pratayang - Tampilan ini hanya untuk preview
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="../uis/bootstrap-5.3.7/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('voteForm');
            const radioButtons = form.querySelectorAll('input[type="radio"]');
            const submitBtn = document.getElementById('submitBtn');
            const confirmVoteBtn = document.getElementById('confirmVoteBtn');
            const candidateCards = document.querySelectorAll('.candidate-card');

            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Aktifkan tombol submit jika ada pilihan
                    submitBtn.disabled = false;
                    
                    // Hapus kelas 'selected' dari semua kartu
                    candidateCards.forEach(card => card.classList.remove('selected'));
                    
                    // Tambahkan kelas 'selected' ke kartu yang dipilih
                    if (this.checked) {
                        this.closest('.candidate-card').classList.add('selected');
                    }
                });
            });

            // Nonaktifkan pengiriman form untuk pratayang
            confirmVoteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                alert('Ini hanya mode pratayang. Pengiriman suara dinonaktifkan.');
            });
        });
    </script>
</body>

</html>
