<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Cetak SSE LJK
 * pusminlihdu/pengelola/cetak_sse_ljk.php
 * 
 * Halaman ini digunakan untuk membuat templat Lembar Jawab Komputer (LJK) siap cetak
 * dalam format PDF untuk metode pemilihan semi-elektronik berbasis OMR.
 * Dapat digunakan sebagai metode utama atau fallback jika sistem elektronik penuh bermasalah.
 * Setiap halaman A4 memuat 2 lembar LJK.
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

if (!defined('ESPPO_VERSION')) {
    define('ESPPO_VERSION', '2.0.0');
}

$db = get_db_connection();
$school_config = get_school_config();

// -----------------------------------------------------------------------------
// 2. KELAS KUSTOM PDF DENGAN TCPDF
// -----------------------------------------------------------------------------

class LJK_PDF extends TCPDF {
    public $schoolConfig;
    public $electionData;
    public $candidates;
    public $ballotMode;

    // Override header - kosong karena LJK punya format khusus
    public function Header() {
        // Tidak ada header standar untuk LJK
    }

    // Override footer - kosong untuk LJK
    public function Footer() {
        // Tidak ada footer standar untuk LJK
    }

    /**
     * Menggambar satu lembar LJK pada posisi Y tertentu
     * @param float $startY Posisi Y awal untuk LJK
     * @param float $height Tinggi area LJK
     */
    public function drawBallot($startY, $height) {
        $pageWidth = $this->getPageWidth();
        $leftMargin = 8;
        $rightMargin = 8;
        $contentWidth = $pageWidth - $leftMargin - $rightMargin;
        
        // OMR Registration Marks (pojok untuk alignment scanner)
        $this->drawRegistrationMarks($startY, $height, $leftMargin, $pageWidth - $rightMargin);
        
        // Border LJK
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.5);
        $this->Rect($leftMargin, $startY, $contentWidth, $height);
        
        // Header LJK
        $headerY = $startY + 3;
        $this->SetFont('helvetica', 'B', 9);
        $this->SetXY($leftMargin, $headerY);
        $this->Cell($contentWidth, 5, 'SURAT SUARA ELEKTRONIK - LEMBAR JAWAB KOMPUTER (SSE-LJK)', 0, 1, 'C');
        
        $this->SetFont('helvetica', 'B', 8);
        $this->SetXY($leftMargin, $headerY + 5);
        $this->Cell($contentWidth, 4, $this->electionData['nama_pemilihan'] ?? 'PEMILIHAN OSIS', 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 7);
        $this->SetXY($leftMargin, $headerY + 9);
        $this->Cell($contentWidth, 4, $this->schoolConfig['nama_sekolah'] ?? 'NAMA SEKOLAH', 0, 1, 'C');
        
        // Tanggal pemilihan
        $tanggal = $this->formatElectionDate();
        $this->SetXY($leftMargin, $headerY + 13);
        $this->Cell($contentWidth, 3, $tanggal, 0, 1, 'C');
        
        // Garis pemisah
        $this->Line($leftMargin + 5, $headerY + 18, $pageWidth - $rightMargin - 5, $headerY + 18);
        
        // Area kandidat
        $candidateAreaY = $headerY + 22;
        $candidateAreaHeight = $height - 35;
        $this->drawCandidateArea($leftMargin, $candidateAreaY, $contentWidth, $candidateAreaHeight);
        
        // Petunjuk pengisian
        $instructionY = $startY + $height - 12;
        $this->SetFont('helvetica', '', 6);
        $this->SetXY($leftMargin + 2, $instructionY);
        $this->MultiCell($contentWidth - 4, 3, 
            'PETUNJUK: Hitamkan SATU lingkaran pilihan Anda menggunakan pensil 2B. Jangan melipat atau merusak lembar ini.', 
            0, 'C');
        
        // Nomor seri (untuk identifikasi)
        $this->SetFont('courier', '', 6);
        $this->SetXY($leftMargin + 2, $startY + $height - 10);
        $serialNo = 'SN: ' . strtoupper(substr(md5(uniqid()), 0, 8));
        $this->Cell(30, 3, $serialNo, 0, 0, 'L');
        
