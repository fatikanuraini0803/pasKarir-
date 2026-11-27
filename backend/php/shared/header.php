<?php
/** Kerangka aplikasi: memakai struktur shell yang sama dengan frontend HTML. */
$flashPesan = ambil_flash();
$role = $user['role'] ?? '';
$areaMenu = $role !== '' ? area_of($role) : '';
$aktif = $halamanAktif ?? '';
$userMenu = [
 'user/dashboard.php'=>['Dashboard','home'],
 'user/klasifikasi.php'=>['Klasifikasi','check'],
 'user/lowongan.php'=>['Lowongan','briefcase'],
 'user/riwayat.php'=>['Riwayat','clock'],
];
$subMenu = [
 'sub-admin/index.php'=>['Dashboard','home'],
 'sub-admin/lowongan.php'=>['Kelola Lowongan','briefcase'],
 'sub-admin/lowongan_form.php'=>['Kelola Lowongan','briefcase'],
 'sub-admin/panduan.php'=>['Kelola Panduan / Rekomendasi','document'],
 'sub-admin/data-pelatihan.php'=>['Data Pelatihan','database'],
];
$superMenu = [
 'super-admin/index.php'=>['Dashboard','home'],
 'super-admin/akun.php'=>['Akun dan Hak Akses','users'],
 'super-admin/akun_form.php'=>['Akun dan Hak Akses','users'],
 'super-admin/lowongan.php'=>['Lowongan dan Persyaratan','briefcase'],
 'super-admin/lowongan_form.php'=>['Lowongan dan Persyaratan','briefcase'],
 'super-admin/data-pelatihan.php'=>['Data Pelatihan','database'],
 'super-admin/model-decision-tree.php'=>['Pengaturan Model','target'],
];
$menu = $role === 'User' ? $userMenu : ($role === 'Sub Admin' ? $subMenu : $superMenu);
$menu = array_filter($menu, fn($k) => substr($k, -9) !== '_form.php', ARRAY_FILTER_USE_KEY);
$aktifMenu = preg_replace('/_form\.php$/', '.php', $aktif);
$profileUrl = $areaMenu . '/profil.php';
$passUrl = $areaMenu . '/ubah_password.php';
$ikon = ['Dashboard'=>'home','Klasifikasi'=>'check','Lowongan'=>'briefcase','Riwayat'=>'clock','Kelola Lowongan'=>'briefcase','Akun dan Hak Akses'=>'users','Lowongan dan Persyaratan'=>'briefcase','Data Pelatihan'=>'database','Pengaturan Model'=>'target'];
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($judul ?? 'PasKarir') ?> | PasKarir</title>
<link rel="icon" href="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php if ($role === 'User' || $role === ''): ?>
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/variables.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/base.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/layout.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/components.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/pages.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/assets/icons/icons-user.css')) ?>">
<?php else: ?>
<link rel="stylesheet" href="<?= esc(url('../../frontend/user/css/variables.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/assets/icons/icons-admin.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/assets/css/admin-common.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../../frontend/' . $areaMenu . '/css/pages.css')) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= esc(url('../../frontend/assets/css/feedback.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('assets/frontend-php-bridge.css')) ?>">
</head><body class="<?= $role === 'User' ? 'role-user' : (in_array($role, ['Sub Admin', 'Super Admin'], true) ? 'role-admin' : 'auth') ?>">
<?php if ($role === 'User'): ?>
<a class="skip-link" href="#main-content">Lewati ke konten utama</a>
<div class="app">
<input aria-label="Buka menu navigasi" class="nav-toggle" id="nav-toggle" type="checkbox">
<aside class="sidebar" aria-label="Menu utama">
<a class="brand" href="<?= esc(url('user/dashboard.php')) ?>"><img alt="" height="32" width="32" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>"><span>PasKarir</span></a>
<nav class="sidebar__menu" aria-label="Menu aplikasi"><ul class="sidebar__nav">
<?php foreach ($userMenu as $file=>$item): ?><li><a class="nav-link <?= ($aktif === $file ? 'is-active' : '') ?>" <?= $aktif === $file ? 'aria-current="page"' : '' ?> href="<?= esc(url($file)) ?>"><span aria-hidden="true" class="icon icon--<?= esc($item[1]) ?>"></span><span><?= esc($item[0]) ?></span></a></li><?php endforeach; ?>
</ul></nav>
<div class="sidebar__footer"><a class="nav-link nav-link--danger" href="#keluar"><span class="icon icon--logout" aria-hidden="true"></span><span>Keluar</span></a></div>
</aside><div class="app__main">
<header class="topbar"><div class="topbar__left"><label class="icon-btn nav-toggle-label" for="nav-toggle" aria-label="Menu"><span class="icon icon--menu"></span></label><nav aria-label="Breadcrumb"><ol class="breadcrumb"><li><span class="icon icon--home"></span></li><li><a href="<?= esc(url('user/dashboard.php')) ?>">Beranda</a></li><li class="breadcrumb__sep">/</li><li aria-current="page"><?= esc($judul ?? 'Halaman') ?></li></ol></nav></div>
<details class="profile"><summary class="profile__toggle"><span class="profile__name"><strong><?= esc($user['nama']) ?></strong><span>User</span></span><span class="avatar"><span class="icon icon--user"></span></span><span class="icon icon--chevron"></span></summary><div class="profile__menu"><div class="profile__info"><strong><?= esc($user['nama']) ?></strong><small><?= esc($user['email']) ?></small><span class="badge badge--primary">User</span></div><a href="<?= esc(url($profileUrl)) ?>"><span class="icon icon--user"></span>Profil Saya</a><a href="<?= esc(url($passUrl)) ?>"><span class="icon icon--lock"></span>Ubah Password</a><a href="#keluar"><span class="icon icon--logout"></span>Keluar</a></div></details></header>
<main class="content" id="main-content">
<?php elseif (in_array($role, ['Sub Admin','Super Admin'], true)): ?>
<a class="skip-link" href="#main-content">Lewati ke konten utama</a><div class="app">
<aside class="sidebar"><a class="brand" href="<?= esc(url(home_for($role))) ?>"><img class="brand__img" alt="" height="40" width="40" src="<?= esc(url('../../frontend/assets/img/logo.svg')) ?>"><span class="brand__name">Paskarir</span></a>
<nav class="nav" aria-label="Menu utama"><ul class="nav__list">
<?php foreach ($menu as $file=>$item): ?><li><a class="nav__link <?= ($aktifMenu === $file ? 'is-active' : '') ?>" <?= $aktifMenu === $file ? 'aria-current="page"' : '' ?> href="<?= esc(url($file)) ?>"><span class="icon icon--<?= esc($item[1]) ?>" aria-hidden="true"></span><?= esc($item[0]) ?></a></li><?php endforeach; ?>

