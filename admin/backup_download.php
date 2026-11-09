<?php
/**
 * KUESIONER ANGGOTA - Database Backup Downloader (Engine)
 * Menghasilkan file .sql lengkap: header, DDL, dan INSERT data semua tabel.
 * Dipanggil oleh halaman admin/backup.php
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin']);
$db = get_db_connection();

// Ambil versi MySQL/MariaDB
$dbVersion = $db->query("SELECT VERSION()")->fetchColumn();

// Nama file: backup_kuesioner_anggota_20261106_095700.sql
$filename = 'backup_kuesioner_anggota_' . date('Ymd_His') . '.sql';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ─── Header Komentar SQL ─────────────────────────────────────────────────────
$divider = str_repeat('-', 65);
echo "-- $divider\n";
echo "-- KUESIONER ANGGOTA - Database Backup\n";
echo "-- KSPPS BMT Fastabiq Khoiro Ummah\n";
echo "-- $divider\n";
echo "-- Tanggal Backup  : " . date('Y-m-d H:i:s') . " WIB\n";
echo "-- Database        : " . DB_NAME . "\n";
echo "-- Host            : " . DB_HOST . ":" . DB_PORT . "\n";
echo "-- Server Version  : $dbVersion\n";
echo "-- PHP Version     : " . PHP_VERSION . "\n";
echo "-- $divider\n\n";

echo "SET FOREIGN_KEY_CHECKS = 0;\n";
echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
echo "SET NAMES utf8mb4;\n\n";

echo "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
echo "USE `" . DB_NAME . "`;\n\n";

// ─── Urutan tabel sesuai dependency FK ───────────────────────────────────────
$tables = [
    'pengaturan',
    'cabang',
    'users',
    'kuesioner',
    'pertanyaan',
    'survey_response',
    'jawaban_detail',
    'rekap_produk_cabang',
];

foreach ($tables as $t) {
    echo "-- $divider\n";
    echo "-- Tabel: `$t`\n";
    echo "-- $divider\n\n";

    // DDL
    $createRow = $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
    echo "DROP TABLE IF EXISTS `$t`;\n";
    echo $createRow['Create Table'] . ";\n\n";

    // Data (chunked per 500 baris agar file tidak terlalu besar sekaligus)
    $offset    = 0;
    $chunkSize = 500;

    do {
        $rows = $db->query("SELECT * FROM `$t` LIMIT $chunkSize OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) break;

        foreach ($rows as $r) {
            $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($r)));
            $vals = implode(', ', array_map(function ($v) use ($db) {
                return $v === null ? 'NULL' : $db->quote((string)$v);
            }, array_values($r)));

            echo "INSERT INTO `$t` ($cols) VALUES ($vals);\n";
        }

        echo "\n";
        $offset += $chunkSize;
    } while (count($rows) === $chunkSize);
}

// ─── Footer ──────────────────────────────────────────────────────────────────
echo "SET FOREIGN_KEY_CHECKS = 1;\n\n";
echo "-- $divider\n";
echo "-- Backup selesai pada: " . date('Y-m-d H:i:s') . " WIB\n";
echo "-- $divider\n";

exit;

