<?php
/**
 * lupa_password.php - Minta tautan reset password. Tautan dikirim HANYA ke email yang terdaftar pada akun tersebut.
 * Pesan hasil dibuat netral agar orang lain tidak bisa menebak email mana yang terdaftar.
 */
require_once __DIR__ . '/../shared/reset.php';

if ($sudah = current_user()) {
    redirect(url(home_for($sudah['role'])));
}

$errors   = [];
$email    = '';
$terkirim = false;

if (is_post()) {
    csrf_check();
    $email = strtolower(post_str('email'));

    if ($email === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!valid_email($email)) {
        $errors['email'] = 'Format email tidak valid. Contoh: nama@email.com';
    } elseif (($_SESSION['reset_terakhir'] ?? 0) > time() - 30) {
        $errors['umum'] = 'Tunggu ' . (30 - (time() - $_SESSION['reset_terakhir'])) . ' detik sebelum meminta lagi.';
    } else {
        $_SESSION['reset_terakhir'] = time();

        $st = db()->prepare("SELECT id, nama, email FROM users WHERE email = :e AND status = 'Aktif' LIMIT 1");
        $st->execute([':e' => $email]);
        $akun = $st->fetch();

        if ($akun) {
            [$ok, $galat] = kirim_tautan_reset($akun);
            // Galat teknis (mis. SMTP belum diisi) hanya diperlihatkan bila dibuka dari komputer lokal.
            if (!$ok && permintaan_lokal()) {
                $errors['umum'] = 'Email gagal dikirim: ' . $galat;
            }
        }
        if (!isset($errors['umum'])) {
            $terkirim = true;
        }
    }
}

$authJudul = 'Lupa Password';
require __DIR__ . '/../shared/auth_atas.php';
?>
<section aria-labelledby="lupa-judul" class="modal__box">
<a aria-label="Tutup" class="modal__close" href="<?= esc(url('user/login.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span></a>
<div class="modal__brand"><a class="brand" href="<?= esc(url('index.php')) ?>"><img alt="" height="32" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" width="32"><span>PasKarir</span></a></div>
<h1 class="modal__title" id="lupa-judul">Lupa Password</h1>
<?php if ($terkirim): ?>
<div class="alert alert--success" role="status">Jika email <strong><?= esc($email) ?></strong> terdaftar, tautan untuk mengatur ulang password sudah dikirim. Periksa kotak masuk (dan folder Spam). Tautan berlaku <?= (int) RESET_BERLAKU_MENIT ?> menit.</div>
<p class="form__note"><a href="<?= esc(url('user/login.php')) ?>">Kembali ke Login</a></p>
<?php else: ?>
<p class="modal__lead">Masukkan email akunmu. Kami akan mengirim tautan untuk mengatur ulang password ke email tersebut.</p>
<?php if (isset($errors['umum'])): ?><div class="alert alert--error" role="alert"><?= esc($errors['umum']) ?></div><?php endif; ?>
<form action="<?= esc(url('user/lupa_password.php')) ?>" class="form" method="post" novalidate>
<?= csrf_field() ?>
<div class="field"><label class="field__label" for="lupa-email">Email terdaftar</label><span class="input-wrap"><span aria-hidden="true" class="icon icon--mail"></span><input autocomplete="email" class="input" id="lupa-email" name="email" placeholder="nama@email.com" required type="email" value="<?= esc($email) ?>"></span><?= err_html($errors, 'email') ?></div>
<button class="btn btn--primary btn--block" type="submit">Kirim Tautan Reset</button>
<p class="form__note"><a href="<?= esc(url('user/login.php')) ?>">Kembali ke Login</a></p>
</form>
<?php endif; ?>
</section>
<?php require __DIR__ . '/../shared/auth_bawah.php'; ?>
