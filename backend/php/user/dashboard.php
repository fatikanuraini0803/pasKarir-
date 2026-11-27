<?php
/** Dashboard User dengan layout frontend dan data live dari MySQL. */
require_once __DIR__ . '/../shared/helpers.php';
$user = require_role(['User']);
$pdo = db();
$stmt = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(label = 'SESUAI'),0) AS sesuai FROM riwayat_klasifikasi WHERE user_id = :uid");
$stmt->execute([':uid'=>$user['id']]); $ringkas = $stmt->fetch();
$stmt = $pdo->prepare("SELECT r.id,l.nama AS lowongan,l.bidang,r.skor,r.label,r.created_at FROM riwayat_klasifikasi r JOIN lowongan l ON l.id=r.lowongan_id WHERE r.user_id=:uid ORDER BY r.created_at DESC,r.id DESC LIMIT 3");
$stmt->execute([':uid'=>$user['id']]); $terakhir = $stmt->fetchAll();
$lowonganAktif = (int)$pdo->query("SELECT COUNT(*) FROM lowongan WHERE status='Aktif'")->fetchColumn();
$hasilTerakhir = $terakhir[0]['label'] ?? 'Belum ada';
$judul='Dashboard'; $halamanAktif='user/dashboard.php';
require __DIR__.'/../shared/header.php';
?>
<div class="page-head"><h1>Dashboard</h1><p>Ringkasan aktivitas klasifikasimu. Mulai dari tombol "Mulai Klasifikasi".</p></div>
<section class="welcome"><h2>Halo, <?=esc($user['nama'])?></h2><p>Siap cek kesesuaianmu dengan lowongan? Isi data, dan hasilnya langsung muncul.</p><a class="btn btn--light" href="<?=esc(url('user/klasifikasi.php'))?>"><span class="icon icon--check"></span> Mulai Klasifikasi</a></section>
<section class="stats" aria-label="Ringkasan">
<article class="card stat"><span class="icon-tile"><span class="icon icon--chart"></span></span><div><p class="stat__label">Total klasifikasi</p><p class="stat__value"><?= (int)$ringkas['total']?></p></div></article>
<article class="card card--dark stat"><span class="icon-tile"><span class="icon icon--check"></span></span><div><p class="stat__label">Hasil terakhir</p><p class="stat__value"><?=esc(ucwords(strtolower($hasilTerakhir)))?></p></div></article>
<article class="card stat"><span class="icon-tile"><span class="icon icon--briefcase"></span></span><div><p class="stat__label">Lowongan aktif</p><p class="stat__value"><?=$lowonganAktif?></p></div></article>
</section>
<section aria-labelledby="skala-judul" class="card"><h2 class="card__title card__title--gap" id="skala-judul">Skala kesesuaian hasilmu</h2><?= skala_html((int)$ringkas['sesuai'], max(0,(int)$ringkas['total']-(int)$ringkas['sesuai'])) ?></section>
<div class="split">
<section class="card"><div class="card__head"><h2 class="card__title">Riwayat klasifikasi terbaru</h2><a href="<?=esc(url('user/riwayat.php'))?>">Lihat semua</a></div>
<?php if (!$terakhir): ?><p class="muted">Belum ada klasifikasi. Mulai cek kesesuaian untuk melihat rekomendasi lowongan.</p><?php else: ?><ul class="item-list"><?php foreach($terakhir as $r): ?><li class="item"><span class="icon-tile"><span class="icon icon--briefcase"></span></span><div class="item__body"><p class="item__title"><?=esc($r['lowongan'])?></p><p class="item__sub"><?=esc($r['bidang'])?> · <?=esc($r['created_at'])?></p></div><a class="badge badge--primary" href="<?=esc(url('user/hasil.php?id='.(int)$r['id']))?>"><?= (int)$r['skor']?>%</a></li><?php endforeach;?></ul><?php endif;?></section>
<section class="card card--panel"><h2 class="card__title card__title--gap">Panduan singkat</h2><ol class="item-list"><li class="item item--step"><span class="num">1</span><div><p class="item__title">Pilih lowongan</p><p class="item__sub">Tentukan lowongan yang dituju.</p></div></li><li class="item item--step"><span class="num">2</span><div><p class="item__title">Isi data dirimu</p><p class="item__sub">Keterampilan, pendidikan, pengalaman.</p></div></li><li class="item item--step"><span class="num">3</span><div><p class="item__title">Lihat hasilnya</p><p class="item__sub">Sesuai / Tidak beserta alasannya.</p></div></li></ol></section>
</div>
<?php require __DIR__.'/../shared/footer.php'; ?>
