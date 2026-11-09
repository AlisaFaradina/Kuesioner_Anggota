<?php
/**
 * KUESIONER ANGGOTA - Halaman Penanganan Kegagalan Pengiriman
 * Sesuai Desain Mockup PDF Halaman 6
 */

require_once __DIR__ . '/includes/functions.php';

$errorMsg = $_GET['msg'] ?? 'Maaf, penilaian belum berhasil disimpan.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">   
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Pengiriman Belum Berhasil - KUESIONER ANGGOTA</title>
  
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="survey-wrapper">
  <!-- Top Bar -->
  <header class="survey-header">
    <div class="survey-brand d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <img src="<?= BASE_URL ?>/assets/images/logo_bmt.png" alt="Logo BMT Fastabiq" style="width: 34px; height: 34px; object-fit: contain;">
        <div>
          <div class="survey-title-text">Kuesioner Anggota</div>
          <div class="survey-subtitle-text">Mendengar untuk melayani</div>
        </div>
      </div>
      <div>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill small">
          Belum tersimpan
        </span>
      </div>
    </div>
  </header>

  <!-- Body Content -->
  <main class="survey-body d-flex flex-column justify-content-center text-center">
    <div class="state-screen py-4">
      <div class="state-icon-circle state-icon-error shadow-sm">
        <i class="bi bi-exclamation-triangle-fill"></i>
      </div>

      <h1 class="state-title text-danger">Pengiriman belum berhasil</h1>

      <p class="state-desc px-2">
        <?= e($errorMsg) ?><br>
        Pastikan koneksi internet Anda stabil, lalu kirim ulang penilaian.
      </p>

      <div class="my-4">
        <button type="button" class="btn-primary-survey" onclick="window.history.back();">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Coba Lagi
        </button>
      </div>

      <div class="alert alert-light border rounded-3 p-3 text-start small text-muted">
        <i class="bi bi-info-circle text-primary me-1"></i>
        Penilaian Anda belum tercatat di database. Silakan tetap di halaman ini untuk mencoba kembali atau hubungi petugas kami jika kendala berlanjut.
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="survey-footer-note">
    Suara Anda, semangat perbaikan kami. &bull; BMT Fastabiq
  </footer>
</div>

</body>
</html>

