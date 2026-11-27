<?php
/**
 * setup_akun.php - Membuat / mereset akun contoh memakai password_hash milik PHP sendiri,
 * sehingga pasti cocok dengan login. Hanya bisa dibuka dari komputer lokal.
 * Buka: http://localhost/Paskarir/backend/php/setup_akun.php
 * HAPUS file ini setelah akun berhasil dibuat.
 */
require_once __DIR__ . '/shared/helpers.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Halaman ini hanya bisa dibuka dari komputer lokal.');
}

$akun = [
    ['Super Admin',  'superadminpaskarir@gmail.com', 'SuperAdmin123', 'Super Admin'],
    ['Sub Admin',    'subadminpaskarir@gmail.com',   'SubAdmin123',   'Sub Admin'],
    ['User Demo',    'userpaskarir@gmail.com',       'User12345',     'User'],
    ['Super Admin 2', 'superadmin@email.com',        'Super12345',    'Super Admin'],
    ['Sub Admin 2',   'subadmin@email.com',          'Admin12345',    'Sub Admin'],
    ['Nama User',     'nama@email.com',              'Password123',   'User'],
];

$hasil = [];
$galat = '';
try {
    $pdo = db();
    $simpan = $pdo->prepare(
        "INSERT INTO users (nama, email, password_hash, telepon, role, status)
         VALUES (:n, :e, :h, '-', :r, 'Aktif')
         ON DUPLICATE KEY UPDATE nama = VALUES(nama), password_hash = VALUES(password_hash),
                                 role = VALUES(role), status = 'Aktif'"
    );
    $baca = $pdo->prepare('SELECT password_hash, role, status FROM users WHERE email = :e LIMIT 1');
    foreach ($akun as $a) {
        $simpan->execute([':n' => $a[0], ':e' => strtolower($a[1]), ':h' => password_hash($a[2], PASSWORD_DEFAULT), ':r' => $a[3]]);
        $baca->execute([':e' => strtolower($a[1])]);
        $b = $baca->fetch();
        $hasil[] = [$a[1], $a[2], $a[3], $b && password_verify($a[2], $b['password_hash']) && $b['status'] === 'Aktif'];
    }
} catch (Throwable $e) {
    $galat = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>Setup Akun PasKarir</title>
<style>body{font-family:Arial,sans-serif;max-width:760px;margin:40px auto;padding:0 16px}
table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:8px;text-align:left}
.ok{color:#15803d;font-weight:bold}.gagal{color:#b91c1c;font-weight:bold}</style></head>
<body>
<h1>Setup Akun PasKarir</h1>
<?php if ($galat !== ''): ?>
  <p class="gagal">Gagal: <?= esc($galat) ?></p>
  <p>Pastikan database <code>paskarir_exam</code> sudah diimpor dari <code>database_lengkap.sql</code>.</p>
<?php else: ?>
  <p>Akun berikut sudah dibuat / direset. Silakan login di
     <a href="<?= esc(url('user/login.php')) ?>">halaman login</a>.</p>
  <table><tr><th>Email</th><th>Password</th><th>Role</th><th>Siap login</th></tr>
  <?php foreach ($hasil as $h): ?>
    <tr><td><?= esc($h[0]) ?></td><td><?= esc($h[1]) ?></td><td><?= esc($h[2]) ?></td>
        <td class="<?= $h[3] ? 'ok' : 'gagal' ?>"><?= $h[3] ? 'Ya' : 'Tidak' ?></td></tr>
  <?php endforeach; ?>
  </table>
  <p><strong>Penting:</strong> hapus file <code>setup_akun.php</code> setelah selesai.</p>
<?php endif; ?>
</body></html>
