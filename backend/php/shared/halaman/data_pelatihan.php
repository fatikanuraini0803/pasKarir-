<?php
/**
 * data_pelatihan.php - Daftar dataset pelatihan (read-only, sumber klasifikasi Decision Tree).
 * Dipakai bersama oleh Super Admin dan Sub Admin lewat wrapper di folder role ($area dan $peran).
 */
require_once __DIR__.'/../helpers.php';
if (!isset($area, $peran)) { http_response_code(404); exit; }
$user=require_role([$peran]);
$q=get_str('q');
$sql='SELECT kode,usia,pendidikan,pengalaman_kerja,bidang_minat,skor_komputer,skor_komunikasi,skor_teknis,kebutuhan_pelatihan FROM dataset_pelatihan';
$params=[];
if($q!==''){$sql.=' WHERE kode LIKE :q OR bidang_minat LIKE :q OR kebutuhan_pelatihan LIKE :q';$params[':q']='%'.addcslashes($q,'%_\\').'%';}
$sql.=' ORDER BY kode';$st=db()->prepare($sql);$st->execute($params);$rows=$st->fetchAll();$pg=paginasi($rows);
$totalData=(int)db()->query('SELECT COUNT(*) FROM dataset_pelatihan')->fetchColumn();
$totalKelas=(int)db()->query('SELECT COUNT(DISTINCT kebutuhan_pelatihan) FROM dataset_pelatihan')->fetchColumn();
$totalBidang=(int)db()->query('SELECT COUNT(DISTINCT bidang_minat) FROM dataset_pelatihan')->fetchColumn();
$judul='Data Pelatihan';$halamanAktif=$area.'/data-pelatihan.php';
require __DIR__.'/../header.php';
?>
<header class="page-header"><h1>Data Pelatihan</h1><p class="page-header__desc">Dataset untuk melatih Decision Tree: data pelamar beserta label kebutuhan pelatihan. Halaman ini hanya menampilkan data tersimpan.</p></header>
<section aria-label="Ringkasan" class="stats">
<article class="stat-card stat-card--primary"><span class="stat-card__icon"><span aria-hidden="true" class="icon icon--database icon--lg"></span></span><div><p class="stat-card__label">Total data</p><p class="stat-card__value"><?= $totalData ?></p></div></article>
<article class="stat-card"><span class="stat-card__icon"><span aria-hidden="true" class="icon icon--check icon--lg"></span></span><div><p class="stat-card__label">Kelas kebutuhan</p><p class="stat-card__value"><?= $totalKelas ?></p></div></article>
<article class="stat-card"><span class="stat-card__icon"><span aria-hidden="true" class="icon icon--document icon--lg"></span></span><div><p class="stat-card__label">Bidang minat</p><p class="stat-card__value"><?= $totalBidang ?></p></div></article>
<article class="stat-card"><span class="stat-card__icon"><span aria-hidden="true" class="icon icon--clock icon--lg"></span></span><div><p class="stat-card__label">Ditampilkan</p><p class="stat-card__value"><?= count($rows) ?></p></div></article>
</section>
<form class="toolbar" method="get">
<div class="search"><label class="visually-hidden" for="cari">Cari data pelatihan</label><span aria-hidden="true" class="icon icon--search"></span><input class="search__input" id="cari" name="q" placeholder="Cari kode, bidang, atau kebutuhan" type="search" value="<?= esc($q) ?>"></div>
<div class="toolbar__end"><button class="btn btn--primary" type="submit"><span aria-hidden="true" class="icon icon--search"></span> Cari</button></div>
</form>
<div class="table-card"><table class="table"><caption class="visually-hidden">Data pelatihan model</caption><thead><tr><th scope="col">Kode</th><th scope="col">Usia</th><th scope="col">Pendidikan</th><th scope="col">Pengalaman</th><th scope="col">Bidang minat</th><th scope="col">Komputer</th><th scope="col">Komunikasi</th><th scope="col">Teknis</th><th scope="col">Kebutuhan pelatihan</th></tr></thead><tbody>
<?php foreach ($pg['items'] as $r): ?><tr><td><?= esc($r['kode']) ?></td><td><?= (int) $r['usia'] ?></td><td><?= esc($r['pendidikan']) ?></td><td><?= esc($r['pengalaman_kerja']) ?></td><td><?= esc($r['bidang_minat']) ?></td><td><?= (int) $r['skor_komputer'] ?></td><td><?= (int) $r['skor_komunikasi'] ?></td><td><?= (int) $r['skor_teknis'] ?></td><td><span class="badge badge--info"><?= esc($r['kebutuhan_pelatihan']) ?></span></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9" class="empty-state">Data tidak ditemukan.</td></tr><?php endif; ?>
</tbody></table></div>
<?= paginasi_html($pg, 'data') ?>
<?php require __DIR__ . '/../footer.php'; ?>
