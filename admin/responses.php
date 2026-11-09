<?php
/**
 * KUESIONER ANGGOTA - Data Responden & Masukan Detail
 */

$activePage = 'responses';
$pageTitle = 'Suara & Keluhan';
require_once __DIR__ . '/../includes/header.php';

$db = get_db_connection();

// Tab parameter
$tab = $_GET['tab'] ?? 'responden'; // 'responden' or 'saran'

$filterBranch = isset($_GET['cabang']) && $_GET['cabang'] !== '' ? (int)$_GET['cabang'] : null;
$filterMonth = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : null;
$filterYear = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$search = trim($_GET['q'] ?? '');

enforce_branch_scope($filterBranch);

$branches = get_all_branches(false);
$isBranchHead = ($currentUser['role'] === 'kepala_cabang');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = ["r.status = 'valid'"];
$params = [];

if ($filterBranch) {
    $where[] = "r.cabang_id = ?";
    $params[] = $filterBranch;
}
if ($filterYear) {
    $where[] = "YEAR(r.waktu_selesai) = ?";
    $params[] = $filterYear;
}
if ($filterMonth) {
    $where[] = "MONTH(r.waktu_selesai) = ?";
    $params[] = $filterMonth;
}
if ($search !== '') {
    $where[] = "(r.respon_token LIKE ? OR r.produk_ditawarkan LIKE ? OR r.kritik_saran_suka LIKE ? OR r.kritik_saran_perbaiki LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(' AND ', $where);

// Total data
$stmtCount = $db->prepare("SELECT COUNT(*) FROM survey_response r WHERE $whereSql");
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Data respon
$sqlData = "
    SELECT r.*, c.nama_cabang, c.kode_cabang
    FROM survey_response r
    JOIN cabang c ON r.cabang_id = c.id
    WHERE $whereSql
    ORDER BY r.waktu_selesai DESC
    LIMIT $limit OFFSET $offset
";
$stmtData = $db->prepare($sqlData);
$stmtData->execute($params);
$responses = $stmtData->fetchAll();
?>

<!-- Header Info & Filter Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Suara & Keluhan</h1>
    <p class="text-muted small mb-0">
      Data responden lengkap dan sar/keluhan anggota untuk perbaikan layanan.
    </p>
  </div>
</div>

<!-- Tab Navigation -->
<div class="d-flex gap-2 mb-4">
  <a href="?tab=responden" class="btn <?= $tab === 'responden' ? 'btn-primary-survey' : 'btn-outline-secondary bg-white' ?> rounded-pill px-4 py-2 fw-bold" style="font-size: 0.9rem;">
    <i class="bi bi-people-fill me-1"></i> Responden
  </a>
  <a href="?tab=saran" class="btn <?= $tab === 'saran' ? 'btn-primary-survey' : 'btn-outline-secondary bg-white' ?> rounded-pill px-4 py-2 fw-bold" style="font-size: 0.9rem;">
    <i class="bi bi-chat-quote-fill me-1"></i> Saran & Keluhan
  </a>
</div>

<?php if ($tab === 'responden'): ?>

<form method="GET" class="d-flex flex-wrap gap-2 align-items-center bg-white p-2 rounded-3 border shadow-sm mb-4">
  <input type="hidden" name="tab" value="responden">
  <div class="input-group input-group-sm" style="max-width: 200px;">
    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
    <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Cari teks/token..." value="<?= e($search) ?>">
  </div>

  <?php if (!$isBranchHead): ?>
  <select name="cabang" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit()">
    <option value="">Semua Cabang</option>
    <?php foreach ($branches as $b): ?>
    <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
      <?= e($b['kode_cabang']) ?> - <?= e($b['nama_cabang']) ?>
    </option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>

  <button type="submit" class="btn btn-sm btn-outline-secondary px-3">Filter</button>
</form>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <small class="text-muted">Total: <strong><?= number_format($totalRows, 0, ',', '.') ?> responden</strong></small>
    <small class="text-muted">Halaman <?= $page ?> dari <?= max(1, $totalPages) ?></small>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
      <thead class="table-light">
        <tr style="font-size: 0.78rem; text-transform: uppercase;">
          <th>Waktu Pengisian</th>
          <th>Cabang</th>
          <th class="text-center">Skor Rata-rata</th>
          <th class="text-center">% Skor</th>
          <th>Produk Ditawarkan</th>
          <th class="text-center">Tanda Tangan</th>
          <th>Masukan Anggota</th>
          <th class="text-end">Detail</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($responses)): ?>
        <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data responden yang cocok.</td></tr>
        <?php else: ?>
          <?php foreach ($responses as $r): 
            $pred = get_predikat_kepuasan($r['rata_rata_skor']);
          ?>
          <tr>
            <td>
              <div class="fw-bold"><?= format_tanggal_id($r['waktu_selesai'], true) ?></div>
              <small class="text-muted">Token: <code><?= e(substr($r['respon_token'], 0, 14)) ?>...</code></small>
            </td>
            <td>
              <span class="badge-branch"><i class="bi bi-geo-alt"></i> <?= e($r['nama_cabang']) ?></span>
            </td>
            <td class="text-center">
              <span class="fw-bold text-primary-fastabiq fs-6"><?= format_desimal($r['rata_rata_skor']) ?></span>
              <small class="text-muted">/ 4</small>
            </td>
            <td class="text-center">
              <span class="badge <?= get_badge_class($pred) ?>"><?= format_desimal($r['persentase_skor'], 1) ?>%</span>
            </td>
            <td>
              <small class="text-secondary"><?= e($r['produk_ditawarkan'] ?: '-') ?></small>
            </td>
            <td class="text-center">
              <?php if ($r['tanda_tangan']): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                  <i class="bi bi-check2"></i> Ada
                </span>
              <?php else: ?>
                <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['kritik_saran_suka']): ?>
                <div class="text-truncate" style="max-width: 220px;" title="<?= e($r['kritik_saran_suka']) ?>">
                  <strong class="text-success small">Suka:</strong> <?= e($r['kritik_saran_suka']) ?>
                </div>
              <?php endif; ?>
              <?php if ($r['kritik_saran_perbaiki']): ?>
                <div class="text-truncate" style="max-width: 220px;" title="<?= e($r['kritik_saran_perbaiki']) ?>">
                  <strong class="text-warning small">Perbaiki:</strong> <?= e($r['kritik_saran_perbaiki']) ?>
                </div>
              <?php endif; ?>
              <?php if (!$r['kritik_saran_suka'] && !$r['kritik_saran_perbaiki'] && !$r['kritik_saran_lain']): ?>
                <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 0.76rem;"
                      onclick='openDetailModal(<?= $r['id'] ?>, "<?= e($r['respon_token']) ?>", "<?= e($r['nama_cabang']) ?>")'>
                Lihat 10 Soal
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <?php if ($totalPages > 1): ?>
  <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
    <div>
      <?php if ($page > 1): ?>
        <a href="?tab=responden&page=<?= $page - 1 ?>&cabang=<?= $filterBranch ?>&q=<?= urlencode($search) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
          &larr; Sebelumnya
        </a>
      <?php endif; ?>
    </div>
    <div>
      <?php if ($page < $totalPages): ?>
        <a href="?tab=responden&page=<?= $page + 1 ?>&cabang=<?= $filterBranch ?>&q=<?= urlencode($search) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
          Berikutnya &rarr;
        </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($tab === 'saran'): ?>
