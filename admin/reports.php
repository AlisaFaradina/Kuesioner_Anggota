<?php
/**
 * KUESIONER ANGGOTA - Laporan Penilaian Layanan
 * Sesuai Dokumen Bagian 6E & Desain Mockup PDF Halaman 11
 */

$activePage = 'reports';
$pageTitle = 'Laporan Penilaian';
require_once __DIR__ . '/../includes/header.php';

$db = get_db_connection();

// Periode Default: Bulan Oktober 2026
$defaultStart = date('Y') . '-10-01';
$defaultEnd   = date('Y') . '-10-31';

$startDate = $_GET['start_date'] ?? $defaultStart;
$endDate   = $_GET['end_date'] ?? $defaultEnd;
$filterBranch = isset($_GET['cabang']) && $_GET['cabang'] !== '' ? (int)$_GET['cabang'] : null;

// Batasi cabang jika Kepala Cabang
enforce_branch_scope($filterBranch);

$branches = get_all_branches(false);
$isBranchHead = ($currentUser['role'] === 'kepala_cabang');

// Query Laporan
$where = ["r.status = 'valid'"];
$params = [];

if ($startDate) {
    $where[] = "DATE(r.waktu_selesai) >= ?";
    $params[] = $startDate;
}
if ($endDate) {
    $where[] = "DATE(r.waktu_selesai) <= ?";
    $params[] = $endDate;
}
if ($filterBranch) {
    $where[] = "r.cabang_id = ?";
    $params[] = $filterBranch;
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT 
        c.id AS cabang_id,
        c.kode_cabang,
        c.nama_cabang,
        c.kepala_cabang,
        COUNT(DISTINCT r.id) AS total_responden,
        -- Akumulasi Jawaban Skala 1 - 4: SS (4), S (3), TS (2), STS (1)
        COUNT(CASE WHEN j.nilai >= 4 THEN 1 END) AS count_ss,
        COUNT(CASE WHEN j.nilai = 3 THEN 1 END) AS count_s,
        COUNT(CASE WHEN j.nilai = 2 THEN 1 END) AS count_ts,
        COUNT(CASE WHEN j.nilai = 1 THEN 1 END) AS count_sts,
        COUNT(j.id) AS total_jawaban
    FROM cabang c
    LEFT JOIN survey_response r ON c.id = r.cabang_id AND $whereSql
    LEFT JOIN jawaban_detail j ON r.id = j.response_id
    WHERE c.status = 'aktif' " . ($filterBranch ? "AND c.id = $filterBranch" : "") . "
    GROUP BY c.id
    ORDER BY c.kode_cabang ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rawReportRows = $stmt->fetchAll();

// Hitung skor tertimbang & akumulasi sesuai rumus:
// Akumulasi STS = STS × 1, TS = TS × 2, S = S × 3, SS = SS × 4
// Rata-rata = Total Skor Tertimbang ÷ Total Jawaban
// % Skor = (Rata-rata ÷ 4) × 100%
$reportRows = [];
$grandResponden = 0;
$grandTotalWeighted = 0;
$grandTotalRatings = 0;
$grandTotalSS = 0;

$grandSumSS = 0;
$grandSumS = 0;
$grandSumTS = 0;
$grandSumSTS = 0;

foreach ($rawReportRows as $r) {
    $cSS = (int)$r['count_ss'];
    $cS = (int)$r['count_s'];
    $cTS = (int)$r['count_ts'];
    $cSTS = (int)$r['count_sts'];
    $totJawaban = $cSS + $cS + $cTS + $cSTS;
    $weightedScore = ($cSS * 4) + ($cS * 3) + ($cTS * 2) + ($cSTS * 1);
    
    $avgSkor = $totJawaban > 0 ? round($weightedScore / $totJawaban, 2) : 0.00;
    $pctSkor = $totJawaban > 0 ? round(($avgSkor / 4) * 100, 1) : 0.0;
    $pctSS = $totJawaban > 0 ? round(($cSS / $totJawaban) * 100, 1) : 0.0;

    $grandResponden += (int)$r['total_responden'];
    $grandTotalWeighted += $weightedScore;
    $grandTotalRatings += $totJawaban;
    $grandTotalSS += $cSS;
    $grandSumSS += $cSS;
    $grandSumS += $cS;
    $grandSumTS += $cTS;
    $grandSumSTS += $cSTS;

    $reportRows[] = array_merge($r, [
        'count_ss' => $cSS,
        'count_s' => $cS,
        'count_ts' => $cTS,
        'count_sts' => $cSTS,
        'avg_skor' => $avgSkor,
        'persentase_skor' => $pctSkor,
        'pct_nilai_ss' => $pctSS,
        'total_jawaban' => $totJawaban
    ]);
}

$grandAvg = $grandTotalRatings > 0 ? round($grandTotalWeighted / $grandTotalRatings, 2) : 0.00;
$grandPctSkor = $grandAvg > 0 ? round(($grandAvg / 4) * 100, 1) : 0.0;
$grandPctSS = $grandTotalRatings > 0 ? round(($grandTotalSS / $grandTotalRatings) * 100, 1) : 0.0;

$branchNameDisplay = 'Seluruh Cabang (' . count($reportRows) . ')';
if ($filterBranch) {
    $br = find_branch($filterBranch);
    if ($br) $branchNameDisplay = $br['nama_cabang'] . ' (' . $br['kode_cabang'] . ')';
}

$exportQuery = http_build_query([
    'start_date' => $startDate,
    'end_date'   => $endDate,
    'cabang'     => $filterBranch ?? ''
]);
?>

<!-- Header Info & Filter Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 no-print">
  <div>
    <h1 class="h3 fw-bold mb-1">Laporan Penilaian</h1>
    <p class="text-muted small mb-0">Tinjau ringkasan kepuasan anggota sebelum mengunduh atau mencetak laporan resmi.</p>
  </div>

  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center bg-white p-2 rounded-3 border shadow-sm">
    <div class="d-flex align-items-center gap-1">
      <small class="text-muted fw-bold ps-1">Dari:</small>
      <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-light" value="<?= e($startDate) ?>">
    </div>

    <div class="d-flex align-items-center gap-1">
      <small class="text-muted fw-bold">Sampai:</small>
      <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-light" value="<?= e($endDate) ?>">
    </div>

    <?php if (!$isBranchHead): ?>
    <div class="d-flex align-items-center gap-1">
      <small class="text-muted fw-bold">Cabang:</small>
      <select name="cabang" class="form-select form-select-sm border-0 bg-light">
        <option value="">Semua Cabang</option>
        <?php foreach ($branches as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
          <?= e($b['kode_cabang']) ?> - <?= e($b['nama_cabang']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-sm btn-primary-survey px-3">Tampilkan Laporan</button>
  </form>
</div>

<!-- PRATINJAU DOKUMEN LAPORAN (Sesuai PDF Mockup) -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4" id="printableArea">
  <!-- Kop Laporan -->
  <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="fs-3">✨</span>
        <span class="fw-bold fs-4 text-primary-fastabiq">KUESIONER ANGGOTA</span>
      </div>
      <h2 class="h5 fw-bold mb-1">Laporan Kepuasan Layanan Anggota</h2>
      <div class="text-muted small">KSPPS BMT Fastabiq Khoiro Ummah &bull; Kantor Pusat Pati</div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex gap-2 no-print">
      <a href="<?= BASE_URL ?>/admin/reports_export_excel.php?<?= $exportQuery ?>" class="btn btn-sm btn-success rounded-pill px-3">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download Excel
      </a>
      <a href="<?= BASE_URL ?>/admin/reports_export_pdf.php?<?= $exportQuery ?>" target="_blank" class="btn btn-sm btn-danger rounded-pill px-3">
        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
      </a>
      <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Cetak
      </button>
    </div>
  </div>

  <!-- Meta Info Grid -->
  <div class="row g-3 p-3 bg-light rounded-3 mb-4 border" style="font-size: 0.88rem;">
    <div class="col-6 col-md-3">
      <span class="text-muted d-block small">Nama Cabang:</span>
      <strong><?= e($branchNameDisplay) ?></strong>
    </div>
    <div class="col-6 col-md-3">
      <span class="text-muted d-block small">Periode Evaluasi:</span>
      <strong><?= format_tanggal_id($startDate) ?> &ndash; <?= format_tanggal_id($endDate) ?></strong>
    </div>
    <div class="col-6 col-md-3">
      <span class="text-muted d-block small">Tanggal Cetak:</span>
      <strong><?= format_tanggal_id(date('Y-m-d')) ?></strong>
    </div>
    <div class="col-6 col-md-3">
      <span class="text-muted d-block small">Jumlah Responden:</span>
      <strong class="text-primary-fastabiq"><?= number_format($grandResponden, 0, ',', '.') ?> anggota</strong>
    </div>
  </div>

  <!-- Tabel Data Laporan -->
  <div class="table-responsive mb-4">
    <table class="table table-bordered table-hover align-middle table-print" style="font-size: 0.85rem;">
      <thead class="table-light text-center" style="font-size: 0.8rem; letter-spacing: 0.03em;">
        <tr>
          <th rowspan="2" style="width: 45px;">No.</th>
          <th rowspan="2">Nama Cabang</th>
          <th rowspan="2" style="width: 95px;">Responden</th>
          <th colspan="4" class="table-primary">Akumulasi Jawaban</th>
          <th rowspan="2" style="width: 85px;">Rata-rata</th>
          <th rowspan="2" style="width: 75px;">% Skor</th>
          <th rowspan="2" style="width: 75px;">% Nilai SS</th>
          <th rowspan="2" style="width: 110px;">Predikat</th>
        </tr>
        <tr>
          <th style="width: 55px; background: #e0f2fe;">SS (4)</th>
          <th style="width: 55px; background: #e0f2fe;">S (3)</th>
          <th style="width: 55px; background: #e0f2fe;">TS (2)</th>
          <th style="width: 55px; background: #e0f2fe;">STS (1)</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1;
        foreach ($reportRows as $row): 
          $hasData = ($row['total_responden'] > 0);
          $predikat = $hasData ? get_predikat_kepuasan($row['avg_skor']) : '-';
        ?>
        <tr>
          <td class="text-center"><?= $no++ ?></td>
          <td>
            <strong><?= e($row['nama_cabang']) ?></strong>
            <span class="text-muted small ms-1">(<?= e($row['kode_cabang']) ?>)</span>
          </td>
          <td class="text-center fw-bold"><?= number_format($row['total_responden'], 0, ',', '.') ?></td>
          <td class="text-center text-success fw-bold"><?= $hasData ? number_format($row['count_ss'], 0, ',', '.') : '-' ?></td>
          <td class="text-center"><?= $hasData ? number_format($row['count_s'], 0, ',', '.') : '-' ?></td>
          <td class="text-center"><?= $hasData ? number_format($row['count_ts'], 0, ',', '.') : '-' ?></td>
          <td class="text-center text-danger"><?= $hasData ? number_format($row['count_sts'], 0, ',', '.') : '-' ?></td>
          <td class="text-center fw-bold text-primary-fastabiq">
            <?= $hasData ? format_desimal($row['avg_skor']) : '-' ?>
          </td>
          <td class="text-center"><?= $hasData ? format_desimal($row['persentase_skor'], 1) . '%' : '-' ?></td>
          <td class="text-center text-success fw-bold"><?= $hasData ? format_desimal($row['pct_nilai_ss'], 1) . '%' : '-' ?></td>
          <td class="text-center">
            <?php if ($hasData): ?>
              <span class="badge <?= get_badge_class($predikat) ?>"><?= $predikat ?></span>
            <?php else: ?>
              <span class="text-muted small">Nirdata</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light fw-bold" style="font-size: 0.88rem;">
        <tr>
          <td colspan="2" class="text-end">Total / Rata-rata Keseluruhan:</td>
          <td class="text-center text-primary-fastabiq"><?= number_format($grandResponden, 0, ',', '.') ?></td>
          <td class="text-center text-success"><?= number_format($grandSumSS, 0, ',', '.') ?></td>
          <td class="text-center"><?= number_format($grandSumS, 0, ',', '.') ?></td>
          <td class="text-center"><?= number_format($grandSumTS, 0, ',', '.') ?></td>
          <td class="text-center text-danger"><?= number_format($grandSumSTS, 0, ',', '.') ?></td>
          <td class="text-center text-primary-fastabiq fs-6"><?= format_desimal($grandAvg) ?></td>
          <td class="text-center text-success"><?= format_desimal($grandPctSkor, 1) ?>%</td>
          <td class="text-center text-success"><?= format_desimal($grandPctSS, 1) ?>%</td>
          <td class="text-center">
            <span class="badge <?= get_badge_class(get_predikat_kepuasan($grandAvg)) ?>">
              <?= get_predikat_kepuasan($grandAvg) ?>
            </span>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- Catatan & Tanda Tangan Pengesahan (Sesuai Spreadsheet BMT Fastabiq) -->
  <div class="row pt-3 align-items-end">
    <div class="col-8">
      <div class="text-muted small">
        <strong>Catatan Perhitungan:</strong><br>
        1. Rata-rata keseluruhan dibobot menurut jumlah responden valid tiap cabang.<br>
        2. % Skor = (Rata-rata kepuasan &divide; 5) &times; 100%. Target kepuasan KPI BMT Fastabiq &ge; 95%.<br>
        3. % Nilai 5 = (Jumlah responden yang memberikan rating Sangat Puas &divide; total responden) &times; 100%.<br>
        4. Data terenkripsi dan disimpan terpisah antar cabang demi integritas informasi.
      </div>
    </div>
    <div class="col-4 text-center">
      <div class="small text-secondary mb-1">Pati, <?= format_tanggal_id(date('Y-m-d')) ?></div>
      <div class="small fw-bold text-dark">Petugas Admisi / Layanan Anggota,</div>
      <div style="height: 60px;"></div>
      <div class="fw-bold text-decoration-underline text-dark">Endang K</div>
      <div class="small text-muted">Bagian Operasional &amp; Layanan</div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

