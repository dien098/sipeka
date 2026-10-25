# SIPEKA — Spesifikasi untuk Coding Agent (Claude Code / Codex)

Sistem Informasi Pencatatan dan Pelaporan Kerusakan Fasilitas Kampus  
Versi dokumen: 1.6 (versi ringkas untuk implementasi, sudah termasuk spesifikasi UI/UX di bagian 10). Bahasa antarmuka dan komentar kode: Indonesia.

## 0. Cara memakai dokumen ini

Dokumen ini adalah **sumber kebenaran tunggal**. Simpan di root proyek sebagai `PRD.md`, lalu buat `AGENTS.md` (Codex) atau `CLAUDE.md` (Claude Code) berisi satu baris: "Baca dan patuhi PRD.md sebelum mengubah kode."

Aturan untuk agent:

1. Kerjakan **per tahap** (bagian 13). Selesaikan dan verifikasi satu tahap sebelum lanjut. Jangan mengerjakan tahap berikutnya tanpa diminta.
2. Jangan menambah fitur di luar bagian 2. Jika ada hal yang tidak jelas, **tanyakan** ke pengguna, jangan berasumsi.
3. Jangan mengubah skema database tanpa memperbarui bagian 8 dan `database.sql` secara bersamaan.
4. Kode harus sederhana dan dapat dijelaskan oleh mahasiswa saat demonstrasi: PHP prosedural, tanpa framework PHP, tanpa Composer. Library front-end yang diizinkan hanya **Bootstrap 5** dan **Chart.js**, disimpan sebagai file lokal di `assets/vendor/` (bagian 10.2); tidak ada library lain. Beri komentar singkat berbahasa Indonesia pada logika penting (transaksi, validasi, otorisasi).
5. Setiap query memakai **prepared statement** (PDO). Setiap output ke HTML memakai `e()`. Setiap form POST memakai token CSRF. Tidak ada pengecualian.
6. Setelah menyelesaikan tahap, jalankan test case terkait (bagian 12) dan laporkan hasilnya. Untuk tampilan, patuhi bagian 10 dan jangan menciptakan gaya sendiri.

## 1. Ringkasan

Aplikasi web dua peran (Admin, User) yang menggabungkan:

- **Pencatatan inventaris**: Admin mencatat fasilitas, jumlah (total/baik/rusak), dan lokasi (gedung, lantai, ruang). Contoh: Kursi, total 40, baik 38, rusak 2, Gedung Selat Sunda, lantai 2, ruang 017.
- **Pelaporan kerusakan**: User melaporkan kerusakan pada fasilitas yang sudah ada di inventaris, dengan deskripsi dan foto, lalu memantau statusnya. Admin memverifikasi, menolak, dan memperbarui status. Saat Admin memverifikasi bahwa fasilitas benar rusak, jumlah baik/rusak di inventaris berubah **otomatis**.

Untuk tugas UTS Perancangan dan Implementasi Basis Data Berbasis Web. Fokus penilaian: relasi tabel, PK/FK, constraint, transaksi, autentikasi, otorisasi, CRUD, validasi, keamanan dasar.

## 2. Ruang lingkup

**Termasuk:** registrasi, login, logout; CRUD inventaris, kategori, lokasi oleh Admin; pembuatan, edit, soft delete laporan oleh User; verifikasi, penolakan, pembaruan status oleh Admin; riwayat status; peringatan duplikat; dashboard ringkasan.

**Tidak termasuk (jangan dibuat):** ubah profil/kata sandi, kelola akun user oleh Admin, hapus akun, inventaris per unit/nomor aset, kamera/GPS/peta, notifikasi email/WhatsApp, chat, AI, peran Petugas/teknisi, banyak Admin, API/mobile app, export laporan.

## 3. Stack dan lingkungan

| Hal | Ketentuan |
|---|---|
| Server | XAMPP di Windows: Apache, PHP, MySQL/MariaDB |
| Akses lokal | `http://localhost/sipeka/` (folder `C:\xampp\htdocs\sipeka`) |
| Database | Nama `sipeka`, engine InnoDB, charset `utf8mb4`, collation `utf8mb4_unicode_ci` |
| Akses DB | PDO, `ERRMODE_EXCEPTION`, `ATTR_EMULATE_PREPARES = false` |
| Frontend | HTML, Bootstrap 5.3 (lokal) + `assets/css/theme.css` kustom, Chart.js 4 (lokal), JavaScript seperlunya (tanpa framework JS) |
| Editor | VS Code |

## 4. Peran dan hak akses

| Aksi | Publik | User | Admin |
|---|---|---|---|
| Registrasi, login | ya | - | - |
| Logout | - | ya | ya |
| Buat laporan | - | ya | tidak |
| Lihat laporan | - | hanya miliknya | semua |
| Edit / soft delete laporan | - | miliknya, hanya saat Menunggu Verifikasi | tidak |
| Verifikasi / tolak / ubah status laporan | - | tidak | ya |
| CRUD inventaris, kategori, lokasi | - | tidak | ya |
| Lihat daftar fasilitas untuk dipilih saat lapor | - | ya (hanya baca) | - |

- Registrasi publik **selalu** membuat role `User`. Role tidak boleh datang dari input form.
- Akun Admin dibuat lewat `install-admin.php`: form meminta nama, username, email, kata sandi saat dijalankan (tidak ada kata sandi di kode). Skrip menolak berjalan jika sudah ada Admin, dan menampilkan pesan agar file dihapus setelah dipakai.
- Setiap halaman memanggil `require_login()` lalu `require_role()` di baris awal. Pemeriksaan dilakukan di server, bukan hanya dengan menyembunyikan menu.

