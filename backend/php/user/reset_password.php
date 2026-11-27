<?php
/**
 * reset_password.php - Halaman yang dibuka dari tautan di email: buat password baru.
 * Token harus valid, belum dipakai, belum kedaluwarsa, dan hanya bisa dipakai satu kali.
 */
require_once __DIR__ . '/../shared/reset.php';

if ($sudah = current_user()) {
    redirect(url(home_for($sudah['role'])));
}

$token  = is_post() ? post_str('token') : get_str('token');
$baris  = cari_token_reset($token);
$errors = [];

if ($baris && is_post()) {
    csrf_check();
    $password   = (string) ($_POST['password'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi'] ?? '');

    if ($password === '') {
        $errors['password'] = 'Password baru wajib diisi.';
    } elseif (!password_kuat($password)) {
        $errors['password'] = 'Password minimal 8 karakter dan memuat huruf serta angka.';
    }
    if ($konfirmasi === '') {
        $errors['konfirmasi'] = 'Konfirmasi password wajib diisi.';
    } elseif ($konfirmasi !== $password) {
        $errors['konfirmasi'] = 'Konfirmasi tidak sama dengan password.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
                ->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $baris['user_id']]);
            // Token ini dan semua token lain milik akun tersebut dihapus agar tidak bisa dipakai lagi.
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = :id')->execute([':id' => $baris['user_id']]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Reset password gagal: ' . $e->getMessage());
            $errors['umum'] = 'Terjadi kesalahan. Coba lagi.';
        }
        if (!$errors) {
            unset($_SESSION['gagal'], $_SESSION['kunci_sampai']);
            flash('success', 'Password berhasil diubah. Silakan masuk dengan password baru.');
            redirect(url('user/login.php'));
        }
    }
}

$authJudul = 'Password Baru';
require __DIR__ . '/../shared/auth_atas.php';
?>
<section aria-labelledby="reset-judul" class="modal__box">
<a aria-label="Tutup" class="modal__close" href="<?= esc(url('user/login.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span></a>
<div class="modal__brand"><a class="brand" href="<?= esc(url('index.php')) ?>"><img alt="" height="32" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" width="32"><span>PasKarir</span></a></div>
<?php if (!$baris): ?>
<h1 class="modal__title" id="reset-judul">Tautan Tidak Berlaku</h1>
<div class="alert alert--error" role="alert">Tautan reset password tidak valid, sudah dipakai, atau sudah kedaluwarsa.</div>
<a class="btn btn--primary btn--block" href="<?= esc(url('user/lupa_password.php')) ?>">Minta Tautan Baru</a>
<p class="form__note"><a href="<?= esc(url('user/login.php')) ?>">Kembali ke Login</a></p>
<?php else: ?>
<h1 class="modal__title" id="reset-judul">Password Baru</h1>
<p class="modal__lead">Buat password baru untuk <strong><?= esc($baris['email']) ?></strong>.</p>
<?php if (isset($errors['umum'])): ?><div class="alert alert--error" role="alert"><?= esc($errors['umum']) ?></div><?php endif; ?>
<form action="<?= esc(url('user/reset_password.php')) ?>" class="form" method="post" novalidate>
<?= csrf_field() ?>
<input type="hidden" name="token" value="<?= esc($token) ?>">
<div class="field"><label class="field__label" for="reset-password">Password baru</label><span class="input-wrap"><span aria-hidden="true" class="icon icon--lock"></span><input autocomplete="new-password" class="input input--end" id="reset-password" name="password" placeholder="Minimal 8 karakter, huruf dan angka" required type="password"><button aria-controls="reset-password" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="reset-password" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span><?= err_html($errors, 'password') ?></div>
<div class="field"><label class="field__label" for="reset-konfirmasi">Konfirmasi password</label><span class="input-wrap"><span aria-hidden="true" class="icon icon--lock"></span><input autocomplete="new-password" class="input input--end" id="reset-konfirmasi" name="konfirmasi" placeholder="Ulangi password baru" required type="password"><button aria-controls="reset-konfirmasi" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="reset-konfirmasi" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span><?= err_html($errors, 'konfirmasi') ?></div>
<button class="btn btn--primary btn--block" type="submit">Simpan Password Baru</button>
<p class="form__note"><a href="<?= esc(url('user/login.php')) ?>">Kembali ke Login</a></p>
</form>
<?php endif; ?>
</section>
<?php require __DIR__ . '/../shared/auth_bawah.php'; ?>
