<?php require_once __DIR__ . '/../includes/domain.php';
require_admin_page();
post_only();
try {
    $id = require_int($_POST['id_lokasi'] ?? null, 'Lokasi', 1);
    $s = $pdo->prepare('SELECT COUNT(*) FROM inventaris_fasilitas WHERE id_lokasi=?');
    $s->execute([$id]);
    if ((int)$s->fetchColumn()) throw new RuntimeException('Lokasi masih dipakai oleh inventaris.');
    $s = $pdo->prepare('DELETE FROM lokasi WHERE id_lokasi=?');
    $s->execute([$id]);
    flash('success', 'Lokasi berhasil dihapus.');
} catch (Throwable $e) {
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Lokasi tidak dapat dihapus.');
}
redirect(app_url('admin/lokasi.php'));
