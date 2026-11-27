<?php
/**
 * login.php - Proses login: validasi, cek password (password_verify), mulai sesi, arahkan sesuai role.
 */
require_once __DIR__ . '/../shared/helpers.php';

if ($sudah = current_user()) {
    redirect(url(home_for($sudah['role'])));
}

// Hash pura-pura agar waktu respons sama untuk email terdaftar maupun tidak (mencegah tebakan akun).
const HASH_PURA = '$2y$10$540bMvs4tpyhxKxeJmssveSW82TNnPnO8OsZPK38ltdxlYQWwOxb6';
const MAKS_GAGAL = 5;
const KUNCI_DETIK = 60;

$errors = [];
$email  = '';

if (is_post()) {
    csrf_check();
    $email    = strtolower(post_str('email'));
    $password = (string) ($_POST['password'] ?? '');

    if (($_SESSION['kunci_sampai'] ?? 0) > time()) {
        $errors['umum'] = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ($_SESSION['kunci_sampai'] - time()) . ' detik.';
    } else {
        if ($email === '') {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (!valid_email($email)) {
            $errors['email'] = 'Format email tidak valid. Contoh: nama@email.com';
        }
        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        }

        if (!$errors) {
            $stmt = db()->prepare('SELECT id, password_hash, role, status FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $baris = $stmt->fetch();

            $cocok = password_verify($password, $baris['password_hash'] ?? HASH_PURA) && $baris;

            if (!$cocok) {
                $_SESSION['gagal'] = ($_SESSION['gagal'] ?? 0) + 1;
                if ($_SESSION['gagal'] >= MAKS_GAGAL) {
                    $_SESSION['kunci_sampai'] = time() + KUNCI_DETIK;
                    $_SESSION['gagal'] = 0;
                }
                $errors['umum'] = 'Login gagal. Email atau password tidak cocok.';
            } elseif ($baris['status'] !== 'Aktif') {
                $errors['umum'] = 'Akun ini sedang dinonaktifkan. Hubungi Super Admin untuk mengaktifkannya kembali.';
            } else {
                // Login berhasil: ganti ID sesi (anti session fixation) lalu simpan id pengguna.
                session_regenerate_id(true);
                $_SESSION['uid']   = (int) $baris['id'];
                $_SESSION['aktif'] = time();
                unset($_SESSION['gagal'], $_SESSION['kunci_sampai']);

                if (password_needs_rehash($baris['password_hash'], PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
                        ->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => (int) $baris['id']]);
                }
                redirect(url(home_for($baris['role'])));
            }
        }
    }
}

$base = app_base();
$judul = 'Masuk';
$flashLogin = ambil_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk | PasKarir</title>
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
<main class="auth" id="main-content"><section aria-labelledby="login-judul" class="modal__box">
<a aria-label="Tutup" class="modal__close" href="<?= esc(url('index.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span></a>
<div class="modal__brand"><a class="brand" href="<?= esc(url('index.php')) ?>"><img alt="" height="32" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" width="32"><span>PasKarir</span></a></div>
<h1 class="modal__title" id="login-judul">Masuk</h1><p class="modal__lead">Masukkan email dan password untuk melanjutkan.</p>
<?php if ($flashLogin): ?><div class="alert alert--<?= $flashLogin['tipe'] === 'success' ? 'success' : 'error' ?>" role="status"><?= esc($flashLogin['pesan']) ?></div><?php endif; ?>
<?php if (isset($errors['umum'])): ?><div class="alert alert--error" role="alert"><?= esc($errors['umum']) ?></div><?php endif; ?>
<form action="<?= esc(url('user/login.php')) ?>" class="form" method="post" novalidate>
<?= csrf_field() ?>
<div class="field"><label class="field__label" for="login-email">Email</label><span class="input-wrap"><span aria-hidden="true" class="icon icon--mail"></span><input autocomplete="username" class="input" id="login-email" name="email" placeholder="nama@email.com" required type="email" value="<?= esc($email) ?>"></span><?= err_html($errors,'email') ?></div>
<div class="field"><label class="field__label" for="login-password">Password</label><span class="input-wrap"><span aria-hidden="true" class="icon icon--lock"></span><input autocomplete="current-password" class="input input--end" id="login-password" name="password" placeholder="Masukkan password" required type="password"><button aria-controls="login-password" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="login-password" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span><?= err_html($errors,'password') ?></div>
<div class="form__row-between"><span></span><a href="<?= esc(url('user/lupa_password.php')) ?>">Lupa password?</a></div>
<button class="btn btn--primary btn--block" type="submit">Masuk <span aria-hidden="true" class="icon icon--arrow"></span></button>
<p class="form__note">Belum punya akun? <a href="<?= esc(url('user/daftar.php')) ?>"><strong>Daftar</strong></a></p>
</form></section></main>
<script src="<?= esc(url('../../backend/js/shared/core.js')) ?>"></script><script src="<?= esc(url('../../backend/js/shared/common.js')) ?>"></script>
</body></html>
