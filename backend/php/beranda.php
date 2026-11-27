<?php
/** beranda.php - Landing page (tampilan dari index.html), dilayani lewat PHP. Di-include oleh index.php. */
require_once __DIR__ . '/shared/helpers.php';
?>
<!DOCTYPE html>

<html lang="id">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta content="Paskarir membantu kamu mengecek kesesuaian dengan lowongan kerja memakai metode Decision Tree, tanpa unggah berkas lamaran." name="description"/>
<title>Cek Kesesuaian dengan Lowongan Kerja | Paskarir</title>
<link href="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" rel="icon" type="image/svg+xml"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<script>document.documentElement.classList.add("js")</script>
<link href="<?= esc(url('../../frontend/user/css/variables.css')) ?>" rel="stylesheet"/>
<link href="<?= esc(url('../../frontend/user/css/base.css')) ?>" rel="stylesheet"/>
<link href="<?= esc(url('../../frontend/assets/icons/icons-user.css')) ?>" rel="stylesheet"/>
<link href="<?= esc(url('../../frontend/user/css/layout.css')) ?>" rel="stylesheet"/>
<link href="<?= esc(url('../../frontend/user/css/components.css')) ?>" rel="stylesheet"/>
<link href="<?= esc(url('../../frontend/user/css/pages.css')) ?>" rel="stylesheet"/>
</head>
<body><a class="skip-link" href="#main-content">Lewati ke konten utama</a>
<header class="site-header">
<div class="container site-header__inner">
<a class="brand" href="<?= esc(url('index.php')) ?>"><img alt="" height="32" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" width="32"/><span>Paskarir</span></a>
<nav aria-label="Menu utama" class="site-nav">
<ul class="site-nav__links" data-nav>
<li><a aria-current="page" class="site-nav__link" href="<?= esc(url('index.php')) ?>">Beranda</a></li>
<li><a class="site-nav__link" href="#fitur">Lowongan</a></li>
<li><a class="site-nav__link" href="#cara-kerja">Cara Kerja</a></li>
</ul>
<div class="site-nav__actions">
<a class="btn btn--outline" href="<?= esc(url('user/daftar.php')) ?>">Daftar</a>
<a class="btn btn--primary" href="<?= esc(url('user/login.php')) ?>"><span aria-hidden="true" class="icon icon--user"></span> Login</a>
</div>
</nav>
</div>
</header>
<main id="main-content">
<section aria-labelledby="hero-judul" class="hero">
<div class="container hero__inner">
<div>
<p class="hero__tag">Berbasis Decision Tree</p>
<h1 class="hero__title" id="hero-judul">Cek kesesuaianmu dengan lowongan kerja</h1>
<p class="hero__text">Isi keterampilan, pendidikan, pengalaman, dan sertifikasi. Tanpa unggah berkas lamaran. Hasil langsung beserta alasannya.</p>
<div class="hero__actions">
<a class="btn btn--light btn--lg" href="<?= esc(url('user/login.php')) ?>"><span aria-hidden="true" class="icon icon--check"></span> Cek Kesesuaian</a>
<a class="btn btn--ghost-light btn--lg" href="#fitur"><span aria-hidden="true" class="icon icon--search"></span> Lihat Lowongan</a>
</div>
<dl class="hero__stats">
<div class="hero__stat"><dt class="visually-hidden">Jumlah lowongan</dt><dd><strong>12+</strong><span>Lowongan</span></dd></div>
<div class="hero__stat"><dt class="visually-hidden">Waktu proses</dt><dd><strong>&lt; 1 mnt</strong><span>Waktu proses</span></dd></div>
</dl>
</div>
<figure class="hero__media"><svg aria-label="Ilustrasi pohon keputusan" role="img" viewbox="0 0 240 180">
<g fill="none" stroke="#2563eb" stroke-linecap="round" stroke-width="3"><path d="M120 34v28M120 62 60 100M120 62l60 38M60 100 30 140M60 100l30 40M180 100l-30 40M180 100l30 40" pathLength="100"></path></g>
<circle cx="120" cy="26" fill="#2563eb" r="14"></circle><circle cx="60" cy="100" fill="#021a4a" r="12"></circle><circle cx="180" cy="100" fill="#021a4a" r="12"></circle>
<circle cx="30" cy="148" fill="#16a34a" r="10"></circle><circle cx="90" cy="148" fill="#dc2626" r="10"></circle><circle cx="150" cy="148" fill="#16a34a" r="10"></circle><circle cx="210" cy="148" fill="#dc2626" r="10"></circle></svg><figcaption class="visually-hidden">Contoh jalur keputusan sesuai atau tidak sesuai</figcaption></figure>
</div>
</section>
<section aria-labelledby="fitur-judul" class="features" id="fitur">
<div class="container">
<h2 class="visually-hidden" id="fitur-judul">Fitur utama</h2>
<div class="features__grid">
<article class="card feature reveal"><span class="icon-tile"><span aria-hidden="true" class="icon icon--check"></span></span><h3>Klasifikasi Sesuai / Tidak</h3><p class="feature__text">Hasil langsung beserta alasannya.</p><a class="feature__link" href="<?= esc(url('user/login.php')) ?>">Selengkapnya <span aria-hidden="true" class="icon icon--arrow"></span></a></article>
<article class="card card--dark feature reveal"><span class="icon-tile"><span aria-hidden="true" class="icon icon--briefcase"></span></span><h3>Rekomendasi Lowongan</h3><p class="feature__text">Lowongan lain yang cocok untukmu.</p><a class="feature__link" href="<?= esc(url('user/login.php')) ?>">Selengkapnya <span aria-hidden="true" class="icon icon--arrow"></span></a></article>
<article class="card feature reveal"><span class="icon-tile"><span aria-hidden="true" class="icon icon--clock"></span></span><h3>Riwayat Klasifikasi</h3><p class="feature__text">Lihat kembali hasil sebelumnya.</p><a class="feature__link" href="<?= esc(url('user/login.php')) ?>">Selengkapnya <span aria-hidden="true" class="icon icon--arrow"></span></a></article>
</div>
</div>
</section>
<section aria-labelledby="cara-judul" class="how" id="cara-kerja">
<div class="container">
<h2 class="how__title" id="cara-judul">Cara Kerja</h2>
<ol class="how__steps">
<li class="how__step reveal"><span class="num num--lg">1</span>Isi data diri</li><li aria-hidden="true" class="how__line"></li>
<li class="how__step reveal"><span class="num num--lg">2</span>Pilih lowongan</li><li aria-hidden="true" class="how__line"></li>
<li class="how__step reveal"><span class="num num--lg">3</span>Proses</li><li aria-hidden="true" class="how__line"></li>
<li class="how__step reveal"><span class="num num--lg">4</span>Lihat hasil</li>
</ol>
</div>
</section>
</main>
<footer class="site-footer">
<div class="container">
<div class="site-footer__grid">
<div><a class="brand brand--light" href="<?= esc(url('index.php')) ?>"><img alt="" height="32" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" width="32"/><span>Paskarir</span></a><p class="site-footer__about">Cek kesesuaianmu dengan lowongan kerja secara mudah, cepat, dan tanpa unggah berkas.</p></div>
<nav aria-labelledby="ft-menu"><h2 class="site-footer__title" id="ft-menu">Menu</h2><ul class="site-footer__list"><li><a href="<?= esc(url('index.php')) ?>">Beranda</a></li><li><a href="#fitur">Lowongan</a></li><li><a href="#cara-kerja">Cara Kerja</a></li></ul></nav>
<nav aria-labelledby="ft-bantuan"><h2 class="site-footer__title" id="ft-bantuan">Bantuan</h2><ul class="site-footer__list"><li><a href="#cara-kerja">Panduan Penggunaan</a></li><li><a href="<?= esc(url('user/lupa_password.php')) ?>">Lupa Password</a></li><li><a href="#">Syarat dan Ketentuan</a></li><li><a href="#">Kebijakan Privasi</a></li></ul></nav>
<div><h2 class="site-footer__title">Hubungi Admin</h2>
<address class="site-footer__contact"><span><span aria-hidden="true" class="icon icon--mail"></span> admin@email.com</span><span>+62 8xx xxxx xxxx</span><span>Senin - Jumat, 08.00 - 16.00</span><a class="btn btn--light btn--sm" href="mailto:admin@email.com"><span aria-hidden="true" class="icon icon--mail"></span> Hubungi Admin</a></address></div>
</div>
<p class="site-footer__copy">© 2026 Paskarir | Kelompok 12 - 5IF1 | Semua hak dilindungi</p>
</div>
</footer>
<script defer src="<?= esc(url('../../backend/js/user/landing.js')) ?>"></script>
</body>
</html>
