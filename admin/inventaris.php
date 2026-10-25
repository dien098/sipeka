<?php require_once __DIR__ . '/../includes/domain.php';
require_admin_page();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_only();
    try {
        $action = $_POST['action'] ?? '';
        $id = filter_var($_POST['id_inventaris'] ?? null, FILTER_VALIDATE_INT);
        if ($action === 'save') {
            if ($id) {
                $baik = require_int($_POST['jumlah_baik'] ?? null, 'Jumlah baik');
                $rusak = require_int($_POST['jumlah_rusak'] ?? null, 'Jumlah rusak');
                $cat = normalize_text($_POST['catatan_kondisi'] ?? '');
                $aktif = isset($_POST['is_aktif']) ? 1 : 0;
                $pdo->beginTransaction();
                $s = $pdo->prepare('SELECT id_inventaris FROM inventaris_fasilitas WHERE id_inventaris=? FOR UPDATE');
                $s->execute([$id]);
                if (!$s->fetch()) throw new RuntimeException('Inventaris tidak ditemukan.');
                validate_manual_inventory((int)$id, $baik, $rusak);
                $s = $pdo->prepare('UPDATE inventaris_fasilitas SET jumlah_total=?,jumlah_baik=?,jumlah_rusak=?,catatan_kondisi=?,is_aktif=?,updated_by=? WHERE id_inventaris=?');
                $s->execute([$baik + $rusak, $baik, $rusak, $cat ?: null, $aktif, current_user()['id_user'], $id]);
                $pdo->commit();
                flash('success', 'Inventaris berhasil diperbarui.');
            } else {
                $nama = normalize_text($_POST['nama_fasilitas'] ?? '');
                $gedung = normalize_text($_POST['nama_gedung'] ?? '');
                $lantai = normalize_text($_POST['lantai'] ?? '');
                $ruang = normalize_text($_POST['ruang'] ?? '');
                $baik = require_int($_POST['jumlah_baik'] ?? null, 'Jumlah baik');
                $rusak = require_int($_POST['jumlah_rusak'] ?? null, 'Jumlah rusak');
                $cat = normalize_text($_POST['catatan_kondisi'] ?? '');
                if (!$nama || !$gedung || !$lantai || !$ruang) throw new RuntimeException('Nama fasilitas, gedung, lantai, dan ruang wajib diisi.');
                $pdo->beginTransaction();
                $s = $pdo->prepare('SELECT id_kategori FROM kategori_fasilitas WHERE nama_kategori=?');
                $s->execute([$nama]);
                $kid = $s->fetchColumn();
                if (!$kid) {
                    $s = $pdo->prepare('INSERT INTO kategori_fasilitas(nama_kategori) VALUES(?)');
                    $s->execute([$nama]);
                    $kid = $pdo->lastInsertId();
                }
                $s = $pdo->prepare('SELECT id_lokasi FROM lokasi WHERE nama_gedung=? AND lantai=? AND ruang=?');
                $s->execute([$gedung, $lantai, $ruang]);
                $lid = $s->fetchColumn();
                if (!$lid) {
                    $s = $pdo->prepare('INSERT INTO lokasi(nama_gedung,lantai,ruang) VALUES(?,?,?)');
                    $s->execute([$gedung, $lantai, $ruang]);
                    $lid = $pdo->lastInsertId();
                }
                $s = $pdo->prepare('SELECT id_inventaris FROM inventaris_fasilitas WHERE id_kategori=? AND id_lokasi=?');
                $s->execute([$kid, $lid]);
                if ($s->fetch()) throw new RuntimeException('Kombinasi fasilitas dan lokasi tersebut sudah ada di inventaris.');
                $s = $pdo->prepare('INSERT INTO inventaris_fasilitas(id_kategori,id_lokasi,jumlah_total,jumlah_baik,jumlah_rusak,catatan_kondisi,updated_by) VALUES(?,?,?,?,?,?,?)');
                $s->execute([$kid, $lid, $baik + $rusak, $baik, $rusak, $cat ?: null, current_user()['id_user']]);
                $pdo->commit();
                flash('success', 'Inventaris berhasil ditambahkan.');
            }
            redirect(app_url('admin/inventaris.php'));
        }
        if ($action === 'toggle' && $id) {
            $s = $pdo->prepare('UPDATE inventaris_fasilitas SET is_aktif=1-is_aktif,updated_by=? WHERE id_inventaris=?');
            $s->execute([current_user()['id_user'], $id]);
            flash('success', 'Status inventaris diperbarui.');
            redirect(app_url('admin/inventaris.php'));
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $err = $e instanceof RuntimeException ? $e->getMessage() : 'Data inventaris gagal disimpan. Pastikan nama fasilitas valid dan kombinasi belum ada.';
        error_log($e instanceof PDOException ? $e->getMessage() : '');
    }
}
$q = trim($_GET['q'] ?? '');
$ged = trim($_GET['gedung'] ?? '');
$kat = (int)($_GET['kategori'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(k.nama_kategori LIKE ? OR l.nama_gedung LIKE ? OR l.ruang LIKE ?)';
    array_push($params, '%' . $q . '%', '%' . $q . '%', '%' . $q . '%');
}
if ($ged !== '') {
    $where[] = 'l.nama_gedung=?';
    $params[] = $ged;
}
if ($kat) {
    $where[] = 'i.id_kategori=?';
    $params[] = $kat;
}
$w = implode(' AND ', $where);
$s = $pdo->prepare('SELECT COUNT(*) FROM inventaris_fasilitas i JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori JOIN lokasi l ON l.id_lokasi=i.id_lokasi WHERE ' . $w);
$s->execute($params);
$count = (int)$s->fetchColumn();
$s = $pdo->prepare('SELECT i.*,k.nama_kategori,l.nama_gedung,l.lantai,l.ruang FROM inventaris_fasilitas i JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori JOIN lokasi l ON l.id_lokasi=i.id_lokasi WHERE ' . $w . ' ORDER BY l.nama_gedung,l.lantai,l.ruang,k.nama_kategori LIMIT 10 OFFSET ' . (($page - 1) * 10));
$s->execute($params);
$rows = $s->fetchAll();
$cats = $pdo->query('SELECT * FROM kategori_fasilitas ORDER BY nama_kategori')->fetchAll();
$locs = $pdo->query('SELECT DISTINCT nama_gedung FROM lokasi ORDER BY nama_gedung')->fetchAll();
$page_title = 'Inventaris';
require __DIR__ . '/../includes/header.php'; ?><div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="sp-muted mb-0">Kelola fasilitas, jumlah, dan lokasi kampus.</p><button class="btn sp-btn-primary" data-bs-toggle="collapse" data-bs-target="#formTambah">＋ Tambah Inventaris</button>
</div><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><div class="collapse mb-4" id="formTambah">
    <form method="post" class="sp-card"><?= csrf_field() ?><input type="hidden" name="action" value="save">
        <h2>Fasilitas dan Lokasi</h2>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="nama_fasilitas">Kategori / nama fasilitas</label><input class="form-control" list="kategoriList" id="nama_fasilitas" name="nama_fasilitas" placeholder="Contoh: Kursi, Meja, AC" required>
                <div class="form-text">Pilih kategori yang tersedia atau ketik kategori baru. Kategori baru akan disimpan otomatis.</div><datalist id="kategoriList"><?php foreach ($cats as $c): ?><option value="<?= e($c['nama_kategori']) ?>"><?php endforeach; ?></datalist>
            </div>
            <div class="col-md-6"><label class="form-label" for="nama_gedung">Gedung</label><input class="form-control" name="nama_gedung" id="nama_gedung" required maxlength="100"></div>
            <div class="col-md-4"><label class="form-label" for="lantai">Lantai</label><input class="form-control" name="lantai" id="lantai" required maxlength="20"></div>
            <div class="col-md-4"><label class="form-label" for="ruang">Ruang</label><input class="form-control" name="ruang" id="ruang" required maxlength="50"></div>
            <div class="col-12">
                <h2 class="mt-3">Jumlah dan Kondisi</h2>
            </div>
            <div class="col-md-4"><label class="form-label" for="jumlah_baik">Jumlah baik</label><input class="form-control" type="number" min="0" name="jumlah_baik" id="jumlah_baik" value="0" required></div>
            <div class="col-md-4"><label class="form-label" for="jumlah_rusak">Jumlah rusak</label><input class="form-control" type="number" min="0" name="jumlah_rusak" id="jumlah_rusak" value="0" required></div>
            <div class="col-md-4"><label class="form-label" for="jumlah_total">Jumlah total (otomatis)</label><input class="form-control" id="jumlah_total" value="0" readonly></div>
            <div class="col-12"><label class="form-label" for="catatan_kondisi">Catatan kondisi (opsional)</label><input class="form-control" name="catatan_kondisi" id="catatan_kondisi" maxlength="255"></div>
        </div>
        <div class="mt-4"><button class="btn sp-btn-primary">Simpan Inventaris →</button></div>
    </form>
</div>
<form class="sp-card mb-4" method="get">
    <div class="row g-2">
        <div class="col-md-5"><label class="form-label" for="q">Cari inventaris</label><input class="form-control" name="q" id="q" value="<?= e($q) ?>" placeholder="Nama fasilitas, gedung, ruang"></div>
        <div class="col-md-3"><label class="form-label" for="gedung">Gedung</label><select class="form-select" name="gedung" id="gedung">
                <option value="0">Semua gedung</option><?php foreach ($locs as $l): ?><option value="<?= e($l['nama_gedung']) ?>" <?= ($ged === $l['nama_gedung']) ? 'selected' : '' ?>><?= e($l['nama_gedung']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label" for="kategori">Kategori</label><select class="form-select" name="kategori" id="kategori">
                <option value="0">Semua kategori</option><?php foreach ($cats as $c): ?><option value="<?= e($c['id_kategori']) ?>" <?= $kat === (int)$c['id_kategori'] ? 'selected' : '' ?>><?= e($c['nama_kategori']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-1 d-flex align-items-end"><button class="btn sp-btn-outline w-100">Cari</button></div>
    </div>
</form>
<div class="sp-card"><?php if (!$rows): render_empty('Inventaris tidak ditemukan', 'Tambahkan fasilitas atau ubah filter pencarian.', 'admin/inventaris.php', 'Muat ulang');
                        else: ?><div class="table-responsive">
            <table class="table sp-table">
                <thead>
                    <tr>
                        <th>Fasilitas</th>
                        <th>Lokasi</th>
                        <th>Total</th>
                        <th>Baik</th>
                        <th>Rusak</th>
                        <th>Kondisi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $r): $pct = $r['jumlah_total'] ? round($r['jumlah_baik'] / $r['jumlah_total'] * 100) : 0; ?><tr>
                            <td><strong><?= e($r['nama_kategori']) ?></strong>
                                <div class="sp-muted small"><?= e($r['catatan_kondisi']) ?></div>
                            </td>
                            <td><?= e($r['nama_gedung']) ?> · Lt <?= e($r['lantai']) ?> · <?= e($r['ruang']) ?></td>
                            <td><?= e($r['jumlah_total']) ?></td>
                            <td><?= e($r['jumlah_baik']) ?></td>
                            <td><?= e($r['jumlah_rusak']) ?></td>
                            <td>
                                <div class="condition-bar"><span style="width:<?= $pct ?>%"></span></div><small class="sp-muted"><?= $pct ?>% baik</small>
                            </td>
                            <td><span class="sp-badge <?= $r['is_aktif'] ? 'status-done' : 'status-rejected' ?>"><?= $r['is_aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td><button class="btn btn-sm sp-btn-outline" data-bs-toggle="modal" data-bs-target="#edit<?= e($r['id_inventaris']) ?>">Ubah</button>
                                <form method="post" class="mt-1"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id_inventaris" value="<?= e($r['id_inventaris']) ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="<?= $r['is_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?> inventaris ini?" type="submit"><?= $r['is_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                            </td>
                        </tr>
                        <div class="modal fade" id="edit<?= e($r['id_inventaris']) ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content" style="background:var(--sp-surface);color:var(--sp-text)">
                                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id_inventaris" value="<?= e($r['id_inventaris']) ?>">
                                        <div class="modal-header">
                                            <h2 class="modal-title fs-5">Ubah <?= e($r['nama_kategori']) ?></h2><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="sp-muted small"><?= e($r['nama_gedung']) ?> · Lt <?= e($r['lantai']) ?> · <?= e($r['ruang']) ?></p>
                                            <div class="mb-3"><label class="form-label">Jumlah baik</label><input class="form-control" type="number" min="0" name="jumlah_baik" value="<?= e($r['jumlah_baik']) ?>" required></div>
                                            <div class="mb-3"><label class="form-label">Jumlah rusak</label><input class="form-control" type="number" min="0" name="jumlah_rusak" value="<?= e($r['jumlah_rusak']) ?>" required></div>
                                            <div class="mb-3"><label class="form-label">Catatan kondisi</label><input class="form-control" name="catatan_kondisi" value="<?= e($r['catatan_kondisi']) ?>"></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_aktif" id="aktif<?= e($r['id_inventaris']) ?>" <?= $r['is_aktif'] ? 'checked' : '' ?>><label class="form-check-label" for="aktif<?= e($r['id_inventaris']) ?>">Inventaris aktif</label></div>
                                        </div>
                                        <div class="modal-footer"><button class="btn sp-btn-outline" data-bs-dismiss="modal" type="button">Batal</button><button class="btn sp-btn-primary">Simpan</button></div>
                                    </form>
                                </div>
                            </div>
                        </div><?php endforeach; ?>
                </tbody>
            </table>
        </div><?php if ($count > 10): ?><div class="d-flex justify-content-between"><span class="sp-muted small">Halaman <?= $page ?> dari <?= ceil($count / 10) ?></span><?php if ($page > 1): ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">← Sebelumnya</a><?php endif;
                                                                                                                                                                                                                                                                            if ($page * 10 < $count): ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Berikutnya →</a><?php endif; ?></div><?php endif;
                                                                                                                                                                                                                                                                                                                                                                                                                    endif; ?></div><?php require __DIR__ . '/../includes/footer.php'; ?>