<?php require_once __DIR__ . '/../includes/domain.php';
require_user_page();
$uid = current_user()['id_user'];
$s = $pdo->prepare("SELECT COUNT(*) total,SUM(status='Menunggu Verifikasi') menunggu,SUM(status IN ('Terverifikasi','Sedang Ditangani')) diproses,SUM(status='Selesai') selesai FROM laporan WHERE id_user=? AND deleted_at IS NULL");
$s->execute([$uid]);
$stat = $s->fetch();
$s = $pdo->prepare('SELECT l.id_laporan,l.status,l.tanggal_laporan,k.nama_kategori,lo.nama_gedung,lo.lantai,lo.ruang FROM laporan l JOIN inventaris_fasilitas i ON i.id_inventaris=l.id_inventaris JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori JOIN lokasi lo ON lo.id_lokasi=i.id_lokasi WHERE l.id_user=? AND l.deleted_at IS NULL ORDER BY l.tanggal_laporan DESC LIMIT 5');
$s->execute([$uid]);
$rows = $s->fetchAll();
$page_title = 'Dashboard';
require __DIR__ . '/../includes/header.php'; ?><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold">Halo, <?= e(current_user()['nama']) ?></h2>
        <p class="sp-muted mb-0">Pantau laporan fasilitas yang Anda kirim.</p>
    </div><a class="btn sp-btn-primary" href="<?= e(app_url('user/buat-laporan.php')) ?>">＋ Buat Laporan</a>
</div>
<div class="row g-3 mb-4"><?php foreach ([['Total laporan', (int)$stat['total'], 'file'], ['Menunggu', (int)$stat['menunggu'], 'clock'], ['Diproses', (int)$stat['diproses'], 'chart'], ['Selesai', (int)$stat['selesai'], 'check']] as $x): ?><div class="col-6 col-xl-3">
            <div class="sp-card sp-stat">
                <div class="sp-stat-label"><?= e($x[0]) ?></div>
                <div class="sp-stat-icon"><?= icon($x[2]) ?></div>
                <div class="sp-stat-value"><?= $x[1] ?></div>
                <div class="sp-stat-note">Laporan milik Anda</div>
            </div>
        </div><?php endforeach; ?></div>
<div class="sp-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Laporan terbaru</h2><a href="<?= e(app_url('user/laporan-saya.php')) ?>">Lihat semua →</a>
    </div><?php if (!$rows): ?><div class="sp-empty">
            <div class="sp-empty-icon"><?= icon('file') ?></div>
            <h3>Belum ada laporan</h3>
            <p class="sp-muted">Buat laporan pertama jika menemukan fasilitas rusak.</p><a class="btn sp-btn-primary" href="<?= e(app_url('user/buat-laporan.php')) ?>">Buat Laporan →</a>
        </div><?php else: ?><div class="table-responsive">
            <table class="table sp-table">
                <thead>
                    <tr>
                        <th>Fasilitas</th>
                        <th>Lokasi</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $r): ?><tr>
                            <td><?= e($r['nama_kategori']) ?></td>
                            <td><?= e($r['nama_gedung']) ?> · Lt <?= e($r['lantai']) ?> · <?= e($r['ruang']) ?></td>
                            <td><?= e(format_date($r['tanggal_laporan'])) ?></td>
                            <td><?= badge_status($r['status']) ?></td>
                            <td><a href="<?= e(app_url('user/detail-laporan.php?id=' . $r['id_laporan'])) ?>">Detail</a></td>
                        </tr><?php endforeach; ?></tbody>
            </table>
        </div><?php endif; ?>
</div><?php require __DIR__ . '/../includes/footer.php'; ?>