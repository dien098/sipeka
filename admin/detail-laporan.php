<?php require_once __DIR__ . '/../includes/domain.php';
require_admin_page();
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
$r = $id ? get_laporan((int)$id) : false;
if (!$r) {
    http_response_code(404);
    exit('Laporan tidak ditemukan.');
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_only();
    try {
        $action = $_POST['action'] ?? '';
        $note = normalize_text($_POST['catatan'] ?? '');
        $reason = trim($_POST['alasan_penolakan'] ?? '');
        $pdo->beginTransaction();
        $s = $pdo->prepare('SELECT * FROM laporan WHERE id_laporan=? AND deleted_at IS NULL FOR UPDATE');
        $s->execute([$id]);
        $l = $s->fetch();
        if (!$l) throw new RuntimeException('Laporan tidak ditemukan.');
        $old = $l['status'];
        $uid = current_user()['id_user'];
        if ($action === 'verifikasi') {
            $allowed = $old === 'Menunggu Verifikasi';
            $new = 'Terverifikasi';
        } elseif ($action === 'tolak') {
            $allowed = $old === 'Menunggu Verifikasi';
            $new = 'Ditolak';
            if (trim($reason) === '') throw new RuntimeException('Alasan penolakan wajib diisi.');
        } elseif ($action === 'tangani') {
            $allowed = $old === 'Terverifikasi';
            $new = 'Sedang Ditangani';
        } elseif ($action === 'selesai') {
            $allowed = $old === 'Sedang Ditangani';
            $new = 'Selesai';
        } else throw new RuntimeException('Aksi status tidak dikenal.');
        if (!$allowed) throw new RuntimeException('Transisi status tidak diizinkan. Muat ulang halaman.');
        if (in_array($action, ['verifikasi', 'selesai'], true)) {
            $s = $pdo->prepare('SELECT * FROM inventaris_fasilitas WHERE id_inventaris=? FOR UPDATE');
            $s->execute([$l['id_inventaris']]);
            $inv = $s->fetch();
            if (!$inv) throw new RuntimeException('Inventaris tidak ditemukan.');
            if ($action === 'verifikasi') {
                if ($l['jumlah_unit'] < 1 || $l['jumlah_unit'] > $inv['jumlah_baik']) throw new RuntimeException('Jumlah unit melebihi jumlah baik inventaris saat ini (' . $inv['jumlah_baik'] . ').');
                $baik = $inv['jumlah_baik'] - $l['jumlah_unit'];
                $rusak = $inv['jumlah_rusak'] + $l['jumlah_unit'];
                $applied = 1;
            } else {
                if ((int)$l['kondisi_diterapkan'] === 1) {
                    if ($inv['jumlah_rusak'] < $l['jumlah_unit']) throw new RuntimeException('Jumlah rusak inventaris tidak cukup untuk pemulihan.');
                    $baik = $inv['jumlah_baik'] + $l['jumlah_unit'];
                    $rusak = $inv['jumlah_rusak'] - $l['jumlah_unit'];
                } else {
                    $baik = $inv['jumlah_baik'];
                    $rusak = $inv['jumlah_rusak'];
                }
                $applied = 0;
            }
            $s = $pdo->prepare('UPDATE inventaris_fasilitas SET jumlah_baik=?,jumlah_rusak=?,jumlah_total=?,updated_by=? WHERE id_inventaris=?');
            $s->execute([$baik, $rusak, $baik + $rusak, $uid, $l['id_inventaris']]);
        } else {
            $applied = (int)$l['kondisi_diterapkan'];
        }
        $reject = $action === 'tolak' ? $reason : $l['alasan_penolakan'];
        $noteForHistory = $action === 'tolak' ? $reason : $note;
        $s = $pdo->prepare('UPDATE laporan SET status=?,kondisi_diterapkan=?,alasan_penolakan=?,catatan_penanganan=? WHERE id_laporan=?');
        $s->execute([$new, $applied, $reject, $note ?: $l['catatan_penanganan'], $id]);
        add_history((int)$id, $uid, $old, $new, $noteForHistory ?: null);
        $pdo->commit();
        flash('success', 'Status laporan diperbarui menjadi ' . $new . '.');
        redirect(app_url('admin/detail-laporan.php?id=' . $id));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $err = $e instanceof RuntimeException ? $e->getMessage() : 'Perubahan gagal disimpan. Tidak ada perubahan yang diterapkan.';
        error_log($e instanceof PDOException ? $e->getMessage() : '');
    }
}
$r = get_laporan((int)$id);
$history = get_riwayat((int)$id);
$page_title = 'Detail Laporan #' . $id;
require __DIR__ . '/../includes/header.php'; ?><div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div><a class="sp-muted text-decoration-none small" href="<?= e(app_url('admin/laporan.php')) ?>">← Kembali ke Laporan</a>
        <h2 class="h4 fw-bold mt-2">Laporan #<?= e($id) ?></h2>
    </div><?= badge_status($r['status']) ?>
