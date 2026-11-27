<?php
/**
 * profil.php - Lihat dan ubah profil sendiri (UPDATE nama dan telepon). Dipakai bersama ketiga role (folder dan role ditentukan wrapper).
 * Email dan role tidak bisa diubah di sini, sehingga pengguna tidak bisa menaikkan hak aksesnya sendiri.
 */
require_once __DIR__ . '/../helpers.php';

if (!isset($area, $peran)) {   // hanya boleh dipanggil dari wrapper di folder role
    http_response_code(404);
    exit;
}

$user = require_role([$peran]);
$errors = [];
$in = ['nama' => $user['nama'], 'telepon' => $user['telepon']];

if (is_post()) {
    csrf_check();
    $in['nama']    = post_str('nama');
    $in['telepon'] = post_str('telepon') === '' ? '-' : post_str('telepon');

    if ($in['nama'] === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (!valid_nama($in['nama'])) {
        $errors['nama'] = 'Nama 3-100 karakter dan hanya berisi huruf dan spasi.';
    }
    if (!valid_telepon($in['telepon'])) {
        $errors['telepon'] = 'Nomor telepon 6-20 karakter (angka, +, -, spasi).';
    }

    if (!$errors) {
        db()->prepare('UPDATE users SET nama = :nama, telepon = :telepon WHERE id = :id')
            ->execute([':nama' => $in['nama'], ':telepon' => $in['telepon'], ':id' => $user['id']]);
        flash('success', 'Profil berhasil diperbarui.');
        redirect(url($area . '/profil.php'));
    }
}

$judul = 'Profil';
$halamanAktif = $area . '/profil.php';
require __DIR__ . '/../header.php';
?>
<?php if (($user['role'] ?? '') === 'User'):
  $st = db()->prepare("SELECT created_at FROM users WHERE id = :id"); $st->execute([':id' => $user['id']]); $gabung = (string) $st->fetchColumn();
  $st = db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(label='SESUAI'),0) sesuai FROM riwayat_klasifikasi WHERE user_id = :id"); $st->execute([':id' => $user['id']]); $rk = $st->fetch();
  $gabungTampil = $gabung !== '' ? date('d/m/Y', strtotime($gabung)) : '-';
?>
<div class="page-head"><h1>Profil Saya</h1><p>Lihat dan ubah data akunmu. Password diubah lewat tombol Ubah Password.</p></div>
<div class="split split--profile">
<aside aria-label="Ringkasan akun" class="card profile-card"><span class="avatar avatar--lg"><span aria-hidden="true" class="icon icon--user"></span></span><h2 class="card__title"><?= esc($user['nama']) ?></h2><p class="muted"><?= esc($user['email']) ?></p><span class="badge badge--primary">User</span>
<dl class="profile-card__meta"><div><dt>Bergabung sejak</dt><dd><?= esc($gabungTampil) ?></dd></div><div><dt>Total klasifikasi</dt><dd><?= (int) $rk['total'] ?></dd></div><div><dt>Hasil Sesuai</dt><dd><?= (int) $rk['sesuai'] ?></dd></div></dl></aside>
<div class="stack">
<section aria-labelledby="akun" class="card"><h2 class="card__title card__title--gap" id="akun">Data akun</h2>
<form class="form" method="post" novalidate><?= csrf_field() ?>
<div class="form-row"><div class="field"><label class="field__label" for="nama">Nama lengkap</label><input autocomplete="name" class="input" id="nama" name="nama" maxlength="100" value="<?= esc($in['nama']) ?>"><?= err_html($errors, 'nama') ?></div><div class="field"><label class="field__label" for="email">Email</label><input class="input" id="email" type="email" readonly value="<?= esc($user['email']) ?>"></div></div>
<div class="form-row"><div class="field"><label class="field__label" for="telepon">Telepon</label><input class="input" id="telepon" name="telepon" maxlength="20" value="<?= esc($in['telepon']) ?>"><?= err_html($errors, 'telepon') ?></div><div class="field"><label class="field__label" for="peran">Peran (hanya dibaca)</label><input class="input" id="peran" readonly value="User"></div></div>
<div class="form__actions"><button class="btn btn--primary btn--lg" type="submit"><span aria-hidden="true" class="icon icon--check"></span> Simpan Perubahan</button><a class="btn btn--outline btn--lg" href="<?= esc(url('user/profil.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span> Batal</a></div>
</form></section>
<section aria-labelledby="aman" class="card security"><div><h2 class="card__title" id="aman">Keamanan akun</h2><p>Ubah password secara berkala agar akunmu tetap aman.</p></div><a class="btn btn--primary btn--lg" href="<?= esc(url('user/ubah_password.php')) ?>"><span aria-hidden="true" class="icon icon--lock"></span> Ubah Password</a></section>
</div>
</div>
<?php else: ?>
<header class="page-header">
<h1>Profil</h1>
<p class="page-header__desc">Lihat dan ubah data akunmu.</p>
</header>
<section aria-label="Ringkasan profil" class="card profile-card">
<span aria-hidden="true" class="profile-card__avatar"><?= esc(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?></span>
<div>
<p class="profile-card__name"><?= esc($user['nama']) ?></p>
<p class="muted" style="color:var(--color-muted);font-size:var(--fs-small)"><?= esc($user['email']) ?></p>
<span class="badge badge--info"><?= esc($user['role']) ?></span>
</div>
</section>
<div class="layout-split layout-split--guide">
<section aria-labelledby="data-judul" class="card">
<h2 id="data-judul">Data Akun</h2>
<form class="form-grid" method="post" novalidate>
<?= csrf_field() ?>
<div class="form-group">
<label class="label" for="nama">Nama lengkap</label>
<div class="control"><span aria-hidden="true" class="icon icon--user"></span><input class="input" id="nama" name="nama" type="text" maxlength="100" value="<?= esc($in['nama']) ?>"></div>
<?= err_html($errors, 'nama') ?>
</div>
<div class="form-group">
<label class="label" for="p-email">Email</label>
<input class="input" id="p-email" type="email" value="<?= esc($user['email']) ?>" disabled style="padding-left:16px">
</div>
<div class="form-group">
<label class="label" for="telepon">Telepon</label>
<input class="input" id="telepon" name="telepon" type="text" maxlength="20" value="<?= esc($in['telepon']) ?>" style="padding-left:16px">
<?= err_html($errors, 'telepon') ?>
</div>
<div class="form-group">
<label class="label" for="p-role">Role</label>
<input class="input" disabled id="p-role" type="text" value="<?= esc($user['role']) ?>" style="padding-left:16px">
</div>
<div class="form-group form-group--full form-actions form-actions--end"><button class="btn btn--primary" type="submit"><span aria-hidden="true" class="icon icon--check"></span> Simpan Perubahan</button></div>
</form>
</section>
<section aria-labelledby="pw-judul" class="card">
<h2 id="pw-judul">Ubah Password</h2>
<p class="muted" style="color:var(--color-muted);font-size:var(--fs-small);margin-bottom:var(--space-4)">Ganti password akunmu secara berkala agar tetap aman.</p>
<a class="btn btn--primary btn--block" href="<?= esc(url($area . '/ubah_password.php')) ?>"><span aria-hidden="true" class="icon icon--lock"></span> Ubah Password</a>
</section>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../footer.php'; ?>
