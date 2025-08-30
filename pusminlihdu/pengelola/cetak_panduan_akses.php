<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Cetak Panduan Akses
 * pusminlihdu/pengelola/cetak_panduan_akses.php
 *
 * Halaman ini digunakan untuk mencetak panduan akses aplikasi e-SPPO dalam format PDF.
 * Fitur ini memungkinkan pengelola untuk menyediakan panduan akses cepat dan
 * kode QR untuk akses aplikasi SSE (Surat Suara Elektronik) dan Pusminlihdu.
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
require_once __DIR__ . '/../../helpers/schooldata_helper.php';

if (!defined('ESPPO_VERSION')) {
    define('ESPPO_VERSION', '2.0.0');
}

$school_config = get_school_config();

// -----------------------------------------------------------------------------
// 2. KELAS KUSTOM PDF DENGAN TCPDF
// -----------------------------------------------------------------------------

class PANDUAN_PDF extends TCPDF {
    public $schoolConfig;

    public function Header() {
        $this->Image('../../assets/imgs/esppo/esppo-logo.png', 15, 12.5, 22.5, '', 'PNG');
        $logo_sekolah_path = '../../assets/imgs/sekolah/' . ($this->schoolConfig['logo'][0]['logo_sekolah'] ?? 'placeholder-sekolah.png');
        if (file_exists($logo_sekolah_path)) {
            $this->Image($logo_sekolah_path, $this->getPageWidth() - 40, 9.5, 22.5, '');
        }
        
        $this->SetY(12);
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 8, 'PANDUAN AKSES CEPAT', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 8, 'Sistem Penyelenggaraan Pemilihan OSIS Elektronik', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 6, $this->schoolConfig['nama_sekolah'] ?? 'NAMA SEKOLAH', 0, 1, 'C');
        
