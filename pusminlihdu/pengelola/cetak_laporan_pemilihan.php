<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola, Cetak Laporan Pemilihan
 * pusminlihdu/pengelola/cetak_laporan_pemilihan.php
 * 
 * Halaman ini digunakan untuk mencetak laporan pelaksanaan dan hasil pemilihan dalam format PDF.
 * Laporan ini mencakup informasi tentang pelaksanaan pemungutan suara, hasil perolehan suara,
 * dan tanda tangan pejabat terkait.
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

class ELECTION_PDF extends TCPDF {
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

    // Fungsi untuk menambahkan halaman tanda tangan
    public function addSignaturePages($committee_data, $candidates, $supervisor, $principal) {
        $pageWidth = $this->getPageWidth() - $this->original_lMargin - $this->original_rMargin;
        // Halaman Tanda Tangan Panitia
        $this->AddPage();
        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 10, 'Ditandatangani di ' . $this->schoolConfig['alamat_sekolah'][1]['kecamatan'] . ', pada hari ' . formatIndonesianDate(date('Y-m-d'), 'short'), 0, 1, 'R');
        $this->Ln(5);

        // Ruang tanda tangan panitia (3 orang)
        $positions = ['Ketua Panitia', 'Sekretaris', 'Kepala Tim Teknis'];
        $col_width = $pageWidth / 3;

        // Header tanda tangan panitia
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Panitia Pemilihan', 0, 1, 'C');
        $this->SetFont('helvetica', '', 11);
        $this->Ln(5);
        
        foreach($positions as $i => $position) {
            $x = 15 + ($i * 90);
            $this->SetX($x);
            $this->Cell($col_width, 6, $position, 0, 1, 'C');
            $this->SetX($x);
            $this->Cell($col_width, 35, '', 0, 1, 'C'); // Ruang tanda tangan
            $this->SetX($x);
            $this->Cell($col_width, 6, '(..........................................................)', 0, 1, 'C');
            $this->Ln(4);
            $this->SetX($x);
            $this->Cell($col_width, 6, 'NIS. ................................', 0, 1, 'C');
            $this->SetX($x);
            $this->SetY($this->GetY() - (6 + 35 + 6 + 4 + 6));
        }

        // Halaman Tanda Tangan Kandidat
        $this->AddPage();
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Kandidat/Saksi Kandidat', 0, 1, 'C');
        $this->SetFont('helvetica', '', 11);
        $this->Ln(5);

        // Tanda tangan kandidat (3 per baris)
        foreach(array_chunk($candidates, 3) as $pair) {
            foreach($pair as $i => $candidate) {
                $x = 15 + ($i * 90);
                $this->SetX($x);
                $this->Cell($col_width, 6, 'Kandidat Nomor Urut ' . $candidate['no_urut_kandidat'], 0, 1, 'C');
                $this->SetX($x);
                $nama = $candidate['nama_calon_1'];
                if (!empty($candidate['nama_calon_2'])) {
                    $nama .= ' & ' . $candidate['nama_calon_2'];
                }
                $this->Cell($col_width, 6, $nama, 0, 1, 'C');
                $this->Cell($col_width, 20, '', 0, 1, 'C');
                $this->SetY($this->GetY() - (6 + 6 + 20));
            }
            $this->SetY($this->GetY() + 50);
            $this->Ln(10);
        }

