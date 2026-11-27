<?php
/**
 * helpers.php - Fungsi bersama: sesi, hak akses per role, CSRF, escape output, validasi input.
 * Di-include paling awal oleh setiap halaman (user/, sub-admin/, super-admin/).
 */
require_once __DIR__ . '/koneksi.php';

const ROLES = ['User', 'Sub Admin', 'Super Admin'];
// Folder halaman tiap role (backend/php/user, backend/php/sub-admin, backend/php/super-admin)
const AREA_ROLE = ['User' => 'user', 'Sub Admin' => 'sub-admin', 'Super Admin' => 'super-admin'];
const TINGKAT_PENDIDIKAN = ['SMA / SMK' => 1, 'D3' => 2, 'S1 / D4' => 3, 'S2' => 4];
const MAKS_PENGALAMAN = 50;
const SESI_MAKS_DIAM = 7200; // sesi berakhir bila tidak aktif 2 jam

/* ---------------- Header keamanan dan sesi ---------------- */
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,      // cookie sesi tidak bisa dibaca JavaScript
        'samesite' => 'Lax',     // cookie tidak ikut dikirim dari situs lain
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/* ---------------- Utilitas umum ---------------- */

/** Escape output HTML agar teks dari pengguna tidak dieksekusi sebagai HTML/JS (XSS). */
function esc($nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Alamat dasar aplikasi, mis. "/paskarir" (dihitung dari lokasi folder). */
function app_base(): string
{
    static $base = null;
    if ($base === null) {
        $doc  = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $root = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
        $base = ($doc !== '' && strncasecmp($root, $doc, strlen($doc)) === 0) ? substr($root, strlen($doc)) : '';
    }
    return $base;
}

function url(string $path = ''): string
{
    return app_base() . '/' . ltrim($path, '/');
}

function redirect(string $tujuan): void
{
    header('Location: ' . $tujuan);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/** Nilai teks dari $_POST (dipangkas spasi; bukan teks -> string kosong). */
function post_str(string $kunci): string
{
    $v = $_POST[$kunci] ?? '';
    return is_string($v) ? trim($v) : '';
}

function get_str(string $kunci): string
{
    $v = $_GET[$kunci] ?? '';
    return is_string($v) ? trim($v) : '';
}

/** Bilangan bulat dari $_GET, atau null bila tidak ada / tidak valid. */
function get_int(string $kunci): ?int
{
    $v = filter_var($_GET[$kunci] ?? null, FILTER_VALIDATE_INT);
    return $v === false ? null : $v;
}

/* ---------------- Pesan flash ---------------- */

function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function ambil_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
}

/** Menolak request POST yang tidak membawa token CSRF yang benar. */
function csrf_check(): void
{
    $kirim = $_POST['csrf'] ?? '';
    if (!is_string($kirim) || !hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        http_response_code(419);
        exit('Token keamanan tidak valid atau sudah kedaluwarsa. Kembali dan muat ulang halaman.');
    }
}

/* ---------------- Sesi, login, dan hak akses ---------------- */

/** Mengakhiri sesi lama dan memulai sesi kosong baru (dipakai saat logout / sesi habis). */
function hancurkan_sesi(): void
{
    $_SESSION = [];
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

/** Akun yang sedang login (dibaca ulang dari database), atau null. */
function current_user(): ?array
{
    if (empty($_SESSION['uid'])) {
        return null;
    }
    if (time() - ($_SESSION['aktif'] ?? time()) > SESI_MAKS_DIAM) {
        hancurkan_sesi();
        return null;
    }
    $_SESSION['aktif'] = time();

    $stmt = db()->prepare("SELECT id, nama, email, telepon, role, status FROM users WHERE id = :id AND status = 'Aktif' LIMIT 1");
    $stmt->execute([':id' => (int) $_SESSION['uid']]);
    $user = $stmt->fetch();
    if (!$user) {            // akun dihapus atau dinonaktifkan saat sesi berjalan
        hancurkan_sesi();
        return null;
    }
    $user['id'] = (int) $user['id'];
    return $user;
}

/** Nama folder halaman untuk sebuah role, mis. 'Sub Admin' -> 'sub-admin'. */
function area_of(string $role): string
{
    return AREA_ROLE[$role] ?? 'user';
}

/** Halaman awal sesuai role (relatif terhadap backend/php). */
function home_for(string $role): string
{
    return $role === 'User' ? 'user/dashboard.php' : area_of($role) . '/index.php';
}

/** Wajib login; bila belum, diarahkan ke halaman login. */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        flash('error', 'Silakan masuk terlebih dahulu.');
        redirect(url('user/login.php'));
    }
    return $user;
}

