<?php
/**
 * KUESIONER ANGGOTA - Export Laporan Penilaian Resmi ke Microsoft Excel (.xlsx)
 * Menghasilkan spreadsheet OpenXML (.xlsx) murni dengan styling lengkap:
 *  - Header Teal (#0f766e), teks putih tebal, rata tengah
 *  - Setiap kolom terpisah mandiri (No, Kode, Nama, Responden, SS, S, TS, STS, Rata2, %, Predikat)
 *  - Format angka numerik asli (integer, desimal 2 digit, persentase)
 *  - Border tabel rapi, AutoFilter aktif, Freeze Panes di baris header
 *  - Fallback otomatis ke SpreadsheetML jika ekstensi ZipArchive tidak tersedia
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$currentUser = require_login();
$db = get_db_connection();

$startDate    = $_GET['start_date'] ?? date('Y') . '-10-01';
$endDate      = $_GET['end_date']   ?? date('Y') . '-10-31';
$filterBranch = isset($_GET['cabang']) && $_GET['cabang'] !== '' ? (int)$_GET['cabang'] : null;

enforce_branch_scope($filterBranch);

// Filter Query
$where = ["r.status = 'valid'"];
$params = [];

if ($startDate) {
    $where[] = "DATE(r.waktu_selesai) >= ?";
    $params[] = $startDate;
}
if ($endDate) {
    $where[] = "DATE(r.waktu_selesai) <= ?";
    $params[] = $endDate;
}
if ($filterBranch) {
    $where[] = "r.cabang_id = ?";
    $params[] = $filterBranch;
}

$whereSql = implode(' AND ', $where);

// Ambil data agregasi cabang skala 1 - 4
$sql = "
    SELECT 
        c.kode_cabang,
        c.nama_cabang,
        COUNT(DISTINCT r.id) AS total_responden,
        COUNT(CASE WHEN j.nilai >= 4 THEN 1 END) AS count_ss,
        COUNT(CASE WHEN j.nilai = 3 THEN 1 END) AS count_s,
        COUNT(CASE WHEN j.nilai = 2 THEN 1 END) AS count_ts,
        COUNT(CASE WHEN j.nilai = 1 THEN 1 END) AS count_sts,
        COUNT(j.id) AS total_jawaban
    FROM cabang c
    LEFT JOIN survey_response r ON c.id = r.cabang_id AND $whereSql
    LEFT JOIN jawaban_detail j ON r.id = j.response_id
    WHERE c.status = 'aktif' " . ($filterBranch ? "AND c.id = $filterBranch" : "") . "
    GROUP BY c.id
    ORDER BY c.kode_cabang ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rawRows = $stmt->fetchAll();

// Hitung akumulasi & rata-rata tertimbang
$rows = [];
$grandResp = 0;
$grandSS   = 0;
$grandS    = 0;
$grandTS   = 0;
$grandSTS  = 0;
$grandTotWeighted = 0;
$grandTotJawaban  = 0;

foreach ($rawRows as $r) {
    $cSS = (int)$r['count_ss'];
    $cS  = (int)$r['count_s'];
    $cTS = (int)$r['count_ts'];
    $cSTS= (int)$r['count_sts'];
    $totJawaban = $cSS + $cS + $cTS + $cSTS;
    $weighted   = ($cSS * 4) + ($cS * 3) + ($cTS * 2) + ($cSTS * 1);
    $avg        = $totJawaban > 0 ? round($weighted / $totJawaban, 2) : 0.00;
    $pct        = $totJawaban > 0 ? round(($avg / 4) * 100, 1) : 0.0;
    $pctSS      = $totJawaban > 0 ? round(($cSS / $totJawaban) * 100, 1) : 0.0;

    $grandResp        += (int)$r['total_responden'];
    $grandSS          += $cSS;
    $grandS           += $cS;
    $grandTS          += $cTS;
    $grandSTS         += $cSTS;
    $grandTotWeighted += $weighted;
    $grandTotJawaban  += $totJawaban;

    $rows[] = [
        'kode'       => $r['kode_cabang'],
        'nama'       => $r['nama_cabang'],
        'responden'  => (int)$r['total_responden'],
        'count_ss'   => $cSS,
        'count_s'    => $cS,
        'count_ts'   => $cTS,
        'count_sts'  => $cSTS,
        'avg_skor'   => $avg,
        'pct_skor'   => $pct,
        'pct_ss'     => $pctSS,
        'predikat'   => $r['total_responden'] > 0 ? get_predikat_kepuasan($avg) : '-'
    ];
}

$grandAvg = $grandTotJawaban > 0 ? round($grandTotWeighted / $grandTotJawaban, 2) : 0.00;
$grandPct = $grandAvg > 0 ? round(($grandAvg / 4) * 100, 1) : 0.0;
$grandPctSS = $grandTotJawaban > 0 ? round(($grandSS / $grandTotJawaban) * 100, 1) : 0.0;
$grandPred = $grandResp > 0 ? get_predikat_kepuasan($grandAvg) : '-';

$periodeStr = format_tanggal_id($startDate) . ' s/d ' . format_tanggal_id($endDate);
$tglUnduhStr = format_tanggal_id(date('Y-m-d'), true);
$filenameBase = "Laporan_Kepuasan_Fastabiq_" . date('Ymd_His');

// ============================================================================
// OPSI A: GENERATE FORMAT OPENXML .XLSX MURNI (Jika ZipArchive Aktif)
// ============================================================================
if (class_exists('ZipArchive')) {
    $tempZip = tempnam(sys_get_temp_dir(), 'xlsx_');
    $zip = new ZipArchive();
    if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets>' .
            '<sheet name="Laporan Kepuasan" sheetId="1" r:id="rId1"/>' .
            '</sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml (Palet 3 Warna: Teal #0F766E, Putih, Slate)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<numFmts count="2">' .
            '<numFmt numFmtId="164" formatCode="0.0%"/>' .
            '<numFmt numFmtId="165" formatCode="#,##0.00"/>' .
            '</numFmts>' .
            '<fonts count="5">' .
            '<font><sz val="10"/><name val="Segoe UI"/><color rgb="FF1E293B"/></font>' .                    // 0: default
            '<font><b/><sz val="10"/><name val="Segoe UI"/><color rgb="FF0F172A"/></font>' .                // 1: bold dark
            '<font><b/><sz val="10"/><name val="Segoe UI"/><color rgb="FFFFFFFF"/></font>' .                // 2: bold white
            '<font><b/><sz val="13"/><name val="Segoe UI"/><color rgb="FF0F766E"/></font>' .                // 3: title teal
            '<font><sz val="9"/><name val="Segoe UI"/><color rgb="FF64748B"/></font>' .                     // 4: small muted
            '</fonts>' .
            '<fills count="4">' .
            '<fill><patternFill patternType="none"/></fill>' .                                              // 0: none
            '<fill><patternFill patternType="gray125"/></fill>' .                                           // 1: gray125
            '<fill><patternFill patternType="solid"><fgColor rgb="FF0F766E"/></patternFill></fill>' .       // 2: teal #0f766e
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>' .       // 3: soft slate #f1f5f9
            '</fills>' .
            '<borders count="3">' .
            '<border><left/><right/><top/><bottom/></border>' .                                             // 0: none
            '<border>' .                                                                                    // 1: thin all
            '<left style="thin"><color rgb="FFCBD5E1"/></left>' .
            '<right style="thin"><color rgb="FFCBD5E1"/></right>' .
            '<top style="thin"><color rgb="FFCBD5E1"/></top>' .
            '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>' .
            '</border>' .
            '<border>' .                                                                                    // 2: total row
            '<left style="thin"><color rgb="FFCBD5E1"/></left>' .
            '<right style="thin"><color rgb="FFCBD5E1"/></right>' .
            '<top style="thin"><color rgb="FF0F766E"/></top>' .
            '<bottom style="double"><color rgb="FF0F766E"/></bottom>' .
            '</border>' .
            '</borders>' .
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
            '<cellXfs count="15">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' .                                      // 0: normal
            '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" applyFont="1"/>' .                         // 1: title
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="1"/>' .                         // 2: subtitle bold
            '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" applyFont="1"/>' .                         // 3: meta small
            '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' . // 4: header table
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' .   // 5: data text left
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . // 6: data text center
            '<xf numFmtId="3" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . // 7: data int
            '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . // 8: data decimal 2
            '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . // 9: data percent
            '<xf numFmtId="0" fontId="1" fillId="3" borderId="2" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . // 10: total label
            '<xf numFmtId="3" fontId="1" fillId="3" borderId="2" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . // 11: total int
            '<xf numFmtId="165" fontId="1" fillId="3" borderId="2" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . // 12: total dec
            '<xf numFmtId="164" fontId="1" fillId="3" borderId="2" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . // 13: total pct
            '<xf numFmtId="0" fontId="1" fillId="3" borderId="2" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . // 14: total center
            '</cellXfs>' .
            '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetViews>' .
            '<sheetView tabSelected="1" workbookViewId="0">' .
            '<pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/>' .
            '</sheetView>' .
            '</sheetViews>' .
            '<cols>' .
            '<col min="1" max="1" width="6" customWidth="1"/>' .    // A: No.
            '<col min="2" max="2" width="12" customWidth="1"/>' .   // B: Kode
            '<col min="3" max="3" width="28" customWidth="1"/>' .   // C: Nama Cabang
            '<col min="4" max="4" width="13" customWidth="1"/>' .   // D: Responden
            '<col min="5" max="5" width="16" customWidth="1"/>' .   // E: SS (4)
            '<col min="6" max="6" width="15" customWidth="1"/>' .   // F: S (3)
            '<col min="7" max="7" width="16" customWidth="1"/>' .   // G: TS (2)
            '<col min="8" max="8" width="18" customWidth="1"/>' .   // H: STS (1)
            '<col min="9" max="9" width="16" customWidth="1"/>' .   // I: Rata-rata Skor
            '<col min="10" max="10" width="13" customWidth="1"/>' . // J: % Skor
            '<col min="11" max="11" width="14" customWidth="1"/>' . // K: % Nilai SS
            '<col min="12" max="12" width="18" customWidth="1"/>' . // L: Predikat
            '</cols>' .
            '<sheetData>';

        // Helper escape XML
        $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8');

        // Baris 1: Judul Laporan
        $sheetXml .= '<row r="1" ht="24" customHeight="1">';
        $sheetXml .= '<c r="A1" s="1" t="inlineStr"><is><t>' . $esc('KUESIONER ANGGOTA - LAPORAN KEPUASAN LAYANAN ANGGOTA') . '</t></is></c>';
        $sheetXml .= '</row>';

        // Baris 2: Nama Lembaga
        $sheetXml .= '<row r="2" ht="18" customHeight="1">';
        $sheetXml .= '<c r="A2" s="2" t="inlineStr"><is><t>' . $esc('KSPPS BMT FASTABIQ KHOIRO UMMAH (Skala Penilaian 1 - 4)') . '</t></is></c>';
        $sheetXml .= '</row>';

        // Baris 3: Periode & Tanggal Unduh
        $sheetXml .= '<row r="3" ht="16" customHeight="1">';
        $sheetXml .= '<c r="A3" s="3" t="inlineStr"><is><t>' . $esc("Periode: $periodeStr   |   Diunduh: $tglUnduhStr") . '</t></is></c>';
        $sheetXml .= '</row>';

        // Baris 4: Kosong
        $sheetXml .= '<row r="4" ht="8" customHeight="1"></row>';

        // Baris 5: HEADER TABEL
        $sheetXml .= '<row r="5" ht="26" customHeight="1">';
        $headers = [
            'A5' => 'No.',
            'B5' => 'Kode Cabang',
            'C5' => 'Nama Cabang',
            'D5' => 'Responden',
            'E5' => 'Akumulasi SS (4)',
            'F5' => 'Akumulasi S (3)',
            'G5' => 'Akumulasi TS (2)',
            'H5' => 'Akumulasi STS (1)',
            'I5' => 'Rata-rata Skor',
            'J5' => '% Skor',
            'K5' => '% Nilai SS',
            'L5' => 'Predikat Kepuasan'
        ];
        foreach ($headers as $cell => $text) {
            $sheetXml .= '<c r="' . $cell . '" s="4" t="inlineStr"><is><t>' . $esc($text) . '</t></is></c>';
        }
        $sheetXml .= '</row>';

        // Baris 6 s/d N: DATA ROWS
        $rIdx = 6;
        $no = 1;
        foreach ($rows as $item) {
            $sheetXml .= '<row r="' . $rIdx . '" ht="20" customHeight="1">';
            $sheetXml .= '<c r="A' . $rIdx . '" s="6"><v>' . $no++ . '</v></c>';
            $sheetXml .= '<c r="B' . $rIdx . '" s="6" t="inlineStr"><is><t>' . $esc($item['kode']) . '</t></is></c>';
            $sheetXml .= '<c r="C' . $rIdx . '" s="5" t="inlineStr"><is><t>' . $esc($item['nama']) . '</t></is></c>';
            $sheetXml .= '<c r="D' . $rIdx . '" s="7"><v>' . $item['responden'] . '</v></c>';
            $sheetXml .= '<c r="E' . $rIdx . '" s="7"><v>' . $item['count_ss'] . '</v></c>';
            $sheetXml .= '<c r="F' . $rIdx . '" s="7"><v>' . $item['count_s'] . '</v></c>';
            $sheetXml .= '<c r="G' . $rIdx . '" s="7"><v>' . $item['count_ts'] . '</v></c>';
            $sheetXml .= '<c r="H' . $rIdx . '" s="7"><v>' . $item['count_sts'] . '</v></c>';
            $sheetXml .= '<c r="I' . $rIdx . '" s="8"><v>' . $item['avg_skor'] . '</v></c>';
            $sheetXml .= '<c r="J' . $rIdx . '" s="9"><v>' . ($item['pct_skor'] / 100) . '</v></c>';
            $sheetXml .= '<c r="K' . $rIdx . '" s="9"><v>' . ($item['pct_ss'] / 100) . '</v></c>';
            $sheetXml .= '<c r="L' . $rIdx . '" s="6" t="inlineStr"><is><t>' . $esc($item['predikat']) . '</t></is></c>';
            $sheetXml .= '</row>';
            $rIdx++;
        }

        // BARIS TOTAL
        $sheetXml .= '<row r="' . $rIdx . '" ht="22" customHeight="1">';
        $sheetXml .= '<c r="A' . $rIdx . '" s="14" t="inlineStr"><is><t></t></is></c>';
        $sheetXml .= '<c r="B' . $rIdx . '" s="14" t="inlineStr"><is><t>TOTAL</t></is></c>';
        $sheetXml .= '<c r="C' . $rIdx . '" s="10" t="inlineStr"><is><t>Semua Cabang Aktif</t></is></c>';
        $sheetXml .= '<c r="D' . $rIdx . '" s="11"><v>' . $grandResp . '</v></c>';
        $sheetXml .= '<c r="E' . $rIdx . '" s="11"><v>' . $grandSS . '</v></c>';
        $sheetXml .= '<c r="F' . $rIdx . '" s="11"><v>' . $grandS . '</v></c>';
        $sheetXml .= '<c r="G' . $rIdx . '" s="11"><v>' . $grandTS . '</v></c>';
        $sheetXml .= '<c r="H' . $rIdx . '" s="11"><v>' . $grandSTS . '</v></c>';
        $sheetXml .= '<c r="I' . $rIdx . '" s="12"><v>' . $grandAvg . '</v></c>';
        $sheetXml .= '<c r="J' . $rIdx . '" s="13"><v>' . ($grandPct / 100) . '</v></c>';
        $sheetXml .= '<c r="K' . $rIdx . '" s="13"><v>' . ($grandPctSS / 100) . '</v></c>';
        $sheetXml .= '<c r="L' . $rIdx . '" s="14" t="inlineStr"><is><t>' . $esc($grandPred) . '</t></is></c>';
        $sheetXml .= '</row>';

        // Baris Tanda Tangan
        $signRow1 = $rIdx + 3;
        $signRow2 = $signRow1 + 1;
        $signRow3 = $signRow2 + 3;
        $sheetXml .= '<row r="' . $signRow1 . '"><c r="I' . $signRow1 . '" s="3" t="inlineStr"><is><t>' . $esc('Pati, ' . format_tanggal_id(date('Y-m-d'))) . '</t></is></c></row>';
        $sheetXml .= '<row r="' . $signRow2 . '"><c r="I' . $signRow2 . '" s="2" t="inlineStr"><is><t>Petugas Admisi / Layanan Anggota,</t></is></c></row>';
        $sheetXml .= '<row r="' . $signRow3 . '"><c r="I' . $signRow3 . '" s="2" t="inlineStr"><is><t>Endang K (KSPPS BMT Fastabiq)</t></is></c></row>';

        $sheetXml .= '</sheetData>';
        $sheetXml .= '<autoFilter ref="A5:L' . ($rIdx - 1) . '"/>';
        $sheetXml .= '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        // Stream file ke browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filenameBase . '.xlsx"');
        header('Content-Length: ' . filesize($tempZip));
        header('Cache-Control: max-age=0');
        readfile($tempZip);
        @unlink($tempZip);
        exit;
    }
}

// ============================================================================
// OPSI B: FALLBACK SPREADSHEETML XML (.xls) JIKA SERVER TIDAK ADA ZIP
// ============================================================================
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filenameBase . '.xls"');
header('Cache-Control: max-age=0');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Borders/>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Color="#1E293B"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="Title">
   <Font ss:FontName="Segoe UI" ss:Size="14" ss:Bold="1" ss:Color="#0F766E"/>
  </Style>
  <Style ss:ID="Subtitle">
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/>
  </Style>
  <Style ss:ID="Meta">
   <Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#64748B"/>
  </Style>
  <Style ss:ID="Header">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#0F766E" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="DataLeft">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
  </Style>
  <Style ss:ID="DataCenter">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
  </Style>
  <Style ss:ID="DataInt">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
   <NumberFormat ss:Format="#,##0"/>
  </Style>
  <Style ss:ID="DataDec">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
   <NumberFormat ss:Format="0.00"/>
  </Style>
  <Style ss:ID="DataPct">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
   <NumberFormat ss:Format="0.0%"/>
  </Style>
  <Style ss:ID="TotalLabel">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#0F766E"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F766E"/>
   </Borders>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/>
   <Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="TotalInt">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#0F766E"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F766E"/>
   </Borders>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/>
   <Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="#,##0"/>
  </Style>
  <Style ss:ID="TotalDec">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#0F766E"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F766E"/>
   </Borders>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/>
   <Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="0.00"/>
  </Style>
  <Style ss:ID="TotalPct">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#0F766E"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F766E"/>
   </Borders>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/>
   <Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="0.0%"/>
  </Style>
  <Style ss:ID="TotalCenter">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Double" ss:Weight="3" ss:Color="#0F766E"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F766E"/>
   </Borders>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/>
   <Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Laporan Kepuasan">
  <Table ss:DefaultRowHeight="20">
   <Column ss:Width="35"/>
   <Column ss:Width="70"/>
   <Column ss:Width="175"/>
   <Column ss:Width="75"/>
   <Column ss:Width="90"/>
   <Column ss:Width="85"/>
   <Column ss:Width="90"/>
   <Column ss:Width="95"/>
   <Column ss:Width="85"/>
   <Column ss:Width="70"/>
   <Column ss:Width="75"/>
   <Column ss:Width="105"/>

   <Row ss:Height="24">
    <Cell ss:StyleID="Title"><Data ss:Type="String">KUESIONER ANGGOTA - LAPORAN KEPUASAN LAYANAN ANGGOTA</Data></Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="Subtitle"><Data ss:Type="String">KSPPS BMT FASTABIQ KHOIRO UMMAH (Skala Penilaian 1 - 4)</Data></Cell>
   </Row>
   <Row ss:Height="16">
    <Cell ss:StyleID="Meta"><Data ss:Type="String"><?= htmlspecialchars("Periode: $periodeStr   |   Diunduh: $tglUnduhStr") ?></Data></Cell>
   </Row>
   <Row ss:Height="10"></Row>

   <Row ss:Height="26">
    <Cell ss:StyleID="Header"><Data ss:Type="String">No.</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Kode Cabang</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Nama Cabang</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Responden</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Akumulasi SS (4)</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Akumulasi S (3)</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Akumulasi TS (2)</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Akumulasi STS (1)</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Rata-rata Skor</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">% Skor</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">% Nilai SS</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Predikat Kepuasan</Data></Cell>
   </Row>

   <?php 
   $no = 1;
   foreach ($rows as $item): 
   ?>
   <Row ss:Height="20">
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number"><?= $no++ ?></Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="String"><?= htmlspecialchars($item['kode']) ?></Data></Cell>
    <Cell ss:StyleID="DataLeft"><Data ss:Type="String"><?= htmlspecialchars($item['nama']) ?></Data></Cell>
    <Cell ss:StyleID="DataInt"><Data ss:Type="Number"><?= $item['responden'] ?></Data></Cell>
    <Cell ss:StyleID="DataInt"><Data ss:Type="Number"><?= $item['count_ss'] ?></Data></Cell>
    <Cell ss:StyleID="DataInt"><Data ss:Type="Number"><?= $item['count_s'] ?></Data></Cell>
    <Cell ss:StyleID="DataInt"><Data ss:Type="Number"><?= $item['count_ts'] ?></Data></Cell>
    <Cell ss:StyleID="DataInt"><Data ss:Type="Number"><?= $item['count_sts'] ?></Data></Cell>
    <Cell ss:StyleID="DataDec"><Data ss:Type="Number"><?= $item['avg_skor'] ?></Data></Cell>
    <Cell ss:StyleID="DataPct"><Data ss:Type="Number"><?= $item['pct_skor'] / 100 ?></Data></Cell>
    <Cell ss:StyleID="DataPct"><Data ss:Type="Number"><?= $item['pct_ss'] / 100 ?></Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="String"><?= htmlspecialchars($item['predikat']) ?></Data></Cell>
   </Row>
   <?php endforeach; ?>

   <Row ss:Height="22">
    <Cell ss:StyleID="TotalCenter"><Data ss:Type="String"></Data></Cell>
    <Cell ss:StyleID="TotalCenter"><Data ss:Type="String">TOTAL</Data></Cell>
    <Cell ss:StyleID="TotalLabel"><Data ss:Type="String">Semua Cabang Aktif</Data></Cell>
    <Cell ss:StyleID="TotalInt"><Data ss:Type="Number"><?= $grandResp ?></Data></Cell>
    <Cell ss:StyleID="TotalInt"><Data ss:Type="Number"><?= $grandSS ?></Data></Cell>
    <Cell ss:StyleID="TotalInt"><Data ss:Type="Number"><?= $grandS ?></Data></Cell>
    <Cell ss:StyleID="TotalInt"><Data ss:Type="Number"><?= $grandTS ?></Data></Cell>
    <Cell ss:StyleID="TotalInt"><Data ss:Type="Number"><?= $grandSTS ?></Data></Cell>
    <Cell ss:StyleID="TotalDec"><Data ss:Type="Number"><?= $grandAvg ?></Data></Cell>
    <Cell ss:StyleID="TotalPct"><Data ss:Type="Number"><?= $grandPct / 100 ?></Data></Cell>
    <Cell ss:StyleID="TotalPct"><Data ss:Type="Number"><?= $grandPctSS / 100 ?></Data></Cell>
    <Cell ss:StyleID="TotalCenter"><Data ss:Type="String"><?= htmlspecialchars($grandPred) ?></Data></Cell>
   </Row>

   <Row ss:Height="20"></Row>
   <Row ss:Height="18">
    <Cell ss:Index="9" ss:StyleID="Meta"><Data ss:Type="String"><?= htmlspecialchars('Pati, ' . format_tanggal_id(date('Y-m-d'))) ?></Data></Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:Index="9" ss:StyleID="Subtitle"><Data ss:Type="String">Petugas Admisi / Layanan Anggota,</Data></Cell>
   </Row>
   <Row ss:Height="40"></Row>
   <Row ss:Height="18">
    <Cell ss:Index="9" ss:StyleID="Subtitle"><Data ss:Type="String">Endang K (KSPPS BMT Fastabiq)</Data></Cell>
   </Row>
  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <FreezePanes/>
   <FrozenNoSplit/>
   <SplitHorizontal>5</SplitHorizontal>
   <TopRowBottomPane>5</TopRowBottomPane>
   <ActivePane>2</ActivePane>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
<?php
exit;