## 5. Status laporan

Lima status: `Menunggu Verifikasi`, `Terverifikasi`, `Sedang Ditangani`, `Selesai`, `Ditolak`.

Transisi yang diizinkan (selain ini ditolak oleh server):

```
Menunggu Verifikasi → Terverifikasi
Menunggu Verifikasi → Ditolak          (alasan_penolakan wajib)
Terverifikasi       → Sedang Ditangani
Sedang Ditangani    → Selesai
```

`Selesai` dan `Ditolak` adalah status akhir.

## 6. Aturan bisnis inventaris dan laporan

### 6.1 Efek tiap aksi terhadap inventaris

| Aksi | Efek pada `inventaris_fasilitas` | Efek pada `laporan` |
|---|---|---|
| User kirim, edit, atau hapus (soft) laporan | tidak ada | `kondisi_diterapkan = 0` |
| Admin **verifikasi** | `jumlah_baik -= jumlah_unit`, `jumlah_rusak += jumlah_unit` (total tetap) | `status = Terverifikasi`, `kondisi_diterapkan = 1` |
| Admin **tolak** | tidak ada | `status = Ditolak`, isi `alasan_penolakan` |
| Admin → **Sedang Ditangani** | tidak ada | `status` berubah |
| Admin → **Selesai** | jika `kondisi_diterapkan = 1`: `jumlah_rusak -= jumlah_unit`, `jumlah_baik += jumlah_unit` | `status = Selesai`, `kondisi_diterapkan = 0` |

Arti `kondisi_diterapkan`: 1 berarti unit pada laporan ini sedang dihitung sebagai rusak di inventaris. Fungsinya mencegah penghitungan ganda.

### 6.2 Transaksi (wajib)

Verifikasi dan Selesai dijalankan dalam **satu transaksi** PDO:

```
BEGIN
  SELECT laporan ... FOR UPDATE            -- cek status saat ini, deleted_at IS NULL
  SELECT inventaris ... FOR UPDATE         -- kunci baris inventaris
  validasi transisi status dan jumlah      -- lihat 6.3
  UPDATE inventaris (jumlah_baik, jumlah_rusak, updated_by)
  UPDATE laporan (status, kondisi_diterapkan, catatan_penanganan)
  INSERT riwayat_status (id_laporan, id_pengubah, status_lama, status_baru, catatan)
COMMIT                                      -- jika ada error apa pun: ROLLBACK
```

Penolakan dan Sedang Ditangani juga dalam transaksi (UPDATE laporan + INSERT riwayat). Pembuatan laporan: INSERT laporan + INSERT riwayat awal (`status_lama` NULL, `status_baru` Menunggu Verifikasi, pengubah = User pelapor) dalam satu transaksi.

### 6.3 Validasi

- `jumlah_unit` ≥ 1 dan ≤ `jumlah_baik` inventaris, dicek saat laporan dibuat **dan** saat verifikasi (nilai bisa berubah di antaranya).
- Penolakan tanpa `alasan_penolakan` ditolak.
- Inventaris nonaktif (`is_aktif = 0`) tidak muncul di formulir laporan baru, tetapi laporan yang sudah ada tetap bisa diproses sampai Selesai.
- Saat Admin mengubah inventaris secara manual: `jumlah_total = jumlah_baik + jumlah_rusak`, semua ≥ 0, dan `jumlah_rusak` ≥ jumlah `jumlah_unit` seluruh laporan dengan `kondisi_diterapkan = 1` pada inventaris itu (agar pemulihan saat Selesai tidak membuat angka negatif).
- Duplikat: saat User mengirim laporan, jika ada laporan lain pada `id_inventaris` yang sama dengan `deleted_at IS NULL` dan status `Menunggu Verifikasi`, `Terverifikasi`, atau `Sedang Ditangani`, tampilkan **peringatan** dan minta konfirmasi. Tidak menolak otomatis.

### 6.4 Penghapusan

- Laporan: hanya soft delete (`deleted_at`, `deleted_by`) oleh pemilik saat status Menunggu Verifikasi. Laporan yang dihapus tidak tampil di daftar aktif dan tidak dihitung dalam duplikat atau ringkasan.
- Inventaris: tidak ada hard delete dari aplikasi; hanya `is_aktif = 0`.
- Kategori dan lokasi: boleh dihapus permanen hanya jika tidak dirujuk inventaris manapun. Jika dirujuk, tampilkan pesan "masih dipakai oleh inventaris" (tangkap error FK atau cek dulu dengan COUNT).
- `riwayat_status`: tidak pernah diubah atau dihapus (log audit).

### 6.5 Input inventaris satu formulir (Admin)

Field: nama fasilitas, gedung, lantai, ruang, jumlah total, jumlah baik, jumlah rusak, catatan kondisi (opsional).

- Nama fasilitas memakai `<input list>` + `<datalist>` berisi kategori yang sudah ada (dirender server-side).
- Normalisasi semua teks: `trim`, ganti spasi berurutan menjadi satu spasi. Pencocokan kategori dan lokasi tidak membedakan huruf besar/kecil (ditangani collation `_ci`).
- Dalam **satu transaksi**: cari kategori, jika tidak ada INSERT; cari lokasi (gedung+lantai+ruang), jika tidak ada INSERT; lalu INSERT inventaris. Jika kombinasi kategori+lokasi sudah ada di inventaris, tolak dengan pesan jelas dan tidak membuat apa pun.

## 7. Halaman

