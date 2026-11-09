<?php
/**
 * KUESIONER ANGGOTA - Halaman Masuk (Login)
 * Sistem Kuesioner Kepuasan Layanan Anggota BMT Fastabiq
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$error = null;

if (is_logged_in()) {
    header("Location: " . BASE_URL . "/admin/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Nama pengguna dan kata sandi wajib diisi.';
    } else {
        $db = get_db_connection();
        $stmt = $db->prepare("
            SELECT u.*, c.nama_cabang, c.kode_cabang
            FROM users u
            LEFT JOIN cabang c ON u.cabang_id = c.id
            WHERE u.username = ? AND u.status = 'aktif'
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID untuk keamanan
            session_regenerate_id(true);

            $_SESSION['user_id']     = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['nama_lengkap']= $user['nama_lengkap'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['cabang_id']   = $user['cabang_id'];
            $_SESSION['nama_cabang'] = $user['nama_cabang'];
            $_SESSION['kode_cabang'] = $user['kode_cabang'];

            // Update terakhir login
            $update = $db->prepare("UPDATE users SET terakhir_login = NOW() WHERE id = ?");
            $update->execute([$user['id']]);

            // Catat log aktivitas login
            log_activity('login', 'auth', 'User berhasil login ke sistem');

            header("Location: " . BASE_URL . "/admin/index.php");
            exit;
        } else {
            $error = 'Nama pengguna atau kata sandi tidak cocok.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk Sistem - KUESIONER ANGGOTA</title>
  
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #f0fdf4 0%, #e6fffa 50%, #f8fafc 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .login-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      box-shadow: 0 20px 40px -15px rgba(15, 118, 110, 0.15);
      width: 100%;
      max-width: 440px;
      padding: 36px 32px;
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="text-center mb-4">
    <div class="mb-3">
      <img src="<?= BASE_URL ?>/assets/images/logo_bmt.png" alt="Logo BMT Fastabiq" style="width: 78px; height: 78px; object-fit: contain; filter: drop-shadow(0 4px 10px rgba(15, 118, 110, 0.15));">
    </div>
    <h2 class="fw-bold text-primary-fastabiq mb-1" style="letter-spacing: -0.5px;">Kuesioner Anggota</h2>
    <div class="text-muted small">Sistem Kuesioner Kepuasan Layanan Anggota</div>
    <div class="badge bg-light text-secondary border mt-2 px-3 py-1 fw-normal">KSPPS BMT Fastabiq Khoiro Ummah</div>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 small d-flex align-items-center">
    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
    <div><?= e($error) ?></div>
  </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label class="form-label fw-bold small text-secondary">Nama Pengguna (Username)</label>
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
        <input type="text" name="username" class="form-control border-start-0" placeholder="Masukkan username" required autofocus>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label fw-bold small text-secondary">Kata Sandi (Password)</label>
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
        <input type="password" name="password" class="form-control border-start-0" placeholder="Masukkan password" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary-survey w-100 py-2 fw-bold">
      <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Ruang Kerja
    </button>
  </form>

  <div class="mt-4 pt-3 border-top">
    <div class="text-muted small fw-bold mb-2 text-center">Akun Uji Coba Default:</div>
    <div class="d-flex flex-wrap gap-2 justify-content-center">
      <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size: 0.76rem;" onclick="setAccount('admin', 'password123')">
        👑 Admin Pusat
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 0.76rem;" onclick="setAccount('endang', 'password123')">
        📋 Staf Admisi (Endang)
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 0.76rem;" onclick="setAccount('kepala_f01', 'password123')">
        🏢 Kepala Cabang Pati
      </button>
    </div>
  </div>

  <div class="mt-4 text-center">
    <a href="<?= BASE_URL ?>/survey.php" class="small text-decoration-none text-muted">
      <i class="bi bi-arrow-left"></i> Buka Halaman Kuesioner Anggota (Publik)
    </a>
  </div>
</div>

<script>
function setAccount(u, p) {
  document.querySelector('input[name="username"]').value = u;
  document.querySelector('input[name="password"]').value = p;
}
</script>

</body>
</html>