<?php
// Reuse suggestions logic
$filterType = $_GET['type'] ?? 'all';
$filterMonthSaran = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : null;
$filterYearSaran = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$searchSaran = trim($_GET['q_saran'] ?? '');

$whereSaran = ["r.status = 'valid'"];
$paramsSaran = [];

if ($filterBranch) {
    $whereSaran[] = "r.cabang_id = ?";
    $paramsSaran[] = $filterBranch;
}
if ($filterYearSaran) {
    $whereSaran[] = "YEAR(r.waktu_selesai) = ?";
    $paramsSaran[] = $filterYearSaran;
}
if ($filterMonthSaran) {
    $whereSaran[] = "MONTH(r.waktu_selesai) = ?";
    $paramsSaran[] = $filterMonthSaran;
}
if ($searchSaran) {
    $whereSaran[] = "(r.kritik_saran_suka LIKE ? OR r.kritik_saran_perbaiki LIKE ? OR r.kritik_saran_lain LIKE ?)";
    $searchTerm = "%$searchSaran%";
    $paramsSaran[] = $searchTerm;
    $paramsSaran[] = $searchTerm;
    $paramsSaran[] = $searchTerm;
}

if ($filterType === 'suka') {
    $whereSaran[] = "r.kritik_saran_suka IS NOT NULL AND r.kritik_saran_suka != ''";
} elseif ($filterType === 'perbaiki') {
    $whereSaran[] = "r.kritik_saran_perbaiki IS NOT NULL AND r.kritik_saran_perbaiki != ''";
} elseif ($filterType === 'lain') {
    $whereSaran[] = "r.kritik_saran_lain IS NOT NULL AND r.kritik_saran_lain != ''";
}

