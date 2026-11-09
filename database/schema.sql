-- ============================================================
-- KUESIONER ANGGOTA - Database Schema
-- Database: db_kuesioner
-- Versi: 1.0 (2026)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `db_kuesioner` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_kuesioner`;

-- 1. Tabel Master Cabang
CREATE TABLE IF NOT EXISTS `cabang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_cabang` VARCHAR(20) NOT NULL UNIQUE,
  `nama_cabang` VARCHAR(100) NOT NULL,
  `alamat` VARCHAR(255) NULL,
  `telepon` VARCHAR(50) NULL,
  `kepala_cabang` VARCHAR(100) NULL,
  `token_qr` VARCHAR(64) NOT NULL UNIQUE,
  `status` ENUM('aktif', 'nonaktif', 'gabung') NOT NULL DEFAULT 'aktif',
  `catatan` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cabang_status` (`status`),
  INDEX `idx_cabang_kode` (`kode_cabang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Master Pengguna (Users)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NULL,
  `role` ENUM('admin', 'pusat', 'kepala_cabang') NOT NULL DEFAULT 'kepala_cabang',
  `cabang_id` INT NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `terakhir_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_users_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE SET NULL,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_cabang` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Master Kuesioner (Survei Versi)
CREATE TABLE IF NOT EXISTS `kuesioner` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(150) NOT NULL,
  `versi` VARCHAR(20) NOT NULL,
  `slogan` VARCHAR(255) DEFAULT 'Suara Anda, Semangat Kami untuk Melayani Lebih Baik.',
  `deskripsi` TEXT NULL,
  `status` ENUM('aktif', 'arsip', 'draft') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Master Pertanyaan
CREATE TABLE IF NOT EXISTS `pertanyaan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kuesioner_id` INT NOT NULL,
  `urutan` INT NOT NULL DEFAULT 1,
  `kode` VARCHAR(30) NOT NULL,
  `aspek` VARCHAR(60) NOT NULL,
  `label_aspek` VARCHAR(100) NOT NULL,
  `teks_pertanyaan` TEXT NOT NULL,
  `tipe` ENUM('rating_4', 'rating_5', 'text_opsional', 'produk_checkbox') NOT NULL DEFAULT 'rating_4',
  `wajib` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pertanyaan_kuesioner` FOREIGN KEY (`kuesioner_id`) REFERENCES `kuesioner` (`id`) ON DELETE CASCADE,
  INDEX `idx_pertanyaan_urutan` (`urutan`),
  INDEX `idx_pertanyaan_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel Respons Kuesioner (Induk Jawaban)
CREATE TABLE IF NOT EXISTS `survey_response` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `respon_token` VARCHAR(64) NOT NULL UNIQUE,
  `cabang_id` INT NOT NULL,
  `kuesioner_id` INT NOT NULL,
  `waktu_mulai` DATETIME NULL,
  `waktu_selesai` DATETIME NOT NULL,
  `durasi_detik` INT DEFAULT 0,
  `rata_rata_skor` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  `persentase_skor` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `jumlah_nilai_5` INT NOT NULL DEFAULT 0,
  `jumlah_pertanyaan` INT NOT NULL DEFAULT 10,
  `produk_ditawarkan` TEXT NULL,
  `tanda_tangan` VARCHAR(255) NULL,
  `kritik_saran_suka` TEXT NULL,
  `kritik_saran_perbaiki` TEXT NULL,
  `kritik_saran_lain` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `device_type` VARCHAR(20) DEFAULT 'mobile',
  `status` ENUM('valid', 'spam', 'dihapus') NOT NULL DEFAULT 'valid',
  `is_dummy` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_response_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`),
  CONSTRAINT `fk_response_kuesioner` FOREIGN KEY (`kuesioner_id`) REFERENCES `kuesioner` (`id`),
  INDEX `idx_resp_cabang` (`cabang_id`),
  INDEX `idx_resp_waktu` (`waktu_selesai`),
  INDEX `idx_resp_status` (`status`),
  INDEX `idx_resp_dummy` (`is_dummy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel Detail Jawaban Rating (Normalisasi)
CREATE TABLE IF NOT EXISTS `jawaban_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `response_id` INT NOT NULL,
  `pertanyaan_id` INT NOT NULL,
  `aspek` VARCHAR(60) NOT NULL,
  `nilai` TINYINT NOT NULL CHECK (`nilai` BETWEEN 1 AND 4),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_jawaban_response` FOREIGN KEY (`response_id`) REFERENCES `survey_response` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jawaban_pertanyaan` FOREIGN KEY (`pertanyaan_id`) REFERENCES `pertanyaan` (`id`) ON DELETE CASCADE,
  INDEX `idx_jawaban_response` (`response_id`),
  INDEX `idx_jawaban_pertanyaan` (`pertanyaan_id`),
  INDEX `idx_jawaban_aspek` (`aspek`),
  INDEX `idx_jawaban_nilai` (`nilai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel Rekap Produk Yang Ditawarkan Cabang (Sesuai Format Excel / Laporan Admisi)
CREATE TABLE IF NOT EXISTS `rekap_produk_cabang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cabang_id` INT NOT NULL,
  `periode_bulan` DATE NOT NULL,
  `pembiayaan` TINYINT(1) NOT NULL DEFAULT 0,
  `arisan` TINYINT(1) NOT NULL DEFAULT 0,
  `sisuka` TINYINT(1) NOT NULL DEFAULT 0,
  `sirela` TINYINT(1) NOT NULL DEFAULT 0,
  `simpelpres` TINYINT(1) NOT NULL DEFAULT 0,
  `simapan` TINYINT(1) NOT NULL DEFAULT 0,
  `simabrur` TINYINT(1) NOT NULL DEFAULT 0,
  `umroh` TINYINT(1) NOT NULL DEFAULT 0,
  `salam` TINYINT(1) NOT NULL DEFAULT 0,
  `lumbung_beras` TINYINT(1) NOT NULL DEFAULT 0,
  `arisan_berkah` TINYINT(1) NOT NULL DEFAULT 0,
  `persentase_nilai` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `petugas_nama` VARCHAR(100) DEFAULT 'Endang K',
  `petugas_jabatan` VARCHAR(100) DEFAULT 'Admisi',
  `tanggal_laporan` DATE NULL,
  `catatan` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rekap_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`),
  UNIQUE KEY `uk_cabang_periode` (`cabang_id`, `periode_bulan`),
  INDEX `idx_rekap_periode` (`periode_bulan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabel Log Aktivitas User
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `detail` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_activity_user` (`user_id`),
  INDEX `idx_activity_date` (`created_at`),
  INDEX `idx_activity_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabel Pengaturan Sistem
CREATE TABLE IF NOT EXISTS `pengaturan` (
  `kunci` VARCHAR(50) PRIMARY KEY,
  `nilai` TEXT NOT NULL,
  `keterangan` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

