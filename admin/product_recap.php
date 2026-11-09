<?php
/**
 * KUESIONER ANGGOTA - Rekap Produk yang Ditawarkan Cabang
 * Sesuai Dokumen WhatsApp & Format Excel CSV BMT Fastabiq
 */

$activePage = 'product_recap';
$pageTitle = 'Rekap Produk Ditawarkan';
require_once __DIR__ . '/../includes/header.php';

$db = get_db_connection();

// Periode Bulan (Default Oktober 2026)
$selectedPeriod = $_GET['periode'] ?? '2026-10-01';

// Ambil semua cabang aktif
$branches = get_all_branches(false);

// Handle Form Update / Simpan Rekap Produk Cabang
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($currentUser['role'], ['admin', 'pusat'], true)) {
    $cabangId = (int)$_POST['cabang_id'];
    $periode = $_POST['periode'];
    $pembiayaan = isset($_POST['pembiayaan']) ? 1 : 0;
    $arisan = isset($_POST['arisan']) ? 1 : 0;
    $sisuka = isset($_POST['sisuka']) ? 1 : 0;
    $sirela = isset($_POST['sirela']) ? 1 : 0;
    $simpelpres = isset($_POST['simpelpres']) ? 1 : 0;
    $simapan = isset($_POST['simapan']) ? 1 : 0;
    $simabrur = isset($_POST['simabrur']) ? 1 : 0;
    $umroh = isset($_POST['umroh']) ? 1 : 0;
    $salam = isset($_POST['salam']) ? 1 : 0;
    $lumbungBeras = isset($_POST['lumbung_beras']) ? 1 : 0;
    $arisanBerkah = isset($_POST['arisan_berkah']) ? 1 : 0;

    $hasAny = ($pembiayaan || $arisan || $sisuka || $sirela || $simpelpres || $simapan || $simabrur || $umroh || $salam || $lumbungBeras || $arisanBerkah);
    $persenNilai = $hasAny ? 100.00 : 0.00;

    $stmtSave = $db->prepare("
        INSERT INTO rekap_produk_cabang (
            cabang_id, periode_bulan, pembiayaan, arisan, sisuka, sirela, simpelpres,
            simapan, simabrur, umroh, salam, lumbung_beras, arisan_berkah, persentase_nilai,
            petugas_nama, petugas_jabatan, tanggal_laporan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
        ON DUPLICATE KEY UPDATE
            pembiayaan = VALUES(pembiayaan), arisan = VALUES(arisan), sisuka = VALUES(sisuka),
            sirela = VALUES(sirela), simpelpres = VALUES(simpelpres), simapan = VALUES(simapan),
            simabrur = VALUES(simabrur), umroh = VALUES(umroh), salam = VALUES(salam),
            lumbung_beras = VALUES(lumbung_beras), arisan_berkah = VALUES(arisan_berkah),
            persentase_nilai = VALUES(persentase_nilai), petugas_nama = VALUES(petugas_nama)
    ");
    $stmtSave->execute([
        $cabangId, $periode, $pembiayaan, $arisan, $sisuka, $sirela, $simpelpres,
        $simapan, $simabrur, $umroh, $salam, $lumbungBeras, $arisanBerkah, $persenNilai,
        $currentUser['nama_lengkap'], 'Staf Layanan'
    ]);
    $message = 'Data produk yang ditawarkan cabang berhasil diperbarui.';
}

// Ambil data rekap produk untuk periode terpilih
$sqlRekap = "
    SELECT c.id AS cid, c.kode_cabang, c.nama_cabang, c.status,
           r.pembiayaan, r.arisan, r.sisuka, r.sirela, r.simpelpres,
           r.simapan, r.simabrur, r.umroh, r.salam, r.lumbung_beras, r.arisan_berkah,
           r.persentase_nilai, r.petugas_nama, r.petugas_jabatan, r.tanggal_laporan
    FROM cabang c
    LEFT JOIN rekap_produk_cabang r ON c.id = r.cabang_id AND r.periode_bulan = ?
    WHERE c.status != 'nonaktif'
    ORDER BY c.kode_cabang ASC
";
$stmtRekap = $db->prepare($sqlRekap);
$stmtRekap->execute([$selectedPeriod]);
$rekapRows = $stmtRekap->fetchAll();

// Daftar periode yang tersedia di database
$periods = $db->query("SELECT DISTINCT periode_bulan FROM rekap_produk_cabang ORDER BY periode_bulan DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($periods)) {
    $periods = ['2026-10-01', '2026-09-01', '2026-08-01', '2026-01-01'];
}
?>

<!-- Header Info & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 no-print">
  <div>
    <h1 class="h3 fw-bold mb-1">Rekap Produk yang Ditawarkan Cabang</h1>
    <p class="text-muted small mb-0">
      Monitoring penawaran produk simpanan, pembiayaan, dan layanan syariah per cabang per bulan.
    </p>
  </div>

  <div class="d-flex gap-2 align-items-center">
    <form method="GET" class="d-flex align-items-center gap-2 bg-white p-2 rounded-3 border shadow-sm">
      <small class="text-muted fw-bold ps-1">Periode:</small>
      <select name="periode" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit()">
        <?php foreach ($periods as $p): ?>
        <option value="<?= $p ?>" <?= $selectedPeriod === $p ? 'selected' : '' ?>>
          <?= date('F Y', strtotime($p)) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </form>

    <a href="<?= BASE_URL ?>/admin/product_export_excel.php?periode=<?= urlencode($selectedPeriod) ?>" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
      <i class="bi bi-file-earmark-spreadsheet me-1"></i> Unduh Excel Rekap Produk
    </a>
  </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success border-0 rounded-3 p-3 mb-4 small d-flex align-items-center">
  <i class="bi bi-check-circle-fill fs-5 me-2"></i>
  <div><?= e($message) ?></div>
</div>
<?php endif; ?>

<!-- Rekap Box (Sesuai Spreadsheet User) -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
  <div class="border-bottom pb-3 mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <span class="badge bg-primary-subtle text-primary border mb-1">KUESIONER OPR &amp; IT_KPI CABANG</span>
        <h2 class="h5 fw-bold mb-0">PRODUK YANG DITAWARKAN CABANG</h2>
        <div class="text-muted small">Periode: <?= date('F Y', strtotime($selectedPeriod)) ?> &bull; KSPPS BMT Fastabiq Khoiro Ummah</div>
      </div>
      <div class="text-end small text-muted">
        <div>Kota Penerbitan: <strong>Pati</strong></div>
        <div>Status F09: <span class="badge bg-secondary-subtle text-secondary">Digabung ke F02 Wedarijaksa</span></div>
      </div>
    </div>
  </div>

  <div class="table-responsive mb-4">
    <table class="table table-bordered table-hover align-middle" style="font-size: 0.83rem;">
      <thead class="table-light text-center" style="font-size: 0.78rem;">
        <tr>
          <th style="width: 50px;">Cabang</th>
          <th>Nama Cabang</th>
          <th style="width: 70px;">Pembiayaan</th>
          <th style="width: 60px;">Arisan</th>
          <th style="width: 60px;">Sisuka</th>
          <th style="width: 60px;">Sirela</th>
          <th style="width: 75px;">Simpelpres</th>
          <th style="width: 65px;">Simapan</th>
          <th style="width: 75px;">Simabrur / Haji</th>
          <th style="width: 60px;">Umroh</th>
          <th style="width: 60px;">Salam</th>
          <th style="width: 75px;">Lumbung Beras</th>
          <th style="width: 75px;">Arisan Berkah</th>
          <th style="width: 60px;">Nilai</th>
          <?php if (in_array($currentUser['role'], ['admin', 'pusat'], true)): ?>
          <th style="width: 60px;" class="no-print">Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php 
        $activeCount = 0;
        $total100 = 0;
        foreach ($rekapRows as $r): 
          $isMerged = ($r['status'] === 'gabung');
          $hasData = ($r['persentase_nilai'] !== null);
          $val = (float)($r['persentase_nilai'] ?? 0);
          if (!$isMerged) {
              $activeCount++;
              if ($val >= 100) $total100++;
          }
        ?>
        <tr class="<?= $isMerged ? 'table-light text-muted' : '' ?>">
          <td class="text-center fw-bold"><?= e($r['kode_cabang']) ?></td>
          <td>
            <?= e($r['nama_cabang']) ?>
            <?php if ($isMerged): ?>
              <span class="badge bg-secondary-subtle text-secondary small ms-1">Digabung F02</span>
            <?php endif; ?>
          </td>
          
          <td class="text-center <?= $r['pembiayaan'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['pembiayaan'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['arisan'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['arisan'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['sisuka'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['sisuka'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['sirela'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['sirela'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['simpelpres'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['simpelpres'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['simapan'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['simapan'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['simabrur'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['simabrur'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['umroh'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['umroh'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['salam'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['salam'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['lumbung_beras'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['lumbung_beras'] ? '✓' : '-' ?>
          </td>
          <td class="text-center <?= $r['arisan_berkah'] ? 'text-success fw-bold' : 'text-muted' ?>">
            <?= $r['arisan_berkah'] ? '✓' : '-' ?>
          </td>

          <td class="text-center fw-bold">
            <?php if ($isMerged): ?>
              <span class="text-muted small">0%</span>
            <?php else: ?>
              <span class="<?= $val >= 100 ? 'text-success' : 'text-danger' ?>">
                <?= number_format($val, 0) ?>%
              </span>
            <?php endif; ?>
          </td>

          <?php if (in_array($currentUser['role'], ['admin', 'pusat'], true)): ?>
          <td class="text-center no-print">
            <?php if (!$isMerged): ?>
            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" 
                    onclick='openEditModal(<?= json_encode($r) ?>)'>
              Edit
            </button>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light fw-bold" style="font-size: 0.85rem;">
        <tr>
          <td colspan="13" class="text-end">Pencapaian Cabang Menawarkan Produk (100%):</td>
          <td class="text-center text-success">
            <?= $activeCount > 0 ? round(($total100 / $activeCount) * 100, 1) : 0 ?>%
          </td>
          <?php if (in_array($currentUser['role'], ['admin', 'pusat'], true)): ?><td></td><?php endif; ?>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- Pengesahan Admisi / Staf Layanan -->
  <div class="row pt-3 align-items-end">
    <div class="col-8">
      <div class="text-muted small">
        <strong>Keterangan Format Spreadsheet:</strong><br>
        &bull; Checklist (&check;) menandakan produk aktif ditawarkan petugas kepada anggota di kantor cabang.<br>
        &bull; Nilai 100% diberikan jika cabang secara konsisten menawarkan minimal satu varian produk syariah unggulan.<br>
        &bull; Rekapitulasi dilaporkan secara periodik kepada Pimpinan Operasional &amp; IT BMT Fastabiq.
      </div>
    </div>
    <div class="col-4 text-center">
      <div class="small text-secondary mb-1">Pati, <?= format_tanggal_id(date('Y-m-d')) ?></div>
      <div class="small fw-bold text-dark">Petugas Admisi / Staf,</div>
      <div style="height: 55px;"></div>
      <div class="fw-bold text-decoration-underline text-dark">Ariyani Chairia / Endang K</div>
      <div class="small text-muted">Staf Operasional &amp; Layanan</div>
    </div>
  </div>
</div>

<!-- MODAL EDIT PRODUK CABANG -->
<div class="modal fade" id="modalEditProduct" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST" action="">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold text-primary-fastabiq" id="editModalTitle">Edit Produk Cabang</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="cabang_id" id="editCabangId">
          <input type="hidden" name="periode" value="<?= e($selectedPeriod) ?>">

          <p class="text-muted small mb-3">Centang produk yang aktif ditawarkan di cabang ini pada periode terpilih:</p>

          <div class="row g-2">
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="pembiayaan" id="ch_pembiayaan">
                <label class="form-check-label small" for="ch_pembiayaan">Pembiayaan</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="arisan" id="ch_arisan">
                <label class="form-check-label small" for="ch_arisan">Arisan</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="sisuka" id="ch_sisuka">
                <label class="form-check-label small" for="ch_sisuka">Sisuka</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="sirela" id="ch_sirela">
                <label class="form-check-label small" for="ch_sirela">Sirela</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="simpelpres" id="ch_simpelpres">
                <label class="form-check-label small" for="ch_simpelpres">Simpelpres</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="simapan" id="ch_simapan">
                <label class="form-check-label small" for="ch_simapan">Simapan</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="simabrur" id="ch_simabrur">
                <label class="form-check-label small" for="ch_simabrur">Simabrur / Haji</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="umroh" id="ch_umroh">
                <label class="form-check-label small" for="ch_umroh">Umroh</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="salam" id="ch_salam">
                <label class="form-check-label small" for="ch_salam">Salam (Aplikasi)</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="lumbung_beras" id="ch_lumbung_beras">
                <label class="form-check-label small" for="ch_lumbung_beras">Lumbung Beras</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="arisan_berkah" id="ch_arisan_berkah">
                <label class="form-check-label small" for="ch_arisan_berkah">Arisan Berkah</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0">
          <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary-survey px-3">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let editModalInstance = null;
document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('modalEditProduct');
  if (modalEl) {
    editModalInstance = new bootstrap.Modal(modalEl);
  }
});

function openEditModal(row) {
  document.getElementById('editCabangId').value = row.cid;
  document.getElementById('editModalTitle').textContent = `Edit Produk: ${row.nama_cabang} (${row.kode_cabang})`;

  const fields = ['pembiayaan', 'arisan', 'sisuka', 'sirela', 'simpelpres', 'simapan', 'simabrur', 'umroh', 'salam', 'lumbung_beras', 'arisan_berkah'];
  fields.forEach(f => {
    const el = document.getElementById(`ch_${f}`);
    if (el) el.checked = (parseInt(row[f]) === 1);
  });

  if (editModalInstance) editModalInstance.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

