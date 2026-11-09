<?php
/**
 * KUESIONER ANGGOTA - Master Data Cabang & QR Code
 * Sesuai Dokumen Bagian 6D & Desain Mockup PDF Halaman 10
 */

$activePage = 'branches';
$pageTitle = 'Master Data Cabang & QR Code';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/qrcode_helper.php';

$db = get_db_connection();
$isFullAdmin = in_array($currentUser['role'], ['admin', 'pusat'], true);

$message = null;
$error = null;

// Handle Simpan / Tambah / Edit Cabang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isFullAdmin) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['cabang_id'] ?? 0);
        $kode = strtoupper(trim($_POST['kode_cabang'] ?? ''));
        $nama = trim($_POST['nama_cabang'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $kepala = trim($_POST['kepala_cabang'] ?? '');
        $status = $_POST['status'] ?? 'aktif';
        $catatan = trim($_POST['catatan'] ?? '');

        if (empty($kode) || empty($nama)) {
            $error = 'Kode Cabang dan Nama Cabang wajib diisi.';
        } else {
            try {
                if ($action === 'create') {
                    $token = 'FASTABIQ_' . $kode . '_' . bin2hex(random_bytes(4));
                    $stmt = $db->prepare("
                        INSERT INTO cabang (kode_cabang, nama_cabang, alamat, telepon, kepala_cabang, status, catatan, token_qr)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$kode, $nama, $alamat, $telepon, $kepala, $status, $catatan, $token]);
                    $message = "Cabang $nama ($kode) berhasil ditambahkan.";
                } else {
                    $stmt = $db->prepare("
                        UPDATE cabang 
                        SET kode_cabang = ?, nama_cabang = ?, alamat = ?, telepon = ?, kepala_cabang = ?, status = ?, catatan = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$kode, $nama, $alamat, $telepon, $kepala, $status, $catatan, $id]);
                    $message = "Data cabang $nama berhasil diperbarui.";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Kode Cabang '$kode' sudah terdaftar. Gunakan kode lain.";
                } else {
                    $error = "Gagal menyimpan cabang: " . $e->getMessage();
                }
            }
        }
    }
}

// Ambil semua cabang
$cabangRows = $db->query("SELECT * FROM cabang ORDER BY kode_cabang ASC")->fetchAll();
$totalCabang = count($cabangRows);
$totalAktif = count(array_filter($cabangRows, fn($c) => $c['status'] === 'aktif'));
$totalNonaktif = count(array_filter($cabangRows, fn($c) => $c['status'] === 'nonaktif'));
$totalGabung = count(array_filter($cabangRows, fn($c) => $c['status'] === 'gabung'));
?>

<!-- Header Info & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 no-print">
  <div>
    <h1 class="h3 fw-bold mb-1">Master Data Cabang &amp; QR Code</h1>
    <p class="text-muted small mb-0">
      Kelola identitas cabang, status layanan, dan unduh QR Code unik untuk meja pelayanan anggota.
    </p>
  </div>

  <div class="d-flex gap-2">
    <?php if ($isFullAdmin): ?>
    <button type="button" class="btn btn-primary-survey btn-sm px-3 shadow-sm rounded-pill" onclick="openCreateModal()">
      <i class="bi bi-plus-circle me-1"></i> Tambah Cabang Baru
    </button>
    <?php endif; ?>
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

<!-- Meta Stat Pill Bar -->
<div class="d-flex flex-wrap gap-2 mb-4">
  <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm">
    Total: <strong><?= $totalCabang ?> Cabang</strong> (Kapasitas s/d 100)
  </span>
  <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
    Aktif: <strong><?= $totalAktif ?> Cabang</strong>
  </span>
  <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill">
    Digabung: <strong><?= $totalGabung ?> Cabang</strong> (F09 &rarr; F02)
  </span>
  <?php if ($totalNonaktif > 0): ?>
  <span class="badge bg-danger-subtle text-danger border px-3 py-2 rounded-pill">
    Nonaktif: <strong><?= $totalNonaktif ?> Cabang</strong>
  </span>
  <?php endif; ?>
</div>

