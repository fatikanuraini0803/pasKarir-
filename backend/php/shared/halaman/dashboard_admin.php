<?php
/** Dashboard panel admin dengan struktur layout frontend; semua angka diambil dari DB. */
require_once __DIR__ . '/../helpers.php';
if (!isset($area,$peran)) { http_response_code(404); exit; }
$user=require_role([$peran]); $pdo=db();
$totalLowongan=(int)$pdo->query('SELECT COUNT(*) FROM lowongan')->fetchColumn();
$lowonganAktif=(int)$pdo->query("SELECT COUNT(*) FROM lowongan WHERE status='Aktif'")->fetchColumn();
$totalUser=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='User'")->fetchColumn();
$totalKlas=(int)$pdo->query('SELECT COUNT(*) FROM riwayat_klasifikasi')->fetchColumn();
$populer=$pdo->query('SELECT l.id,l.nama,l.bidang,l.status,COUNT(r.id) jumlah FROM lowongan l LEFT JOIN riwayat_klasifikasi r ON r.lowongan_id=l.id GROUP BY l.id,l.nama,l.bidang,l.status ORDER BY l.created_at DESC,l.id DESC LIMIT 50')->fetchAll();
$pg=paginasi($populer);
$sesuaiAll=(int)$pdo->query("SELECT COUNT(*) FROM riwayat_klasifikasi WHERE label='SESUAI'")->fetchColumn(); $tidakAll=max(0,$totalKlas-$sesuaiAll);
$judul='Dashboard'; $halamanAktif=$area.'/index.php';
if ($peran==='Super Admin') {
 require_once __DIR__.'/../decision_tree.php';
 $jumlahAkun=(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
 $jumlahSub=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='Sub Admin'")->fetchColumn();
 $rows=dt_rows($pdo); $eval=dt_evaluate($rows);
$bulanNama=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; $bulanData=[];
 for($i=5;$i>=0;$i--){$ts=strtotime("first day of -$i month");$awal=date('Y-m-01',$ts);$akhir=date('Y-m-01',strtotime('+1 month',$ts));$st=$pdo->prepare('SELECT COUNT(*) FROM riwayat_klasifikasi WHERE created_at >= :a AND created_at < :b');$st->execute([':a'=>$awal,':b'=>$akhir]);$bulanData[]=['label'=>$bulanNama[(int)date('n',$ts)-1],'n'=>(int)$st->fetchColumn(),'tinggi'=>0];}
 $maks=max(1,max(array_column($bulanData,'n'))); foreach($bulanData as &$bd){$bd['tinggi']=$bd['n']>0?max(24,(int)round($bd['n']/$maks*275)):12;} unset($bd);
 require __DIR__.'/../header.php';
 ?>
<header class="page-header"><h1>Dashboard Super Admin</h1><p class="page-header__desc">Ringkasan seluruh sistem: akun, data pelatihan, dan performa model.</p></header>
<section class="stats" aria-label="Ringkasan sistem">
<article class="stat-card"><span class="stat-card__icon"><span class="icon icon--users icon--lg"></span></span><div><p class="stat-card__label">Total akun</p><p class="stat-card__value"><?=$jumlahAkun?></p></div></article>
<article class="stat-card"><span class="stat-card__icon"><span class="icon icon--shield icon--lg"></span></span><div><p class="stat-card__label">Sub Admin</p><p class="stat-card__value"><?=$jumlahSub?></p></div></article>
<article class="stat-card stat-card--primary"><span class="stat-card__icon"><span class="icon icon--database icon--lg"></span></span><div><p class="stat-card__label">Data pelatihan</p><p class="stat-card__value"><?=count($rows)?></p></div></article>
<article class="stat-card"><span class="stat-card__icon"><span class="icon icon--chart icon--lg"></span></span><div><p class="stat-card__label">Akurasi model</p><p class="stat-card__value"><?=number_format($eval['accuracy']*100,2,',','.')?>%</p></div></article>
</section>
<div class="layout-split layout-split--dashboard">
<section aria-labelledby="judul-grafik" class="card chart-card">
<div class="chart-card__header"><h2 id="judul-grafik">Klasifikasi per bulan</h2><p class="text-muted text-xs">Semua hasil</p></div>
<ol class="chart"><?php foreach ($bulanData as $i => $bd): ?><li class="chart__col<?= $i === count($bulanData) - 1 ? ' chart__col--active' : '' ?>"><span class="chart__bar" style="height:<?= (int) $bd['tinggi'] ?>px" title="<?= (int) $bd['n'] ?> klasifikasi"></span><span class="chart__label"><?= esc($bd['label']) ?></span></li><?php endforeach; ?></ol>
</section>
<section aria-labelledby="judul-status" class="card card--navy model-status">
<h2 class="card__title" id="judul-status"><span aria-hidden="true" class="icon icon--target"></span> Status model</h2>
<dl class="model-status__list">
<div><dt class="model-status__label">Data pelatihan</dt><dd class="model-status__value"><?= count($rows) ?> baris</dd></div>
<div><dt class="model-status__label">Metode</dt><dd class="model-status__value">ID3 | Decision Tree</dd></div>
<div><dt class="model-status__label">Akurasi data uji</dt><dd class="model-status__value"><?= number_format($eval['accuracy'] * 100, 2, ',', '.') ?>%</dd></div>
</dl>
<a class="btn btn--outline btn--block" href="<?= esc(url('super-admin/model-decision-tree.php')) ?>"><span aria-hidden="true" class="icon icon--arrow"></span> Buka Pengaturan Model</a>
</section>
</div>
<section aria-labelledby="judul-skala" class="card" style="margin-top:var(--space-5)"><h2 class="text-h3" id="judul-skala">Skala kesesuaian hasil klasifikasi</h2><p class="text-muted text-xs" style="margin-bottom:12px">Perbandingan persen hasil Sesuai dan Tidak Sesuai dari seluruh klasifikasi.</p><?= skala_html($sesuaiAll, $tidakAll) ?></section>
<section aria-labelledby="judul-lowongan" style="margin-top:var(--space-5)"><h2 class="section-title" id="judul-lowongan">Ringkasan lowongan</h2>
<div class="table-card"><table class="table"><caption class="visually-hidden">Ringkasan lowongan</caption><thead><tr><th scope="col">Nama lowongan</th><th scope="col">Bidang</th><th scope="col">Status</th><th scope="col">Klasifikasi</th></tr></thead><tbody>
<?php foreach ($pg['items'] as $l): ?><tr><td><?= esc($l['nama']) ?></td><td><?= esc($l['bidang']) ?></td><td><span class="badge <?= $l['status'] === 'Aktif' ? 'badge--success' : 'badge--warning' ?>"><?= esc($l['status']) ?></span></td><td><?= (int) $l['jumlah'] ?></td></tr><?php endforeach; ?>
<?php if (!$populer): ?><tr><td colspan="4" class="empty-state">Belum ada lowongan.</td></tr><?php endif; ?>
</tbody></table></div><?= paginasi_html($pg, 'lowongan') ?></section>
<?php
} else {
 require __DIR__.'/../header.php';
 ?>
<header class="page-header"><h1>Dashboard Sub Admin</h1><p class="page-header__desc">Pantau data lowongan dan aktivitas klasifikasi. Kelola cepat lewat tombol aksi di bawah.</p></header>
<section class="stats" aria-label="Ringkasan"><article class="stat-card"><span class="stat-card__icon"><span class="icon icon--briefcase icon--lg"></span></span><div><p class="stat-card__label">Total lowongan</p><p class="stat-card__value"><?=$totalLowongan?></p></div></article><article class="stat-card stat-card--primary"><span class="stat-card__icon"><span class="icon icon--document icon--lg"></span></span><div><p class="stat-card__label">Lowongan aktif</p><p class="stat-card__value"><?=$lowonganAktif?></p></div></article><article class="stat-card"><span class="stat-card__icon"><span class="icon icon--users icon--lg"></span></span><div><p class="stat-card__label">Pelamar terdaftar</p><p class="stat-card__value"><?=$totalUser?></p></div></article><article class="stat-card"><span class="stat-card__icon"><span class="icon icon--clock icon--lg"></span></span><div><p class="stat-card__label">Total klasifikasi</p><p class="stat-card__value"><?=$totalKlas?></p></div></article></section>
<section aria-labelledby="judul-skala" class="card" style="margin-top:var(--space-5)"><h2 class="text-h3" id="judul-skala">Skala kesesuaian hasil klasifikasi</h2><p class="text-muted text-xs" style="margin-bottom:12px">Perbandingan persen hasil Sesuai dan Tidak Sesuai dari seluruh klasifikasi.</p><?= skala_html($sesuaiAll, $tidakAll) ?></section>
<div class="layout-split layout-split--sub"><section><h2 class="section-title">Lowongan terbaru</h2><div class="table-card"><table class="table"><thead><tr><th>Nama lowongan</th><th>Bidang</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($pg['items'] as $l):?><tr><td><?=esc($l['nama'])?></td><td><?=esc($l['bidang'])?></td><td><span class="badge <?= $l['status']==='Aktif'?'badge--success':'badge--warning' ?>"><?=esc($l['status'])?></span></td><td><div class="actions"><button aria-label="Lihat lowongan <?=esc($l['nama'])?>" class="icon-btn js-detail" title="Lihat" type="button" data-judul="<?=esc($l['nama'])?>" data-sub="<?=esc($l['bidang'])?>" data-rows="<?=esc(json_encode([['Bidang',$l['bidang']],['Status',$l['status']],['Total klasifikasi',(string)(int)$l['jumlah']]],JSON_UNESCAPED_UNICODE))?>" data-ubah="<?=esc(url('sub-admin/lowongan_form.php?id='.(int)$l['id']))?>"><span aria-hidden="true" class="icon icon--eye"></span></button><a class="icon-btn" title="Ubah" href="<?=esc(url('sub-admin/lowongan_form.php?id='.(int)$l['id']))?>"><span class="icon icon--edit"></span></a></div></td></tr><?php endforeach;?></tbody></table></div><?=paginasi_html($pg,'lowongan')?></section><div class="stack"><section class="card"><h2 class="text-h3">Aksi cepat</h2><div class="stack"><a class="btn btn--primary btn--block" href="<?=esc(url('sub-admin/lowongan_form.php'))?>"><span class="icon icon--plus"></span> Tambah Lowongan</a><a class="btn btn--outline btn--block" href="<?=esc(url('sub-admin/panduan.php'))?>"><span aria-hidden="true" class="icon icon--document"></span> Tambah Panduan</a></div></section><section class="card card--muted"><h2 class="text-h3">Ringkasan aktivitas</h2><ul class="activity"><li class="activity__item"><p class="activity__title">Lowongan aktif</p><p class="activity__time"><?=$lowonganAktif?> dari <?=$totalLowongan?> lowongan</p></li><li class="activity__item"><p class="activity__title">Pelamar terdaftar</p><p class="activity__time"><?=$totalUser?> akun User</p></li><li class="activity__item"><p class="activity__title">Klasifikasi tersimpan</p><p class="activity__time"><?=$totalKlas?> hasil klasifikasi</p></li></ul></section></div></div>
<script src="<?=esc(url('../../backend/js/shared/detail-modal.js'))?>?v=1" defer></script>
<?php }
require __DIR__.'/../footer.php';
