<?php
/**
 * KUESIONER ANGGOTA - Formulir Kuesioner Publik (Mobile-First)
 * Sistem Kuesioner Kepuasan Layanan Anggota BMT Fastabiq
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/qrcode_helper.php';

// Ambil cabang dari token atau kode di query string
$tokenParam = $_GET['token'] ?? $_GET['cabang'] ?? $_GET['kode'] ?? null;
$selectedBranch = null;

if ($tokenParam) {
    $selectedBranch = find_branch($tokenParam);
}

// Ambil semua cabang aktif untuk modal pilihan cabang
$branches = get_all_branches(false);

// Ambil instrumen kuesioner aktif
$survey = get_active_survey();
if (!$survey || empty($survey['pertanyaan'])) {
    die("Kuesioner belum aktif atau pertanyaan belum dikonfigurasi oleh administrator.");
}

$totalQuestions = count($survey['pertanyaan']);
$productOptions = [
    'Pembiayaan', 'Sisuka', 'Sirela', 'Simpelpres', 'Simapan',
    'Simabrur (Porsi Haji)', 'Umroh', 'Salam (Aplikasi Mobile)',
    'Lumbung Beras', 'Arisan Berkah'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Kuesioner Kepuasan Layanan - KUESIONER ANGGOTA</title>
  
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="survey-wrapper">
  <!-- Top Navigation Bar -->
  <header class="survey-header">
    <div class="survey-header-inner">
      <div class="survey-brand d-flex align-items-center gap-2">
        <img src="<?= BASE_URL ?>/assets/images/logo_bmt.png" alt="Logo BMT Fastabiq" class="survey-header-logo">
        <div>
          <div class="survey-title-text">Kuesioner Anggota</div>
          <div class="survey-subtitle-text">Kepuasan Layanan BMT Fastabiq</div>
        </div>
      </div>
      <div id="topBranchBadge">
        <?php if ($selectedBranch): ?>
          <span class="badge-branch">
            <i class="bi bi-geo-alt-fill"></i> Cabang <?= e($selectedBranch['nama_cabang']) ?>
          </span>
        <?php else: ?>
          <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="openBranchModal()">
            <i class="bi bi-geo-alt"></i> Pilih Cabang
          </button>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Survey Main Content -->
  <main class="survey-body">

    <!-- STEP 0: Cover / Intro Screen (Responsive Wide Hero on Desktop) -->
    <div id="screenCover" class="survey-screen survey-landing-hero">
      <div class="row align-items-center g-4 g-lg-5">
        <!-- Kolom Kiri: Konten Utama & Call-to-Action -->
        <div class="col-12 col-lg-7">
          <div class="hero-left-content">
            <div class="hero-brand-pill mb-3">
              <img src="<?= BASE_URL ?>/assets/images/logo_bmt.png" alt="BMT" class="hero-brand-icon">
              <span>KUESIONER KEPUASAN LAYANAN &bull; RESMI 2026</span>
            </div>

            <div class="hero-greeting mb-2">
              <i class="bi bi-chat-quote-fill me-1 text-primary-fastabiq"></i> Assalamu'alaikum Warahmatullahi Wabarakatuh
            </div>

            <h1 class="hero-main-title mb-2">
              KUESIONER <span class="text-teal-accent">ANGGOTA</span>
            </h1>

            <p class="hero-subtitle mb-3">
              Suara Anda, Semangat Kami untuk Melayani Lebih Baik
            </p>

            <p class="hero-intro-text mb-4">
              Bapak/Ibu Anggota yang kami hormati, terima kasih atas kepercayaan Anda bertransaksi syariah bersama KSPPS BMT Fastabiq Khoiro Ummah. Bantu kami terus menyempurnakan kualitas pelayanan dengan memberikan penilaian objektif hari ini.
            </p>

            <!-- 3 Poin Keunggulan -->
            <div class="row g-2 mb-4">
              <div class="col-4">
                <div class="hero-feature-card">
                  <div class="feature-icon"><i class="bi bi-clock-history"></i></div>
                  <div class="feature-title">&plusmn; 2 Menit</div>
                  <div class="feature-desc">10 pertanyaan cepat skala 1–4</div>
                </div>
              </div>
              <div class="col-4">
                <div class="hero-feature-card">
                  <div class="feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
                  <div class="feature-title">Aman &amp; Rahasia</div>
                  <div class="feature-desc">Tanpa login akun, data terjaga</div>
                </div>
              </div>
              <div class="col-4">
                <div class="hero-feature-card">
                  <div class="feature-icon"><i class="bi bi-buildings-fill"></i></div>
                  <div class="feature-title">27 Cabang</div>
                  <div class="feature-desc">Terhubung langsung ke pimpinan</div>
                </div>
              </div>
            </div>

            <!-- Box Status Cabang -->
            <div class="hero-branch-box mb-4">
              <?php if ($selectedBranch): ?>
              <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border border-success-subtle">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-check-circle-fill text-success fs-5"></i>
                  <div>
                    <div class="small text-muted">Cabang Tempat Anda Menerima Layanan:</div>
                    <div class="fw-bold text-dark">
                      Cabang <?= e($selectedBranch['nama_cabang']) ?> (<?= e($selectedBranch['kode_cabang']) ?>)
                    </div>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="openBranchModal()">
                  <i class="bi bi-arrow-repeat me-1"></i> Ganti
                </button>
              </div>
              <?php else: ?>
              <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-warning-subtle border border-warning-subtle">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                  <div>
                    <div class="small text-warning-emphasis fw-bold">Pilih Kantor Cabang Terlebih Dahulu:</div>
                    <div class="small text-muted">Tentukan kantor cabang tempat Anda dilayani sebelum memulai.</div>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-primary-survey rounded-pill px-3" onclick="openBranchModal()">
                  <i class="bi bi-geo-alt-fill me-1"></i> Pilih Cabang
                </button>
              </div>
              <?php endif; ?>
            </div>

            <!-- Tombol Aksi Utama -->
            <div class="d-flex flex-wrap align-items-center gap-3">
              <?php if (!$selectedBranch): ?>
              <button type="button" class="btn-hero-cta" onclick="openBranchModal()">
                <span>PILIH CABANG &amp; MULAI PENILAIAN</span>
                <i class="bi bi-arrow-right"></i>
              </button>
              <?php else: ?>
              <button type="button" class="btn-hero-cta" onclick="startSurvey()">
                <span>MULAI PENILAIAN SEKARANG</span>
                <i class="bi bi-arrow-right"></i>
              </button>
              <?php endif; ?>

              <div class="hero-privacy-note text-muted small">
                <i class="bi bi-shield-check text-success me-1"></i>
                Jawaban dijamin kerahasiaannya &amp; langsung dievaluasi manajemen.
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Phone Mockup Frame Showcase (Desktop Only) -->
        <div class="col-12 col-lg-5 d-none d-lg-block">
          <div class="hero-showcase-wrapper">
            <!-- Floating Badge Atas -->
            <div class="floating-badge badge-top">
              <i class="bi bi-star-fill text-warning"></i>
              <div>
                <div class="fw-bold">Target Layanan Prima</div>
                <small class="text-muted">Indeks Kepuasan &ge; 95%</small>
              </div>
            </div>

            <!-- Smartphone Mockup Frame -->
            <div class="phone-mockup-frame">
              <div class="phone-speaker-bar"></div>
              
              <div class="phone-screen-content">
                <!-- Phone App Bar -->
                <div class="mockup-app-bar">
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?= BASE_URL ?>/assets/images/logo_bmt.png" alt="BMT" style="width: 22px; height: 22px; object-fit: contain;">
                    <span class="fw-bold" style="font-size: 0.78rem; color: #0f766e;">Kuesioner Anggota</span>
                  </div>
                  <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">Skala 1–4</span>
                </div>

                <!-- Customer Service Vector Art Banner -->
                <div class="mockup-hero-banner">
                  <div class="cs-avatar-wrap">
                    <svg width="64" height="64" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <circle cx="36" cy="36" r="35" fill="#E6FFFA" stroke="#0F766E" stroke-width="2"/>
                      <path d="M36 12C25 12 19 19 19 32C19 44 24 52 24 52L48 52C48 52 53 44 53 32C53 19 47 12 36 12Z" fill="#0F766E"/>
                      <ellipse cx="36" cy="32" rx="10" ry="12" fill="#FDE68A"/>
                      <path d="M28 28C28 28 32 24 36 24C40 24 44 28 44 28C44 28 42 22 36 22C30 22 28 28 28 28Z" fill="#0D625B"/>
                      <circle cx="32" cy="31" r="1.5" fill="#1E293B"/>
                      <circle cx="40" cy="31" r="1.5" fill="#1E293B"/>
                      <path d="M33 37C33 37 34.5 39 36 39C37.5 39 39 37 39 37" stroke="#92400E" stroke-width="1.5" stroke-linecap="round"/>
                      <path d="M24 52C24 52 27 60 36 60C45 60 48 52 48 52L24 52Z" fill="#0D9488"/>
                      <path d="M32 52L36 46L40 52" fill="#FDE68A"/>
                    </svg>
                  </div>
                  <div class="mockup-banner-text">
                    <div class="fw-bold text-dark" style="font-size: 0.84rem;">Bagaimana Pelayanan Kami?</div>
                    <small class="text-muted" style="font-size: 0.70rem;">Setiap masukan Anda sangat berarti bagi peningkatan mutu BMT Fastabiq.</small>
                  </div>
                </div>

                <!-- Preview Question Card -->
                <div class="mockup-card-question">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-light text-primary-fastabiq border" style="font-size: 0.65rem;">Dimensi Pelayanan</span>
                    <small class="text-muted" style="font-size: 0.65rem;">Pertanyaan 1 dari 10</small>
                  </div>
                  <div class="fw-bold text-dark mb-2" style="font-size: 0.80rem;">
                    Bagaimana keramahan petugas kami saat melayani Anda?
                  </div>

                  <!-- 4 Rating Options Preview -->
                  <div class="mockup-rating-grid">
                    <div class="mockup-rating-opt">
                      <span class="emoji">😡</span>
                      <span class="num">1</span>
                      <span class="lbl">STS</span>
                    </div>
                    <div class="mockup-rating-opt">
                      <span class="emoji">🙁</span>
                      <span class="num">2</span>
                      <span class="lbl">TS</span>
                    </div>
                    <div class="mockup-rating-opt">
                      <span class="emoji">🙂</span>
                      <span class="num">3</span>
                      <span class="lbl">S</span>
                    </div>
                    <div class="mockup-rating-opt active-opt">
                      <span class="emoji">😊</span>
                      <span class="num">4</span>
                      <span class="lbl">SS</span>
                    </div>
                  </div>
                </div>

                <!-- Mockup Action Bar Fake -->
                <div class="mockup-action-bar text-center mt-3">
                  <div class="mockup-btn-fake">
                    <i class="bi bi-cursor-fill me-1"></i> Pilih satu nilai 1–4 untuk melanjutkan
                  </div>
                </div>
              </div>
            </div>

            <!-- Floating Badge Bawah -->
            <div class="floating-badge badge-bottom">
              <i class="bi bi-chat-heart-fill text-danger"></i>
              <div>
                <div class="fw-bold">Amanah &amp; Menenteramkan</div>
                <small class="text-muted">KSPPS BMT Fastabiq Khoiro Ummah</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SURVEY FORM WIZARD -->
    <form id="formSurvey" style="display: none;" onsubmit="return false;">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" id="cabangIdInput" name="cabang_id" value="<?= $selectedBranch['id'] ?? '' ?>">
      <input type="hidden" name="kuesioner_id" value="<?= $survey['id'] ?>">
      <input type="hidden" id="waktuMulaiInput" name="waktu_mulai" value="">
      <input type="hidden" id="signatureDataInput" name="signature_data" value="">

      <!-- RATING QUESTIONS STEPS (1 to N) -->
      <?php foreach ($survey['pertanyaan'] as $idx => $p): 
        $stepNum = $idx + 1;
        $pct = round(($stepNum / ($totalQuestions + 1)) * 100);
      ?>
      <div class="survey-screen survey-step-card" id="step_<?= $stepNum ?>" style="display: none;">
        <!-- Progress -->
        <div class="survey-progress-wrap">
          <div class="survey-progress-label">
            <span>Pertanyaan <?= $stepNum ?> dari <?= $totalQuestions ?></span>
            <span><?= $pct ?>%</span>
          </div>
          <div class="survey-progress-bar">
            <div class="survey-progress-fill" style="width: <?= $pct ?>%;"></div>
          </div>
        </div>

        <!-- Aspect Badge -->
        <span class="survey-aspect-badge"><?= e($p['label_aspek']) ?></span>

        <!-- Question Title -->
        <h2 class="survey-question-title"><?= e($p['teks_pertanyaan']) ?></h2>
        <div class="survey-question-desc">Pilih satu nilai yang paling sesuai dengan pengalaman Anda.</div>

        <!-- 4 Rating Options (Skala 1-4) -->
        <div class="rating-options">
          <div class="rating-card" data-step="<?= $stepNum ?>" data-val="1" onclick="selectRating(<?= $stepNum ?>, 1, 'Sangat Tidak Puas (STS)')">
            <div class="rating-num">1</div>
            <div class="rating-emoji">😡</div>
            <div class="rating-text-label">Sangat Tidak Puas (STS)</div>
          </div>
          <div class="rating-card" data-step="<?= $stepNum ?>" data-val="2" onclick="selectRating(<?= $stepNum ?>, 2, 'Tidak Puas (TS)')">
            <div class="rating-num">2</div>
            <div class="rating-emoji">🙁</div>
            <div class="rating-text-label">Tidak Puas (TS)</div>
          </div>
          <div class="rating-card" data-step="<?= $stepNum ?>" data-val="3" onclick="selectRating(<?= $stepNum ?>, 3, 'Puas (S)')">
            <div class="rating-num">3</div>
            <div class="rating-emoji">🙂</div>
            <div class="rating-text-label">Puas (S)</div>
          </div>
          <div class="rating-card" data-step="<?= $stepNum ?>" data-val="4" onclick="selectRating(<?= $stepNum ?>, 4, 'Sangat Puas (SS)')">
            <div class="rating-num">4</div>
            <div class="rating-emoji">😊</div>
            <div class="rating-text-label">Sangat Puas (SS)</div>
          </div>
        </div>

        <input type="hidden" name="jawaban[<?= $p['id'] ?>]" id="input_rating_<?= $stepNum ?>" value="">

        <!-- Selected Feedback Indicator -->
        <div class="selection-indicator" id="indicator_<?= $stepNum ?>" style="display: none;">
          <i class="bi bi-check-circle-fill"></i>
          <span id="indicator_text_<?= $stepNum ?>"></span>
        </div>

        <!-- Navigation Buttons -->
        <div class="survey-nav-actions">
          <button type="button" class="btn-nav-prev" onclick="prevStep(<?= $stepNum ?>)">Sebelumnya</button>
          <button type="button" class="btn-nav-next" id="btn_next_<?= $stepNum ?>" onclick="nextStep(<?= $stepNum ?>)" disabled>Berikutnya</button>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- FINAL STEP: Produk Ditawarkan + Tanda Tangan Digital + Kritik/Saran -->
      <div class="survey-screen" id="step_final" style="display: none;">
        <div class="survey-progress-wrap">
          <div class="survey-progress-label">
            <span><?= $totalQuestions ?> dari <?= $totalQuestions ?> pertanyaan selesai</span>
            <span>100%</span>
          </div>
          <div class="survey-progress-bar">
            <div class="survey-progress-fill" style="width: 100%;"></div>
          </div>
        </div>

        <h2 class="survey-question-title mb-1">Informasi Tambahan & Tanda Tangan</h2>
        <p class="survey-question-desc mb-3">Lengkapi rincian pelayanan hari ini sebelum mengirim penilaian.</p>

        <!-- Waktu Pengisian Otomatis -->
        <div class="d-flex align-items-center gap-2 p-2 px-3 mb-3 bg-light rounded-3" style="font-size: 0.82rem;">
          <i class="bi bi-calendar-check text-primary-fastabiq"></i>
          <span>Waktu Pengisian Otomatis: <strong id="liveTimestamp"><?= date('d F Y, H:i') ?> WIB</strong></span>
        </div>

        <!-- Jenis Produk yang Ditawarkan (Sesuai Catatan WA) -->
        <div class="mb-3">
          <label class="form-label fw-bold" style="font-size: 0.88rem;">
            <i class="bi bi-tag-fill text-primary-fastabiq me-1"></i> Produk Yang Ditawarkan Petugas Hari Ini:
          </label>
          <div class="products-grid">
            <?php foreach ($productOptions as $prod): ?>
            <label class="product-chip">
              <input type="checkbox" name="produk_ditawarkan[]" value="<?= e($prod) ?>">
              <span><?= e($prod) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <input type="text" name="produk_lainnya" class="form-control form-control-sm rounded-3" placeholder="Produk lainnya (isi manual jika ada)...">
        </div>

        <!-- Tanda Tangan Digital (Sesuai Catatan WA) -->
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label fw-bold mb-0" style="font-size: 0.88rem;">
              <i class="bi bi-pen-fill text-primary-fastabiq me-1"></i> Tanda Tangan Digital Anggota:
            </label>
            <button type="button" class="btn-clear-sig" onclick="clearSignature()">
              <i class="bi bi-arrow-counterclockwise"></i> Bersihkan
            </button>
          </div>
          <div class="signature-box">
            <canvas id="sigCanvas" class="signature-canvas"></canvas>
          </div>
          <div class="signature-toolbar">
            <span>Silakan goreskan tanda tangan Anda di area kotak di atas.</span>
          </div>
        </div>

        <!-- Kritik & Saran Opsional (Sesuai PDF Screen 4) -->
        <div class="pt-2 border-top">
          <h3 class="fw-bold mb-1" style="font-size: 1.05rem;">Kritik &amp; Saran (Opsional)</h3>
          <p class="text-muted mb-3" style="font-size: 0.82rem;">Ada hal lain yang ingin Bapak/Ibu sampaikan? Anda tidak wajib mengisi bagian ini.</p>

          <div class="form-feedback-group">
            <label><i class="bi bi-heart text-danger me-1"></i> Apa yang paling Anda sukai?</label>
            <textarea name="kritik_saran_suka" placeholder="Ceritakan hal yang paling berkesan bagi Anda..."></textarea>
          </div>

          <div class="form-feedback-group">
            <label><i class="bi bi-tools text-warning me-1"></i> Apa yang perlu kami perbaiki?</label>
            <textarea name="kritik_saran_perbaiki" placeholder="Bantu kami mengetahui apa yang perlu ditingkatkan..."></textarea>
          </div>

          <div class="form-feedback-group">
            <label><i class="bi bi-chat-text text-info me-1"></i> Pesan / saran lainnya:</label>
            <textarea name="kritik_saran_lain" placeholder="Tuliskan pesan atau harapan Anda..."></textarea>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="survey-nav-actions mt-3">
          <button type="button" class="btn-nav-prev" onclick="prevFromFinal()">Sebelumnya</button>
          <button type="button" class="btn-primary-survey flex-grow-1" id="btnSubmitSurvey" onclick="submitSurveyForm()">
            KIRIM PENILAIAN
          </button>
        </div>
      </div>
    </form>

    <!-- LOADING STATE -->
    <div id="screenLoading" style="display: none;" class="state-screen">
      <div class="spinner-border text-primary-fastabiq" style="width: 3rem; height: 3rem;" role="status"></div>
      <h2 class="state-title mt-3">Menyimpan Penilaian...</h2>
      <p class="state-desc">Mohon tunggu sebentar, data kuesioner Anda sedang kami proses dengan aman.</p>
    </div>

  </main>

  <!-- Survey Footer -->
  <footer class="survey-footer-note">
    Suara Anda, semangat kami untuk melayani lebih baik. &bull; BMT Fastabiq
  </footer>
</div>

<!-- MODAL PILIH CABANG (Jika belum memilih cabang) -->
<div class="modal fade" id="modalBranchSelect" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold text-primary-fastabiq">Pilih Kantor Cabang</h5>
          <small class="text-muted">Pilih cabang tempat Anda menerima pelayanan hari ini</small>
        </div>
      </div>
      <div class="modal-body">
        <div class="input-group mb-3">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" id="branchSearchInput" class="form-control border-start-0" placeholder="Ketik nama atau kode cabang..." oninput="filterBranches()">
        </div>

        <div class="list-group rounded-3" id="branchListGroup" style="max-height: 280px; overflow-y: auto;">
          <?php foreach ($branches as $b): ?>
          <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 branch-option-btn" 
                  data-name="<?= strtolower(e($b['nama_cabang'] . ' ' . $b['kode_cabang'])) ?>"
                  onclick="selectBranch(<?= $b['id'] ?>, '<?= e($b['nama_cabang']) ?>', '<?= e($b['kode_cabang']) ?>')">
            <div>
              <div class="fw-bold" style="font-size: 0.9rem;"><?= e($b['nama_cabang']) ?></div>
              <small class="text-muted"><?= e($b['kode_cabang']) ?> &bull; <?= e($b['alamat'] ?? 'Pati, Jawa Tengah') ?></small>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
          </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/signature_pad.umd.min.js"></script>
<script>
const TOTAL_QUESTIONS = <?= $totalQuestions ?>;
let currentStep = 0;
let signaturePad = null;
let branchModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('modalBranchSelect');
  if (modalEl) {
    branchModalInstance = new bootstrap.Modal(modalEl);
  }

  // Inisialisasi Signature Pad
  const canvas = document.getElementById('sigCanvas');
  if (canvas) {
    function resizeCanvas() {
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      canvas.width = canvas.offsetWidth * ratio;
      canvas.height = canvas.offsetHeight * ratio;
      canvas.getContext("2d").scale(ratio, ratio);
      if (signaturePad) signaturePad.clear();
    }
    window.addEventListener("resize", resizeCanvas);
    signaturePad = new SignaturePad(canvas, {
      backgroundColor: 'rgb(255, 255, 255)',
      penColor: 'rgb(15, 118, 110)'
    });
    setTimeout(resizeCanvas, 300);
  }
});

function openBranchModal() {
  if (branchModalInstance) branchModalInstance.show();
}

function selectBranch(id, name, code) {
  document.getElementById('cabangIdInput').value = id;
  const badge = document.getElementById('topBranchBadge');
  if (badge) {
    badge.innerHTML = `<span class="badge-branch"><i class="bi bi-geo-alt-fill"></i> Cabang ${name}</span>`;
  }
  if (branchModalInstance) branchModalInstance.hide();
  startSurvey();
}

function filterBranches() {
  const query = document.getElementById('branchSearchInput').value.toLowerCase();
  const items = document.querySelectorAll('.branch-option-btn');
  items.forEach(el => {
    const text = el.getAttribute('data-name');
    el.style.display = text.includes(query) ? '' : 'none';
  });
}

function startSurvey() {
  const cabangId = document.getElementById('cabangIdInput').value;
  if (!cabangId) {
    openBranchModal();
    return;
  }
  // Catat waktu mulai
  const now = new Date();
  document.getElementById('waktuMulaiInput').value = now.toISOString().slice(0, 19).replace('T', ' ');

  document.getElementById('screenCover').style.display = 'none';
  document.getElementById('formSurvey').style.display = 'block';
  showStep(1);
}

function showStep(step) {
  currentStep = step;
  // Sembunyikan semua step
  for (let i = 1; i <= TOTAL_QUESTIONS; i++) {
    const el = document.getElementById(`step_${i}`);
    if (el) el.style.display = 'none';
  }
  const finalEl = document.getElementById('step_final');
  if (finalEl) finalEl.style.display = 'none';

  if (step <= TOTAL_QUESTIONS) {
    const activeEl = document.getElementById(`step_${step}`);
    if (activeEl) {
      activeEl.style.display = 'block';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  } else {
    if (finalEl) {
      finalEl.style.display = 'block';
      window.scrollTo({ top: 0, behavior: 'smooth' });
      // Trigger canvas resize
      setTimeout(() => {
        const canvas = document.getElementById('sigCanvas');
        if (canvas && signaturePad) {
          const ratio = Math.max(window.devicePixelRatio || 1, 1);
          canvas.width = canvas.offsetWidth * ratio;
          canvas.height = canvas.offsetHeight * ratio;
          canvas.getContext("2d").scale(ratio, ratio);
        }
      }, 100);
    }
  }
}

function selectRating(step, value, label) {
  // Update hidden input
  document.getElementById(`input_rating_${step}`).value = value;

  // Update card UI styles
  const stepContainer = document.getElementById(`step_${step}`);
  const cards = stepContainer.querySelectorAll('.rating-card');
  cards.forEach(card => {
    if (parseInt(card.getAttribute('data-val')) === value) {
      card.classList.add('selected');
    } else {
      card.classList.remove('selected');
    }
  });

  // Tampilkan teks indikator pilihan
  const ind = document.getElementById(`indicator_${step}`);
  const indText = document.getElementById(`indicator_text_${step}`);
  if (ind && indText) {
    indText.textContent = `Anda memilih nilai ${value} (${label})`;
    ind.style.display = 'flex';
  }

  // Aktifkan tombol berikutnya
  const nextBtn = document.getElementById(`btn_next_${step}`);
  if (nextBtn) nextBtn.disabled = false;

  // Auto-advance setelah jeda singkat (400ms) untuk kemudahan di HP
  setTimeout(() => {
    nextStep(step);
  }, 380);
}

function nextStep(current) {
  const val = document.getElementById(`input_rating_${current}`).value;
  if (!val) {
    alert('Silakan pilih salah satu nilai sebelum melanjutkan.');
    return;
  }
  showStep(current + 1);
}

function prevStep(current) {
  if (current === 1) {
    document.getElementById('formSurvey').style.display = 'none';
    document.getElementById('screenCover').style.display = 'block';
  } else {
    showStep(current - 1);
  }
}

function prevFromFinal() {
  showStep(TOTAL_QUESTIONS);
}

function clearSignature() {
  if (signaturePad) signaturePad.clear();
}

function submitSurveyForm() {
  const submitBtn = document.getElementById('btnSubmitSurvey');
  submitBtn.disabled = true;

  // Ambil tanda tangan jika diisi
  if (signaturePad && !signaturePad.isEmpty()) {
    document.getElementById('signatureDataInput').value = signaturePad.toDataURL();
  }

  const formData = new FormData(document.getElementById('formSurvey'));

  // Tampilkan layar loading
  document.getElementById('formSurvey').style.display = 'none';
  document.getElementById('screenLoading').style.display = 'block';

  fetch('<?= BASE_URL ?>/api/submit_survey.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.href = '<?= BASE_URL ?>/thank_you.php?token=' + encodeURIComponent(data.token);
    } else {
      window.location.href = '<?= BASE_URL ?>/error_page.php?msg=' + encodeURIComponent(data.message || 'Gagal menyimpan respons');
    }
  })
  .catch(err => {
    console.error(err);
    window.location.href = '<?= BASE_URL ?>/error_page.php?msg=' + encodeURIComponent('Gangguan jaringan saat mengirim kuesioner.');
  });
}
</script>

</body>
</html>