        // Halaman Tanda Tangan Mengetahui
        $this->AddPage();
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'LEMBAR PENGESAHAN', 0, 1, 'C');
        $this->Ln(10);

        // Kolom tanda tangan pengesahan
        $this->SetFont('helvetica', '', 11);
        $col_width = $pageWidth / 2;
        
        // Pembina/Pengawas
        $this->SetY($this->GetY() + 5 + 4);
        $this->SetX(30);
        $this->Cell($col_width, 6, $supervisor['jabatan_pejabat_sekolah'], 0, 1, 'C');
        $this->SetX(30);
        $this->Cell($col_width, 35, '', 0, 1, 'C'); // Ruang tanda tangan
        $this->SetX(30);
        $this->Cell($col_width, 6, $supervisor['nama_pejabat_sekolah'], 0, 1, 'C');
        if ($supervisor['nip_pejabat_sekolah']) {
            $this->SetX(30);
            $this->Cell($col_width, 6, 'NIP. ' . $supervisor['nip_pejabat_sekolah'], 0, 1, 'C');
            $this->SetY($this->GetY() - 6 - 5 - (6 + 35 + 6 + 6));
        } else {
            $this->SetY($this->GetY() - 6 - 5 - (6 + 35 + 6));
        }

        // Kepala Sekolah
        $this->SetX(140);
        $this->Cell($col_width, 6, 'Mengetahui dan Mengesahkan,', 0, 1, 'C');
        $this->Ln(5);
        $this->SetX(140);
        $this->Cell($col_width, 6, 'Kepala ' . $this->schoolConfig['nama_sekolah'], 0, 1, 'C');
        $this->SetX(140);
        $this->Cell($col_width, 35, '', 0, 1, 'C'); // Ruang tanda tangan
        $this->SetX(140);
        $this->Cell($col_width, 6, $principal['nama'], 0, 1, 'C');
        if ($principal['nip']) {
            $this->SetX(140);
            $this->Cell($col_width, 6, 'NIP. ' . $principal['nip'], 0, 1, 'C');
        }
    }
}

// Fungsi untuk format tanggal Indonesia
function formatIndonesianDate($date, $format = 'full') {
    $fmt = new IntlDateFormatter(
        'id_ID',
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'Asia/Makassar',
        IntlDateFormatter::GREGORIAN
    );

    $timestamp = strtotime($date);
    
    if ($format === 'full') {
        // For full format, return array of components
        $fmt->setPattern('EEEE');
        $day = $fmt->format($timestamp);
        
        $fmt->setPattern('d');
        $date = $fmt->format($timestamp);
        
        $fmt->setPattern('MMMM');
        $month = $fmt->format($timestamp);
        
        $fmt->setPattern('Y');
        $year = $fmt->format($timestamp);
        
        return [$day, $date, $month, $year];
    } else {
        // For short format, return formatted string
        $fmt->setPattern('EEEE, d MMMM Y');
        return $fmt->format($timestamp);
    }
}

// -----------------------------------------------------------------------------
// 3. PENGAMBILAN DATA
// -----------------------------------------------------------------------------

