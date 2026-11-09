<?php
/**
 * KUESIONER ANGGOTA - Master Pengguna & Hak Akses
 */

$activePage = 'users';
$pageTitle = 'Master Pengguna Sistem';
require_once __DIR__ . '/../includes/header.php';

require_role(['admin']);
$db = get_db_connection();

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'kepala_cabang';
        $cabangId = ($role === 'kepala_cabang' && !empty($_POST['cabang_id'])) ? (int)$_POST['cabang_id'] : null;
        $status = $_POST['status'] ?? 'aktif';

        if (empty($username) || empty($nama)) {
            $error = 'Username dan Nama Lengkap wajib diisi.';
        } else {
            try {
                if ($action === 'create') {
                    if (empty($password)) {
                        $error = 'Password wajib diisi untuk pengguna baru.';
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("
                            INSERT INTO users (username, password, nama_lengkap, email, role, cabang_id, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$username, $hash, $nama, $email, $role, $cabangId, $status]);
                        $message = "Pengguna $username berhasil ditambahkan.";
                    }
                } else {
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("
                            UPDATE users 
                            SET username = ?, password = ?, nama_lengkap = ?, email = ?, role = ?, cabang_id = ?, status = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $hash, $nama, $email, $role, $cabangId, $status, $id]);
                    } else {
                        $stmt = $db->prepare("
                            UPDATE users 
                            SET username = ?, nama_lengkap = ?, email = ?, role = ?, cabang_id = ?, status = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $nama, $email, $role, $cabangId, $status, $id]);
                    }
                    $message = "Data pengguna $username berhasil diperbarui.";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Username '$username' sudah digunakan. Pilih username lain.";
                } else {
                    $error = "Terjadi kesalahan: " . $e->getMessage();
                }
            }
        }
    }
}

$branches = get_all_branches(false);
$sqlUsers = "
    SELECT u.*, c.nama_cabang, c.kode_cabang
    FROM users u
    LEFT JOIN cabang c ON u.cabang_id = c.id
    ORDER BY u.role ASC, u.username ASC
";
$userRows = $db->query($sqlUsers)->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Master Pengguna &amp; Hak Akses</h1>
    <p class="text-muted small mb-0">Kelola kredensial akun administrator pusat, staf admisi, dan pembatasan akses kepala cabang.</p>
  </div>

  <div>
    <button type="button" class="btn btn-primary-survey btn-sm rounded-pill px-3 shadow-sm" onclick="openCreateUserModal()">
      <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
    </button>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success border-0 rounded-3 p-3 mb-4 small d-flex align-items-center">
  <i class="bi bi-check-circle-fill fs-5 me-2"></i><div><?= e($message) ?></div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger border-0 rounded-3 p-3 mb-4 small d-flex align-items-center">
  <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i><div><?= e($error) ?></div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
      <thead class="table-light">
        <tr style="font-size: 0.78rem; text-transform: uppercase;">
          <th>Nama Pengguna</th>
          <th>Nama Lengkap</th>
          <th>Peran (Role)</th>
          <th>Kewenangan Cabang</th>
          <th class="text-center">Status</th>
          <th>Terakhir Login</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($userRows as $u): ?>
        <tr>
          <td><strong><?= e($u['username']) ?></strong></td>
          <td>
            <div><?= e($u['nama_lengkap']) ?></div>
            <small class="text-muted"><?= e($u['email'] ?: '-') ?></small>
          </td>
          <td>
            <?php if ($u['role'] === 'admin'): ?>
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Administrator</span>
            <?php elseif ($u['role'] === 'pusat'): ?>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Staf Admisi / Pusat</span>
            <?php else: ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Kepala Cabang</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u['role'] === 'kepala_cabang' && $u['nama_cabang']): ?>
              <span class="badge-branch"><i class="bi bi-geo-alt-fill"></i> <?= e($u['nama_cabang']) ?> (<?= e($u['kode_cabang']) ?>)</span>
            <?php else: ?>
              <span class="text-muted small">&bull; Semua Cabang (Pusat) &bull;</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <span class="badge <?= $u['status'] === 'aktif' ? 'bg-success-subtle text-success' : 'bg-secondary' ?>">
              <?= ucfirst($u['status']) ?>
            </span>
          </td>
          <td class="small text-muted">
            <?= $u['terakhir_login'] ? format_tanggal_id($u['terakhir_login'], true) : 'Belum pernah' ?>
          </td>
          <td class="text-end">
            <button type="button" class="btn btn-sm btn-light border py-1 px-2" onclick='openEditUserModal(<?= json_encode($u) ?>)'>
              <i class="bi bi-pencil"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL FORM USER -->
