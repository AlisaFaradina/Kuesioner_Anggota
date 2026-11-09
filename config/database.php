<?php
/**
 * KUESIONER ANGGOTA - Konfigurasi Database & Lingkungan
 * Sistem Kuesioner Kepuasan Layanan Anggota BMT Fastabiq
 */

// Tetapkan zona waktu resmi Indonesia Barat
date_default_timezone_set('Asia/Jakarta');

// ── Prioritas konfigurasi database ─────────────────────────────────────────
// 1. File lokal db.local.php  (diisi lewat setup.php saat deploy ke server)
// 2. Environment Variables    (untuk Docker / VPS)
// 3. Default XAMPP lokal      (root tanpa password)
$_dbLocalCfg = __DIR__ . '/db.local.php';
if (file_exists($_dbLocalCfg)) {
    $_localDb = require $_dbLocalCfg;   // returns array
    define('DB_HOST', $_localDb['host'] ?? '127.0.0.1');
    define('DB_PORT', $_localDb['port'] ?? '3306');
    define('DB_NAME', $_localDb['name'] ?? 'db_kuesioner');
    define('DB_USER', $_localDb['user'] ?? 'root');
    define('DB_PASS', $_localDb['pass'] ?? '');
} else {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'db_kuesioner');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
}
unset($_dbLocalCfg, $_localDb);
define('DB_CHARSET', 'utf8mb4');

// URL Dasar Aplikasi (Otomatis mendeteksi HTTPS/HTTP, host, dan subfolder)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? null) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Bersihkan path admin atau api jika berada di subdirektori
    $subDir = preg_replace('/(\/(admin|api|includes|assets))$/', '', $scriptDir);
    $subDir = rtrim($subDir, '/');
    define('BASE_URL', $protocol . $host . ($subDir ? $subDir : ''));
}

/**
 * Mendapatkan koneksi PDO Database Singleton
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log kesalahan internal
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Jika dijalankan dari web dan koneksi gagal, tampilkan pesan aman
            if (php_sapi_name() !== 'cli') {
                http_response_code(500);
                echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Koneksi Terputus - KUESIONER ANGGOTA</title>';
                echo '<style>body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}';
                echo '.box{background:#fff;padding:2.5rem;border-radius:1rem;box-shadow:0 10px 25px -5px rgba(0,0,0,0.1);max-width:480px;text-align:center;border-top:5px solid #0d9488;}';
                echo 'h2{color:#0f766e;margin-top:0;}p{color:#64748b;font-size:0.95rem;line-height:1.6;}';
                echo '.btn{display:inline-block;background:#0d9488;color:#fff;padding:0.75rem 1.5rem;border-radius:0.5rem;text-decoration:none;font-weight:600;margin-top:1rem;}';
                echo '</style></head><body><div class="box"><h2>Layanan Sedang Disiapkan</h2>';
                echo '<p>Sistem tidak dapat terhubung ke database <code>' . htmlspecialchars(DB_NAME) . '</code>. Pastikan server MySQL telah aktif dan berkas konfigurasi telah sesuai.</p>';
                echo '<a href="setup.php" class="btn">Buka Halaman Setup & Instalasi →</a>';
                echo '</div></body></html>';
                exit;
            } else {
                throw $e;
            }
        }
    }

    return $pdo;
}

