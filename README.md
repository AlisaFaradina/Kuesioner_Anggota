# KUESIONER ANGGOTA - Sistem Kuesioner Kepuasan Layanan Anggota BMT Fastabiq

> **"Suara Anda, Semangat Kami untuk Melayani Lebih Baik."**  
> Aplikasi Kuesioner Kepuasan Layanan Anggota KSPPS BMT Fastabiq Khoiro Ummah berbasis Web Responsif (Mobile-First).

---

## 📌 1. Gambaran Umum & Fitur Utama

KUESIONER ANGGOTA dirancang untuk mengukur mutu pelayanan anggota secara objektif, modern, dan mudah digunakan di 27 kantor cabang (dengan kapasitas hingga 100 cabang).

### Fitur Kuesioner Anggota (Mobile-First):
- **Akses Cepat & Ramah HP:** Tanpa perlu login/registrasi anggota, minim pengumpulan data pribadi.
- **Dukungan QR Code Unik Per Cabang:** Anggota memindai QR code di meja pelayanan, formulir langsung mengenali cabang terkait.
- **Pilih Cabang Interaktif:** Jika anggota membuka tanpa tautan QR, tersedia pemilih cabang dengan pencarian cepat.
- **10 Pertanyaan Rating Berkartu:** Skala 1 (Sangat Tidak Puas) sampai 5 (Sangat Puas) dengan ikon emoji dan indikator pilihan dinamis.
- **Produk yang Ditawarkan:** Checklist & input manual jenis produk yang ditawarkan petugas saat pelayanan (Pembiayaan, Sisuka, Sirela, Simpelpres, Simapan, Simabrur/Porsi Haji, Umroh, Salam, Lumbung Beras, Arisan Berkah).
- **Tanda Tangan Digital:** Canvas tanda tangan digital langsung pada layar sentuh HP / tablet.
- **Waktu Pengisian Otomatis:** Tanggal dan jam pengisian tercatat otomatis dari sistem server.
- **Kritik & Saran Opsional:** 3 masukan opsional (hal yang disukai, perlu diperbaiki, dan pesan/saran lainnya).
- **Halaman Sukses & Gagal Ramah:** Menggunakan desain selaras identitas BMT Fastabiq ("Alhamdulillah, Terima Kasih!" dan "Pengiriman belum berhasil - Coba Lagi").

### Fitur Ruang Kerja Admin & Kepala Cabang:
- **Pemisahan Hak Akses Ketat (Strict Scoping):**
  - **Administrator Pusat:** Mengelola seluruh 27 cabang, master pengguna, kuesioner, pertanyaan, dan pengaturan sistem.
  - **Staf Admisi / Layanan (Pusat):** Memantau laporan gabungan seluruh cabang, peringkat, rekap produk yang ditawarkan, dan ekspor.
  - **Kepala Cabang:** **HANYA** dapat melihat data cabangnya sendiri. Dibentengi secara server-side sehingga tidak dapat mengakses data cabang lain walau mengubah parameter URL/request.
- **Dashboard Interaktif & Grafik (Chart.js):**
  - Tren kepuasan bulanan.
  - Perbandingan peringkat antar cabang.
  - Distribusi nilai 1–5 (doughnut chart dengan persentase).
  - Evaluasi rata-rata per aspek pelayanan.
  - Masukan dan kritik/saran anggota terbaru.
- **Peringkat Cabang (Rankings):**
  - Tab 1: Peringkat berdasarkan Rata-rata Kepuasan.
  - Tab 2: Peringkat berdasarkan % Nilai 5 (Sangat Puas).
  - Tampilan Podium Juara Top 3 (Emas, Perak, Perunggu).
  - Selalu menampilkan jumlah responden sebagai konteks berimbang.
- **Rekap Produk yang Ditawarkan Cabang:**
  - Persis sesuai format lembar kerja Excel BMT Fastabiq (Pembiayaan, Arisan, Sisuka, Sirela, Simpelpres, Simapan, Simabrur, Umroh, Salam, Lumbung Beras, Arisan Berkah, Nilai 100%).
  - Unduh file Excel `.csv` ber-BOM UTF-8 (dapat langsung dibuka di Microsoft Excel tanpa rusak format).
  - Dilengkapi kolom pengesahan petugas admisi (Endang K / Ariyani Chairia).
- **Laporan Penilaian & Cetak:**
  - Filter tanggal mulai, tanggal akhir, dan cabang.
  - Pratinjau tabel cetak resmi.
  - Tombol **Download Excel**, **Download PDF**, dan **Cetak Standar**.
  - Detail akumulasi pilihan nilai (SS, S, TS, STS).
- **Master Data Cabang & Generator Stand Akrilik QR Meja:**
  - Mengelola 27 cabang aktif (F01–F27).
  - Khusus **F09** ditandai status `gabung` (digabung ke F02 Wedarijaksa sesuai arahan manajemen).
  - Generator otomatis kartu akrilik meja (A5 Tent Card) siap cetak dan pasang di loket pelayanan cabang.