        $this->Line(15, 40, $this->getPageWidth() - 15, 40);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'e-SPPO v' . ESPPO_VERSION . ' — Halaman ' . $this->getAliasNumPage() . 
                         ' dari ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// -----------------------------------------------------------------------------
// 3. PENGATURAN DOKUMEN PDF
// -----------------------------------------------------------------------------

$pdf = new PANDUAN_PDF('P', 'mm', 'A4');
$pdf->schoolConfig = $school_config;
$pdf->SetCreator('e-SPPO Pusminlihdu');
$pdf->SetAuthor('Panitia Pemilihan');
$pdf->SetTitle('Panduan dan Kode QR Akses Aplikasi e-SPPO');
$pdf->SetMargins(15, 42.5, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// -----------------------------------------------------------------------------
// 4. KONTEN PANDUAN
// -----------------------------------------------------------------------------

// Bagian 1: Panduan SSE
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Panduan Akses Surat Suara Elektronik (SSE)', 0, 1, 'C');

// Generate QR codes untuk SSE
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 6, 'Pindai kode QR berikut menggunakan kamera ponsel Anda untuk membuka aplikasi SSE:', 0, 'C');
$pdf->Ln(4);

// URL SSE dari konfigurasi
$sse_urls = $school_config['url_akses_esppo'][0]['url_sse'] ?? [];
$url_count = count($sse_urls);
if ($url_count > 0) {
    $cell_width = 180 / min($url_count, 3); // Max 3 QR codes per row
    $qr_size = min(40, $cell_width - 10);
    
    $pdf->SetFillColor(245, 245, 245);
    $y_start = $pdf->GetY();
    
    foreach ($sse_urls as $index => $url) {
        if ($index > 0 && $index % 3 == 0) {
            $pdf->Ln($qr_size + 20);
            $y_start = $pdf->GetY();
        }
        
        $x = 15 + ($index % 3) * $cell_width;
        $pdf->SetXY($x, $y_start);
        
        // QR Code
        $style = array(
            'border' => false,
            'padding' => 2,
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => array(255, 255, 255)
        );
        $pdf->write2DBarcode($url . 'sse/', 'QRCODE,H', $x + 7, $y_start, $qr_size, $qr_size, $style);
        
        // URL Text
        $pdf->SetXY($x, $y_start + $qr_size + 2);
        $pdf->SetFont('courier', '', 8);
        $pdf->MultiCell($cell_width - 5, 4, "URL " . ($index + 1) . ":\n" . $url . 'sse/', 0, 'C');
    }
    $pdf->Ln($qr_size / 2 + 2.5);
}

// Petunjuk Singkat SSE
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 9, 'Petunjuk Singkat Penggunaan SSE:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 11);
$steps_sse = array(
    'Pindai salah satu kode QR di atas atau ketik URL pada peramban web.',
    'Masuk menggunakan nama akun dan kata sandi yang telah diberikan.',
    'Ikuti petunjuk pada layar untuk memilih kandidat.',
    'Pastikan pilihan Anda sudah benar sebelum mengirim suara.',
    'Setelah berhasil, Anda akan melihat pesan konfirmasi.'
);

foreach ($steps_sse as $index => $step) {
    $pdf->Cell(10, 6.5, ($index + 1) . '.', 0, 0);
    $pdf->Cell(10, 6.5, $step, 0, 'L');
    $pdf->setX($pdf->GetX() - 10);
}

$pdf->Ln(3);
$pdf->MultiCell(0, 6, 'Panduan dan Informasi lebih lanjut dapat ditemukan di bagian panduan penggunaan SSE untuk pemilih di Panduan Pengguna dan Administrasi e-SPPO.', 0, 'L');
$pdf->setFont('helvetica', 'B', 11);
$pdf->Ln(2);
$pdf->MultiCell(0, 6, 'Ikuti instruksi yang diberikan oleh panitia pemilihan.', 0, 'L');
$pdf->MultiCell(0, 6, 'Apabila panitia menyediakan perangkat akses surat suara elektronik (PASSE) di TPS, jangan gunakan ponsel pribadi Anda untuk mengakses SSE kecuali dengan izin.', 0, 'L');
$pdf->setFont('helvetica', 'B', 14);
$pdf->Ln(2);
$pdf->MultiCell(0, 6, 'Demi keamanan Anda, JANGAN MENYEBARLUASKAN nama akun dan kata sandi akun SSE Anda atau orang lain.', 0, 'L');
$pdf->Ln(2);
$pdf->Cell(0, 6, 'JIKA MENGALAMI KESULITAN, HUBUNGI PANITIA PEMILIHAN.', 0, 'L');
$pdf->setFont('helvetica', '', 11);
$pdf->Ln(8);

// Bagian 2: Panduan Pusminlihdu (jika dibutuhkan)
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Panduan Akses Pusminlihdu', 0, 1, 'C');
$pdf->Cell(0, 11, 'HANYA UNTUK AKSES PANITIA DAN PENGAWAS PEMILIHAN', 0, 1, 'C');

// Generate QR codes untuk Pusminlihdu
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 6, 'Pindai kode QR berikut untuk mengakses Pusminlihdu (khusus panitia dan pengawas):', 0, 'C');
$pdf->Ln(4);

// URL Pusminlihdu dari konfigurasi
$pspo_urls = $school_config['url_akses_esppo'][1]['url_pspo'] ?? [];
if (!empty($pspo_urls)) {
    $cell_width = 180 / min(count($pspo_urls), 3);
    $qr_size = min(40, $cell_width - 10);
    
    $y_start = $pdf->GetY();
    foreach ($pspo_urls as $index => $url) {
        if ($index > 0 && $index % 3 == 0) {
            $pdf->Ln($qr_size + 20);
            $y_start = $pdf->GetY();
        }
        
        $x = 15 + ($index % 3) * $cell_width;
        $pdf->SetXY($x, $y_start);
        
        // QR Code
        $style = array(
            'border' => false,
            'padding' => 2,
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => array(255, 255, 255)
        );
        $pdf->write2DBarcode($url . 'pusminlihdu/', 'QRCODE,H', $x + 7, $y_start, $qr_size, $qr_size, $style);
        
        // URL Text
        $pdf->SetXY($x, $y_start + $qr_size + 2);
        $pdf->SetFont('courier', '', 8);
        $pdf->MultiCell($cell_width - 5, 4, "URL " . ($index + 1) . ":\n" . $url . 'pusminlihdu/', 0, 'C');
    }
    $pdf->Ln($qr_size / 2 + 2.5);
}

