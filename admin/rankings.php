<?php
/**
 * KUESIONER ANGGOTA - Peringkat Cabang (Rankings)
 * Sesuai Dokumen Bagian 7 & Desain Mockup PDF Halaman 9
 */

$activePage = 'rankings';
$pageTitle = 'Peringkat Cabang';
require_once __DIR__ . '/../includes/header.php';

$db = get_db_connection();

$filterYear = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$filterMonth = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : 10; // Default Oktober 2026
$mode = $_GET['mode'] ?? 'avg'; // 'avg' (Rata-rata) atau 'star4' / 'star5' (% Nilai SS)

$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Query Data Peringkat Seluruh Cabang (Skala 1 - 4)
$where = ["r.status = 'valid'"];
$params = [];

if ($filterYear) {
    $where[] = "YEAR(r.waktu_selesai) = ?";
    $params[] = $filterYear;
}
if ($filterMonth) {
    $where[] = "MONTH(r.waktu_selesai) = ?";
    $params[] = $filterMonth;
}

$whereSql = implode(' AND ', $where);

// Ambil agregasi per cabang
$sql = "
    SELECT 
        c.id, c.kode_cabang, c.nama_cabang, c.kepala_cabang,
        COUNT(r.id) AS total_responden,
        ROUND(AVG(r.rata_rata_skor), 4) AS raw_avg_skor,
        ROUND(AVG(r.rata_rata_skor), 2) AS display_avg_skor,
        ROUND(AVG(r.persentase_skor), 1) AS persentase_skor,
        SUM(r.jumlah_nilai_5) AS total_nilai_ss,
        SUM(r.jumlah_pertanyaan) AS total_jawaban,
        ROUND((SUM(r.jumlah_nilai_5) / NULLIF(SUM(r.jumlah_pertanyaan), 0)) * 100, 1) AS persentase_nilai_ss
    FROM cabang c
    LEFT JOIN survey_response r ON c.id = r.cabang_id AND $whereSql
    WHERE c.status = 'aktif'
    GROUP BY c.id
";

