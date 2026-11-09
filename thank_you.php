<?php
/**
 * KUESIONER ANGGOTA - Halaman Konfirmasi Sukses (Terima Kasih)
 * Sesuai Desain Mockup PDF Halaman 5
 */

require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? null;
$response = null;
$cabang = null;

if ($token) {
    $db = get_db_connection();
    $stmt = $db->prepare("
        SELECT r.*, c.nama_cabang, c.kode_cabang
        FROM survey_response r
        JOIN cabang c ON r.cabang_id = c.id
        WHERE r.respon_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $response = $stmt->fetch();
    if ($response) {
        $cabang = $response['nama_cabang'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Terima Kasih - KUESIONER ANGGOTA</title>
  
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
        <?php if ($cabang): ?>
          <span class="badge-branch"><i class="bi bi-geo-alt-fill"></i> Cabang <?= e($cabang) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Body Content -->
  <main class="survey-body d-flex flex-column justify-content-center text-center">
    <div class="state-screen py-4">
      <div class="state-icon-circle state-icon-success shadow-sm">
        <i class="bi bi-check2-circle"></i>
      </div>

      <h1 class="state-title text-primary-fastabiq">Alhamdulillah, Terima Kasih!</h1>

      <p class="state-desc px-2">
        Penilaian Bapak/Ibu telah kami terima dengan aman. Masukan Anda membantu kami meningkatkan kualitas pelayanan kepada seluruh anggota.
      </p>

      <div class="p-3 my-4 bg-light rounded-4 border text-start" style="font-size: 0.88rem;">
        <div class="d-flex align-items-center gap-2 mb-2 text-primary-fastabiq fw-bold">
          <i class="bi bi-shield-check fs-5"></i>
          <span>Data Pelayanan Tercatat</span>
        </div>
        <div class="text-secondary small mb-1">
          Waktu: <strong><?= date('d F Y, H:i') ?> WIB</strong>
        </div>
        <?php if ($response && !empty($response['respon_token'])): ?>
        <div class="text-secondary small">
          No. Tiket: <code><?= e($response['respon_token']) ?></code>
        </div>
        <?php endif; ?>
      </div>

      <div class="my-4">
        <p class="fst-italic text-secondary" style="font-size: 0.95rem;">
          &ldquo;Bersama anggota, bertumbuh dalam amanah.&rdquo;
        </p>
      </div>

      <div class="alert alert-success border-0 rounded-3 py-2 px-3 small d-inline-block">
        <i class="bi bi-info-circle me-1"></i> Anda dapat menutup halaman ini sekarang.
      </div>

      <div class="mt-4">
        <a href="<?= BASE_URL ?>/survey.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
          <i class="bi bi-arrow-repeat me-1"></i> Mulai Penilaian Baru
        </a>
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

