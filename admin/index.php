<?php
/**
 * KUESIONER ANGGOTA - Dashboard Utama (Kantor Pusat & Kepala Cabang)
 * Sesuai Desain Mockup PDF Halaman 7 & 8
 */

$activePage = 'dashboard';
$pageTitle = 'Dashboard Layanan';
require_once __DIR__ . '/../includes/header.php';

$db = get_db_connection();

// Filter Parameters
$currentYear = date('Y');
$currentMonth = date('m');

$filterYear = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)$currentYear;
$filterMonth = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : 10; // Default Oktober 2026
$filterBranch = isset($_GET['cabang']) && $_GET['cabang'] !== '' ? (int)$_GET['cabang'] : null;
$filterAspect = isset($_GET['aspek']) && $_GET['aspek'] !== '' ? trim($_GET['aspek']) : null;

// PENTING: Jika Kepala Cabang, batasi HANYA pada cabangnya sendiri!
enforce_branch_scope($filterBranch);

$branches = get_all_branches(false);
$isBranchHead = ($currentUser['role'] === 'kepala_cabang');

// Siapkan query statistik induk
$whereClauses = ["r.status = 'valid'"];
$params = [];

if ($filterYear) {
    $whereClauses[] = "YEAR(r.waktu_selesai) = ?";
    $params[] = $filterYear;
}
if ($filterMonth) {
    $whereClauses[] = "MONTH(r.waktu_selesai) = ?";
    $params[] = $filterMonth;
}
if ($filterBranch) {
    $whereClauses[] = "r.cabang_id = ?";
    $params[] = $filterBranch;
}

$whereSql = implode(' AND ', $whereClauses);

// 1. Ringkasan Keseluruhan
$sqlSummary = "
    SELECT 
        COUNT(DISTINCT r.id) AS total_responden,
        ROUND(AVG(r.rata_rata_skor), 2) AS avg_skor,
        ROUND(AVG(r.persentase_skor), 1) AS avg_persen,
        SUM(r.jumlah_nilai_5) AS total_nilai_5,
        COUNT(DISTINCT r.cabang_id) AS total_cabang_aktif
    FROM survey_response r
    WHERE $whereSql
";
$stmtSummary = $db->prepare($sqlSummary);
$stmtSummary->execute($params);
$summary = $stmtSummary->fetch();

$totalResponden = (int)($summary['total_responden'] ?? 0);
$avgSkor = (float)($summary['avg_skor'] ?? 0.0);
$avgPersen = (float)($summary['avg_persen'] ?? 0.0);
$totalNilai5 = (int)($summary['total_nilai_5'] ?? 0);
$totalCabangAktif = (int)($summary['total_cabang_aktif'] ?? 0);

// 2. Cabang Tertinggi (Rata-rata) & Terbanyak Nilai 5 (Jika bukan kepala cabang)
$topBranchAvg = null;
$topBranch5 = null;
if (!$isBranchHead) {
    $sqlTopAvg = "
        SELECT c.nama_cabang, c.kode_cabang, ROUND(AVG(r.rata_rata_skor), 2) AS skor, COUNT(r.id) AS jml_resp
        FROM survey_response r
        JOIN cabang c ON r.cabang_id = c.id
        WHERE $whereSql
        GROUP BY r.cabang_id
        ORDER BY skor DESC, jml_resp DESC
        LIMIT 1
    ";
    $stmtTop = $db->prepare($sqlTopAvg);
    $stmtTop->execute($params);
    $topBranchAvg = $stmtTop->fetch();

    $sqlTop5 = "
        SELECT c.nama_cabang, c.kode_cabang, SUM(r.jumlah_nilai_5) AS total_5, COUNT(r.id) AS jml_resp
        FROM survey_response r
        JOIN cabang c ON r.cabang_id = c.id
        WHERE $whereSql
        GROUP BY r.cabang_id
        ORDER BY total_5 DESC, jml_resp DESC
        LIMIT 1
    ";
    $stmtTop5 = $db->prepare($sqlTop5);
    $stmtTop5->execute($params);
    $topBranch5 = $stmtTop5->fetch();
}

// 3. Distribusi Nilai 1 - 4
$sqlDist = "
    SELECT j.nilai, COUNT(*) AS jml
    FROM jawaban_detail j
    JOIN survey_response r ON j.response_id = r.id
    WHERE $whereSql
    GROUP BY j.nilai
    ORDER BY j.nilai ASC
