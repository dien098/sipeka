<?php require_once __DIR__ . '/../includes/domain.php';
require_admin_page();
$tot = $pdo->query('SELECT COALESCE(SUM(jumlah_total),0) total,COALESCE(SUM(jumlah_baik),0) baik,COALESCE(SUM(jumlah_rusak),0) rusak FROM inventaris_fasilitas WHERE is_aktif=1')->fetch();
$statuses = $pdo->query('SELECT status,COUNT(*) jumlah FROM laporan WHERE deleted_at IS NULL GROUP BY status')->fetchAll();
$by = [];
foreach ($statuses as $s) $by[$s['status']] = (int)$s['jumlah'];
$wait = $by['Menunggu Verifikasi'] ?? 0;
$months = [];
$start = new DateTime('first day of -5 months');
for ($i = 0; $i < 6; $i++) {
    $d = (clone $start)->modify("+{$i} months");
    $months[$d->format('Y-m')] = ['label' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int)$d->format('n') - 1], 'masuk' => 0, 'selesai' => 0];
}
$q = $pdo->query("SELECT DATE_FORMAT(tanggal_laporan,'%Y-%m') bulan,COUNT(*) jumlah FROM laporan WHERE deleted_at IS NULL AND tanggal_laporan>=DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'),INTERVAL 5 MONTH) GROUP BY bulan");
foreach ($q as $x) if (isset($months[$x['bulan']])) $months[$x['bulan']]['masuk'] = (int)$x['jumlah'];
$q = $pdo->query("SELECT DATE_FORMAT(rs.waktu_perubahan,'%Y-%m') bulan,COUNT(*) jumlah FROM riwayat_status rs JOIN laporan l ON l.id_laporan=rs.id_laporan WHERE rs.status_baru='Selesai' AND l.deleted_at IS NULL AND rs.waktu_perubahan>=DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'),INTERVAL 5 MONTH) GROUP BY bulan");
foreach ($q as $x) if (isset($months[$x['bulan']])) $months[$x['bulan']]['selesai'] = (int)$x['jumlah'];
$cats = $pdo->query('SELECT k.nama_kategori,SUM(i.jumlah_rusak) rusak FROM inventaris_fasilitas i JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori WHERE i.is_aktif=1 GROUP BY k.id_kategori,k.nama_kategori HAVING rusak>0 ORDER BY rusak DESC LIMIT 5')->fetchAll();
$todo = $pdo->query("SELECT l.id_laporan,k.nama_kategori,lo.nama_gedung,lo.lantai,lo.ruang,u.nama,l.tanggal_laporan FROM laporan l JOIN inventaris_fasilitas i ON i.id_inventaris=l.id_inventaris JOIN kategori_fasilitas k ON k.id_kategori=i.id_kategori JOIN lokasi lo ON lo.id_lokasi=i.id_lokasi JOIN users u ON u.id_user=l.id_user WHERE l.status='Menunggu Verifikasi' AND l.deleted_at IS NULL ORDER BY l.tanggal_laporan ASC LIMIT 5")->fetchAll();
$page_title = 'Dashboard';
require __DIR__ . '/../includes/header.php';
$pct = (int)$tot['total'] ? round($tot['baik'] / $tot['total'] * 100) : 0; ?><div class="row g-3 mb-4"><?php foreach ([['Total fasilitas', (int)$tot['total'], 'Unit tercatat', 'box'], ['Kondisi baik', $pct . '%', $tot['baik'] . ' unit baik', 'check'], ['Kondisi rusak', (int)$tot['rusak'], 'Unit perlu perhatian', 'file'], ['Perlu verifikasi', $wait, 'Laporan menunggu', 'clock']] as $idx => $x): ?><div class="col-6 col-xl-3">
            <div class="sp-card sp-stat <?= $idx === 3 ? 'border-warning' : '' ?>">
                <div class="sp-stat-label"><?= e($x[0]) ?></div>
                <div class="sp-stat-icon"><?= icon($x[3]) ?></div>
                <div class="sp-stat-value"><?= e($x[1]) ?></div>
                <div class="sp-stat-note"><?= e($x[2]) ?></div>
            </div>
        </div><?php endforeach; ?></div>
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="sp-card h-100">
            <h2>Laporan masuk dan selesai</h2>
            <p class="sp-muted small">Enam bulan terakhir</p><?php if (array_sum(array_column($months, 'masuk')) + array_sum(array_column($months, 'selesai')) === 0): ?><div class="sp-empty">
                    <div class="sp-empty-icon"><?= icon('chart') ?></div>
                    <h3>Belum ada data grafik</h3>
                    <p class="sp-muted">Grafik akan muncul setelah laporan tercatat.</p>
                </div><?php else: ?><div style="height:280px"><canvas data-chart="line" aria-label="Grafik laporan masuk dan selesai enam bulan terakhir" data-labels="<?= e(json_encode(array_column($months, 'label'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>" data-datasets="<?= e(json_encode([['label' => 'Masuk', 'data' => array_column($months, 'masuk')], ['label' => 'Selesai', 'data' => array_column($months, 'selesai')]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"></canvas></div><?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sp-card h-100">
            <h2>Kondisi fasilitas</h2>
            <p class="sp-muted small">Inventaris aktif</p><?php if (!(int)$tot['total']): ?><div class="sp-empty">
                    <p class="sp-muted">Belum ada inventaris aktif.</p>
                </div><?php else: ?><div style="height:220px;position:relative"><canvas data-chart="doughnut" aria-label="Kondisi fasilitas: <?= $tot['baik'] ?> baik dan <?= $tot['rusak'] ?> rusak" data-labels="<?= e(json_encode(['Baik', 'Rusak'])) ?>" data-datasets="<?= e(json_encode([['data' => [(int)$tot['baik'], (int)$tot['rusak']]]])) ?>"></canvas>
                    <div style="position:absolute;inset:0;display:grid;place-items:center;pointer-events:none;font-size:26px;font-weight:700"><?= $pct ?>%</div>
                </div>
                <div class="d-flex justify-content-between small mt-3"><span><span class="sp-accent">●</span> Baik: <?= e($tot['baik']) ?></span><span style="color:#FF8A7A">● Rusak: <?= e($tot['rusak']) ?></span></div><?php endif; ?>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="sp-card h-100">
            <h2>Laporan per status</h2><?php foreach (status_list() as $s): $n = $by[$s] ?? 0;
                                            $max = max(1, ...array_values($by ?: [1])); ?><div class="sp-bar-row"><span><?= e($s === 'Sedang Ditangani' ? 'Ditangani' : $s) ?></span>
                    <div class="sp-bar"><span style="width:<?= round($n / $max * 100) ?>%"></span></div><strong><?= $n ?></strong>
                </div><?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sp-card h-100">
            <h2>Kategori paling banyak rusak</h2><?php if (!$cats): ?><p class="sp-muted">Belum ada unit rusak pada inventaris aktif.</p><?php else: $max = max(array_map(fn($x) => (int)$x['rusak'], $cats));
                                                                                                                                            foreach ($cats as $c): ?><div class="sp-bar-row"><span><?= e($c['nama_kategori']) ?></span>
                        <div class="sp-bar"><span style="width:<?= round($c['rusak'] / $max * 100) ?>%"></span></div><strong><?= e($c['rusak']) ?></strong>
                    </div><?php endforeach;
                                                                                                                                        endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sp-card h-100">
            <div class="d-flex justify-content-between">
                <h2>Perlu tindakan</h2><span class="sp-badge status-wait"><?= $wait ?></span>
            </div><?php if (!$todo): ?><p class="sp-muted">Tidak ada laporan yang menunggu verifikasi.</p><?php else: foreach ($todo as $t): ?><div class="py-3 border-bottom" style="border-color:var(--sp-border)!important"><strong><?= e($t['nama_kategori']) ?></strong>
                        <div class="sp-muted small"><?= e($t['nama_gedung']) ?> · Lt <?= e($t['lantai']) ?> · <?= e($t['ruang']) ?></div>
                        <div class="sp-muted small">Pelapor: <?= e($t['nama']) ?></div><a class="small" href="<?= e(app_url('admin/detail-laporan.php?id=' . $t['id_laporan'])) ?>">Periksa →</a>
                    </div><?php endforeach;
                                                                                                        endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="<?= e(app_url('assets/js/charts.js')) ?>"></script><?php require __DIR__ . '/../includes/footer.php'; ?>