<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Cetak Daftar Kehadiran Pemilih
 * pusminlihdu/pengelola/cetak_kehadiran.php
 * 
 * Halaman ini digunakan untuk mencetak laporan dan statistik kehadiran pemilih dalam pemilihan dalam format PDF.
 * Laporan ini mencakup informasi seperti jumlah pemilih yang hadir, tidak hadir, dan total pemilih, serta
 * daftar nama pemilih yang hadir dan waktu login mereka.
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

class ATTENDANCE_PDF extends TCPDF {
    public $schoolConfig;

    public function Header() {
        $this->Image('../../assets/imgs/esppo/esppo-logo.png', 12.5, 10.5, 22.5, '', 'PNG');
        $logo_sekolah_path = '../../assets/imgs/sekolah/' . ($this->schoolConfig['logo'][0]['logo_sekolah'] ?? 'placeholder_sekolah.png');
        if (file_exists($logo_sekolah_path)) {
            $this->Image($logo_sekolah_path, $this->getPageWidth() - 32.5, 7, 22.5, '');
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
// 3. PENGAMBILAN & PEMROSESAN DATA
// -----------------------------------------------------------------------------

try {
    // Ambil data pemilihan
    $election_data = $db->query("SELECT nama_pemilihan FROM data_pemilihan LIMIT 1")->fetch_assoc();

    // Ambil statistik kehadiran per konstituensi
    $attendance_stats = $db->query("
        SELECT 
            k.kode_konstituensi,
            k.nama_konstituensi,
            k.tipe_konstituensi,
            COUNT(DISTINCT p.id_unik_pemilih) as total_dpt,
            COUNT(DISTINCT h.id_unik_pemilih) as total_hadir
        FROM data_konstituensi k
        LEFT JOIN data_pemilih p ON k.kode_konstituensi = p.kk_pemilih
        LEFT JOIN data_kehadiran h ON p.id_unik_pemilih = h.id_unik_pemilih
        WHERE k.status_konstituensi = 'DIAKTIFKAN'
        GROUP BY k.kode_konstituensi, k.nama_konstituensi, k.tipe_konstituensi
        ORDER BY k.kode_konstituensi ASC")->fetch_all(MYSQLI_ASSOC);

    // Ambil daftar kehadiran detail
    $attendance_list = $db->query("
        SELECT 
            h.urutan_kehadiran,
            h.stempel_waktu_kehadiran,
            p.nama_pemilih,
            p.nomor_dpt_pemilih,
            k.nama_konstituensi,
            h.jk_pemilih
        FROM data_kehadiran h
        JOIN data_pemilih p ON h.id_unik_pemilih = p.id_unik_pemilih
        JOIN data_konstituensi k ON h.kk_pemilih = k.kode_konstituensi
        ORDER BY h.stempel_waktu_kehadiran ASC")->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("<div class='alert alert-danger'>Gagal mengambil data: " . $e->getMessage() . "</div>");
}

// -----------------------------------------------------------------------------
// 4. PEMBUATAN DOKUMEN PDF
// -----------------------------------------------------------------------------

$pdf = new ATTENDANCE_PDF('P', 'mm', 'A4');
$pdf->schoolConfig = $school_config;
$pdf->SetCreator('e-SPPO - Pusminlihdu');
$pdf->SetAuthor('Panitia Pemilihan OSIS' . ($school_config['nama_sekolah'] ? ' ' . $school_config['nama_sekolah'] : ''));
$pdf->SetTitle('Laporan Statistik dan Daftar Kehadiran Pemilih');
$pdf->setSubject('Laporan Statistik dan Daftar Kehadiran Pemilih ' . ($election_data['nama_pemilihan'] ?? 'Pemilihan OSIS') . ' di ' . ($school_config['nama_sekolah'] ?? 'Sekolah'));
$pdf->setKeywords('e-SPPO, Pusminlihdu, Laporan Kehadiran, Daftar Kehadiran, Pemilih, Pemilihan OSIS, ' . ($school_config['nama_sekolah'] ?? 'OSIS'));
$pdf->SetMargins(10, 42, 10);
$pdf->SetAutoPageBreak(TRUE, 15);

// Halaman 1: Statistik Kehadiran
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN STATISTIK KEHADIRAN PEMILIH', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, strtoupper($election_data['nama_pemilihan'] ?? 'PEMILIHAN OSIS'), 0, 1, 'C');
$pdf->Ln(5);

// Header tabel statistik
$header = ['No', 'Konstituensi', 'Total DPT', 'Hadir', 'Tidak Hadir', '%'];
$w = [12, 80, 25, 25, 25, 20];
$pdf->SetFillColor(224, 235, 255);
$pdf->SetFont('helvetica', 'B', 10);
for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Isi tabel statistik
$pdf->SetFont('helvetica', '', 10);
$no = 1;
$grand_total = ['dpt' => 0, 'hadir' => 0];
$fill = false;

foreach($attendance_stats as $stat) {
    $tidak_hadir = $stat['total_dpt'] - $stat['total_hadir'];
    $persen = $stat['total_dpt'] > 0 ? ($stat['total_hadir'] / $stat['total_dpt'] * 100) : 0;
    
    $pdf->Cell($w[0], 6, $no++, 'LR', 0, 'C', $fill);
    $pdf->Cell($w[1], 6, '   ' . $stat['nama_konstituensi'], 'LR', 0, 'L', $fill);
    $pdf->Cell($w[2], 6, number_format($stat['total_dpt']), 'LR', 0, 'C', $fill);
    $pdf->Cell($w[3], 6, number_format($stat['total_hadir']), 'LR', 0, 'C', $fill);
    $pdf->Cell($w[4], 6, number_format($tidak_hadir), 'LR', 0, 'C', $fill);
    $pdf->Cell($w[5], 6, number_format($persen, 1) . '%', 'LR', 0, 'C', $fill);
    $pdf->Ln();
    
    $grand_total['dpt'] += $stat['total_dpt'];
    $grand_total['hadir'] += $stat['total_hadir'];
    $fill = !$fill;
}

// Baris total
$pdf->SetFont('helvetica', 'B', 10);
$total_tidak_hadir = $grand_total['dpt'] - $grand_total['hadir'];
$total_persen = $grand_total['dpt'] > 0 ? ($grand_total['hadir'] / $grand_total['dpt'] * 100) : 0;

$pdf->Cell($w[0] + $w[1], 7, 'TOTAL', 'T', 0, 'C', true);
$pdf->Cell($w[2], 7, number_format($grand_total['dpt']), 'T', 0, 'C', true);
$pdf->Cell($w[3], 7, number_format($grand_total['hadir']), 'T', 0, 'C', true);
$pdf->Cell($w[4], 7, number_format($total_tidak_hadir), 'T', 0, 'C', true);
$pdf->Cell($w[5], 7, number_format($total_persen, 1) . '%', 'T', 1, 'C', true);

// Halaman 2+: Daftar Kehadiran
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'DAFTAR KEHADIRAN PEMILIH', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, strtoupper($election_data['nama_pemilihan'] ?? 'PEMILIHAN OSIS'), 0, 1, 'C');
$pdf->Ln(5);

// Header tabel kehadiran
$header2 = ['No', 'Waktu Hadir', 'No. DPT', 'Nama Pemilih', 'JK', 'Konstituensi'];
$w2 = [12, 35, 25, 70, 15, 30];
$pdf->SetFont('helvetica', 'B', 10);
for($i = 0; $i < count($header2); $i++) {
    $pdf->Cell($w2[$i], 7, $header2[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Isi tabel kehadiran
$pdf->SetFont('helvetica', '', 9);
$no = 1;
$fill = false;

foreach($attendance_list as $attendance) {
    if ($pdf->GetY() > ($pdf->getPageHeight() - 25)) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'DAFTAR KEHADIRAN PEMILIH (LANJUTAN)', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 10);
        for($i = 0; $i < count($header2); $i++) {
            $pdf->Cell($w2[$i], 7, $header2[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 9);
    }
    
    $pdf->Cell($w2[0], 6, $no++, 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[1], 6, date('d/m/Y H:i:s', strtotime($attendance['stempel_waktu_kehadiran'])), 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[2], 6, $attendance['nomor_dpt_pemilih'], 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[3], 6, $attendance['nama_pemilih'], 'LR', 0, 'L', $fill);
    $pdf->Cell($w2[4], 6, substr($attendance['jk_pemilih'], 0, 1), 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[5], 6, $attendance['nama_konstituensi'], 'LR', 0, 'L', $fill);
    $pdf->Ln();
    $fill = !$fill;
}
$pdf->Cell(array_sum($w2), 0, '', 'T');

// -----------------------------------------------------------------------------
// 5. OUTPUT KE BROWSER
// -----------------------------------------------------------------------------

$filename = 'laporan_kehadiran_' . time() . '.pdf';
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
        <h3 class="card-title">Pratayang Laporan Kehadiran</h3>
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
