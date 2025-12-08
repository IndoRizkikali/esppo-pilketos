<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Cetak Daftar Kandidat
 * pusminlihdu/pengelola/cetak_daftar_kandidat.php
 * 
 * Halaman ini digunakan untuk mencetak daftar kandidat dalam format PDF.
 * Fitur ini memungkinkan pengelola untuk melihat dan mengunduh daftar kandidat
 * yang telah terdaftar dalam sistem e-SPPO untuk dicetak.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// -----------------------------------------------------------------------------
// 1. SETUP AWAL
// -----------------------------------------------------------------------------

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../helpers/db_helper.php';
require_once __DIR__ . '/../../helpers/schooldata_helper.php';

$db = get_db_connection();
$school_config = get_school_config();
if (!defined('ESPPO_VERSION')) {
    define('ESPPO_VERSION', '2.0.0');
}

// -----------------------------------------------------------------------------
// 2. KELAS KUSTOM PDF DENGAN TCPDF
// -----------------------------------------------------------------------------

class KANDIDAT_PDF extends TCPDF {
    public $schoolConfig;

    public function Header() {
        $this->Image('../../assets/imgs/esppo/esppo-logo.png', 20, 10, 20, '', 'PNG');
        $logo_sekolah_path = '../../assets/imgs/sekolah/' . ($this->schoolConfig['logo'][0]['logo_sekolah'] ?? 'placeholder_sekolah.png');
        if (file_exists($logo_sekolah_path)) {
            $this->Image($logo_sekolah_path, $this->getPageWidth() - 30, 8, 20, '');
        }

        $this->SetY(8);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 6, 'PUSAT ADMINISTRASI PEMILIHAN TERPADU (PUSMINLIHDU)', 0, 1, 'C');
        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 6, 'SISTEM PENYELENGGARAAN PEMILIHAN OSIS ELEKTRONIK (eSPPO)', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 8, $this->schoolConfig['nama_sekolah'] ?? 'NAMA SEKOLAH', 0, 1, 'C');
        $this->SetFont('helvetica', '', 9);
        $alamat = ($this->schoolConfig['alamat_sekolah'][0]['alamat'] ?? '') . ' Kec. ' . ($this->schoolConfig['alamat_sekolah'][1]['kecamatan']?? '') . ', ' . ($this->schoolConfig['alamat_sekolah'][2]['dati_2'] ?? '') . ' — Prov. ' . ($this->schoolConfig['alamat_sekolah'][3]['dati_1'] ?? '') . ' ' .  ($this->schoolConfig['alamat_sekolah'][4]['kode_pos'] ?? '');
        $this->Cell(0, 5, $alamat, 0, 1, 'C');
        
        $this->Line(10, 38, $this->getPageWidth() - 10, 38);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $pageWidth = $this->getPageWidth() - $this->original_lMargin - $this->original_rMargin;
        $this->Cell($pageWidth / 3, 10, 'e-SPPO v' . ESPPO_VERSION . ' — ' . $_SERVER['HTTP_HOST'], 0, 0, 'L');
        $this->Cell($pageWidth / 3, 10, 'Halaman ' . $this->getAliasNumPage() . ' dari ' . $this->getAliasNbPages(), 0, 0, 'C');
        $this->Cell($pageWidth / 3, 10, 'Dibuat: ' . date('d/m/Y H:i'), 0, 0, 'R');
    }
}

// -----------------------------------------------------------------------------
// 3. PENGAMBILAN DATA KANDIDAT
// -----------------------------------------------------------------------------

try {
    $election_data = $db->query("SELECT nama_pemilihan, tipe_peserta_pemilihan FROM data_pemilihan LIMIT 1")->fetch_assoc();
    
    $query = "
        SELECT 
            k.id_unik_kandidat, k.no_urut_kandidat, k.nama_calon_1, k.nama_calon_2, 
            k.jk_calon_1, k.jk_calon_2, k.foto_kandidat,
            kon1.nama_konstituensi AS konstituensi_calon_1,
            kon2.nama_konstituensi AS konstituensi_calon_2
        FROM data_kandidat k
        LEFT JOIN data_konstituensi kon1 ON k.kk_calon_1 = kon1.kode_konstituensi
        LEFT JOIN data_konstituensi kon2 ON k.kk_calon_2 = kon2.kode_konstituensi
        ORDER BY k.no_urut_kandidat ASC";
    $candidates = $db->query($query)->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) { 
    echo "<div class='alert alert-danger'>Gagal mengambil data: " . $e->getMessage() . "</div>"; 
    exit; 
}

// -----------------------------------------------------------------------------
// 4. PEMBUATAN DOKUMEN PDF
// -----------------------------------------------------------------------------

$pdf = new KANDIDAT_PDF('L', 'mm', 'A4'); // Landscape
$pdf->schoolConfig = $school_config;
$pdf->SetCreator('e-SPPO - Pusminlihdu');
$pdf->SetAuthor('Panitia Pemilihan OSIS' . ($school_config['nama_sekolah'] ? ' ' . $school_config['nama_sekolah'] : ''));
$pdf->SetTitle('Laporan Daftar Kandidat');
$pdf->setSubject('Laporan Daftar ' . ($election_data['tipe_peserta_pemilihan'] == 'BERPASANGAN' ? 'Pasangan Calon' : 'Calon') . ' Peserta Pemilihan' . ($election_data['nama_pemilihan'] ? ' - ' . $election_data['nama_pemilihan'] : ''));
$pdf->setKeywords('e-SPPO, Pusminlihdu, Kandidat, Pemilihan OSIS, ' . ($school_config['nama_sekolah'] ?? 'OSIS'));
$pdf->SetMargins(15, 42, 15);
$pdf->SetAutoPageBreak(TRUE, 20);