</ul></nav><div class="sidebar__footer"><a class="logout" href="#keluar"><span class="icon icon--logout"></span>Keluar</a></div></aside>
<header class="topbar"><div class="topbar__start"><button class="menu-btn" type="button" aria-label="Buka/tutup menu"><span class="icon icon--menu"></span></button><nav class="breadcrumb" aria-label="Breadcrumb"><ol class="breadcrumb__list"><li class="breadcrumb__item"><a href="<?= esc(url(home_for($role))) ?>"><?= $role === 'Sub Admin' ? 'Panel Admin' : 'Super Admin' ?></a></li><li class="breadcrumb__item" aria-current="page"><?= esc($judul ?? 'Dashboard') ?></li></ol></nav></div>
<details class="profile-menu"><summary class="profile"><span class="profile__info"><span class="profile__name"><?= esc($user['nama']) ?></span><span class="profile__role"><?= esc($role) ?></span></span><span class="avatar"><span class="icon icon--user"></span></span><span class="icon icon--chevron"></span></summary><div class="profile-menu__list"><div class="profile-menu__head"><strong><?= esc($user['nama']) ?></strong><small><?= esc($user['email']) ?></small><span class="badge badge--info"><?= esc($role) ?></span></div><a href="<?= esc(url($profileUrl)) ?>"><span class="icon icon--user"></span>Profil</a><?php if ($role === 'Super Admin'): ?><a href="<?= esc(url('super-admin/akun_form.php')) ?>"><span class="icon icon--plus"></span>Tambah Akun</a><?php endif; ?><a href="<?= esc(url($passUrl)) ?>"><span class="icon icon--lock"></span>Ubah Password</a><a class="is-danger" href="#keluar"><span class="icon icon--logout"></span>Keluar</a></div></details></header>
<main class="main" id="main-content">
<?php else: ?>
<main class="container">
<?php endif; ?>
<?php if ($flashPesan): ?><div class="alert alert--<?= esc($flashPesan['tipe']) ?>" role="alert"><?= esc($flashPesan['pesan']) ?></div><?php endif; ?>
