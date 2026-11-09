<?php
/**
 * KUESIONER ANGGOTA - Log Aktivitas User
 * Mencatat dan menampilkan aktivitas pengguna sistem untuk audit trail
 */

$activePage = 'activity_log';
$pageTitle = 'Log Aktivitas User';
require_once __DIR__ . '/../includes/header.php';

require_role(['admin']);
$db = get_db_connection();

// Filter Parameters
$filterUser = isset($_GET['user']) && $_GET['user'] !== '' ? (int)$_GET['user'] : null;
$filterModule = $_GET['module'] ?? 'all';
$filterDate = $_GET['date'] ?? '';
$search = trim($_GET['q'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 30;
$offset = ($page - 1) * $limit;

// Build Query
$where = ["1=1"];
$params = [];

if ($filterUser) {
    $where[] = "al.user_id = ?";
    $params[] = $filterUser;
}
if ($filterModule !== 'all') {
    $where[] = "al.module = ?";
    $params[] = $filterModule;
}
if ($filterDate) {
    $where[] = "DATE(al.created_at) = ?";
    $params[] = $filterDate;
}
if ($search) {
    $where[] = "(al.action LIKE ? OR al.detail LIKE ? OR al.username LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSql = implode(' AND ', $where);

// Count total
$countSql = "SELECT COUNT(*) FROM activity_log al WHERE $whereSql";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch data
$sql = "
    SELECT 
        al.id, al.username, al.nama_lengkap, al.action, al.module, al.detail,
        al.ip_address, al.created_at
    FROM activity_log al
    WHERE $whereSql
    ORDER BY al.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get users for filter
$users = $db->query("SELECT id, username, nama_lengkap FROM users WHERE status = 'aktif' ORDER BY nama_lengkap ASC")->fetchAll();

// Get available modules
$modules = $db->query("SELECT DISTINCT module FROM activity_log ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($modules)) {
    $modules = ['dashboard', 'reports', 'responses', 'users', 'settings', 'branches', 'questions'];
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Log Aktivitas User</h1>
    <p class="text-muted small mb-0">
      Audit trail aktivitas pengguna sistem untuk keamanan dan monitoring.
    </p>
  </div>
  <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
  </a>
</div>

<!-- Filter Bar -->
<form method="GET" class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white mb-4">
  <div class="row g-3">
    <div class="col-12 col-md-3">
      <label class="form-label small fw-bold">User</label>
      <select name="user" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Semua User</option>
        <?php foreach ($users as $u): ?>
        <option value="<?= $u['id'] ?>" <?= $filterUser === (int)$u['id'] ? 'selected' : '' ?>>
          <?= e($u['nama_lengkap']) ?> (<?= e($u['username']) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-2">
      <label class="form-label small fw-bold">Module</label>
      <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="all" <?= $filterModule === 'all' ? 'selected' : '' ?>>Semua</option>
        <?php foreach ($modules as $m): ?>
        <option value="<?= e($m) ?>" <?= $filterModule === $m ? 'selected' : '' ?>><?= ucfirst(e($m)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-2">
      <label class="form-label small fw-bold">Tanggal</label>
      <input type="date" name="date" class="form-control form-control-sm" value="<?= e($filterDate) ?>" onchange="this.form.submit()">
    </div>

    <div class="col-12 col-md-5">
      <label class="form-label small fw-bold">Cari</label>
      <div class="input-group">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Kata kunci, action, detail..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </div>
  </div>
</form>

<!-- Statistik Ringkas -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-3">
    <div class="stat-card">
      <div class="stat-label">Total Aktivitas</div>
      <div class="stat-value"><?= number_format($totalRows, 0, ',', '.') ?></div>
      <div class="stat-sub">Semua waktu</div>
    </div>
  </div>
  <div class="col-12 col-md-3">
    <div class="stat-card">
      <div class="stat-label">Hari Ini</div>
      <div class="stat-value text-primary-fastabiq">
        <?php
        $todayCount = $db->query("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        echo number_format($todayCount, 0, ',', '.');
        ?>
      </div>
      <div class="stat-sub"><?= date('d/m/Y') ?></div>
    </div>
  </div>
  <div class="col-12 col-md-3">
    <div class="stat-card">
      <div class="stat-label">User Aktif</div>
      <div class="stat-value text-success">
        <?= count($users) ?>
      </div>
      <div class="stat-sub">Terdaftar</div>
    </div>
  </div>
  <div class="col-12 col-md-3">
    <div class="stat-card">
      <div class="stat-label">Modul</div>
      <div class="stat-value text-warning">
        <?= count($modules) ?>
      </div>
      <div class="stat-sub">Digunakan</div>
    </div>
  </div>
</div>

<!-- Daftar Log -->
<div class="card border-0 shadow-sm rounded-4 bg-white">
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size: 0.82rem;">
      <thead>
        <tr>
          <th style="width: 140px;">Waktu</th>
          <th style="width: 150px;">User</th>
          <th style="width: 100px;">Module</th>
          <th>Action</th>
          <th>Detail</th>
          <th style="width: 120px;">IP Address</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4">
            <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
            Belum ada aktivitas yang sesuai filter.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td>
            <small class="text-muted"><?= date('d/m/Y', strtotime($log['created_at'])) ?></small><br>
            <small class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
          </td>
          <td>
            <div class="fw-bold"><?= e($log['nama_lengkap']) ?></div>
            <small class="text-muted">@<?= e($log['username']) ?></small>
          </td>
          <td>
            <span class="badge bg-light text-dark border"><?= ucfirst(e($log['module'])) ?></span>
          </td>
          <td>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($log['action']) ?></span>
          </td>
          <td>
            <small class="text-muted"><?= e($log['detail'] ?? '-') ?></small>
          </td>
          <td>
            <small class="text-muted"><?= e($log['ip_address'] ?? '-') ?></small>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">
      Halaman <?= $page ?> dari <?= $totalPages ?> (<?= number_format($totalRows, 0, ',', '.') ?> log)
    </small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Sebelumnya</a>
        </li>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
        <li class="page-item">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Berikutnya</a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<div class="mt-3 p-3 bg-light rounded-3 border small text-muted">
  <i class="bi bi-info-circle me-1"></i>
  <strong>Catatan:</strong> Log aktivitas otomatis dicatat saat user login, logout, dan melakukan aksi penting di sistem.
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