| File | Peran | Fungsi |
|---|---|---|
| `index.php` | publik | Landing page (bagian 10.5); jika sudah login, arahkan ke dashboard sesuai peran |
| `register.php`, `login.php`, `logout.php` | publik/login | Autentikasi; logout lewat POST + CSRF |
| `user/dashboard.php` | User | Ringkasan laporan miliknya per status (bagian 10.7) |
| `user/buat-laporan.php` | User | Pilih lokasi, lalu fasilitas (dropdown bertingkat dari data JSON yang dirender di halaman, tanpa endpoint terpisah), jumlah unit, deskripsi, foto, peringatan duplikat |
| `user/laporan-saya.php` | User | Daftar laporan aktif miliknya |
| `user/detail-laporan.php` | User | Detail, foto, riwayat status, alasan penolakan, tombol edit/hapus bila boleh |
| `user/edit-laporan.php` | User | Edit saat Menunggu Verifikasi |
| `admin/dashboard.php` | Admin | Statistik dan diagram (bagian 10.6) |
| `admin/inventaris.php` | Admin | Daftar (cari, filter lokasi/kategori), form tambah satu formulir, ubah, nonaktifkan/aktifkan |
| `admin/kategori.php`, `admin/lokasi.php` | Admin | Daftar, ubah nama, hapus bila belum dipakai |
| `admin/laporan.php` | Admin | Semua laporan aktif, cari dan filter status |
| `admin/detail-laporan.php` | Admin | Detail, foto, riwayat; form verifikasi/tolak/ubah status + catatan |

Struktur folder:

```
sipeka/
├── config/database.php
├── includes/ (auth.php, functions.php, csrf.php, header.php, sidebar.php, footer.php)
├── admin/    (dashboard, inventaris, laporan, detail-laporan, kategori, lokasi)
├── user/     (dashboard, buat-laporan, laporan-saya, detail-laporan, edit-laporan)
├── uploads/laporan/   (+ .htaccess yang menonaktifkan eksekusi PHP)
├── assets/   (css/theme.css, js/app.js, js/charts.js, vendor/bootstrap/, vendor/chartjs/, img/)
├── register.php  login.php  logout.php  index.php  install-admin.php
├── database.sql
├── PRD.md
└── README.md
```

## 8. Skema database

Enam tabel. Salin ke `database.sql` apa adanya, urutan pembuatan seperti di bawah. Semua FK `ON DELETE RESTRICT ON UPDATE CASCADE`.

```sql
CREATE DATABASE IF NOT EXISTS sipeka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sipeka;

CREATE TABLE users (
  id_user       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama          VARCHAR(100) NOT NULL,
  username      VARCHAR(50)  NOT NULL,
  email         VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('Admin','User') NOT NULL DEFAULT 'User',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_user),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE kategori_fasilitas (
  id_kategori   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_kategori VARCHAR(50) NOT NULL,
  PRIMARY KEY (id_kategori),
  UNIQUE KEY uq_kategori_nama (nama_kategori)
) ENGINE=InnoDB;

CREATE TABLE lokasi (
  id_lokasi   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_gedung VARCHAR(100) NOT NULL,
  lantai      VARCHAR(20)  NOT NULL,
  ruang       VARCHAR(50)  NOT NULL,
  PRIMARY KEY (id_lokasi),
  UNIQUE KEY uq_lokasi (nama_gedung, lantai, ruang)
) ENGINE=InnoDB;

CREATE TABLE inventaris_fasilitas (
  id_inventaris   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_kategori     INT UNSIGNED NOT NULL,
  id_lokasi       INT UNSIGNED NOT NULL,
  jumlah_total    INT NOT NULL,
  jumlah_baik     INT NOT NULL,
  jumlah_rusak    INT NOT NULL,
  catatan_kondisi VARCHAR(255) NULL,
  is_aktif        TINYINT(1) NOT NULL DEFAULT 1,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_inventaris),
  UNIQUE KEY uq_inventaris (id_kategori, id_lokasi),
  CONSTRAINT chk_inv_jumlah CHECK (jumlah_baik >= 0 AND jumlah_rusak >= 0 AND jumlah_total = jumlah_baik + jumlah_rusak),
  CONSTRAINT fk_inv_kategori FOREIGN KEY (id_kategori) REFERENCES kategori_fasilitas (id_kategori) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_inv_lokasi   FOREIGN KEY (id_lokasi)   REFERENCES lokasi (id_lokasi)               ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_inv_updater  FOREIGN KEY (updated_by)  REFERENCES users (id_user)                  ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE laporan (
  id_laporan          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_user             INT UNSIGNED NOT NULL,
  id_inventaris       INT UNSIGNED NOT NULL,
  jumlah_unit         INT NOT NULL DEFAULT 1,
  deskripsi           TEXT NOT NULL,
  foto                VARCHAR(255) NOT NULL,
  tanggal_laporan     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status              ENUM('Menunggu Verifikasi','Terverifikasi','Sedang Ditangani','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu Verifikasi',
  alasan_penolakan    TEXT NULL,
  catatan_penanganan  TEXT NULL,
  kondisi_diterapkan  TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at          DATETIME NULL,
  deleted_by          INT UNSIGNED NULL,
  PRIMARY KEY (id_laporan),
  KEY idx_laporan_inv_status (id_inventaris, status),
  KEY idx_laporan_user (id_user),
  CONSTRAINT chk_laporan_unit CHECK (jumlah_unit >= 1),
  CONSTRAINT fk_laporan_user FOREIGN KEY (id_user)       REFERENCES users (id_user)                       ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_laporan_inv  FOREIGN KEY (id_inventaris) REFERENCES inventaris_fasilitas (id_inventaris) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_laporan_del  FOREIGN KEY (deleted_by)    REFERENCES users (id_user)                       ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE riwayat_status (
  id_riwayat       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_laporan       INT UNSIGNED NOT NULL,
  id_pengubah      INT UNSIGNED NOT NULL,
  status_lama      ENUM('Menunggu Verifikasi','Terverifikasi','Sedang Ditangani','Selesai','Ditolak') NULL,
  status_baru      ENUM('Menunggu Verifikasi','Terverifikasi','Sedang Ditangani','Selesai','Ditolak') NOT NULL,
  waktu_perubahan  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  catatan          VARCHAR(255) NULL,
  PRIMARY KEY (id_riwayat),
  KEY idx_riwayat_laporan (id_laporan),
  CONSTRAINT fk_riwayat_laporan  FOREIGN KEY (id_laporan)  REFERENCES laporan (id_laporan) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_riwayat_pengubah FOREIGN KEY (id_pengubah) REFERENCES users (id_user)      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
```

