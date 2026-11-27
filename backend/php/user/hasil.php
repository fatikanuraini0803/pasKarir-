<?php
/**
 * hasil.php - Hasil satu klasifikasi (READ).
 * Kondisi "AND user_id = :uid" memastikan pengguna hanya bisa membuka riwayatnya sendiri.
 */
require_once __DIR__ . '/../shared/helpers.php';
require_once __DIR__ . '/../shared/data.php';
require_once __DIR__ . '/../shared/classifier.php';

$user = require_role(['User']);
$id   = get_int('id');

$riwayat = null;
if ($id !== null) {
    $stmt = db()->prepare('SELECT r.id, r.lowongan_id, r.keterampilan_pelamar, r.pendidikan, r.pengalaman,
                                  r.sertifikasi, r.skor, r.label, r.created_at, l.nama AS lowongan_nama, l.bidang AS lowongan_bidang
                           FROM riwayat_klasifikasi r
                           JOIN lowongan l ON l.id = r.lowongan_id
                           WHERE r.id = :id AND r.user_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $user['id']]);
    $riwayat = $stmt->fetch();
}
if (!$riwayat) {
    flash('error', 'Hasil klasifikasi tidak ditemukan.');
    redirect(url('user/riwayat.php'));
}

$pelamar = [
    'keterampilan' => array_values(array_filter(array_map('trim', explode(',', $riwayat['keterampilan_pelamar'])))),
    'pendidikan'   => $riwayat['pendidikan'],
    'pengalaman'   => (int) $riwayat['pengalaman'],
    'sertifikasi'  => $riwayat['sertifikasi'],
];
$lowongan = lowongan_by_id((int) $riwayat['lowongan_id']);
$alasan   = $lowongan ? klasifikasi($pelamar, $lowongan)['alasan'] : [];
$saran    = rekomendasi($pelamar, (int) $riwayat['lowongan_id']);

$judul = 'Hasil';
$halamanAktif = 'user/riwayat.php';
require __DIR__ . '/../shared/header.php';
?>
<div class="page-head"><h1>Hasil Klasifikasi</h1><p>Berikut hasil kesesuaianmu beserta alasan keputusan dan lowongan lain yang cocok.</p></div>
<?php $sesuai = $riwayat['label'] === 'SESUAI'; ?>
<section aria-labelledby="hasil-judul" class="result<?= $sesuai ? '' : ' result--no' ?>">
<div class="result__main"><p class="result__badge"><span aria-hidden="true" class="icon icon--<?= $sesuai ? 'check' : 'doc' ?>"></span> <?= esc($riwayat['label']) ?></p>
<div><h2 class="result__title" id="hasil-judul">Lowongan: <?= esc($riwayat['lowongan_nama']) ?></h2><p class="result__note">Bidang: <?= esc($riwayat['lowongan_bidang']) ?> · Dicek pada <?= esc(date('d/m/Y', strtotime($riwayat['created_at']))) ?></p></div></div>
<div class="result__score"><p class="result__note">Skor kecocokan</p><strong><?= (int) $riwayat['skor'] ?>%</strong></div>
</section>
<div class="split split--even">
<section aria-labelledby="alasan" class="card"><h2 class="card__title" id="alasan">Alasan keputusan</h2><p class="muted card__sub">Dari aturan Decision Tree:</p>
<ul class="item-list item-list--boxed">
<?php foreach ($alasan as $a): ?><li class="item"><span class="num num--lg"><span aria-hidden="true" class="icon icon--<?= $a['ok'] ? 'check' : 'doc' ?>"></span></span><?= esc($a['teks']) ?></li><?php endforeach; ?>
<?php if (!$alasan): ?><li class="item">Rincian alasan tidak tersedia karena lowongan sudah tidak ada.</li><?php endif; ?>
</ul></section>
<section aria-labelledby="rekom" class="card"><h2 class="card__title card__title--gap" id="rekom">Rekomendasi lowongan</h2>
<p class="muted card__sub">Dihitung Decision Tree dari keterampilanmu: <?= esc(implode(', ', $pelamar['keterampilan'])) ?></p>
<?php if (!$saran): ?><p class="muted">Belum ada lowongan lain yang membutuhkan keterampilan yang kamu pilih.</p><?php else: ?>
<ul class="item-list item-list--boxed"><?php foreach ($saran as $s): ?><li class="item"><span class="icon-tile"><span aria-hidden="true" class="icon icon--briefcase"></span></span><div class="item__body"><p class="item__title"><?= esc($s['nama']) ?> <span class="badge <?= $s['sesuai'] ? 'badge--success' : 'badge--danger' ?>"><?= $s['sesuai'] ? 'SESUAI' : 'TIDAK SESUAI' ?></span></p><p class="item__sub"><?= esc($s['bidang']) ?> · Cocok <?= (int) $s['skor'] ?>%</p><ul aria-label="Keterampilan cocok" class="chip-list chip-list--gap"><?php foreach ($s['cocok'] as $k): ?><li class="chip"><?= esc($k) ?></li><?php endforeach; ?></ul><?php if ($s['kurang']): ?><p class="item__sub">Belum dimiliki: <?= esc(implode(', ', $s['kurang'])) ?></p><?php endif; ?></div><a class="btn btn--outline btn--sm" href="<?= esc(url('user/lowongan.php#detail-' . (int) $s['id'])) ?>"><span aria-hidden="true" class="icon icon--eye"></span> Lihat</a><a class="btn btn--primary btn--sm" href="<?= esc(url('user/klasifikasi.php?lowongan_id=' . (int) $s['id'])) ?>"><span aria-hidden="true" class="icon icon--check"></span> Cek</a></li><?php endforeach; ?></ul>
<?php endif; ?></section>
</div>
<div class="result__actions"><a class="btn btn--primary btn--lg" href="<?= esc(url('user/klasifikasi.php')) ?>"><span aria-hidden="true" class="icon icon--check"></span> Klasifikasi Lagi</a><a class="btn btn--outline btn--lg" href="<?= esc(url('user/riwayat.php')) ?>"><span aria-hidden="true" class="icon icon--clock"></span> Lihat Riwayat</a></div>
<?php require __DIR__ . '/../shared/footer.php'; ?>
