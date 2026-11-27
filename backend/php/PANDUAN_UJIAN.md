# Panduan Ujian - PasKarir (PHP + MySQL)

Pemetaan rubrik ke kode. Format: `file : baris` (relatif terhadap `backend/php/`).

---

## 1. Logika dan Pemrosesan Backend
*Ketepatan pemrosesan request, penggunaan PHP, validasi sisi server, penerapan logika bisnis.*

**Pemrosesan request.** Setiap halaman form memakai pola yang sama: `if (is_post())` -> `csrf_check()` -> ambil input dengan
`post_str()` -> validasi -> proses ke database -> `redirect()`. Contoh lengkap: `user/login.php : 19`.

**Event dan validasi sisi server** (semuanya dijalankan di PHP):
| Event / kondisi | Validasi | File |
|---|---|---|
| Submit login | email wajib dan valid, password wajib, akun aktif, kunci 60 detik setelah 5 kali gagal | `user/login.php : 19` |
| Submit daftar | nama, email (format dan belum terdaftar), password kuat, konfirmasi sama, setuju syarat | `user/daftar.php : 14` |
| Submit klasifikasi | lowongan harus ada di daftar aktif, keterampilan minimal 1, pendidikan whitelist, pengalaman bulat 0-50 | `user/klasifikasi.php : 16` |
| Hapus riwayat | id harus bilangan bulat, hanya milik sendiri (`AND user_id`) | `user/riwayat.php : 14` |
| Submit form lowongan | nama, bidang, deskripsi, pendidikan, pengalaman, keterampilan, status | `shared/halaman/lowongan_form.php : 32` |
| Submit form akun | email unik, role dan status whitelist, password kuat, aturan Super Admin terakhir | `super-admin/akun_form.php : 26` |

Fungsi validasi bersama di `shared/helpers.php`: `valid_email`, `valid_nama`, `password_kuat` (`shared/helpers.php : 209`),
`valid_telepon`, `parse_keterampilan` (`shared/helpers.php : 229`).

**Logika bisnis (Decision Tree)** - `shared/classifier.php`:
- `klasifikasi()` `shared/classifier.php : 42`: Node 1 keterampilan >= 50%, Node 2 pendidikan >= minimal, Node 3 pengalaman >= minimal.
  Lolos semua = SESUAI. Skor = 50% keterampilan + 25% pendidikan + 25% pengalaman (+5 bila bersertifikasi).
- `rekomendasi()` `shared/classifier.php : 75`: lowongan aktif lain diurutkan berdasarkan skor, ambil 3 teratas.

Lowongan bersifat umum, bukan hanya IT: data contoh memuat bidang Teknologi, Administrasi, Operasional, Layanan Pelanggan, dan Keuangan.
Admin dapat menambah bidang apa pun lewat form lowongan.

---

## 2. Integrasi Database
*Koneksi, struktur tabel, relasi data, query SQL.*

**Rute koneksi:** halaman -> `require_once shared/helpers.php` -> `shared/koneksi.php` -> fungsi `db()` (`shared/koneksi.php : 15`) yang membuat satu koneksi PDO dan memakainya ulang.
- `ATTR_ERRMODE => ERRMODE_EXCEPTION` (`shared/koneksi.php : 22`): error SQL menjadi exception.
- `ATTR_EMULATE_PREPARES => false` (`shared/koneksi.php : 24`): prepared statement asli di server MySQL.
- Gagal konek: pesan ramah ke pengguna, detail hanya ke `error_log`.

**Tabel dan relasi** (`database.sql`):
| Tabel | Isi | Relasi |
|---|---|---|
| `users` | akun, `password_hash`, role, status | `email` UNIQUE |
| `lowongan` | nama, bidang, pendidikan_min, pengalaman_min, status | `dibuat_oleh` -> `users` (SET NULL) |
| `keterampilan` | master keterampilan | `nama` UNIQUE |
| `lowongan_keterampilan` | penghubung | banyak-ke-banyak `lowongan` <-> `keterampilan` (CASCADE) |
| `riwayat_klasifikasi` | hasil tiap klasifikasi | `user_id` -> `users` (CASCADE), `lowongan_id` -> `lowongan` (RESTRICT) |