        // Barcode untuk validasi
        $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
        $barcodeData = ($this->schoolConfig['npsn_sekolah'] ?? '00000000') . '-LJK';
        $this->write1DBarcode($barcodeData, 'C39', $pageWidth - $rightMargin - 45, $startY + $height - 8, 40, 6, 0.3, $style, 'N');
    }

    /**
     * Menggambar tanda registrasi OMR di pojok-pojok
     */
    private function drawRegistrationMarks($startY, $height, $leftX, $rightX) {
        $this->SetFillColor(0, 0, 0);
        $markSize = 4;
        
        // Pojok kiri atas
        $this->Rect($leftX, $startY, $markSize, $markSize, 'F');
        // Pojok kanan atas
        $this->Rect($rightX - $markSize, $startY, $markSize, $markSize, 'F');
        // Pojok kiri bawah
        $this->Rect($leftX, $startY + $height - $markSize, $markSize, $markSize, 'F');
        // Pojok kanan bawah
        $this->Rect($rightX - $markSize, $startY + $height - $markSize, $markSize, $markSize, 'F');
    }

    /**
     * Menggambar area kandidat dengan lingkaran OMR
     */
    private function drawCandidateArea($leftX, $startY, $width, $height) {
        $candidateCount = count($this->candidates);
        if ($candidateCount === 0) {
            $this->SetFont('helvetica', 'I', 8);
            $this->SetXY($leftX, $startY + ($height / 2) - 5);
            $this->Cell($width, 10, 'Tidak ada data kandidat', 0, 1, 'C');
            return;
        }
        
        // Hitung lebar kolom per kandidat
        $colWidth = $width / $candidateCount;
        $circleRadius = 5;
        $circleY = $startY + 8;
        
        foreach ($this->candidates as $index => $candidate) {
            $colX = $leftX + ($index * $colWidth);
            $centerX = $colX + ($colWidth / 2);
            
            // Lingkaran OMR untuk pilihan
            $this->SetDrawColor(0, 0, 0);
            $this->SetLineWidth(0.4);
            $this->Circle($centerX, $circleY, $circleRadius);
            
            // Nomor urut di dalam lingkaran
            $this->SetFont('helvetica', 'B', 10);
            $this->SetXY($centerX - 3, $circleY - 3);
            $this->Cell(6, 6, $candidate['no_urut_kandidat'], 0, 0, 'C');
            
            // Tampilkan foto jika mode DENGAN_GAMBAR
            $contentY = $circleY + $circleRadius + 3;
            if ($this->ballotMode === 'DENGAN_GAMBAR') {
                $fotoPath = '../../assets/imgs/sse-foto-kandidat/' . ($candidate['foto_kandidat'] ?? 'placeholder_kandidat.png');
                if (file_exists($fotoPath)) {
                    $fotoWidth = min(25, $colWidth - 6);
                    $fotoHeight = $fotoWidth * 1.2; // Rasio 5:6
                    $fotoX = $centerX - ($fotoWidth / 2);
                    $this->Image($fotoPath, $fotoX, $contentY, $fotoWidth, $fotoHeight);
                    $contentY += $fotoHeight + 2;
                }
            }
            
            // Nama kandidat
            $this->SetFont('helvetica', '', 6);
            $this->SetXY($colX + 1, $contentY);
            
            $namaKandidat = $candidate['nama_calon_1'];
            if (!empty($candidate['nama_calon_2'])) {
                $namaKandidat .= "\n& " . $candidate['nama_calon_2'];
            }
            $this->MultiCell($colWidth - 2, 3, $namaKandidat, 0, 'C');
            
            // Garis pemisah antar kandidat (kecuali kandidat terakhir)
            if ($index < $candidateCount - 1) {
                $this->SetDrawColor(200, 200, 200);
                $this->SetLineWidth(0.2);
                $lineX = $colX + $colWidth;
                $this->Line($lineX, $startY + 2, $lineX, $startY + $height - 5);
            }
        }
    }

    /**
     * Format tanggal pemilihan
     */
    private function formatElectionDate() {
        $tanggalMulai = $this->electionData['tanggal_mulai'] ?? date('Y-m-d');
        $tanggalSelesai = $this->electionData['tanggal_selesai'] ?? date('Y-m-d');
        
        if ($tanggalMulai === $tanggalSelesai) {
            return date('d/m/Y', strtotime($tanggalMulai));
        } else {
            return date('d/m/Y', strtotime($tanggalMulai)) . ' s.d. ' . date('d/m/Y', strtotime($tanggalSelesai));
        }
    }
}

// -----------------------------------------------------------------------------
// 3. PENGAMBILAN DATA
// -----------------------------------------------------------------------------

