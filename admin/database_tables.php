<?php
/**
 * KUESIONER ANGGOTA - Browser Tabel Database
 * Menampilkan struktur kolom, indeks, FK, statistik, dan pratinjau data setiap tabel.
 * Hanya dapat diakses oleh Administrator Pusat (role: admin)
 */

$activePage = 'database_tables';
$pageTitle  = 'Struktur Tabel Database';
require_once __DIR__ . '/../includes/header.php';

require_role(['admin']);
$db = get_db_connection();

// ─── Daftar tabel beserta metadata ───────────────────────────────────────────
$tableMeta = [
    'cabang' => [
        'label'   => 'Master Cabang',
        'desc'    => 'Menyimpan data 27 kantor cabang BMT Fastabiq beserta token QR unik per cabang.',
        'icon'    => 'bi-building',
        'color'   => '#0f766e',
        'bg'      => '#f0fdf4',
    ],
    'users' => [
        'label'   => 'Pengguna Sistem',
        'desc'    => 'Akun login Admin Pusat, Staf Admisi, dan Kepala Cabang. Password di-hash dengan bcrypt.',
        'icon'    => 'bi-people-fill',
        'color'   => '#2563eb',
        'bg'      => '#eff6ff',
    ],
    'kuesioner' => [
        'label'   => 'Instrumen Kuesioner',
        'desc'    => 'Master versi kuesioner yang aktif, arsip, atau draft. Mendukung multi-versi instrumen.',
        'icon'    => 'bi-clipboard2-check-fill',
        'color'   => '#4338ca',
        'bg'      => '#eef2ff',
    ],
    'pertanyaan' => [
        'label'   => 'Master Pertanyaan',
        'desc'    => 'Butir pertanyaan dinamis dengan 3 tipe: rating_5, text_opsional, produk_checkbox.',
        'icon'    => 'bi-question-diamond-fill',
        'color'   => '#7c3aed',
        'bg'      => '#faf5ff',
    ],
    'survey_response' => [
        'label'   => 'Respons Kuesioner',
        'desc'    => 'Satu baris = satu kunjungan anggota. Menyimpan skor, persentase, tanda tangan, dan kritik.',
        'icon'    => 'bi-chat-heart-fill',
        'color'   => '#e11d48',
        'bg'      => '#fff1f2',
    ],
    'jawaban_detail' => [
        'label'   => 'Detail Jawaban Rating',
        'desc'    => 'Normalisasi nilai 1–4 (STS, TS, S, SS) per pertanyaan per respons. Dasar semua query analitik.',
        'icon'    => 'bi-bar-chart-fill',
        'color'   => '#ea580c',
        'bg'      => '#fff7ed',
    ],
    'rekap_produk_cabang' => [
        'label'   => 'Rekap Produk Cabang',
        'desc'    => 'Rekap 11 jenis produk yang ditawarkan petugas per cabang per bulan, format laporan admisi.',
        'icon'    => 'bi-table',
        'color'   => '#d97706',
        'bg'      => '#fffbeb',
    ],
    'pengaturan' => [
        'label'   => 'Pengaturan Sistem',
        'desc'    => 'Konfigurasi aplikasi berbasis key-value: nama app, slogan, KPI target, penandatangan default.',
        'icon'    => 'bi-sliders',
        'color'   => '#475569',
        'bg'      => '#f8fafc',
    ],
];

// ─── Ambil struktur kolom, indeks, dan FK semua tabel ────────────────────────
$tableTab  = $_GET['tabel'] ?? array_key_first($tableMeta);
if (!array_key_exists($tableTab, $tableMeta)) {
    $tableTab = array_key_first($tableMeta);
}

