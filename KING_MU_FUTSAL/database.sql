CREATE DATABASE IF NOT EXISTS king_mu_futsal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE king_mu_futsal;

CREATE TABLE IF NOT EXISTS lapangan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL,
  jenis VARCHAR(50) NOT NULL DEFAULT 'Futsal',
  harga_per_jam INT UNSIGNED NOT NULL,
  status ENUM('tersedia','maintenance') NOT NULL DEFAULT 'tersedia',
  ukuran VARCHAR(50) NOT NULL DEFAULT 'Standar',
  jenis_rumput VARCHAR(50) NOT NULL DEFAULT 'Sintetis',
  parkir TINYINT(1) NOT NULL DEFAULT 1,
  kapasitas TINYINT UNSIGNED NOT NULL DEFAULT 10,
  fasilitas VARCHAR(255) NOT NULL DEFAULT 'Toilet, mushola, ruang tunggu',
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  lokasi VARCHAR(180) NOT NULL DEFAULT 'Lokasi venue belum diatur',
  rating DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  harga_siang INT UNSIGNED NULL, harga_malam INT UNSIGNED NULL, harga_weekend INT UNSIGNED NULL, foto VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kode_booking VARCHAR(24) NOT NULL UNIQUE,
  lapangan_id INT NOT NULL,
  nama_penyewa VARCHAR(100) NOT NULL,
  no_whatsapp VARCHAR(25) NOT NULL,
  email VARCHAR(120) NULL,
  tanggal DATE NOT NULL,
  jam TIME NOT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status_booking ENUM('menunggu_pembayaran','dikonfirmasi','dibatalkan','kedaluwarsa') NOT NULL DEFAULT 'menunggu_pembayaran',
  metode_pembayaran ENUM('transfer','qris','tunai') NOT NULL DEFAULT 'tunai',
  status_pembayaran ENUM('belum_bayar','menunggu_verifikasi','lunas','ditolak') NOT NULL DEFAULT 'belum_bayar',
  total_harga INT UNSIGNED NOT NULL DEFAULT 0, kode_promo VARCHAR(30) NULL, expires_at DATETIME NULL,
  CONSTRAINT fk_booking_lapangan FOREIGN KEY (lapangan_id) REFERENCES lapangan(id),
  CONSTRAINT uq_jadwal UNIQUE (lapangan_id, tanggal, jam),
  INDEX idx_tanggal (tanggal),
  INDEX idx_penyewa (no_whatsapp)
) ENGINE=InnoDB;

INSERT INTO lapangan (nama, jenis, harga_per_jam, ukuran, jenis_rumput, parkir, kapasitas, fasilitas)
SELECT 'Lapangan 1', 'Futsal', 120000, 'Standar', 'Rumput sintetis', 1, 10, 'Toilet, mushola, ruang tunggu'
WHERE NOT EXISTS (SELECT 1 FROM lapangan WHERE nama='Lapangan 1');
INSERT INTO lapangan (nama, jenis, harga_per_jam, ukuran, jenis_rumput, parkir, kapasitas, fasilitas)
SELECT 'Lapangan 2', 'Futsal', 100000, 'Standar', 'Rumput sintetis', 1, 10, 'Toilet, mushola, ruang tunggu'
WHERE NOT EXISTS (SELECT 1 FROM lapangan WHERE nama='Lapangan 2');

CREATE TABLE IF NOT EXISTS blocked_slots (
 id INT AUTO_INCREMENT PRIMARY KEY, lapangan_id INT NOT NULL, tanggal DATE NOT NULL, jam TIME NOT NULL,
 alasan VARCHAR(180) NOT NULL DEFAULT 'Diblokir admin', FOREIGN KEY (lapangan_id) REFERENCES lapangan(id) ON DELETE CASCADE,
 UNIQUE KEY uq_block (lapangan_id,tanggal,jam)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS promos (
 id INT AUTO_INCREMENT PRIMARY KEY, kode VARCHAR(30) NOT NULL UNIQUE, diskon_persen TINYINT UNSIGNED NOT NULL,
 aktif TINYINT(1) NOT NULL DEFAULT 1, mulai DATE NULL, selesai DATE NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS admin_users (
 id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;
