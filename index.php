<?php
/**
 * KUESIONER ANGGOTA - Index Router & Portal
 * Mengarahkan pengunjung ke Kuesioner Anggota atau Ruang Kerja Admin
 */

require_once __DIR__ . '/includes/functions.php';

// Jika terdapat token di URL, langsung arahkan ke survey.php dengan token tersebut
if (!empty($_GET['token']) || !empty($_GET['cabang']) || !empty($_GET['kode'])) {
    $param = $_GET['token'] ?? $_GET['cabang'] ?? $_GET['kode'];
    header("Location: " . BASE_URL . "/survey.php?token=" . urlencode($param));
    exit;
}

// Redirect langsung ke formulir kuesioner anggota
header("Location: " . BASE_URL . "/survey.php");
exit;

