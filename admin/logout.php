<?php
/**
 * KUESIONER ANGGOTA - Logout
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Catat log aktivitas logout sebelum session dihapus
if (!empty($_SESSION['user_id'])) {
    log_activity('logout', 'auth', 'User logout dari sistem');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: " . BASE_URL . "/login.php");
exit;