**Query yang bisa ditunjukkan:**
- JOIN tiga tabel + `GROUP_CONCAT`: `shared/data.php : 39` (`fetch_lowongan`).
- Agregasi `COUNT` / `SUM` dengan `LEFT JOIN` + `GROUP BY`: `shared/halaman/dashboard_admin.php : 22`.
- Filter + `LIKE` + pagination `LIMIT/OFFSET`: `user/riwayat.php : 72`.
- Transaksi (`beginTransaction`, `commit`, `rollBack`): `shared/halaman/lowongan_form.php : 67`.

---

## 3. Implementasi CRUD
*Fungsi Create, Read, Update, Delete dan kesesuaiannya dengan alur database.*

| Entitas | Create | Read | Update | Delete |
|---|---|---|---|---|
| **Akun** | `super-admin/akun_form.php : 83` (juga `user/daftar.php : 57`) | `super-admin/akun.php` | `super-admin/akun_form.php : 89` (juga `shared/halaman/profil.php : 32`, `shared/halaman/ubah_password.php : 46`) | `super-admin/akun.php : 36` |
| **Lowongan** | `shared/halaman/lowongan_form.php : 69` | `shared/halaman/lowongan_list.php`, `user/lowongan.php` | `shared/halaman/lowongan_form.php : 75` | `shared/halaman/lowongan_list.php : 24` |
| **Riwayat klasifikasi** | `user/klasifikasi.php : 73` | `user/riwayat.php`, `user/hasil.php : 19` | - (riwayat tidak diubah) | **`user/riwayat.php : 20`** (tombol Hapus per baris) |
| **Keterampilan lowongan** | `shared/data.php : 76` (hapus relasi lama, isi ulang) | `fetch_lowongan` | sama dengan Update lowongan | CASCADE saat lowongan dihapus |

**Aksi Hapus riwayat (yang baru):** tombol Hapus ada di tiap baris `user/riwayat.php`, dengan konfirmasi `confirm()`,
lewat POST + token CSRF. Query `DELETE ... WHERE id = :id AND user_id = :uid` memastikan User hanya bisa menghapus
riwayatnya sendiri. Setelah hapus, halaman kembali ke pencarian dan filter yang sama. Versi JavaScript-nya ada di
`backend/js/user/list-filter.js` (`Store.deleteHistory` di `backend/js/shared/core.js`).

Kesesuaian dengan alur database:
- Hapus lowongan yang sudah punya riwayat ditolak oleh `RESTRICT` (error 23000), ditangkap di `shared/halaman/lowongan_list.php : 28` dan diberi pesan "ubah status menjadi Ditutup".
- Email duplikat ditolak dua lapis: cek `SELECT` lebih dulu, dan `UNIQUE KEY` sebagai pengaman bila dua request bersamaan (`user/daftar.php : 66`).
- Super Admin aktif terakhir tidak bisa dihapus (`super-admin/akun.php : 11`) maupun diturunkan (`super-admin/akun_form.php : 76`).
- Semua Delete lewat POST + token CSRF, bukan tautan GET.

---

## 4. Autentikasi dan Keamanan
*Login/logout, session, password hashing, validasi akses, pencegahan SQL injection.*

