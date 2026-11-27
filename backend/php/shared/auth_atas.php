<?php /** Kerangka halaman auth (latar landing + overlay). Variabel: \$authJudul. */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($authJudul ?? 'PasKarir') ?> | PasKarir</title>
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
