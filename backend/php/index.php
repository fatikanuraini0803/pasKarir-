<?php
/**
 * index.php - Satu-satunya pintu masuk aplikasi: http://localhost/Paskarir/backend/php/
 * - Sudah login  -> diarahkan ke dashboard sesuai role.
 * - Belum login  -> menampilkan landing page (beranda.php) dengan desain frontend.
 */
require_once __DIR__ . '/shared/helpers.php';
$user = current_user();
if ($user) {
    redirect(url(home_for($user['role'])));
}
require __DIR__ . '/beranda.php';