/** Wajib login DAN memiliki salah satu role; selain itu ditolak (403). */
function require_role(array $roleBoleh): array
{
    $user = require_login();
    if (!in_array($user['role'], $roleBoleh, true)) {
        http_response_code(403);
        exit('<!DOCTYPE html><meta charset="utf-8"><title>Akses ditolak</title>'
            . '<p style="font-family:sans-serif;padding:2rem">Akses ditolak: role Anda tidak berhak membuka halaman ini. '
            . '<a href="' . esc(url(home_for($user['role']))) . '">Kembali</a></p>');
    }
    return $user;
}

/* ---------------- Validasi input ---------------- */

function valid_email(string $v): bool
{
    return strlen($v) <= 120 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}

function valid_nama(string $v): bool
{
    return preg_match('/^[\p{L} .\'-]{3,100}$/u', $v) === 1;
}

/** Password kuat: minimal 8 karakter, memuat huruf dan angka. */
function password_kuat(string $v): bool
{
    return strlen($v) >= 8 && preg_match('/[A-Za-z]/', $v) === 1 && preg_match('/\d/', $v) === 1;
}

function valid_telepon(string $v): bool
{
    return $v === '-' || preg_match('/^[0-9+\- ]{6,20}$/', $v) === 1;
}

/** Elemen <p> pesan error untuk satu kolom form. */
function err_html(array $errors, string $kunci): string
{
    return isset($errors[$kunci]) ? '<p class="field__error">' . esc($errors[$kunci]) . '</p>' : '';
}

/**
 * Memecah teks "A, B, C" menjadi daftar keterampilan bersih tanpa duplikat.
 * @return array [daftar string[], pesan error atau null]
 */
function parse_keterampilan(string $teks, int $maks = 10): array
{
    $hasil = [];
    $lihat = [];
    foreach (explode(',', $teks) as $bagian) {
        $nama = trim(str_replace('|', ' ', $bagian));
        if ($nama === '') {
            continue;
        }
        if (mb_strlen($nama) > 60) {
            return [[], 'Satu keterampilan maksimal 60 karakter.'];
        }
        $kunci = mb_strtolower($nama);
        if (isset($lihat[$kunci])) {
            continue;
        }
        $lihat[$kunci] = true;
        $hasil[] = $nama;
    }
    if (count($hasil) === 0) {
        return [[], 'Tambahkan minimal satu keterampilan.'];
    }
    if (count($hasil) > $maks) {
        return [[], "Maksimal $maks keterampilan."];
    }
    return [$hasil, null];
}

/* ---------------- Pagination (Sebelumnya, 1, 2, Next) ---------------- */
const PER_HALAMAN = 5;   // minimal baris per halaman. Jumlah halaman selalu maksimal 2, jadi data banyak = tabel makin panjang

/** Baris per halaman agar total halaman tidak lebih dari 2. */
function per_halaman(int $total): int
{
    return max(PER_HALAMAN, (int) ceil($total / 2));
}

/** Memotong array menjadi satu halaman. Nomor halaman dibaca dari ?hal=. */
function paginasi(array $items): array
{
    $total = count($items);
    $per   = per_halaman($total);
    $info  = paginasi_info($total, (int) ($_GET['hal'] ?? 1), $per);
    return $info + ['items' => array_slice($items, ($info['hal'] - 1) * $per, $per)];
}

/** Info halaman saja (dipakai bila datanya sudah dipotong lewat SQL LIMIT). */
function paginasi_info(int $total, int $hal, int $perHal = PER_HALAMAN): array
{
    $jml = max(1, (int) ceil($total / $perHal));
    $hal = min(max(1, $hal), $jml);
    return ['total' => $total, 'hal' => $hal, 'jml' => $jml, 'per' => $perHal,
            'awal' => $total ? ($hal - 1) * $perHal + 1 : 0, 'akhir' => min($total, $hal * $perHal)];
}

/** Alamat halaman ke-N dengan mempertahankan pencarian/filter yang sedang aktif. */
function url_halaman(int $hal): string
{
    $q = $_GET;
    unset($q['edit']);
    if ($hal <= 1) { unset($q['hal']); } else { $q['hal'] = $hal; }
    return '?' . http_build_query($q);
}

