<?php
/**
 * lowongan_form.php - Tambah (CREATE) dan ubah (UPDATE) lowongan beserta syarat keterampilannya.
 * Tanpa ?id= = tambah baru; dengan ?id= = ubah. Penyimpanan memakai transaksi.
 */
require_once __DIR__ . '/../helpers.php';

if (!isset($area, $peran)) {   // hanya boleh dipanggil dari wrapper di folder role
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../data.php';

$user = require_role([$peran]);
$id   = get_int('id');

$in = ['nama' => '', 'bidang' => '', 'deskripsi' => '', 'pendidikan_min' => 'SMA / SMK', 'pengalaman_min' => '0', 'keterampilan' => '', 'status' => 'Aktif'];
if ($id !== null) {
    $lama = lowongan_by_id($id);
    if (!$lama) {
        flash('error', 'Lowongan tidak ditemukan.');
        redirect(url($area . '/lowongan.php'));
    }
    $in = [
        'nama' => $lama['nama'], 'bidang' => $lama['bidang'], 'deskripsi' => $lama['deskripsi'],
        'pendidikan_min' => $lama['pendidikan_min'], 'pengalaman_min' => (string) $lama['pengalaman_min'],
        'keterampilan' => implode(', ', $lama['keterampilan']), 'status' => $lama['status'],
    ];
}

$errors = [];
if (is_post()) {
    csrf_check();
    foreach ($in as $kunci => $_) {
        $in[$kunci] = post_str($kunci);
    }

    if ($in['nama'] === '') {
        $errors['nama'] = 'Nama lowongan wajib diisi.';
    } elseif (mb_strlen($in['nama']) < 3 || mb_strlen($in['nama']) > 100) {
        $errors['nama'] = 'Nama lowongan 3-100 karakter.';
    }
    $in['bidang'] = samakan_bidang($in['bidang']);
    if ($in['bidang'] === '' || mb_strlen($in['bidang']) > 50) {
        $errors['bidang'] = 'Bidang wajib diisi (maksimal 50 karakter).';
    }
    if (mb_strlen($in['deskripsi']) > 500) {
        $errors['deskripsi'] = 'Deskripsi maksimal 500 karakter.';
    }
    if (!isset(TINGKAT_PENDIDIKAN[$in['pendidikan_min']])) {
        $errors['pendidikan_min'] = 'Pilih pendidikan minimal.';
    }
    $peng = filter_var($in['pengalaman_min'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => MAKS_PENGALAMAN]]);
    if ($peng === false) {
        $errors['pengalaman_min'] = 'Pengalaman minimal harus bilangan bulat 0 sampai ' . MAKS_PENGALAMAN . ' tahun.';
    }
    list($skills, $errSkill) = parse_keterampilan($in['keterampilan'], 10);
    if ($errSkill !== null) {
        $errors['keterampilan'] = $errSkill;
    }
    if (!in_array($in['status'], ['Aktif', 'Ditutup'], true)) {
        $errors['status'] = 'Status tidak valid.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($id === null) {
                $pdo->prepare('INSERT INTO lowongan (nama, bidang, deskripsi, pendidikan_min, pengalaman_min, status, dibuat_oleh)
                               VALUES (:nama, :bidang, :desk, :pend, :peng, :status, :oleh)')
                    ->execute([':nama' => $in['nama'], ':bidang' => $in['bidang'], ':desk' => $in['deskripsi'],
                               ':pend' => $in['pendidikan_min'], ':peng' => $peng, ':status' => $in['status'], ':oleh' => $user['id']]);
                $lowonganId = (int) $pdo->lastInsertId();
            } else {
                $pdo->prepare('UPDATE lowongan SET nama = :nama, bidang = :bidang, deskripsi = :desk,
                               pendidikan_min = :pend, pengalaman_min = :peng, status = :status WHERE id = :id')
                    ->execute([':nama' => $in['nama'], ':bidang' => $in['bidang'], ':desk' => $in['deskripsi'],
                               ':pend' => $in['pendidikan_min'], ':peng' => $peng, ':status' => $in['status'], ':id' => $id]);
                $lowonganId = $id;
            }
            sinkron_keterampilan($pdo, $lowonganId, $skills);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        flash('success', $id === null ? 'Lowongan berhasil ditambahkan.' : 'Lowongan berhasil diperbarui.');
        redirect(url($area . '/lowongan.php'));
    }
}

$judul = $id === null ? 'Tambah Lowongan' : 'Ubah Lowongan';
$halamanAktif = $area . '/lowongan.php';
require __DIR__ . '/../header.php';
?>
<header class="page-header">
<h1><?= esc($judul) ?></h1>
<p class="page-header__desc">Isi data lowongan dan syaratnya. Syarat ini dipakai model untuk menentukan Sesuai atau Tidak Sesuai.</p>
</header>
<div class="layout-split layout-split--form">
<section aria-label="Form lowongan" class="card">
<form class="form-grid" method="post" novalidate>
<?= csrf_field() ?>
<div class="form-group">
<label class="label" for="nama">Nama lowongan</label>
<div class="control"><span aria-hidden="true" class="icon icon--briefcase"></span><input class="input" id="nama" name="nama" type="text" maxlength="100" placeholder="Contoh: Staf IT" value="<?= esc($in['nama']) ?>"></div>
<?= err_html($errors, 'nama') ?>
</div>
<div class="form-group">
<label class="label" for="bidang">Bidang</label>
<div class="control"><span aria-hidden="true" class="icon icon--document"></span><input class="input" id="bidang" name="bidang" type="text" maxlength="50" list="bidang-list" placeholder="Pilih dari daftar atau ketik bidang baru" autocomplete="off" value="<?= esc($in['bidang']) ?>"></div>
<datalist id="bidang-list"><?php foreach (daftar_bidang() as $b): ?><option value="<?= esc($b) ?>"><?php endforeach; ?></datalist>
<p class="field__hint">Pilih bidang yang sudah ada agar lowongan tampil di bidang yang benar pada form klasifikasi. Penulisan yang sama (beda huruf besar/kecil atau spasi) otomatis disatukan.</p>
<?= err_html($errors, 'bidang') ?>
</div>
<div class="form-group form-group--full">
<label class="label" for="skill-baru">Syarat keterampilan</label>
<div class="chip-input" data-chips><?php foreach (array_filter(array_map('trim', explode(',', (string) $in['keterampilan']))) as $__k): ?><span class="chip"><?= esc($__k) ?> <button aria-label="Hapus <?= esc($__k) ?>" class="chip__remove" type="button">×</button></span><?php endforeach; ?><input class="chip-input__field" id="skill-baru" type="text" placeholder="+ tambah keterampilan"></div>
<input type="hidden" name="keterampilan" id="keterampilan" value="<?= esc($in['keterampilan']) ?>">
<p class="field__hint">Ketik lalu tekan Enter atau koma, maksimal 10 keterampilan.</p>
<?= err_html($errors, 'keterampilan') ?>
</div>
<div class="form-group">
<label class="label" for="pendidikan_min">Pendidikan minimal</label>
<div class="control"><span aria-hidden="true" class="icon icon--document"></span>
<select class="select" id="pendidikan_min" name="pendidikan_min"><?php foreach (array_keys(TINGKAT_PENDIDIKAN) as $p): ?><option value="<?= esc($p) ?>" <?= $p === $in['pendidikan_min'] ? 'selected' : '' ?>><?= esc($p) ?></option><?php endforeach; ?></select></div>
<?= err_html($errors, 'pendidikan_min') ?>
</div>
<div class="form-group">
<label class="label" for="pengalaman_min">Pengalaman minimal (tahun)</label>
<div class="control"><span aria-hidden="true" class="icon icon--clock"></span><input class="input" id="pengalaman_min" name="pengalaman_min" type="number" min="0" max="<?= MAKS_PENGALAMAN ?>" step="1" placeholder="0" value="<?= esc($in['pengalaman_min']) ?>"></div>
<?= err_html($errors, 'pengalaman_min') ?>
</div>
<fieldset class="form-group form-group--full">
<legend class="label">Status</legend>
<div class="radio-group"><?php foreach (['Aktif', 'Ditutup'] as $s): ?><label class="radio-card"><input name="status" type="radio" value="<?= $s ?>" <?= $s === $in['status'] ? 'checked' : '' ?>> <?= $s ?></label><?php endforeach; ?></div>
<?= err_html($errors, 'status') ?>
</fieldset>
<div class="form-group form-group--full">
<label class="label" for="deskripsi">Deskripsi (opsional)</label>
<textarea class="textarea" id="deskripsi" name="deskripsi" rows="4" maxlength="500"><?= esc($in['deskripsi']) ?></textarea>
<?= err_html($errors, 'deskripsi') ?>
</div>
<div class="form-actions form-group--full">
<button class="btn btn--primary" type="submit"><span aria-hidden="true" class="icon icon--check"></span> Simpan</button>
<a class="btn btn--outline" href="<?= esc(url($area . '/lowongan.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span> Batal</a>
</div>
</form>
</section>
<aside aria-labelledby="judul-petunjuk" class="card card--muted">
<h2 class="card__title" id="judul-petunjuk"><span aria-hidden="true" class="icon icon--help icon--lg"></span> Petunjuk</h2>
<ul class="hint-list">
<li>Nama dan bidang wajib diisi.</li>
<li>Tambah semua keterampilan yang dibutuhkan.</li>
<li>Pendidikan dan pengalaman adalah batas minimal.</li>
</ul>
<div class="hint-box"><p class="hint-box__title">Hapus: muncul dialog</p><p>konfirmasi (lihat Kelola Lowongan).</p></div>
</aside>
</div>
<script defer src="<?= esc(url('../../backend/js/shared/chips.js')) ?>"></script><script defer src="<?= esc(url('../../backend/js/shared/chips-php.js')) ?>"></script>
<?php require __DIR__ . '/../footer.php'; ?>
