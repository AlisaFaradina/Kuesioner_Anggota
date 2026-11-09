<?php
/**
 * KUESIONER ANGGOTA - Master Kuesioner & Pertanyaan Layanan
 * Pengelolaan Pertanyaan Dinamis & Versi Instrumen Kuesioner
 */

$activePage = 'questions';
$pageTitle = 'Master Kuesioner & Pertanyaan';
require_once __DIR__ . '/../includes/header.php';

require_role(['admin', 'pusat']);
$db = get_db_connection();

$message = null;
$error = null;

// Handle CRUD Pertanyaan & Kuesioner
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_pertanyaan' || $action === 'update_pertanyaan') {
        $id = (int)($_POST['pertanyaan_id'] ?? 0);
        $kuesionerId = (int)$_POST['kuesioner_id'];
        $urutan = (int)$_POST['urutan'];
        $kode = strtoupper(trim($_POST['kode'] ?? 'P' . $urutan));
        $aspek = trim($_POST['aspek'] ?? '');
        $label = trim($_POST['label_aspek'] ?? strtoupper($aspek));
        $teks = trim($_POST['teks_pertanyaan'] ?? '');
        $status = $_POST['status'] ?? 'aktif';

        if (empty($aspek) || empty($teks)) {
            $error = 'Aspek dan Teks Pertanyaan wajib diisi.';
        } else {
            if ($action === 'create_pertanyaan') {
                $stmt = $db->prepare("
                    INSERT INTO pertanyaan (kuesioner_id, urutan, kode, aspek, label_aspek, teks_pertanyaan, tipe, wajib, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'rating_5', 1, ?)
                ");
                $stmt->execute([$kuesionerId, $urutan, $kode, $aspek, $label, $teks, $status]);
                $message = "Pertanyaan baru ($kode) berhasil ditambahkan.";
            } else {
                $stmt = $db->prepare("
                    UPDATE pertanyaan
                    SET urutan = ?, kode = ?, aspek = ?, label_aspek = ?, teks_pertanyaan = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$urutan, $kode, $aspek, $label, $teks, $status, $id]);
                $message = "Pertanyaan ($kode) berhasil diperbarui.";
            }
        }
    }
}

// Ambil kuesioner aktif
$kuesioner = $db->query("SELECT * FROM kuesioner ORDER BY id DESC LIMIT 1")->fetch();
$pertanyaanList = [];
if ($kuesioner) {
    $stmtPert = $db->prepare("SELECT * FROM pertanyaan WHERE kuesioner_id = ? ORDER BY urutan ASC");
    $stmtPert->execute([$kuesioner['id']]);
    $pertanyaanList = $stmtPert->fetchAll();
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Master Kuesioner &amp; Pertanyaan</h1>
    <p class="text-muted small mb-0">
      Kelola instrumen kuesioner dan daftar pertanyaan rating secara dinamis tanpa mengubah kode aplikasi.
    </p>
  </div>

  <div>
    <button type="button" class="btn btn-primary-survey btn-sm rounded-pill px-3 shadow-sm" onclick="openCreateModal()">
      <i class="bi bi-plus-circle me-1"></i> Tambah Pertanyaan
    </button>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success border-0 rounded-3 p-3 mb-4 small d-flex align-items-center">
  <i class="bi bi-check-circle-fill fs-5 me-2"></i>
  <div><?= e($message) ?></div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger border-0 rounded-3 p-3 mb-4 small d-flex align-items-center">
  <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
  <div><?= e($error) ?></div>
</div>
<?php endif; ?>

<!-- Info Card Kuesioner Aktif -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">Instrumen Aktif</span>
      <h2 class="h5 fw-bold mb-1"><?= e($kuesioner['judul'] ?? 'Kuesioner Kepuasan Layanan') ?></h2>
      <div class="text-muted small">
        Versi: <strong><?= e($kuesioner['versi'] ?? 'v1.0') ?></strong> &bull; Slogan: <em>&ldquo;<?= e($kuesioner['slogan'] ?? '') ?>&rdquo;</em>
      </div>
    </div>
    <div class="text-end">
      <span class="badge bg-light text-dark border fs-6 px-3 py-2">
        <?= count($pertanyaanList) ?> Pertanyaan Rating (Skala 1–5)
      </span>
    </div>
  </div>
</div>

<!-- Tabel Pertanyaan -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
      <thead class="table-light">
        <tr style="font-size: 0.78rem; text-transform: uppercase;">
          <th style="width: 50px;">No</th>
          <th style="width: 70px;">Kode</th>
          <th style="width: 170px;">Aspek / Dimensi</th>
          <th>Teks Pertanyaan</th>
          <th style="width: 90px;" class="text-center">Skala</th>
          <th style="width: 80px;" class="text-center">Status</th>
          <th style="width: 70px;" class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pertanyaanList as $p): ?>
        <tr>
          <td class="text-center fw-bold"><?= $p['urutan'] ?></td>
          <td><span class="badge bg-light text-dark border"><?= e($p['kode']) ?></span></td>
          <td>
            <div class="fw-bold"><?= e($p['aspek']) ?></div>
            <small class="text-muted"><?= e($p['label_aspek']) ?></small>
          </td>
          <td><?= e($p['teks_pertanyaan']) ?></td>
          <td class="text-center"><span class="badge bg-info-subtle text-info border">1 - 5</span></td>
          <td class="text-center">
            <span class="badge <?= $p['status'] === 'aktif' ? 'bg-success-subtle text-success' : 'bg-secondary' ?>">
              <?= ucfirst($p['status']) ?>
            </span>
          </td>
          <td class="text-end">
            <button type="button" class="btn btn-sm btn-light border py-1 px-2" onclick='openEditModal(<?= json_encode($p) ?>)'>
              <i class="bi bi-pencil"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL FORM PERTANYAAN -->