try {
    // Data pemilihan
    $election = $db->query("SELECT * FROM data_pemilihan LIMIT 1")->fetch_assoc();
    
    // Statistik pemilih
    $voter_stats = $db->query("
        SELECT 
            COUNT(*) as total_voters,
            SUM(CASE WHEN jk_pemilih = 'PRIA' THEN 1 ELSE 0 END) as male_voters,
            SUM(CASE WHEN jk_pemilih = 'WANITA' THEN 1 ELSE 0 END) as female_voters,
            SUM(CASE WHEN status_pemilih = 'SUDAH_MEMILIH' THEN 1 ELSE 0 END) as voted
        FROM data_pemilih")->fetch_assoc();

    // Data kandidat dan perolehan suara
    $vote_results = $db->query("
        SELECT 
            k.*,
            COUNT(s.kode_id_suara) as total_votes,
            SUM(CASE WHEN s.jk_pemilih = 'PRIA' THEN 1 ELSE 0 END) as male_votes,
            SUM(CASE WHEN s.jk_pemilih = 'WANITA' THEN 1 ELSE 0 END) as female_votes
        FROM data_kandidat k
        LEFT JOIN data_suara s ON k.no_urut_kandidat = s.no_urut_kandidat
        GROUP BY k.no_urut_kandidat
        ORDER BY k.no_urut_kandidat")->fetch_all(MYSQLI_ASSOC);

    // Data perolehan per konstituensi
    $constituency_results = $db->query("
        SELECT 
            k.kode_konstituensi,
            k.nama_konstituensi,
            k.tipe_konstituensi,
            dk.no_urut_kandidat,
            COUNT(s.kode_id_suara) as votes,
            s.jk_pemilih
        FROM data_konstituensi k
        CROSS JOIN data_kandidat dk
        LEFT JOIN data_suara s ON s.no_urut_kandidat = dk.no_urut_kandidat 
            AND s.kk_pemilih = k.kode_konstituensi
        WHERE k.status_konstituensi = 'DIAKTIFKAN'
        GROUP BY k.kode_konstituensi, dk.no_urut_kandidat, s.jk_pemilih
        ORDER BY k.kode_konstituensi, dk.no_urut_kandidat")->fetch_all(MYSQLI_ASSOC);

    // Data pejabat untuk tanda tangan
    $supervisor = $db->query("
        SELECT * FROM data_pejabat_sekolah 
        WHERE fungsi_pejabat_sekolah IN ('WAKASEKSIS', 'WAKASEK', 'PEMBINA') 
        ORDER BY FIELD(fungsi_pejabat_sekolah, 'WAKASEKSIS', 'WAKASEK', 'PEMBINA') 
        LIMIT 1")->fetch_assoc();

    // Data kepala sekolah dari konfigurasi
    $principal = [
        'nama' => $school_config['kepala_sekolah'][0]['nama'],
        'nip' => $school_config['kepala_sekolah'][1]['nip_kepsek']
    ];

} catch (Exception $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}

// -----------------------------------------------------------------------------
// 4. PEMBUATAN DOKUMEN PDF
// -----------------------------------------------------------------------------

$pdf = new ELECTION_PDF('L', 'mm', 'A4');
$pdf->schoolConfig = $school_config;
$pdf->SetCreator('e-SPPO - Pusminlihdu');
$pdf->SetAuthor('Panitia Pemilihan OSIS' . ($school_config['nama_sekolah'] ? ' ' . $school_config['nama_sekolah'] : ''));
$pdf->SetTitle('Laporan Pelaksanaan dan Hasil Pemilihan');
$pdf->setSubject('Laporan Pelaksanaan dan Hasil Pemilihan ' . ($election['nama_pemilihan'] ?? 'Pemilihan OSIS') . ' di ' . ($school_config['nama_sekolah'] ?? 'Sekolah'));
$pdf->setKeywords('e-SPPO, Pusminlihdu, Laporan Pemilihan, Hasil Pemilihan, Pemilihan OSIS, ' . ($school_config['nama_sekolah'] ?? 'OSIS'));
$pdf->SetMargins(15, 42.5, 15);

// Halaman 1: Laporan Pelaksanaan
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'BERITA ACARA LAPORAN PELAKSANAAN PEMUNGUTAN DAN PENGHITUNGAN SUARA', 0, 1, 'C');
$pdf->Cell(0, 10, strtoupper($election['nama_pemilihan']), 0, 1, 'C');
$pdf->Ln(6);

$pdf->SetFont('helvetica', '', 10.25);

// Get current date parts
list($today_day, $today_date, $today_month, $today_year) = formatIndonesianDate(date('Y-m-d'));

// Calculate attendance percentages
$percent_men = ($stats['total_voters'] > 0) ? ($voter_stats['male_voters'] / $stats['total_voters'] * 100) : 0;
$percent_women = ($stats['total_voters'] > 0) ? ($voter_stats['female_voters'] / $stats['total_voters'] * 100) : 0;
$percent_attend = ($stats['total_voters'] > 0) ? ($voter_stats['voted'] / $stats['total_voters'] * 100) : 0;

$percent_attend_men = ($voter_stats['male_voters'] > 0) ? 
    ($voter_stats['male_votes'] / $voter_stats['male_voters'] * 100) : 0;
$percent_attend_women = ($voter_stats['female_voters'] > 0) ? 
    ($voter_stats['female_votes'] / $voter_stats['female_voters'] * 100) : 0;

// Generate report text
$report_text = sprintf(
    "Pada hari ini, %s, tanggal %s bulan %s tahun %s, telah selesai dilaksanakan kegiatan pemungutan dan penghitungan suara secara elektronik dalam rangka pelaksanaan %s Masa Bakti %s di %s.\n\n",
    $today_day, $today_date, $today_month, $today_year,
    $election['nama_pemilihan'],
    $election['masa_bakti'],
    $school_config['nama_sekolah']
);

// Add execution period
if ($election['tanggal_mulai'] != $election['tanggal_selesai']) {
    list($start_day, $start_date, $start_month, $start_year) = formatIndonesianDate($election['tanggal_mulai']);
    list($end_day, $end_date, $end_month, $end_year) = formatIndonesianDate($election['tanggal_selesai']);
    
    $date_diff = ceil((strtotime($election['tanggal_selesai']) - strtotime($election['tanggal_mulai'])) / (60 * 60 * 24));
    
    $report_text .= sprintf(
        "Pelaksanaan kegiatan pemungutan suara berlangsung selama %d hari, dimulai pada hari %s tanggal %s sampai dengan hari %s tanggal %s",
        $date_diff, $start_day, "$start_date $start_month $start_year",
        $end_day, "$end_date $end_month $end_year"
    );
} else {
    list($start_day, $start_date, $start_month, $start_year) = formatIndonesianDate($election['tanggal_mulai']);
    $report_text .= sprintf(
        "Pelaksanaan kegiatan pemungutan suara berlangsung pada hari %s tanggal %s",
        $start_day, "$start_date $start_month $start_year"
    );
}

$report_text .= sprintf(
    ", dengan mengelar sebanyak %d satuan Tempat Pemungutan Suara (TPS) dan menyediakan sebanyak %d unit Perangkat Akses Surat Suara Elektronik (PASSE).\n\n",
    $election['jumlah_tps'],
    $election['jumlah_passe']
);

// Add candidate information
$candidate_text = ($election['tipe_peserta_pemilihan'] === 'BERPASANGAN') ?
    sprintf("Pemilihan ini diikuti oleh sebanyak %d pasang calon", count($vote_results)) :
    sprintf("Pemilihan ini diikuti oleh sebanyak %d calon", count($vote_results));

$report_text .= $candidate_text . sprintf(
    " dan dengan jumlah pemilih terdaftar sebanyak %d orang, yang terdiri dari %d pemilih pria atau %.1f%% dari jumlah pemilih dan %d pemilih perempuan atau %.1f%% dari jumlah pemilih.\n\n",
    $stats['total_voters'],
    $voter_stats['male_voters'],
    $percent_men,
    $voter_stats['female_voters'],
    $percent_women
);

$report_text .= sprintf(
    "Pada saat waktu penutupan kegiatan pemungutan suara, sebanyak %d orang pemilih atau sebanyak %.1f%% telah hadir dan memberikan suaranya, dengan rincian %d pemilih laki-laki atau sebesar %.1f%% dari seluruh pemilih laki-laki dan %d pemilih perempuan atau %.1f%% dari seluruh pemilih perempuan. Dalam pelaksanaan pemungutan suara, tidak terjadi/telah terjadi *) kejadian khusus, sesuai yang tercatat oleh Panitia Pemilihan.\n\n",
    $voter_stats['voted'],
    $percent_attend,
    $voter_stats['male_votes'],
    $percent_attend_men,
    $voter_stats['female_votes'],
    $percent_attend_women
);

$report_text .= sprintf(
    "Pelaksanaan acara penghitungan suara secara elektronik disaksikan oleh %s *), serta diawasi oleh %s.\n\n",
    ($election['tipe_peserta_pemilihan'] === 'BERPASANGAN' ? 
        "Pasangan Calon/yang mewakili/Saksi Siswa" : 
        "Calon/yang mewakili/Saksi Siswa"),
    "Guru Pembina/Pengawas Pelaksanaan Pemilihan"
);

$report_text .= sprintf(
    "Demikian Berita Acara Laporan Pelaksanaan Kegiatan Pemungutan dan Penghitungan Suara ini dan dibuat sebanyak             (                 ) rangkap dan masing-masing ditandatangani oleh Panitia Pemilihan, %s *), dan Guru Pembina/Pengawas Pelaksanaan Pemilihan, serta Kepala Sekolah %s.",
    ($election['tipe_peserta_pemilihan'] === 'BERPASANGAN' ? 
        "Pasangan Calon/yang mewakili/Saksi Siswa" : 
        "Calon/yang mewakili/Saksi Siswa"),
    $school_config['nama_sekolah']
);

$pdf->MultiCell(0, 6, $report_text, 0, 'L');
$pdf->SetY(-24);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 3, '*): Coret yang tidak perlu', 0, 1, 'L');

// Halaman 2: Hasil Keseluruhan
$pdf->AddPage('L');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'HASIL PEROLEHAN SUARA KESELURUHAN', 0, 1, 'C');
$pdf->Ln(5);

// Statistik DPT dan Partisipasi
$pdf->SetFont('helvetica', '', 10);
$stats_table = [
    ['Daftar Pemilih Tetap (DPT)', $voter_stats['male_voters'], $voter_stats['female_voters'], $voter_stats['total_voters']],
    ['Pemilih yang Menggunakan Hak Pilih', $voter_stats['male_votes'], $voter_stats['female_votes'], $voter_stats['voted']],
    ['Tingkat Partisipasi (%)', 
        number_format($percent_attend_men, 1),
        number_format($percent_attend_women, 1),
        number_format($percent_attend, 1)
    ]
];

$w = [80, 50, 50, 50];
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w[0], 7, 'Uraian', 1, 0, 'C', true);
$pdf->Cell($w[1], 7, 'Laki-laki', 1, 0, 'C', true);
$pdf->Cell($w[2], 7, 'Perempuan', 1, 0, 'C', true);
$pdf->Cell($w[3], 7, 'Total', 1, 1, 'C', true);

