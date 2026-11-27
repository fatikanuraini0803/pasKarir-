<?php
/**
 * config_email.php - Pengaturan pengiriman email (SMTP) untuk fitur Lupa Password.
 *
 * CARA ISI (memakai Gmail):
 *  1. Siapkan satu akun Gmail untuk "pengirim" (boleh akun baru khusus aplikasi, mis. paskarir.app@gmail.com).
 *  2. Aktifkan Verifikasi 2 Langkah di akun itu: https://myaccount.google.com/security
 *  3. Buat "Sandi aplikasi" (App Password): https://myaccount.google.com/apppasswords
 *     -> salin 16 karakternya ke SMTP_PASS di bawah (tanpa spasi). BUKAN password login Gmail biasa.
 *  4. Isi SMTP_USER dengan alamat Gmail pengirim tersebut.
 *
 * JANGAN upload file ini ke GitHub / publik karena berisi password email pengirim.
 */
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;                 // 587 = STARTTLS (disarankan), 465 = SSL langsung
const SMTP_USER = '';                  // <-- isi: alamat Gmail pengirim
const SMTP_PASS = '';                  // <-- isi: App Password 16 karakter
const SMTP_NAMA = 'PasKarir';          // nama pengirim yang tampil di inbox

const RESET_BERLAKU_MENIT = 30;        // masa berlaku tautan reset
const RESET_MAKS_PER_JAM  = 3;         // batas permintaan reset per akun per jam

// Alamat dasar yang dipakai pada tautan di email, mis. 'http://localhost/Paskarir'.
// Kosongkan untuk dibuat otomatis dari alamat yang sedang dibuka. WAJIB diisi saat aplikasi di-hosting.
const APP_URL = '';

// Verifikasi sertifikat SSL server email. Biarkan true; ubah false HANYA bila di XAMPP muncul galat "certificate verify failed".
const SMTP_VERIFY_SSL = true;
