<?php
require_once __DIR__.'/includes/auth.php';
if (!empty($_SESSION['user'])) {
    redirect(app_url($_SESSION['user']['role'] === 'Admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
}
page_start('Beranda', true);
?>
<section class="sp-hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-8">
        <div class="sp-eyebrow">SISTEM PENCATATAN DAN PELAPORAN FASILITAS KAMPUS</div>
        <h1>Lapor Cepat.<br><span class="sp-accent">Kampus Nyaman.</span></h1>
        <p>SIPEKA membantu kampus mencatat inventaris fasilitas, menerima laporan kerusakan, dan memantau tindak lanjut secara terstruktur dan transparan.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a class="btn sp-btn-primary" href="<?=e(app_url('register.php'))?>">Buat Laporan →</a>
          <a class="btn sp-btn-outline" href="#cara-kerja">Lihat Alur Pelaporan</a>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="cara-kerja" class="sp-section">
  <div class="container">
    <div class="text-center mb-5"><div class="sp-eyebrow">SEDERHANA DAN TRANSPARAN</div><h2 class="sp-section-title mt-2">Alur Pelaporan</h2><p class="sp-muted mt-3 mb-0">Dari laporan dikirim hingga tindak lanjut selesai.</p></div>
    <div class="row g-3">
      <?php foreach ([['01','Kirim laporan','Pilih fasilitas, jelaskan kerusakan, dan lampirkan foto.'],['02','Verifikasi Admin','Admin memeriksa laporan dan menentukan tindak lanjut.'],['03','Pantau status','Pelapor dapat melihat perkembangan sampai selesai.']] as $step): ?>
        <div class="col-md-6 col-lg-4"><div class="sp-card h-100"><div class="sp-stat-icon position-static mb-3"><?=e($step[0])?></div><h3><?=e($step[1])?></h3><p class="sp-muted mb-0"><?=e($step[2])?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section id="fitur" class="sp-section">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6"><div class="sp-eyebrow">FITUR UTAMA</div><h2 class="sp-section-title mt-2 mb-4">Semua proses dalam satu tempat.</h2>
        <?php foreach (['Pencatatan inventaris dan lokasi fasilitas','Pelaporan kerusakan dengan foto','Riwayat perubahan status yang transparan','Pembaruan kondisi inventaris otomatis setelah verifikasi'] as $item): ?><p class="mb-3"><span class="sp-accent me-2">✓</span><?=e($item)?></p><?php endforeach; ?>
      </div>
      <div class="col-lg-6"><div class="sp-card h-100"><div class="sp-stat-icon position-static mb-3"><?=icon('check')?></div><h3>Pelaporan yang terstruktur</h3><p class="sp-muted mb-0">Setiap laporan terhubung dengan fasilitas dan lokasinya. Status, catatan penanganan, serta riwayat perubahan dapat dipantau sesuai hak akses pengguna.</p></div></div>
    </div>
  </div>
</section>
<section id="mulai" class="sp-section"><div class="container"><div class="sp-card text-center py-5"><h2 class="sp-section-title">Ada fasilitas yang rusak?</h2><p class="sp-muted">Bergabung untuk melaporkan kerusakan fasilitas kampus dan memantau tindak lanjutnya.</p><a class="btn sp-btn-primary" href="<?=e(app_url('register.php'))?>">Daftar Sekarang →</a> <a class="btn sp-btn-outline ms-2" href="<?=e(app_url('login.php'))?>">Masuk</a></div></div></section>
<?php page_end(true); ?>