$whereSaranSql = implode(' AND ', $whereSaran);

$pageSaran = max(1, (int)($_GET['page_saran'] ?? 1));
$limitSaran = 20;
$offsetSaran = ($pageSaran - 1) * $limitSaran;

$countSqlSaran = "SELECT COUNT(*) FROM survey_response r WHERE $whereSaranSql";
$stmtCountSaran = $db->prepare($countSqlSaran);
$stmtCountSaran->execute($paramsSaran);
$totalRowsSaran = $stmtCountSaran->fetchColumn();
$totalPagesSaran = ceil($totalRowsSaran / $limitSaran);

$sqlSaran = "
    SELECT 
        r.id, r.respon_token, r.waktu_selesai, r.rata_rata_skor, r.persentase_skor,
        r.kritik_saran_suka, r.kritik_saran_perbaiki, r.kritik_saran_lain,
        c.kode_cabang, c.nama_cabang
    FROM survey_response r
    JOIN cabang c ON r.cabang_id = c.id
    WHERE $whereSaranSql
    ORDER BY r.waktu_selesai DESC
    LIMIT $limitSaran OFFSET $offsetSaran
";
$stmtSaran = $db->prepare($sqlSaran);
$stmtSaran->execute($paramsSaran);
$suggestionsData = $stmtSaran->fetchAll();
?>