<div class="row g-4">
  <!-- Kolom Tabel Daftar Cabang -->
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="input-group input-group-sm w-50">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari nama atau kode cabang..." oninput="filterBranchTable()">
        </div>
        <small class="text-muted">Menampilkan <?= $totalCabang ?> cabang</small>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="branchesTable">
          <thead class="table-light" style="font-size: 0.8rem; text-transform: uppercase;">
            <tr>
              <th style="width: 75px;">Kode</th>
              <th>Nama Cabang</th>
              <th>Kepala Cabang</th>
              <th class="text-center">Status</th>
              <th class="text-center">QR Survei</th>
              <?php if ($isFullAdmin): ?><th class="text-end">Aksi</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cabangRows as $c): 
              $surveyUrl = FastabiqQRCode::getSurveyUrl($c);
              $isMerged = ($c['status'] === 'gabung');
            ?>
            <tr class="branch-row" data-search="<?= strtolower(e($c['kode_cabang'] . ' ' . $c['nama_cabang'] . ' ' . $c['kepala_cabang'])) ?>">
              <td>
                <span class="badge bg-light text-dark border fw-bold"><?= e($c['kode_cabang']) ?></span>
              </td>
              <td>
                <div class="fw-bold"><?= e($c['nama_cabang']) ?></div>
                <small class="text-muted"><?= e($c['alamat'] ?: 'Pati, Jawa Tengah') ?></small>
                <?php if ($c['catatan']): ?>
                  <div class="text-muted fst-italic" style="font-size: 0.72rem;"><?= e($c['catatan']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?= e($c['kepala_cabang'] ?: '-') ?>
              </td>
              <td class="text-center">
                <?php if ($c['status'] === 'aktif'): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                <?php elseif ($c['status'] === 'gabung'): ?>
                  <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Digabung F02</span>
                <?php else: ?>
                  <span class="badge bg-danger-subtle text-danger border px-2 py-1">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($c['status'] === 'aktif'): ?>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" style="font-size: 0.76rem;"
                        onclick='showQRModal(<?= json_encode($c) ?>, "<?= e($surveyUrl) ?>")'>
                  <i class="bi bi-qr-code"></i> Lihat QR
                </button>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
              <?php if ($isFullAdmin): ?>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border py-1 px-2" style="font-size: 0.76rem;" onclick='openEditModal(<?= json_encode($c) ?>)'>
                  <i class="bi bi-pencil"></i>
                </button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Kolom Pratinjau Stand QR Meja Pelayanan (Sesuai PDF Mockup) -->
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 20px;">
      <h2 class="h6 fw-bold text-muted text-uppercase mb-3" style="letter-spacing: 0.05em;">
        Pratinjau QR Code Meja
      </h2>

      <div id="previewEmptyState" class="text-center py-5 text-muted">
        <i class="bi bi-qr-code fs-1 d-block mb-2 text-secondary"></i>
        <small>Pilih tombol &ldquo;Lihat QR&rdquo; pada salah satu cabang untuk melihat pratinjau dan mencetak kartu akrilik meja.</small>
      </div>

      <div id="previewCardWrap" style="display: none;">
        <!-- Stand Card Preview Box -->
        <div class="p-3 rounded-4 border text-center shadow-sm" style="background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 40%);">
          <div class="d-flex align-items-center justify-content-center gap-1 mb-1">
            <span class="fs-5">✨</span>
            <span class="fw-bold text-primary-fastabiq" style="font-size: 0.95rem;">KUESIONER ANGGOTA</span>
          </div>
          <div class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 mb-2" id="previewBranchBadge">
            Cabang Pati Kota
          </div>
          <div class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">Penilaian Kepuasan Layanan</div>
          <p class="text-muted mb-3" style="font-size: 0.75rem;">Silakan pindai menggunakan kamera HP Anda</p>

          <div id="qrCodeContainer" class="d-flex justify-content-center mb-3"></div>

          <div class="p-2 bg-light rounded-3 border text-start mb-3" style="font-size: 0.72rem;">
            <div class="text-muted">Tautan Langsung:</div>
            <code id="previewDirectLink" class="text-break" style="font-size: 0.72rem;"></code>
          </div>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-success w-50 rounded-pill" onclick="downloadQRCode()">
              <i class="bi bi-download me-1"></i> Unduh QR
            </button>
            <button type="button" class="btn btn-sm btn-primary-survey w-50 rounded-pill" onclick="printTentCard()">
              <i class="bi bi-printer me-1"></i> Cetak Kartu
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL TAMBAH / EDIT CABANG -->
<?php if ($isFullAdmin): ?>
<div class="modal fade" id="modalBranchForm" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST" action="">
        <input type="hidden" name="action" id="formAction" value="create">
        <input type="hidden" name="cabang_id" id="formCabangId" value="">

        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold text-primary-fastabiq" id="formModalTitle">Tambah Cabang Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label small fw-bold">Kode Cabang</label>
              <input type="text" name="kode_cabang" id="inputKode" class="form-control form-control-sm text-uppercase" placeholder="F28" required>
            </div>
            <div class="col-8">
              <label class="form-label small fw-bold">Nama Cabang</label>
              <input type="text" name="nama_cabang" id="inputNama" class="form-control form-control-sm" placeholder="Contoh: Pati Kota" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Alamat Kantor Cabang</label>
            <input type="text" name="alamat" id="inputAlamat" class="form-control form-control-sm" placeholder="Jl. Raya ...">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Kepala Cabang</label>
              <input type="text" name="kepala_cabang" id="inputKepala" class="form-control form-control-sm" placeholder="Nama Pimpinan">
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">No. Telepon</label>
              <input type="text" name="telepon" id="inputTelepon" class="form-control form-control-sm" placeholder="0295-...">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold">Status Layanan</label>
              <select name="status" id="inputStatus" class="form-select form-select-sm">
                <option value="aktif">Aktif</option>
                <option value="gabung">Digabung (Contoh F09)</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">Catatan Khusus</label>
              <input type="text" name="catatan" id="inputCatatan" class="form-control form-control-sm" placeholder="Keterangan merger/lainnya">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary-survey px-4">Simpan Cabang</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- MODAL CETAK TENT CARD A5 STAND MEJA -->
<div class="modal fade" id="modalPrintTentCard" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom-0 no-print">
        <h5 class="modal-title fw-bold text-primary-fastabiq">Pratinjau Cetak Stand Meja (A5 Tent Card)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <!-- Area Cetak Stand Card -->
        <div id="printTentCardArea" class="p-5 border rounded-4 bg-white mx-auto shadow-sm" style="max-width: 480px; border: 2px solid #0f766e !important;">
          <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
            <span style="font-size: 2rem;">✨</span>
            <span class="fw-bold fs-3 text-primary-fastabiq">BMT FASTABIQ</span>
          </div>
          <div class="text-muted small text-uppercase fw-bold mb-3" style="letter-spacing: 0.1em;">
            KSPPS BMT Fastabiq Khoiro Ummah
          </div>

          <div class="p-3 my-3 bg-light rounded-3 border">
            <span class="badge bg-success-subtle text-success border border-success px-3 py-1 fs-6" id="tentBranchBadge">
              Cabang Pati Kota (F01)
            </span>
            <h2 class="h4 fw-bold mt-3 mb-1 text-dark">KUESIONER KEPUASAN ANGGOTA</h2>
            <p class="text-secondary small mb-0">Bagaimana Pelayanan Kami Hari Ini?</p>
          </div>

          <div id="tentQrCodeBox" class="my-4 d-flex justify-content-center"></div>

          <div class="p-3 bg-light rounded-3 text-start small mb-3 border">
            <strong>Petunjuk Pengisian:</strong>
            <ol class="mb-0 ps-3 mt-1 text-muted">
              <li>Buka kamera atau aplikasi pemindai QR di HP Anda.</li>
              <li>Arahkan kamera ke kode QR di atas.</li>
              <li>Beri penilaian 1–5 untuk keramahan, kecepatan, dan kenyamanan.</li>
              <li>Hanya membutuhkan waktu &plusmn; 1–2 menit, tanpa perlu login.</li>
            </ol>
          </div>

          <div class="text-center fst-italic text-secondary small">
            &ldquo;Suara Anda, Semangat Kami untuk Melayani Lebih Baik.&rdquo;
          </div>
        </div>
      </div>
      <div class="modal-footer border-top-0 no-print">
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-sm btn-primary-survey px-4" onclick="window.print()">
          <i class="bi bi-printer me-1"></i> Cetak Kartu Meja Ini
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let currentSelectedBranch = null;
let currentSurveyUrl = '';
let branchFormModal = null;
let printTentModal = null;

document.addEventListener('DOMContentLoaded', () => {
  const bfEl = document.getElementById('modalBranchForm');
  if (bfEl) branchFormModal = new bootstrap.Modal(bfEl);

  const ptEl = document.getElementById('modalPrintTentCard');
  if (ptEl) printTentModal = new bootstrap.Modal(ptEl);

  // Auto-select first active branch for preview
  const firstBtn = document.querySelector('.branch-row button');
  if (firstBtn) firstBtn.click();
});

function filterBranchTable() {
  const query = document.getElementById('tableSearchInput').value.toLowerCase();
  const rows = document.querySelectorAll('.branch-row');
  rows.forEach(r => {
    const text = r.getAttribute('data-search');
    r.style.display = text.includes(query) ? '' : 'none';
  });
}

function showQRModal(branch, surveyUrl) {
  currentSelectedBranch = branch;
  currentSurveyUrl = surveyUrl;

  document.getElementById('previewEmptyState').style.display = 'none';
  document.getElementById('previewCardWrap').style.display = 'block';

  document.getElementById('previewBranchBadge').textContent = `${branch.nama_cabang} (${branch.kode_cabang})`;
  document.getElementById('previewDirectLink').textContent = surveyUrl;

  // Render SVG QR Code inline
  const qrBox = document.getElementById('qrCodeContainer');
  qrBox.innerHTML = `
    <img src="<?= BASE_URL ?>/includes/qrcode_render.php?token=${encodeURIComponent(branch.token_qr)}&size=200" 
         alt="QR Code ${branch.nama_cabang}" class="img-fluid border rounded-3 p-2 bg-white shadow-sm" style="width: 190px; height: 190px;">
  `;
}

function printTentCard() {
  if (!currentSelectedBranch) return;
  document.getElementById('tentBranchBadge').textContent = `${currentSelectedBranch.nama_cabang} (${currentSelectedBranch.kode_cabang})`;
  document.getElementById('tentQrCodeBox').innerHTML = `
    <img src="<?= BASE_URL ?>/includes/qrcode_render.php?token=${encodeURIComponent(currentSelectedBranch.token_qr)}&size=240" 
         alt="QR Code ${currentSelectedBranch.nama_cabang}" class="img-fluid border rounded-3 p-2 bg-white shadow-sm" style="width: 220px; height: 220px;">
  `;
  if (printTentModal) printTentModal.show();
}

function downloadQRCode() {
  if (!currentSelectedBranch) return;
  const link = document.createElement('a');
  link.href = `<?= BASE_URL ?>/includes/qrcode_render.php?token=${encodeURIComponent(currentSelectedBranch.token_qr)}&size=400&download=1`;
  link.download = `QR_${currentSelectedBranch.kode_cabang}_${currentSelectedBranch.nama_cabang}.svg`;
  link.click();
}

function openCreateModal() {
  document.getElementById('formAction').value = 'create';
  document.getElementById('formCabangId').value = '';
  document.getElementById('formModalTitle').textContent = 'Tambah Cabang Baru';
  document.getElementById('inputKode').value = '';
  document.getElementById('inputNama').value = '';
  document.getElementById('inputAlamat').value = '';
  document.getElementById('inputTelepon').value = '';
  document.getElementById('inputKepala').value = '';
  document.getElementById('inputStatus').value = 'aktif';
  document.getElementById('inputCatatan').value = '';

  if (branchFormModal) branchFormModal.show();
}

function openEditModal(b) {
  document.getElementById('formAction').value = 'update';
  document.getElementById('formCabangId').value = b.id;
  document.getElementById('formModalTitle').textContent = `Edit Cabang: ${b.nama_cabang} (${b.kode_cabang})`;
  document.getElementById('inputKode').value = b.kode_cabang;
  document.getElementById('inputNama').value = b.nama_cabang;
  document.getElementById('inputAlamat').value = b.alamat || '';
  document.getElementById('inputTelepon').value = b.telepon || '';
  document.getElementById('inputKepala').value = b.kepala_cabang || '';
  document.getElementById('inputStatus').value = b.status;
  document.getElementById('inputCatatan').value = b.catatan || '';

  if (branchFormModal) branchFormModal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