Relasi: users 1—N laporan; kategori 1—N inventaris; lokasi 1—N inventaris; inventaris 1—N laporan; laporan 1—N riwayat_status; users 1—N riwayat_status. `inventaris_fasilitas` adalah relasi M:N antara kategori dan lokasi yang membawa atribut jumlah dan kondisi.

Data awal di `database.sql` (tanpa user; Admin dibuat via `install-admin.php`): kategori Meja, Kursi, Pintu, Jendela, Lampu, AC, Toilet, Keran Air. Data inventaris contoh dibuat setelah Admin ada, lewat form atau skrip seed terpisah.

## 9. Keamanan (checklist wajib)

- `password_hash()` / `password_verify()`; `session_regenerate_id(true)` setelah login; pesan gagal login generik.
- Prepared statements di semua query; validasi di server; `e()` (`htmlspecialchars` dengan `ENT_QUOTES`, UTF-8) di semua output.
- Token CSRF di semua form POST, termasuk logout dan aksi status.
- Cek kepemilikan: User hanya boleh membuka, mengedit, menghapus laporan dengan `id_user` miliknya; pelanggaran → 403 atau redirect.
- Upload foto (wajib 1 per laporan): ekstensi `jpg/jpeg/png`, ukuran maksimal 2 MB, cek MIME dengan `finfo` dan `getimagesize()`, nama simpan acak (`bin2hex(random_bytes(16))` + ekstensi), simpan di `uploads/laporan/`, `.htaccess` di folder itu mematikan eksekusi PHP. Path di DB hanya nama file.
- Error database tidak ditampilkan ke pengguna (log ke `error_log`, tampilkan pesan umum). Kredensial DB hanya di `config/database.php`.
- Pola Post/Redirect/Get dan flash message setelah setiap aksi POST.

## 10. UI/UX

Acuan visual: mockup "SIPEKA UI Mockup" (landing page, login, dashboard Admin) yang bergaya gelap kehijauan dengan aksen lime, kartu transparan membulat, dan angka statistik besar. Jika mockup dan dokumen ini berbeda, **dokumen ini yang berlaku**. Semua angka dan nama di mockup hanyalah contoh; jangan dimasukkan ke database kecuali data awal pada bagian 8.

### 10.1 Prinsip

1. Tampilan konsisten: semua halaman memakai token (10.3) dan komponen (10.4); jangan memberi warna atau ukuran acak.
2. Bootstrap sebagai dasar, `theme.css` sebagai kulit. Jangan menulis ulang grid, modal, toast, form, atau tabel dari nol.
3. Status dan kondisi selalu memakai **teks + warna** (dan titik atau ikon), tidak hanya warna.
4. Pesan, label, dan tombol dalam bahasa Indonesia yang ringkas.
5. Tampilan harus tetap rapi di layar HP (lebar 360px) sampai desktop (1440px).

### 10.2 Penggunaan Bootstrap dan library

- Bootstrap 5.3 (CSS dan bundle JS) disimpan lokal di `assets/vendor/bootstrap/` supaya aplikasi jalan tanpa internet di XAMPP. Jika agent tidak dapat mengunduh, pakai tautan CDN sementara dan tulis catatan di `README.md` agar diganti ke file lokal.
- Chart.js 4 (satu file `chart.umd.min.js`) di `assets/vendor/chartjs/`.
- Urutan muat: `bootstrap.min.css` → Google Fonts (Plus Jakarta Sans, dengan fallback `system-ui`) → `assets/css/theme.css`.
- `theme.css` menimpa Bootstrap lewat CSS variables (`--bs-body-bg`, `--bs-body-color`, `--bs-border-color`, `--bs-primary`, dan sebagainya) dan class milik sendiri berawalan `sp-` (contoh `sp-card`, `sp-badge`, `sp-stat`). Dilarang memakai `!important` berlebihan dan dilarang gaya inline berulang.
- Ikon: SVG inline bergaya garis tipis (stroke 1.8, ujung bulat), disimpan sebagai fungsi `icon('nama')` di `includes/functions.php`. Dilarang memakai emoji sebagai ikon.
- Tema: default gelap (`data-bs-theme="dark"` di `<html>`). Tombol toggle di topbar mengganti ke terang dan menyimpannya di `localStorage` (bungkus try/catch, halaman harus tetap tampil benar tanpa localStorage). Terapkan tema sebelum halaman digambar (skrip kecil di `<head>`) agar tidak berkedip.

### 10.3 Token desain (didefinisikan sekali di `:root` dan `[data-bs-theme="light"]` pada `theme.css`)

