<?php
/**
 * koneksi.php - Koneksi database MySQL memakai PDO.
 *
 * - ERRMODE_EXCEPTION : kesalahan SQL dilempar sebagai exception (bisa di-catch).
 * - EMULATE_PREPARES=false : prepared statement asli di sisi server MySQL, sehingga
 *   data pengguna tidak pernah digabung ke teks SQL (pencegahan SQL injection).
 */
const DB_HOST = 'localhost';
const DB_NAME = 'paskarir_exam';
const DB_USER = 'root';   // sesuaikan dengan MySQL di komputer
const DB_PASS = '';       // XAMPP bawaan: kosong

/** Mengembalikan satu koneksi PDO yang dipakai ulang di seluruh request. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('Koneksi database gagal: ' . $e->getMessage());
            http_response_code(500);
            exit('Koneksi database gagal. Periksa pengaturan di shared/koneksi.php dan pastikan database sudah di-import.');
        }
    }
    return $pdo;
}
