<?php
/**
 * KUESIONER ANGGOTA - Auth & Access Control
 * Sistem Kuesioner Kepuasan Layanan Anggota BMT Fastabiq
 */

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Ambil data user yang sedang login
 */
function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id'           => $_SESSION['user_id'],
        'username'     => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'role'         => $_SESSION['role'],
        'cabang_id'    => $_SESSION['cabang_id'] ?? null,
        'nama_cabang'  => $_SESSION['nama_cabang'] ?? null,
        'kode_cabang'  => $_SESSION['kode_cabang'] ?? null,
    ];
}

/**
 * Pastikan user sudah login, jika belum redirect ke halaman login
 */
function require_login(): array {
    if (!is_logged_in()) {
        $loginUrl = BASE_URL . '/login.php';
        header("Location: $loginUrl");
        exit;
    }
    return current_user();
}

/**
 * Batasi akses berdasarkan role tertentu
 */
function require_role(array $allowedRoles): array {
    $user = require_login();
    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        include __DIR__ . '/../error_forbidden.php';
        exit;
    }
    return $user;
}

/**
 * Periksa hak akses cabang:
 * Jika role adalah kepala_cabang, paksa cabangId harus sama persis dengan cabangnya.
 * Jika admin atau pusat, bebas mengakses semua cabang atau cabang tertentu.
 */
function enforce_branch_scope(&$targetCabangId): void {
    $user = current_user();
    if (!$user) return;

    if ($user['role'] === 'kepala_cabang') {
        // Kunci secara mutlak ke cabang milik kepala cabang
        $targetCabangId = (int)$user['cabang_id'];
    }
}

