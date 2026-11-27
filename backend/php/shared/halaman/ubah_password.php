<?php
/**
 * ubah_password.php - Ganti password sendiri (UPDATE password_hash). Dipakai bersama ketiga role (folder dan role ditentukan wrapper).
 */
require_once __DIR__ . '/../helpers.php';

if (!isset($area, $peran)) {   // hanya boleh dipanggil dari wrapper di folder role
    http_response_code(404);
    exit;
}

$user = require_role([$peran]);
$errors = [];

if (is_post()) {
    csrf_check();
    $lama       = (string) ($_POST['lama'] ?? '');
    $baru       = (string) ($_POST['baru'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi'] ?? '');

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute([':id' => $user['id']]);
    $hashLama = (string) $stmt->fetchColumn();

    if ($lama === '') {
        $errors['lama'] = 'Password lama wajib diisi.';
    } elseif (!password_verify($lama, $hashLama)) {
        $errors['lama'] = 'Password lama salah.';
    }

    if ($baru === '') {
        $errors['baru'] = 'Password baru wajib diisi.';
    } elseif (!password_kuat($baru)) {
        $errors['baru'] = 'Password minimal 8 karakter dan memuat huruf serta angka.';
    } elseif ($baru === $lama) {
        $errors['baru'] = 'Password baru tidak boleh sama dengan password lama.';
    }

    if ($konfirmasi === '') {
        $errors['konfirmasi'] = 'Konfirmasi password wajib diisi.';
    } elseif ($konfirmasi !== $baru) {
        $errors['konfirmasi'] = 'Konfirmasi tidak sama dengan password baru.';
    }

    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
            ->execute([':h' => password_hash($baru, PASSWORD_DEFAULT), ':id' => $user['id']]);
        session_regenerate_id(true);
        flash('success', 'Password berhasil diubah.');
        redirect(url($area . '/profil.php'));
    }
}

$judul = 'Ubah Password';
$halamanAktif = $area . '/profil.php';
require __DIR__ . '/../header.php';
?>
<?php if (($user['role'] ?? '') === 'User'): ?>
<div class="page-head"><h1>Ubah Password</h1><p>Masukkan password lama, lalu buat password baru untuk akunmu.</p></div>
<div class="split split--even">
<section aria-labelledby="form-pw" class="card"><h2 class="card__title card__title--gap" id="form-pw">Form ubah password</h2>
<form class="form" method="post" novalidate><?= csrf_field() ?>
<?php foreach ([['lama','Password lama','current-password','********'],['baru','Password baru','new-password','Min. 8 karakter'],['konfirmasi','Konfirmasi password baru','new-password','Ulangi password baru']] as [$f,$lbl,$ac,$ph]): ?>
<div class="field"><label class="field__label" for="<?= $f ?>"><?= $lbl ?></label><span class="input-wrap"><input autocomplete="<?= $ac ?>" class="input input--toggle" id="<?= $f ?>" name="<?= $f ?>" placeholder="<?= $ph ?>" type="password"><button aria-controls="<?= $f ?>" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="<?= $f ?>" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span><?= err_html($errors, $f) ?></div>
<?php endforeach; ?>
<div class="form__actions"><button class="btn btn--primary btn--lg" type="submit"><span aria-hidden="true" class="icon icon--check"></span> Simpan Password</button><a class="btn btn--outline btn--lg" href="<?= esc(url('user/profil.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span> Batal</a></div>
</form></section>
<aside aria-labelledby="tips" class="card card--panel"><h2 class="card__title card__title--gap" id="tips">Tips password aman</h2>
<ol class="item-list"><li class="item item--step"><span class="num">1</span>Minimal 8 karakter.</li><li class="item item--step"><span class="num">2</span>Gabungkan huruf besar, huruf kecil, dan angka.</li><li class="item item--step"><span class="num">3</span>Jangan memakai password yang sama dengan akun lain.</li></ol></aside>
</div>
<?php else: ?>
<header class="page-header">
<h1>Ubah Password</h1>
<p class="page-header__desc">Gunakan password baru yang kuat: minimal 8 karakter, memuat huruf dan angka.</p>
</header>
<div class="layout-split layout-split--form">
<section aria-labelledby="pw-judul" class="card">
<h2 id="pw-judul">Ubah Password</h2>
<form class="stack" method="post" novalidate>
<?= csrf_field() ?>
<?php foreach ([['lama','Password lama','current-password'],['baru','Password baru','new-password'],['konfirmasi','Konfirmasi password baru','new-password']] as [$f,$lbl,$ac]): ?>
<div class="form-group">
<label class="label" for="pw-<?= $f ?>"><?= $lbl ?></label>
<span class="pw-wrap"><input class="input" id="pw-<?= $f ?>" name="<?= $f ?>" type="password" autocomplete="<?= $ac ?>"><button aria-controls="pw-<?= $f ?>" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="pw-<?= $f ?>" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span>
<?= err_html($errors, $f) ?>
</div>
<?php endforeach; ?>
<div class="form-actions form-actions--end"><a class="btn btn--outline" href="<?= esc(url($area . '/profil.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span> Batal</a><button class="btn btn--primary" type="submit"><span aria-hidden="true" class="icon icon--lock"></span> Ubah Password</button></div>
</form>
</section>
<aside class="card card--muted info-card"><span aria-hidden="true" class="icon icon--shield"></span><p class="info-card__text">Jangan membagikan password kepada siapa pun. Setelah berhasil diubah, gunakan password baru saat masuk berikutnya.</p></aside>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../footer.php'; ?>
