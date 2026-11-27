<?php
/**
 * super-admin/akun_form.php - Tambah (CREATE) dan ubah (UPDATE) akun, termasuk role dan status. Khusus Super Admin.
 * Role dan status dicek terhadap daftar yang diizinkan (whitelist), bukan dipercaya begitu saja dari form.
 */
require_once __DIR__ . '/../shared/helpers.php';

$user = require_role(['Super Admin']);
$pdo  = db();
$id   = get_int('id');

$in = ['nama' => '', 'email' => '', 'telepon' => '-', 'role' => 'User', 'status' => 'Aktif'];
$lama = null;
if ($id !== null) {
    $stmt = $pdo->prepare('SELECT id, nama, email, telepon, role, status FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $lama = $stmt->fetch();
    if (!$lama) {
        flash('error', 'Akun tidak ditemukan.');
        redirect(url('super-admin/akun.php'));
    }
    $in = ['nama' => $lama['nama'], 'email' => $lama['email'], 'telepon' => $lama['telepon'], 'role' => $lama['role'], 'status' => $lama['status']];
}

$errors = [];
if (is_post()) {
    csrf_check();
    $in['nama']    = post_str('nama');
    $in['email']   = strtolower(post_str('email'));
    $in['telepon'] = post_str('telepon') === '' ? '-' : post_str('telepon');
    $in['role']    = post_str('role');
    $in['status']  = post_str('status');
    $password      = (string) ($_POST['password'] ?? '');

    if ($in['nama'] === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (!valid_nama($in['nama'])) {
        $errors['nama'] = 'Nama 3-100 karakter dan hanya berisi huruf dan spasi.';
    }

    if ($in['email'] === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!valid_email($in['email'])) {
        $errors['email'] = 'Format email tidak valid.';
    } else {
        $cek = $pdo->prepare('SELECT 1 FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $cek->execute([':email' => $in['email'], ':id' => $id ?? 0]);
        if ($cek->fetchColumn()) {
            $errors['email'] = 'Email ini sudah dipakai akun lain.';
        }
    }

    if (!valid_telepon($in['telepon'])) {
        $errors['telepon'] = 'Nomor telepon 6-20 karakter (angka, +, -, spasi).';
    }
    if (!in_array($in['role'], ROLES, true)) {
        $errors['role'] = 'Role tidak valid.';
    }
    if (!in_array($in['status'], ['Aktif', 'Nonaktif'], true)) {
        $errors['status'] = 'Status tidak valid.';
    }

    // Password wajib saat tambah; saat ubah boleh kosong (berarti tidak diganti)
    if ($id === null && $password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif ($password !== '' && !password_kuat($password)) {
        $errors['password'] = 'Password minimal 8 karakter dan memuat huruf serta angka.';
    }

    // Aturan bisnis: sistem harus selalu punya minimal satu Super Admin aktif
    if (!$errors && $lama && $lama['role'] === 'Super Admin' && $lama['status'] === 'Aktif'
        && ($in['role'] !== 'Super Admin' || $in['status'] !== 'Aktif')) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'Super Admin' AND status = 'Aktif' AND id <> :id");
        $stmt->execute([':id' => $lama['id']]);
        if ((int) $stmt->fetchColumn() === 0) {
            $errors['role'] = 'Ini Super Admin aktif terakhir. Role dan statusnya tidak bisa diubah.';
        }
    }

    if (!$errors) {
        try {
            if ($id === null) {
                $pdo->prepare('INSERT INTO users (nama, email, password_hash, telepon, role, status)
                               VALUES (:nama, :email, :hash, :telepon, :role, :status)')
                    ->execute([':nama' => $in['nama'], ':email' => $in['email'], ':hash' => password_hash($password, PASSWORD_DEFAULT),
                               ':telepon' => $in['telepon'], ':role' => $in['role'], ':status' => $in['status']]);
                flash('success', 'Akun berhasil ditambahkan.');
            } else {
                $sql = 'UPDATE users SET nama = :nama, email = :email, telepon = :telepon, role = :role, status = :status';
                $par = [':nama' => $in['nama'], ':email' => $in['email'], ':telepon' => $in['telepon'],
                        ':role' => $in['role'], ':status' => $in['status'], ':id' => $id];
                if ($password !== '') {
                    $sql .= ', password_hash = :hash';
                    $par[':hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $pdo->prepare($sql . ' WHERE id = :id')->execute($par);
                flash('success', 'Akun berhasil diperbarui.');
            }
            redirect(url('super-admin/akun.php'));
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['email'] = 'Email ini sudah dipakai akun lain.';
            } else {
                throw $e;
            }
        }
    }
}

$judul = $id === null ? 'Tambah Akun' : 'Ubah Akun';
$halamanAktif = 'super-admin/akun.php';
require __DIR__ . '/../shared/header.php';
?>
<header class="page-header">
<h1><?= esc($judul) ?></h1>
<p class="page-header__desc">Buat atau ubah akun dan tentukan perannya. Akun Sub Admin hanya dibuat oleh Super Admin.</p>
</header>
<div class="layout-split layout-split--form">
<section aria-label="Form akun" class="card">
<form class="form-grid" method="post" novalidate>
<?= csrf_field() ?>
<div class="form-group form-group--full">
<label class="label" for="nama">Nama lengkap</label>
<div class="control"><span aria-hidden="true" class="icon icon--user"></span><input class="input" id="nama" name="nama" type="text" maxlength="100" placeholder="Nama lengkap" value="<?= esc($in['nama']) ?>"></div>
<?= err_html($errors, 'nama') ?>
</div>
<div class="form-group form-group--full">
<label class="label" for="email">Email</label>
<input class="input" id="email" name="email" type="email" maxlength="120" placeholder="nama@email.com" style="padding-left:16px" value="<?= esc($in['email']) ?>">
<?= err_html($errors, 'email') ?>
</div>
<div class="form-group">
<label class="label" for="telepon">Telepon</label>
<input class="input" id="telepon" name="telepon" type="text" maxlength="20" style="padding-left:16px" value="<?= esc($in['telepon']) ?>">
<?= err_html($errors, 'telepon') ?>
</div>
<div class="form-group">
<label class="label" for="role">Role</label>
<select class="select" id="role" name="role"><?php foreach (ROLES as $r): ?><option value="<?= esc($r) ?>" <?= $r === $in['role'] ? 'selected' : '' ?>><?= esc($r) ?></option><?php endforeach; ?></select>
<?= err_html($errors, 'role') ?>
</div>
<div class="form-group">
<label class="label" for="status">Status</label>
<select class="select" id="status" name="status"><?php foreach (['Aktif', 'Nonaktif'] as $s): ?><option value="<?= $s ?>" <?= $s === $in['status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
<?= err_html($errors, 'status') ?>
</div>
<div class="form-group">
<label class="label" for="password"><?= $id === null ? 'Password' : 'Reset password (opsional)' ?></label>
<span class="pw-wrap"><input class="input" id="password" name="password" type="password" autocomplete="new-password" placeholder="Min. 8 karakter"><button aria-controls="password" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="password" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span>
<p class="field__hint"><?= $id === null ? 'Minimal 8 karakter, memuat huruf dan angka.' : 'Kosongkan bila tidak ingin mengganti password.' ?></p>
<?= err_html($errors, 'password') ?>
</div>
<div class="form-group--full form-actions form-actions--end">
<a class="btn btn--outline" href="<?= esc(url('super-admin/akun.php')) ?>"><span aria-hidden="true" class="icon icon--close"></span> Batal</a>
<button class="btn btn--primary" type="submit"><span aria-hidden="true" class="icon icon--plus"></span> Simpan Akun</button>
</div>
</form>
</section>
<aside class="card card--muted info-card"><span aria-hidden="true" class="icon icon--shield"></span><p class="info-card__text">Pilih peran sesuai kebutuhan. Super Admin dapat mengelola semua data dan akun, Sub Admin hanya mengelola lowongan dan panduan, User hanya menggunakan klasifikasi.</p></aside>
</div>
<?php require __DIR__ . '/../shared/footer.php'; ?>
