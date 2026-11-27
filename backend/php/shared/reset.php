<?php
/**
 * reset.php - Logika bersama fitur Lupa Password: tabel token, pembuatan tautan, pengecekan token.
 * Token acak 64 karakter hanya dikirim lewat email; yang disimpan di database hanya hash SHA-256-nya.
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mailer.php';

/** Membuat tabel password_resets bila belum ada (aman dipanggil berulang). */
function pastikan_tabel_reset(): void
{
    static $sudah = false;
    if ($sudah) {
        return;
    }
    db()->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id    INT UNSIGNED NOT NULL,
        token_hash CHAR(64)     NOT NULL,
        expires_at DATETIME     NOT NULL,
        used_at    DATETIME     NULL,
        created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_reset_token (token_hash),
        KEY idx_reset_user (user_id),
        CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $sudah = true;
}

/** Alamat dasar aplikasi untuk tautan di email, mis. http://localhost/Paskarir */
function alamat_dasar(): string
{
    if (APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {   // cegah Host header palsu
        $host = 'localhost';
    }
    $skema = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    return $skema . '://' . $host . app_base();
}

/** Permintaan dari komputer lokal (untuk menampilkan galat teknis hanya saat pengembangan). */
function permintaan_lokal(): bool
{
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

/**
 * Membuat token reset untuk satu akun dan mengirim emailnya ke alamat akun itu.
 * @return array [bool terkirim, string galat]
 */
function kirim_tautan_reset(array $u): array
{
    pastikan_tabel_reset();
    $pdo = db();

    // Batasi permintaan per akun per jam.
    $hitung = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = :u AND created_at > (NOW() - INTERVAL 1 HOUR)');
    $hitung->execute([':u' => $u['id']]);
    if ((int) $hitung->fetchColumn() >= RESET_MAKS_PER_JAM) {
        return [true, ''];   // diam-diam: pesan ke pengguna tetap netral
    }

    $token = bin2hex(random_bytes(32));
    $pdo->prepare('DELETE FROM password_resets WHERE user_id = :u AND (used_at IS NOT NULL OR expires_at < NOW())')->execute([':u' => $u['id']]);
    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:u, :h, NOW() + INTERVAL ' . (int) RESET_BERLAKU_MENIT . ' MINUTE)')
        ->execute([':u' => $u['id'], ':h' => hash('sha256', $token)]);

    $tautan = alamat_dasar() . '/backend/php/user/reset_password.php?token=' . $token;
    $nama   = esc($u['nama']);
    $menit  = (int) RESET_BERLAKU_MENIT;

    $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:auto;color:#0f172a">'
          . '<h2 style="color:#2563eb;margin:0 0 16px">PasKarir</h2>'
          . "<p>Halo <b>$nama</b>,</p>"
          . '<p>Kami menerima permintaan untuk mengatur ulang password akun PasKarir yang terhubung dengan email ini. Klik tombol di bawah untuk membuat password baru.</p>'
          . '<p style="margin:24px 0"><a href="' . esc($tautan) . '" style="background:#2563eb;color:#fff;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:bold;display:inline-block">Atur Ulang Password</a></p>'
          . "<p>Tautan ini berlaku <b>$menit menit</b> dan hanya bisa dipakai sekali.</p>"
          . '<p style="font-size:13px;color:#475569">Jika tombol tidak berfungsi, salin alamat ini ke browser:<br>' . esc($tautan) . '</p>'
          . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0">'
          . '<p style="font-size:13px;color:#475569">Bukan Anda yang meminta? Abaikan email ini; password Anda tidak akan berubah.</p></div>';
    $teks = "Halo {$u['nama']},\n\nKami menerima permintaan atur ulang password akun PasKarir Anda.\n"
          . "Buka tautan berikut untuk membuat password baru (berlaku $menit menit, sekali pakai):\n\n$tautan\n\n"
          . "Bukan Anda yang meminta? Abaikan email ini; password Anda tidak akan berubah.\n";

    return kirim_email($u['email'], 'Atur ulang password PasKarir', $html, $teks);
}

/** Mencari token yang masih berlaku. @return array|null baris (id, user_id, email, nama) */
function cari_token_reset(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    pastikan_tabel_reset();
    $st = db()->prepare("SELECT pr.id, pr.user_id, u.email, u.nama
                           FROM password_resets pr JOIN users u ON u.id = pr.user_id
                          WHERE pr.token_hash = :h AND pr.used_at IS NULL AND pr.expires_at > NOW() AND u.status = 'Aktif' LIMIT 1");
    $st->execute([':h' => hash('sha256', $token)]);
    return $st->fetch() ?: null;
}