foreach($stats_table as $row) {
    $pdf->Cell($w[0], 7, $row[0], 1, 0, 'L');
    $pdf->Cell($w[1], 7, $row[1], 1, 0, 'C');
    $pdf->Cell($w[2], 7, $row[2], 1, 0, 'C');
    $pdf->Cell($w[3], 7, $row[3], 1, 1, 'C');
}
$pdf->Ln(10);

// Tabel Perolehan Suara
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Rincian Perolehan Suara per Kandidat:', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$header = ['No. Urut', 'Nama Calon', 'Jumlah Suara', 'Persentase'];
$w = [25, 140, 35, 30];

$pdf->SetFillColor(220, 220, 220);
foreach($header as $i => $h) {
    $pdf->Cell($w[$i], 7, $h, 1, 0, 'C', true);
}
$pdf->Ln();

foreach($vote_results as $row) {
    $percentage = ($voter_stats['voted'] > 0) ? 
        ($row['total_votes'] / $voter_stats['voted'] * 100) : 0;
    
    $pdf->Cell($w[0], 7, $row['no_urut_kandidat'], 1, 0, 'C');
    $nama_kandidat = $election['tipe_peserta_pemilihan'] === 'BERPASANGAN' ?
        $row['nama_calon_1'] . ' & ' . $row['nama_calon_2'] :
        $row['nama_calon_1'];
    $pdf->Cell($w[1], 7, $nama_kandidat, 1, 0, 'L');
    $pdf->Cell($w[2], 7, number_format($row['total_votes']), 1, 0, 'C');
    $pdf->Cell($w[3], 7, number_format($percentage, 1) . '%', 1, 0, 'C');
    $pdf->Ln();
}

