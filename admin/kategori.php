<?php require_once __DIR__ . '/../includes/domain.php';
require_admin_page();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_only();
    try {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id_kategori'] ?? 0);
        if ($action === 'save') {
            $nama = normalize_text($_POST['nama_kategori'] ?? '');
            if (!$nama || mb_strlen($nama) > 50) throw new RuntimeException('Nama kategori wajib diisi (maksimal 50 karakter).');
            if ($id) {
                $s = $pdo->prepare('UPDATE kategori_fasilitas SET nama_kategori=? WHERE id_kategori=?');
                $s->execute([$nama, $id]);
            } else {
                $s = $pdo->prepare('INSERT INTO kategori_fasilitas(nama_kategori) VALUES(?)');
                $s->execute([$nama]);
            }
            flash('success', 'Kategori berhasil disimpan.');
        } elseif ($action === 'delete') {
            $s = $pdo->prepare('SELECT COUNT(*) FROM inventaris_fasilitas WHERE id_kategori=?');
            $s->execute([$id]);
            if ((int)$s->fetchColumn() > 0) throw new RuntimeException('Kategori masih dipakai oleh inventaris.');
            $s = $pdo->prepare('DELETE FROM kategori_fasilitas WHERE id_kategori=?');
            $s->execute([$id]);
            flash('success', 'Kategori berhasil dihapus.');
        }
        redirect(app_url('admin/kategori.php'));
    } catch (Throwable $e) {
        $err = $e instanceof RuntimeException ? $e->getMessage() : 'Nama kategori sudah digunakan atau kategori masih dirujuk inventaris.';
        error_log($e instanceof PDOException ? $e->getMessage() : '');
    }
}
$rows = $pdo->query('SELECT k.*,COUNT(i.id_inventaris) dipakai FROM kategori_fasilitas k LEFT JOIN inventaris_fasilitas i ON i.id_kategori=k.id_kategori GROUP BY k.id_kategori ORDER BY k.nama_kategori')->fetchAll();
$page_title = 'Kategori';
require __DIR__ . '/../includes/header.php'; ?><div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="sp-muted mb-0">Kelola kategori fasilitas yang digunakan inventaris.</p><button class="btn sp-btn-primary" data-bs-toggle="modal" data-bs-target="#formKategori">＋ Tambah Kategori</button>
</div><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><div class="sp-card">
    <div class="table-responsive">
        <table class="table sp-table">
            <thead>
                <tr>
                    <th>Nama kategori</th>
                    <th>Dipakai inventaris</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody><?php foreach ($rows as $r): ?><tr>
                        <td><strong><?= e($r['nama_kategori']) ?></strong></td>
                        <td><?= e($r['dipakai']) ?> inventaris</td>
                        <td><button class="btn btn-sm sp-btn-outline" data-bs-toggle="modal" data-bs-target="#edit<?= e($r['id_kategori']) ?>">Ubah</button>
                            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_kategori" value="<?= e($r['id_kategori']) ?>"><button class="btn btn-sm btn-outline-danger" <?= $r['dipakai'] ? 'disabled title="Masih dipakai oleh inventaris"' : 'data-confirm="Hapus kategori ini?"' ?> type="submit">Hapus</button></form>
                        </td>
                    </tr>
                    <div class="modal fade" id="edit<?= e($r['id_kategori']) ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content" style="background:var(--sp-surface);color:var(--sp-text)">
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id_kategori" value="<?= e($r['id_kategori']) ?>">
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5">Ubah kategori</h2><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body"><label class="form-label">Nama kategori</label><input class="form-control" name="nama_kategori" maxlength="50" value="<?= e($r['nama_kategori']) ?>" required></div>
                                    <div class="modal-footer"><button type="button" class="btn sp-btn-outline" data-bs-dismiss="modal">Batal</button><button class="btn sp-btn-primary">Simpan</button></div>
                                </form>
                            </div>
                        </div>
                    </div><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="formKategori" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--sp-surface);color:var(--sp-text)">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save">
                <div class="modal-header">
                    <h2 class="modal-title fs-5">Tambah kategori</h2><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body"><label class="form-label">Nama kategori</label><input class="form-control" name="nama_kategori" maxlength="50" required></div>
                <div class="modal-footer"><button type="button" class="btn sp-btn-outline" data-bs-dismiss="modal">Batal</button><button class="btn sp-btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div><?php require __DIR__ . '/../includes/footer.php'; ?>