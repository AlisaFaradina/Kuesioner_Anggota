<?php
/**
 * KUESIONER ANGGOTA - Admin Header & Layout Start
 * Sistem Kuesioner Kepuasan Layanan Anggota BMT Fastabiq
 */

require_once __DIR__ . '/auth.php';
$currentUser = require_login();
$activePage = $activePage ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Dashboard';

// Initials avatar
$names = explode(' ', $currentUser['nama_lengkap']);
$initials = '';
foreach (array_slice($names, 0, 2) as $n) {
    $initials .= strtoupper(substr($n, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> - KUESIONER ANGGOTA</title>
  
  <!-- CSS Assets (Offline Supported) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="admin-layout">
  <!-- Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-brand d-flex align-items-center gap-2">
      <img src="<?= BASE_URL ?>/assets/images/logo_bmt.png" alt="Logo BMT Fastabiq" style="width: 38px; height: 38px; object-fit: contain; flex-shrink: 0;">
      <div style="min-width: 0;">
        <div class="admin-brand-title" style="font-size: 0.98rem; font-weight: 800; line-height: 1.2; letter-spacing: -0.3px;">Kuesioner Anggota</div>
        <div class="admin-brand-sub" style="font-size: 0.72rem; line-height: 1.2;">Mendengar untuk melayani</div>
      </div>
    </div>

    <!-- User Profile Pill -->
    <div class="admin-user-pill">
      <div class="admin-avatar"><?= e($initials) ?></div>
      <div style="min-width: 0; flex: 1;">
        <div style="font-weight: 700; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
          <?= e($currentUser['nama_lengkap']) ?>
        </div>
        <div style="font-size: 0.72rem; color: #64748b; text-transform: capitalize;">
          <?= $currentUser['role'] === 'kepala_cabang' ? 'Kepala Cabang' : ($currentUser['role'] === 'pusat' ? 'Staf Admisi' : 'Administrator') ?>
        </div>
      </div>
    </div>

    <!-- Branch Notice if Branch Head -->
    <?php if ($currentUser['role'] === 'kepala_cabang'): ?>
    <div style="padding: 10px 16px; background: #f0fdf4; border-bottom: 1px solid #bbf7d0; font-size: 0.76rem; color: #166534;">
      <i class="bi bi-geo-alt-fill me-1"></i> Cabang: <strong><?= e($currentUser['nama_cabang'] ?? 'Cabang Khusus') ?></strong>
    </div>
    <?php endif; ?>

    <!-- Navigation Menu -->
    <nav class="admin-nav">
      <div class="admin-nav-heading">RUANG KERJA</div>
      
      <a href="<?= BASE_URL ?>/admin/index.php" class="admin-nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/rankings.php" class="admin-nav-item <?= $activePage === 'rankings' ? 'active' : '' ?>">
        <i class="bi bi-trophy-fill"></i>
        <span>Peringkat Cabang</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/reports.php" class="admin-nav-item <?= $activePage === 'reports' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-bar-graph-fill"></i>
        <span>Laporan Penilaian</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/product_recap.php" class="admin-nav-item <?= $activePage === 'product_recap' ? 'active' : '' ?>">
        <i class="bi bi-table"></i>
        <span>Rekap Produk Ditawarkan</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/responses.php" class="admin-nav-item <?= in_array($activePage, ['responses', 'suggestions']) ? 'active' : '' ?>">
        <i class="bi bi-chat-heart-fill"></i>
        <span>Suara & Keluhan</span>
      </a>

      <?php if ($currentUser['role'] === 'admin'): ?>
      <a href="<?= BASE_URL ?>/admin/activity_log.php" class="admin-nav-item <?= $activePage === 'activity_log' ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i>
        <span>Log Aktivitas</span>
      </a>
      <?php endif; ?>

      <div class="admin-nav-heading mt-3">MASTER & SISTEM</div>

      <a href="<?= BASE_URL ?>/admin/branches.php" class="admin-nav-item <?= $activePage === 'branches' ? 'active' : '' ?>">
        <i class="bi bi-building"></i>
        <span>Cabang & QR Code</span>
      </a>

      <?php if (in_array($currentUser['role'], ['admin', 'pusat'], true)): ?>
      <a href="<?= BASE_URL ?>/admin/questions.php" class="admin-nav-item <?= $activePage === 'questions' ? 'active' : '' ?>">
        <i class="bi bi-question-diamond-fill"></i>
        <span>Kuesioner & Soal</span>
      </a>
      <?php endif; ?>

      <?php if ($currentUser['role'] === 'admin'): ?>
      <a href="<?= BASE_URL ?>/admin/users.php" class="admin-nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i>
        <span>Pengguna Sistem</span>
      </a>
      
      <a href="<?= BASE_URL ?>/admin/settings.php" class="admin-nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
        <i class="bi bi-sliders"></i>
        <span>Pengaturan</span>
      </a>
      <?php endif; ?>

      <a href="<?= BASE_URL ?>/admin/info_program.php" class="admin-nav-item <?= $activePage === 'info_program' ? 'active' : '' ?>">
        <i class="bi bi-info-circle-fill"></i>
        <span>Panduan Program</span>
      </a>

      <?php if ($currentUser['role'] === 'admin'): ?>
      <a href="<?= BASE_URL ?>/admin/database_tables.php" class="admin-nav-item <?= $activePage === 'database_tables' ? 'active' : '' ?>">
        <i class="bi bi-database-fill"></i>
        <span>Tabel Database</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/backup.php" class="admin-nav-item <?= $activePage === 'backup' ? 'active' : '' ?>">
        <i class="bi bi-database-fill-gear"></i>
        <span>Backup Database</span>
      </a>
      <?php endif; ?>

      <div class="mt-4 pt-2 border-top">
        <a href="<?= BASE_URL ?>/admin/logout.php" class="admin-nav-item text-danger">
          <i class="bi bi-box-arrow-right"></i>
          <span>Keluar</span>
        </a>
      </div>
    </nav>
  </aside>

  <!-- Main Content Area -->
  <main class="admin-main">
    <!-- Top Mobile Header Bar -->
    <div class="d-flex d-lg-none justify-content-between align-items-center mb-3 pb-2 border-bottom">
      <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('adminSidebar').classList.toggle('show')">
        <i class="bi bi-list fs-5"></i>
      </button>
      <span class="fw-bold text-primary-fastabiq">KUESIONER ANGGOTA</span>
      <a href="<?= BASE_URL ?>/admin/logout.php" class="text-danger"><i class="bi bi-box-arrow-right fs-5"></i></a>
    </div>

