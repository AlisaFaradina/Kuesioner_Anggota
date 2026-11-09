<?php
/**
 * KUESIONER ANGGOTA - Export Laporan Penilaian ke PDF / Print-Ready View
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$currentUser = require_login();
$db = get_db_connection();

$startDate = $_GET['start_date'] ?? date('Y') . '-10-01';
$endDate   = $_GET['end_date'] ?? date('Y') . '-10-31';
$filterBranch = isset($_GET['cabang']) && $_GET['cabang'] !== '' ? (int)$_GET['cabang'] : null;

enforce_branch_scope($filterBranch);

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
        c.kode_cabang,
        c.nama_cabang,
        COUNT(DISTINCT r.id) AS total_responden,
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
$rawRows = $stmt->fetchAll();

$rows = [];
$grandResponden = 0;
$grandTotalWeighted = 0;
$grandTotalJawaban = 0;
$grandTotalSS = 0;
$grandSumSS = 0;
$grandSumS = 0;
$grandSumTS = 0;
$grandSumSTS = 0;

foreach ($rawRows as $r) {
    $cSS = (int)$r['count_ss'];
    $cS = (int)$r['count_s'];
    $cTS = (int)$r['count_ts'];
    $cSTS = (int)$r['count_sts'];
    $totJawaban = $cSS + $cS + $cTS + $cSTS;
    $weighted = ($cSS * 4) + ($cS * 3) + ($cTS * 2) + ($cSTS * 1);
    $avg = $totJawaban > 0 ? round($weighted / $totJawaban, 2) : 0.00;
    $pct = $totJawaban > 0 ? round(($avg / 4) * 100, 1) : 0.0;
    $pctSS = $totJawaban > 0 ? round(($cSS / $totJawaban) * 100, 1) : 0.0;

    $grandResponden += (int)$r['total_responden'];
    $grandTotalWeighted += $weighted;
    $grandTotalJawaban += $totJawaban;
    $grandTotalSS += $cSS;
    $grandSumSS += $cSS;
    $grandSumS += $cS;
    $grandSumTS += $cTS;
    $grandSumSTS += $cSTS;

    $rows[] = array_merge($r, [
        'count_ss' => $cSS,
        'count_s' => $cS,
        'count_ts' => $cTS,
        'count_sts' => $cSTS,
        'avg_skor' => $avg,
        'persentase_skor' => $pct,
        'pct_nilai_ss' => $pctSS,
        'total_jawaban' => $totJawaban
    ]);
}

$grandAvg = $grandTotalJawaban > 0 ? round($grandTotalWeighted / $grandTotalJawaban, 2) : 0.00;
$grandPct = $grandAvg > 0 ? round(($grandAvg / 4) * 100, 1) : 0.0;
$grandPctSS = $grandTotalJawaban > 0 ? round(($grandTotalSS / $grandTotalJawaban) * 100, 1) : 0.0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan_Kepuasan_Anggota_Fastabiq_<?= date('Ymd') ?></title>
  <style>
    @page {
      size: A4 portrait;
      margin: 1.2cm;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 11px;
      color: #1e293b;
      margin: 0;
      padding: 0;
    }
    .header-box {
      border-bottom: 2px solid #0f766e;
      padding-bottom: 12px;
      margin-bottom: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .title {
      font-size: 18px;
      font-weight: bold;
      color: #0f766e;
      margin: 0;
    }
    .subtitle {
      font-size: 12px;
      color: #475569;
      margin-top: 3px;
    }
    .meta-table {
      width: 100%;
      margin-bottom: 15px;
      font-size: 10.5px;
    }
    .meta-table td {
      padding: 3px 6px;
    }
    table.data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    table.data-table th, table.data-table td {
      border: 1px solid #cbd5e1;
      padding: 5px 6px;
      text-align: left;
    }
    table.data-table th {
      background: #f1f5f9;
      font-weight: bold;
      text-align: center;
      font-size: 10px;
    }
    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }
    .fw-bold { font-weight: bold; }
    .tfoot-row td {
      background: #f8fafc;
      font-weight: bold;
      border-top: 2px solid #0f766e;
    }
    .signature-section {
      width: 100%;
      margin-top: 25px;
      page-break-inside: avoid;
    }
    .no-print {
      margin-bottom: 15px;
      text-align: right;
    }
    .btn-print {
      background: #0f766e;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }
    @media print {
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button type="button" class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan sebagai PDF</button>
</div>

<div class="header-box">
  <div>
    <div class="title">KUESIONER ANGGOTA</div>
    <div class="subtitle">Laporan Kepuasan Layanan Anggota BMT Fastabiq Khoiro Ummah</div>
  </div>
  <div style="text-align: right; font-size: 10px; color: #64748b;">
    <div>Kantor Pusat Pati</div>
    <div>Dicetak: <?= date('d/m/Y H:i') ?> WIB</div>
  </div>
</div>

<table class="meta-table">
  <tr>
    <td style="width: 18%;"><strong>Periode:</strong></td>
    <td style="width: 32%;"><?= format_tanggal_id($startDate) ?> s/d <?= format_tanggal_id($endDate) ?></td>
    <td style="width: 18%;"><strong>Total Responden:</strong></td>
    <td style="width: 32%;"><?= number_format($grandResponden, 0, ',', '.') ?> Anggota</td>
  </tr>
  <tr>
    <td><strong>Cabang:</strong></td>
    <td><?= $filterBranch ? e($rows[0]['nama_cabang'] ?? 'Cabang Khusus') : 'Seluruh Cabang Aktif (' . count($rows) . ')' ?></td>
    <td><strong>Target KPI:</strong></td>
    <td>&ge; 95% Kepuasan</td>
  </tr>
</table>

<table class="data-table">
  <thead>
    <tr>
      <th rowspan="2" style="width: 25px;">No</th>
      <th rowspan="2" style="width: 45px;">Kode</th>
      <th rowspan="2">Nama Cabang</th>
      <th rowspan="2" style="width: 60px;">Resp.</th>
      <th colspan="4">Akumulasi Jawaban</th>
      <th rowspan="2" style="width: 55px;">Rata2</th>
      <th rowspan="2" style="width: 50px;">% Skor</th>
      <th rowspan="2" style="width: 55px;">% Nilai SS</th>
      <th rowspan="2" style="width: 85px;">Predikat</th>
    </tr>
    <tr>
      <th style="width: 32px;">SS (4)</th>
      <th style="width: 32px;">S (3)</th>
      <th style="width: 32px;">TS (2)</th>
      <th style="width: 32px;">STS (1)</th>
    </tr>
  </thead>
  <tbody>
    <?php 
    $no = 1;
    foreach ($rows as $r): 
      $hasData = ($r['total_responden'] > 0);
      $pred = $hasData ? get_predikat_kepuasan($r['avg_skor']) : '-';
    ?>
    <tr>
      <td class="text-center"><?= $no++ ?></td>
      <td class="text-center"><?= e($r['kode_cabang']) ?></td>
      <td><?= e($r['nama_cabang']) ?></td>
      <td class="text-center fw-bold"><?= number_format($r['total_responden'], 0, ',', '.') ?></td>
      <td class="text-center"><?= $hasData ? number_format($r['count_ss'], 0, ',', '.') : '-' ?></td>
      <td class="text-center"><?= $hasData ? number_format($r['count_s'], 0, ',', '.') : '-' ?></td>
      <td class="text-center"><?= $hasData ? number_format($r['count_ts'], 0, ',', '.') : '-' ?></td>
      <td class="text-center"><?= $hasData ? number_format($r['count_sts'], 0, ',', '.') : '-' ?></td>
      <td class="text-center fw-bold"><?= $hasData ? format_desimal($r['avg_skor']) : '-' ?></td>
      <td class="text-center"><?= $hasData ? format_desimal($r['persentase_skor'], 1) . '%' : '-' ?></td>
      <td class="text-center"><?= $hasData ? format_desimal($r['pct_nilai_ss'], 1) . '%' : '-' ?></td>
      <td class="text-center"><?= $pred ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr class="tfoot-row">
      <td colspan="3" class="text-right">Total / Rata-rata:</td>
      <td class="text-center"><?= number_format($grandResponden, 0, ',', '.') ?></td>
      <td class="text-center"><?= number_format($grandSumSS, 0, ',', '.') ?></td>
      <td class="text-center"><?= number_format($grandSumS, 0, ',', '.') ?></td>
      <td class="text-center"><?= number_format($grandSumTS, 0, ',', '.') ?></td>
      <td class="text-center"><?= number_format($grandSumSTS, 0, ',', '.') ?></td>
      <td class="text-center"><?= format_desimal($grandAvg) ?></td>
      <td class="text-center"><?= format_desimal($grandPct, 1) ?>%</td>
      <td class="text-center"><?= format_desimal($grandPctSS, 1) ?>%</td>
      <td class="text-center"><?= get_predikat_kepuasan($grandAvg) ?></td>
    </tr>
  </tfoot>
</table>

<table class="signature-section">
  <tr>
    <td style="width: 65%; vertical-align: top; font-size: 9.5px; color: #475569;">
      <strong>Catatan Evaluasi Mutu (Skala 1 - 4):</strong><br>
      &bull; Bobot Nilai: SS = 4, S = 3, TS = 2, STS = 1.<br>
      &bull; Rata-rata kepuasan dihitung berbobot: Total Skor Tertimbang &divide; Total Jawaban Rating.<br>
      &bull; Persentase skor = (Rata-rata &divide; 4) &times; 100%.<br>
      &bull; Persentase nilai SS = (Jawaban SS &divide; total jawaban rating) &times; 100%.<br>
      &bull; Dokumen resmi dievaluasi bersama jajaran pimpinan cabang dan pusat.
    </td>
    <td style="width: 35%; text-align: center; font-size: 10.5px;">
      <div>Pati, <?= format_tanggal_id(date('Y-m-d')) ?></div>
      <div style="font-weight: bold; margin-top: 4px;">Petugas Admisi / Staf Layanan,</div>
      <div style="height: 50px;"></div>
      <div style="font-weight: bold; text-decoration: underline;">Endang K</div>
      <div style="font-size: 9.5px; color: #64748b;">KSPPS BMT Fastabiq Khoiro Ummah</div>
    </td>
  </tr>
</table>

</body>
</html>

