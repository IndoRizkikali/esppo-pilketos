<?php
/**
 * e-SPPO - Pusat Administrasi Pemilihan Terpadu (Pusminlihdu) - Pengelola - Pratayang SSE
 * pusminlihdu/pengelola/pratayang_sse.php
 * 
 * Ini adalah halaman Pratayang SSE untuk pengelola di Pusminlihdu.
 * Halaman ini menampilkan iframe dari tampilan SSE untuk keperluan pratinjau.
 * Pratayang ini membantu memastikan bahwa semua elemen tampilan SSE sudah sesuai dengan yang diinginkan.
 *
 * @version 2.0.0
 * @author Rizki Yandri & OSIS SMA Negeri 1 Bati-Bati
 * @copyright (c) 2025
 * @license Apache License 2.0
 * @see NOTICE untuk informasi lisensi dan hak cipta lengkap.
 */

// Pastikan sesi sudah dimulai dan pengguna sudah login
// (Sudah ditangani oleh index.php)
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pratayang Tampilan Surat Suara Elektronik</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5><i class="icon fas fa-info"></i> Informasi</h5>
                    Ini adalah pratinjau dari tampilan Surat Suara Elektronik (SSE) yang akan dilihat oleh pemilih.
                    Pratinjau ini membantu Anda memastikan bahwa semua elemen tampilan SSE sudah sesuai dengan yang diinginkan.
                </div>
                
                <div class="embed-responsive" style="min-height: 800px;">
                    <iframe src="../../sse/index_preview.php" 
                            style="width: 100%; height: 100%; border: 1px solid #dee2e6; border-radius: 0.25rem;"
                            title="Pratayang SSE">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</div>
