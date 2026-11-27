<?php
require_once __DIR__ . '/../shared/helpers.php';
require_once __DIR__ . '/../shared/decision_tree.php';
$user=require_role(['User']);$rows=dt_rows(db());$eval=dt_evaluate($rows);
$in=['usia'=>'','pendidikan'=>'','pengalaman_kerja'=>'','bidang_minat'=>'','skor_komputer'=>'','skor_komunikasi'=>'','skor_teknis'=>''];$hasil=null;$errors=[];
if(is_post()){csrf_check();foreach($in as $k=>$_)$in[$k]=post_str($k);
 $usia=filter_var($in['usia'],FILTER_VALIDATE_INT,['options'=>['min_range'=>18,'max_range'=>33]]);if($usia===false)$errors['usia']='Usia harus 18–33 tahun sesuai rentang dataset.';
 foreach(['pendidikan','pengalaman_kerja','bidang_minat'] as $k)if($in[$k]==='')$errors[$k]='Pilih salah satu nilai.';
 foreach(['skor_komputer','skor_komunikasi','skor_teknis'] as $k){$v=filter_var($in[$k],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100]]);if($v===false)$errors[$k]='Skor harus 0–100.';}
 if(!$errors){$raw=['usia'=>$usia,'pendidikan'=>$in['pendidikan'],'pengalaman_kerja'=>$in['pengalaman_kerja'],'bidang_minat'=>$in['bidang_minat'],'skor_komputer'=>(int)$in['skor_komputer'],'skor_komunikasi'=>(int)$in['skor_komunikasi'],'skor_teknis'=>(int)$in['skor_teknis']];$hasil=dt_predict($eval['tree'],dt_features($raw));}
}
$opts=[];foreach($rows as $r){$opts['pendidikan'][$r['raw']['pendidikan']]=$r['raw']['pendidikan'];$opts['pengalaman_kerja'][$r['raw']['pengalaman_kerja']]=$r['raw']['pengalaman_kerja'];$opts['bidang_minat'][$r['raw']['bidang_minat']]=$r['raw']['bidang_minat'];}
$judul='Klasifikasi Kebutuhan Pelatihan';$halamanAktif='user/klasifikasi-pelatihan.php';require __DIR__.'/../shared/header.php';
?>
<div class="page-head"><h1>Klasifikasi Kebutuhan Pelatihan</h1><p>Isi profil dan skor untuk memperoleh rekomendasi kelas pelatihan berdasarkan Decision Tree yang dilatih dari dataset.</p></div>
<div class="split split--even">
<section aria-labelledby="form-pelatihan" class="card"><p class="form-step" id="form-pelatihan">Data profil dan skor</p>
<?php if ($errors): ?><div class="alert alert--error" role="alert">Periksa kembali isian yang belum benar.</div><?php endif; ?>
<form class="form" method="post" novalidate><?= csrf_field() ?>
<div class="form-row">
<div class="field"><label class="field__label" for="usia">Usia</label><input class="input input--pill" id="usia" name="usia" type="number" min="18" max="33" value="<?= esc($in['usia']) ?>"><?= err_html($errors, 'usia') ?></div>
<div class="field"><label class="field__label" for="pendidikan">Pendidikan</label><select class="input input--pill" id="pendidikan" name="pendidikan"><option value="">Pilih</option><?php foreach ($opts['pendidikan'] as $v): ?><option value="<?= esc($v) ?>" <?= $in['pendidikan'] === $v ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select><?= err_html($errors, 'pendidikan') ?></div>
</div>
<div class="form-row">
<div class="field"><label class="field__label" for="pengalaman_kerja">Pengalaman kerja</label><select class="input input--pill" id="pengalaman_kerja" name="pengalaman_kerja"><option value="">Pilih</option><?php foreach ($opts['pengalaman_kerja'] as $v): ?><option value="<?= esc($v) ?>" <?= $in['pengalaman_kerja'] === $v ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select><?= err_html($errors, 'pengalaman_kerja') ?></div>
<div class="field"><label class="field__label" for="bidang_minat">Bidang minat</label><select class="input input--pill" id="bidang_minat" name="bidang_minat"><option value="">Pilih</option><?php foreach ($opts['bidang_minat'] as $v): ?><option value="<?= esc($v) ?>" <?= $in['bidang_minat'] === $v ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select><?= err_html($errors, 'bidang_minat') ?></div>
</div>
<div class="form-row">
<?php foreach (['skor_komputer' => 'Skor komputer', 'skor_komunikasi' => 'Skor komunikasi', 'skor_teknis' => 'Skor teknis'] as $k => $label): ?>
<div class="field"><label class="field__label" for="<?= $k ?>"><?= $label ?> (0–100)</label><input class="input input--pill" id="<?= $k ?>" name="<?= $k ?>" type="number" min="0" max="100" value="<?= esc($in[$k]) ?>"><?= err_html($errors, $k) ?></div>
<?php endforeach; ?>
</div>
<div class="form__actions"><button class="btn btn--primary btn--lg" type="submit"><span aria-hidden="true" class="icon icon--check"></span> Prediksi</button><a class="btn btn--outline btn--lg" href="<?= esc(url('user/klasifikasi-pelatihan.php')) ?>"><span aria-hidden="true" class="icon icon--refresh"></span> Reset</a></div>
</form></section>
<aside aria-labelledby="hasil-pelatihan" class="card card--panel"><h2 class="card__title card__title--gap" id="hasil-pelatihan">Hasil klasifikasi</h2>
<?php if ($hasil !== null): ?>
<p class="item__sub">Rekomendasi kebutuhan pelatihan:</p><p class="stat__value" style="margin:8px 0 12px"><?= esc($hasil) ?></p>
<p class="item__sub">Hasil ini merupakan prediksi pola dataset dan bukan keputusan final. Diskusikan dengan pendamping/pengelola pelatihan.</p>
<?php else: ?><p class="item__sub">Hasil prediksi akan tampil di sini setelah formulir diproses.</p><?php endif; ?>
</aside>
</div>
<?php require __DIR__ . '/../shared/footer.php'; ?>
