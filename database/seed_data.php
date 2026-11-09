<?php
/**
 * KUESIONER ANGGOTA - Database Seeder
 * Mengisi Master Cabang, Users, Kuesioner, Pertanyaan, Pengaturan,
 * dan Data Survei Contoh (Januari - Oktober 2026) sesuai CSV BMT Fastabiq.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = get_db_connection();
    echo "Memulai seeding database Kuesioner Anggota...\n";

    // 1. Master Pengaturan
    $settings = [
        ['nama_aplikasi', 'KUESIONER ANGGOTA', 'Nama resmi aplikasi kuesioner'],
        ['slogan', 'Suara Anda, Semangat Kami untuk Melayani Lebih Baik.', 'Slogan kuesioner'],
        ['nama_instansi', 'KSPPS BMT Fastabiq Khoiro Ummah', 'Nama badan hukum/lembaga'],
        ['zona_waktu', 'Asia/Jakarta', 'Zona waktu default aplikasi'],
        ['kpi_target_persen', '95.00', 'Target KPI Kepuasan Anggota (%)'],
        ['skala_maksimal', '4', 'Skala rating maksimal (1-4)'],
        ['petugas_default_nama', 'Endang K', 'Penanggung jawab laporan admisi'],
        ['petugas_default_jabatan', 'Admisi', 'Jabatan penanggung jawab'],
        ['kota_laporan', 'Pati', 'Kota penerbitan laporan'],
    ];

    $stmt = $db->prepare("INSERT INTO pengaturan (kunci, nilai, keterangan) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), keterangan = VALUES(keterangan)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }
    echo "✓ Pengaturan sistem berhasil disimpan.\n";

    // 2. Master Cabang (27 Cabang, F09 digabung ke F02 Wedarijaksa)
    $cabangList = [
        ['F01', 'Pati Kota (Pusat)', 'Jl. AKBP Agil Kusumadya No. 21, Pati', '0295-381234', 'Ahmad Ridwan', 'aktif', 'Cabang Utama'],
        ['F02', 'Wedarijaksa', 'Jl. Raya Wedarijaksa - Tayu KM 5', '0295-382345', 'Siti Nur Aini', 'aktif', 'Gabungan F09 Wedarijaksa'],
        ['F03', 'Juwana', 'Jl. Silugonggo No. 12, Juwana', '0295-383456', 'Budi Santoso', 'aktif', null],
        ['F04', 'Tayu', 'Jl. Diponegoro No. 45, Tayu', '0295-384567', 'Zainal Arifin', 'aktif', null],
        ['F05', 'Kayen', 'Jl. Raya Kayen - Tambakromo KM 1', '0295-385678', 'H. Joko Susilo', 'aktif', null],
        ['F06', 'Jakenan', 'Jl. Jakenan - Winong KM 2', '0295-386789', 'Nurul Huda', 'aktif', null],
        ['F07', 'Trangkil', 'Jl. Raya Pati - Tayu KM 8, Trangkil', '0295-387890', 'Anwar Sadat', 'aktif', null],
        ['F08', 'Margoyoso', 'Jl. Raya Pati - Jepara KM 15, Cebolek', '0295-388901', 'Fathoni Malik', 'aktif', null],
        ['F09', 'Wedarijaksa 2', 'Jl. Pasar Wedarijaksa (Eks F09)', '0295-389012', null, 'gabung', 'F09 dihapus/digabung dengan F02 Wedarijaksa'],
        ['F10', 'Sukolilo', 'Jl. Raya Sukolilo - Kudus KM 1', '0295-390123', 'Ahmad Syafi\'i', 'aktif', null],
        ['F11', 'Gabus', 'Jl. Raya Gabus - Tambakromo KM 3', '0295-391234', 'M. Irfan', 'aktif', null],
        ['F12', 'Batangan', 'Jl. Pantura Pati - Rembang KM 18', '0295-392345', 'Dwi Cahyono', 'aktif', null],
        ['F13', 'Winong', 'Jl. Raya Winong - Pucakwangi KM 2', '0295-393456', 'Hasan Basri', 'aktif', null],
        ['F14', 'Gembong', 'Jl. Raya Pati - Gembong KM 10', '0295-394567', 'Eko Prasetyo', 'aktif', null],
        ['F15', 'Cluwak', 'Jl. Raya Cluwak - Keling KM 4', '0295-395678', 'Ali Mas\'ud', 'aktif', null],
        ['F16', 'Tlogowungu', 'Jl. Tlogowungu - Pati KM 5', '0295-396789', 'Suharto', 'aktif', null],
        ['F17', 'Tambakromo', 'Jl. Raya Tambakromo No. 8', '0295-397890', 'Bambang Irawan', 'aktif', null],
        ['F18', 'Pucakwangi', 'Jl. Raya Pucakwangi - Todanan', '0295-398901', 'Rahmat Hidayat', 'aktif', null],
        ['F19', 'Dukuhseti', 'Jl. Tayu - Puncel KM 12, Dukuhseti', '0295-399012', 'Agus Supriyadi', 'aktif', null],
        ['F20', 'Gunungwungkal', 'Jl. Gunungwungkal KM 3', '0295-400123', 'Khoirul Anam', 'aktif', null],
        ['F21', 'Kudus Kota', 'Jl. Sunan Kudus No. 88, Kudus', '0291-431234', 'Dedi Kurniawan', 'aktif', null],
        ['F22', 'Kudus Jati', 'Jl. Raya Kudus - Demak KM 4, Jati', '0291-432345', 'Arif Munandar', 'aktif', null],
        ['F23', 'Rembang Kota', 'Jl. Pemuda No. 50, Rembang', '0295-691234', 'Fajar Sidik', 'aktif', null],
        ['F24', 'Lasem', 'Jl. Untung Suropati No. 15, Lasem', '0295-692345', 'Taufiqur Rahman', 'aktif', null],
        ['F25', 'Jepara Kota', 'Jl. Pemuda No. 70, Jepara', '0291-591234', 'Lutfi Hakim', 'aktif', null],
        ['F26', 'Mayong', 'Jl. Raya Kudus - Jepara KM 17, Mayong', '0291-592345', 'Imam Subekti', 'aktif', null],
        ['F27', 'Blora', 'Jl. Gatot Subroto No. 34, Blora', '0296-531234', 'Wahyu Wicaksono', 'aktif', null],
    ];

    $stmtCabang = $db->prepare("
        INSERT INTO cabang (kode_cabang, nama_cabang, alamat, telepon, kepala_cabang, status, catatan, token_qr)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            nama_cabang = VALUES(nama_cabang),
            alamat = VALUES(alamat),
            telepon = VALUES(telepon),
            kepala_cabang = VALUES(kepala_cabang),
            status = VALUES(status),
            catatan = VALUES(catatan)
    ");

    $cabangIdMap = [];
    foreach ($cabangList as $c) {
        $token = 'FASTABIQ_' . strtoupper($c[0]) . '_' . substr(md5($c[0] . 'fastabiq_salt_2026'), 0, 8);
        $stmtCabang->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], $token]);
    }

    $allCabang = $db->query("SELECT kode_cabang, id FROM cabang")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "✓ 27 Master Cabang berhasil disimpan (F09 status: gabung).\n";

    // 3. Master Users
    $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
    $users = [
        ['admin', $passwordHash, 'Ahmad Ridwan, S.E.', 'admin@fastabiq.co.id', 'admin', null, 'aktif'],
        ['endang', $passwordHash, 'Endang K (Admisi)', 'endang@fastabiq.co.id', 'pusat', null, 'aktif'],
        ['ariyani', $passwordHash, 'Ariyani Chairia (Staf)', 'ariyani@fastabiq.co.id', 'pusat', null, 'aktif'],
    ];

    // Kepala cabang per cabang
    foreach ($cabangList as $c) {
        if ($c[5] === 'aktif' && isset($allCabang[$c[0]])) {
            $uName = 'kepala_' . strtolower($c[0]);
            $uNama = 'Kepala Cabang ' . $c[1];
            $users[] = [$uName, $passwordHash, $uNama, $uName . '@fastabiq.co.id', 'kepala_cabang', $allCabang[$c[0]], 'aktif'];
        }
    }

    $stmtUser = $db->prepare("
        INSERT INTO users (username, password, nama_lengkap, email, role, cabang_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            nama_lengkap = VALUES(nama_lengkap),
            role = VALUES(role),
            cabang_id = VALUES(cabang_id),
            status = VALUES(status)
    ");

    foreach ($users as $u) {
        $stmtUser->execute($u);
    }
    echo "✓ Pengguna sistem (Admin, Pusat, Kepala Cabang) berhasil disimpan.\n";

    // 4. Master Kuesioner
    $stmtKues = $db->prepare("
        INSERT INTO kuesioner (id, judul, versi, slogan, deskripsi, status)
        VALUES (1, 'Kuesioner Kepuasan Layanan Anggota BMT Fastabiq', 'v1.0', 'Suara Anda, Semangat Kami untuk Melayani Lebih Baik.', 'Instrumen pengukuran kepuasan anggota setelah menerima pelayanan di kantor cabang BMT Fastabiq.', 'aktif')
        ON DUPLICATE KEY UPDATE judul = VALUES(judul), versi = VALUES(versi), slogan = VALUES(slogan), status = VALUES(status)
    ");
    $stmtKues->execute();
    $kuesionerId = 1;
    echo "✓ Master Kuesioner v1.0 berhasil disimpan.\n";

    // 5. Master Pertanyaan (10 Pertanyaan Rating 1-5 Sesuai Dokumen Resmi)
    $pertanyaanList = [
        [1, 'P01', 'Keramahan', 'KERAMAHAN PETUGAS', 'Bagaimana keramahan petugas kami saat melayani Anda?', 'rating_5', 1],
        [2, 'P02', 'Kesopanan', 'KESOPANAN PETUGAS', 'Bagaimana sikap dan kesopanan petugas selama memberikan pelayanan?', 'rating_5', 1],
        [3, 'P03', 'Kecepatan', 'KECEPATAN PELAYANAN', 'Bagaimana kecepatan pelayanan yang Anda terima?', 'rating_5', 1],
        [4, 'P04', 'Ketepatan', 'KETEPATAN INFORMASI', 'Bagaimana ketepatan petugas dalam memberikan informasi atau menyelesaikan kebutuhan Anda?', 'rating_5', 1],
        [5, 'P05', 'Kejelasan Informasi', 'KEJELASAN INFORMASI', 'Apakah informasi yang diberikan petugas mudah dipahami?', 'rating_5', 1],
        [6, 'P06', 'Kompetensi', 'KOMPETENSI PETUGAS', 'Bagaimana kemampuan petugas dalam membantu kebutuhan Anda?', 'rating_5', 1],
        [7, 'P07', 'Kenyamanan', 'KENYAMANAN RUANGAN', 'Bagaimana kenyamanan ruang pelayanan di kantor kami?', 'rating_5', 1],
        [8, 'P08', 'Kebersihan', 'KEBERSIHAN & KERAPIAN', 'Bagaimana kebersihan dan kerapian kantor cabang kami?', 'rating_5', 1],
        [9, 'P09', 'Kemudahan', 'KEMUDAHAN PROSES', 'Bagaimana kemudahan proses pelayanan yang Anda jalani?', 'rating_5', 1],
        [10, 'P10', 'Kepuasan Keseluruhan', 'KEPUASAN KESELURUHAN', 'Secara keseluruhan, seberapa puas Anda terhadap pelayanan BMT Fastabiq?', 'rating_5', 1],
    ];

    $db->exec("DELETE FROM pertanyaan WHERE kuesioner_id = $kuesionerId");
    $stmtPert = $db->prepare("
        INSERT INTO pertanyaan (kuesioner_id, urutan, kode, aspek, label_aspek, teks_pertanyaan, tipe, wajib, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
    ");

    foreach ($pertanyaanList as $p) {
        $stmtPert->execute([$kuesionerId, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]]);
    }
    echo "✓ 10 Pertanyaan kuesioner resmi berhasil disimpan.\n";

    // 6. Rekap Produk yang Ditawarkan Cabang (Data Historis Jan - Sep 2026 Sesuai CSV Asli)
    $stmtRekap = $db->prepare("
        INSERT INTO rekap_produk_cabang (
            cabang_id, periode_bulan, pembiayaan, arisan, sisuka, sirela, simpelpres,
            simapan, simabrur, umroh, salam, lumbung_beras, arisan_berkah, persentase_nilai,
            petugas_nama, petugas_jabatan, tanggal_laporan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            pembiayaan = VALUES(pembiayaan), arisan = VALUES(arisan), sisuka = VALUES(sisuka),
            sirela = VALUES(sirela), simpelpres = VALUES(simpelpres), simapan = VALUES(simapan),
            simabrur = VALUES(simabrur), umroh = VALUES(umroh), salam = VALUES(salam),
            lumbung_beras = VALUES(lumbung_beras), arisan_berkah = VALUES(arisan_berkah),
            persentase_nilai = VALUES(persentase_nilai), petugas_nama = VALUES(petugas_nama)
    ");

    // Mapping ringkasan produk cabang per bulan dari spreadsheet user
    $monthlyData = [
        '2026-01-01' => [
            'petugas' => 'Endang K', 'jabatan' => 'Admisi', 'tgl' => '2026-01-30',
            'rows' => [
                'F01' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F02' => ['pembiayaan'=>1, 'sisuka'=>1, 'simpelpres'=>1, 'umroh'=>1],
                'F03' => ['pembiayaan'=>1, 'sisuka'=>1, 'simapan'=>1, 'umroh'=>1],
                'F04' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F05' => ['pembiayaan'=>1, 'simpelpres'=>1, 'umroh'=>1],
                'F06' => ['pembiayaan'=>1, 'sisuka'=>1, 'simapan'=>1, 'umroh'=>1],
                'F07' => ['sisuka'=>1, 'umroh'=>1],
                'F08' => ['simpelpres'=>1, 'simapan'=>1, 'simabrur'=>1],
                'F10' => ['sisuka'=>1, 'sirela'=>1, 'simpelpres'=>1, 'simapan'=>1],
                'F11' => ['pembiayaan'=>1, 'umroh'=>1],
                'F12' => [],
                'F13' => ['sisuka'=>1, 'sirela'=>1, 'simpelpres'=>1, 'simapan'=>1, 'simabrur'=>1],
                'F14' => ['pembiayaan'=>1, 'sisuka'=>1, 'sirela'=>1, 'simpelpres'=>1],
                'F15' => ['pembiayaan'=>1, 'sirela'=>1],
                'F16' => ['sisuka'=>1, 'simpelpres'=>1],
                'F17' => ['sisuka'=>1, 'simpelpres'=>1, 'simapan'=>1, 'simabrur'=>1],
                'F18' => ['pembiayaan'=>1, 'sirela'=>1, 'simpelpres'=>1],
                'F19' => ['pembiayaan'=>1, 'umroh'=>1],
                'F20' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F21' => ['sisuka'=>1],
                'F22' => ['pembiayaan'=>1, 'sirela'=>1],
                'F23' => ['pembiayaan'=>1, 'sisuka'=>1],
                'F24' => ['pembiayaan'=>1, 'sisuka'=>1],
                'F25' => ['pembiayaan'=>1, 'sisuka'=>1, 'sirela'=>1, 'simpelpres'=>1],
                'F26' => ['pembiayaan'=>1, 'sisuka'=>1, 'simpelpres'=>1],
                'F27' => ['pembiayaan'=>1, 'sisuka'=>1, 'simapan'=>1],
            ]
        ],
        '2026-09-01' => [
            'petugas' => 'Ariyani Chairia', 'jabatan' => 'Staf', 'tgl' => '2026-09-30',
            'rows' => [
                'F01' => ['sisuka'=>1, 'simpelpres'=>1, 'simabrur'=>1, 'umroh'=>1, 'arisan_berkah'=>1],
                'F02' => ['arisan_berkah'=>1],
                'F03' => ['arisan_berkah'=>1],
                'F04' => ['arisan_berkah'=>1],
                'F05' => ['pembiayaan'=>1, 'arisan_berkah'=>1],
                'F06' => ['lumbung_beras'=>1, 'arisan_berkah'=>1],
                'F07' => ['arisan_berkah'=>1],
                'F08' => ['lumbung_beras'=>1, 'sisuka'=>1, 'sirela'=>1, 'simpelpres'=>1, 'simapan'=>1, 'simabrur'=>1],
                'F10' => ['lumbung_beras'=>1, 'sisuka'=>1, 'simpelpres'=>1, 'arisan_berkah'=>1],
                'F11' => ['lumbung_beras'=>1, 'sisuka'=>1, 'simpelpres'=>1, 'simabrur'=>1, 'arisan_berkah'=>1],
                'F12' => ['simpelpres'=>1, 'simapan'=>1, 'simabrur'=>1, 'arisan_berkah'=>1],
                'F13' => ['pembiayaan'=>1, 'sisuka'=>1, 'simpelpres'=>1, 'arisan_berkah'=>1],
                'F14' => ['pembiayaan'=>1, 'lumbung_beras'=>1, 'sirela'=>1, 'umroh'=>1, 'arisan_berkah'=>1],
                'F15' => ['sisuka'=>1, 'sirela'=>1, 'simpelpres'=>1, 'arisan_berkah'=>1],
                'F16' => ['sirela'=>1, 'arisan_berkah'=>1],
                'F17' => ['sisuka'=>1, 'umroh'=>1, 'arisan_berkah'=>1],
                'F18' => ['pembiayaan'=>1, 'sisuka'=>1, 'simpelpres'=>1, 'arisan_berkah'=>1],
                'F19' => ['lumbung_beras'=>1, 'arisan_berkah'=>1],
                'F20' => ['simabrur'=>1, 'umroh'=>1, 'arisan_berkah'=>1],
                'F21' => ['pembiayaan'=>1, 'sisuka'=>1, 'sirela'=>1],
                'F22' => ['simpelpres'=>1, 'arisan_berkah'=>1],
                'F23' => ['pembiayaan'=>1, 'sisuka'=>1, 'arisan_berkah'=>1],
                'F24' => ['pembiayaan'=>1, 'lumbung_beras'=>1, 'sisuka'=>1, 'arisan_berkah'=>1],
                'F25' => ['pembiayaan'=>1, 'sisuka'=>1, 'sirela'=>1, 'simpelpres'=>1, 'arisan_berkah'=>1],
                'F26' => ['pembiayaan'=>1, 'simabrur'=>1, 'arisan_berkah'=>1],
                'F27' => ['simpelpres'=>1, 'arisan_berkah'=>1],
            ]
        ],
        '2026-10-01' => [
            'petugas' => 'Ariyani Chairia', 'jabatan' => 'Staf', 'tgl' => '2026-10-30',
            'rows' => [
                'F01' => ['pembiayaan'=>1, 'sisuka'=>1, 'simpelpres'=>1, 'simapan'=>1, 'umroh'=>1],
                'F02' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F03' => ['sisuka'=>1, 'simabrur'=>1, 'umroh'=>1],
                'F04' => ['pembiayaan'=>1, 'sirela'=>1, 'umroh'=>1],
                'F05' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F06' => ['pembiayaan'=>1, 'simapan'=>1, 'umroh'=>1],
                'F07' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F08' => ['sisuka'=>1, 'simpelpres'=>1, 'simapan'=>1, 'simabrur'=>1],
                'F10' => ['sisuka'=>1, 'sirela'=>1, 'simpelpres'=>1],
                'F11' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F12' => ['pembiayaan'=>1, 'sisuka'=>1],
                'F13' => ['pembiayaan'=>1, 'sisuka'=>1, 'simpelpres'=>1, 'umroh'=>1],
                'F14' => ['pembiayaan'=>1, 'sisuka'=>1, 'sirela'=>1],
                'F15' => ['pembiayaan'=>1, 'umroh'=>1],
                'F16' => ['sisuka'=>1, 'simpelpres'=>1],
                'F17' => ['sisuka'=>1, 'simabrur'=>1, 'umroh'=>1],
                'F18' => ['pembiayaan'=>1, 'simpelpres'=>1],
                'F19' => ['pembiayaan'=>1, 'umroh'=>1],
                'F20' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F21' => ['sisuka'=>1],
                'F22' => ['pembiayaan'=>1, 'sirela'=>1],
                'F23' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F24' => ['pembiayaan'=>1, 'sisuka'=>1],
                'F25' => ['pembiayaan'=>1, 'sisuka'=>1, 'simpelpres'=>1],
                'F26' => ['pembiayaan'=>1, 'sisuka'=>1, 'umroh'=>1],
                'F27' => ['pembiayaan'=>1, 'simapan'=>1, 'umroh'=>1],
            ]
        ]
    ];

    foreach ($monthlyData as $bulan => $mInfo) {
        foreach ($mInfo['rows'] as $kode => $prod) {
            if (!isset($allCabang[$kode])) continue;
            $cabId = $allCabang[$kode];
            $hasAny = count($prod) > 0 ? 100.00 : 0.00;
            $stmtRekap->execute([
                $cabId, $bulan,
                $prod['pembiayaan'] ?? 0,
                $prod['arisan'] ?? 0,
                $prod['sisuka'] ?? 0,
                $prod['sirela'] ?? 0,
                $prod['simpelpres'] ?? 0,
                $prod['simapan'] ?? 0,
                $prod['simabrur'] ?? 0,
                $prod['umroh'] ?? 0,
                $prod['salam'] ?? 0,
                $prod['lumbung_beras'] ?? 0,
                $prod['arisan_berkah'] ?? 0,
                $hasAny,
                $mInfo['petugas'],
                $mInfo['jabatan'],
                $mInfo['tgl']
            ]);
        }
    }
    echo "✓ Rekap Produk Cabang berhasil disimpan.\n";

    // 7. Seed Sample Survey Responses (Tandai is_dummy = 1 agar dapat dibedakan dengan jelas)
    // Buat data respons kuesioner bulan Oktober 2026 (1.480 responden seperti pada mockup PDF)
    $respCount = $db->query("SELECT COUNT(*) FROM survey_response")->fetchColumn();
    if ($respCount < 50) {
        echo "Menghasilkan data survei contoh untuk pengujian dashboard...\n";
        
        $pertanyaanIds = $db->query("SELECT id, aspek FROM pertanyaan WHERE kuesioner_id = $kuesionerId ORDER BY urutan ASC")->fetchAll(PDO::FETCH_ASSOC);

        $sampleFeedbackSuka = [
            'Pelayanan sangat cepat, ramah, dan ruang tunggunya sejuk ber-AC.',
            'Petugas menyambut dengan senyuman dan menjelaskan akad syariah dengan sangat gamblang.',
            'Proses pencairan pembiayaan tepat waktu dan komunikasinya amanah.',
            'Petugas kasir/teller sangat cekatan melayani setoran tabungan.',
            'Ruang kantor bersih, rapi, dan ada air minum untuk anggota.',
            'Sangat puas dengan keramahan mbak teller dan kejelasan penjelasannya.',
        ];

        $sampleFeedbackPerbaiki = [
            'Saat awal bulan antrean cukup ramai, mohon loket teller ditambah.',
            'Brosur produk simpanan haji sebaiknya ditaruh di meja tunggu depan.',
            'Aplikasi mobile/online banking agar semakin ditingkatkan performanya.',
            'Area parkir saat jam sibuk mohon dibantu penataannya oleh satpam.',
            'Sudah sangat bagus, pertahankan keramahannya.',
        ];

        $sampleFeedbackLain = [
            'Semoga BMT Fastabiq semakin berkah dan terus maju melayani umat.',
            'Terima kasih atas pelayanan terbaiknya selama ini.',
            'Tetap amanah dan terus menjadi kebanggaan warga Pati dan sekitarnya.',
        ];

        $productsList = ['Pembiayaan', 'Sisuka', 'Sirela', 'Simpelpres', 'Simapan', 'Simabrur', 'Umroh', 'Salam'];

        $stmtResp = $db->prepare("
            INSERT INTO survey_response (
                respon_token, cabang_id, kuesioner_id, waktu_mulai, waktu_selesai,
                durasi_detik, rata_rata_skor, persentase_skor, jumlah_nilai_5,
                jumlah_pertanyaan, produk_ditawarkan, tanda_tangan,
                kritik_saran_suka, kritik_saran_perbaiki, kritik_saran_lain,
                ip_address, device_type, status, is_dummy, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'mobile', 'valid', 1, ?)
        ");

        $stmtDetail = $db->prepare("
            INSERT INTO jawaban_detail (response_id, pertanyaan_id, aspek, nilai)
            VALUES (?, ?, ?, ?)
        ");

        // Target responden per cabang untuk simulasi (Oktober 2026 total 1.480 responden)
        // Distribusi: F01=180, F02=176, F03=116, F04=100, F05=100, F06=116, F07=116, F08=100, F10=112, etc.
        $targetPerCabang = [
            'F01' => 120, 'F02' => 110, 'F03' => 80, 'F04' => 60, 'F05' => 60,
            'F06' => 65,  'F07' => 70,  'F08' => 85, 'F10' => 75, 'F11' => 60,
            'F12' => 55,  'F13' => 60,  'F14' => 70, 'F15' => 50, 'F16' => 55,
            'F17' => 50,  'F18' => 65,  'F19' => 45, 'F20' => 50, 'F21' => 60,
            'F22' => 45,  'F23' => 80,  'F24' => 65, 'F25' => 75, 'F26' => 60,
            'F27' => 55
        ];

        $db->beginTransaction();

        $totalInserted = 0;
        foreach ($targetPerCabang as $kode => $targetCount) {
            if (!isset($allCabang[$kode])) continue;
            $cabId = $allCabang[$kode];

            for ($i = 0; $i < $targetCount; $i++) {
                $day = rand(1, 31);
                $hour = rand(8, 15);
                $minute = rand(0, 59);
                $second = rand(0, 59);
                $waktuSelesai = sprintf("2026-10-%02d %02d:%02d:%02d", $day, $hour, $minute, $second);
                $waktuMulai = date('Y-m-d H:i:s', strtotime($waktuSelesai) - rand(70, 180));
                $token = 'RSP_' . strtoupper(bin2hex(random_bytes(12)));

                // Tentukan rating per pertanyaan skala 1 - 4: STS(1), TS(2), S(3), SS(4)
                $ratings = [];
                $countSS = 0;
                $sum = 0;
                foreach ($pertanyaanIds as $pert) {
                    $rnd = rand(1, 100);
                    if ($rnd <= 65) {
                        $val = 4; // SS (Sangat Puas)
                    } elseif ($rnd <= 92) {
                        $val = 3; // S (Puas)
                    } elseif ($rnd <= 98) {
                        $val = 2; // TS (Tidak Puas)
                    } else {
                        $val = 1; // STS (Sangat Tidak Puas)
                    }
                    $ratings[$pert['id']] = ['aspek' => $pert['aspek'], 'nilai' => $val];
                    $sum += $val;
                    if ($val === 4) $countSS++;
                }

                $avg = round($sum / count($pertanyaanIds), 2);
                $pct = round(($sum / (count($pertanyaanIds) * 4)) * 100, 2);

                // Produk
                shuffle($productsList);
                $pickProds = array_slice($productsList, 0, rand(1, 3));
                $prodStr = implode(', ', $pickProds);

                // Teks masukan opsional
                $hasFeedback = (rand(1, 10) <= 4);
                $fbSuka = $hasFeedback ? $sampleFeedbackSuka[array_rand($sampleFeedbackSuka)] : null;
                $fbPerbaiki = $hasFeedback && rand(1, 2) === 1 ? $sampleFeedbackPerbaiki[array_rand($sampleFeedbackPerbaiki)] : null;
                $fbLain = $hasFeedback && rand(1, 3) === 1 ? $sampleFeedbackLain[array_rand($sampleFeedbackLain)] : null;

                $stmtResp->execute([
                    $token, $cabId, $kuesionerId, $waktuMulai, $waktuSelesai,
                    rand(80, 180), $avg, $pct, $countSS, count($pertanyaanIds),
                    $prodStr, 'sample_sign.png',
                    $fbSuka, $fbPerbaiki, $fbLain,
                    '192.168.1.' . rand(10, 200), $waktuSelesai
                ]);

                $responseId = $db->lastInsertId();

                foreach ($ratings as $pertId => $item) {
                    $stmtDetail->execute([$responseId, $pertId, $item['aspek'], $item['nilai']]);
                }

                $totalInserted++;
            }
        }

        $db->commit();
        echo "✓ $totalInserted data kuesioner contoh (Oktober 2026) berhasil di-generate.\n";
    }

    echo "\n=== Database Kuesioner Anggota siap digunakan! ===\n";
    echo "Akun Admin:\n";
    echo "  Username: admin\n";
    echo "  Password: password123\n";
    echo "Akun Admisi / Pusat:\n";
    echo "  Username: endang\n";
    echo "  Password: password123\n";
    echo "Akun Kepala Cabang Pati:\n";
    echo "  Username: kepala_f01\n";
    echo "  Password: password123\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
