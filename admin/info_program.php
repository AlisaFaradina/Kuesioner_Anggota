<?php
/**
 * KUESIONER ANGGOTA - Panduan Program & Interpretasi Nilai
 * Mengintegrasikan dokumen rancangan info_program.php BMT Fastabiq
 */

$activePage = 'info_program';
$pageTitle = 'Panduan Program KUESIONER ANGGOTA';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill mb-2">
    PANDUAN PROGRAM RESMI
  </span>
  <h1 class="h2 fw-bold text-primary-fastabiq mb-1">KUESIONER <em>ANGGOTA</em></h1>
  <p class="text-secondary lead fs-6 mb-3">Suara Anda, Semangat Kami untuk Melayani Lebih Baik.</p>
  <p class="text-muted" style="max-width: 800px;">
    KUESIONER ANGGOTA adalah program pengukuran pengalaman anggota setelah menerima layanan di kantor cabang BMT Fastabiq. Masukan anggota membantu BMT Fastabiq melihat hal yang sudah baik, menemukan bagian yang perlu diperbaiki, dan membahas mutu layanan berbasis bukti data nyata.
  </p>
  <div class="d-flex gap-2">
    <a class="btn btn-sm btn-primary-survey rounded-pill px-3" href="<?= BASE_URL ?>/admin/reports.php">
      <i class="bi bi-file-earmark-bar-graph me-1"></i> Buka Laporan Penilaian
    </a>
    <a class="btn btn-sm btn-outline-secondary rounded-pill px-3" href="#alur">
      Lihat Alur Program &darr;
    </a>
  </div>
</div>

<!-- ALUR PROGRAM -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4" id="alur">
  <div class="mb-4">
    <span class="text-primary-fastabiq small fw-bold text-uppercase" style="letter-spacing: 0.08em;">DARI PENGALAMAN KE PERBAIKAN</span>
    <h2 class="h4 fw-bold mt-1">Alur Kerja KUESIONER ANGGOTA</h2>
    <p class="text-muted small">Prosesnya sederhana untuk anggota dan hasilnya langsung dapat digunakan sebagai bahan evaluasi cabang.</p>
  </div>

  <div class="row g-3">
    <div class="col-12 col-md-4">
      <div class="p-3 border rounded-3 h-100 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-primary text-white rounded-circle p-2">01</span>
          <i class="bi bi-gear-fill fs-4 text-primary"></i>
        </div>
        <h3 class="h6 fw-bold mb-1">Pusat Menyiapkan</h3>
        <p class="text-muted small mb-0">Admin mengelola cabang, QR unik, versi kuesioner, dan 10 pertanyaan pelayanan.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="p-3 border rounded-3 h-100 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-primary text-white rounded-circle p-2">02</span>
          <i class="bi bi-qr-code fs-4 text-primary"></i>
        </div>
        <h3 class="h6 fw-bold mb-1">Anggota Membuka</h3>
        <p class="text-muted small mb-0">Anggota memindai QR code unik yang dipasang pada meja pelayanan cabang atau memilih cabang.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="p-3 border rounded-3 h-100 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-primary text-white rounded-circle p-2">03</span>
          <i class="bi bi-star-fill fs-4 text-warning"></i>
        </div>
        <h3 class="h6 fw-bold mb-1">Anggota Menilai</h3>
        <p class="text-muted small mb-0">Memberi nilai 1–4 (STS, TS, S, SS). Tanda tangan digital &amp; produk yang ditawarkan tercatat otomatis.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="p-3 border rounded-3 h-100 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-primary text-white rounded-circle p-2">04</span>
          <i class="bi bi-database-check fs-4 text-success"></i>
        </div>
        <h3 class="h6 fw-bold mb-1">Respons Tersimpan</h3>
        <p class="text-muted small mb-0">Respons tercatat bersama cabang, waktu pengisian otomatis, dan tersimpan aman di database.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="p-3 border rounded-3 h-100 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-primary text-white rounded-circle p-2">05</span>
          <i class="bi bi-bar-chart-fill fs-4 text-info"></i>
        </div>
        <h3 class="h6 fw-bold mb-1">Pusat Membaca Hasil</h3>
        <p class="text-muted small mb-0">Gunakan periode dan filter cabang untuk membandingkan ringkasan, aspek, tren, dan ekspor Excel/PDF.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="p-3 border rounded-3 h-100 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-primary text-white rounded-circle p-2">06</span>
          <i class="bi bi-graph-up-arrow fs-4 text-primary"></i>
        </div>
        <h3 class="h6 fw-bold mb-1">Cabang Menindaklanjuti</h3>
        <p class="text-muted small mb-0">Bahas hasil bersama tim cabang, sepakati perbaikan mutu layanan, dan tingkatkan kepuasan anggota.</p>
      </div>
    </div>
  </div>