<form method="GET" class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white mb-4">
  <input type="hidden" name="tab" value="saran">
  <div class="row g-3">
    <div class="col-12 col-md-3">
      <label class="form-label small fw-bold">Cabang</label>
      <select name="cabang" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Semua Cabang</option>
        <?php foreach ($branches as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
          <?= e($b['nama_cabang']) ?> (<?= e($b['kode_cabang']) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-2">
      <label class="form-label small fw-bold">Tahun</label>
      <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php for ($y = 2026; $y >= 2024; $y--): ?>
        <option value="<?= $y ?>" <?= $filterYearSaran === $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="col-12 col-md-2">
      <label class="form-label small fw-bold">Bulan</label>
      <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Semua Bulan</option>
        <?php for ($m = 1; $m <= 12; $m++): ?>
        <option value="<?= $m ?>" <?= $filterMonthSaran === $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="col-12 col-md-2">
      <label class="form-label small fw-bold">Tipe Masukan</label>
      <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Semua</option>
        <option value="suka" <?= $filterType === 'suka' ? 'selected' : '' ?>>Hal Disukai</option>
        <option value="perbaiki" <?= $filterType === 'perbaiki' ? 'selected' : '' ?>>Perlu Perbaiki</option>
        <option value="lain" <?= $filterType === 'lain' ? 'selected' : '' ?>>Pesan Lain</option>
      </select>
    </div>

    <div class="col-12 col-md-3">
      <label class="form-label small fw-bold">Cari</label>
      <div class="input-group">
        <input type="text" name="q_saran" class="form-control form-control-sm" placeholder="Kata kunci..." value="<?= e($searchSaran) ?>">
        <button type="submit" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </div>
  </div>
</form>

<!-- Statistik Ringkas -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="stat-card">
      <div class="stat-label">Total Masukan</div>
      <div class="stat-value"><?= number_format($totalRowsSaran, 0, ',', '.') ?></div>
      <div class="stat-sub">Semua cabang</div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="stat-card">
      <div class="stat-label">Hal Disukai</div>
      <div class="stat-value text-success">
        <?php
        $countSuka = $db->query("SELECT COUNT(*) FROM survey_response WHERE kritik_saran_suka IS NOT NULL AND kritik_saran_suka != '' AND status = 'valid'")->fetchColumn();
        echo number_format($countSuka, 0, ',', '.');
        ?>
      </div>
      <div class="stat-sub">Saran positif</div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="stat-card">
      <div class="stat-label">Perlu Perbaiki</div>
      <div class="stat-value text-warning">
        <?php
        $countPerbaiki = $db->query("SELECT COUNT(*) FROM survey_response WHERE kritik_saran_perbaiki IS NOT NULL AND kritik_saran_perbaiki != '' AND status = 'valid'")->fetchColumn();
        echo number_format($countPerbaiki, 0, ',', '.');
        ?>
      </div>
      <div class="stat-sub">Saran perbaikan</div>
    </div>
  </div>
</div>

<!-- Daftar Saran -->
<div class="card border-0 shadow-sm rounded-4 bg-white">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width: 80px;">Tanggal</th>
          <th style="width: 100px;">Cabang</th>
          <th>Tipe Masukan</th>
          <th>Isi Saran/Keluhan</th>
          <th style="width: 70px;">Skor</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($suggestionsData)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Belum ada masukan yang sesuai filter.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($suggestionsData as $s): ?>
        <tr>
          <td>
            <small class="text-muted"><?= date('d/m/Y', strtotime($s['waktu_selesai'])) ?></small><br>
            <small class="text-muted"><?= date('H:i', strtotime($s['waktu_selesai'])) ?></small>
          </td>
          <td>
            <span class="badge bg-light text-dark border"><?= e($s['kode_cabang']) ?></span><br>
            <small class="text-muted"><?= e($s['nama_cabang']) ?></small>
          </td>
          <td>
            <?php if ($s['kritik_saran_suka']): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">Disukai</span>
            <?php endif; ?>
            <?php if ($s['kritik_saran_perbaiki']): ?>
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle mb-1">Perbaiki</span>
            <?php endif; ?>
            <?php if ($s['kritik_saran_lain']): ?>
              <span class="badge bg-info-subtle text-info border border-info-subtle">Lainnya</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($s['kritik_saran_suka']): ?>
              <div class="mb-1 p-2 bg-success-subtle rounded" style="font-size: 0.82rem;">
                <strong class="text-success">Disukai:</strong> <?= e($s['kritik_saran_suka']) ?>
              </div>
            <?php endif; ?>
            <?php if ($s['kritik_saran_perbaiki']): ?>
              <div class="mb-1 p-2 bg-warning-subtle rounded" style="font-size: 0.82rem;">
                <strong class="text-warning">Perbaiki:</strong> <?= e($s['kritik_saran_perbaiki']) ?>
              </div>
            <?php endif; ?>
            <?php if ($s['kritik_saran_lain']): ?>
              <div class="p-2 bg-info-subtle rounded" style="font-size: 0.82rem;">
                <strong class="text-info">Pesan:</strong> <?= e($s['kritik_saran_lain']) ?>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge bg-primary-subtle text-primary border"><?= format_desimal($s['rata_rata_skor']) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPagesSaran > 1): ?>
  <div class="d-flex justify-content-between align-items-center p-3 border-top">
    <small class="text-muted">
      Halaman <?= $pageSaran ?> dari <?= $totalPagesSaran ?> (<?= number_format($totalRowsSaran, 0, ',', '.') ?> data)
    </small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php if ($pageSaran > 1): ?>
        <li class="page-item">
          <a class="page-link" href="?tab=saran&page_saran=<?= $pageSaran - 1 ?>&cabang=<?= $filterBranch ?>&type=<?= $filterType ?>&tahun=<?= $filterYearSaran ?>&bulan=<?= $filterMonthSaran ?>&q_saran=<?= urlencode($searchSaran) ?>">Sebelumnya</a>
        </li>
        <?php endif; ?>
        <?php if ($pageSaran < $totalPagesSaran): ?>
        <li class="page-item">
          <a class="page-link" href="?tab=saran&page_saran=<?= $pageSaran + 1 ?>&cabang=<?= $filterBranch ?>&type=<?= $filterType ?>&tahun=<?= $filterYearSaran ?>&bulan=<?= $filterMonthSaran ?>&q_saran=<?= urlencode($searchSaran) ?>">Berikutnya</a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<?php endif; ?>

<!-- MODAL DETAIL 10 JAWABAN -->
<div class="modal fade" id="modalDetailResponse" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom-0">
        <div>
          <h5 class="modal-title fw-bold text-primary-fastabiq" id="detailModalTitle">Rincian Jawaban Responden</h5>
          <small class="text-muted" id="detailModalSub"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="detailModalBody">
        <div class="text-center py-4"><div class="spinner-border text-primary-fastabiq" role="status"></div></div>
      </div>
    </div>
  </div>
</div>

<script>
let detailModalInstance = null;
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('modalDetailResponse');
  if (el) detailModalInstance = new bootstrap.Modal(el);
});