try {
    // Data pemilihan
    $election_data = $db->query("SELECT * FROM data_pemilihan LIMIT 1")->fetch_assoc();
    
    if (!$election_data) {
        throw new Exception("Data pemilihan tidak ditemukan");
    }
    
    // Data kandidat
    $candidates = $db->query("
        SELECT no_urut_kandidat, foto_kandidat, nama_calon_1, nama_calon_2 
        FROM data_kandidat 
        ORDER BY no_urut_kandidat ASC
    ")->fetch_all(MYSQLI_ASSOC);
    
    if (empty($candidates)) {
        throw new Exception("Tidak ada data kandidat yang ditemukan");
    }
    
    // Mode surat suara
    $ballotMode = $election_data['mode_surat_suara'] ?? 'TANPA_GAMBAR';
    
} catch (Exception $e) {
    die("<div class='alert alert-danger'>Gagal mengambil data: " . $e->getMessage() . "</div>");
}

// -----------------------------------------------------------------------------
// 4. PEMBUATAN DOKUMEN PDF
// -----------------------------------------------------------------------------

$pdf = new LJK_PDF('P', 'mm', 'A4'); // Portrait A4
$pdf->schoolConfig = $school_config;
$pdf->electionData = $election_data;
$pdf->candidates = $candidates;
$pdf->ballotMode = $ballotMode;

$pdf->SetCreator('e-SPPO - Pusminlihdu');
$pdf->SetAuthor('Panitia Pemilihan OSIS' . ($school_config['nama_sekolah'] ? ' ' . $school_config['nama_sekolah'] : ''));
$pdf->SetTitle('Templat SSE Lembar Jawab Komputer (LJK) OMR');
$pdf->SetSubject('Surat Suara Elektronik LJK untuk ' . ($election_data['nama_pemilihan'] ?? 'Pemilihan OSIS'));
$pdf->SetKeywords('e-SPPO, Pusminlihdu, SSE, LJK, OMR, Surat Suara, Pemilihan OSIS, ' . ($school_config['nama_sekolah'] ?? 'OSIS'));

// Margin sempit untuk memaksimalkan ruang
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(FALSE);

// Halaman dengan 2 LJK
$pdf->AddPage();

// Tinggi halaman A4 = 297mm, margin atas/bawah = 5mm
// Area tersedia = 297 - 10 = 287mm
// Jarak antar LJK = 5mm
// Tinggi per LJK = (287 - 5) / 2 = 141mm

$pageHeight = $pdf->getPageHeight();
$topMargin = 5;
$bottomMargin = 5;
$gapBetween = 5;
$availableHeight = $pageHeight - $topMargin - $bottomMargin - $gapBetween;
$ballotHeight = $availableHeight / 2;

// LJK pertama (atas)
$pdf->drawBallot($topMargin, $ballotHeight);

// Garis potong (optional, untuk memudahkan pemotongan)
$pdf->SetDrawColor(150, 150, 150);
$pdf->SetLineWidth(0.2);
$cutLineY = $topMargin + $ballotHeight + ($gapBetween / 2);

// Garis putus-putus menggunakan SetLineStyle
$pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => '2,2', 'color' => array(150, 150, 150)));
$pdf->Line(0, $cutLineY, $pdf->getPageWidth(), $cutLineY);

// Reset ke garis solid
$pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

// Simbol gunting
$pdf->SetFont('zapfdingbats', '', 8);
$pdf->SetXY(2, $cutLineY - 2);
$pdf->Cell(5, 4, chr(34), 0, 0, 'L'); // Simbol gunting

// LJK kedua (bawah)
$pdf->drawBallot($topMargin + $ballotHeight + $gapBetween, $ballotHeight);

// -----------------------------------------------------------------------------
// 5. OUTPUT KE BROWSER
// -----------------------------------------------------------------------------

$filename = 'templat_sse_ljk_' . time() . '.pdf';
$pdf_web_path = '../../assets/pdfs/laporan/' . $filename;
$pdf_server_path = realpath(__DIR__ . '/../../assets/pdfs/laporan') . DIRECTORY_SEPARATOR . $filename;

try {
    if (!is_writable(dirname($pdf_server_path))) {
        throw new Exception("Direktori PDF tidak dapat ditulis: " . dirname($pdf_server_path));
    }
    $pdf->Output($pdf_server_path, 'F');
} catch (Exception $e) {
    die("<div class='alert alert-danger'>TCPDF ERROR: " . $e->getMessage() . "</div>");
}
?>

<!-- PDF.js Library -->
<script src="../../vendor/clean-composer-packages/pdf-js/build/pdf.mjs" type="module"></script>
<script src="../../assets/js/pdfjs-viewer.js"></script>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pratayang Templat SSE Lembar Jawab Komputer (LJK)</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Petunjuk:</strong> Setiap halaman A4 memuat 2 lembar LJK. 
            Gunakan kertas HVS 80gsm dan cetak dengan kualitas tinggi untuk hasil OMR yang optimal.
            Potong mengikuti garis putus-putus.
        </div>
        <div id="pdf-viewer-container"></div>
        <noscript>
            <iframe src="<?= htmlspecialchars($pdf_web_path) ?>" height="800" width="100%" style="border:none;" allowfullscreen></iframe>
        </noscript>
    </div>
    <div class="card-footer">
        <a href="<?= htmlspecialchars($pdf_web_path) ?>" class="btn btn-success" download>
            <i class="fas fa-file-download"></i> Unduh Templat SSE LJK (PDF)
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
