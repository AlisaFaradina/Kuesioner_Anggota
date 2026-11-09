<?php
/**
 * KUESIONER ANGGOTA - API Get Response Detail
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$currentUser = require_login();
$responseId = (int)($_GET['id'] ?? 0);

if (!$responseId) {
    echo json_encode(['success' => false, 'message' => 'ID Respons tidak valid.']);
    exit;
}

$db = get_db_connection();

// Ambil induk respons
$stmt = $db->prepare("
    SELECT r.*, c.nama_cabang, c.kode_cabang
    FROM survey_response r
    JOIN cabang c ON r.cabang_id = c.id
    WHERE r.id = ?
    LIMIT 1
");
$stmt->execute([$responseId]);
$response = $stmt->fetch();

if (!$response) {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
    exit;
}

// Pastikan Kepala Cabang hanya bisa melihat data cabangnya sendiri!
if ($currentUser['role'] === 'kepala_cabang' && (int)$response['cabang_id'] !== (int)$currentUser['cabang_id']) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke data cabang ini.']);
    exit;
}

// Ambil rincian jawaban
$stmtAns = $db->prepare("
    SELECT j.*, p.teks_pertanyaan, p.label_aspek, p.urutan
    FROM jawaban_detail j
    JOIN pertanyaan p ON j.pertanyaan_id = p.id
    WHERE j.response_id = ?
    ORDER BY p.urutan ASC
");
$stmtAns->execute([$responseId]);
$answers = $stmtAns->fetchAll();

echo json_encode([
    'success' => true,
    'response' => $response,
    'answers' => $answers
]);
exit;