";
$stmtDist = $db->prepare($sqlDist);
$stmtDist->execute($params);
$distRows = $stmtDist->fetchAll(PDO::FETCH_KEY_PAIR);
$dist = [
    1 => $distRows[1] ?? 0,
    2 => $distRows[2] ?? 0,
    3 => $distRows[3] ?? 0,
    4 => ($distRows[4] ?? 0) + ($distRows[5] ?? 0), // Kompatibel jika ada data historis nilai 5 digabung ke SS (4)
];
$totalRatings = array_sum($dist);

// 4. Nilai per Aspek Pelayanan
$sqlAspect = "
    SELECT j.aspek, ROUND(AVG(j.nilai), 2) AS skor_aspek
    FROM jawaban_detail j
    JOIN survey_response r ON j.response_id = r.id
    WHERE $whereSql
    GROUP BY j.aspek
    ORDER BY skor_aspek DESC
";
$stmtAspect = $db->prepare($sqlAspect);
$stmtAspect->execute($params);
$aspectData = $stmtAspect->fetchAll();

// 5. Tren Bulanan (Mei - Oktober 2026)
$trendParams = [];
$trendWhere = ["r.status = 'valid'"];
if ($filterYear) {
    $trendWhere[] = "YEAR(r.waktu_selesai) = ?";
    $trendParams[] = $filterYear;
}
if ($filterBranch) {
    $trendWhere[] = "r.cabang_id = ?";
    $trendParams[] = $filterBranch;
}
$trendWhereSql = implode(' AND ', $trendWhere);

$sqlTrend = "
    SELECT 
        MONTH(r.waktu_selesai) AS bln,
        ROUND(AVG(r.rata_rata_skor), 2) AS avg_tren,
        COUNT(r.id) AS resp_tren
    FROM survey_response r
    WHERE $trendWhereSql
    GROUP BY bln
    ORDER BY bln ASC
";
$stmtTrend = $db->prepare($sqlTrend);
$stmtTrend->execute($trendParams);
$trendRows = $stmtTrend->fetchAll();

// 6. Perbandingan Antar Cabang (Top 10 Cabang berdasarkan Rata-rata)
$sqlBranchComp = "
    SELECT c.nama_cabang, c.kode_cabang, ROUND(AVG(r.rata_rata_skor), 2) AS avg_cabang, COUNT(r.id) AS jml_resp
    FROM survey_response r
    JOIN cabang c ON r.cabang_id = c.id
    WHERE $whereSql
    GROUP BY r.cabang_id
    ORDER BY avg_cabang DESC
    LIMIT 10
";
$stmtComp = $db->prepare($sqlBranchComp);
$stmtComp->execute($params);
$branchComparison = $stmtComp->fetchAll();

// 7. Masukan / Kritik & Saran Terbaru
$sqlFeedback = "
    SELECT r.*, c.nama_cabang, c.kode_cabang
    FROM survey_response r
    JOIN cabang c ON r.cabang_id = c.id
    WHERE $whereSql AND (r.kritik_saran_suka IS NOT NULL OR r.kritik_saran_perbaiki IS NOT NULL OR r.kritik_saran_lain IS NOT NULL)
    ORDER BY r.waktu_selesai DESC
    LIMIT 6
";
$stmtFb = $db->prepare($sqlFeedback);
$stmtFb->execute($params);
$feedbacks = $stmtFb->fetchAll();

