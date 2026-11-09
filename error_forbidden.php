<?php
/**
 * KUESIONER ANGGOTA - 403 Forbidden Access
 */

require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Akses Dibatasi - KUESIONER ANGGOTA</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light p-3">
  <div class="card border-0 shadow-sm rounded-4 p-4 text-center" style="max-width: 460px;">
    <div class="text-danger fs-1 mb-2"><i class="bi bi-shield-lock-fill"></i></div>
    <h3 class="fw-bold">Akses Dibatasi</h3>
    <p class="text-muted small">
      Anda tidak memiliki izin untuk melihat data atau mengelola modul ini. Hak akses Anda dibatasi sesuai cabang yang telah ditentukan demi keamanan data.
    </p>
    <div class="mt-3">
      <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-primary-survey btn-sm">
        Kembali ke Dashboard Cabang Anda
      </a>
    </div>
  </div>
</body>
</html>