if (empty($candidates)) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Tidak ada data kandidat yang ditemukan.', 0, 1, 'C');
} else {
    foreach ($candidates as $candidate) {
        $pdf->AddPage();
        
        // Judul halaman
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'DAFTAR RINCIAN DATA KANDIDAT', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, strtoupper($election_data['nama_pemilihan'] ?? 'PEMILIHAN OSIS'), 0, 1, 'C');
        $pdf->Ln(4);

        // Nomor Urut
        $pdf->SetFont('helvetica', 'B', 36);
        $pdf->Cell(0, 20, $candidate['no_urut_kandidat'], 0, 1, 'C');
        $pdf->Ln(4);

        // --- DATA BLOK ---
        $leftMargin = 40;
        $cellHeight = 7;
        $labelWidth = 50;
        $valueWidth = 120;
        
        // Foto
        $photo_path = '../../assets/imgs/sse-foto-kandidat/' . ($candidate['foto_kandidat'] ?? 'placeholder_kandidat.png');
        if (file_exists($photo_path)) {
            $pdf->Image($photo_path, 190, 80, 60, 0);
        }

        // Detail Calon 1
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetX($leftMargin);
        $pdf->Cell(0, $cellHeight, 'DATA CALON 1 (KETUA):', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 11);
        
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelWidth, $cellHeight, 'Nama Lengkap', 0, 0, 'L');
        $pdf->Cell($valueWidth, $cellHeight, ': ' . htmlspecialchars($candidate['nama_calon_1']), 0, 1, 'L');
        
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelWidth, $cellHeight, 'Jenis Kelamin', 0, 0, 'L');
        $pdf->Cell($valueWidth, $cellHeight, ': ' . htmlspecialchars($candidate['jk_calon_1']), 0, 1, 'L');
        
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelWidth, $cellHeight, 'Konstituensi', 0, 0, 'L');
        $pdf->Cell($valueWidth, $cellHeight, ': ' . htmlspecialchars($candidate['konstituensi_calon_1'] ?? 'N/A'), 0, 1, 'L');
        $pdf->Ln(5);

        // Detail Calon 2 (jika ada)
        if (($election_data['tipe_peserta_pemilihan'] ?? '') === 'BERPASANGAN' && !empty($candidate['nama_calon_2'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetX($leftMargin);
            $pdf->Cell(0, $cellHeight, 'DATA CALON 2 (WAKIL):', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 11);
            
            $pdf->SetX($leftMargin);
            $pdf->Cell($labelWidth, $cellHeight, 'Nama Lengkap', 0, 0, 'L');
            $pdf->Cell($valueWidth, $cellHeight, ': ' . htmlspecialchars($candidate['nama_calon_2']), 0, 1, 'L');
            
            $pdf->SetX($leftMargin);
            $pdf->Cell($labelWidth, $cellHeight, 'Jenis Kelamin', 0, 0, 'L');
            $pdf->Cell($valueWidth, $cellHeight, ': ' . htmlspecialchars($candidate['jk_calon_2']), 0, 1, 'L');
            
            $pdf->SetX($leftMargin);
            $pdf->Cell($labelWidth, $cellHeight, 'Konstituensi', 0, 0, 'L');
            $pdf->Cell($valueWidth, $cellHeight, ': ' . htmlspecialchars($candidate['konstituensi_calon_2'] ?? 'N/A'), 0, 1, 'L');
        }
    }
}

// -----------------------------------------------------------------------------
// 5. OUTPUT KE BROWSER
// -----------------------------------------------------------------------------

$filename = 'laporan_daftar_kandidat_' . time() . '.pdf';
$pdf_web_path = '../../assets/pdfs/laporan/' . $filename; 
$pdf_server_path = realpath(__DIR__ . '/../../assets/pdfs/laporan') . DIRECTORY_SEPARATOR . $filename;

try {
    if (!is_writable(dirname($pdf_server_path))) {
        throw new Exception("Direktori PDF tidak dapat ditulis: " . dirname($pdf_server_path));
    }
    $pdf->Output($pdf_server_path, 'F');
} catch (Exception $e) { echo "<div class='alert alert-danger'>TCPDF ERROR: " . $e->getMessage() . "</div>"; exit; }
?>

<!-- PDF.js Library -->
<script src="../../vendor/clean-composer-packages/pdf-js/build/pdf.mjs" type="module"></script>
<script src="../../assets/js/pdfjs-viewer.js"></script>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pratayang Laporan Daftar Kandidat</h3>
    </div>
    <div class="card-body">
        <div id="pdf-viewer-container"></div>
        <noscript>
            <iframe src="<?= htmlspecialchars($pdf_web_path) ?>" height="800" width="100%" style="border:none;" allowfullscreen></iframe>
        </noscript>
    </div>
    <div class="card-footer">
        <a href="<?= htmlspecialchars($pdf_web_path) ?>" class="btn btn-success" download>
            <i class="fas fa-file-download"></i> Unduh Laporan (PDF)
        </a>
        <a href="<?= htmlspecialchars($pdf_web_path) ?>" class="btn btn-secondary" target="_blank">
            <i class="fas fa-external-link-alt"></i> Buka di Tab Baru
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    ESPPOPDFViewer.init('pdf-viewer-container', '<?= htmlspecialchars($pdf_web_path) ?>', {
        height: 800,
        workerSrc: '../../vendor/clean-composer-packages/pdf-js/build/pdf.worker.mjs'
    });
});
</script>