- **Master Pertanyaan Dinamis:**
  - Menambah/mengubah soal dan aspek tanpa merusak struktur database lama.

---

## 🛠️ 2. Spesifikasi Teknologi

- **Bahasa:** PHP 8.x (Native murni, cepat dan mudah dipasang di server mana pun).
- **Database:** MySQL / MariaDB (Database khusus: `db_kuesioner`).
- **Akses Database:** PDO dengan *Prepared Statements* (Aman terhadap SQL Injection).
- **Frontend:** HTML5, CSS3, JavaScript modern, Bootstrap 5.3.
- **Grafik:** Chart.js 4.x (Tersedia offline di lokal).
- **Tanda Tangan:** SignaturePad HTML5 Canvas (Tersedia offline).
- **QR Code:** Standalone PHP SVG Generator (Bekerja 100% tanpa internet).
- **Zona Waktu:** `Asia/Jakarta` (WIB).

---

## 🚀 3. Panduan Pemasangan (Instalasi)

### Opsi A: Menggunakan XAMPP Lokal
1. Pastikan modul **Apache** dan **MySQL** pada XAMPP Control Panel telah aktif.
2. Tempatkan folder proyek ini di `C:\xampp\htdocs\kuesioner_anggota` atau jalankan virtual host ke folder `d:\EKSTERNAL`.
3. Buka browser dan akses halaman instalasi otomatis:
   ```
   http://localhost/kuesioner_anggota/setup.php
   ```
   *(atau `http://localhost:8000/setup.php` jika menggunakan built-in server).*
4. Tekan tombol **"Jalankan Setup & Inisialisasi Database Otomatis"**.
5. Sistem akan otomatis membuat database `db_kuesioner`, seluruh tabel, master cabang, pengguna, kuesioner, dan data uji coba.

### Opsi B: Server Kantor Pusat / Hosting Online (cPanel)
1. Buat database baru bernama `db_kuesioner` di MySQL/cPanel.
2. Impor file skema database: `database/schema.sql`.
3. Jalankan file seeder melalui browser atau terminal: `database/seed_data.php`.
4. Sesuaikan konfigurasi database pada file `config/database.php` atau melalui *environment variables*:
   - `DB_HOST`: Host database (default: `127.0.0.1`)
   - `DB_PORT`: Port MySQL (default: `3306`)
   - `DB_NAME`: Nama database (`db_kuesioner`)
   - `DB_USER`: Username database
   - `DB_PASS`: Password database

---

## 🔑 4. Akun Pengguna Bawaan (Default Login)

Akses halaman login di: `http://localhost/kuesioner_anggota/login.php`

| Peran (Role) | Username | Password | Akses & Wewenang |
| :--- | :--- | :--- | :--- |
| **Administrator Pusat** | `admin` | `password123` | Akses penuh seluruh cabang, master data, kuesioner, pengguna, dan pengaturan sistem. |
| **Staf Admisi / Pusat** | `endang` | `password123` | Akses seluruh cabang, laporan penilaian, ekspor Excel/PDF, dan rekap produk cabang. |
| **Kepala Cabang Pati** | `kepala_f01` | `password123` | **Terbatas khusus Cabang F01 (Pati Kota).** Tidak dapat melihat data cabang lain. |
| **Kepala Cabang Wedarijaksa** | `kepala_f02` | `password123` | **Terbatas khusus Cabang F02 (Wedarijaksa - Gabungan F09).** |
| **Kepala Cabang Lainnya** | `kepala_f03` s/d `kepala_f27` | `password123` | Terbatas khusus pada cabangnya masing-masing. |

---

## 📱 5. Tautan Penggunaan Kuesioner (Anggota / Publik)

- **Tautan Kuesioner Umum (Anggota Memilih Cabang):**
  `http://localhost/kuesioner_anggota/survey.php`
- **Tautan QR Langsung Per Cabang (Otomatis Terkunci ke Cabang):**
  - Cabang F01 Pati Kota: `http://localhost/kuesioner_anggota/survey.php?token=FASTABIQ_F01_...` (atau `?cabang=F01`)
  - Cabang F02 Wedarijaksa: `http://localhost/kuesioner_anggota/survey.php?cabang=F02`
  - Cabang F03 Juwana: `http://localhost/kuesioner_anggota/survey.php?cabang=F03`
  - *(Dapat diunduh dan dicetak dalam bentuk QR Stand Meja pada menu Master Data Cabang).*

---

## 🛡️ 6. Catatan Khusus & Penanganan Merger F09
Sesuai arahan tertulis pada catatan WhatsApp:
- **Cabang F09** telah dinonaktifkan/digabung ke **F02 Wedarijaksa**.
- Data kuesioner dan rekap produk untuk wilayah Wedarijaksa disatukan ke kode F02, dan kode F09 diberi keterangan jelas pada sistem agar tidak terjadi kerancuan pelaporan.

---
*Dikembangkan dengan amanah untuk KSPPS BMT Fastabiq Khoiro Ummah.*

