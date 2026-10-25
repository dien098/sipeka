<?php require_once __DIR__ . '/../includes/domain.php';
require_user_page();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$r = $id ? get_laporan((int)$id) : false;
if (!$r || (int)$r['id_user'] !== current_user()['id_user']) {
    http_response_code(404);
    exit('Laporan tidak ditemukan.');
}
$history = get_riwayat((int)$id);
$page_title = 'Detail Laporan #' . $id;
require __DIR__ . '/../includes/header.php'; ?><div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div><a class="sp-muted text-decoration-none small" href="<?= e(app_url('user/laporan-saya.php')) ?>">← Kembali ke Laporan Saya</a>
        <h2 class="h4 fw-bold mt-2">Laporan #<?= e($id) ?></h2>
    </div>
    <div><?= badge_status($r['status']) ?></div>
</div>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="sp-card mb-4">
            <h2>Foto kerusakan</h2><?php if ($r['foto']): ?><img class="sp-photo mt-3" src="<?= e(app_url('uploads/laporan/' . $r['foto'])) ?>" alt="Foto kerusakan laporan #<?= e($id) ?>"><?php endif; ?><h2 class="mt-4">Deskripsi</h2>
            <p><?= nl2br(e($r['deskripsi'])) ?></p>
            <hr class="border-secondary">
            <h2>Informasi fasilitas</h2>
            <div class="row g-3 mt-1">
                <div class="col-sm-6">
                    <div class="sp-muted small">Fasilitas</div><strong><?= e($r['nama_kategori']) ?></strong>
                </div>
                <div class="col-sm-6">
                    <div class="sp-muted small">Jumlah unit</div><strong><?= e($r['jumlah_unit']) ?> unit</strong>
                </div>
                <div class="col-12">
                    <div class="sp-muted small">Lokasi</div><strong><?= e($r['nama_gedung']) ?> · Lantai <?= e($r['lantai']) ?> · Ruang <?= e($r['ruang']) ?></strong>
                </div>
                <div class="col-12">
                    <div class="sp-muted small">Tanggal laporan</div><strong><?= e(format_date($r['tanggal_laporan'])) ?></strong>
                </div>
            </div><?php if ($r['alasan_penolakan']): ?><div class="alert alert-danger mt-4 mb-0"><strong>Alasan penolakan</strong>
                    <p class="mb-0"><?= nl2br(e($r['alasan_penolakan'])) ?></p>
                </div><?php endif; ?>
        </div><?php if ($r['status'] === 'Menunggu Verifikasi'): ?><div class="sp-card">
                <h2>Aksi laporan</h2>
                <p class="sp-muted">Laporan dapat diubah atau dihapus selama masih menunggu verifikasi.</p><a class="btn sp-btn-outline" href="<?= e(app_url('user/edit-laporan.php?id=' . $id)) ?>">Ubah Laporan</a>
                <form class="d-inline" method="post" action="<?= e(app_url('user/hapus-laporan.php')) ?>" onsubmit="return confirm('Hapus laporan ini? Laporan akan disembunyikan dan tidak dapat dipulihkan dari aplikasi.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><button class="btn btn-outline-danger ms-2" type="submit">Hapus Laporan</button></form>
            </div><?php endif; ?>
    </div>
    <div class="col-lg-5">
        <div class="sp-card mb-4">
            <h2>Perjalanan status</h2>
            <div class="mt-4"><?php status_stepper($r['status']); ?></div>
        </div>
        <div class="sp-card">
            <h2>Riwayat status</h2><?php if (!$history): ?><p class="sp-muted">Belum ada riwayat.</p><?php else: ?><div class="sp-timeline mt-4"><?php foreach ($history as $h): ?><div class="sp-timeline-item"><strong><?= e($h['status_lama'] ?? 'Laporan dibuat') ?> → <?= e($h['status_baru']) ?></strong>
                            <div class="sp-muted small"><?= e($h['pengubah']) ?> · <?= e(format_date($h['waktu_perubahan'])) ?></div><?php if ($h['catatan']): ?><p class="small mt-1 mb-0"><?= e($h['catatan']) ?></p><?php endif; ?>
                        </div><?php endforeach; ?></div><?php endif; ?>
        </div>
    </div>
</div><?php require __DIR__ . '/../includes/footer.php'; ?>