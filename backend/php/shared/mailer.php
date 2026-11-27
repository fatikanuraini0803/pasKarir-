<?php
/**
 * mailer.php - Pengirim email lewat SMTP (tanpa library tambahan, murni PHP).
 * Mendukung STARTTLS (port 587) dan SSL langsung (port 465) dengan AUTH LOGIN (cocok untuk Gmail App Password).
 */
require_once __DIR__ . '/config_email.php';

/** Cari file sertifikat CA (XAMPP Windows) agar verifikasi SSL berjalan. */
function smtp_cafile(): ?string
{
    $ini = (string) ini_get('openssl.cafile');
    if ($ini !== '' && is_file($ini)) {
        return $ini;
    }
    $bin = defined('PHP_BINARY') ? (string) PHP_BINARY : '';
    $calon = [
        $bin !== '' ? dirname($bin) . '/curl-ca-bundle.crt' : '',
        $bin !== '' ? dirname($bin, 2) . '/apache/bin/curl-ca-bundle.crt' : '',
        $bin !== '' ? dirname($bin) . '/extras/ssl/cacert.pem' : '',
        'C:/xampp/apache/bin/curl-ca-bundle.crt',
        'C:/xampp/php/extras/ssl/cacert.pem',
    ];
    foreach ($calon as $f) {
        if ($f !== '' && is_file($f)) {
            return $f;
        }
    }
    return null;
}

/** Baca balasan SMTP (bisa banyak baris). @return array [kode, teks] */
function smtp_baca($fp): array
{
    $teks = '';
    while (($baris = fgets($fp, 1024)) !== false) {
        $teks .= $baris;
        if (strlen($baris) < 4 || $baris[3] === ' ') {
            break;
        }
    }
    return [(int) substr($teks, 0, 3), trim($teks)];
}

/** Kirim satu perintah lalu pastikan kode balasan sesuai. Melempar RuntimeException bila tidak. */
function smtp_cmd($fp, string $cmd, array $kodeOk, string $sensor = ''): string
{
    if ($cmd !== '') {
        fwrite($fp, $cmd . "\r\n");
    }
    [$kode, $teks] = smtp_baca($fp);
    if (!in_array($kode, $kodeOk, true)) {
        throw new RuntimeException('SMTP menolak' . ($sensor !== '' ? " ($sensor)" : '') . ': ' . $teks);
    }
    return $teks;
}

/**
 * Mengirim email HTML (dengan versi teks biasa).
 * @return array [bool berhasil, string pesan galat]
 */
function kirim_email(string $ke, string $subjek, string $html, string $teks): array
{
    if (SMTP_USER === '' || SMTP_PASS === '') {
        return [false, 'Email pengirim belum diatur. Isi SMTP_USER dan SMTP_PASS di backend/php/shared/config_email.php.'];
    }
    if (!filter_var($ke, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Alamat tujuan tidak valid.'];
    }

    $ssl = ['verify_peer' => SMTP_VERIFY_SSL, 'verify_peer_name' => SMTP_VERIFY_SSL, 'allow_self_signed' => !SMTP_VERIFY_SSL];
    if (SMTP_VERIFY_SSL && ($ca = smtp_cafile())) {
        $ssl['cafile'] = $ca;
    }
    $ctx = stream_context_create(['ssl' => $ssl]);
    $fp = null;

    try {
        $skema = SMTP_PORT === 465 ? 'ssl' : 'tcp';
        $fp = @stream_socket_client($skema . '://' . SMTP_HOST . ':' . SMTP_PORT, $no, $str, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new RuntimeException("Tidak bisa terhubung ke server email ($str). Periksa internet / firewall.");
        }
        stream_set_timeout($fp, 20);

        smtp_cmd($fp, '', [220]);
        smtp_cmd($fp, 'EHLO localhost', [250]);
        if (SMTP_PORT !== 465) {
            smtp_cmd($fp, 'STARTTLS', [220]);
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Koneksi aman (TLS) gagal. Bila XAMPP menampilkan "certificate verify failed", lihat catatan SMTP_VERIFY_SSL di config_email.php.');
            }
            smtp_cmd($fp, 'EHLO localhost', [250]);
        }
        smtp_cmd($fp, 'AUTH LOGIN', [334]);
        smtp_cmd($fp, base64_encode(SMTP_USER), [334], 'username');
        smtp_cmd($fp, base64_encode(SMTP_PASS), [235], 'login gagal; pastikan memakai App Password, bukan password Gmail biasa');
        smtp_cmd($fp, 'MAIL FROM:<' . SMTP_USER . '>', [250]);
        smtp_cmd($fp, 'RCPT TO:<' . $ke . '>', [250, 251]);
        smtp_cmd($fp, 'DATA', [354]);

        $batas  = 'pk_' . bin2hex(random_bytes(12));
        $domain = substr(strrchr(SMTP_USER, '@') ?: '@localhost', 1);
        $b64 = fn(string $s) => chunk_split(base64_encode($s), 76, "\r\n");
        $pesan  = 'From: =?UTF-8?B?' . base64_encode(SMTP_NAMA) . '?= <' . SMTP_USER . ">\r\n"
                . 'To: <' . $ke . ">\r\n"
                . 'Subject: =?UTF-8?B?' . base64_encode($subjek) . "?=\r\n"
                . 'Date: ' . date('r') . "\r\n"
                . 'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . $domain . ">\r\n"
                . "MIME-Version: 1.0\r\n"
                . 'Content-Type: multipart/alternative; boundary="' . $batas . "\"\r\n\r\n"
                . '--' . $batas . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($teks)
                . '--' . $batas . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($html)
                . '--' . $batas . "--\r\n";
        fwrite($fp, $pesan . "\r\n.\r\n");
        [$kode, $balas] = smtp_baca($fp);
        if ($kode !== 250) {
            throw new RuntimeException('Pesan ditolak server: ' . $balas);
        }
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return [true, ''];
    } catch (Throwable $e) {
        if (is_resource($fp)) {
            @fclose($fp);
        }
        error_log('Kirim email gagal: ' . $e->getMessage());
        return [false, $e->getMessage()];
    }
}
