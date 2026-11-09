<?php
/**
 * KUESIONER ANGGOTA - API Submit Survey
 * Validasi Server-Side Ketat, Penyimpanan Transaksional, Tanda Tangan Digital
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
    exit;
}

// 1. Validasi CSRF Token
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Sesi kuesioner kedaluwarsa. Silakan muat ulang halaman.']);
    exit;
}

$cabangId = (int)($_POST['cabang_id'] ?? 0);
$kuesionerId = (int)($_POST['kuesioner_id'] ?? 0);
$jawaban = $_POST['jawaban'] ?? [];
$waktuMulai = $_POST['waktu_mulai'] ?? null;
$waktuSelesai = date('Y-m-d H:i:s');

// 2. Validasi Cabang
$branch = find_branch($cabangId);
if (!$branch || $branch['status'] !== 'aktif') {
    echo json_encode(['success' => false, 'message' => 'Kantor cabang tidak valid atau sedang tidak aktif.']);
    exit;
}

// 3. Validasi Jawaban Rating (Skala 1 - 4)
if (empty($jawaban) || !is_array($jawaban)) {
    echo json_encode(['success' => false, 'message' => 'Seluruh pertanyaan rating wajib diisi.']);
    exit;
}

$totalRating = 0;
$countRating = 0;
$count5 = 0;
$cleanJawaban = [];

foreach ($jawaban as $pertId => $val) {
    $ratingVal = (int)$val;
    // Server-side validation: Nilai rating hanya boleh 1, 2, 3, atau 4!
    if ($ratingVal < 1 || $ratingVal > 4) {
        echo json_encode(['success' => false, 'message' => 'Nilai rating di luar skala 1–4 ditolak oleh server.']);
        exit;
    }
    $cleanJawaban[(int)$pertId] = $ratingVal;
    $totalRating += $ratingVal;
    $countRating++;
    if ($ratingVal === 4) {
        $count5++;
    }
}

if ($countRating === 0) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada jawaban rating yang diterima.']);
    exit;
}

$rataRataSkor = round($totalRating / $countRating, 2);
$persentaseSkor = round(($totalRating / ($countRating * 4)) * 100, 2);

// Durasi Pengisian
$durasiDetik = 0;
if ($waktuMulai) {
    $mulaiTs = strtotime($waktuMulai);
    $selesaiTs = strtotime($waktuSelesai);
    if ($mulaiTs && $selesaiTs >= $mulaiTs) {
        $durasiDetik = min(3600, $selesaiTs - $mulaiTs);
    }
}

// 4. Proses Produk yang Ditawarkan (Catatan WA: jenis produk yang ditawarkan isi manual/checkbox)
$produkList = $_POST['produk_ditawarkan'] ?? [];
if (!is_array($produkList)) $produkList = [];
$produkLain = trim($_POST['produk_lainnya'] ?? '');
if ($produkLain !== '') {
    $produkList[] = $produkLain;
}
$produkStr = !empty($produkList) ? implode(', ', array_map('strip_tags', $produkList)) : null;

// 5. Proses Tanda Tangan Digital (Catatan WA: sertakan juga tanda tangan digital)
$signatureFile = null;
$sigData = $_POST['signature_data'] ?? '';
if (!empty($sigData) && str_starts_with($sigData, 'data:image/png;base64,')) {
    $base64 = substr($sigData, strlen('data:image/png;base64,'));
    $decoded = base64_decode($base64);
    if ($decoded !== false) {
        $sigFileName = 'sig_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.png';
        $uploadDir = __DIR__ . '/../uploads/signatures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        file_put_contents($uploadDir . $sigFileName, $decoded);
        $signatureFile = $sigFileName;
    }
}

// 6. Kritik & Saran Opsional
$fbSuka = trim($_POST['kritik_saran_suka'] ?? '') ?: null;
$fbPerbaiki = trim($_POST['kritik_saran_perbaiki'] ?? '') ?: null;
$fbLain = trim($_POST['kritik_saran_lain'] ?? '') ?: null;

$tokenRespon = 'RSP_' . strtoupper(bin2hex(random_bytes(10)));
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// 7. Simpan Database dalam Transaksi Aman
$db = get_db_connection();
try {
    // Ambil daftar aspek pertanyaan yang valid
    $stmtPertMap = $db->prepare("SELECT id, aspek FROM pertanyaan WHERE kuesioner_id = ?");
    $stmtPertMap->execute([$kuesionerId]);
    $aspekMap = $stmtPertMap->fetchAll(PDO::FETCH_KEY_PAIR);

    $db->beginTransaction();

    // Simpan Induk Response
    $stmtResp = $db->prepare("
        INSERT INTO survey_response (
            respon_token, cabang_id, kuesioner_id, waktu_mulai, waktu_selesai,
            durasi_detik, rata_rata_skor, persentase_skor, jumlah_nilai_5,
            jumlah_pertanyaan, produk_ditawarkan, tanda_tangan,
            kritik_saran_suka, kritik_saran_perbaiki, kritik_saran_lain,
            ip_address, user_agent, device_type, status, is_dummy
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, 'mobile', 'valid', 0
        )
    ");

    $stmtResp->execute([
        $tokenRespon, $cabangId, $kuesionerId, $waktuMulai, $waktuSelesai,
        $durasiDetik, $rataRataSkor, $persentaseSkor, $count5,
        $countRating, $produkStr, $signatureFile,
        $fbSuka, $fbPerbaiki, $fbLain,
        $ipAddress, $userAgent
    ]);

    $responseId = $db->lastInsertId();

    // Simpan Detail Jawaban Normalisasi
    $stmtDetail = $db->prepare("
        INSERT INTO jawaban_detail (response_id, pertanyaan_id, aspek, nilai)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($cleanJawaban as $pertId => $val) {
        $aspekName = $aspekMap[$pertId] ?? 'Pelayanan';
        $stmtDetail->execute([$responseId, $pertId, $aspekName, $val]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'token'   => $tokenRespon,
        'message' => 'Penilaian berhasil disimpan.'
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Gagal simpan survei: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem database saat menyimpan penilaian. Silakan coba kembali.'
    ]);
}