// Halaman 3: Hasil per Konstituensi
$pdf->AddPage('L');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'RINCIAN PEROLEHAN SUARA PER KONSTITUENSI', 0, 1, 'C');
$pdf->Ln(5);

// Reorganize constituency data
$const_data = [];
foreach($constituency_results as $result) {
    $key = $result['kode_konstituensi'];
    if (!isset($const_data[$key])) {
        $const_data[$key] = [
            'nama' => $result['nama_konstituensi'],
            'tipe' => $result['tipe_konstituensi'],
            'results' => []
        ];
    }
    if (!isset($const_data[$key]['results'][$result['no_urut_kandidat']])) {
        $const_data[$key]['results'][$result['no_urut_kandidat']] = [
            'PRIA' => 0, 'WANITA' => 0
        ];
    }
    $const_data[$key]['results'][$result['no_urut_kandidat']][$result['jk_pemilih']] = $result['votes'];
}

// Print constituency results
$pdf->SetFont('helvetica', '', 10);
foreach($const_data as $kode => $const) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, $const['nama'] . ' - ' . $const['tipe'], 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    
    // Header
    $w = [25, 100, 35, 35, 35];
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell($w[0], 7, 'No. Urut', 1, 0, 'C', true);
    $pdf->Cell($w[1], 7, 'Nama Kandidat', 1, 0, 'C', true);
    $pdf->Cell($w[2], 7, 'Suara Pria', 1, 0, 'C', true);
    $pdf->Cell($w[3], 7, 'Suara Wanita', 1, 0, 'C', true);
    $pdf->Cell($w[4], 7, 'Total', 1, 1, 'C', true);
    
    foreach($const['results'] as $no_urut => $votes) {
        $candidate = array_filter($vote_results, function($c) use ($no_urut) {
            return $c['no_urut_kandidat'] == $no_urut;
        });
        $candidate = reset($candidate);
        
        $total = $votes['PRIA'] + $votes['WANITA'];
        
        $pdf->Cell($w[0], 7, $no_urut, 1, 0, 'C');
        $nama_kandidat = $election['tipe_peserta_pemilihan'] === 'BERPASANGAN' ?
            $candidate['nama_calon_1'] . ' & ' . $candidate['nama_calon_2'] :
            $candidate['nama_calon_1'];
        $pdf->Cell($w[1], 7, $nama_kandidat, 1, 0, 'L');
        $pdf->Cell($w[2], 7, $votes['PRIA'], 1, 0, 'C');
        $pdf->Cell($w[3], 7, $votes['WANITA'], 1, 0, 'C');
        $pdf->Cell($w[4], 7, $total, 1, 1, 'C');
    }
    $pdf->Ln(7);
    
    if ($pdf->GetY() > 150 && $kode !== array_key_last($const_data)) {
        $pdf->AddPage('L');
    }
}

