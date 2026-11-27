<?php
/**
 * klasifikasi.php - Form klasifikasi (CREATE riwayat).
 * Alur: validasi di server -> hitung dengan klasifikasi() -> INSERT riwayat -> redirect ke hasil.
 */
require_once __DIR__ . '/../shared/helpers.php';
require_once __DIR__ . '/../shared/data.php';
require_once __DIR__ . '/../shared/classifier.php';

$user = require_role(['User']);
$daftarLowongan = fetch_lowongan(['hanya_aktif' => true]);
// Satukan penulisan bidang (spasi/huruf besar-kecil) supaya lowongan selalu masuk bidang yang benar
foreach ($daftarLowongan as &$__l) {
    $__l['bidang'] = rapikan_bidang($__l['bidang']);
}
unset($__l);
// Hanya bidang yang punya lowongan aktif yang ditampilkan
$daftarBidang = [];
foreach ($daftarLowongan as $__l) {
    $daftarBidang[kunci_bidang($__l['bidang'])] = $__l['bidang'];
}
$daftarBidang = array_values($daftarBidang);
sort($daftarBidang, SORT_NATURAL | SORT_FLAG_CASE);

$errors = [];
$in = ['bidang' => get_str('bidang'), 'lowongan_id' => get_str('lowongan_id'), 'keterampilan' => '', 'pendidikan' => '', 'pengalaman' => '', 'sertifikasi' => 'tidak'];

if (is_post()) {
    csrf_check();
    foreach ($in as $kunci => $_) {
        $in[$kunci] = post_str($kunci);
    }

    // 0. Jenis lowongan (bidang) harus salah satu bidang yang tersedia
    $in['bidang'] = rapikan_bidang($in['bidang']);
    $bidangValid = false;
    foreach ($daftarBidang as $__b) {
        if (kunci_bidang($__b) === kunci_bidang($in['bidang'])) {
            $in['bidang'] = $__b;
            $bidangValid = true;
        }
    }
    if (!$bidangValid) {
        $errors['bidang'] = 'Pilih jenis lowongan (bidang) dari daftar.';
    }

    // 1. Lowongan harus ada di daftar lowongan aktif (bukan sekadar angka dari form)
    $lowongan = null;
    $lid = filter_var($in['lowongan_id'], FILTER_VALIDATE_INT);
    if ($lid === false) {
        $errors['lowongan_id'] = 'Pilih lowongan dari daftar.';
    } else {
        foreach ($daftarLowongan as $l) {
            if ($l['id'] === $lid) {
                $lowongan = $l;
            }
        }
        if ($lowongan === null) {
            $errors['lowongan_id'] = 'Lowongan tidak ditemukan. Pilih dari daftar.';
        } elseif (!isset($errors['bidang']) && kunci_bidang($lowongan['bidang']) !== kunci_bidang($in['bidang'])) {
            $errors['lowongan_id'] = 'Lowongan tidak termasuk bidang yang dipilih.';
        }
    }

    // 2. Keterampilan
    list($skills, $errSkill) = parse_keterampilan($in['keterampilan'], 15);
    if ($errSkill !== null) {
        $errors['keterampilan'] = $errSkill;
    }

    // 3. Pendidikan harus salah satu nilai yang diizinkan
    if (!isset(TINGKAT_PENDIDIKAN[$in['pendidikan']])) {
        $errors['pendidikan'] = 'Pilih pendidikan terakhir.';
    }

    // 4. Pengalaman: bilangan bulat 0..50
    if ($in['pengalaman'] === '') {
        $errors['pengalaman'] = 'Lama pengalaman wajib diisi.';
    } else {
        $peng = filter_var($in['pengalaman'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => MAKS_PENGALAMAN]]);
        if ($peng === false) {
            $errors['pengalaman'] = 'Pengalaman harus bilangan bulat 0 sampai ' . MAKS_PENGALAMAN . ' tahun.';
        }
    }

    // 5. Sertifikasi
    if (!in_array($in['sertifikasi'], ['ya', 'tidak'], true)) {
        $errors['sertifikasi'] = 'Pilih ya atau tidak.';
    }

    if (!$errors) {
        $pelamar = [
            'keterampilan' => $skills,
            'pendidikan'   => $in['pendidikan'],
            'pengalaman'   => $peng,
            'sertifikasi'  => $in['sertifikasi'],
        ];
        $hasil = klasifikasi($pelamar, $lowongan);

        $stmt = db()->prepare('INSERT INTO riwayat_klasifikasi
              (user_id, lowongan_id, keterampilan_pelamar, pendidikan, pengalaman, sertifikasi, skor, label)
              VALUES (:uid, :lid, :skill, :pend, :peng, :sert, :skor, :label)');
        $stmt->execute([
            ':uid'   => $user['id'],
            ':lid'   => $lowongan['id'],
            ':skill' => implode(', ', $skills),
            ':pend'  => $pelamar['pendidikan'],
            ':peng'  => $peng,
            ':sert'  => $pelamar['sertifikasi'],
            ':skor'  => $hasil['skor'],
            ':label' => $hasil['label'],
        ]);
        redirect(url('user/hasil.php?id=' . (int) db()->lastInsertId()));
    }
}

