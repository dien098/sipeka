# SIPEKA — Sistem Informasi Pencatatan dan Pelaporan Kerusakan Fasilitas Kampus

SIPEKA adalah aplikasi berbasis web yang digunakan untuk membantu pencatatan, pengelolaan, dan pelaporan kerusakan fasilitas kampus. Aplikasi ini dirancang agar proses pelaporan kerusakan dan pemantauan status penanganannya dapat dilakukan secara lebih terstruktur.

## 1. Teknologi yang Digunakan

Aplikasi SIPEKA dikembangkan menggunakan teknologi berikut:

- **PHP prosedural** sebagai bahasa pemrograman sisi server.
- **MySQL/MariaDB** sebagai sistem manajemen basis data.
- **PDO (PHP Data Objects)** untuk koneksi dan interaksi dengan basis data.
- **Bootstrap 5.3** untuk membantu pembuatan antarmuka yang responsif.
- **Chart.js 4** untuk menampilkan visualisasi data dalam bentuk grafik.
- **Apache** sebagai web server lokal melalui XAMPP.

Aplikasi ini tidak menggunakan framework PHP maupun Composer.

## 2. Persyaratan Instalasi

Sebelum menjalankan aplikasi, pastikan perangkat telah memiliki:

- Sistem operasi Windows atau sistem operasi lain yang mendukung PHP dan MySQL/MariaDB.
- XAMPP atau lingkungan server lokal sejenis.
- PHP dengan ekstensi PDO MySQL aktif.
- MySQL atau MariaDB.
- Browser, seperti Google Chrome, Mozilla Firefox, atau Microsoft Edge.

## 3. Instalasi Aplikasi

### Langkah 1 — Siapkan file aplikasi

Unduh atau salin source code SIPEKA ke komputer, kemudian ekstrak jika masih berbentuk file ZIP.

Jika menggunakan XAMPP di Windows, letakkan folder aplikasi di:

```text
C:\xampp\htdocs\sipeka
```

Pastikan struktur folder aplikasi tetap utuh setelah diekstrak.

### Langkah 2 — Jalankan server lokal

1. Buka XAMPP Control Panel.
2. Klik **Start** pada modul Apache.
3. Klik **Start** pada modul MySQL.
4. Pastikan kedua modul berjalan tanpa kesalahan.

### Langkah 3 — Buat dan konfigurasi database

Buka browser dan akses:

```text
http://localhost/phpmyadmin
```

Jika file `database.sql` tersedia pada source code, gunakan langkah berikut:

1. Pilih menu **Import** di phpMyAdmin.
2. Pilih file `database.sql` dari folder proyek.
3. Jalankan proses import.
4. Pastikan database `sipeka` dan tabel-tabelnya berhasil dibuat.

Database utama yang digunakan adalah `sipeka`, dengan tabel inti berikut:

- `users`
- `kategori_fasilitas`
- `lokasi`
- `inventaris_fasilitas`
- `laporan`
- `riwayat_status`

Jika file SQL tidak tersedia, database dan tabel harus dibuat berdasarkan skema basis data proyek.

### Langkah 4 — Konfigurasi koneksi database

Buka file konfigurasi database:

```text
config/database.php
```

Sesuaikan host, nama database, username, dan password dengan konfigurasi MySQL/MariaDB pada komputer.

Contoh konfigurasi untuk XAMPP dengan pengaturan lokal bawaan:

```php
<?php

$host = 'localhost';
$dbname = 'sipeka';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal.');
}
```

**Catatan:** kode di atas merupakan contoh konfigurasi. Pertahankan struktur dan nama variabel yang digunakan oleh source code asli. Sesuaikan nilainya jika format konfigurasi proyek berbeda. Username `root` dan password kosong hanya sesuai untuk konfigurasi XAMPP lokal tertentu.

### Langkah 5 — Buat akun administrator

Jika source code menyediakan file `install-admin.php`, buka alamat berikut melalui browser:

```text
http://localhost/sipeka/install-admin.php
```

Ikuti instruksi yang ditampilkan untuk membuat akun administrator.

Langkah ini hanya diperlukan jika fitur instalasi administrator tersedia dan belum dilakukan. Setelah akun berhasil dibuat, hapus atau nonaktifkan file instalasi tersebut jika sudah tidak diperlukan agar tidak dapat digunakan kembali tanpa izin.