| Token | Gelap | Terang |
|---|---|---|
| `--sp-bg` (latar halaman) | `#0B1410` | `#F4F8F1` |
| `--sp-sidebar` | `#0F1A14` | `#FFFFFF` |
| `--sp-surface` (kartu) | `#111C16` | `#FFFFFF` |
| `--sp-surface-2` (input, kartu bersarang) | `#16241C` | `#EEF4EA` |
| `--sp-border` | `rgba(255,255,255,.08)` | `rgba(11,20,16,.12)` |
| `--sp-text` | `#E8F0EA` | `#14211A` |
| `--sp-muted` | `#8FA597` | `#4F6558` |
| `--sp-accent` (isi tombol utama) | `#A3D65C` | `#A3D65C` |
| `--sp-on-accent` (teks di atas aksen) | `#0B1410` | `#0B1410` |
| `--sp-accent-text` (link, angka sorot) | `#A3D65C` | `#3F6B10` |

Warna status laporan dan kondisi (badge memakai latar warna yang sama dengan opasitas 16%):

| Status / kondisi | Gelap | Terang (teks) |
|---|---|---|
| Menunggu Verifikasi | `#F2B84B` | `#8A5A00` |
| Terverifikasi | `#5BC0EB` | `#0B6FA0` |
| Sedang Ditangani | `#B79CFF` | `#5B3FC4` |
| Selesai | `#5FD38D` | `#17743F` |
| Ditolak | `#FF8A7A` | `#B3392B` |
| Baik | `#A3D65C` | `#3F6B10` |
| Rusak | `#FF8A7A` | `#B3392B` |

Bentuk dan tipografi:
- Font Plus Jakarta Sans 400/500/600/700. Judul halaman 30px/700, judul kartu 17px/600, isi 15px, keterangan 13px, angka statistik 36px/700. Judul hero landing memakai `clamp(40px, 6vw, 68px)`.
- Kartu: radius 18px, border 1px `--sp-border`. Input: tinggi 50px, radius 12px. Tombol: bentuk kapsul (radius 999px), tinggi minimal 44px.
- Tombol utama: latar `--sp-accent`, teks `--sp-on-accent`, tebal 700, ikon panah kanan. Tombol sekunder: kapsul dengan garis tepi. Tombol bahaya: garis tepi merah, isi merah hanya di dialog konfirmasi.
- Ikon dalam kartu: lingkaran 36–52px dengan latar warna ikon pada opasitas 14–16%.
- Fokus keyboard: cincin 2px `--sp-accent-text` pada semua elemen interaktif. Target sentuh minimal 44px.
- Target kontras teks 4,5:1 di kedua tema; periksa saat tahap review.

### 10.4 Komponen

| Komponen | Spesifikasi |
|---|---|
| Badge status | Kapsul kecil: titik warna + teks status (mis. "● Menunggu Verifikasi"). Fungsi `badge_status($status)` satu-satunya sumber warna. |
| Kartu statistik | Label di kiri atas + ikon lingkaran di kanan atas, angka 36px, keterangan 13px. Kartu "Perlu verifikasi" bergaris tepi amber. |
| Bar kondisi | Bar 6–8px radius penuh, isi hijau = persen baik; teks "38 / 40 baik". Dipakai di tabel inventaris dan kartu. |
| Tabel | `table-responsive`; header teks redup; baris dengan garis pemisah tipis; di bawah 768px berubah menjadi daftar kartu (`d-md-none` + `d-none d-md-block`). |
| Form | Label di atas field, bantuan teks di bawah, error di bawah field memakai `is-invalid` + `invalid-feedback`; nilai yang sudah diisi tidak hilang saat validasi gagal. |
| Toast | Bootstrap Toast pojok kanan atas, otomatis hilang 5 detik. Semua flash message (sukses, error, peringatan) tampil sebagai toast, kecuali peringatan duplikat yang berupa banner permanen di formulir. |
| Dialog konfirmasi | Bootstrap Modal untuk: Verifikasi, Tolak (dengan kolom alasan wajib), Ubah ke Selesai, Nonaktifkan inventaris, Hapus kategori/lokasi, Hapus laporan. Berisi ringkasan dampak dan tombol Batal + tombol aksi. |
| Kondisi kosong | Ikon besar redup, satu kalimat penjelasan, satu tombol ajakan. |
| Stepper status | 4 langkah (Menunggu → Terverifikasi → Ditangani → Selesai); langkah tercapai berwarna aksen; jika Ditolak, tampil satu penanda merah menggantikan langkah selanjutnya. |
| Linimasa riwayat | Daftar vertikal dari `riwayat_status`: titik berwarna status, "status lama → status baru", pelaku, waktu, catatan. |

### 10.5 Kerangka halaman

**Layout publik** (`index.php`): navbar (logo + SIPEKA, tautan Beranda, Cara Kerja, Fitur, Mulai; tombol Masuk dan Daftar; toggle tema). Urutan seksi landing:
1. Hero: lencana kecil "SISTEM PENCATATAN DAN PELAPORAN FASILITAS KAMPUS", judul dua warna "Lapor Cepat. **Kampus Nyaman.**" (baris kedua aksen), paragraf, tombol "Buat Laporan" (utama) dan "Lihat Cara Kerja". Di kanan, kartu kaca "Kondisi Ruang" berisi 3 baris fasilitas dengan bar kondisi (diambil dari inventaris aktif acak/teratas; jika kosong, tampilkan kondisi kosong).
2. Strip statistik 4 angka dari database: unit fasilitas tercatat, laporan masuk, laporan selesai, persen kondisi baik.
3. "Cara Kerja": 4 kartu (Catat, Lapor, Verifikasi, Pantau sampai selesai) dengan ikon lingkaran berwarna.
4. "Fitur utama": daftar centang di kiri, kartu ringkasan donut di kanan.
5. Pita ajakan "Ada fasilitas yang rusak? Laporkan sekarang." dengan tombol Daftar Sekarang dan Masuk, lalu footer.
Angka dan kartu pada landing dihitung dari database (agregat saja, tanpa data pribadi); bila belum ada data, tampilkan 0 dan kondisi kosong yang rapi.