// Datang dari tombol "Cek Kesesuaian" (hanya lowongan_id): isi bidang otomatis
if ($in['bidang'] === '' && $in['lowongan_id'] !== '') {
    foreach ($daftarLowongan as $l) {
        if ((string) $l['id'] === $in['lowongan_id']) {
            $in['bidang'] = $l['bidang'];
        }
    }
}
$opsiLowongan = array_values(array_filter($daftarLowongan, function ($l) use ($in) {
    return $in['bidang'] !== '' && kunci_bidang($l['bidang']) === kunci_bidang($in['bidang']);
}));
$dataJs = array_map(function ($l) {
    return ['id' => $l['id'], 'nama' => $l['nama'], 'bidang' => $l['bidang'], 'syarat' => $l['keterampilan'], 'pendidikan' => $l['pendidikan_min'], 'pengalaman' => $l['pengalaman_min']];
}, $daftarLowongan);

$judul = 'Klasifikasi';
$halamanAktif = 'user/klasifikasi.php';
require __DIR__ . '/../shared/header.php';
?>
<div class="page-head"><h1>Form Klasifikasi</h1><p>Isi data berikut dengan jujur. Semua kolom wajib diisi sebelum menekan "Proses".</p></div>
<div class="split split--even">
<section class="card"><p class="form-step">Langkah 1 dari 1 | Data kesesuaian</p>
<?php if ($errors): ?><div class="alert alert--error">Ada <?= count($errors) ?> isian yang belum benar.</div><?php endif; ?>
<form method="post" class="form" novalidate><?= csrf_field() ?>
<div class="field"><label class="field__label" for="bidang">Jenis lowongan (bidang)</label><select class="input input--pill" id="bidang" name="bidang"><option value="">Pilih jenis lowongan</option><?php foreach ($daftarBidang as $b): ?><option value="<?= esc($b) ?>" <?= kunci_bidang($b) === kunci_bidang($in['bidang']) ? 'selected' : '' ?>><?= esc($b) ?></option><?php endforeach; ?></select><?=err_html($errors,'bidang')?></div>
<div class="field"><label class="field__label" for="lowongan_id">Lowongan yang dituju</label><select class="input input--pill" id="lowongan_id" name="lowongan_id"><option value=""><?= $in['bidang'] === '' ? 'Pilih jenis lowongan dulu' : 'Pilih lowongan' ?></option><?php foreach ($opsiLowongan as $l): ?><option value="<?= (int)$l['id'] ?>" <?= (string)$l['id']===$in['lowongan_id']?'selected':'' ?>><?=esc($l['nama'])?></option><?php endforeach; ?></select><?=err_html($errors,'lowongan_id')?><p class="field__hint" id="hint-lowongan" aria-live="polite"></p></div>
<script id="data-lowongan" type="application/json"><?= json_encode($dataJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<fieldset class="field fieldset-reset"><legend class="field__label">Keterampilan utama</legend>
<div class="input input--tags input--pill" data-chips><?php foreach (array_filter(array_map('trim', explode(',', (string) $in['keterampilan']))) as $__k): ?><span class="chip"><?= esc($__k) ?> <button aria-label="Hapus <?= esc($__k) ?>" class="chip__remove" type="button">×</button></span><?php endforeach; ?><label class="visually-hidden" for="skill-baru">Tambah keterampilan</label><input class="tag-input" id="skill-baru" type="text" placeholder="+ tambah keterampilan (Enter)"></div>
<input type="hidden" name="keterampilan" id="keterampilan" value="<?=esc($in['keterampilan'])?>"><?=err_html($errors,'keterampilan')?></fieldset>
<div class="form-row"><div class="field"><label class="field__label" for="pendidikan">Tingkat pendidikan</label><select class="input input--pill" id="pendidikan" name="pendidikan"><option value="">Pilih</option><?php foreach(array_keys(TINGKAT_PENDIDIKAN) as $p):?><option value="<?=esc($p)?>" <?=$p===$in['pendidikan']?'selected':''?>><?=esc($p)?></option><?php endforeach;?></select><?=err_html($errors,'pendidikan')?></div><div class="field"><label class="field__label" for="pengalaman">Lama pengalaman kerja (tahun)</label><input class="input input--pill" type="number" id="pengalaman" name="pengalaman" min="0" max="<?=MAKS_PENGALAMAN?>" value="<?=esc($in['pengalaman'])?>"><?=err_html($errors,'pengalaman')?></div></div>
<fieldset class="field fieldset-reset"><legend class="field__label">Punya sertifikasi?</legend><div class="choice-group"><label class="choice"><input type="radio" name="sertifikasi" value="ya" <?=$in['sertifikasi']==='ya'?'checked':''?>> Ya</label><label class="choice"><input type="radio" name="sertifikasi" value="tidak" <?=$in['sertifikasi']==='tidak'?'checked':''?>> Tidak</label></div><?=err_html($errors,'sertifikasi')?></fieldset>
<div class="form__actions"><button type="submit" class="btn btn--primary btn--lg"><span class="icon icon--check"></span> Proses</button><a class="btn btn--outline btn--lg" href="<?=esc(url('user/klasifikasi.php'))?>"><span aria-hidden="true" class="icon icon--refresh"></span> Reset</a><span class="muted toolbar__count">Hasil tampil di halaman berikutnya</span></div></form></section>
<aside class="card card--panel"><h2 class="card__title card__title--gap">Petunjuk pengisian</h2><ol class="item-list"><li class="item item--step"><span class="num num--dark">1</span><div><p class="item__title">Pilih jenis dan lowongan</p><p class="item__sub">Pilih bidang dulu, lalu lowongan di bidang itu.</p></div></li><li class="item item--step"><span class="num num--dark">2</span><div><p class="item__title">Tambah keterampilan</p><p class="item__sub">Tuliskan keterampilan yang kamu kuasai.</p></div></li><li class="item item--step"><span class="num num--dark">3</span><div><p class="item__title">Isi pendidikan</p><p class="item__sub">Pendidikan terakhir dan lama pengalaman.</p></div></li><li class="item item--step"><span class="num num--dark">4</span><div><p class="item__title">Sertifikasi</p><p class="item__sub">Pilih Ya jika memiliki sertifikasi relevan.</p></div></li></ol></aside>
</div>
<script defer src="<?= esc(url('../../backend/js/user/klasifikasi-bidang.js')) ?>"></script><script defer src="<?= esc(url('../../backend/js/shared/chips.js')) ?>"></script><script defer src="<?= esc(url('../../backend/js/shared/chips-php.js')) ?>"></script>
<?php require __DIR__ . '/../shared/footer.php'; ?>
