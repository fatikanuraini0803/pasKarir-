<?php
require_once __DIR__ . '/../shared/helpers.php';
require_once __DIR__ . '/../shared/decision_tree.php';
$user=require_role(['Super Admin']);
$rows=dt_rows(db());$eval=dt_evaluate($rows);$counts=dt_counts($rows);
$gains=$eval['gains'];usort($gains,fn($a,$b)=>$b['gain']<=>$a['gain']);
$judul='Analisis Decision Tree';$halamanAktif='super-admin/model-decision-tree.php';
require __DIR__ . '/../shared/header.php';
?>
<header class="page-header"><h1>Pengaturan Model Decision Tree</h1><p class="page-header__desc">Model ID3 dari dataset yang tersimpan di tabel <code>dataset_pelatihan</code>. Kolom ID hanya identitas, bukan atribut prediksi.</p></header>
<section aria-label="Ringkasan model" class="stats">
<article class="stat-card"><span class="stat-card__icon"><span aria-hidden="true" class="icon icon--database icon--lg"></span></span><div><p class="stat-card__label">Total data</p><p class="stat-card__value"><?= count($rows) ?></p></div></article>
<article class="stat-card"><span class="stat-card__icon"><span aria-hidden="true" class="icon icon--document icon--lg"></span></span><div><p class="stat-card__label">Data latih (80%)</p><p class="stat-card__value"><?= count($eval['train']) ?></p></div></article>
<article class="stat-card"><span class="stat-card__icon"><span aria-hidden="true" class="icon icon--check icon--lg"></span></span><div><p class="stat-card__label">Data uji (20%)</p><p class="stat-card__value"><?= count($eval['test']) ?></p></div></article>
<article class="stat-card stat-card--primary"><span class="stat-card__icon"><span aria-hidden="true" class="icon icon--chart icon--lg"></span></span><div><p class="stat-card__label">Akurasi data uji</p><p class="stat-card__value"><?= number_format($eval['accuracy'] * 100, 2, ',', '.') ?>%</p></div></article>
</section>
<div class="layout-split layout-split--guide">
<section aria-labelledby="judul-distribusi" class="card"><h2 class="card__title" id="judul-distribusi"><span aria-hidden="true" class="icon icon--chart icon--lg"></span> Distribusi kelas</h2>
<div class="table-card"><table class="table"><caption class="visually-hidden">Distribusi kelas</caption><thead><tr><th scope="col">Kebutuhan pelatihan</th><th scope="col">Jumlah</th></tr></thead><tbody><?php foreach ($counts as $label => $n): ?><tr><td><?= esc($label) ?></td><td><?= (int) $n ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section aria-labelledby="judul-gain" class="card"><h2 class="card__title" id="judul-gain"><span aria-hidden="true" class="icon icon--target icon--lg"></span> Entropy dan Information Gain</h2>
<p class="info-card__text">Entropy data latih (<?= count($eval['train']) ?> baris): <strong><?= number_format($eval['entropy'], 6) ?></strong> bit.</p>
<div class="table-card"><table class="table"><caption class="visually-hidden">Information gain</caption><thead><tr><th scope="col">Atribut</th><th scope="col">Information Gain</th></tr></thead><tbody><?php foreach ($gains as $g): ?><tr><td><?= esc($g['feature']) ?></td><td><?= number_format($g['gain'], 6) ?></td></tr><?php endforeach; ?></tbody></table></div>
<p class="form-hint">Entropy(S) = −Σ pᵢ log₂(pᵢ); Gain(S,A) = Entropy(S) − Σ (|Sᵥ|/|S| × Entropy(Sᵥ)). Atribut dengan Gain tertinggi dipilih sebagai node.</p></section>
</div>
<section aria-labelledby="judul-pohon" class="card" style="margin-top:var(--space-5)"><div class="card__header"><h2 id="judul-pohon">Pohon keputusan hasil pelatihan</h2><span class="badge badge--info">ID3</span></div>
<p class="info-card__text">Atribut skor dibagi menjadi Rendah (&lt;60), Sedang (60–79), dan Tinggi (80–100). Usia dibagi menjadi Di bawah 21, 21–25, dan Di atas 25 tahun; gunakan kategori yang sama pada prediksi.</p>
<div class="tree-wrap"><?= dt_tree_html($eval['tree']) ?></div></section>
<section aria-labelledby="judul-evaluasi" class="card" style="margin-top:var(--space-5)">
<h2 class="text-h3" id="judul-evaluasi">Evaluasi model: Sesuai dan Tidak Sesuai</h2>
<?php $bn = $eval['biner']; $persen = number_format($bn['accuracy'] * 100, 2, ',', '.'); ?>
<p class="info-card__text">Hasil confusion matrix pada <?= (int) $bn['total'] ?> data uji:</p>
<div class="table-card"><table class="table"><caption class="visually-hidden">Confusion matrix Sesuai dan Tidak Sesuai</caption>
<thead><tr><th scope="col">Aktual / Prediksi</th><th scope="col" style="text-align:right">Sesuai</th><th scope="col" style="text-align:right">Tidak Sesuai</th></tr></thead>
<tbody><tr><td>Sesuai</td><td style="text-align:right"><?= (int) $bn['tp'] ?></td><td style="text-align:right"><?= (int) $bn['fn'] ?></td></tr>
<tr><td>Tidak Sesuai</td><td style="text-align:right"><?= (int) $bn['fp'] ?></td><td style="text-align:right"><?= (int) $bn['tn'] ?></td></tr></tbody></table></div>
<p class="info-card__text" style="margin-top:var(--space-4)">Accuracy:</p>
<div class="rumus" role="math" aria-label="Accuracy sama dengan <?= (int) $bn['tp'] ?> tambah <?= (int) $bn['tn'] ?> dibagi <?= (int) $bn['total'] ?> kali 100 persen sama dengan <?= esc($persen) ?> persen">
<i>Accuracy</i><span>=</span><span class="frac"><span>TP + TN</span><span>Total</span></span><span>× 100% =</span><span class="frac"><span><?= (int) $bn['tp'] ?> + <?= (int) $bn['tn'] ?></span><span><?= (int) $bn['total'] ?></span></span><span>× 100% = <?= esc($persen) ?>%</span></div>
<p class="info-card__text">Jadi, akurasi model pada data uji adalah <?= esc($persen) ?>%.</p>
<p class="form-hint">Sesuai = kelas <strong><?= esc(DT_LABEL_SESUAI) ?></strong>; Tidak Sesuai = kelas lainnya. TP = Sesuai terprediksi Sesuai, FN = Sesuai terprediksi Tidak Sesuai, FP = Tidak Sesuai terprediksi Sesuai, TN = Tidak Sesuai terprediksi Tidak Sesuai.</p>
</section>
<aside class="card card--muted info-card" style="margin-top:var(--space-5)"><span aria-hidden="true" class="icon icon--help"></span><div><h2 class="text-h3">Catatan metodologi</h2>
<p class="info-card__text">Pembagian data stratified dan deterministik: <?= count($eval['train']) ?> data latih dan <?= count($eval['test']) ?> data uji. Akurasi hanya menggambarkan data uji dari dataset kecil ini, bukan jaminan performa pada populasi nyata. Label target mengikuti isi dataset sumber, sehingga model mempelajari pola label tersebut, bukan menetapkan kebutuhan pelatihan yang objektif secara independen.</p></div></aside>
<?php require __DIR__ . '/../shared/footer.php'; ?>
