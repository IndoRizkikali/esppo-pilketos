-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 04 Agu 2025 pada 13.43
-- Versi server: 10.7.3-MariaDB
-- Versi PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sppoe_development`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `akun_administrasi`
--

CREATE TABLE `akun_administrasi` (
  `id_unik_akun_admin` varchar(16) NOT NULL COMMENT 'Kode ID Unik Akun Administrasi',
  `nama_akun_admin` varchar(20) NOT NULL COMMENT 'Nama Akun (Username) Administrasi',
  `status_akun_admin` set('DIAKTIFKAN','DINONAKTIFKAN') NOT NULL COMMENT 'Status Aktivasi Akun Administrasi',
  `tingkat_akses_akun` set('PENGELOLA','PENGAWAS') NOT NULL COMMENT 'Tingkatan Akses (Access Control Level) Akun Administrasi',
  `kata_sandi_akun_admin` varchar(255) NOT NULL COMMENT 'Kata Sandi Akun Administrasi (dalam bentuk Hash)',
  `waktu_masuk_terakhir` timestamp NULL DEFAULT NULL COMMENT 'Waktu Terakhir Masuk (Login) ke sistem Pusminlihdu eSPPO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `akun_administrasi`
--

INSERT INTO `akun_administrasi` (`id_unik_akun_admin`, `nama_akun_admin`, `status_akun_admin`, `tingkat_akses_akun`, `kata_sandi_akun_admin`, `waktu_masuk_terakhir`) VALUES
('Dt0oG7d6bhc=', 'SYSADMIN', 'DIAKTIFKAN', 'PENGELOLA', '$2y$10$nHj2JYxS5.VltoJQbHNcf.ZGP/qy/0AYw37/4tiewVVUu.GAwzsR6', NULL),
('QWoePE8UwGY=', 'SYSVIEWER', 'DIAKTIFKAN', 'PENGAWAS', '$2y$10$ZbtZgTVSss0FupSkROsu..6tk8ahQEOW0Va1DCFbwsXJarq5NeLti', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_kandidat`
--

