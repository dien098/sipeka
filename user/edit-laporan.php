<?php require_once __DIR__ . '/../includes/domain.php';
require_user_page();
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
$uid = current_user()['id_user'];
$r = $id ? get_laporan((int)$id) : false;
if (!$r || (int)$r['id_user'] !== $uid || $r['status'] !== 'Menunggu Verifikasi') {
    http_response_code(403);
    exit('Laporan tidak tersedia untuk diubah.');
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $unit = require_int($_POST['jumlah_unit'] ?? null, 'Jumlah unit', 1);
        $desc = trim($_POST['deskripsi'] ?? '');
        if (mb_strlen($desc) < 5) throw new RuntimeException('Deskripsi minimal 5 karakter.');
        $s = $pdo->prepare('SELECT jumlah_baik FROM inventaris_fasilitas WHERE id_inventaris=?');
        $s->execute([$r['id_inventaris']]);
        $baik = (int)$s->fetchColumn();
        if ($unit > $baik) throw new RuntimeException('Jumlah unit tidak boleh lebih dari ' . $baik . ' unit yang berkondisi baik.');
        $foto = $r['foto'];
        $newPhoto = null;
        if (!empty($_FILES['foto']['name'])) {
            $newPhoto = upload_foto($_FILES['foto']);
            $foto = $newPhoto;
        }
        $pdo->beginTransaction();
        $s = $pdo->prepare("UPDATE laporan SET jumlah_unit=?,deskripsi=?,foto=?,kondisi_diterapkan=0 WHERE id_laporan=? AND id_user=? AND status='Menunggu Verifikasi' AND deleted_at IS NULL");
        $s->execute([$unit, $desc, $foto, $id, $uid]);
        $check = $pdo->prepare("SELECT status FROM laporan WHERE id_laporan=? AND id_user=? AND deleted_at IS NULL FOR UPDATE");
        $check->execute([$id, $uid]);
        $current = $check->fetchColumn();
        if ($current !== 'Menunggu Verifikasi') throw new RuntimeException('Laporan sudah berubah. Muat ulang halaman.');
        $pdo->commit();
        if ($newPhoto) safe_unlink_photo($r['foto']);
        flash('success', 'Laporan berhasil diperbarui.');
        redirect(app_url('user/detail-laporan.php?id=' . $id));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (isset($newPhoto) && $newPhoto) safe_unlink_photo($newPhoto);
        $err = $e instanceof RuntimeException ? $e->getMessage() : 'Laporan gagal diperbarui.';
        error_log($e instanceof PDOException ? $e->getMessage() : '');
    }
}
$page_title = 'Ubah Laporan #' . $id;
require __DIR__ . '/../includes/header.php'; ?><div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="sp-card">
            <p class="sp-muted">Fasilitas dan lokasi tidak dapat diubah setelah laporan dibuat.</p><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>">
                <div class="mb-3"><label class="form-label">Fasilitas</label><input class="form-control" value="<?= e($r['nama_kategori'] . ' · ' . $r['nama_gedung'] . ' · Lt ' . $r['lantai'] . ' · ' . $r['ruang']) ?>" disabled></div>
                <div class="mb-3"><label class="form-label" for="jumlah_unit">Jumlah unit</label><input class="form-control" type="number" min="1" max="<?= e($r['jumlah_baik']) ?>" name="jumlah_unit" id="jumlah_unit" value="<?= e($_POST['jumlah_unit'] ?? $r['jumlah_unit']) ?>" required></div>
                <div class="mb-3"><label class="form-label" for="deskripsi">Deskripsi</label><textarea class="form-control" name="deskripsi" id="deskripsi" rows="5" required><?= e($_POST['deskripsi'] ?? $r['deskripsi']) ?></textarea></div>
                <div class="mb-3"><label class="form-label" for="foto">Ganti foto (opsional, JPG/PNG maks. 2 MB)</label><input class="form-control" type="file" name="foto" id="foto" accept=".jpg,.jpeg,.png,image/jpeg,image/png"></div><button class="btn sp-btn-primary">Simpan Perubahan →</button> <a class="btn sp-btn-outline" href="<?= e(app_url('user/detail-laporan.php?id=' . $id)) ?>">Batal</a>
            </form>
        </div>
    </div>
</div><?php require __DIR__ . '/../includes/footer.php'; ?>