// Petunjuk Singkat Pusminlihdu
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 9, 'Petunjuk Singkat Penggunaan Pusminlihdu:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 9, 'Pusminlihdu adalah pusat administrasi pemilihan yang digunakan oleh panitia dan pengawas pemilihan untuk mengelola proses pemilihan secara elektronik.', 0, 'L');
$pdf->MultiCell(0, 9, 'Karena fungsinya, Pusminlihdu berisi informasi dan pengaturan sensitif dan penting/kritis terkait pemilihan, termasuk data pemilih, kandidat, dan hasil pemilihan.
Data-data ini, terutama data pemilih dan hasil pemilihan, jika bocor dapat mengganggu proses pemilihan dan dapat menyebabkan pelanggaran privasi dan integritas pemilihan.', 0, 'L');
$pdf->MultiCell(0, 9, 'Karena hal ini, akses Pusminlihdu hanya diperuntukkan bagi panitia dan pengawas pemilihan, dan akun akses Pusminlihdu berbeda dengan akun SSE.', 0, 'L');
$pdf->MultiCell(0, 9, 'Ikuti instruksi akses dan penggunaan yang diberikan oleh tim teknis panitia pemilihan. 
Panduan dan Informasi lebih lanjut dapat ditemukan di bagian panduan penggunaan Pusminlihdu di Panduan Pengguna dan Administrasi e-SPPO.', 0, 'L');
$pdf->Ln(2);
$pdf->setFont('helvetica', 'B', 14);
$pdf->MultiCell(0, 6, 'SELAIN PETUGAS DAN PENGAWAS PEMILIHAN, DILARANG MENGAKSES PUSMINLIHDU TANPA IZIN! PELANGGARAN DAPAT DITINDAKLANJUTI DAN DAPAT DIHUKUM BERDASARKAN TATA TERTIB SEKOLAH.', 0, 'L');
$pdf->setFont('helvetica', 'B', 12);
$pdf->MultiCell(0, 6, 'Segala akses Pusminlihdu hanya diberikan kepada panitia dan pengawas pemilihan, serta pihak lain yang diizinkan.', 0, 'L');
$pdf->Ln(2);
$pdf->setFont('helvetica', 'B', 14);
$pdf->Cell(0, 6, 'JIKA MENGALAMI KESULITAN, HUBUNGI TIM TEKNIS PANITIA PEMILIHAN.', 0, 'L');

// -----------------------------------------------------------------------------
// 5. OUTPUT KE BROWSER
// -----------------------------------------------------------------------------

$filename = 'panduan_akses_esppo_' . time() . '.pdf';
$pdf_web_path = '../../assets/pdfs/laporan/' . $filename;
$pdf_server_path = realpath(__DIR__ . '/../../assets/pdfs/laporan') . DIRECTORY_SEPARATOR . $filename;

try {
    if (!is_writable(dirname($pdf_server_path))) {
        throw new Exception("Direktori PDF tidak dapat ditulis: " . dirname($pdf_server_path));
    }
    $pdf->Output($pdf_server_path, 'F');
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>TCPDF ERROR: " . $e->getMessage() . "</div>";
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pratayang Panduan Akses</h3>
    </div>
    <div class="card-body">
        <iframe src="<?= htmlspecialchars($pdf_web_path) ?>" height="800" width="100%" style="border:none;" allowfullscreen></iframe>
    </div>
    <div class="card-footer">
        <a href="<?= htmlspecialchars($pdf_web_path) ?>" class="btn btn-success" download>
            <i class="fas fa-file-download"></i> Unduh Panduan (PDF)
        </a>
    </div>
</div>