**Layout auth** (`login.php`, `register.php`): dua kolom. Kiri panel merek (logo, tagline dua warna, 3 poin manfaat, hiasan lingkaran garis tipis); kanan kartu formulir dengan tautan "Kembali ke beranda". Di bawah 992px panel kiri disembunyikan.

**Layout aplikasi** (semua halaman setelah login): sidebar 248px tetap di layar ≥992px dan menjadi `offcanvas` dengan tombol menu di bawah itu. Topbar: judul halaman + tanggal, tombol aksi utama halaman, toggle tema, inisial pengguna.
- Menu Admin: Dashboard, Inventaris, Laporan (lencana jumlah Menunggu Verifikasi), Kategori, Lokasi, Keluar.
- Menu User: Dashboard, Buat Laporan, Laporan Saya, Keluar.
- Item aktif: latar aksen 14% + teks `--sp-accent-text`. Blok pengguna di dasar sidebar (inisial, nama, peran, ikon keluar; keluar lewat POST + CSRF).

### 10.6 Dashboard Admin (statistik dan diagram)

Susunan (desktop; di layar kecil menumpuk satu kolom):

| Baris | Widget | Jenis |
|---|---|---|
| 1 | Total fasilitas, Kondisi baik (+ persen), Kondisi rusak (+ persen), Perlu verifikasi | 4 kartu statistik |
| 2 (2/3 lebar) | Laporan masuk dan selesai, 6 bulan terakhir | Grafik garis, Chart.js |
| 2 (1/3 lebar) | Kondisi fasilitas | Donut, Chart.js, persen baik di tengah |
| 3 (1/3) | Laporan per status | Batang horizontal HTML (bar CSS) |
| 3 (1/3) | Kategori paling banyak rusak (5 teratas) | Batang horizontal HTML (bar CSS) |
| 3 (1/3) | Perlu tindakan | Daftar 5 laporan Menunggu Verifikasi terlama + tombol "Periksa" |

Query (semua prepared/aman; tidak ada input pengguna):

```sql
-- Kartu & donut (inventaris aktif)
SELECT COALESCE(SUM(jumlah_total),0) AS total, COALESCE(SUM(jumlah_baik),0) AS baik,
       COALESCE(SUM(jumlah_rusak),0) AS rusak
FROM inventaris_fasilitas WHERE is_aktif = 1;

-- Laporan per status (juga untuk kartu "Perlu verifikasi")
SELECT status, COUNT(*) AS jumlah FROM laporan WHERE deleted_at IS NULL GROUP BY status;

-- Laporan masuk per bulan (6 bulan terakhir)
SELECT DATE_FORMAT(tanggal_laporan,'%Y-%m') AS bulan, COUNT(*) AS jumlah
FROM laporan
WHERE deleted_at IS NULL
  AND tanggal_laporan >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 5 MONTH)
GROUP BY bulan ORDER BY bulan;

-- Laporan selesai per bulan (diambil dari riwayat status)
SELECT DATE_FORMAT(rs.waktu_perubahan,'%Y-%m') AS bulan, COUNT(*) AS jumlah
FROM riwayat_status rs JOIN laporan l ON l.id_laporan = rs.id_laporan
WHERE rs.status_baru = 'Selesai' AND l.deleted_at IS NULL
  AND rs.waktu_perubahan >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 5 MONTH)
GROUP BY bulan ORDER BY bulan;

-- Kategori paling banyak rusak
SELECT k.nama_kategori, SUM(i.jumlah_rusak) AS rusak
FROM inventaris_fasilitas i JOIN kategori_fasilitas k ON k.id_kategori = i.id_kategori
WHERE i.is_aktif = 1 GROUP BY k.id_kategori, k.nama_kategori
HAVING rusak > 0 ORDER BY rusak DESC LIMIT 5;

-- Perlu tindakan
SELECT l.id_laporan, k.nama_kategori, lo.nama_gedung, lo.lantai, lo.ruang, u.nama, l.tanggal_laporan
FROM laporan l
JOIN inventaris_fasilitas i ON i.id_inventaris = l.id_inventaris
JOIN kategori_fasilitas k ON k.id_kategori = i.id_kategori
JOIN lokasi lo ON lo.id_lokasi = i.id_lokasi
JOIN users u ON u.id_user = l.id_user
WHERE l.status = 'Menunggu Verifikasi' AND l.deleted_at IS NULL
ORDER BY l.tanggal_laporan ASC LIMIT 5;
```

Aturan grafik:
- PHP melengkapi bulan yang kosong dengan 0 sehingga selalu ada 6 titik; label bulan singkat bahasa Indonesia (Mei, Jun, Jul, Agu, Sep, Okt).
- Data diteruskan ke JavaScript lewat atribut `data-*` berisi `json_encode` dengan `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`; skrip `assets/js/charts.js` membaca atribut itu. Tidak ada endpoint JSON terpisah.
- Garis "Masuk" solid warna aksen, garis "Selesai" putus-putus warna teks; legenda ditampilkan; tooltip menampilkan angka persis.
- Donut: `cutout` ±70%, warna baik/rusak dari token, persen baik di tengah (elemen HTML di atas kanvas). Beri `aria-label` berisi ringkasan angka pada setiap kanvas, dan tampilkan angka juga sebagai teks (legenda) agar tidak bergantung pada warna.
- Warna Chart.js dibaca dari CSS variables saat halaman dimuat, dan grafik digambar ulang ketika tema diganti.
- Jika tidak ada data: tampilkan kondisi kosong, bukan grafik kosong.

