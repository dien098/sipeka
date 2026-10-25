
USE sipeka;
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO users (id_user, nama, username, email, password_hash, role) VALUES
(1, 'Administrator SIPEKA', 'admin', 'admin@sipeka.test', '$2y$12$DHcoEcoS4EJHft4vpww8o.DPKpnmSHV4D4iekKqjkfspbLQuHU4eW', 'Admin'),
(2, 'Andi Pratama', 'user1', 'andi@sipeka.test', '$2y$12$DHcoEcoS4EJHft4vpww8o.DPKpnmSHV4D4iekKqjkfspbLQuHU4eW', 'User'),
(3, 'Siti Rahma', 'user2', 'siti@sipeka.test', '$2y$12$DHcoEcoS4EJHft4vpww8o.DPKpnmSHV4D4iekKqjkfspbLQuHU4eW', 'User'),
(4, 'Budi Santoso', 'user3', 'budi@sipeka.test', '$2y$12$DHcoEcoS4EJHft4vpww8o.DPKpnmSHV4D4iekKqjkfspbLQuHU4eW', 'User');

INSERT INTO lokasi (id_lokasi, nama_gedung, lantai, ruang) VALUES
(2, 'Gedung Selat Sunda', '2', '017'),
(3, 'Gedung Baru', '1', '03'),
(4, 'Gedung Baru', '2', '09'),
(5, 'Labkom 1', '1', 'Lab 1'),
(6, 'Labkom 2', '1', 'Lab 2');

INSERT INTO inventaris_fasilitas
(id_inventaris, id_kategori, id_lokasi, jumlah_total, jumlah_baik, jumlah_rusak, catatan_kondisi, is_aktif, updated_by) VALUES
(1, 2, 2, 10, 9, 1, 'Satu kursi mengalami kerusakan pada kaki', 1, 1),
(2, 1, 1, 16, 14, 2, 'Dua meja perlu perbaikan permukaan', 1, 1),
(3, 5, 3, 12, 12, 0, 'Seluruh lampu berfungsi', 1, 1),
(4, 6, 4, 8, 6, 2, 'Dua unit AC perlu pemeriksaan', 1, 1),
(5, 3, 1, 8, 7, 1, 'Pintu ruang sedikit sulit ditutup', 1, 1),
(6, 4, 2, 10, 9, 1, 'Satu jendela macet', 1, 1),
(7, 7, 5, 4, 4, 0, 'Toilet dalam kondisi baik', 1, 1),
(8, 8, 5, 6, 5, 1, 'Keran air menetes', 1, 1),
(9, 2, 3, 20, 18, 2, 'Kursi ruang kelas', 1, 1),
(10, 1, 6, 14, 13, 1, 'Meja dosen dan mahasiswa', 1, 1),
(11, 5, 4, 15, 15, 0, 'Lampu LED', 1, 1),
(12, 6, 6, 5, 4, 1, 'Satu AC tidak dingin', 0, 1);

INSERT INTO laporan
(id_laporan, id_user, id_inventaris, jumlah_unit, deskripsi, foto, tanggal_laporan, status, alasan_penolakan, catatan_penanganan, kondisi_diterapkan, deleted_at, deleted_by) VALUES
(1, 2, 1, 1, 'Kaki kursi goyah dan berisiko membuat pengguna terjatuh.', 'dummy-laporan.jpg', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Terverifikasi', NULL, 'Laporan telah diverifikasi admin.', 1, NULL, NULL),
(2, 3, 2, 2, 'Permukaan meja retak dan perlu diperbaiki.', 'dummy-laporan.jpg', DATE_SUB(NOW(), INTERVAL 4 DAY), 'Sedang Ditangani', NULL, 'Menunggu jadwal perbaikan fasilitas.', 1, NULL, NULL),
(3, 4, 3, 1, 'Lampu di ruang kelas berkedip sesekali.', 'dummy-laporan.jpg', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Menunggu Verifikasi', NULL, NULL, 0, NULL, NULL),
(4, 2, 4, 1, 'AC mengeluarkan suara keras saat dinyalakan.', 'dummy-laporan.jpg', DATE_SUB(NOW(), INTERVAL 12 DAY), 'Selesai', NULL, 'Pembersihan dan pemeriksaan unit telah selesai.', 0, NULL, NULL),
(5, 3, 5, 1, 'Pintu ruang tidak dapat dikunci.', 'dummy-laporan.jpg', DATE_SUB(NOW(), INTERVAL 8 DAY), 'Ditolak', 'Mohon lampirkan foto kerusakan yang lebih jelas dan ajukan kembali jika masalah masih terjadi.', NULL, 0, NULL, NULL);

INSERT INTO riwayat_status (id_riwayat, id_laporan, id_pengubah, status_lama, status_baru, waktu_perubahan, catatan) VALUES
(1, 1, 2, NULL, 'Menunggu Verifikasi', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Laporan dibuat oleh User.'),
(2, 1, 1, 'Menunggu Verifikasi', 'Terverifikasi', DATE_SUB(NOW(), INTERVAL 4 DAY), 'Laporan diverifikasi Admin.'),
(3, 2, 3, NULL, 'Menunggu Verifikasi', DATE_SUB(NOW(), INTERVAL 4 DAY), 'Laporan dibuat oleh User.'),
(4, 2, 1, 'Menunggu Verifikasi', 'Terverifikasi', DATE_SUB(NOW(), INTERVAL 3 DAY), 'Laporan diverifikasi Admin.'),
(5, 2, 1, 'Terverifikasi', 'Sedang Ditangani', DATE_SUB(NOW(), INTERVAL 2 DAY), 'Perbaikan sedang dijadwalkan.'),
(6, 3, 4, NULL, 'Menunggu Verifikasi', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Laporan dibuat oleh User.'),
(7, 4, 2, NULL, 'Menunggu Verifikasi', DATE_SUB(NOW(), INTERVAL 12 DAY), 'Laporan dibuat oleh User.'),
(8, 4, 1, 'Menunggu Verifikasi', 'Terverifikasi', DATE_SUB(NOW(), INTERVAL 11 DAY), 'Laporan diverifikasi Admin.'),
(9, 4, 1, 'Terverifikasi', 'Sedang Ditangani', DATE_SUB(NOW(), INTERVAL 10 DAY), 'Perbaikan dimulai.'),
(10, 4, 1, 'Sedang Ditangani', 'Selesai', DATE_SUB(NOW(), INTERVAL 9 DAY), 'Perbaikan selesai.'),
(11, 5, 3, NULL, 'Menunggu Verifikasi', DATE_SUB(NOW(), INTERVAL 8 DAY), 'Laporan dibuat oleh User.'),
(12, 5, 1, 'Menunggu Verifikasi', 'Ditolak', DATE_SUB(NOW(), INTERVAL 7 DAY), 'Foto belum cukup jelas untuk verifikasi.');

COMMIT;