// Halaman 4: Tanda Tangan Pejabat
$committee = [
    ['nama' => '', 'jabatan' => 'Ketua Panitia'],
    ['nama' => '', 'jabatan' => 'Sekretaris'],
    ['nama' => '', 'jabatan' => 'Kepala Tim Teknis']
];

$pdf->addSignaturePages($committee, $vote_results, $supervisor, $principal);

// -----------------------------------------------------------------------------
// 5. OUTPUT KE BROWSER
// -----------------------------------------------------------------------------
$filename = 'laporan_pemilihan_' . time() . '.pdf';
$pdf_web_path = '../../assets/pdfs/laporan/' . $filename;
$pdf_server_path = realpath(__DIR__ . '/../../assets/pdfs/laporan') . DIRECTORY_SEPARATOR . $filename;

try {
    if (!is_writable(dirname($pdf_server_path))) {
        throw new Exception("Direktori PDF tidak dapat ditulis: " . dirname($pdf_server_path));
    }
    $pdf->Output($pdf_server_path, 'F');
} catch (Exception $e) {
    die("<div class='alert alert-danger'>Gagal membuat PDF: " . $e->getMessage() . "</div>");
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pratayang Laporan Pelaksanaan dan Hasil Pemilihan</h3>
    </div>
    <div class="card-body">
        <iframe src="<?= htmlspecialchars($pdf_web_path) ?>" height="800" width="100%" style="border:none;" allowfullscreen></iframe>
    </div>
    <div class="card-footer">
        <a href="<?= htmlspecialchars($pdf_web_path) ?>" class="btn btn-success" download>
            <i class="fas fa-file-download"></i> Unduh Laporan (PDF)
        </a>
    </div>
</div>