</div><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><div class="row g-4">
    <div class="col-lg-7">
        <div class="sp-card mb-4">
            <h2>Foto kerusakan</h2><?php if ($r['foto']): ?><img class="sp-photo mt-3" src="<?= e(app_url('uploads/laporan/' . $r['foto'])) ?>" alt="Foto laporan #<?= e($id) ?>"><?php endif; ?><h2 class="mt-4">Deskripsi</h2>
            <p><?= nl2br(e($r['deskripsi'])) ?></p>
            <hr>
            <h2>Informasi fasilitas</h2>
            <div class="row g-3 mt-1">
                <div class="col-sm-6">
                    <div class="sp-muted small">Fasilitas</div><strong><?= e($r['nama_kategori']) ?></strong>
                </div>
                <div class="col-sm-6">
                    <div class="sp-muted small">Jumlah unit</div><strong><?= e($r['jumlah_unit']) ?></strong>
                </div>
                <div class="col-12">
                    <div class="sp-muted small">Lokasi</div><strong><?= e($r['nama_gedung']) ?> · Lt <?= e($r['lantai']) ?> · <?= e($r['ruang']) ?></strong>
                </div>
                <div class="col-12">
                    <div class="sp-muted small">Pelapor</div><strong><?= e($r['pelapor']) ?></strong>
                </div>
            </div><?php if ($r['alasan_penolakan']): ?><div class="alert alert-danger mt-4"><strong>Alasan penolakan</strong>
                    <p class="mb-0"><?= nl2br(e($r['alasan_penolakan'])) ?></p>
                </div><?php endif; ?>
        </div><?php if ($r['catatan_penanganan']): ?><div class="sp-card">
                <h2>Catatan penanganan</h2>
                <p><?= nl2br(e($r['catatan_penanganan'])) ?></p>
            </div><?php endif; ?>
    </div>
    <div class="col-lg-5">
        <div class="sp-card mb-4">
            <h2>Perjalanan status</h2>
            <div class="mt-4"><?php status_stepper($r['status']); ?></div>
        </div><?php if (in_array($r['status'], ['Menunggu Verifikasi', 'Terverifikasi', 'Sedang Ditangani'], true)): ?><div class="sp-card mb-4">
                <h2>Tindakan Admin</h2>
                <p class="sp-muted small">Perubahan status dan penyesuaian inventaris diproses secara transaksional.</p><?php if ($r['status'] === 'Menunggu Verifikasi'): ?><form method="post" class="mb-3"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="action" value="verifikasi"><label class="form-label">Pratinjau dampak</label>
                        <div class="alert alert-info small">Jika diverifikasi: <?= e($r['nama_kategori']) ?> · <?= e($r['nama_gedung']) ?> Lt <?= e($r['lantai']) ?> · <?= e($r['ruang']) ?> — baik <?= e($r['jumlah_baik']) ?> → <?= e($r['jumlah_baik'] - $r['jumlah_unit']) ?>, rusak <?= e($r['jumlah_rusak']) ?> → <?= e($r['jumlah_rusak'] + $r['jumlah_unit']) ?>. Nilai akhir divalidasi ulang saat disimpan.</div><button class="btn sp-btn-primary w-100" data-confirm="Verifikasi laporan ini dan ubah kondisi inventaris?">Verifikasi</button>
                    </form>
                    <form method="post" class="border-top pt-3"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="action" value="tolak"><label class="form-label" for="alasan_penolakan">Alasan penolakan (wajib)</label><textarea class="form-control mb-3" name="alasan_penolakan" id="alasan_penolakan" rows="3" required></textarea><button class="btn btn-outline-danger w-100" data-confirm="Tolak laporan ini? Inventaris tidak akan berubah.">Tolak Laporan</button></form><?php elseif ($r['status'] === 'Terverifikasi'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="action" value="tangani"><label class="form-label">Catatan penanganan (opsional)</label><textarea class="form-control mb-3" name="catatan" rows="3"><?= e($r['catatan_penanganan']) ?></textarea><button class="btn sp-btn-primary w-100">Mulai Ditangani</button></form><?php elseif ($r['status'] === 'Sedang Ditangani'): ?><div class="alert alert-info small">Jika ditandai selesai dan kondisi sebelumnya diterapkan, <?= e($r['jumlah_unit']) ?> unit akan dipulihkan dari rusak ke baik.</div>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="action" value="selesai"><label class="form-label">Catatan penanganan (opsional)</label><textarea class="form-control mb-3" name="catatan" rows="3"><?= e($r['catatan_penanganan']) ?></textarea><button class="btn sp-btn-primary w-100" data-confirm="Tandai selesai? Inventaris akan dipulihkan jika kondisi diterapkan.">Tandai Selesai</button></form><?php endif; ?>
            </div><?php endif; ?><div class="sp-card">
            <h2>Riwayat status</h2><?php if (!$history): ?><p class="sp-muted">Belum ada riwayat.</p><?php else: ?><div class="sp-timeline mt-4"><?php foreach ($history as $h): ?><div class="sp-timeline-item"><strong><?= e($h['status_lama'] ?? 'Laporan dibuat') ?> → <?= e($h['status_baru']) ?></strong>
                            <div class="sp-muted small"><?= e($h['pengubah']) ?> · <?= e(format_date($h['waktu_perubahan'])) ?></div><?php if ($h['catatan']): ?><p class="small mt-1 mb-0"><?= e($h['catatan']) ?></p><?php endif; ?>
                        </div><?php endforeach; ?></div><?php endif; ?>
        </div>
    </div>
</div><?php require __DIR__ . '/../includes/footer.php'; ?>