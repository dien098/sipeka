CREATE DATABASE IF NOT EXISTS sipeka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sipeka;
CREATE TABLE users (
 id_user INT UNSIGNED NOT NULL AUTO_INCREMENT, nama VARCHAR(100) NOT NULL, username VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL, password_hash VARCHAR(255) NOT NULL, role ENUM('Admin','User') NOT NULL DEFAULT 'User', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id_user), UNIQUE KEY uq_users_username(username), UNIQUE KEY uq_users_email(email)
) ENGINE=InnoDB;
CREATE TABLE kategori_fasilitas (
 id_kategori INT UNSIGNED NOT NULL AUTO_INCREMENT, nama_kategori VARCHAR(50) NOT NULL, PRIMARY KEY(id_kategori), UNIQUE KEY uq_kategori_nama(nama_kategori)
) ENGINE=InnoDB;
CREATE TABLE lokasi (
 id_lokasi INT UNSIGNED NOT NULL AUTO_INCREMENT, nama_gedung VARCHAR(100) NOT NULL, lantai VARCHAR(20) NOT NULL, ruang VARCHAR(50) NOT NULL, PRIMARY KEY(id_lokasi), UNIQUE KEY uq_lokasi(nama_gedung,lantai,ruang)
) ENGINE=InnoDB;
CREATE TABLE inventaris_fasilitas (
 id_inventaris INT UNSIGNED NOT NULL AUTO_INCREMENT, id_kategori INT UNSIGNED NOT NULL, id_lokasi INT UNSIGNED NOT NULL, jumlah_total INT NOT NULL, jumlah_baik INT NOT NULL, jumlah_rusak INT NOT NULL, catatan_kondisi VARCHAR(255) NULL, is_aktif TINYINT(1) NOT NULL DEFAULT 1, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, updated_by INT UNSIGNED NOT NULL,
 PRIMARY KEY(id_inventaris), UNIQUE KEY uq_inventaris(id_kategori,id_lokasi), CONSTRAINT chk_inv_jumlah CHECK(jumlah_baik>=0 AND jumlah_rusak>=0 AND jumlah_total=jumlah_baik+jumlah_rusak), CONSTRAINT fk_inv_kategori FOREIGN KEY(id_kategori) REFERENCES kategori_fasilitas(id_kategori) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_inv_lokasi FOREIGN KEY(id_lokasi) REFERENCES lokasi(id_lokasi) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_inv_updater FOREIGN KEY(updated_by) REFERENCES users(id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE TABLE laporan (
 id_laporan INT UNSIGNED NOT NULL AUTO_INCREMENT, id_user INT UNSIGNED NOT NULL, id_inventaris INT UNSIGNED NOT NULL, jumlah_unit INT NOT NULL DEFAULT 1, deskripsi TEXT NOT NULL, foto VARCHAR(255) NOT NULL, tanggal_laporan TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, status ENUM('Menunggu Verifikasi','Terverifikasi','Sedang Ditangani','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu Verifikasi', alasan_penolakan TEXT NULL, catatan_penanganan TEXT NULL, kondisi_diterapkan TINYINT(1) NOT NULL DEFAULT 0, deleted_at DATETIME NULL, deleted_by INT UNSIGNED NULL,
 PRIMARY KEY(id_laporan), KEY idx_laporan_inv_status(id_inventaris,status), KEY idx_laporan_user(id_user), CONSTRAINT chk_laporan_unit CHECK(jumlah_unit>=1), CONSTRAINT fk_laporan_user FOREIGN KEY(id_user) REFERENCES users(id_user) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_laporan_inv FOREIGN KEY(id_inventaris) REFERENCES inventaris_fasilitas(id_inventaris) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_laporan_del FOREIGN KEY(deleted_by) REFERENCES users(id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE TABLE riwayat_status (
 id_riwayat INT UNSIGNED NOT NULL AUTO_INCREMENT, id_laporan INT UNSIGNED NOT NULL, id_pengubah INT UNSIGNED NOT NULL, status_lama ENUM('Menunggu Verifikasi','Terverifikasi','Sedang Ditangani','Selesai','Ditolak') NULL, status_baru ENUM('Menunggu Verifikasi','Terverifikasi','Sedang Ditangani','Selesai','Ditolak') NOT NULL, waktu_perubahan TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, catatan VARCHAR(255) NULL,
 PRIMARY KEY(id_riwayat), KEY idx_riwayat_laporan(id_laporan), CONSTRAINT fk_riwayat_laporan FOREIGN KEY(id_laporan) REFERENCES laporan(id_laporan) ON DELETE RESTRICT ON UPDATE CASCADE, CONSTRAINT fk_riwayat_pengubah FOREIGN KEY(id_pengubah) REFERENCES users(id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
INSERT INTO kategori_fasilitas(nama_kategori) VALUES ('Meja'),('Kursi'),('Pintu'),('Jendela'),('Lampu'),('AC'),('Toilet'),('Keran Air');
