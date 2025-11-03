<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Cetak DPT
 * pusminlihdu/pengelola/cetak_dpt.php
 * 
 * Halaman ini digunakan untuk mencetak Daftar Pemilih Tetap (DPT) dalam format PDF.
 * Laporan ini terdiri dari statistik umum DPT, rincian per konstituensi, serta
 * Daftar Pemilih Tetap itu sendiri, dikategorikan berdasarkan konstituensi, dan
 * dapat digunakan untuk keperluan administrasi pemilihan.
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

$db = get_db_connection();
$school_config = get_school_config();
if (!defined('ESPPO_VERSION')) {
    define('ESPPO_VERSION', '2.0.0');
}

// -----------------------------------------------------------------------------
// 2. KELAS KUSTOM PDF DENGAN TCPDF
// -----------------------------------------------------------------------------

class DPT_PDF extends TCPDF {
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
// 3. PENGAMBILAN & PEMROSESAN DATA
// -----------------------------------------------------------------------------

try {
    $pivot_data_raw = $db->query("SELECT k.tipe_konstituensi, p.jk_pemilih, COUNT(p.id_unik_pemilih) as total FROM data_konstituensi k LEFT JOIN data_pemilih p ON k.kode_konstituensi = p.kk_pemilih GROUP BY k.tipe_konstituensi, p.jk_pemilih")->fetch_all(MYSQLI_ASSOC);
    $detail_stats_raw = $db->query("SELECT p.kk_pemilih, k.nama_konstituensi, k.tipe_konstituensi, p.jk_pemilih, COUNT(p.id_unik_pemilih) as total FROM data_pemilih p JOIN data_konstituensi k ON p.kk_pemilih = k.kode_konstituensi GROUP BY p.kk_pemilih, k.nama_konstituensi, k.tipe_konstituensi, p.jk_pemilih ORDER BY k.kode_konstituensi")->fetch_all(MYSQLI_ASSOC);
    $voters_raw = $db->query("SELECT p.nomor_dpt_pemilih, p.nama_pemilih, p.jk_pemilih, p.nama_akun_pemilih, p.kk_pemilih, k.nama_konstituensi, k.tipe_konstituensi FROM data_pemilih p JOIN data_konstituensi k ON p.kk_pemilih = k.kode_konstituensi ORDER BY p.kk_pemilih, p.nomor_dpt_pemilih ASC")->fetch_all(MYSQLI_ASSOC);
    $election_data = $db->query("SELECT nama_pemilihan FROM data_pemilihan LIMIT 1")->fetch_assoc();

    $pivot = ['KELAS' => [], 'KEPEGAWAIAN' => [], 'LAINNYA' => []];
    foreach($pivot_data_raw as $row) { if ($row['tipe_konstituensi'] !== null) { $pivot[$row['tipe_konstituensi']][$row['jk_pemilih']] = $row['total']; } }
    $detail_stats = [];
    foreach($detail_stats_raw as $row) { $detail_stats[$row['kk_pemilih']]['data'] = ['nama' => $row['nama_konstituensi'], 'tipe' => $row['tipe_konstituensi']]; $detail_stats[$row['kk_pemilih']]['stats'][$row['jk_pemilih']] = $row['total']; }
    $voters_by_const = [];
    foreach($voters_raw as $voter) $voters_by_const[$voter['kk_pemilih']][] = $voter;

} catch (Exception $e) { echo "<div class='alert alert-danger'>Gagal mengambil data: " . $e->getMessage() . "</div>"; exit; }

// -----------------------------------------------------------------------------
// 4. PEMBUATAN DOKUMEN PDF
// -----------------------------------------------------------------------------

$pdf = new DPT_PDF('L', 'mm', 'A4');
$pdf->schoolConfig = $school_config;
$pdf->SetCreator('e-SPPO - Pusminlihdu');
$pdf->SetAuthor('Panitia Pemilihan OSIS' . ($school_config['nama_sekolah'] ? ' ' . $school_config['nama_sekolah'] : ''));
$pdf->SetTitle('Laporan Statistik Pemilih dan Daftar Pemilih Tetap (DPT)');
$pdf->SetSubject('Laporan DPT untuk ' . ($election_data['nama_pemilihan'] ?? 'Pemilihan OSIS'));
$pdf->SetKeywords('e-SPPO, Pusminlihdu, DPT, Daftar Pemilih Tetap, Pemilih, Pemilihan OSIS, ' . ($school_config['nama_sekolah'] ?? 'OSIS'));
$pdf->SetMargins(10, 42, 10);
$pdf->SetAutoPageBreak(TRUE, 5);

// Halaman 1: Statistik Umum
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN STATISTIK UMUM DAFTAR PEMILIH TETAP (DPT)', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, strtoupper($election_data['nama_pemilihan'] ?? 'PEMILIHAN OSIS'), 0, 1, 'C');
$pdf->Ln(5);
$header1 = ['Tipe Konstituensi', 'PRIA', 'WANITA', 'TIDAK DIKETAHUI', 'TOTAL'];
$w1 = [70, 52, 52, 52, 51];
$pdf->SetFillColor(224, 235, 255); $pdf->SetTextColor(0); $pdf->SetDrawColor(128, 128, 128); $pdf->SetLineWidth(0.2); $pdf->SetFont('helvetica', 'B', 10);
for($i = 0; $i < count($header1); $i++) $pdf->Cell($w1[$i], 7, $header1[$i], 1, 0, 'C', 1);
$pdf->Ln();
$pdf->SetFont('helvetica', '', 10); $fill = 0;
$totals = ['PRIA' => 0, 'WANITA' => 0, 'TIDAK_DIKETAHUI' => 0, 'GRAND' => 0];
foreach($pivot as $tipe => $data) {
    $row_total = ($data['PRIA'] ?? 0) + ($data['WANITA'] ?? 0) + ($data['TIDAK_DIKETAHUI'] ?? 0);
    $totals['PRIA'] += $data['PRIA'] ?? 0; $totals['WANITA'] += $data['WANITA'] ?? 0; $totals['TIDAK_DIKETAHUI'] += $data['TIDAK_DIKETAHUI'] ?? 0; $totals['GRAND'] += $row_total;
    $pdf->Cell($w1[0], 6, '   ' . $tipe, 'LR', 0, 'L', $fill);
    $pdf->Cell($w1[1], 6, number_format($data['PRIA'] ?? 0), 'LR', 0, 'C', $fill);
    $pdf->Cell($w1[2], 6, number_format($data['WANITA'] ?? 0), 'LR', 0, 'C', $fill);
    $pdf->Cell($w1[3], 6, number_format($data['TIDAK_DIKETAHUI'] ?? 0), 'LR', 0, 'C', $fill);
    $pdf->Cell($w1[4], 6, number_format($row_total), 'LR', 0, 'C', $fill);
    $pdf->Ln(); $fill = !$fill;
}
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($w1[0], 7, 'TOTAL', 'T', 0, 'C', 1);
$pdf->Cell($w1[1], 7, number_format($totals['PRIA']), 'T', 0, 'C', 1);
$pdf->Cell($w1[2], 7, number_format($totals['WANITA']), 'T', 0, 'C', 1);
$pdf->Cell($w1[3], 7, number_format($totals['TIDAK_DIKETAHUI']), 'T', 0, 'C', 1);
$pdf->Cell($w1[4], 7, number_format($totals['GRAND']), 'T', 1, 'C', 1);

// Halaman 2: Statistik Rinci
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN STATISTIK RINCI DPT PER KONSTITUENSI', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, strtoupper($election_data['nama_pemilihan'] ?? 'PEMILIHAN OSIS'), 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetX(22.5);
$header2 = ['No.', 'Kode', 'Tipe', 'Nama Konstituensi', 'Pria', 'Wanita', 'Tdk. Dik.', 'Total'];
$w2 = [15, 20, 40, 70, 25, 25, 30, 25];
$pdf->SetFillColor(224, 235, 255); $pdf->SetFont('helvetica', 'B', 10);
for($i = 0; $i < count($header2); $i++) $pdf->Cell($w2[$i], 7, $header2[$i], 1, 0, 'C', 1);
$pdf->Ln();
$pdf->SetX(22.5);
$pdf->SetFont('helvetica', '', 9); $fill = 0;
$no = 1; $total_detail = ['PRIA' => 0, 'WANITA' => 0, 'TIDAK_DIKETAHUI' => 0, 'GRAND' => 0];
foreach($detail_stats as $kode => $data) {
    if ($pdf->GetY() > ($pdf->getPageHeight() - 25)){
        $pdf->SetX(22.5);
        $pdf->Cell(array_sum($w2), 0, '', 'T'); // Tutup tabel lama
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 12); // Judul halaman lanjutan
        $pdf->Cell(0, 6, "LAPORAN STATISTIK RINCI DPT PER KONSTITUENSI (LANJUTAN)", 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetX(22.5);
        $pdf->SetFillColor(224, 235, 255); $pdf->SetFont('helvetica', 'B', 10); // Ulangi header tabel
        for($i = 0; $i < count($header2); $i++) $pdf->Cell($w2[$i], 7, $header2[$i], 1, 0, 'C', 1);
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 9);
    }
    $pdf->SetX(22.5);
    $pria = $data['stats']['PRIA'] ?? 0; $wanita = $data['stats']['WANITA'] ?? 0; $lain = $data['stats']['TIDAK_DIKETAHUI'] ?? 0; $total = $pria + $wanita + $lain;
    $pdf->Cell($w2[0], 6, $no++, 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[1], 6, $kode, 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[2], 6, '   ' . $data['data']['tipe'], 'LR', 0, 'L', $fill);
    $pdf->Cell($w2[3], 6, '   ' . $data['data']['nama'], 'LR', 0, 'L', $fill);
    $pdf->Cell($w2[4], 6, $pria, 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[5], 6, $wanita, 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[6], 6, $lain, 'LR', 0, 'C', $fill);
    $pdf->Cell($w2[7], 6, $total, 'LR', 0, 'C', $fill);
    $pdf->Ln(); $fill = !$fill;
    $total_detail['PRIA'] += $pria; $total_detail['WANITA'] += $wanita; $total_detail['TIDAK_DIKETAHUI'] += $lain; $total_detail['GRAND'] += $total;
}
$pdf->SetX(22.5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(array_sum(array_slice($w2, 0, 4)), 7, 'TOTAL', 'T', 0, 'C', 1);
$pdf->Cell($w2[4], 7, $total_detail['PRIA'], 'T', 0, 'C', 1);
$pdf->Cell($w2[5], 7, $total_detail['WANITA'], 'T', 0, 'C', 1);
$pdf->Cell($w2[6], 7, $total_detail['TIDAK_DIKETAHUI'], 'T', 0, 'C', 1);
$pdf->Cell($w2[7], 7, $total_detail['GRAND'], 'T', 1, 'C', 1);

// Halaman 3+: Daftar Pemilih per Konstituensi
foreach ($voters_by_const as $kode => $list) {
    $pdf->AddPage();
    $nama_konst = $list[0]['nama_konstituensi'] ?? 'N/A';
    $tipe_konst = $list[0]['tipe_konstituensi'] ?? 'N/A';
    $summary_counts = ['PRIA' => 0, 'WANITA' => 0, 'TIDAK_DIKETAHUI' => 0];
    foreach($list as $v) $summary_counts[$v['jk_pemilih']]++;
    $summary_text = sprintf('Jumlah Pemilih: %d (%d PRIA, %d WANITA, %d TIDAK DIKETAHUI)', count($list), $summary_counts['PRIA'], $summary_counts['WANITA'], $summary_counts['TIDAK_DIKETAHUI']);
    $npsn = $school_config['npsn_sekolah'] ?? '00000000';
    $barcode_val = "{$npsn}-{$kode}";

    // Judul & Barcode
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'DAFTAR PEMILIH TETAP - ' . $election_data['nama_pemilihan'] ?? 'PEMILIHAN OSIS' , 0, 1, 'C');
    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, "Konstituensi: {$nama_konst} ({$tipe_konst})", 0, 1, 'C');
    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $summary_text, 0, 1, 'C');
    $style = ['border' => false, 'padding' => 0, 'text' => true, 'font' => 'helvetica', 'fontsize' => 8, 'stretchtext' => 4];
    $pdf->write1DBarcode($barcode_val, 'C39', 205, $pdf->GetY() - 18.5, '', 12, 0.4, $style, 'N');
    $pdf->Ln(8);

    // Header Tabel DPT
    $header3 = ['No', 'No. DPT', 'Nama Lengkap Pemilih', 'JK', 'Nama Akun SSE'];
    $w3 = [15, 30, 130, 15, 87];
    $pdf->SetFillColor(224, 235, 255); $pdf->SetFont('helvetica', 'B', 10);
    for($i = 0; $i < count($header3); $i++) $pdf->Cell($w3[$i], 7, $header3[$i], 1, 0, 'C', 1);
    $pdf->Ln();

    // Isi Tabel DPT
    $pdf->SetFont('helvetica', '', 9); $fill = 0; $no_voter = 1;
    foreach($list as $voter) {
        if ($pdf->GetY() > ($pdf->getPageHeight() - 25)) { // Cek jika perlu page break
            $pdf->Cell(array_sum($w3), 0, '', 'T'); // Tutup tabel lama
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 12); // Judul halaman lanjutan
            $pdf->Cell(0, 6, "Lanjutan Daftar Pemilih - Konstituensi: {$nama_konst}", 0, 1, 'C');
            $pdf->Ln(4);
            $pdf->SetFillColor(224, 235, 255); $pdf->SetFont('helvetica', 'B', 10); // Ulangi header tabel
            for($i = 0; $i < count($header3); $i++) $pdf->Cell($w3[$i], 7, $header3[$i], 1, 0, 'C', 1);
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 9);
        }
        $pdf->Cell($w3[0], 6, $no_voter++, 'LR', 0, 'C', $fill);
        $pdf->Cell($w3[1], 6, $voter['nomor_dpt_pemilih'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w3[2], 6, '   ' . $voter['nama_pemilih'], 'LR', 0, 'L', $fill);
        $pdf->Cell($w3[3], 6, ($voter['jk_pemilih'] == 'PRIA' ? 'L' : ($voter['jk_pemilih'] == 'WANITA' ? 'P' : '-')), 'LR', 0, 'C', $fill);
        $pdf->Cell($w3[4], 6, '   ' . $voter['nama_akun_pemilih'], 'LR', 0, 'L', $fill);
        $pdf->Ln(); $fill = !$fill;
    }
    $pdf->Cell(array_sum($w3), 0, '', 'T'); // Tutup tabel terakhir
}

