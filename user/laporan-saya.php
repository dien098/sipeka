<?php require_once __DIR__ . '/../includes/domain.php';
require_user_page();
$uid = current_user()['id_user'];
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 10;
$s = $pdo->prepare('SELECT COUNT(*) FROM laporan WHERE id_user=? AND deleted_at IS NULL');
$s->execute([$uid]);
$count = (int)$s->fetchColumn();
$s = $pdo->prepare('SELECT l.id_laporan,l.status,l.tanggal_laporan,l.jumlah_unit,k.nama_kategori,lo.nama_gedung,lo.lantai,lo.ruang FROM laporan l JOIN inventaris_fasilitas i ON i.id_inventaris=l.id_inventaris JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori JOIN lokasi lo ON lo.id_lokasi=i.id_lokasi WHERE l.id_user=? AND l.deleted_at IS NULL ORDER BY l.tanggal_laporan DESC LIMIT ? OFFSET ?');
$s->bindValue(1, $uid, PDO::PARAM_INT);
$s->bindValue(2, $per, PDO::PARAM_INT);
$s->bindValue(3, ($page - 1) * $per, PDO::PARAM_INT);
$s->execute();
$rows = $s->fetchAll();
$page_title = 'Laporan Saya';
require __DIR__ . '/../includes/header.php'; ?><div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="sp-muted mb-0">Daftar laporan aktif yang Anda kirim.</p><a class="btn sp-btn-primary" href="<?= e(app_url('user/buat-laporan.php')) ?>">＋ Buat Laporan</a>
</div>
<div class="sp-card"><?php if (!$rows): render_empty('Belum ada laporan', 'Laporan yang Anda buat akan tampil di sini.', 'user/buat-laporan.php', 'Buat Laporan');
                        else: ?><div class="table-responsive">
            <table class="table sp-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fasilitas</th>
                        <th>Lokasi</th>
                        <th>Unit</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $r): ?><tr>
                            <td>#<?= e($r['id_laporan']) ?></td>
                            <td><?= e($r['nama_kategori']) ?></td>
                            <td><?= e($r['nama_gedung']) ?> · Lt <?= e($r['lantai']) ?> · <?= e($r['ruang']) ?></td>
                            <td><?= e($r['jumlah_unit']) ?></td>
                            <td><?= e(format_date($r['tanggal_laporan'])) ?></td>
                            <td><?= badge_status($r['status']) ?></td>
                            <td><a href="<?= e(app_url('user/detail-laporan.php?id=' . $r['id_laporan'])) ?>">Detail</a></td>
                        </tr><?php endforeach; ?></tbody>
            </table>
        </div><?php if ($count > $per): ?><nav class="d-flex justify-content-between"><span class="sp-muted small">Halaman <?= $page ?> dari <?= ceil($count / $per) ?></span><?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">← Sebelumnya</a><?php endif;
                                                                                                                                                                                                                                                    if ($page * $per < $count): ?><a href="?page=<?= $page + 1 ?>">Berikutnya →</a><?php endif; ?></nav><?php endif;
                                                                                                                                                                                                                                                                                                                                endif; ?></div><?php require __DIR__ . '/../includes/footer.php'; ?>