**Login** - `user/login.php` (dipakai semua role, lalu diarahkan ke folder role masing-masing lewat `home_for()`):
1. Cari akun: `SELECT ... WHERE email = :email` (`user/login.php : 37`, prepared statement).
2. `password_verify()` (`user/login.php : 41`) membandingkan password dengan hash bcrypt.
3. Pesan gagal sama untuk "email tidak ada" dan "password salah", dan ada hash pura-pura `HASH_PURA` agar waktu respons sama.
4. Batas percobaan: 5 kali gagal -> terkunci 60 detik (`MAKS_GAGAL`, `KUNCI_DETIK`).
5. Akun Nonaktif ditolak.
6. Berhasil: `session_regenerate_id(true)` (`user/login.php : 54`, mencegah session fixation), simpan `$_SESSION['uid']`.

**Password hashing:**
- Simpan: `password_hash($password, PASSWORD_DEFAULT)` di `user/daftar.php : 61`, `shared/halaman/ubah_password.php : 47`, dan `super-admin/akun_form.php`.
- Kolom `users.password_hash` hanya berisi hash bcrypt, tidak ada password asli.
- `password_needs_rehash` (`user/login.php : 59`) memperbarui hash otomatis bila algoritma default berubah.

**Session dan logout** - `shared/helpers.php`:
- Cookie sesi `httponly` + `samesite=Lax` (`shared/helpers.php : 21`).
- `current_user()` (`shared/helpers.php : 138`) membaca akun dari database di setiap request; akun dihapus atau dinonaktifkan = sesi berakhir.
  Sesi juga habis setelah 2 jam tidak aktif (`SESI_MAKS_DIAM`).
- Logout: `user/logout.php` hanya menerima POST + CSRF, lalu `hancurkan_sesi()` (`shared/helpers.php : 129`).

**Validasi akses per role (otorisasi):**
- `require_login()` `shared/helpers.php : 173`: belum login -> diarahkan ke login.
- `require_role([...])` `shared/helpers.php : 184`: role tidak cocok -> HTTP 403.
- Pemisahan folder: `user/` hanya role User, `sub-admin/` hanya Sub Admin, `super-admin/` hanya Super Admin
  (contoh: `super-admin/akun.php : 7`; halaman bersama memakai `require_role([$peran])` dari wrapper).
- Kepemilikan data: `user/hasil.php : 19` dan `user/riwayat.php : 20` memakai `AND user_id = :uid`,
  jadi User tidak bisa membuka atau menghapus riwayat orang lain dengan mengganti `id`.
- Pelamar tidak bisa menaikkan role sendiri: `user/daftar.php` selalu menyimpan role `User`, dan profil tidak menerima kolom role.

**Pencegahan SQL injection:**
- Semua query yang memakai input pengguna memakai prepared statement dengan placeholder (`:email`, `:id`, ...). Input tidak pernah digabung ke teks SQL.
- Pengecualian aman: `LIMIT/OFFSET` di `user/riwayat.php` dipasang langsung, tetapi nilainya sudah dipaksa bilangan bulat.
- Kata pencarian `LIKE` di-escape dengan `addcslashes` supaya `%` dan `_` tidak jadi wildcard liar.
- Nilai ENUM (role, status, pendidikan) dicek terhadap whitelist sebelum disimpan.

**Perlindungan lain:**
- XSS: semua output lewat `esc()` (`shared/helpers.php : 34`) = `htmlspecialchars`.
- CSRF: token acak per sesi, dicek dengan `hash_equals` (`shared/helpers.php : 117`), ada di semua form POST.
- Header `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` di `shared/helpers.php`.
- `shared/.htaccess` menolak akses langsung ke folder `shared`.

---

## Catatan keterbatasan (sampaikan jujur bila ditanya)
- Input keterampilan memakai teks dipisah koma, bukan chip seperti di prototipe JavaScript.
- Belum ada fitur kirim email untuk "Lupa password" (butuh server SMTP); di prototipe JavaScript hanya simulasi.
- Halaman Data Pelatihan dan Pengaturan Model pada prototipe belum dibuat di versi PHP; klasifikasi memakai aturan tetap di `shared/classifier.php`.
- Belum ada pembatasan lalu lintas di level server selain kunci login per sesi.