Jika aplikasi menggunakan mekanisme pembuatan akun yang berbeda, ikuti mekanisme yang tersedia pada source code.

## 4. Cara Menjalankan Aplikasi

Setelah Apache, MySQL/MariaDB, database, dan konfigurasi koneksi siap:

1. Pastikan Apache dan MySQL tetap berjalan.
2. Buka browser.
3. Akses alamat aplikasi:

   ```text
   http://localhost/sipeka/
   ```

4. Masuk menggunakan akun yang tersedia.
5. Gunakan menu aplikasi sesuai hak akses akun.

Jika aplikasi tidak dapat dibuka, periksa kembali nama folder proyek, status Apache, serta alamat URL yang digunakan.

## 5. Data Awal dan Data Dummy

Jika proyek menyediakan file `dummy-data.sql`, file tersebut dapat digunakan untuk mengisi database dengan data contoh untuk keperluan pengujian atau demonstrasi.

Cara mengimpornya:

1. Pastikan skema database dari `database.sql` sudah dibuat.
2. Buka phpMyAdmin.
3. Pilih database `sipeka`.
4. Buka menu **Import**.
5. Pilih file `dummy-data.sql`, kemudian jalankan import.

Gunakan data dummy hanya untuk pengujian. Periksa isi file sebelum mengimpornya dan hindari mengimpor data contoh ke database produksi yang berisi data asli.

## 6. Struktur Database

Database SIPEKA memiliki tabel inti berikut:

| Tabel | Fungsi |
|---|---|
| `users` | Menyimpan informasi pengguna aplikasi. |
| `kategori_fasilitas` | Menyimpan kategori fasilitas. |
| `lokasi` | Menyimpan informasi lokasi fasilitas. |
| `inventaris_fasilitas` | Menyimpan data inventaris fasilitas kampus. |
| `laporan` | Menyimpan laporan kerusakan fasilitas. |
| `riwayat_status` | Menyimpan riwayat perubahan status laporan. |

Relasi antartabel digunakan untuk menjaga keterkaitan data pengguna, fasilitas, lokasi, kategori, laporan kerusakan, dan riwayat statusnya.

## 7. Pemecahan Masalah

### A. Aplikasi tidak dapat diakses

- Pastikan Apache telah berjalan di XAMPP.
- Pastikan folder aplikasi berada di `htdocs`.
- Pastikan URL yang diakses sesuai dengan nama folder proyek.

### B. Koneksi database gagal

- Pastikan MySQL/MariaDB telah berjalan.
- Pastikan database `sipeka` sudah dibuat.
- Periksa host, nama database, username, dan password di `config/database.php`.
- Pastikan ekstensi PDO MySQL pada PHP aktif.

### C. Tabel database tidak ditemukan

- Pastikan file `database.sql` berhasil diimpor.
- Pastikan tabel dibuat di database `sipeka`, bukan database lain.
- Periksa apakah struktur tabel sesuai dengan source code aplikasi.

### D. Data tidak muncul

- Pastikan data sudah tersedia di database.
- Jika diperlukan, impor `dummy-data.sql` setelah skema database dibuat.
- Periksa pesan kesalahan PHP dan query database yang digunakan.

### E. Halaman menampilkan kesalahan PHP

Periksa konfigurasi PHP, log kesalahan Apache/PHP, dan kode pada bagian yang bermasalah. Hindari menampilkan detail kesalahan database atau informasi sensitif kepada pengguna pada lingkungan produksi.

## 8. Catatan Keamanan

- Gunakan password yang kuat untuk akun pengguna.
- Jangan menyimpan kredensial database produksi di repositori publik.
- Batasi akses terhadap file konfigurasi.
- Hapus atau nonaktifkan skrip instalasi setelah digunakan.
- Validasi input pengguna dan gunakan prepared statements melalui PDO.
- Gunakan HTTPS dan konfigurasi server yang sesuai ketika aplikasi dipasang di lingkungan produksi.

## 9. Penutup

SIPEKA dikembangkan untuk membantu proses pencatatan dan pelaporan kerusakan fasilitas kampus secara terstruktur. Dengan mengikuti petunjuk instalasi dan konfigurasi di atas, aplikasi dapat dijalankan pada lingkungan server lokal untuk keperluan pengembangan, pengujian, dan demonstrasi.

Pastikan seluruh konfigurasi dan langkah instalasi disesuaikan dengan file serta struktur source code yang digunakan pada versi proyek SIPEKA.
