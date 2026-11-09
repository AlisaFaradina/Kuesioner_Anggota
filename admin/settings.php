<?php
/**
 * KUESIONER ANGGOTA - Pengaturan Sistem & Backup Database
 */

$activePage = 'settings';
$pageTitle = 'Pengaturan Sistem';
require_once __DIR__ . '/../includes/header.php';

require_role(['admin']);
$db = get_db_connection();

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $keys = [
            'nama_aplikasi', 'slogan', 'nama_instansi', 'zona_waktu',
            'kpi_target_persen', 'petugas_default_nama', 'petugas_default_jabatan', 'kota_laporan'
        ];

        $stmt = $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $stmt->execute([$k, trim($_POST[$k])]);
            }
        }
        $message = 'Pengaturan sistem berhasil disimpan.';
    }
}

// Ambil semua pengaturan
$settings = $db->query("SELECT kunci, nilai FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Pengaturan Sistem &amp; Backup</h1>
    <p class="text-muted small mb-0">Kelola identitas lembaga, target KPI kepuasan, dan manajemen cadangan database.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success border-0 rounded-3 p-3 mb-4 small d-flex align-items-center">
  <i class="bi bi-check-circle-fill fs-5 me-2"></i><div><?= e($message) ?></div>
</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
      <h2 class="h5 fw-bold mb-3 text-primary-fastabiq">Parameter Konfigurasi Aplikasi</h2>

      <form method="POST" action="">
        <input type="hidden" name="action" value="save_settings">

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label small fw-bold">Nama Aplikasi</label>
            <input type="text" name="nama_aplikasi" class="form-control" value="<?= e($settings['nama_aplikasi'] ?? 'KUESIONER ANGGOTA') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-bold">Target KPI Kepuasan (%)</label>
            <input type="number" step="0.1" name="kpi_target_persen" class="form-control" value="<?= e($settings['kpi_target_persen'] ?? '95.00') ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Slogan Resmi</label>
          <input type="text" name="slogan" class="form-control" value="<?= e($settings['slogan'] ?? 'Suara Anda, Semangat Kami untuk Melayani Lebih Baik.') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Nama Badan Hukum / Instansi</label>
          <input type="text" name="nama_instansi" class="form-control" value="<?= e($settings['nama_instansi'] ?? 'KSPPS BMT Fastabiq Khoiro Ummah') ?>" required>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label small fw-bold">Zona Waktu Aplikasi</label>
            <input type="text" name="zona_waktu" class="form-control" value="<?= e($settings['zona_waktu'] ?? 'Asia/Jakarta') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-bold">Kota Penerbitan Laporan</label>
            <input type="text" name="kota_laporan" class="form-control" value="<?= e($settings['kota_laporan'] ?? 'Pati') ?>" required>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label small fw-bold">Nama Penandatangan Default</label>
            <input type="text" name="petugas_default_nama" class="form-control" value="<?= e($settings['petugas_default_nama'] ?? 'Endang K') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-bold">Jabatan Penandatangan Default</label>
            <input type="text" name="petugas_default_jabatan" class="form-control" value="<?= e($settings['petugas_default_jabatan'] ?? 'Admisi / Layanan Anggota') ?>" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary-survey px-4 py-2">
          <i class="bi bi-save me-1"></i> Simpan Pengaturan
        </button>
      </form>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
      <h2 class="h6 fw-bold text-uppercase text-muted mb-3">Cadangan Database (Backup)</h2>
      <p class="small text-muted mb-3">
        Buat cadangan database <code>db_kuesioner</code> secara berkala untuk menjaga keamanan data kepuasan anggota dan rekap produk.
      </p>
      <a href="<?= BASE_URL ?>/admin/backup_download.php" class="btn btn-outline-success w-100 rounded-pill py-2 mb-2">
        <i class="bi bi-cloud-arrow-down-fill me-1"></i> Download Backup SQL (.sql)
      </a>
      <div class="form-text small text-muted">
        File backup berisi skema lengkap dan seluruh data survei yang dapat dipulihkan kapan saja.
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
      <h2 class="h6 fw-bold text-uppercase text-muted mb-3">Informasi Lingkungan</h2>
      <ul class="list-unstyled small mb-0">
        <li class="mb-2"><strong>PHP:</strong> <?= PHP_VERSION ?></li>
        <li class="mb-2"><strong>Web Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI Server' ?></li>
        <li class="mb-2"><strong>Database:</strong> MariaDB / MySQL</li>
        <li class="mb-2"><strong>Nama DB:</strong> <code><?= DB_NAME ?></code></li>
        <li><strong>Zona Waktu:</strong> <?= date_default_timezone_get() ?></li>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

