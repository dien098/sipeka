<?php require_once __DIR__ . '/../includes/domain.php';
require_user_page();
post_only();
try {
    $id = require_int($_POST['id'] ?? null, 'ID laporan', 1);
    $pdo->beginTransaction();
    $s = $pdo->prepare("SELECT id_laporan,status FROM laporan WHERE id_laporan=? AND id_user=? AND deleted_at IS NULL FOR UPDATE");
    $s->execute([$id, current_user()['id_user']]);
    $r = $s->fetch();
    if (!$r) throw new RuntimeException('Laporan tidak ditemukan.');
    if ($r['status'] !== 'Menunggu Verifikasi') throw new RuntimeException('Hanya laporan Menunggu Verifikasi yang boleh dihapus.');
    $s = $pdo->prepare('UPDATE laporan SET deleted_at=NOW(),deleted_by=? WHERE id_laporan=?');
    $s->execute([current_user()['id_user'], $id]);
    $pdo->commit();
    flash('success', 'Laporan berhasil dihapus.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Laporan gagal dihapus.');
    error_log($e instanceof PDOException ? $e->getMessage() : '');
}
redirect(app_url('user/laporan-saya.php'));