function openDetailModal(id, token, branch) {
  document.getElementById('detailModalTitle').textContent = `Rincian Jawaban: Cabang ${branch}`;
  document.getElementById('detailModalSub').textContent = `Token Tiket: ${token}`;
  document.getElementById('detailModalBody').innerHTML = `
    <div class="text-center py-4"><div class="spinner-border text-primary-fastabiq" role="status"></div><div class="small text-muted mt-2">Memuat rincian 10 pertanyaan...</div></div>
  `;
  if (detailModalInstance) detailModalInstance.show();

  fetch(`<?= BASE_URL ?>/api/get_response_detail.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        document.getElementById('detailModalBody').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        return;
      }
      let html = `
        <div class="row g-2 mb-3 p-3 bg-light rounded-3 border">
          <div class="col-4"><strong>Rata-rata:</strong> ${data.response.rata_rata_skor} / 4</div>
          <div class="col-4"><strong>% Skor:</strong> ${data.response.persentase_skor}%</div>
          <div class="col-4"><strong>Nilai SS:</strong> ${data.response.jumlah_nilai_5} pertanyaan</div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead class="table-light"><tr><th style="width: 40px;">No</th><th>Aspek Pelayanan</th><th>Teks Pertanyaan</th><th style="width: 110px;" class="text-center">Nilai</th></tr></thead>
            <tbody>
      `;
      const valLabels = {1: 'STS (1)', 2: 'TS (2)', 3: 'S (3)', 4: 'SS (4)', 5: 'SS (4)'};
      data.answers.forEach((ans, idx) => {
        const valText = valLabels[ans.nilai] || ans.nilai;
        html += `
          <tr>
            <td class="text-center">${idx + 1}</td>
            <td><span class="badge bg-light text-dark border">${ans.aspek}</span></td>
            <td>${ans.teks_pertanyaan}</td>
            <td class="text-center fw-bold ${ans.nilai >= 4 ? 'text-success' : (ans.nilai <= 2 ? 'text-danger' : 'text-primary')}">
              ${valText}
            </td>
          </tr>
        `;
      });
      html += `</tbody></table></div>`;
      document.getElementById('detailModalBody').innerHTML = html;
    })
    .catch(err => {
      document.getElementById('detailModalBody').innerHTML = `<div class="alert alert-danger">Gagal memuat detail jawaban.</div>`;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

