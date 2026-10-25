<?php require_once __DIR__ . '/../includes/domain.php';
require_admin_page();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_only();
    try {
        $id = (int)($_POST['id_lokasi'] ?? 0);
        $gedung = normalize_text($_POST['nama_gedung'] ?? '');
        $lantai = normalize_text($_POST['lantai'] ?? '');
        $ruang = normalize_text($_POST['ruang'] ?? '');
        if (!$gedung || !$lantai || !$ruang) throw new RuntimeException('Gedung, lantai, dan ruang wajib diisi.');
        $s = $pdo->prepare('UPDATE lokasi SET nama_gedung=?,lantai=?,ruang=? WHERE id_lokasi=?');
        $s->execute([$gedung, $lantai, $ruang, $id]);
        flash('success', 'Lokasi berhasil diperbarui.');
        redirect(app_url('admin/lokasi.php'));
    } catch (Throwable $e) {
        $err = $e instanceof RuntimeException ? $e->getMessage() : 'Kombinasi gedung, lantai, dan ruang sudah digunakan.';
        error_log($e instanceof PDOException ? $e->getMessage() : '');
    }
}
$rows = $pdo->query('SELECT l.*,COUNT(i.id_inventaris) dipakai FROM lokasi l LEFT JOIN inventaris_fasilitas i ON i.id_lokasi=l.id_lokasi GROUP BY l.id_lokasi ORDER BY l.nama_gedung,l.lantai,l.ruang')->fetchAll();
$page_title = 'Lokasi';
require __DIR__ . '/../includes/header.php'; ?><div class="mb-4">
    <p class="sp-muted">Lokasi dibuat otomatis saat Admin menambahkan inventaris. Di halaman ini, lokasi hanya dapat diubah atau dihapus jika belum digunakan.</p>
</div><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><div class="sp-card">
    <div class="table-responsive">
        <table class="table sp-table">
            <thead>
                <tr>
                    <th>Gedung</th>
                    <th>Lantai</th>
                    <th>Ruang</th>
                    <th>Dipakai inventaris</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody><?php foreach ($rows as $r): ?><tr>
                        <td><?= e($r['nama_gedung']) ?></td>
                        <td><?= e($r['lantai']) ?></td>
                        <td><?= e($r['ruang']) ?></td>
                        <td><?= e($r['dipakai']) ?> inventaris</td>
                        <td><button class="btn btn-sm sp-btn-outline" data-bs-toggle="modal" data-bs-target="#edit<?= e($r['id_lokasi']) ?>">Ubah</button>
                            <form method="post" action="<?= e(app_url('admin/hapus-lokasi.php')) ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id_lokasi" value="<?= e($r['id_lokasi']) ?>"><button class="btn btn-sm btn-outline-danger" <?= $r['dipakai'] ? 'disabled title="Masih dipakai oleh inventaris"' : 'data-confirm="Hapus lokasi ini?"' ?> type="submit">Hapus</button></form>
                        </td>
                    </tr>
                    <div class="modal fade" id="edit<?= e($r['id_lokasi']) ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content" style="background:var(--sp-surface);color:var(--sp-text)">
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="id_lokasi" value="<?= e($r['id_lokasi']) ?>">
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5">Ubah lokasi</h2><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body"><label class="form-label">Gedung</label><input class="form-control mb-3" name="nama_gedung" value="<?= e($r['nama_gedung']) ?>" required><label class="form-label">Lantai</label><input class="form-control mb-3" name="lantai" value="<?= e($r['lantai']) ?>" required><label class="form-label">Ruang</label><input class="form-control" name="ruang" value="<?= e($r['ruang']) ?>" required></div>
                                    <div class="modal-footer"><button type="button" class="btn sp-btn-outline" data-bs-dismiss="modal">Batal</button><button class="btn sp-btn-primary">Simpan</button></div>
                                </form>
                            </div>
                        </div>
                    </div><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div><?php require __DIR__ . '/../includes/footer.php'; ?>