<?php
/**
 * KUESIONER ANGGOTA - Installer & System Diagnostics
 * Mendukung konfigurasi database mandiri untuk server online (FileZilla deploy).
 *
 * ALUR:
 *  Langkah 1 → Isi form kredensial database server
 *  Langkah 2 → Simpan ke config/db.local.php & uji koneksi
 *  Langkah 3 → Jalankan setup database (DDL + Seed)
 */

date_default_timezone_set('Asia/Jakarta');

// ─── Muat konfigurasi yang sudah tersimpan (jika ada) ────────────────────────
$localCfgFile = __DIR__ . '/config/db.local.php';
$hasSavedConfig = file_exists($localCfgFile);

$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'db_kuesioner';
$dbUser = 'root';
$dbPass = '';

if ($hasSavedConfig) {
    $saved  = require $localCfgFile;
    $dbHost = $saved['host'] ?? $dbHost;
    $dbPort = $saved['port'] ?? $dbPort;
    $dbName = $saved['name'] ?? $dbName;
    $dbUser = $saved['user'] ?? $dbUser;
    $dbPass = $saved['pass'] ?? $dbPass;
} else {
    $dbHost = getenv('DB_HOST') ?: $dbHost;
    $dbPort = getenv('DB_PORT') ?: $dbPort;
    $dbName = getenv('DB_NAME') ?: $dbName;
    $dbUser = getenv('DB_USER') ?: $dbUser;
    $dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : $dbPass;
}

$step       = (int)($_GET['step'] ?? 1);
$saveMsg    = null;
$saveError  = null;
$installed  = false;
$errorMsg   = null;

// ─── Simpan Konfigurasi Database ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $newHost = trim($_POST['db_host'] ?? '');
    $newPort = trim($_POST['db_port'] ?? '3306');
    $newName = trim($_POST['db_name'] ?? '');
    $newUser = trim($_POST['db_user'] ?? '');
    $newPass = $_POST['db_pass'] ?? '';

    if (!$newHost || !$newName || !$newUser) {
        $saveError = 'Host, Nama Database, dan Username wajib diisi.';
    } else {
        // Uji koneksi terlebih dahulu sebelum menyimpan
        try {
            $testPdo = new PDO(
                "mysql:host=$newHost;port=$newPort;charset=utf8mb4",
                $newUser, $newPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
            // Koneksi berhasil — simpan ke config/db.local.php
            $cfgContent = "<?php\n// Konfigurasi database server — dibuat otomatis oleh setup.php\n// JANGAN dibagikan ke publik atau di-commit ke repository!\nreturn [\n"
                . "    'host' => " . var_export($newHost, true) . ",\n"
                . "    'port' => " . var_export($newPort, true) . ",\n"
                . "    'name' => " . var_export($newName, true) . ",\n"
                . "    'user' => " . var_export($newUser, true) . ",\n"
                . "    'pass' => " . var_export($newPass, true) . ",\n"
                . "];\n";

            if (!is_dir(__DIR__ . '/config')) {
                mkdir(__DIR__ . '/config', 0755, true);
            }

            if (file_put_contents($localCfgFile, $cfgContent) !== false) {
                // Reload variabel dari data yang baru disimpan
                $dbHost = $newHost; $dbPort = $newPort;
                $dbName = $newName; $dbUser = $newUser; $dbPass = $newPass;
                $hasSavedConfig = true;
                $saveMsg = 'Konfigurasi berhasil disimpan & koneksi database berhasil diuji!';
                $step = 2;
            } else {
                $saveError = 'Gagal menulis file config/db.local.php. Pastikan folder config/ dapat ditulis (permission 755).';
            }
        } catch (PDOException $ex) {
            $saveError = 'Koneksi gagal: ' . $ex->getMessage();
        }
    }
}

// ─── Hapus Konfigurasi Tersimpan ─────────────────────────────────────────────
if (isset($_GET['reset_config'])) {
    if (file_exists($localCfgFile)) {
        @unlink($localCfgFile);
    }
    header('Location: setup.php');
    exit;
}