$allTableStats = [];
foreach (array_keys($tableMeta) as $t) {
    $cnt = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $sz  = $db->prepare("
        SELECT ROUND((data_length + index_length) / 1024, 1)
        FROM information_schema.TABLES
        WHERE table_schema = ? AND table_name = ? LIMIT 1
    ");
    $sz->execute([DB_NAME, $t]);
    $allTableStats[$t] = ['rows' => $cnt, 'size_kb' => (float)($sz->fetchColumn() ?: 0)];
}

// Kolom aktif
$columnsStmt = $db->prepare("
    SELECT
        COLUMN_NAME        AS kolom,
        ORDINAL_POSITION   AS urutan,
        COLUMN_TYPE        AS tipe,
        IS_NULLABLE        AS nullable,
        COLUMN_DEFAULT     AS default_val,
        EXTRA              AS extra,
        COLUMN_KEY         AS key_type,
        COLUMN_COMMENT     AS komentar
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
    ORDER BY ORDINAL_POSITION ASC
");
$columnsStmt->execute([DB_NAME, $tableTab]);
$columns = $columnsStmt->fetchAll();

// Indeks
$indexStmt = $db->prepare("SHOW INDEX FROM `$tableTab`");
$indexStmt->execute();
$rawIdx = $indexStmt->fetchAll();
$indexes = [];
foreach ($rawIdx as $idx) {
    $name = $idx['Key_name'];
    if (!isset($indexes[$name])) {
        $indexes[$name] = [
            'name'    => $name,
            'unique'  => !$idx['Non_unique'],
            'columns' => [],
        ];
    }
    $indexes[$name]['columns'][] = $idx['Column_name'];
}

// Foreign Keys
$fkStmt = $db->prepare("
    SELECT
        kcu.CONSTRAINT_NAME       AS nama_fk,
        kcu.COLUMN_NAME           AS kolom_lokal,
        kcu.REFERENCED_TABLE_NAME AS tabel_ref,
        kcu.REFERENCED_COLUMN_NAME AS kolom_ref,
        rc.DELETE_RULE             AS on_delete,
        rc.UPDATE_RULE             AS on_update
    FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
        ON rc.CONSTRAINT_NAME   = kcu.CONSTRAINT_NAME
        AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
    WHERE kcu.TABLE_SCHEMA  = ?
      AND kcu.TABLE_NAME    = ?
      AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY kcu.CONSTRAINT_NAME
");
$fkStmt->execute([DB_NAME, $tableTab]);
$foreignKeys = $fkStmt->fetchAll();

// Pratinjau data (5 baris terbaru)
$previewRows = [];
try {
    $previewRows = $db->query("SELECT * FROM `$tableTab` ORDER BY 1 DESC LIMIT 5")->fetchAll();
} catch (\Exception $e) {
    $previewRows = [];
}

// DDL lengkap
$ddl = $db->query("SHOW CREATE TABLE `$tableTab`")->fetch(PDO::FETCH_ASSOC)['Create Table'] ?? '';

$meta = $tableMeta[$tableTab];
$stat = $allTableStats[$tableTab];
?>

<!-- ─── Page Header ─────────────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1">
      <i class="bi bi-database-fill me-2 text-primary-fastabiq"></i>Struktur Tabel Database
    </h1>
    <p class="text-muted small mb-0">
      Browser struktur kolom, indeks, foreign key, dan pratinjau data — Database <code><?= e(DB_NAME) ?></code>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/admin/backup.php" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
      <i class="bi bi-database-fill-gear me-1"></i>Backup DB
    </a>
  </div>
</div>

<!-- ─── Kartu Ringkasan Semua Tabel ─────────────────────────────────────────── -->
<div class="row g-2 mb-4">
  <?php foreach ($tableMeta as $tName => $tInfo): $s = $allTableStats[$tName]; ?>
  <div class="col-6 col-md-3">
    <a href="?tabel=<?= urlencode($tName) ?>"
       class="card border-0 shadow-sm rounded-4 p-3 text-decoration-none h-100 d-block <?= $tName === $tableTab ? 'ring-active' : '' ?>"
       style="background:<?= $tInfo['bg'] ?>; border:2px solid <?= $tName === $tableTab ? $tInfo['color'] : 'transparent' ?> !important; transition:all .2s;">
      <div class="d-flex align-items-center gap-2 mb-1">
        <i class="bi <?= $tInfo['icon'] ?>" style="color:<?= $tInfo['color'] ?>; font-size:1.1rem;"></i>
        <span class="fw-bold small" style="color:<?= $tInfo['color'] ?>;"><?= e($tInfo['label']) ?></span>
      </div>
      <div class="d-flex justify-content-between align-items-end mt-1">
        <span class="fw-bold" style="font-size:1.3rem; color:<?= $tInfo['color'] ?>;"><?= number_format($s['rows']) ?></span>
        <span class="text-muted" style="font-size:0.72rem;"><?= $s['size_kb'] > 0 ? $s['size_kb'].' KB' : '< 1 KB' ?></span>
      </div>
      <div class="text-muted" style="font-size:0.7rem;">baris data</div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- ─── Detail Tabel Aktif ───────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">

  <!-- Header Tabel Aktif -->
  <div class="p-4 d-flex flex-wrap align-items-center gap-3" style="background:<?= $meta['bg'] ?>; border-bottom:3px solid <?= $meta['color'] ?>;">
    <div style="width:52px;height:52px;background:<?= $meta['color'] ?>;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;flex-shrink:0;">
      <i class="bi <?= $meta['icon'] ?>"></i>
    </div>
    <div class="flex-grow-1">
      <div class="fw-bold fs-5 mb-0" style="color:<?= $meta['color'] ?>;"><?= e($meta['label']) ?></div>
      <code style="font-size:0.9rem; color:<?= $meta['color'] ?>;"><?= e($tableTab) ?></code>
      <span class="text-muted small ms-2">— <?= e($meta['desc']) ?></span>
    </div>
    <div class="d-flex gap-3 text-center">
      <div>
        <div class="fw-bold fs-5" style="color:<?= $meta['color'] ?>;"><?= number_format($stat['rows']) ?></div>
        <div class="text-muted" style="font-size:0.72rem;">Baris</div>
      </div>
      <div>
        <div class="fw-bold fs-5" style="color:<?= $meta['color'] ?>;"><?= count($columns) ?></div>
        <div class="text-muted" style="font-size:0.72rem;">Kolom</div>
      </div>
      <div>
        <div class="fw-bold fs-5" style="color:<?= $meta['color'] ?>;"><?= count($indexes) ?></div>
        <div class="text-muted" style="font-size:0.72rem;">Indeks</div>
      </div>
      <div>
        <div class="fw-bold fs-5" style="color:<?= $meta['color'] ?>;"><?= count($foreignKeys) ?></div>
        <div class="text-muted" style="font-size:0.72rem;">FK</div>
      </div>
    </div>
  </div>

  <!-- Tab Panel -->
  <div class="bg-white">
    <ul class="nav nav-tabs px-4 pt-3 border-0" id="tabTabel" role="tablist" style="border-bottom:1px solid #e2e8f0 !important;">
      <li class="nav-item"><button class="nav-link active fw-semibold small" data-bs-toggle="tab" data-bs-target="#tabKolom"><i class="bi bi-layout-three-columns me-1"></i>Kolom</button></li>
      <li class="nav-item"><button class="nav-link fw-semibold small" data-bs-toggle="tab" data-bs-target="#tabIndeks"><i class="bi bi-lightning-charge-fill me-1"></i>Indeks</button></li>
      <?php if (!empty($foreignKeys)): ?>
      <li class="nav-item"><button class="nav-link fw-semibold small" data-bs-toggle="tab" data-bs-target="#tabFK"><i class="bi bi-link-45deg me-1"></i>Foreign Key</button></li>
      <?php endif; ?>
      <li class="nav-item"><button class="nav-link fw-semibold small" data-bs-toggle="tab" data-bs-target="#tabPreview"><i class="bi bi-eye-fill me-1"></i>Pratinjau Data</button></li>
      <li class="nav-item"><button class="nav-link fw-semibold small" data-bs-toggle="tab" data-bs-target="#tabDDL"><i class="bi bi-code-slash me-1"></i>DDL SQL</button></li>
    </ul>

    <div class="tab-content p-4">

      <!-- ── Tab Kolom ───────────────────────────────────────────────────── -->
      <div class="tab-pane fade show active" id="tabKolom">
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle mb-0" style="font-size:0.85rem;">
            <thead style="background:#f8fafc;">
              <tr>
                <th class="fw-semibold text-muted" style="width:30px;">#</th>
                <th class="fw-semibold">Nama Kolom</th>
                <th class="fw-semibold">Tipe Data</th>
                <th class="fw-semibold text-center">Nullable</th>
                <th class="fw-semibold">Default</th>
                <th class="fw-semibold text-center">Key</th>
                <th class="fw-semibold">Extra</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($columns as $col):
                $isPK = $col['key_type'] === 'PRI';
                $isUK = $col['key_type'] === 'UNI';
                $isMUL = $col['key_type'] === 'MUL';
              ?>
              <tr <?= $isPK ? 'style="background:#fffbeb;"' : '' ?>>
                <td class="text-muted"><?= $col['urutan'] ?></td>
                <td>
                  <code class="fw-bold" style="color:<?= $isPK ? '#d97706' : ($meta['color']) ?>;">
                    <?php if ($isPK): ?><i class="bi bi-key-fill text-warning me-1" title="Primary Key"></i><?php endif; ?>
                    <?php if ($isUK): ?><i class="bi bi-fingerprint text-info me-1" title="Unique Key"></i><?php endif; ?>
                    <?php if ($isMUL): ?><i class="bi bi-lightning-charge text-secondary me-1" title="Index"></i><?php endif; ?>
                    <?= e($col['kolom']) ?>
                  </code>
                </td>
                <td><span class="badge rounded-pill text-bg-light border font-monospace fw-normal"><?= e($col['tipe']) ?></span></td>
                <td class="text-center">
                  <?php if ($col['nullable'] === 'YES'): ?>
                    <span class="badge text-bg-warning rounded-pill" style="font-size:0.68rem;">NULL</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary rounded-pill" style="font-size:0.68rem; opacity:.7;">NOT NULL</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted">
                  <?php if ($col['default_val'] !== null): ?>
                    <code style="font-size:0.8rem;"><?= e($col['default_val']) ?></code>
                  <?php else: ?>
                    <span class="text-muted" style="font-size:0.75rem;">—</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($isPK): ?>
                    <span class="badge rounded-pill" style="background:#fef3c7;color:#d97706;font-size:0.68rem;">PK</span>
                  <?php elseif ($isUK): ?>
                    <span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;font-size:0.68rem;">UK</span>
                  <?php elseif ($isMUL): ?>
                    <span class="badge rounded-pill" style="background:#f1f5f9;color:#64748b;font-size:0.68rem;">IDX</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted" style="font-size:0.78rem;"><?= e($col['extra'] ?: '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Legenda -->
        <div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top" style="font-size:0.76rem;">
          <span><i class="bi bi-key-fill text-warning me-1"></i>Primary Key</span>
          <span><i class="bi bi-fingerprint text-info me-1"></i>Unique Key</span>
          <span><i class="bi bi-lightning-charge text-secondary me-1"></i>Index / Multi-Key</span>
          <span><span class="badge text-bg-warning rounded-pill" style="font-size:0.68rem;">NULL</span> Boleh kosong</span>
          <span><span class="badge text-bg-secondary rounded-pill" style="font-size:0.68rem; opacity:.7;">NOT NULL</span> Wajib diisi</span>
        </div>
      </div>

      <!-- ── Tab Indeks ──────────────────────────────────────────────────── -->
      <div class="tab-pane fade" id="tabIndeks">
        <?php if (empty($indexes)): ?>
          <p class="text-muted small">Tidak ada indeks pada tabel ini.</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle mb-0" style="font-size:0.85rem;">
            <thead style="background:#f8fafc;">
              <tr>
                <th class="fw-semibold">Nama Indeks</th>
                <th class="fw-semibold">Kolom</th>
                <th class="fw-semibold text-center">Tipe</th>
                <th class="fw-semibold">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($indexes as $idx): ?>
              <tr>
                <td><code><?= e($idx['name']) ?></code></td>
                <td>
                  <?php foreach ($idx['columns'] as $c): ?>
                    <code class="me-1" style="font-size:0.82rem; background:#f1f5f9; padding:1px 5px; border-radius:4px;"><?= e($c) ?></code>
                  <?php endforeach; ?>
                </td>
                <td class="text-center">
                  <?php if ($idx['name'] === 'PRIMARY'): ?>
                    <span class="badge" style="background:#fef3c7;color:#d97706;">PRIMARY</span>
                  <?php elseif ($idx['unique']): ?>
                    <span class="badge" style="background:#e0f2fe;color:#0369a1;">UNIQUE</span>
                  <?php else: ?>
                    <span class="badge" style="background:#f1f5f9;color:#64748b;">INDEX</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted" style="font-size:0.78rem;">
                  <?php if ($idx['name'] === 'PRIMARY'): ?>Kunci utama identitas baris
                  <?php elseif ($idx['unique']): ?>Menjamin nilai tidak duplikat
                  <?php else: ?>Mempercepat query pencarian & JOIN
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── Tab Foreign Key ────────────────────────────────────────────── -->
      <?php if (!empty($foreignKeys)): ?>
      <div class="tab-pane fade" id="tabFK">
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle mb-0" style="font-size:0.85rem;">
            <thead style="background:#f8fafc;">
              <tr>
                <th class="fw-semibold">Nama Constraint</th>
                <th class="fw-semibold">Kolom Lokal</th>
                <th class="fw-semibold">→ Tabel Referensi</th>
                <th class="fw-semibold">Kolom Referensi</th>
                <th class="fw-semibold text-center">ON DELETE</th>
                <th class="fw-semibold text-center">ON UPDATE</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($foreignKeys as $fk): ?>
              <tr>
                <td><code style="font-size:0.8rem;"><?= e($fk['nama_fk']) ?></code></td>
                <td><code style="color:#e11d48;"><?= e($fk['kolom_lokal']) ?></code></td>
                <td>
                  <a href="?tabel=<?= urlencode($fk['tabel_ref']) ?>" class="fw-semibold text-decoration-none" style="color:<?= $meta['color'] ?>;">
                    <i class="bi bi-box-arrow-in-right me-1"></i><?= e($fk['tabel_ref']) ?>
                  </a>
                </td>
                <td><code style="color:#2563eb;"><?= e($fk['kolom_ref']) ?></code></td>
                <td class="text-center">
                  <?php
                    $delClass = match($fk['on_delete']) {
                        'CASCADE'  => 'danger',
                        'SET NULL' => 'warning',
                        default    => 'secondary',
                    };
                  ?>
                  <span class="badge text-bg-<?= $delClass ?> rounded-pill" style="font-size:0.7rem;"><?= e($fk['on_delete']) ?></span>
                </td>
                <td class="text-center">
                  <span class="badge text-bg-secondary rounded-pill" style="font-size:0.7rem; opacity:.8;"><?= e($fk['on_update']) ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="alert alert-light border rounded-3 mt-3 small mb-0 p-3">
          <strong>Keterangan ON DELETE:</strong>
          <span class="badge text-bg-danger ms-1">CASCADE</span> → baris child ikut terhapus &nbsp;|&nbsp;
          <span class="badge text-bg-warning ms-1">SET NULL</span> → FK di-set NULL &nbsp;|&nbsp;
          <span class="badge text-bg-secondary ms-1">RESTRICT</span> → penghapusan dicegah
        </div>
      </div>
      <?php endif; ?>

      <!-- ── Tab Pratinjau Data ─────────────────────────────────────────── -->
      <div class="tab-pane fade" id="tabPreview">
        <?php if (empty($previewRows)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Tabel ini belum memiliki data.
          </div>
        <?php else:
          $previewCols = array_keys($previewRows[0]);
          // Kolom sensitif yang perlu disanitasi
          $sensitiveFields = ['password', 'token_qr', 'respon_token', 'ip_address', 'user_agent', 'tanda_tangan'];
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="text-muted small">Menampilkan 5 baris terbaru dari <strong><?= number_format($stat['rows']) ?></strong> total baris</span>
          <span class="badge rounded-pill" style="background:<?= $meta['bg'] ?>;color:<?= $meta['color'] ?>;border:1px solid <?= $meta['color'] ?>;">
            <?= count($previewCols) ?> kolom
          </span>
        </div>
        <div class="table-responsive" style="max-height: 320px; overflow-y:auto;">
          <table class="table table-sm table-bordered align-top mb-0" style="font-size:0.78rem; white-space:nowrap;">
            <thead class="table-dark sticky-top">
              <tr>
                <?php foreach ($previewCols as $col): ?>
                <th class="fw-normal text-white-50" style="font-weight:600;">
                  <?php if ($col === 'id'): ?><i class="bi bi-key-fill text-warning me-1"></i><?php endif; ?>
                  <?= e($col) ?>
                </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($previewRows as $row): ?>
              <tr>
                <?php foreach ($previewCols as $col):
                  $val = $row[$col];
                  $isSensitive = in_array($col, $sensitiveFields);
                ?>
                <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; vertical-align:top;">
                  <?php if ($val === null): ?>
                    <span class="text-muted fst-italic" style="font-size:0.72rem;">NULL</span>
                  <?php elseif ($isSensitive && strlen($val) > 8): ?>
                    <span class="text-muted" title="Disembunyikan untuk keamanan">
                      <?= e(substr($val, 0, 6)) ?>…<i class="bi bi-shield-lock-fill ms-1 text-secondary" style="font-size:0.65rem;"></i>
                    </span>
                  <?php elseif (strlen($val) > 60): ?>
                    <span title="<?= e($val) ?>"><?= e(mb_substr($val, 0, 60)) ?>…</span>
                  <?php else: ?>
                    <?= e($val) ?>
                  <?php endif; ?>
                </td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-2 text-muted" style="font-size:0.73rem;">
          <i class="bi bi-shield-lock me-1"></i>
          Kolom sensitif (password, token, IP, tanda tangan) ditampilkan sebagian demi keamanan.
        </div>
        <?php endif; ?>
      </div>

      <!-- ── Tab DDL SQL ────────────────────────────────────────────────── -->
      <div class="tab-pane fade" id="tabDDL">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted small"><i class="bi bi-code-slash me-1"></i>DDL lengkap tabel <code><?= e($tableTab) ?></code></span>
          <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="copyDDL()">
            <i class="bi bi-clipboard me-1"></i>Salin
          </button>
        </div>
        <pre id="ddlCode" class="rounded-3 p-3 mb-0" style="background:#0f172a;color:#94a3b8;font-size:0.78rem;line-height:1.7;overflow-x:auto;max-height:360px;"><?= e($ddl) ?></pre>
      </div>

    </div><!-- /tab-content -->
  </div><!-- /bg-white -->
</div><!-- /card -->

<!-- ─── Peta Relasi Antar Tabel (ERD Ringkas) ─────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
  <h2 class="h6 fw-bold text-uppercase text-muted mb-3">
    <i class="bi bi-diagram-3-fill me-1"></i>Peta Relasi Antar Tabel (FK)
  </h2>
  <div class="table-responsive">
    <table class="table table-sm table-bordered text-center align-middle mb-0" style="font-size:0.8rem;">
      <thead class="table-dark">
        <tr>
          <th class="text-start">Tabel Anak</th>
          <th>Kolom FK</th>
          <th></th>
          <th>Tabel Induk</th>
          <th>Kolom Ref</th>
          <th>ON DELETE</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $allFKs = [
            ['child'=>'users',               'col'=>'cabang_id',    'parent'=>'cabang',          'ref'=>'id',  'del'=>'SET NULL'],
            ['child'=>'pertanyaan',           'col'=>'kuesioner_id', 'parent'=>'kuesioner',        'ref'=>'id',  'del'=>'CASCADE'],
            ['child'=>'survey_response',      'col'=>'cabang_id',    'parent'=>'cabang',          'ref'=>'id',  'del'=>'RESTRICT'],
            ['child'=>'survey_response',      'col'=>'kuesioner_id', 'parent'=>'kuesioner',        'ref'=>'id',  'del'=>'RESTRICT'],
            ['child'=>'jawaban_detail',       'col'=>'response_id',  'parent'=>'survey_response',  'ref'=>'id',  'del'=>'CASCADE'],
            ['child'=>'jawaban_detail',       'col'=>'pertanyaan_id','parent'=>'pertanyaan',       'ref'=>'id',  'del'=>'CASCADE'],
            ['child'=>'rekap_produk_cabang',  'col'=>'cabang_id',    'parent'=>'cabang',          'ref'=>'id',  'del'=>'RESTRICT'],
        ];
        $delColors = ['CASCADE'=>'danger','SET NULL'=>'warning','RESTRICT'=>'secondary'];
        foreach ($allFKs as $fk):
            $isActive = ($fk['child'] === $tableTab || $fk['parent'] === $tableTab);
        ?>
        <tr <?= $isActive ? 'style="background:#fffbeb;"' : '' ?>>
          <td class="text-start">
            <a href="?tabel=<?= urlencode($fk['child']) ?>" class="fw-semibold text-decoration-none text-dark">
              <code><?= e($fk['child']) ?></code>
            </a>
            <?= $isActive ? '<span class="badge text-bg-warning ms-1" style="font-size:0.65rem;">aktif</span>' : '' ?>
          </td>
          <td><code style="color:#e11d48;"><?= e($fk['col']) ?></code></td>
          <td><i class="bi bi-arrow-right text-muted"></i></td>
          <td>
            <a href="?tabel=<?= urlencode($fk['parent']) ?>" class="fw-semibold text-decoration-none" style="color:#0f766e;">
              <code><?= e($fk['parent']) ?></code>
            </a>
          </td>
          <td><code style="color:#2563eb;"><?= e($fk['ref']) ?></code></td>
          <td>
            <span class="badge text-bg-<?= $delColors[$fk['del']] ?? 'secondary' ?> rounded-pill" style="font-size:0.68rem;">
              <?= e($fk['del']) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="text-muted mt-2 mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Baris yang disorot kuning menunjukkan relasi yang melibatkan tabel <strong><?= e($tableMeta[$tableTab]['label']) ?></strong> yang sedang aktif.
  </p>
</div>

<style>
.ring-active { box-shadow: 0 0 0 2.5px currentColor; }
.nav-tabs .nav-link { color: #64748b; border-radius: 8px 8px 0 0 !important; border:none; padding:.5rem .9rem; }
.nav-tabs .nav-link.active { color: var(--primary); border-bottom: 3px solid var(--primary) !important; background: transparent; font-weight: 700; }
.nav-tabs .nav-link:hover:not(.active) { background: #f1f5f9; color: #0f172a; }
pre#ddlCode { font-family: 'Cascadia Code', 'Fira Code', 'JetBrains Mono', monospace; }
</style>

<script>
function copyDDL() {
    var text = document.getElementById('ddlCode').innerText;
    navigator.clipboard.writeText(text).then(function() {
        var btn = event.target.closest('button');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg me-1 text-success"></i>Tersalin!';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