// Pengurutan berdasarkan mode
if ($mode === 'star4' || $mode === 'star5') {
    $sql .= " ORDER BY persentase_nilai_ss DESC, total_nilai_ss DESC, raw_avg_skor DESC, total_responden DESC";
} else {
    $sql .= " ORDER BY raw_avg_skor DESC, total_responden DESC, persentase_nilai_ss DESC";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rankings = $stmt->fetchAll();

$top1 = $rankings[0] ?? null;
$top2 = $rankings[1] ?? null;
$top3 = $rankings[2] ?? null;
?>

<!-- Header Info & Filter Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Peringkat Cabang</h1>
    <p class="text-muted small mb-0">
      Apresiasi pelayanan terbaik, dengan jumlah responden sebagai konteks yang berimbang.
    </p>
  </div>

  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center bg-white p-2 rounded-3 border shadow-sm">
    <input type="hidden" name="mode" value="<?= e($mode) ?>">

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

    <noscript><button type="submit" class="btn btn-sm btn-primary-survey">Terapkan</button></noscript>
  </form>
</div>

<!-- Tabs Pilihan Metode Peringkat -->
<div class="d-flex gap-2 mb-4">
  <a href="?tahun=<?= $filterYear ?>&bulan=<?= $filterMonth ?>&mode=avg" 
     class="btn <?= $mode === 'avg' ? 'btn-primary-survey' : 'btn-outline-secondary bg-white' ?> rounded-pill px-4 py-2 fw-bold" style="font-size: 0.9rem;">
    <i class="bi bi-star-half me-1"></i> Berdasarkan Rata-rata
  </a>
  <a href="?tahun=<?= $filterYear ?>&bulan=<?= $filterMonth ?>&mode=star4" 
     class="btn <?= in_array($mode, ['star4', 'star5']) ? 'btn-primary-survey' : 'btn-outline-secondary bg-white' ?> rounded-pill px-4 py-2 fw-bold" style="font-size: 0.9rem;">
    <i class="bi bi-star-fill text-warning me-1"></i> Berdasarkan % Nilai SS (Sangat Puas)
  </a>
</div>

<!-- PODIUM TOP 3 (Sesuai Skala 1 - 4) -->
<?php if ($top1 && $top1['total_responden'] > 0): 
  $isStarMode = in_array($mode, ['star4', 'star5']);
?>
<div class="podium-container">
  <!-- Peringkat 2 (Silver) -->
  <?php if ($top2 && $top2['total_responden'] > 0): ?>
  <div class="podium-step silver">
    <span class="podium-rank-badge">Peringkat 2</span>
    <h3 class="fw-bold mb-1" style="font-size: 1.15rem;"><?= e($top2['nama_cabang']) ?></h3>
    <div class="text-muted small mb-2"><?= e($top2['kode_cabang']) ?></div>
    <div class="stat-value fs-2 text-secondary mb-1">
      <?= $isStarMode ? format_desimal($top2['persentase_nilai_ss'], 1) . '%' : format_desimal($top2['display_avg_skor']) ?>
      <?php if (!$isStarMode): ?><span class="fs-6 text-muted">/ 4</span><?php endif; ?>
    </div>
    <div class="badge bg-light text-secondary border">
      <?= number_format($top2['total_responden'], 0, ',', '.') ?> responden
    </div>
  </div>
  <?php endif; ?>

  <!-- Peringkat 1 (Gold) -->
  <div class="podium-step gold">
    <div class="fs-1 mb-1">👑</div>
    <span class="podium-rank-badge">Peringkat 1</span>
    <h3 class="fw-bold mb-1" style="font-size: 1.35rem; color: #92400e;"><?= e($top1['nama_cabang']) ?></h3>
    <div class="text-muted small mb-2"><?= e($top1['kode_cabang']) ?></div>
    <div class="stat-value fs-1 text-primary-fastabiq mb-1">
      <?= $isStarMode ? format_desimal($top1['persentase_nilai_ss'], 1) . '%' : format_desimal($top1['display_avg_skor']) ?>
      <?php if (!$isStarMode): ?><span class="fs-6 text-muted">/ 4</span><?php endif; ?>
    </div>
    <div class="badge bg-warning-subtle text-dark border border-warning">
      <?= number_format($top1['total_responden'], 0, ',', '.') ?> responden
    </div>
    <div class="mt-2 text-success small fw-bold">
      <?= format_desimal($top1['persentase_skor'], 1) ?>% Skor
    </div>
  </div>

  <!-- Peringkat 3 (Bronze) -->
  <?php if ($top3 && $top3['total_responden'] > 0): ?>
  <div class="podium-step bronze">
    <span class="podium-rank-badge">Peringkat 3</span>
    <h3 class="fw-bold mb-1" style="font-size: 1.15rem;"><?= e($top3['nama_cabang']) ?></h3>
    <div class="text-muted small mb-2"><?= e($top3['kode_cabang']) ?></div>
    <div class="stat-value fs-2 text-warning-emphasis mb-1">
      <?= $isStarMode ? format_desimal($top3['persentase_nilai_ss'], 1) . '%' : format_desimal($top3['display_avg_skor']) ?>
      <?php if (!$isStarMode): ?><span class="fs-6 text-muted">/ 4</span><?php endif; ?>
    </div>
    <div class="badge bg-light text-secondary border">
      <?= number_format($top3['total_responden'], 0, ',', '.') ?> responden
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- TABEL LENGKAP PERINGKAT CABANG (Skala 1 - 4) -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em;">
          <th style="width: 70px;">Rank</th>
          <th>Cabang</th>
          <th class="text-center">Responden</th>
          <th class="text-center">Rata-rata</th>
          <th class="text-center">% Skor</th>
          <th class="text-center">% Nilai SS</th>
          <th class="text-center">Predikat</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $rankNum = 1;
        foreach ($rankings as $row): 
          $hasData = ($row['total_responden'] > 0);
          $predikat = $hasData ? get_predikat_kepuasan($row['display_avg_skor']) : 'Belum Ada Data';
        ?>
        <tr>
          <td>
            <?php if ($rankNum === 1 && $hasData): ?>
              <span class="badge bg-warning text-dark fw-bold rounded-pill px-2">1 🥇</span>
            <?php elseif ($rankNum === 2 && $hasData): ?>
              <span class="badge bg-secondary text-white fw-bold rounded-pill px-2">2 🥈</span>
            <?php elseif ($rankNum === 3 && $hasData): ?>
              <span class="badge bg-warning-subtle text-dark fw-bold rounded-pill px-2">3 🥉</span>
            <?php else: ?>
              <span class="fw-bold text-muted ps-2"><?= $rankNum ?></span>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-bold"><?= e($row['nama_cabang']) ?></div>
            <small class="text-muted"><?= e($row['kode_cabang']) ?><?= $row['kepala_cabang'] ? ' &bull; ' . e($row['kepala_cabang']) : '' ?></small>
          </td>
          <td class="text-center">
            <strong><?= number_format($row['total_responden'], 0, ',', '.') ?></strong>
          </td>
          <td class="text-center">
            <span class="fw-bold text-primary-fastabiq fs-6">
              <?= $hasData ? format_desimal($row['display_avg_skor']) : '-' ?>
            </span>
            <?php if ($hasData): ?><small class="text-muted">/ 4</small><?php endif; ?>
          </td>
          <td class="text-center">
            <?= $hasData ? format_desimal($row['persentase_skor'], 1) . '%' : '-' ?>
          </td>
          <td class="text-center">
            <?php if ($hasData): ?>
              <span class="fw-bold text-success"><?= format_desimal($row['persentase_nilai_ss'], 1) ?>%</span>
              <div class="text-muted" style="font-size: 0.72rem;">(<?= number_format($row['total_nilai_ss'], 0, ',', '.') ?> SS)</div>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
          <td class="text-center">
            <span class="badge <?= get_badge_class($predikat) ?>"><?= $predikat ?></span>
          </td>
          <td class="text-end">
            <a href="<?= BASE_URL ?>/admin/reports.php?cabang=<?= $row['id'] ?>&tahun=<?= $filterYear ?>&bulan=<?= $filterMonth ?>" 
               class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="font-size: 0.78rem;">
              <i class="bi bi-file-earmark-text"></i> Laporan
            </a>
          </td>
        </tr>
        <?php 
          $rankNum++;
        endforeach; 
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Catatan Perhitungan & Rumus Skala 1 - 4 -->
<div class="p-3 bg-white rounded-4 border text-muted small">
  <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle-fill text-primary-fastabiq me-1"></i> Catatan Perhitungan & Standar Peringkat (Skala 1 - 4):</div>
  <ul class="mb-0 ps-3">
    <li><strong>Rata-rata</strong> = Total skor tertimbang &divide; Total jawaban rating valid (skala 1–4).</li>
    <li><strong>Persentase Skor</strong> = (Rata-rata kepuasan &divide; 4) &times; 100%.</li>
    <li><strong>Persentase Nilai SS</strong> = Jumlah jawaban bernilai Sangat Puas (4) &divide; Jumlah seluruh jawaban rating valid &times; 100%.</li>
    <li>Peringkat selalu menyertakan jumlah responden sebagai konteks agar evaluasi tetap adil dan objektif.</li>
  </ul>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

