<?php
/**
 * logout.php - Mengakhiri sesi. Hanya lewat POST + token CSRF agar tidak bisa dipicu dari tautan situs lain.
 */
require_once __DIR__ . '/../shared/helpers.php';

if (!is_post()) {
    redirect(url('user/login.php'));
}
csrf_check();
hancurkan_sesi();
flash('success', 'Kamu sudah keluar.');
redirect(url('user/login.php'));
