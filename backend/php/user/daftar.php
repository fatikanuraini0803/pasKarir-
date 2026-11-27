<?php
/**
 * daftar.php - Pendaftaran akun baru (CREATE akun). Role: 'User' (default) atau 'Sub Admin'; password di-hash.
 */
require_once __DIR__ . '/../shared/helpers.php';

if ($sudah = current_user()) {
    redirect(url(home_for($sudah['role'])));
}

$errors = [];
$in = ['nama' => '', 'email' => '', 'role' => 'User'];

if (is_post()) {
    csrf_check();
    $in['nama']  = post_str('nama');
    $in['email'] = strtolower(post_str('email'));
    $in['role']  = post_str('role') === 'Sub Admin' ? 'Sub Admin' : 'User';   // hanya dua role ini yang boleh dipilih
    $password    = (string) ($_POST['password'] ?? '');
    $konfirmasi  = (string) ($_POST['konfirmasi'] ?? '');

    if ($in['nama'] === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (!valid_nama($in['nama'])) {
        $errors['nama'] = 'Nama 3-100 karakter dan hanya berisi huruf dan spasi.';
    }

    if ($in['email'] === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!valid_email($in['email'])) {
        $errors['email'] = 'Format email tidak valid. Contoh: nama@email.com';
    } else {
        $cek = db()->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $cek->execute([':email' => $in['email']]);
        if ($cek->fetchColumn()) {
            $errors['email'] = 'Email ini sudah terdaftar. Silakan masuk.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (!password_kuat($password)) {
        $errors['password'] = 'Password minimal 8 karakter dan memuat huruf serta angka.';
    }

    if ($konfirmasi === '') {
        $errors['konfirmasi'] = 'Konfirmasi password wajib diisi.';
    } elseif ($konfirmasi !== $password) {
        $errors['konfirmasi'] = 'Konfirmasi tidak sama dengan password.';
    }

    if (empty($_POST['setuju'])) {
        $errors['setuju'] = 'Kamu harus menyetujui syarat dan ketentuan.';
    }

    if (!$errors) {
        try {
            db()->prepare("INSERT INTO users (nama, email, password_hash, role, status) VALUES (:nama, :email, :hash, :role, 'Aktif')")
                ->execute([
                    ':nama'  => $in['nama'],
                    ':email' => $in['email'],
                    ':hash'  => password_hash($password, PASSWORD_DEFAULT),
                    ':role'  => $in['role'],
                ]);
            flash('success', 'Akun berhasil dibuat. Silakan masuk dengan email dan password kamu.');
            redirect(url('user/login.php'));
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {   // pelanggaran UNIQUE (email dipakai bersamaan)
                $errors['email'] = 'Email ini sudah terdaftar. Silakan masuk.';
            } else {
                throw $e;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar | PasKarir</title>
<link rel="icon" href="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/variables.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/base.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/assets/icons/icons-user.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/layout.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/components.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/pages.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/assets/css/feedback.css')) ?>">
</head>
<body class="auth-page">
<div aria-hidden="true" class="auth__bg" inert>
<header class="site-header"><div class="container site-header__inner">
<a class="brand" href="<?= esc(url('index.php')) ?>"><img alt="" height="32" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" width="32"><span>PasKarir</span></a>
<nav aria-label="Menu utama" class="site-nav"><ul class="site-nav__links"><li><a class="site-nav__link" href="<?= esc(url('index.php')) ?>">Beranda</a></li><li><a class="site-nav__link" href="<?= esc(url('user/lowongan.php')) ?>">Lowongan</a></li><li><a class="site-nav__link" href="<?= esc(url('index.php')) ?>#cara-kerja">Cara Kerja</a></li></ul>
<div class="site-nav__actions"><a class="btn btn--outline" href="<?= esc(url('user/daftar.php')) ?>">Daftar</a><a class="btn btn--primary" href="<?= esc(url('user/login.php')) ?>"><span aria-hidden="true" class="icon icon--user"></span> Login</a></div></nav>
</div></header>
<div class="auth__bg-main"><section class="hero"><div class="container hero__inner"><div>
<p class="hero__tag">Berbasis Decision Tree</p><h1 class="hero__title">Cek kesesuaianmu dengan lowongan kerja</h1>
<p class="hero__text">Isi keterampilan, pendidikan, pengalaman, dan sertifikasi. Hasil langsung beserta alasannya.</p>
<div class="hero__actions"><a class="btn btn--light btn--lg" href="<?= esc(url('user/login.php')) ?>"><span aria-hidden="true" class="icon icon--check"></span> Cek Kesesuaian</a><a class="btn btn--ghost-light btn--lg" href="<?= esc(url('user/lowongan.php')) ?>"><span aria-hidden="true" class="icon icon--search"></span> Lihat Lowongan</a></div>
<dl class="hero__stats"><div class="hero__stat"><dt class="visually-hidden">Fitur</dt><dd><strong>Decision Tree</strong><span>Klasifikasi</span></dd></div><div class="hero__stat"><dt class="visually-hidden">Waktu proses</dt><dd><strong>Otomatis</strong><span>Hasil analisis</span></dd></div></dl>
</div><figure class="hero__media"><svg aria-label="Ilustrasi pohon keputusan" role="img" viewBox="0 0 240 180"><g fill="none" stroke="#2563eb" stroke-linecap="round" stroke-width="3"><path d="M120 34v28M120 62 60 100M120 62l60 38M60 100 30 140M60 100l30 40M180 100l-30 40M180 100l30 40" pathLength="100"></path></g><circle cx="120" cy="26" fill="#2563eb" r="14"></circle><circle cx="60" cy="100" fill="#021a4a" r="12"></circle><circle cx="180" cy="100" fill="#021a4a" r="12"></circle><circle cx="30" cy="148" fill="#16a34a" r="10"></circle><circle cx="90" cy="148" fill="#dc2626" r="10"></circle><circle cx="150" cy="148" fill="#16a34a" r="10"></circle><circle cx="210" cy="148" fill="#dc2626" r="10"></circle></svg></figure>
</div></section><section class="features"><div class="container"><div class="features__grid"><article class="card feature"><span class="icon-tile"><span class="icon icon--check"></span></span><h3>Klasifikasi Sesuai / Tidak</h3><p class="feature__text">Hasil langsung beserta alasannya.</p></article><article class="card card--dark feature"><span class="icon-tile"><span class="icon icon--briefcase"></span></span><h3>Rekomendasi Lowongan</h3><p class="feature__text">Temukan lowongan yang cocok.</p></article><article class="card feature"><span class="icon-tile"><span class="icon icon--clock"></span></span><h3>Riwayat Klasifikasi</h3><p class="feature__text">Lihat kembali hasil sebelumnya.</p></article></div></div></section><section class="how"><div class="container"><h2 class="how__title">Cara Kerja</h2><ol class="how__steps"><li class="how__step"><span class="num num--lg">1</span>Isi data diri</li><li aria-hidden="true" class="how__line"></li><li class="how__step"><span class="num num--lg">2</span>Pilih lowongan</li><li aria-hidden="true" class="how__line"></li><li class="how__step"><span class="num num--lg">3</span>Proses</li><li aria-hidden="true" class="how__line"></li><li class="how__step"><span class="num num--lg">4</span>Lihat hasil</li></ol></div></section></div></div>
<div class="auth__overlay" aria-hidden="true"></div>
<main class="auth" id="main-content">
<section aria-labelledby="daftar-judul" class="modal__box">
<a aria-label="Tutup" class="modal__close" href="<?= esc(url('index.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span></a>
<div class="modal__brand"><a class="brand" href="<?= esc(url('index.php')) ?>"><img alt="" height="32" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" width="32"><span>Paskarir</span></a></div>
<h1 class="modal__title" id="daftar-judul">Daftar Akun</h1>
<p class="modal__lead">Lengkapi data berikut untuk membuat akun baru.</p>
<?php if ($errors): ?><div class="alert alert--error" role="alert">Ada <?= count($errors) ?> isian yang belum benar.</div><?php endif; ?>
<form action="<?= esc(url('user/daftar.php')) ?>" class="form" method="post" novalidate>
<?= csrf_field() ?>
<div class="field"><label class="field__label" for="daftar-nama">Nama lengkap</label><input autocomplete="name" class="input" id="daftar-nama" name="nama" maxlength="100" placeholder="Nama lengkap" type="text" value="<?= esc($in['nama']) ?>"><?= err_html($errors, 'nama') ?></div>
<div class="field"><label class="field__label" for="daftar-email">Email</label><input autocomplete="email" class="input" id="daftar-email" name="email" maxlength="120" placeholder="nama@email.com" type="email" value="<?= esc($in['email']) ?>"><?= err_html($errors, 'email') ?></div>
<div class="field"><span class="field__label">Daftar sebagai</span>
<label class="choice"><input name="role" type="radio" value="User" <?= $in['role'] === 'User' ? 'checked' : '' ?>> <span>Pengguna (cek kesesuaian lowongan)</span></label>
<label class="choice"><input name="role" type="radio" value="Sub Admin" <?= $in['role'] === 'Sub Admin' ? 'checked' : '' ?>> <span>Sub Admin (kelola lowongan &amp; panduan)</span></label></div>
<div class="form-row">
<div class="field"><label class="field__label" for="daftar-password">Password</label><span class="input-wrap"><input autocomplete="new-password" class="input input--toggle" id="daftar-password" name="password" placeholder="Min. 8 karakter" type="password"><button aria-controls="daftar-password" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="daftar-password" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span><?= err_html($errors, 'password') ?></div>
<div class="field"><label class="field__label" for="daftar-konfirmasi">Konfirmasi</label><span class="input-wrap"><input autocomplete="new-password" class="input input--toggle" id="daftar-konfirmasi" name="konfirmasi" placeholder="Ulangi password" type="password"><button aria-controls="daftar-konfirmasi" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="daftar-konfirmasi" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span><?= err_html($errors, 'konfirmasi') ?></div>
</div>
<label class="choice"><input name="setuju" type="checkbox" value="1" <?= !empty($_POST['setuju']) ? 'checked' : '' ?>> <span>Saya setuju dengan Syarat dan Ketentuan serta Kebijakan Privasi</span></label>
<?= err_html($errors, 'setuju') ?>
<button class="btn btn--primary btn--block" type="submit">Daftar</button>
<p class="form__note">Sudah punya akun? <a href="<?= esc(url('user/login.php')) ?>"><strong>Masuk</strong></a></p>
</form>
</section>
</main>
<script src="<?= esc(url('../../backend/js/shared/core.js')) ?>"></script><script src="<?= esc(url('../../backend/js/shared/common.js')) ?>"></script>
</body></html>
