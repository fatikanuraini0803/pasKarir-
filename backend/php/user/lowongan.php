<?php
/**
 * lowongan.php - Daftar lowongan aktif untuk User (READ dengan pencarian dan filter bidang).
 */
require_once __DIR__ . '/../shared/helpers.php';
require_once __DIR__ . '/../shared/data.php';

$user   = require_role(['User']);
$q      = get_str('q');
$bidang = get_str('bidang');

$daftar     = fetch_lowongan(['hanya_aktif' => true, 'q' => $q, 'bidang' => $bidang]);
$semuaBidang = daftar_bidang();

$judul = 'Lowongan';
$halamanAktif = 'user/lowongan.php';
require __DIR__ . '/../shared/header.php';
?>
<div class="page-head"><h1>Daftar Lowongan</h1><p>Pilih lowongan untuk melihat syaratnya, lalu klik "Cek Kesesuaian".</p></div>
<form action="<?=esc(url('user/lowongan.php'))?>" class="toolbar" id="filter-lowongan" method="get" role="search">
<span class="input-wrap toolbar__grow"><label class="visually-hidden" for="cari">Cari lowongan</label><span aria-hidden="true" class="icon icon--search"></span><input class="input" id="cari" name="q" placeholder="Cari lowongan" type="search" value="<?=esc($q)?>"></span>
<label class="visually-hidden" for="bidang">Bidang</label><select class="input input--auto" id="bidang" name="bidang"><option value="">Semua bidang</option><?php foreach($semuaBidang as $b):?><option value="<?=esc($b)?>" <?=$b===$bidang?'selected':''?>><?=esc($b)?></option><?php endforeach;?></select>
<button class="btn btn--primary btn--sm" type="submit"><span aria-hidden="true" class="icon icon--search"></span> Cari</button>
<p class="muted toolbar__count">Menampilkan <?=count($daftar)?> lowongan</p>
</form>
<section aria-label="Daftar lowongan" class="cards-grid">
<?php foreach($daftar as $l):?><article class="card job"><div><h2 class="card__title"><?=esc($l['nama'])?></h2><p class="job__field"><?=esc($l['bidang'])?></p></div><ul aria-label="Keterampilan" class="chip-list"><?php foreach(array_slice($l['keterampilan'],0,4) as $k):?><li class="chip"><?=esc($k)?></li><?php endforeach;?></ul><p class="job__meta">Min. <?=esc($l['pendidikan_min'])?> · Pengalaman <?= (int)$l['pengalaman_min']?> th</p><p class="job__meta"><?=esc($l['deskripsi'])?></p><div class="job__actions"><a class="btn btn--outline btn--sm" href="#detail-<?= (int)$l['id']?>"><span aria-hidden="true" class="icon icon--eye"></span> Detail</a><a class="btn btn--primary btn--sm" href="<?=esc(url('user/klasifikasi.php?lowongan_id='.(int)$l['id']))?>"><span aria-hidden="true" class="icon icon--check"></span> Cek Kesesuaian</a></div></article><?php endforeach;?>
<?php if(!$daftar):?><p class="muted">Tidak ada lowongan yang cocok.</p><?php endif;?></section>
<?php foreach($daftar as $l):?>
<div aria-labelledby="detail-<?= (int)$l['id']?>-judul" aria-modal="true" class="modal" id="detail-<?= (int)$l['id']?>" role="dialog">
<a aria-label="Tutup" class="modal__backdrop" href="#"></a>
<div class="modal__box modal__box--wide">
<a aria-label="Tutup" class="modal__close" href="#"><span aria-hidden="true" class="icon icon--close"></span></a>
<h2 class="modal__title modal__title--sm" id="detail-<?= (int)$l['id']?>-judul"><?=esc($l['nama'])?></h2><p class="job__field"><?=esc($l['bidang'])?></p>
<h3 class="modal__sub modal__sub--gap">Syarat keterampilan</h3><ul class="chip-list chip-list--gap"><?php foreach($l['keterampilan'] as $k):?><li class="chip"><?=esc($k)?></li><?php endforeach;?><?php if(!$l['keterampilan']):?><li class="muted">Tidak ada syarat keterampilan khusus.</li><?php endif;?></ul>
<dl class="spec"><div><dt>Pendidikan minimal</dt><dd><?=esc($l['pendidikan_min'])?></dd></div><div><dt>Pengalaman minimal</dt><dd><?=(int)$l['pengalaman_min']?> tahun</dd></div><div><dt>Status</dt><dd><?=esc($l['status'])?></dd></div></dl>
<h3 class="modal__sub">Deskripsi</h3><p class="muted"><?=esc($l['deskripsi'])?></p>
<div class="modal__actions"><a class="btn btn--primary" href="<?=esc(url('user/klasifikasi.php?lowongan_id='.(int)$l['id']))?>"><span aria-hidden="true" class="icon icon--check"></span> Cek Kesesuaian</a><a class="btn btn--outline" href="#"><span aria-hidden="true" class="icon icon--close"></span> Tutup</a></div>
</div>
</div>
<?php endforeach;?>
<script src="<?=esc(url('../../backend/js/user/lowongan-filter.js'))?>"></script>
<?php require __DIR__ . '/../shared/footer.php'; ?>