### 10.7 Dashboard User
Tombol utama besar "Buat Laporan"; 4 kartu angka (Total laporan, Menunggu, Diproses = Terverifikasi + Sedang Ditangani, Selesai); daftar 5 laporan terbaru dengan badge status dan tautan "Lihat semua". Jika belum ada laporan: kondisi kosong dengan ajakan membuat laporan pertama.

### 10.8 Halaman kunci

**Inventaris (Admin).**
- Daftar: kotak cari, filter gedung dan kategori, tabel dengan kolom Fasilitas, Lokasi ("Selat Sunda · Lt 2 · 017"), Total, Baik, Rusak, bar kondisi, status (Aktif/Nonaktif), aksi (Ubah, Nonaktifkan/Aktifkan). Paginasi 10 baris.
- Formulir tambah (satu halaman, dua kelompok kartu): *Fasilitas dan Lokasi* (nama fasilitas dengan datalist, gedung, lantai, ruang) dan *Jumlah dan Kondisi* (baik, rusak, catatan). Kolom **Total hanya-baca** dan dihitung otomatis dari baik + rusak lewat JavaScript; server tetap memvalidasi ulang. Tombol Simpan (utama) dan Batal.
- Formulir ubah: gedung/lantai/ruang dan kategori tidak diubah di sini (hanya jumlah, catatan, aktif); koreksi nama lokasi/kategori lewat halaman masing-masing. Tampilkan peringatan jika jumlah rusak ditolak oleh aturan 6.3.

**Buat laporan (User).** Satu halaman, urutan: Gedung → Ruang (tertulis "Lantai 2 · Ruang 017") → Fasilitas (tertulis "Kursi · 38 baik") → Jumlah unit (angka, min 1, maks jumlah baik) → Deskripsi → Foto (area unggah dengan pratinjau dan batas 2 MB, JPG/PNG). Dropdown bertingkat dari data yang dirender server dalam atribut `data-*`. Jika ada laporan aktif pada inventaris yang sama, tampil banner amber berisi tautan ke laporan itu dan kotak centang "Tetap kirim laporan ini" sebelum tombol Kirim aktif. Tidak ada fasilitas yang bisa dipilih → kondisi kosong "Belum ada fasilitas tercatat di ruang ini".

**Detail laporan.** Kolom kiri: foto, deskripsi, info fasilitas (kategori, lokasi, jumlah unit). Kolom kanan: stepper status dan linimasa riwayat. Alasan penolakan tampil di banner merah jika Ditolak. Panel aksi **Admin** menampilkan tombol sesuai status saat ini saja (Verifikasi/Tolak; Mulai Ditangani; Tandai Selesai) dan **pratinjau dampak** di dalam dialog konfirmasi, mis. "Kursi · Selat Sunda Lt 2 · 017: baik 38 → 37, rusak 2 → 3" (diambil dari data saat itu, bukan teks tetap). Panel aksi **User**: tombol Edit dan Hapus hanya jika status Menunggu Verifikasi.

**Kategori dan Lokasi (Admin).** Tabel dengan kolom "Dipakai N inventaris". Tombol Hapus dinonaktifkan dengan tooltip jika N > 0. Tambah/ubah lewat modal sederhana (lokasi hanya bisa diubah, bukan ditambah dari halaman ini, sesuai bagian 8).

### 10.9 Responsif dan aksesibilitas (checklist review)
- Lebar 360px: tidak ada gulir horizontal pada halaman; tabel berubah jadi kartu; sidebar jadi offcanvas.
- Semua input memiliki `<label>`; semua tombol berupa `<button>` atau `<a>` asli; tombol ikon punya `aria-label`; ikon dekoratif `aria-hidden="true"`.
- Urutan Tab logis dan fokus terlihat; dialog menahan fokus (perilaku bawaan Bootstrap).
- Status tidak hanya dibedakan warna; grafik punya alternatif teks.
- Teks utama dan redup memenuhi kontras 4,5:1 di tema gelap dan terang.
- Setiap halaman memiliki `<title>` yang spesifik, mis. "Inventaris · SIPEKA".

### 10.10 Bahasa antarmuka
Gunakan istilah tetap: Inventaris, Laporan, Fasilitas, Lokasi, Kategori, "Menunggu Verifikasi", "Terverifikasi", "Sedang Ditangani", "Selesai", "Ditolak". Tombol aksi memakai kata kerja: Simpan, Batal, Kirim Laporan, Verifikasi, Tolak, Mulai Ditangani, Tandai Selesai, Nonaktifkan, Hapus. Pesan error menjelaskan masalah dan cara memperbaikinya (mis. "Jumlah unit tidak boleh lebih dari 38 unit yang berkondisi baik").

## 11. Keputusan default (ubah di sini jika perlu)

| Hal | Default |
|---|---|
| Format foto | JPG/PNG, maks 2 MB, wajib |
| `lantai`, `ruang` | teks bebas (mis. "2", "017", "Basement") |
| Akses DB XAMPP | user `root`, kata sandi kosong, diisi di `config/database.php` |
| Pagination | 10 baris per halaman pada daftar laporan dan inventaris |
| Library front-end | Bootstrap 5.3 dan Chart.js 4, file lokal di `assets/vendor/` |
| Tema | Gelap sebagai default, terang opsional lewat toggle |
| Grafik yang memakai Chart.js | Hanya garis dan donut; batang horizontal memakai bar CSS |

