<?php
/**
 * KUESIONER ANGGOTA - Export Rekap Produk yang Ditawarkan Cabang ke Excel (.csv)
 * Sesuai Dokumen WhatsApp & Format Persis File Excel BMT Fastabiq
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$currentUser = require_login();
$db = get_db_connection();

$selectedPeriod = $_GET['periode'] ?? '2026-10-01';
$periodeLabel = date('F Y', strtotime($selectedPeriod));

$sql = "
    SELECT c.kode_cabang, c.nama_cabang, c.status,
           r.pembiayaan, r.arisan, r.sisuka, r.sirela, r.simpelpres,
           r.simapan, r.simabrur, r.umroh, r.salam, r.lumbung_beras, r.arisan_berkah,
           r.persentase_nilai, r.petugas_nama, r.petugas_jabatan, r.tanggal_laporan
    FROM cabang c
    LEFT JOIN rekap_produk_cabang r ON c.id = r.cabang_id AND r.periode_bulan = ?
    WHERE c.status != 'nonaktif'
    ORDER BY c.kode_cabang ASC
";
$stmt = $db->prepare($sql);
$stmt->execute([$selectedPeriod]);
$rows = $stmt->fetchAll();

$filename = "Rekap_Produk_Cabang_" . date('Ym', strtotime($selectedPeriod)) . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM untuk Microsoft Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['PRODUK YANG DITAWARKAN CABANG']);
fputcsv($output, ['Periode ' . $periodeLabel]);
fputcsv($output, []);

fputcsv($output, [
    'Cabang', 'Nama Cabang', 'Pembiayaan', 'Arisan', 'Sisuka', 'Sirela',
    'Simpelpres', 'Simapan', 'Simabrur / Haji', 'Umroh', 'Salam (Aplikasi)',
    'Lumbung Beras', 'Arisan Berkah', 'Nilai'
]);

foreach ($rows as $r) {
    $isMerged = ($r['status'] === 'gabung');
    fputcsv($output, [
        $r['kode_cabang'],
        $r['nama_cabang'] . ($isMerged ? ' (Digabung F02)' : ''),
        $r['pembiayaan'] ? 'v' : '',
        $r['arisan'] ? 'v' : '',
        $r['sisuka'] ? 'v' : '',
        $r['sirela'] ? 'v' : '',
        $r['simpelpres'] ? 'v' : '',
        $r['simapan'] ? 'v' : '',
        $r['simabrur'] ? 'v' : '',
        $r['umroh'] ? 'v' : '',
        $r['salam'] ? 'v' : '',
        $r['lumbung_beras'] ? 'v' : '',
        $r['arisan_berkah'] ? 'v' : '',
        $isMerged ? '0%' : (isset($r['persentase_nilai']) ? number_format((float)$r['persentase_nilai'], 0) . '%' : '100%')
    ]);
}

fputcsv($output, []);
fputcsv($output, ['Pati, ' . format_tanggal_id(date('Y-m-d'))]);
fputcsv($output, []);
fputcsv($output, ['Ariyani Chairia / Endang K']);
fputcsv($output, ['Staf Layanan & Admisi']);
fputcsv($output, ['KSPPS BMT Fastabiq Khoiro Ummah']);

fclose($output);
exit;