</div>

<!-- MEMBACA HASIL & ARTI ANGKA -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
  <div class="mb-4">
    <span class="text-primary-fastabiq small fw-bold text-uppercase" style="letter-spacing: 0.08em;">MEMBACA HASIL</span>
    <h2 class="h4 fw-bold mt-1">Arti Angka di Laporan &amp; KPI Cabang</h2>
    <p class="text-muted small">Gunakan periode yang sama saat membandingkan antar cabang atau antar bulan.</p>
  </div>

  <div class="row g-4">
    <div class="col-12 col-md-6">
      <h3 class="h6 fw-bold mb-3 text-primary-fastabiq">Indikator Yang Tersedia (Skala 1 - 4)</h3>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item px-0">
          <strong>Jumlah Responden:</strong> Jumlah pengisian kuesioner valid yang unik dalam periode terpilih.
        </li>
        <li class="list-group-item px-0">
          <strong>Pembobotan Nilai:</strong> Sangat Tidak Puas (STS) = 1, Tidak Puas (TS) = 2, Puas (S) = 3, Sangat Puas (SS) = 4.
        </li>
        <li class="list-group-item px-0">
          <strong>Rata-rata Kepuasan:</strong> Total skor tertimbang dibagi total seluruh jawaban rating valid (skala 1–4).
        </li>
        <li class="list-group-item px-0">
          <strong>KPI Kepuasan (% Skor):</strong> <code>(Rata-rata kepuasan &divide; 4) &times; 100%</code>.
        </li>
        <li class="list-group-item px-0">
          <strong>Persentase Pilihan SS (Nilai 4):</strong> <code>(Jawaban nilai SS &divide; seluruh jawaban rating) &times; 100%</code>.
        </li>
        <li class="list-group-item px-0">
          <strong>Rata-rata per Aspek:</strong> Rata-rata rating untuk dimensi layanan tertentu (skala 1–4).
        </li>
      </ul>
    </div>

    <div class="col-12 col-md-6">
      <h3 class="h6 fw-bold mb-3 text-primary-fastabiq">Interval Kategori Kepuasan BMT Fastabiq</h3>
      <div class="table-responsive">
        <table class="table table-sm table-bordered" style="font-size: 0.85rem;">
          <thead class="table-light">
            <tr><th>Interval Nilai</th><th>Kategori Penilaian</th><th>Predikat KPI</th></tr>
          </thead>
          <tbody>
            <tr><td>3.26 &ndash; 4.00</td><td><span class="badge badge-sangat-puas">Sangat Puas</span></td><td>Target Tercapai</td></tr>
            <tr><td>2.51 &ndash; 3.25</td><td><span class="badge badge-puas">Puas</span></td><td>Cukup Baik</td></tr>
            <tr><td>1.76 &ndash; 2.50</td><td><span class="badge badge-cukup-puas">Cukup Puas</span></td><td>Perlu Perhatian</td></tr>
            <tr><td>1.00 &ndash; 1.75</td><td><span class="badge badge-kurang-puas">Kurang Puas</span></td><td>Perlu Tindakan Cepat</td></tr>
          </tbody>
        </table>
      </div>
      <div class="p-3 bg-light rounded-3 small text-muted border">
        <strong>Langkah Evaluasi:</strong> Periksa jumlah responden terlebih dahulu. Jumlah responden yang kecil membuat rata-rata lebih mudah berubah. Temukan aspek terendah untuk mengarahkan prioritas perbaikan layanan di cabang.
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

