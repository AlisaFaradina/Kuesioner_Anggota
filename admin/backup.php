<?php
/**
 * KUESIONER ANGGOTA - Halaman Manajemen Backup Database
 * Menampilkan statistik database, info tabel, dan tombol unduh backup SQL
 * Hanya dapat diakses oleh Administrator Pusat (role: admin)
 */

$activePage = 'backup';
$pageTitle  = 'Backup Database';
require_once __DIR__ . '/../includes/header.php';

require_role(['admin']);
$db = get_db_connection();

// ─── Statistik Per Tabel ─────────────────────────────────────────────────────
$tables = [
    'cabang'              => ['label' => 'Master Cabang',              'icon' => 'bi-building',              'color' => 'teal'],
    'users'               => ['label' => 'Pengguna Sistem',            'icon' => 'bi-people-fill',           'color' => 'blue'],
    'kuesioner'           => ['label' => 'Instrumen Kuesioner',        'icon' => 'bi-clipboard2-check-fill', 'color' => 'indigo'],
    'pertanyaan'          => ['label' => 'Master Pertanyaan',          'icon' => 'bi-question-diamond-fill', 'color' => 'purple'],
    'survey_response'     => ['label' => 'Respons Kuesioner',          'icon' => 'bi-chat-heart-fill',       'color' => 'rose'],
    'jawaban_detail'      => ['label' => 'Detail Jawaban Rating',      'icon' => 'bi-bar-chart-fill',        'color' => 'orange'],
    'rekap_produk_cabang' => ['label' => 'Rekap Produk Cabang',        'icon' => 'bi-table',                 'color' => 'amber'],
    'pengaturan'          => ['label' => 'Pengaturan Sistem',          'icon' => 'bi-sliders',               'color' => 'slate'],
];

$tableStats    = [];
$totalRows     = 0;
$totalSizeKB   = 0;