CREATE TABLE `data_kandidat` (
  `id_unik_kandidat` varchar(16) NOT NULL COMMENT 'Kode ID Unik milik Kandidat',
  `no_urut_kandidat` decimal(2,0) NOT NULL COMMENT 'Nomor Urut Kandidat',
  `nama_calon_1` varchar(110) NOT NULL COMMENT 'Nama Calon Utama/Calon Ketua OSIS/MPK',
  `nama_calon_2` varchar(110) DEFAULT NULL COMMENT 'Nama Calon Tambahan/Calon Wakil Ketua OSIS/MPK',
  `jk_calon_1` set('PRIA','WANITA','TIDAK_DIKETAHUI') NOT NULL COMMENT 'Jenis Kelamin Calon Utama/Calon Ketua OSIS/MPK',
  `jk_calon_2` set('PRIA','WANITA','TIDAK_DIKETAHUI') DEFAULT NULL COMMENT 'Jenis Kelamin Calon Tambahan/Calon Wakil Ketua OSIS/MPK',
  `kk_calon_1` decimal(2,0) NOT NULL COMMENT 'Kode Konstituensi Asal Calon Utama/Calon Ketua OSIS/MPK',
  `kk_calon_2` decimal(2,0) DEFAULT NULL COMMENT 'Kode Konstituensi Asal Calon Tambahan/Calon Wakil Ketua OSIS/MPK',
  `visi_kandidat` text DEFAULT NULL COMMENT 'Teks Visi Kandidat',
  `misi_kandidat` text DEFAULT NULL COMMENT 'Teks Misi Kandidat',
  `foto_kandidat` varchar(110) DEFAULT NULL COMMENT 'Nama Berkas Foto Resmi Kandidat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_kehadiran`
--

CREATE TABLE `data_kehadiran` (
  `urutan_kehadiran` int(7) NOT NULL COMMENT 'Urutan Kehadiran Pemilih (dengan masuk ke SSE)',
  `stempel_waktu_kehadiran` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Stempel Waktu Kehadiran Pemilih (masuk ke SSE)',
  `id_unik_pemilih` varchar(28) NOT NULL COMMENT 'Kode ID Unik Pemilih yang Hadir',
  `jk_pemilih` set('PRIA','WANITA','TIDAK_DIKETAHUI') NOT NULL COMMENT 'Jenis Kelamin Pemilih yang Hadir',
  `kk_pemilih` decimal(2,0) NOT NULL COMMENT 'Kode Konstituensi Pemilih yang Hadir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_konstituensi`
--

CREATE TABLE `data_konstituensi` (
  `kode_konstituensi` decimal(2,0) NOT NULL COMMENT 'Kode Numerik untuk Konstituensi Pemilih',
  `nama_konstituensi` varchar(20) NOT NULL COMMENT 'Nama Konstituensi Pemilih',
  `tipe_konstituensi` set('KELAS','KEPEGAWAIAN','LAINNYA') NOT NULL COMMENT 'Tipe Konstituensi Pemilih',
  `status_konstituensi` set('DIAKTIFKAN','DINONAKTIFKAN') NOT NULL COMMENT 'Status Konstituensi Pemilih'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `data_konstituensi`
--

INSERT INTO `data_konstituensi` (`kode_konstituensi`, `nama_konstituensi`, `tipe_konstituensi`, `status_konstituensi`) VALUES
(11, 'X-A', 'KELAS', 'DIAKTIFKAN'),
(12, 'X-B', 'KELAS', 'DIAKTIFKAN'),
(13, 'X-C', 'KELAS', 'DIAKTIFKAN'),
(14, 'X-D', 'KELAS', 'DIAKTIFKAN'),
(15, 'X-E', 'KELAS', 'DIAKTIFKAN'),
(16, 'X-F', 'KELAS', 'DIAKTIFKAN'),
(17, 'X-G', 'KELAS', 'DIAKTIFKAN'),
(18, 'X-H', 'KELAS', 'DIAKTIFKAN'),
(21, 'XI-A', 'KELAS', 'DIAKTIFKAN'),
(22, 'XI-B', 'KELAS', 'DIAKTIFKAN'),
(23, 'XI-C', 'KELAS', 'DIAKTIFKAN'),
(24, 'XI-D', 'KELAS', 'DIAKTIFKAN'),
(25, 'XI-E', 'KELAS', 'DIAKTIFKAN'),
(26, 'XI-F', 'KELAS', 'DIAKTIFKAN'),
(27, 'XI-G', 'KELAS', 'DIAKTIFKAN'),
(28, 'XI-H', 'KELAS', 'DIAKTIFKAN'),
(31, 'XII-A', 'KELAS', 'DIAKTIFKAN'),
(32, 'XII-B', 'KELAS', 'DIAKTIFKAN'),
(33, 'XII-C', 'KELAS', 'DIAKTIFKAN'),
(34, 'XII-D', 'KELAS', 'DIAKTIFKAN'),
(35, 'XII-E', 'KELAS', 'DIAKTIFKAN'),
(36, 'XII-F', 'KELAS', 'DIAKTIFKAN'),
(37, 'XII-G', 'KELAS', 'DIAKTIFKAN'),
(38, 'XII-H', 'KELAS', 'DIAKTIFKAN'),
(41, 'GURU-TENDIK', 'KEPEGAWAIAN', 'DIAKTIFKAN'),
(99, 'TESTING', 'LAINNYA', 'DIAKTIFKAN');

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_pejabat_sekolah`
--

CREATE TABLE `data_pejabat_sekolah` (
  `id_unik_pejabat_sekolah` varchar(16) NOT NULL COMMENT 'Kode ID Unik Pejabat Satuan Pendidikan (non Kepala Sekolah) yang Berwenang',
  `nama_pejabat_sekolah` varchar(110) NOT NULL COMMENT 'Nama Pejabat Satuan Pendidikan (non Kepala Sekolah) yang Berwenang',
  `nip_pejabat_sekolah` decimal(18,0) DEFAULT NULL COMMENT 'Nomor Induk Pegawai Pejabat Satuan Pendidikan (non Kepala Sekolah) yang Berwenang (hanya untuk yang berstatus ASN)',
  `jabatan_pejabat_sekolah` varchar(120) NOT NULL COMMENT 'Jabatan Pejabat Satuan Pendidikan (non Kepala Sekolah) yang Berwenang',
  `fungsi_pejabat_sekolah` set('WAKASEK','WAKASEKSIS','PEMBINA','PENGAWAS','LAINNYA') NOT NULL COMMENT 'Fungsi Pejabat Satuan Pendidikan (non Kepala Sekolah) yang Berwenang yang relevan dengan tugas Kesiswaan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_pemilih`
--

CREATE TABLE `data_pemilih` (
  `id_unik_pemilih` varchar(28) NOT NULL COMMENT 'Kode ID Unik milik Pemilih',
  `nomor_dpt_pemilih` decimal(6,0) NOT NULL COMMENT 'Nomor Registrasi Pemilih dalam Daftar Pemilih Tetap',
  `nama_pemilih` varchar(120) NOT NULL COMMENT 'Nama Lengkap Pemilih',
  `jk_pemilih` set('PRIA','WANITA','TIDAK_DIKETAHUI') NOT NULL COMMENT 'Jenis Kelamin Pemilih',
  `kk_pemilih` decimal(2,0) NOT NULL COMMENT 'Kode Konstituensi Pemilih',
  `nama_akun_pemilih` varchar(14) NOT NULL COMMENT 'Nama Akun Akses SSE milik Pemilih',
  `kata_sandi_pemilih` varchar(255) NOT NULL COMMENT 'Kata Sandi Akun Akses SSE milik Pemilih (dalam bentuk Hash)',
  `iv_akun_pemilih` varchar(255) NOT NULL COMMENT 'Kode Vektor Inisiasi Enkripsi Data Pemilih (dalam format Base64)',
  `status_pemilih` set('BELUM_MEMILIH','SUDAH_MEMILIH') NOT NULL COMMENT 'Status Akun Pemilih'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_pemilihan`
--

CREATE TABLE `data_pemilihan` (
  `id_unik_pemilihan` varchar(44) NOT NULL COMMENT 'Kode ID Unik eSPPO untuk Kegiatan Pemilihan ini',
  `nama_pemilihan` varchar(120) NOT NULL COMMENT 'Nama Kegiatan Pemilihan',
  `tipe_peserta_pemilihan` set('TUNGGAL','BERPASANGAN') NOT NULL COMMENT 'Tipe Peserta Pemilihan',
  `masa_bakti` varchar(9) NOT NULL COMMENT 'Periode/Masa Bakti Hasil Pemilihan',
  `status_pemilihan` set('BELUM_DIMULAI','SEDANG_BERLANSUNG','SELESAI_DILAKSANAKAN') NOT NULL COMMENT 'Status Pelaksanaan Kegiatan Pemilihan',
  `tanggal_mulai` date NOT NULL COMMENT 'Tanggal Dimulainya Kegiatan Pemilihan',
  `tanggal_selesai` date DEFAULT NULL COMMENT 'Tanggal Selesainya Kegiatan Pemilihan',
  `jumlah_tps` decimal(4,0) NOT NULL COMMENT 'Jumlah Tempat Pemungutan Suara yang Dibuka',
  `jumlah_passe` decimal(4,0) NOT NULL COMMENT 'Jumlah Keseluruhan Perangkat Akses Surat Suara Elektronik yang Tersedia',
  `mode_tampilan_sse` set('BERGAMBAR_BERTEKSVM','BERGAMBAR_NONTEKSVM','NONGAMBAR_BERTEKSVM','NONGAMBAR_NONTEKSVM') NOT NULL COMMENT 'Mode Tampilan yang digunakan untuk SSE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_suara`
--

CREATE TABLE `data_suara` (
  `urutan_suara_masuk` int(7) NOT NULL COMMENT 'Urutan Masuknya Suara Elektronik',
  `stempel_waktu_suara` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Stempel Waktu Masuknya Suara Elektronik dari Pemilih',
  `kode_id_suara` varchar(44) NOT NULL COMMENT 'Kode ID Unik Surat Suara Elektronik',
  `no_urut_kandidat` decimal(2,0) NOT NULL COMMENT 'Nomor Urut Kandidat yang Dipilih Pemilih',
  `kk_pemilih` decimal(2,0) NOT NULL COMMENT 'Kode Konstituensi Pemilih',
  `jk_pemilih` set('PRIA','WANITA','TIDAK_DIKETAHUI') NOT NULL COMMENT 'Jenis Kelamin Pemilih',
  `id_pemilih_terenkripsi` varchar(255) NOT NULL COMMENT 'Kode ID Unik milik Pemilih yang dienkripsi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `akun_administrasi`
--
ALTER TABLE `akun_administrasi`
  ADD PRIMARY KEY (`id_unik_akun_admin`);

--
-- Indeks untuk tabel `data_kandidat`
--
ALTER TABLE `data_kandidat`
  ADD PRIMARY KEY (`id_unik_kandidat`),
  ADD UNIQUE KEY `indeks_no_kandidat` (`no_urut_kandidat`),
  ADD KEY `relasi_kk_calon_1` (`kk_calon_1`),
  ADD KEY `relasi_kk_calon_2` (`kk_calon_2`);

--
-- Indeks untuk tabel `data_kehadiran`
--
ALTER TABLE `data_kehadiran`
  ADD PRIMARY KEY (`urutan_kehadiran`),
  ADD KEY `indeks_waktu_kehadiran` (`stempel_waktu_kehadiran`),
  ADD KEY `relasi_kode_id_pemilih_kehadiran` (`id_unik_pemilih`),
  ADD KEY `relasi_kk_pemilih_kehadiran` (`kk_pemilih`);

--
-- Indeks untuk tabel `data_konstituensi`
--
ALTER TABLE `data_konstituensi`
  ADD PRIMARY KEY (`kode_konstituensi`);

--
-- Indeks untuk tabel `data_pejabat_sekolah`
--
ALTER TABLE `data_pejabat_sekolah`
  ADD PRIMARY KEY (`id_unik_pejabat_sekolah`);

--
-- Indeks untuk tabel `data_pemilih`
--
ALTER TABLE `data_pemilih`
  ADD PRIMARY KEY (`id_unik_pemilih`),
  ADD UNIQUE KEY `indeks_no_dpt` (`nomor_dpt_pemilih`),
  ADD KEY `relasi_kk_pemilih` (`kk_pemilih`);

--
-- Indeks untuk tabel `data_pemilihan`
--
ALTER TABLE `data_pemilihan`
  ADD PRIMARY KEY (`id_unik_pemilihan`);

--
-- Indeks untuk tabel `data_suara`
--
ALTER TABLE `data_suara`
  ADD PRIMARY KEY (`kode_id_suara`),
  ADD UNIQUE KEY `indeks_urutan_suara` (`urutan_suara_masuk`),
  ADD KEY `relasi_kk_pemilih_sse` (`kk_pemilih`),
  ADD KEY `relasi_no_kandidat_sse` (`no_urut_kandidat`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `data_kehadiran`
--
ALTER TABLE `data_kehadiran`
  MODIFY `urutan_kehadiran` int(7) NOT NULL AUTO_INCREMENT COMMENT 'Urutan Kehadiran Pemilih (dengan masuk ke SSE)', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT untuk tabel `data_suara`
--
ALTER TABLE `data_suara`
  MODIFY `urutan_suara_masuk` int(7) NOT NULL AUTO_INCREMENT COMMENT 'Urutan Masuknya Suara Elektronik', AUTO_INCREMENT=1;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `data_kandidat`
--
ALTER TABLE `data_kandidat`
  ADD CONSTRAINT `relasi_kk_calon_1` FOREIGN KEY (`kk_calon_1`) REFERENCES `data_konstituensi` (`kode_konstituensi`) ON UPDATE CASCADE,
  ADD CONSTRAINT `relasi_kk_calon_2` FOREIGN KEY (`kk_calon_2`) REFERENCES `data_konstituensi` (`kode_konstituensi`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `data_kehadiran`
--
ALTER TABLE `data_kehadiran`
  ADD CONSTRAINT `relasi_kk_pemilih_kehadiran` FOREIGN KEY (`kk_pemilih`) REFERENCES `data_konstituensi` (`kode_konstituensi`) ON UPDATE CASCADE,
  ADD CONSTRAINT `relasi_kode_id_pemilih_kehadiran` FOREIGN KEY (`id_unik_pemilih`) REFERENCES `data_pemilih` (`id_unik_pemilih`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `data_pemilih`
--
ALTER TABLE `data_pemilih`
  ADD CONSTRAINT `relasi_kk_pemilih` FOREIGN KEY (`kk_pemilih`) REFERENCES `data_konstituensi` (`kode_konstituensi`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `data_suara`
--
ALTER TABLE `data_suara`
  ADD CONSTRAINT `relasi_kk_pemilih_sse` FOREIGN KEY (`kk_pemilih`) REFERENCES `data_konstituensi` (`kode_konstituensi`) ON UPDATE CASCADE,
  ADD CONSTRAINT `relasi_no_kandidat_sse` FOREIGN KEY (`no_urut_kandidat`) REFERENCES `data_kandidat` (`no_urut_kandidat`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
