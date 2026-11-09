<?php
/**
 * KUESIONER ANGGOTA - Helper Functions
 * Sistem Kuesioner Kepuasan Layanan Anggota BMT Fastabiq
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape string untuk output HTML aman
 */
function e($string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format tanggal Indonesia (contoh: 30 Oktober 2026)
 */
function format_tanggal_id($datetime, $withTime = false): string {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return '-';

    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $d = date('j', $timestamp);
    $m = $months[(int)date('n', $timestamp)];
    $y = date('Y', $timestamp);

    $formatted = "$d $m $y";
    if ($withTime) {
        $formatted .= ' ' . date('H:i', $timestamp) . ' WIB';
    }
    return $formatted;
}

/**
 * Format angka desimal Indonesia (koma sebagai pemisah desimal)
 */
function format_desimal($number, $decimals = 2): string {
    if ($number === null || $number === '') return '0,00';
    return number_format((float)$number, $decimals, ',', '.');
}

/**
 * Generate CSRF Token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF Token
 */
function validate_csrf_token($token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Hitung Kategori Kepuasan berdasarkan Skor Rata-rata (Skala 1 - 4)
 */
function get_predikat_kepuasan($avgScore): string {
    $score = (float)$avgScore;
    if ($score >= 3.26) {
        return 'Sangat Puas';
    } elseif ($score >= 2.51) {
        return 'Puas';
    } elseif ($score >= 1.76) {
        return 'Cukup Puas';
    } else {
        return 'Kurang Puas';
    }
}

/**
 * Skala maksimum penilaian
 */
function get_skala_max(): int {
    return 4;
}

/**
 * Kelas Badge CSS berdasarkan predikat kepuasan
 */
function get_badge_class($predikat): string {
    switch ($predikat) {
        case 'Sangat Puas':
            return 'badge-sangat-puas';
        case 'Puas':
            return 'badge-puas';
        case 'Cukup Puas':
            return 'badge-cukup-puas';
        default:
            return 'badge-kurang-puas';
    }
}

/**
 * Ambil daftar semua cabang aktif
 */
function get_all_branches($includeNonActive = false): array {
    $db = get_db_connection();
    $sql = "SELECT * FROM cabang " . ($includeNonActive ? "" : "WHERE status = 'aktif'") . " ORDER BY kode_cabang ASC";
    return $db->query($sql)->fetchAll();
}

/**
 * Cari cabang berdasarkan ID, Kode (F01..), atau Token QR
 */
function find_branch($identifier): ?array {
    if (!$identifier) return null;
    $db = get_db_connection();

    $stmt = $db->prepare("
        SELECT * FROM cabang 
        WHERE (id = :id OR kode_cabang = :kode OR token_qr = :token)
        LIMIT 1
    ");
    $stmt->execute([
        'id' => is_numeric($identifier) ? (int)$identifier : 0,
        'kode' => (string)$identifier,
        'token' => (string)$identifier
    ]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Ambil Kuesioner Aktif beserta daftar pertanyaan
 */
function get_active_survey(): ?array {
    $db = get_db_connection();
    $kuesioner = $db->query("SELECT * FROM kuesioner WHERE status = 'aktif' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$kuesioner) return null;

    $stmt = $db->prepare("
        SELECT * FROM pertanyaan 
        WHERE kuesioner_id = ? AND status = 'aktif' 
        ORDER BY urutan ASC
    ");
    $stmt->execute([$kuesioner['id']]);
    $kuesioner['pertanyaan'] = $stmt->fetchAll();

    return $kuesioner;
}

/**
 * Ambil pengaturan sistem
 */
function get_setting($key, $default = ''): string {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT nilai FROM pengaturan WHERE kunci = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

/**
 * Catat log aktivitas user
 */
function log_activity(string $action, string $module, ?string $detail = null): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'system';
    $namaLengkap = $_SESSION['nama_lengkap'] ?? 'System';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (!$userId) return;

    try {
        $db = get_db_connection();
        $stmt = $db->prepare("
            INSERT INTO activity_log (user_id, username, nama_lengkap, action, module, detail, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $username, $namaLengkap, $action, $module, $detail, $ipAddress, $userAgent]);
    } catch (Exception $e) {
        // Silent fail jika tabel belum ada
        error_log("Gagal mencatat log aktivitas: " . $e->getMessage());
    }
}

