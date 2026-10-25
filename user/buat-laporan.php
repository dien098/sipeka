<?php require_once __DIR__ . '/../includes/domain.php';
require_user_page();
$uid = current_user()['id_user'];
$err = '';
$duplicate = [];
$old = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $iid = require_int($_POST['id_inventaris'] ?? null, 'Fasilitas', 1);
        $unit = require_int($_POST['jumlah_unit'] ?? null, 'Jumlah unit', 1);
        $desc = trim($_POST['deskripsi'] ?? '');
        if (mb_strlen($desc) < 5 || mb_strlen($desc) > 5000) throw new RuntimeException('Deskripsi wajib diisi minimal 5 karakter.');
        $s = $pdo->prepare('SELECT i.*,k.nama_kategori,l.nama_gedung,l.lantai,l.ruang FROM inventaris_fasilitas i JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori JOIN lokasi l ON l.id_lokasi=i.id_lokasi WHERE i.id_inventaris=? AND i.is_aktif=1');
        $s->execute([$iid]);
        $inv = $s->fetch();
        if (!$inv) throw new RuntimeException('Fasilitas tidak tersedia. Pilih fasilitas aktif.');
        if ($unit > $inv['jumlah_baik']) throw new RuntimeException('Jumlah unit tidak boleh lebih dari ' . $inv['jumlah_baik'] . ' unit yang berkondisi baik.');
        $s = $pdo->prepare("SELECT id_laporan,status FROM laporan WHERE id_inventaris=? AND deleted_at IS NULL AND status IN ('Menunggu Verifikasi','Terverifikasi','Sedang Ditangani') ORDER BY tanggal_laporan DESC LIMIT 5");
        $s->execute([$iid]);
        $duplicate = $s->fetchAll();
        if ($duplicate && empty($_POST['konfirmasi_duplikat'])) throw new RuntimeException('Ada laporan aktif untuk fasilitas ini. Periksa peringatan dan centang konfirmasi jika tetap ingin mengirim.');
        $photo = upload_foto($_FILES['foto'] ?? []);
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("INSERT INTO laporan(id_user,id_inventaris,jumlah_unit,deskripsi,foto,status,kondisi_diterapkan) VALUES(?,?,?,?,?,'Menunggu Verifikasi',0)");
            $s->execute([$uid, $iid, $unit, $desc, $photo]);
            $id = (int)$pdo->lastInsertId();
            add_history($id, $uid, null, 'Menunggu Verifikasi', 'Laporan dibuat oleh pelapor');
            $pdo->commit();
            flash('success', 'Laporan berhasil dikirim. Status: Menunggu Verifikasi.');
            redirect(app_url('user/detail-laporan.php?id=' . $id));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            safe_unlink_photo($photo);
            throw $e;
        }
    } catch (Throwable $e) {
        $err = $e instanceof RuntimeException ? $e->getMessage() : 'Laporan gagal disimpan. Periksa data lalu coba lagi.';
        error_log($e instanceof PDOException ? $e->getMessage() : '');
        if (isset($iid)) {
            $s = $pdo->prepare("SELECT id_laporan,status FROM laporan WHERE id_inventaris=? AND deleted_at IS NULL AND status IN ('Menunggu Verifikasi','Terverifikasi','Sedang Ditangani') ORDER BY tanggal_laporan DESC LIMIT 5");
            $s->execute([$iid]);
            $duplicate = $s->fetchAll();
        }
    }
}
$loc = $pdo->query('SELECT DISTINCT l.id_lokasi,l.nama_gedung,l.lantai,l.ruang FROM lokasi l JOIN inventaris_fasilitas i ON i.id_lokasi=l.id_lokasi WHERE i.is_aktif=1 ORDER BY l.nama_gedung,l.lantai,l.ruang')->fetchAll();
$inv = $pdo->query('SELECT i.id_inventaris,i.jumlah_baik,k.nama_kategori,l.id_lokasi,l.nama_gedung,l.lantai,l.ruang FROM inventaris_fasilitas i JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori JOIN lokasi l ON l.id_lokasi=i.id_lokasi WHERE i.is_aktif=1 ORDER BY l.nama_gedung,l.lantai,l.ruang,k.nama_kategori')->fetchAll();
$page_title = 'Buat Laporan';
require __DIR__ . '/../includes/header.php'; ?><div class="row justify-content-center">
    <div class="col-xl-9">
        <p class="sp-muted mb-4">Isi informasi fasilitas yang rusak dan lampirkan foto untuk membantu proses verifikasi.</p><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><?php if (!$inv): render_empty('Belum ada fasilitas tercatat', 'Belum ada fasilitas aktif yang dapat dilaporkan.', 'user/dashboard.php', 'Kembali ke Dashboard');
                                                                                                                                                                                                                else: ?><form method="post" enctype="multipart/form-data" class="sp-card"><?= csrf_field() ?><div class="row g-3">
                    <div class="col-12">
                        <h2>Lokasi fasilitas</h2>
                    </div>
                    <div class="col-md-6"><label class="form-label" for="lokasi">Gedung · Lantai · Ruang</label><select class="form-select" id="lokasi" required>
                            <option value="">Pilih lokasi</option><?php foreach ($loc as $l): ?><option value="<?= e($l['id_lokasi']) ?>" <?= ((string)($old['lokasi'] ?? '') === (string)$l['id_lokasi']) ? 'selected' : '' ?>><?= e($l['nama_gedung']) ?> · Lt <?= e($l['lantai']) ?> · Ruang <?= e($l['ruang']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-6"><label class="form-label" for="id_inventaris">Fasilitas</label><select class="form-select" id="id_inventaris" name="id_inventaris" required>
                            <option value="">Pilih fasilitas</option><?php foreach ($inv as $i): ?><option data-lokasi="<?= e($i['id_lokasi']) ?>" value="<?= e($i['id_inventaris']) ?>" data-baik="<?= e($i['jumlah_baik']) ?>" <?= ((string)($old['id_inventaris'] ?? '') === (string)$i['id_inventaris']) ? 'selected' : '' ?>><?= e($i['nama_kategori']) ?> · <?= e($i['jumlah_baik']) ?> baik</option><?php endforeach; ?>
                        </select>
                        <div class="form-text sp-muted" id="fasilitas-help">Pilih lokasi terlebih dahulu.</div>
                    </div>
                    <div class="col-md-4"><label class="form-label" for="jumlah_unit">Jumlah unit rusak</label><input class="form-control" type="number" id="jumlah_unit" name="jumlah_unit" min="1" value="<?= e($old['jumlah_unit'] ?? 1) ?>" required></div>
                    <div class="col-12"><label class="form-label" for="deskripsi">Deskripsi kerusakan</label><textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" maxlength="5000" required placeholder="Jelaskan kondisi kerusakan dan lokasi detail..."><?= e($old['deskripsi'] ?? '') ?></textarea></div>
                    <div class="col-12"><label class="form-label" for="foto">Foto kerusakan (JPG/PNG, maksimal 2 MB)</label>
                        <div class="sp-upload"><input class="form-control" type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-preview-file="#preview-foto" required>
                            <div class="form-text sp-muted">Foto wajib dilampirkan.</div><img id="preview-foto" class="sp-photo mt-3 d-none" alt="Pratinjau foto" onload="this.classList.remove('d-none')">
                        </div>
                    </div>
                </div><?php if ($duplicate): ?><div class="alert alert-warning mt-4"><strong>Perhatian: laporan aktif ditemukan.</strong>
                        <p class="mb-2">Fasilitas ini sudah memiliki laporan yang belum selesai:</p><?php foreach ($duplicate as $d): ?><a class="d-block" href="<?= e(app_url('user/detail-laporan.php?id=' . $d['id_laporan'])) ?>">Laporan #<?= e($d['id_laporan']) ?> — <?= e($d['status']) ?></a><?php endforeach; ?><div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="konfirmasi_duplikat" name="konfirmasi_duplikat" value="1" <?= !empty($old['konfirmasi_duplikat']) ? 'checked' : '' ?>><label class="form-check-label" for="konfirmasi_duplikat">Tetap kirim laporan ini</label></div>
                    </div><?php endif; ?><div class="d-flex gap-2 justify-content-end mt-4"><a class="btn sp-btn-outline" href="<?= e(app_url('user/dashboard.php')) ?>">Batal</a><button class="btn sp-btn-primary" type="submit">Kirim Laporan →</button></div>
            </form><?php endif; ?>
    </div>
</div>
<script>
    const locSel = document.getElementById('lokasi'),
        facSel = document.getElementById('id_inventaris'),
        unit = document.getElementById('jumlah_unit'),
        help = document.getElementById('fasilitas-help');

    function filterFacilities() {
        if (!locSel || !facSel) return;
        let selected = facSel.value;
        let visible = 0;
        [...facSel.options].forEach(o => {
            if (!o.value) return;
            const show = o.dataset.lokasi === locSel.value;
            o.hidden = !show;
            o.disabled = !show;
            if (show) visible++;
        });
        if (![...facSel.options].some(o => o.value === selected && !o.disabled)) facSel.value = '';
        help.textContent = visible ? 'Pilih fasilitas pada lokasi tersebut.' : 'Belum ada fasilitas tercatat di ruang ini.';
        facSel.disabled = !locSel.value || visible === 0;
        updateMax()
    }

    function updateMax() {
        const o = facSel?.selectedOptions[0];
        if (o && o.dataset.baik) {
            unit.max = o.dataset.baik;
            if (parseInt(unit.value || 1) > parseInt(unit.max)) unit.value = unit.max;
        } else unit.removeAttribute('max')
    }
    locSel?.addEventListener('change', filterFacilities);
    facSel?.addEventListener('change', updateMax);
    filterFacilities();
</script><?php require __DIR__ . '/../includes/footer.php'; ?>