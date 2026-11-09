<?php
/**
 * KUESIONER ANGGOTA - QR Code Render Endpoint
 * Mengembalikan file SVG langsung untuk gambar atau unduhan
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/qrcode_helper.php';

$token = $_GET['token'] ?? '';
$size = isset($_GET['size']) ? (int)$_GET['size'] : 220;
$size = max(100, min(800, $size));

$branch = find_branch($token);
if ($branch) {
    $surveyUrl = FastabiqQRCode::getSurveyUrl($branch);
} else {
    $surveyUrl = rtrim(BASE_URL, '/') . '/survey.php?token=' . urlencode($token);
}

$svgString = FastabiqQRCode::generateSvgString($surveyUrl, $size);

if (isset($_GET['download'])) {
    $filename = "QR_Fastabiq_" . ($branch ? $branch['kode_cabang'] : 'Survey') . ".svg";
    header('Content-Type: image/svg+xml');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
}

echo $svgString;
exit;