// -----------------------------------------------------------------------------
// 5. OUTPUT KE BROWSER
// -----------------------------------------------------------------------------

$filename = 'laporan_dpt_' . time() . '.pdf';
$pdf_web_path = '../../assets/pdfs/laporan/' . $filename; 
$pdf_server_path = realpath(__DIR__ . '/../../assets/pdfs/laporan') . DIRECTORY_SEPARATOR . $filename;

try {
    if (!is_writable(dirname($pdf_server_path))) {
        throw new Exception("Direktori PDF tidak dapat ditulis: " . dirname($pdf_server_path));
    }
    $pdf->Output($pdf_server_path, 'F');
} catch (Exception $e) { echo "<div class='alert alert-danger'>TCPDF ERROR: " . $e->getMessage() . "</div>"; exit; }
?>

<div class="card">
    <div class="card-header"><h3 class="card-title">Pratayang Laporan DPT</h3></div>
    <div class="card-body">
        <iframe src="<?= htmlspecialchars($pdf_web_path) ?>" height="800" width="100%" style="border:none;" allowfullscreen></iframe>
    </div>
    <div class="card-footer">
        <a href="<?= htmlspecialchars($pdf_web_path) ?>" class="btn btn-success" download>
            <i class="fas fa-file-download"></i> Unduh Laporan (PDF)
        </a>
    </div>
</div>