<div class="modal fade" id="modalPertanyaanForm" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST" action="">
        <input type="hidden" name="action" id="formPertAction" value="create_pertanyaan">
        <input type="hidden" name="pertanyaan_id" id="formPertId" value="">
        <input type="hidden" name="kuesioner_id" value="<?= $kuesioner['id'] ?? 1 ?>">

        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold text-primary-fastabiq" id="pertModalTitle">Tambah Pertanyaan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label small fw-bold">Nomor Urut</label>
              <input type="number" name="urutan" id="inputUrutan" class="form-control form-control-sm" value="<?= count($pertanyaanList) + 1 ?>" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-bold">Kode Soal</label>
              <input type="text" name="kode" id="inputKodePert" class="form-control form-control-sm" placeholder="P11" required>
            </div>
            <div class="col-4">
              <label class="form-label small fw-bold">Status</label>
              <select name="status" id="inputStatusPert" class="form-select form-select-sm">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Aspek Layanan</label>
              <input type="text" name="aspek" id="inputAspek" class="form-control form-control-sm" placeholder="Contoh: Keramahan" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Label Header Kartu</label>
              <input type="text" name="label_aspek" id="inputLabelAspek" class="form-control form-control-sm" placeholder="Contoh: KERAMAHAN PETUGAS" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Teks Pertanyaan (Untuk Anggota)</label>
            <textarea name="teks_pertanyaan" id="inputTeksPert" class="form-control form-control-sm" rows="3" placeholder="Bagaimana ... saat melayani Anda?" required></textarea>
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary-survey px-4">Simpan Pertanyaan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let pertModalInstance = null;
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('modalPertanyaanForm');
  if (el) pertModalInstance = new bootstrap.Modal(el);
});

function openCreateModal() {
  document.getElementById('formPertAction').value = 'create_pertanyaan';
  document.getElementById('formPertId').value = '';
  document.getElementById('pertModalTitle').textContent = 'Tambah Pertanyaan Rating Baru';
  document.getElementById('inputUrutan').value = '<?= count($pertanyaanList) + 1 ?>';
  document.getElementById('inputKodePert').value = 'P<?= count($pertanyaanList) + 1 ?>';
  document.getElementById('inputAspek').value = '';
  document.getElementById('inputLabelAspek').value = '';
  document.getElementById('inputTeksPert').value = '';
  document.getElementById('inputStatusPert').value = 'aktif';

  if (pertModalInstance) pertModalInstance.show();
}

function openEditModal(p) {
  document.getElementById('formPertAction').value = 'update_pertanyaan';
  document.getElementById('formPertId').value = p.id;
  document.getElementById('pertModalTitle').textContent = `Edit Pertanyaan (${p.kode})`;
  document.getElementById('inputUrutan').value = p.urutan;
  document.getElementById('inputKodePert').value = p.kode;
  document.getElementById('inputAspek').value = p.aspek;
  document.getElementById('inputLabelAspek').value = p.label_aspek;
  document.getElementById('inputTeksPert').value = p.teks_pertanyaan;
  document.getElementById('inputStatusPert').value = p.status;

  if (pertModalInstance) pertModalInstance.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