<div class="modal fade" id="modalUserForm" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST" action="">
        <input type="hidden" name="action" id="formUserAction" value="create">
        <input type="hidden" name="user_id" id="formUserId" value="">

        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold text-primary-fastabiq" id="userModalTitle">Tambah Pengguna Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Username</label>
              <input type="text" name="username" id="inputUsername" class="form-control form-control-sm" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Password</label>
              <input type="password" name="password" id="inputPassword" class="form-control form-control-sm" placeholder="Kosongkan jika tak diubah">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Nama Lengkap &amp; Gelar</label>
            <input type="text" name="nama_lengkap" id="inputNamaLengkap" class="form-control form-control-sm" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Peran (Role)</label>
              <select name="role" id="inputRole" class="form-select form-select-sm" onchange="toggleBranchSelect()">
                <option value="kepala_cabang">Kepala Cabang</option>
                <option value="pusat">Staf Admisi / Pusat</option>
                <option value="admin">Administrator Pusat</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Status Akun</label>
              <select name="status" id="inputStatusUser" class="form-select form-select-sm">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
          </div>

          <div class="mb-3" id="branchSelectGroup">
            <label class="form-label small fw-bold">Kewenangan Cabang (Khusus Kepala Cabang)</label>
            <select name="cabang_id" id="inputCabangId" class="form-select form-select-sm">
              <option value="">Pilih Cabang</option>
              <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>"><?= e($b['kode_cabang']) ?> - <?= e($b['nama_cabang']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text small text-muted">Kepala cabang hanya diizinkan melihat data cabangnya sendiri.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Email (Opsional)</label>
            <input type="email" name="email" id="inputEmail" class="form-control form-control-sm" placeholder="email@fastabiq.co.id">
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary-survey px-4">Simpan Pengguna</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let userModalInstance = null;
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('modalUserForm');
  if (el) userModalInstance = new bootstrap.Modal(el);
});

function toggleBranchSelect() {
  const role = document.getElementById('inputRole').value;
  const grp = document.getElementById('branchSelectGroup');
  grp.style.display = (role === 'kepala_cabang') ? 'block' : 'none';
}

function openCreateUserModal() {
  document.getElementById('formUserAction').value = 'create';
  document.getElementById('formUserId').value = '';
  document.getElementById('userModalTitle').textContent = 'Tambah Pengguna Baru';
  document.getElementById('inputUsername').value = '';
  document.getElementById('inputPassword').value = '';
  document.getElementById('inputNamaLengkap').value = '';
  document.getElementById('inputEmail').value = '';
  document.getElementById('inputRole').value = 'kepala_cabang';
  document.getElementById('inputCabangId').value = '';
  document.getElementById('inputStatusUser').value = 'aktif';
  toggleBranchSelect();

  if (userModalInstance) userModalInstance.show();
}

function openEditUserModal(u) {
  document.getElementById('formUserAction').value = 'update';
  document.getElementById('formUserId').value = u.id;
  document.getElementById('userModalTitle').textContent = `Edit Pengguna: ${u.username}`;
  document.getElementById('inputUsername').value = u.username;
  document.getElementById('inputPassword').value = '';
  document.getElementById('inputNamaLengkap').value = u.nama_lengkap;
  document.getElementById('inputEmail').value = u.email || '';
  document.getElementById('inputRole').value = u.role;
  document.getElementById('inputCabangId').value = u.cabang_id || '';
  document.getElementById('inputStatusUser').value = u.status;
  toggleBranchSelect();

  if (userModalInstance) userModalInstance.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