// ─── Cek Ekstensi & Koneksi (untuk tampilan Langkah 2) ───────────────────────
$status     = [];
$canInstall = true;
$pdoRoot    = null;

$exts = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($exts as $ext) {
    if (extension_loaded($ext)) {
        $status[] = ['ok' => true,  'msg' => "Ekstensi PHP <code>$ext</code> aktif."];
    } else {
        $status[] = ['ok' => false, 'msg' => "Ekstensi PHP <code>$ext</code> <strong>TIDAK</strong> ditemukan."];
        $canInstall = false;
    }
}

if ($step >= 2 || $hasSavedConfig) {
    try {
        $pdoRoot = new PDO(
            "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4",
            $dbUser, $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $ver = $pdoRoot->query("SELECT VERSION()")->fetchColumn();
        $status[] = ['ok' => true,  'msg' => "Terhubung ke MySQL/MariaDB server <strong>(Versi: $ver)</strong>."];
    } catch (PDOException $e) {
        $status[] = ['ok' => false, 'msg' => "Gagal terhubung ke MySQL (<code>$dbHost:$dbPort</code>): <code>" . htmlspecialchars($e->getMessage()) . "</code>"];
        $canInstall = false;
    }
}

// ─── Jalankan Instalasi Database ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_db']) && $canInstall && $pdoRoot) {
    try {
        $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdoRoot->exec("USE `$dbName`");
        $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
        // Jalankan per statement agar tidak error pada multi-statement
        foreach (array_filter(array_map('trim', explode(';', $schemaSql))) as $sql) {
            if ($sql) $pdoRoot->exec($sql . ';');
        }
        require_once __DIR__ . '/database/seed_data.php';
        $installed = true;
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup & Instalasi - KUESIONER ANGGOTA</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .step-badge { width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:.8rem; }
    .step-active  { background:#0f766e; color:#fff; }
    .step-done    { background:#dcfce7; color:#15803d; }
    .step-pending { background:#f1f5f9; color:#94a3b8; }
    .steps-bar    { display:flex; align-items:center; gap:8px; margin-bottom:1.5rem; }
    .step-line    { flex:1; height:2px; background:#e2e8f0; border-radius:2px; }
    .step-line.done { background:#0f766e; }
  </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white" style="max-width:640px; width:100%;">

  <!-- Brand -->
  <div class="text-center mb-4">
    <div class="mb-2">
      <img src="assets/images/logo_bmt.png" alt="Logo BMT Fastabiq" style="width: 64px; height: 64px; object-fit: contain;">
    </div>
    <h1 class="h3 fw-bold text-primary-fastabiq mb-1">Kuesioner Anggota</h1>
    <div class="text-muted small">Panduan Instalasi &amp; Penyiapan Database</div>
    <div class="badge bg-light text-secondary border mt-2">KSPPS BMT Fastabiq Khoiro Ummah</div>
  </div>

  <?php if ($installed): ?>
  <!-- ════════════════════ SUKSES ════════════════════ -->
  <div class="alert alert-success border-0 rounded-4 p-4 text-center">
    <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
    <h4 class="fw-bold text-success mb-2">Instalasi Berhasil! 🎉</h4>
    <p class="small text-muted mb-4">
      Database <code><?= htmlspecialchars($dbName) ?></code> berhasil dibuat dengan 27 kantor cabang,
      instrumen kuesioner, dan data uji coba.
    </p>
    <div class="d-grid gap-2">
      <a href="survey.php" class="btn btn-primary-survey py-2 fw-bold">
        <i class="bi bi-phone me-1"></i> Buka Kuesioner Anggota
      </a>
      <a href="login.php" class="btn btn-outline-secondary py-2 fw-bold">
        <i class="bi bi-shield-lock me-1"></i> Masuk Ruang Admin
      </a>
    </div>
    <div class="mt-3 p-3 rounded-3 text-start small" style="background:#fffbeb;border:1px solid #fde68a;">
      <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
      <strong>Keamanan:</strong> Setelah instalasi selesai, hapus atau batasi akses ke file
      <code>setup.php</code> di server agar tidak diakses orang lain.
    </div>
  </div>

  <?php else: ?>

  <!-- ════ STEP INDICATOR ════ -->
  <div class="steps-bar">
    <span class="step-badge <?= $hasSavedConfig ? 'step-done' : 'step-active' ?>">
      <?= $hasSavedConfig ? '<i class="bi bi-check-lg"></i>' : '1' ?>
    </span>
    <span class="text-muted" style="font-size:.78rem; white-space:nowrap;">Konfigurasi DB</span>
    <div class="step-line <?= $hasSavedConfig ? 'done' : '' ?>"></div>
    <span class="step-badge <?= $hasSavedConfig ? 'step-active' : 'step-pending' ?>">2</span>
    <span class="text-muted" style="font-size:.78rem; white-space:nowrap;">Jalankan Setup</span>
  </div>

  <?php if ($saveMsg): ?>
  <div class="alert alert-success border-0 rounded-3 d-flex align-items-start gap-2 mb-3 p-3 small">
    <i class="bi bi-check-circle-fill text-success mt-1"></i>
    <div><?= $saveMsg ?></div>
  </div>
  <?php endif; ?>

  <?php if ($saveError): ?>
  <div class="alert alert-danger border-0 rounded-3 d-flex align-items-start gap-2 mb-3 p-3 small">
    <i class="bi bi-x-circle-fill text-danger mt-1 flex-shrink-0"></i>
    <div><?= $saveError ?></div>
  </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
  <div class="alert alert-danger border-0 rounded-3 p-3 mb-3 small">
    <strong>Gagal Memproses:</strong> <?= htmlspecialchars($errorMsg) ?>
  </div>
  <?php endif; ?>


  <!-- ════════════════════ LANGKAH 1: Form Konfigurasi ════════════════════ -->
  <?php if (!$hasSavedConfig): ?>

  <div class="p-3 rounded-3 mb-4 small" style="background:#eff6ff; border:1px solid #bfdbfe;">
    <i class="bi bi-info-circle-fill text-primary me-1"></i>
    <strong>Untuk server online:</strong> Masukkan kredensial database dari panel hosting Anda
    (cPanel → MySQL Databases, atau tanyakan ke admin server).
  </div>

  <h5 class="fw-bold mb-3" style="font-size:.95rem;">
    <span class="step-badge step-active me-2">1</span> Konfigurasi Koneksi Database
  </h5>

  <form method="POST" action="">
    <input type="hidden" name="save_config" value="1">

    <div class="row g-3 mb-3">
      <div class="col-8">
        <label class="form-label small fw-bold">Host Database <span class="text-danger">*</span></label>
        <input type="text" name="db_host" class="form-control" required
               value="<?= htmlspecialchars($dbHost) ?>"
               placeholder="localhost atau 127.0.0.1">
        <div class="form-text">Biasanya <code>localhost</code> di hosting shared/cPanel</div>
      </div>
      <div class="col-4">
        <label class="form-label small fw-bold">Port</label>
        <input type="text" name="db_port" class="form-control"
               value="<?= htmlspecialchars($dbPort) ?>" placeholder="3306">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label small fw-bold">Nama Database <span class="text-danger">*</span></label>
      <input type="text" name="db_name" class="form-control" required
             value="<?= htmlspecialchars($dbName) ?>"
             placeholder="db_kuesioner">
      <div class="form-text">Buat database ini terlebih dahulu di cPanel jika belum ada</div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6">
        <label class="form-label small fw-bold">Username Database <span class="text-danger">*</span></label>
        <input type="text" name="db_user" class="form-control" required
               value="<?= htmlspecialchars($dbUser) ?>"
               placeholder="user_fastabiq">
      </div>
      <div class="col-6">
        <label class="form-label small fw-bold">Password Database</label>
        <div class="input-group">
          <input type="password" name="db_pass" class="form-control" id="dbPassInput"
                 value="<?= htmlspecialchars($dbPass) ?>" placeholder="••••••••">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePass()">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary-survey w-100 py-3 fw-bold">
      <i class="bi bi-plug-fill me-1"></i> Simpan &amp; Uji Koneksi Database
    </button>
  </form>

  <div class="mt-3 p-3 rounded-3 small" style="background:#f8fafc; border:1px solid #e2e8f0;">
    <strong><i class="bi bi-lightbulb-fill text-warning me-1"></i>Cara mendapat kredensial di cPanel:</strong>
    <ol class="mb-0 mt-2 ps-3" style="line-height:1.9;">
      <li>Login cPanel hosting → <strong>MySQL Databases</strong></li>
      <li>Buat database baru → misal: <code>user_kuesioneranggota</code></li>
      <li>Buat MySQL User baru → isi username &amp; password</li>
      <li>Add User to Database → centang <strong>All Privileges</strong></li>
      <li>Gunakan data tersebut di form di atas. Host biasanya <code>localhost</code></li>
    </ol>
  </div>

  <?php else: ?>
  <!-- ════════════════════ LANGKAH 2: Cek & Install ════════════════════ -->

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0" style="font-size:.95rem;">
      <span class="step-badge step-active me-2">2</span> Pemeriksaan Lingkungan Sistem
    </h5>
    <a href="setup.php?reset_config=1" class="btn btn-sm btn-outline-danger rounded-pill px-3"
       onclick="return confirm('Hapus konfigurasi database tersimpan dan mulai ulang?')">
      <i class="bi bi-arrow-counterclockwise me-1"></i>Ubah Konfigurasi
    </a>
  </div>

  <!-- Hasil pengecekan -->
  <div class="list-group mb-3">
    <?php foreach ($status as $s): ?>
    <div class="list-group-item d-flex align-items-center gap-2 py-2 px-3 small border-0 bg-light mb-1 rounded-2">
      <i class="bi <?= $s['ok'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?>"></i>
      <span><?= $s['msg'] ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Info konfigurasi aktif -->
  <div class="p-3 bg-light rounded-3 border mb-4 small">
    <div class="fw-bold text-dark mb-2">
      <i class="bi bi-database-fill-check text-success me-1"></i>Konfigurasi Aktif:
    </div>
    <div class="row g-1">
      <div class="col-4 text-muted">Host:</div>
      <div class="col-8"><code><?= htmlspecialchars("$dbHost:$dbPort") ?></code></div>
      <div class="col-4 text-muted">Database:</div>
      <div class="col-8"><code><?= htmlspecialchars($dbName) ?></code></div>
      <div class="col-4 text-muted">Username:</div>
      <div class="col-8"><code><?= htmlspecialchars($dbUser) ?></code></div>
      <div class="col-4 text-muted">Password:</div>
      <div class="col-8"><code><?= $dbPass ? str_repeat('•', min(strlen($dbPass), 8)) : '(kosong)' ?></code></div>
      <div class="col-4 text-muted">Sumber:</div>
      <div class="col-8">
        <span class="badge text-bg-success rounded-pill" style="font-size:.68rem;">
          <i class="bi bi-file-earmark-check me-1"></i>config/db.local.php
        </span>
      </div>
    </div>
  </div>

  <?php if ($canInstall): ?>
  <form method="POST" action="">
    <button type="submit" name="install_db" value="1" class="btn btn-primary-survey w-100 py-3 fw-bold">
      <i class="bi bi-database-fill-gear me-1"></i> Jalankan Setup &amp; Inisialisasi Database Otomatis
    </button>
  </form>
  <?php else: ?>
  <div class="alert alert-warning border-0 rounded-3 small text-center mb-0">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    Perbaiki kendala di atas, lalu klik <strong>"Ubah Konfigurasi"</strong> untuk memasukkan kredensial yang benar.
  </div>
  <?php endif; ?>

  <?php endif; // end hasSavedConfig ?>

  <div class="text-center mt-4">
    <a href="login.php" class="small text-muted text-decoration-none">
      Sudah terpasang? Langsung ke Halaman Login &rarr;
    </a>
  </div>

  <?php endif; // end installed ?>
</div>

<script>
function togglePass() {
    var inp = document.getElementById('dbPassInput');
    var ico = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        ico.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