foreach (array_keys($tables) as $t) {
    // Hitung baris
    $cnt = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();

    // Estimasi ukuran dari information_schema
    $sizeStmt = $db->prepare("
        SELECT ROUND((data_length + index_length) / 1024, 1) AS size_kb
        FROM information_schema.TABLES
        WHERE table_schema = ? AND table_name = ?
        LIMIT 1
    ");
    $sizeStmt->execute([DB_NAME, $t]);
    $sizeKB = (float)($sizeStmt->fetchColumn() ?: 0);

    $tableStats[$t] = ['rows' => $cnt, 'size_kb' => $sizeKB];
    $totalRows    += $cnt;
    $totalSizeKB  += $sizeKB;
}

// ─── Info Database dari information_schema ────────────────────────────────────
$dbInfoStmt = $db->prepare("
    SELECT
        SUM(data_length + index_length)                AS total_bytes,
        SUM(data_length)                               AS data_bytes,
        SUM(index_length)                              AS index_bytes,
        COUNT(*)                                       AS total_tables
    FROM information_schema.TABLES
    WHERE table_schema = ?
");
$dbInfoStmt->execute([DB_NAME]);
$dbInfo = $dbInfoStmt->fetch();

$dbSizeKB  = round(($dbInfo['total_bytes'] ?? 0) / 1024, 1);
$dbSizeMB  = round(($dbInfo['total_bytes'] ?? 0) / 1024 / 1024, 2);
$dataSizeKB  = round(($dbInfo['data_bytes']  ?? 0) / 1024, 1);
$indexSizeKB = round(($dbInfo['index_bytes'] ?? 0) / 1024, 1);

// ─── Ambil respons & signature terbaru ───────────────────────────────────────
$lastResponseDate = $db->query("SELECT MAX(waktu_selesai) FROM survey_response")->fetchColumn();
$totalSignatures  = count(glob(__DIR__ . '/../uploads/signatures/*.png') ?: []);
$sigDirKB = 0;
foreach (glob(__DIR__ . '/../uploads/signatures/*.png') ?: [] as $f) {
    $sigDirKB += filesize($f);
}
$sigDirKB = round($sigDirKB / 1024, 1);

// Waktu server saat ini
$now = date('Y-m-d H:i:s');
?>

<!-- ─── Page Header ─────────────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-database-fill-gear me-2 text-primary-fastabiq"></i>Backup Database</h1>
    <p class="text-muted small mb-0">
      Unduh cadangan lengkap database <code><?= e(DB_NAME) ?></code> beserta seluruh data survei, master cabang, dan pengaturan sistem.
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/admin/backup_download.php"
       class="btn btn-success rounded-pill px-4 py-2 fw-semibold"
       id="btnDownloadBackup"
       onclick="return confirmDownload(this)">
      <i class="bi bi-cloud-arrow-down-fill me-1"></i> Unduh Backup SQL
    </a>
  </div>
</div>

<!-- ─── Alert Info ──────────────────────────────────────────────────────────── -->
<div class="alert alert-info border-0 rounded-4 d-flex align-items-start gap-3 mb-4 p-3" style="background:#eff6ff;">
  <i class="bi bi-info-circle-fill fs-5 mt-1 text-primary"></i>
  <div class="small">
    <strong>Tentang Backup SQL:</strong> File backup berisi perintah <code>DROP TABLE</code> + <code>CREATE TABLE</code> + <code>INSERT</code> untuk semua tabel dan seluruh datanya.
    File dapat langsung diimpor ke MySQL/phpMyAdmin untuk memulihkan data kapan saja.
    Lakukan backup secara rutin — minimal seminggu sekali atau sebelum pembaruan sistem.
  </div>
</div>

<!-- ─── Kartu Ringkasan Database ────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 p-3 h-100 text-center" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
      <div style="font-size:1.8rem; color:#16a34a;"><i class="bi bi-hdd-stack-fill"></i></div>
      <div class="fw-bold fs-5 mt-1"><?= $dbSizeKB >= 1024 ? $dbSizeMB . ' MB' : $dbSizeKB . ' KB' ?></div>
      <div class="text-muted small">Total Ukuran DB</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 p-3 h-100 text-center" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
      <div style="font-size:1.8rem; color:#2563eb;"><i class="bi bi-table"></i></div>
      <div class="fw-bold fs-5 mt-1"><?= count($tables) ?> Tabel</div>
      <div class="text-muted small">Total Tabel Aktif</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 p-3 h-100 text-center" style="background:linear-gradient(135deg,#fefce8,#fef9c3);">
      <div style="font-size:1.8rem; color:#ca8a04;"><i class="bi bi-list-ol"></i></div>
      <div class="fw-bold fs-5 mt-1"><?= number_format($totalRows) ?></div>
      <div class="text-muted small">Total Baris Data</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 p-3 h-100 text-center" style="background:linear-gradient(135deg,#fdf4ff,#fae8ff);">
      <div style="font-size:1.8rem; color:#9333ea;"><i class="bi bi-pen-fill"></i></div>
      <div class="fw-bold fs-5 mt-1"><?= $totalSignatures ?> File</div>
      <div class="text-muted small">Tanda Tangan Digital <span class="text-muted">(<?= $sigDirKB ?> KB)</span></div>
    </div>
  </div>

</div>

<!-- ─── Tabel Statistik Per Tabel ───────────────────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
      <h2 class="h6 fw-bold text-uppercase text-muted mb-3">
        <i class="bi bi-database me-1"></i> Detail Isi Setiap Tabel
      </h2>

      <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0" style="font-size:0.875rem;">
          <thead class="table-light">
            <tr>
              <th class="fw-semibold">Tabel</th>
              <th class="fw-semibold">Keterangan</th>
              <th class="fw-semibold text-end">Baris</th>
              <th class="fw-semibold text-end">Ukuran</th>
              <th class="fw-semibold text-center">Proporsi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tables as $tName => $tInfo):
                $stat = $tableStats[$tName];
                $pct  = $totalSizeKB > 0 ? round($stat['size_kb'] / $totalSizeKB * 100, 1) : 0;
                $colorMap = [
                    'teal'   => '#0f766e', 'blue'   => '#2563eb', 'indigo' => '#4338ca',
                    'purple' => '#7c3aed', 'rose'   => '#e11d48', 'orange' => '#ea580c',
                    'amber'  => '#d97706', 'slate'  => '#475569',
                ];
                $col = $colorMap[$tInfo['color']] ?? '#0f766e';
            ?>
            <tr>
              <td>
                <i class="bi <?= $tInfo['icon'] ?> me-1" style="color:<?= $col ?>;"></i>
                <code class="text-dark"><?= e($tName) ?></code>
              </td>
              <td class="text-muted"><?= e($tInfo['label']) ?></td>
              <td class="text-end fw-semibold"><?= number_format($stat['rows']) ?></td>
              <td class="text-end text-muted"><?= $stat['size_kb'] > 0 ? $stat['size_kb'] . ' KB' : '< 1 KB' ?></td>
              <td style="min-width:100px;">
                <div class="d-flex align-items-center gap-2">
                  <div class="flex-grow-1" style="height:6px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                    <div style="width:<?= $pct ?>%; height:100%; background:<?= $col ?>; border-radius:4px; transition:width .4s;"></div>
                  </div>
                  <small class="text-muted" style="min-width:32px;"><?= $pct ?>%</small>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="border-top">
            <tr class="fw-bold">
              <td colspan="2">TOTAL</td>
              <td class="text-end"><?= number_format($totalRows) ?></td>
              <td class="text-end"><?= $totalSizeKB >= 1024 ? round($totalSizeKB/1024,2).' MB' : $totalSizeKB.' KB' ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- ─── Panel Kanan ──────────────────────────────────────────────────────── -->
  <div class="col-12 col-lg-4 d-flex flex-column gap-4">

    <!-- Info Teknis Database -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
      <h2 class="h6 fw-bold text-uppercase text-muted mb-3">
        <i class="bi bi-info-circle me-1"></i> Informasi Database
      </h2>
      <ul class="list-unstyled small mb-0">
        <li class="d-flex justify-content-between border-bottom py-2">
          <span class="text-muted">Nama Database</span>
          <code><?= e(DB_NAME) ?></code>
        </li>
        <li class="d-flex justify-content-between border-bottom py-2">
          <span class="text-muted">Host</span>
          <span class="fw-semibold"><?= e(DB_HOST) ?>:<?= e(DB_PORT) ?></span>
        </li>
        <li class="d-flex justify-content-between border-bottom py-2">
          <span class="text-muted">Data Murni</span>
          <span class="fw-semibold"><?= $dataSizeKB ?> KB</span>
        </li>
        <li class="d-flex justify-content-between border-bottom py-2">
          <span class="text-muted">Indeks</span>
          <span class="fw-semibold"><?= $indexSizeKB ?> KB</span>
        </li>
        <li class="d-flex justify-content-between border-bottom py-2">
          <span class="text-muted">Total Tabel</span>
          <span class="fw-semibold"><?= (int)($dbInfo['total_tables'] ?? 0) ?> tabel</span>
        </li>
        <li class="d-flex justify-content-between border-bottom py-2">
          <span class="text-muted">Karakter Set</span>
          <code>utf8mb4_unicode_ci</code>
        </li>
        <li class="d-flex justify-content-between border-bottom py-2">
          <span class="text-muted">PHP Version</span>
          <span class="fw-semibold"><?= PHP_VERSION ?></span>
        </li>
        <li class="d-flex justify-content-between border-bottom py-2">
          <span class="text-muted">Zona Waktu</span>
          <span class="fw-semibold"><?= date_default_timezone_get() ?></span>
        </li>
        <li class="d-flex justify-content-between pt-2">
          <span class="text-muted">Respons Terakhir</span>
          <span class="fw-semibold text-end" style="max-width:120px; word-break:break-all;">
            <?= $lastResponseDate ? format_tanggal_id($lastResponseDate, true) : '<span class="text-muted">-</span>' ?>
          </span>
        </li>
      </ul>
    </div>

    <!-- Tombol Unduh Backup -->
    <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5); border:1.5px solid #bbf7d0 !important;">
      <div class="text-center mb-3">
        <div style="width:64px;height:64px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#16a34a;margin:0 auto;">
          <i class="bi bi-database-fill-down"></i>
        </div>
      </div>
      <h2 class="h6 fw-bold text-center mb-2">Unduh Backup SQL</h2>
      <p class="small text-muted text-center mb-3">
        Backup dibuat secara real-time dari data terkini.
        File <code>.sql</code> berisi DDL + seluruh data siap pulihkan.
      </p>

      <a href="<?= BASE_URL ?>/admin/backup_download.php"
         class="btn btn-success w-100 rounded-pill py-2 fw-semibold mb-2"
         id="btnDownloadBackup2"
         onclick="return confirmDownload(this)">
        <i class="bi bi-cloud-arrow-down-fill me-1"></i> Download .SQL Sekarang
      </a>

      <div class="text-center">
        <small class="text-muted">
          <i class="bi bi-clock me-1"></i>
          Dibuat: <strong><?= format_tanggal_id($now, true) ?></strong>
        </small>
      </div>
    </div>

  </div>
</div>

<!-- ─── Panduan Restore ──────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
  <h2 class="h6 fw-bold text-uppercase text-muted mb-3">
    <i class="bi bi-arrow-counterclockwise me-1"></i> Panduan Pemulihan (Restore) Database
  </h2>
  <div class="row g-4">

    <div class="col-12 col-md-6">
      <h3 class="h6 fw-semibold mb-2"><i class="bi bi-window me-1 text-primary-fastabiq"></i> Via phpMyAdmin</h3>
      <ol class="small text-muted mb-0 ps-3" style="line-height:1.9;">
        <li>Buka phpMyAdmin di browser (<code>http://localhost/phpmyadmin</code>).</li>
        <li>Pilih atau buat database <code><?= e(DB_NAME) ?></code>.</li>
        <li>Klik tab <strong>Import</strong> di bagian atas.</li>
        <li>Pilih file backup <code>.sql</code> yang diunduh.</li>
        <li>Klik <strong>Go / Kirim</strong> — proses restore akan berjalan otomatis.</li>
      </ol>
    </div>

    <div class="col-12 col-md-6">
      <h3 class="h6 fw-semibold mb-2"><i class="bi bi-terminal me-1 text-primary-fastabiq"></i> Via Command Line (MySQL CLI)</h3>
      <div class="bg-dark text-success rounded-3 p-3 small font-monospace" style="line-height:1.8; overflow-x:auto; white-space:nowrap;">
        <span class="text-muted"># Buat database jika belum ada</span><br>
        mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS <?= e(DB_NAME) ?>;"<br><br>
        <span class="text-muted"># Import file backup</span><br>
        mysql -u root -p <?= e(DB_NAME) ?> &lt; backup_kuesioner_anggota_YYYYMMDD.sql
      </div>
    </div>

  </div>

  <div class="alert alert-warning border-0 rounded-3 d-flex align-items-start gap-2 mt-3 mb-0 p-3 small" style="background:#fffbeb;">
    <i class="bi bi-exclamation-triangle-fill text-warning mt-1 flex-shrink-0"></i>
    <div>
      <strong>Perhatian:</strong> Proses restore akan menimpa (overwrite) seluruh data yang ada di database tujuan karena file backup menggunakan perintah
      <code>DROP TABLE IF EXISTS</code> sebelum membuat ulang tabel. Pastikan Anda sudah yakin sebelum melanjutkan.
    </div>
  </div>
</div>

<!-- ─── Modal Konfirmasi Download ──────────────────────────────────────────────── -->
<div class="modal fade" id="modalConfirmBackup" tabindex="-1" aria-labelledby="modalConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalConfirmLabel">
          <i class="bi bi-database-fill-down text-success me-2"></i>Konfirmasi Unduh Backup
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="text-muted small mb-3">
          Anda akan mengunduh backup database <strong><?= e(DB_NAME) ?></strong> pada:
        </p>
        <div class="rounded-3 p-3 mb-3 small" style="background:#f0fdf4; border:1px solid #bbf7d0;">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Total Tabel</span>
            <strong><?= count($tables) ?> tabel</strong>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Total Baris</span>
            <strong><?= number_format($totalRows) ?> baris</strong>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Estimasi Ukuran DB</span>
            <strong><?= $dbSizeKB >= 1024 ? $dbSizeMB . ' MB' : $dbSizeKB . ' KB' ?></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Waktu Backup</span>
            <strong><?= format_tanggal_id($now, true) ?></strong>
          </div>
        </div>
        <p class="small text-muted mb-0">
          Nama file: <code>backup_kuesioner_anggota_<?= date('Ymd_His') ?>.sql</code>
        </p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
        <a href="<?= BASE_URL ?>/admin/backup_download.php"
           class="btn btn-success rounded-pill px-4 fw-semibold"
           id="btnModalDownload">
          <i class="bi bi-cloud-arrow-down-fill me-1"></i> Ya, Unduh Sekarang
        </a>
      </div>
    </div>
  </div>
</div>

<script>
/**
 * Tampilkan modal konfirmasi sebelum download, lalu tunjukkan spinner.
 */
function confirmDownload(btn) {
  // Cegah navigasi langsung; tampilkan modal dulu
  var modal = new bootstrap.Modal(document.getElementById('modalConfirmBackup'));
  modal.show();

  // Ketika tombol di modal diklik, tampilkan feedback loading
  document.getElementById('btnModalDownload').addEventListener('click', function () {
    var downloadBtn  = document.getElementById('btnDownloadBackup');
    var downloadBtn2 = document.getElementById('btnDownloadBackup2');
    var loadingHtml  = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyiapkan...';

    if (downloadBtn)  downloadBtn.innerHTML  = loadingHtml;
    if (downloadBtn2) downloadBtn2.innerHTML = loadingHtml;

    // Kembalikan teks tombol setelah 4 detik (download sudah mulai)
    setTimeout(function () {
      var original = '<i class="bi bi-cloud-arrow-down-fill me-1"></i> Unduh Backup SQL';
      var original2 = '<i class="bi bi-cloud-arrow-down-fill me-1"></i> Download .SQL Sekarang';
      if (downloadBtn)  downloadBtn.innerHTML  = original;
      if (downloadBtn2) downloadBtn2.innerHTML = original2;
    }, 4000);
  }, { once: true });

  return false; // cegah href langsung
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