## 12. Test case

| ID | Skenario | Hasil |
|---|---|---|
| TC-01 | Registrasi valid | Akun User dibuat |
| TC-02 | Registrasi username/email sudah ada | Ditolak |
| TC-03 | Login valid | Masuk ke dashboard sesuai peran |
| TC-04 | Login salah | Pesan generik |
| TC-05 | User membuka halaman Admin | Akses ditolak |
| TC-06 | User buat laporan valid | Status Menunggu Verifikasi; inventaris tidak berubah |
| TC-07 | Upload file tidak diizinkan | Ditolak server |
| TC-08 | User ubah laporan milik orang lain | Ditolak |
| TC-09 | User edit laporan yang sudah diverifikasi | Ditolak |
| TC-10 | Admin tolak tanpa alasan | Diminta alasan |
| TC-11 | Admin ubah status | Status dan riwayat konsisten |
| TC-12 | User hapus laporan Menunggu Verifikasi | Soft delete |
| TC-13 | Gagal simpan riwayat atau inventaris di tengah transaksi | Rollback total |
| TC-14 | Laporan pada inventaris yang sudah punya laporan aktif | Peringatan duplikat |
| TC-15 | Tambah inventaris dengan kategori+lokasi yang sudah ada | Ditolak |
| TC-16 | Total ≠ baik + rusak, atau angka negatif | Ditolak |
| TC-17 | Pilih lokasi tanpa fasilitas tertentu | Fasilitas itu tidak muncul |
| TC-18 | Verifikasi laporan 1 unit pada inventaris 38 baik / 2 rusak | Menjadi 37 / 3, total 40 |
| TC-19 | Ubah ke Selesai | Kembali 38 / 2 |
| TC-20 | Laporan ditolak | Inventaris tidak berubah |
| TC-21 | Hapus inventaris yang dipakai laporan | Tidak ada fitur hapus; hanya nonaktifkan |
| TC-22 | `jumlah_unit` > `jumlah_baik` | Ditolak saat buat atau verifikasi |
| TC-23 | Input inventaris dengan lokasi baru | Lokasi dibuat otomatis |
| TC-24 | Input "kursi " padahal "Kursi" ada | Memakai kategori yang ada |
| TC-25 | Inventaris gagal tersimpan setelah kategori/lokasi dibuat | Rollback, tidak ada data yatim |
| TC-26 | Hapus kategori belum dipakai | Terhapus |
| TC-27 | Hapus kategori sudah dipakai | Ditolak dengan pesan jelas |
| TC-28 | Hapus lokasi sudah dipakai | Ditolak dengan pesan jelas |
| TC-29 | Admin ubah manual `jumlah_rusak` di bawah total unit laporan terverifikasi | Ditolak |
| TC-30 | Transisi tidak sah (mis. Menunggu Verifikasi → Selesai) | Ditolak server |
| TC-31 | Dashboard Admin: bandingkan angka kartu dan grafik dengan hitungan manual dari tabel | Angka sama |
| TC-32 | Dashboard Admin saat belum ada laporan atau inventaris | Kondisi kosong tampil, tanpa error |
| TC-33 | Buka semua halaman pada lebar 360px | Tidak ada gulir horizontal; sidebar jadi menu; tabel jadi kartu |
| TC-34 | Ganti tema gelap ↔ terang | Warna dan grafik ikut berubah, pilihan tetap setelah muat ulang |
| TC-35 | Gunakan seluruh formulir hanya dengan keyboard | Semua bisa dijangkau dan fokus terlihat |

## 13. Tahap implementasi

Tiap tahap punya kriteria selesai; jangan lanjut sebelum terpenuhi.

| Tahap | Pekerjaan | Selesai bila |
|---|---|---|
| 1 | Struktur folder, `config/database.php`, `includes/*` (koneksi PDO, `e()`, CSRF, session, `require_login`, `require_role`), Bootstrap dan Chart.js lokal, `theme.css` dengan token bagian 10.3, layout `header/sidebar/footer`, fungsi `icon()` dan `badge_status()`, toggle tema | Halaman uji terhubung ke DB dan tampil dengan tema gelap-lime di dua tema tanpa error |
| 2 | `database.sql` (bagian 8) dan data awal | Import di phpMyAdmin sukses, 6 tabel dan semua FK terlihat |
| 3 | Autentikasi: registrasi, login, logout, `install-admin.php`, redirect per peran; layout auth (10.5) dan landing page `index.php` | TC-01 s.d. TC-05 lulus; login dan landing sesuai bagian 10.5 |
| 4 | Admin: kategori, lokasi, inventaris (form satu formulir, find-or-create, ubah, nonaktifkan, hapus kategori/lokasi) | TC-15, 16, 21, 23–29 lulus |
| 5 | User: buat laporan (dropdown bertingkat, upload, duplikat), daftar, detail, edit, soft delete | TC-06 s.d. TC-09, 12, 14, 17, 22 lulus |
| 6 | Admin: daftar laporan, detail, verifikasi, tolak, ubah status, riwayat, transaksi + sinkron inventaris | TC-10, 11, 13, 18–20, 30 lulus |
| 7 | Dashboard User dan Admin (kartu, grafik Chart.js, batang CSS, daftar Perlu tindakan sesuai 10.6–10.7), pencarian, filter, pagination | TC-31 dan TC-32 lulus |
| 8 | Review keamanan (bagian 9), review UI (checklist 10.9), `README.md` (instalasi, akun demo), screenshot pengujian | Semua TC lulus; checklist keamanan dan UI terpenuhi |