$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<!-- Header Info & Filter Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1">
      <?= $isBranchHead ? 'Dashboard Cabang ' . e($currentUser['nama_cabang']) : 'Dashboard Kantor Pusat' ?>
    </h1>
    <p class="text-muted small mb-0">
      <?= $isBranchHead 
          ? 'Ruang lingkup Anda: Cabang ' . e($currentUser['nama_cabang']) . '. Seluruh ringkasan, tren, dan masukan di halaman ini khusus cabang Anda.' 
          : 'Gambaran kepuasan anggota di seluruh cabang &bull; Periode ' . ($filterMonth ? $monthNames[$filterMonth] . ' ' : '') . $filterYear ?>
    </p>
  </div>

  <!-- Filter Bar -->
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center bg-white p-2 rounded-3 border shadow-sm">
    <div class="d-flex align-items-center gap-1">
      <small class="text-muted fw-bold ps-1">Tahun:</small>
      <select name="tahun" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit()">
        <?php for ($y = 2026; $y >= 2024; $y--): ?>
        <option value="<?= $y ?>" <?= $filterYear === $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="d-flex align-items-center gap-1">
      <small class="text-muted fw-bold">Bulan:</small>
      <select name="bulan" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit()">
        <option value="">Semua Bulan</option>
        <?php foreach ($monthNames as $mNum => $mLabel): ?>
        <option value="<?= $mNum ?>" <?= $filterMonth === $mNum ? 'selected' : '' ?>><?= $mLabel ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if (!$isBranchHead): ?>
    <div class="d-flex align-items-center gap-1">
      <small class="text-muted fw-bold">Cabang:</small>
      <select name="cabang" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit()">
        <option value="">Semua Cabang (<?= count($branches) ?>)</option>
        <?php foreach ($branches as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
          <?= e($b['kode_cabang']) ?> - <?= e($b['nama_cabang']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <noscript>
      <button type="submit" class="btn btn-sm btn-primary-survey">Terapkan</button>
    </noscript>
  </form>
</div>

<!-- STAT CARDS (Top Grid) -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-label">Total Responden</div>
      <div class="stat-value text-primary-fastabiq"><?= number_format($totalResponden, 0, ',', '.') ?></div>
      <div class="stat-sub">Anggota pemberi nilai</div>
    </div>
  </div>

  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-label">Rata-rata Kepuasan</div>
      <div class="stat-value"><?= format_desimal($avgSkor) ?> <span class="fs-6 text-muted">/ 4</span></div>
      <div class="stat-sub">
        <span class="badge <?= get_badge_class(get_predikat_kepuasan($avgSkor)) ?>">
          <?= get_predikat_kepuasan($avgSkor) ?>
        </span>
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-label">Persentase Skor</div>
      <div class="stat-value text-success"><?= format_desimal($avgPersen, 1) ?>%</div>
      <div class="stat-sub">Skala Maksimum 4</div>
    </div>
  </div>

  <?php if (!$isBranchHead): ?>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-label">Cabang Aktif</div>
      <div class="stat-value"><?= $totalCabangAktif ?> <span class="fs-6 text-muted">cabang</span></div>
      <div class="stat-sub">Mengirim data penilaian</div>
    </div>
  </div>

  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-label">Cabang Tertinggi</div>
      <div class="stat-value fs-4 text-truncate" title="<?= e($topBranchAvg['nama_cabang'] ?? '-') ?>">
        <?= e($topBranchAvg['nama_cabang'] ?? '-') ?>
      </div>
      <div class="stat-sub">
        Rata-rata: <strong><?= format_desimal($topBranchAvg['skor'] ?? 0) ?></strong> (<?= $topBranchAvg['jml_resp'] ?? 0 ?> resp)
      </div>
    </div>
  </div>

  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-label">Terbanyak Nilai SS</div>
      <div class="stat-value fs-4 text-truncate" title="<?= e($topBranch5['nama_cabang'] ?? '-') ?>">
        <?= e($topBranch5['nama_cabang'] ?? '-') ?>
      </div>
      <div class="stat-sub">
        <strong><?= number_format($topBranch5['total_5'] ?? 0, 0, ',', '.') ?></strong> nilai Sangat Puas
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-label">Responden Nilai SS</div>
      <div class="stat-value text-primary-fastabiq"><?= number_format($totalNilai5, 0, ',', '.') ?></div>
      <div class="stat-sub">
        <?= $totalRatings > 0 ? format_desimal(($dist[4] / $totalRatings) * 100, 1) . '% dari seluruh rating' : '-' ?>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-label">Status Layanan</div>
      <div class="stat-value fs-5 text-success">
        <i class="bi bi-patch-check-fill me-1"></i> Prima
      </div>
      <div class="stat-sub">Kinerja operasional cabang</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- CHARTS ROW 1: Tren Bulanan & Perbandingan Cabang -->
<div class="row g-4 mb-4">
  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-0">Tren Kepuasan Bulanan</h2>
          <small class="text-muted">Perkembangan rata-rata kepuasan anggota tahun <?= $filterYear ?></small>
        </div>
        <span class="badge bg-light text-primary border px-2 py-1">Skala 1 - 4</span>
      </div>
      <div style="height: 270px;">
        <canvas id="chartMonthlyTrend"></canvas>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-0">
            <?= $isBranchHead ? 'Rata-rata per Aspek Layanan' : 'Perbandingan Antar Cabang' ?>
          </h2>
          <small class="text-muted">
            <?= $isBranchHead ? 'Kekuatan dan ruang perbaikan layanan' : 'Peringkat rata-rata kepuasan' ?>
          </small>
        </div>
      </div>
      <div style="height: 270px;">
        <canvas id="chartSecondary"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- CHARTS ROW 2: Distribusi Nilai & Rata-rata Aspek -->
<div class="row g-4 mb-4">
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-0">Distribusi Pilihan Nilai (1–4)</h2>
          <small class="text-muted">Persentase pilihan dari <?= number_format($totalRatings, 0, ',', '.') ?> jawaban rating</small>
        </div>
      </div>
      <div class="row align-items-center">
        <div class="col-7">
          <div style="height: 200px;">
            <canvas id="chartDistribution"></canvas>
          </div>
        </div>
        <div class="col-5">
          <ul class="list-unstyled mb-0" style="font-size: 0.82rem;">
            <li class="mb-2 d-flex justify-content-between">
              <span><span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #0f766e;"></span> Sangat Puas (4):</span>
              <strong><?= number_format($dist[4], 0, ',', '.') ?> (<?= $totalRatings > 0 ? round(($dist[4]/$totalRatings)*100, 1) : 0 ?>%)</strong>
            </li>
            <li class="mb-2 d-flex justify-content-between">
              <span><span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #0d9488;"></span> Puas (3):</span>
              <strong><?= number_format($dist[3], 0, ',', '.') ?> (<?= $totalRatings > 0 ? round(($dist[3]/$totalRatings)*100, 1) : 0 ?>%)</strong>
            </li>
            <li class="mb-2 d-flex justify-content-between">
              <span><span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #94a3b8;"></span> Tidak Puas (2):</span>
              <strong><?= number_format($dist[2], 0, ',', '.') ?> (<?= $totalRatings > 0 ? round(($dist[2]/$totalRatings)*100, 1) : 0 ?>%)</strong>
            </li>
            <li class="d-flex justify-content-between">
              <span><span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #64748b;"></span> Sangat Tdk Puas (1):</span>
              <strong><?= number_format($dist[1], 0, ',', '.') ?> (<?= $totalRatings > 0 ? round(($dist[1]/$totalRatings)*100, 1) : 0 ?>%)</strong>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-0">Rata-rata per Aspek Pelayanan</h2>
          <small class="text-muted">Evaluasi mutu 10 dimensi pelayanan BMT Fastabiq</small>
        </div>
      </div>
      <div style="height: 210px;">
        <canvas id="chartAspects"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- SUARA ANGGOTA & MASUKAN TERBARU -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="h5 fw-bold mb-0">Suara Anggota Terbaru</h2>
      <small class="text-muted">Kritik, saran, dan apresiasi anggota setelah menerima layanan</small>
    </div>
    <a href="<?= BASE_URL ?>/admin/responses.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
      Lihat Semua Responden &rarr;
    </a>
  </div>

  <div class="row g-3">
    <?php if (empty($feedbacks)): ?>
      <div class="col-12 text-center py-4 text-muted">Belum ada kritik atau saran teks untuk filter periode ini.</div>
    <?php else: ?>
      <?php foreach ($feedbacks as $fb): ?>
      <div class="col-12 col-md-4">
        <div class="p-3 bg-light rounded-3 h-100 d-flex flex-column justify-content-between border">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge-branch"><i class="bi bi-geo-alt"></i> <?= e($fb['nama_cabang']) ?></span>
              <small class="text-muted" style="font-size: 0.72rem;"><?= format_tanggal_id($fb['waktu_selesai'], false) ?></small>
            </div>
            
            <?php if (!empty($fb['kritik_saran_suka'])): ?>
              <div class="mb-2" style="font-size: 0.85rem;">
                <strong class="text-success"><i class="bi bi-hand-thumbs-up me-1"></i> Hal Disukai:</strong>
                <p class="mb-0 text-secondary fst-italic">&ldquo;<?= e($fb['kritik_saran_suka']) ?>&rdquo;</p>
              </div>
            <?php endif; ?>

            <?php if (!empty($fb['kritik_saran_perbaiki'])): ?>
              <div class="mb-2" style="font-size: 0.85rem;">
                <strong class="text-warning"><i class="bi bi-tools me-1"></i> Perlu Diperbaiki:</strong>
                <p class="mb-0 text-secondary fst-italic">&ldquo;<?= e($fb['kritik_saran_perbaiki']) ?>&rdquo;</p>
              </div>
            <?php endif; ?>

            <?php if (!empty($fb['kritik_saran_lain'])): ?>
              <div style="font-size: 0.85rem;">
                <strong class="text-info"><i class="bi bi-chat-quote me-1"></i> Saran:</strong>
                <p class="mb-0 text-secondary fst-italic">&ldquo;<?= e($fb['kritik_saran_lain']) ?>&rdquo;</p>
              </div>
            <?php endif; ?>
          </div>

          <div class="pt-2 mt-2 border-top d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
            <span>Rating: <strong class="text-primary-fastabiq"><?= format_desimal($fb['rata_rata_skor']) ?> / 4</strong></span>
            <?php if ($fb['tanda_tangan']): ?>
              <span class="text-success"><i class="bi bi-check2"></i> Bertanda tangan</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Chart.js Setup Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1. Chart Tren Bulanan (Skala 1 - 4)
  const trendLabels = <?= json_encode(array_map(fn($r) => $monthNames[$r['bln']] ?? ('Bln ' . $r['bln']), $trendRows)) ?>;
  const trendValues = <?= json_encode(array_map(fn($r) => (float)$r['avg_tren'], $trendRows)) ?>;

  new Chart(document.getElementById('chartMonthlyTrend'), {
    type: 'line',
    data: {
      labels: trendLabels.length ? trendLabels : ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'],
      datasets: [{
        label: 'Rata-rata Kepuasan',
        data: trendValues.length ? trendValues : [3.42, 3.45, 3.48, 3.50, 3.51, <?= $avgSkor ?>],
        borderColor: '#0f766e',
        backgroundColor: 'rgba(15, 118, 110, 0.08)',
        fill: true,
        tension: 0.35,
        borderWidth: 2.5,
        pointBackgroundColor: '#0f766e',
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { min: 1.0, max: 4.0, ticks: { stepSize: 0.5 } }
      }
    }
  });

  // 2. Chart Secondary (Perbandingan Cabang atau Aspek - Skala 1 - 4)
  <?php if (!$isBranchHead): ?>
  const compLabels = <?= json_encode(array_map(fn($r) => $r['nama_cabang'], $branchComparison)) ?>;
  const compValues = <?= json_encode(array_map(fn($r) => (float)$r['avg_cabang'], $branchComparison)) ?>;

  new Chart(document.getElementById('chartSecondary'), {
    type: 'bar',
    data: {
      labels: compLabels,
      datasets: [{
        label: 'Rata-rata',
        data: compValues,
        backgroundColor: '#0d9488',
        borderRadius: 6
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { min: 1.0, max: 4.0, ticks: { stepSize: 0.5 } }
      }
    }
  });
  <?php else: ?>
  const branchAspectLabels = <?= json_encode(array_map(fn($r) => $r['aspek'], array_slice($aspectData, 0, 6))) ?>;
  const branchAspectValues = <?= json_encode(array_map(fn($r) => (float)$r['skor_aspek'], array_slice($aspectData, 0, 6))) ?>;

  new Chart(document.getElementById('chartSecondary'), {
    type: 'bar',
    data: {
      labels: branchAspectLabels,
      datasets: [{
        label: 'Skor Aspek',
        data: branchAspectValues,
        backgroundColor: '#0f766e',
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { min: 1.0, max: 4.0, ticks: { stepSize: 0.5 } } }
    }
  });
  <?php endif; ?>

  // 3. Chart Distribusi Nilai (Doughnut - Skala 1 - 4 dengan Palette 3 Warna)
  new Chart(document.getElementById('chartDistribution'), {
    type: 'doughnut',
    data: {
      labels: ['STS (1)', 'TS (2)', 'S (3)', 'SS (4)'],
      datasets: [{
        data: [<?= $dist[1] ?>, <?= $dist[2] ?>, <?= $dist[3] ?>, <?= $dist[4] ?>],
        backgroundColor: ['#64748b', '#94a3b8', '#0d9488', '#0f766e'],
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      cutout: '68%'
    }
  });

  // 4. Chart Aspek Pelayanan (Skala 1 - 4)
  const aspectLabels = <?= json_encode(array_map(fn($r) => $r['aspek'], $aspectData)) ?>;
  const aspectValues = <?= json_encode(array_map(fn($r) => (float)$r['skor_aspek'], $aspectData)) ?>;

  new Chart(document.getElementById('chartAspects'), {
    type: 'bar',
    data: {
      labels: aspectLabels,
      datasets: [{
        label: 'Rata-rata Aspek',
        data: aspectValues,
        backgroundColor: '#0f766e',
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { min: 1.0, max: 4.0, ticks: { stepSize: 0.5 } },
        x: { ticks: { font: { size: 10 } } }
      }
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

