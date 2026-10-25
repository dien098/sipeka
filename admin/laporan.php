<?php require_once __DIR__ . '/../includes/domain.php';
require_admin_page();
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$w = ['l.deleted_at IS NULL'];
$p = [];
if ($q !== '') {
    $w[] = '(k.nama_kategori LIKE ? OR u.nama LIKE ? OR l.deskripsi LIKE ? OR CAST(l.id_laporan AS CHAR)=?)';
    array_push($p, '%' . $q . '%', '%' . $q . '%', '%' . $q . '%', $q);
}
if (in_array($status, status_list(), true)) {
    $w[] = 'l.status=?';
    $p[] = $status;
}
$where = implode(' AND ', $w);
$s = $pdo->prepare('SELECT COUNT(*) FROM laporan l JOIN users u ON u.id_user=l.id_user JOIN inventaris_fasilitas i ON i.id_inventaris=l.id_inventaris JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori WHERE ' . $where);
$s->execute($p);
$count = (int)$s->fetchColumn();
$s = $pdo->prepare('SELECT l.id_laporan,l.status,l.jumlah_unit,l.tanggal_laporan,u.nama pelapor,k.nama_kategori,lo.nama_gedung,lo.lantai,lo.ruang FROM laporan l JOIN users u ON u.id_user=l.id_user JOIN inventaris_fasilitas i ON i.id_inventaris=l.id_inventaris JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori JOIN lokasi lo ON lo.id_lokasi=i.id_lokasi WHERE ' . $where . ' ORDER BY FIELD(l.status,"Menunggu Verifikasi","Terverifikasi","Sedang Ditangani","Selesai","Ditolak"),l.tanggal_laporan ASC LIMIT 10 OFFSET ' . (($page - 1) * 10));
$s->execute($p);
$rows = $s->fetchAll();
$page_title = 'Laporan';
require __DIR__ . '/../includes/header.php'; ?><form class="sp-card mb-4" method="get">
    <div class="row g-2">
        <div class="col-md-7"><label class="form-label" for="q">Cari laporan</label><input class="form-control" name="q" id="q" value="<?= e($q) ?>" placeholder="ID, fasilitas, pelapor, deskripsi"></div>
        <div class="col-md-4"><label class="form-label" for="status">Status</label><select class="form-select" name="status" id="status">
                <option value="">Semua status</option><?php foreach (status_list() as $s): ?><option <?= ($status === $s ? 'selected' : '') ?>><?= e($s) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-1 d-flex align-items-end"><button class="btn sp-btn-outline w-100">Cari</button></div>
    </div>
</form>
<div class="sp-card"><?php if (!$rows): render_empty('Laporan tidak ditemukan', 'Coba ubah kata kunci atau filter status.', 'admin/laporan.php', 'Reset filter');
                        else: ?><div class="table-responsive">
            <table class="table sp-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fasilitas dan lokasi</th>
                        <th>Pelapor</th>
                        <th>Unit</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $r): ?><tr>
                            <td>#<?= e($r['id_laporan']) ?></td>
                            <td><strong><?= e($r['nama_kategori']) ?></strong>
                                <div class="sp-muted small"><?= e($r['nama_gedung']) ?> · Lt <?= e($r['lantai']) ?> · <?= e($r['ruang']) ?></div>
                            </td>
                            <td><?= e($r['pelapor']) ?></td>
                            <td><?= e($r['jumlah_unit']) ?></td>
                            <td><?= e(format_date($r['tanggal_laporan'])) ?></td>
                            <td><?= badge_status($r['status']) ?></td>
                            <td><a class="btn btn-sm sp-btn-outline" href="<?= e(app_url('admin/detail-laporan.php?id=' . $r['id_laporan'])) ?>">Periksa</a></td>
                        </tr><?php endforeach; ?></tbody>
            </table>
        </div><?php if ($count > 10): ?><div class="d-flex justify-content-between"><span class="sp-muted small">Halaman <?= $page ?> dari <?= ceil($count / 10) ?></span><?php if ($page > 1): ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">← Sebelumnya</a><?php endif;
                                                                                                                                                                                                                                                                            if ($page * 10 < $count): ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Berikutnya →</a><?php endif; ?></div><?php endif;
                                                                                                                                                                                                                                                                                                                                                                                                                    endif; ?></div><?php require __DIR__ . '/../includes/footer.php'; ?>