/** Baris bawah tabel: info jumlah data + tombol [Sebelumnya] [1] [2] [Next]. Halaman aktif berwarna biru. */
function paginasi_html(array $pg, string $satuan = 'data', string $gaya = 'admin'): string
{
    $info  = 'Menampilkan ' . $pg['awal'] . '–' . $pg['akhir'] . ' dari ' . $pg['total'] . ' ' . esc($satuan);
    $user  = $gaya === 'user';
    $kAngka = $user ? 'pagination__link' : 'page-btn';
    $kNav   = $user ? 'pagination__link pagination__link--nav' : 'page-btn';
    $svg = fn(string $d) => '<svg aria-hidden="true" class="pager-ico" fill="none" height="14" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" viewBox="0 0 24 24" width="14"><path d="' . $d . '"/></svg>';
    $link = function (string $isi, int $tujuan, bool $aktif, string $kelas, string $aria, bool $sekarang = false): string {
        if ($sekarang) {
            return '<a aria-current="page" aria-label="' . esc($aria) . '" class="' . $kelas . '" href="' . esc(url_halaman($tujuan)) . '">' . $isi . '</a>';
        }
        if (!$aktif) {
            return '<span aria-disabled="true" aria-label="' . esc($aria) . '" class="' . $kelas . ' is-disabled">' . $isi . '</span>';
        }
        return '<a aria-label="' . esc($aria) . '" class="' . $kelas . '" href="' . esc(url_halaman($tujuan)) . '">' . $isi . '</a>';
    };
    $item = $user ? (fn(string $h) => '<li>' . $h . '</li>') : (fn(string $h) => $h);
    $nav  = $item($link($svg('m15 18-6-6 6-6') . ' Sebelumnya', $pg['hal'] - 1, $pg['hal'] > 1, $kNav, 'Halaman sebelumnya'));
    for ($h = 1; $h <= 2; $h++) {   // hanya dua nomor halaman
        $nav .= $item($link((string) $h, $h, $h <= $pg['jml'], $kAngka, 'Halaman ' . $h, $h === $pg['hal']));
    }
    $nav .= $item($link('Next ' . $svg('m9 18 6-6-6-6'), $pg['hal'] + 1, $pg['hal'] < $pg['jml'], $kNav, 'Halaman berikutnya'));
    if ($user) {
        return '<nav aria-label="Halaman tabel" class="pagination"><span>' . $info . '</span><ul class="pagination__list">' . $nav . '</ul></nav>';
    }
    return '<div class="table-footer"><p>' . $info . '</p><nav aria-label="Halaman tabel" class="pagination">' . $nav . '</nav></div>';
}

/* ---------------- Skala kesesuaian (persen Sesuai vs Tidak Sesuai) ---------------- */

/** Bar persentase Sesuai dan Tidak Sesuai. Persen Tidak Sesuai = 100 - Sesuai agar total selalu 100%. */
function skala_html(int $sesuai, int $tidak): string
{
    $total = $sesuai + $tidak;
    $pS = $total ? (int) round($sesuai / $total * 100) : 0;
    $pT = $total ? 100 - $pS : 0;
    $bar = '<div aria-hidden="true" class="skala__bar">'
         . ($pS ? '<span class="skala__seg skala__seg--ok" style="width:' . $pS . '%"></span>' : '')
         . ($pT ? '<span class="skala__seg skala__seg--no" style="width:' . $pT . '%"></span>' : '') . '</div>';
    $legend = '<ul class="skala__legend">'
         . '<li><span class="skala__dot skala__dot--ok"></span>Sesuai <b>' . $pS . '%</b> <small>(' . $sesuai . ' dari ' . $total . ')</small></li>'
         . '<li><span class="skala__dot skala__dot--no"></span>Tidak Sesuai <b>' . $pT . '%</b> <small>(' . $tidak . ' dari ' . $total . ')</small></li></ul>';
    $kosong = $total ? '' : '<p class="skala__kosong">Belum ada hasil klasifikasi.</p>';
    return '<div class="skala" role="img" aria-label="Sesuai ' . $pS . ' persen, Tidak Sesuai ' . $pT . ' persen">' . $bar . $legend . $kosong . '</div>';
}

