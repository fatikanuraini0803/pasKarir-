# Paskarir - Struktur Proyek

```
Paskarir/
├── index.php                   Redirect ke backend/php/ (satu pintu masuk)
├── frontend/                   Semua HTML dan CSS
│   ├── user/                   Halaman User (login, daftar, dashboard, dst.) + css/
│   ├── sub-admin/              Halaman Sub Admin + css/
│   ├── super-admin/            Halaman Super Admin + css/
│   ├── assets/
│   │   ├── css/                admin-common.css, feedback.css
│   │   ├── icons/              icons-user.css, icons-admin.css
│   │   ├── img/                logo
│   │   └── fonts/              tempat font lokal
│   └── admin-menu.html         Daftar tautan halaman admin (navigasi proyek)
├── backend/
│   └── js/
│       ├── shared/             core.js, chips.js, common.js, admin-common.js
│       ├── user/               auth.js, account.js, klasifikasi.js, classifier.js,
│       │                       hasil.js, list-filter.js, dashboard.js, landing.js
│       ├── sub-admin/          form-lowongan.js, kelola-panduan.js
│       └── super-admin/        tambah-akun.js, form-persyaratan.js,
│                               form-data-pelatihan.js, pengaturan-model.js
│   └── php/                    Versi PHP + MySQL, dipisah user/ sub-admin/ super-admin/
│                               (lihat backend/php/README.md)
├── README.md
└── PANDUAN_PENILAIAN_JS.md
```

## Menjalankan aplikasi dengan MySQL (disarankan)
Gunakan versi PHP + MySQL di `backend/php/`:
1. Pindahkan folder `Paskarir` ke `xampp/htdocs/`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Import `backend/php/database_lengkap.sql` (database baru/kosong) melalui phpMyAdmin; `database.sql` hanya migrasi tabel dataset_pelatihan untuk database yang sudah ada.
4. Buka **satu-satunya** alamat: `http://localhost/Paskarir/backend/php/` (menampilkan landing page frontend, lalu login/daftar/dashboard semuanya lewat PHP).
   `http://localhost/Paskarir/` otomatis diarahkan ke alamat yang sama.

**Penting:** `index.html` dan folder `frontend/` adalah prototipe JavaScript yang menyimpan data di `localStorage`. Untuk memakai database MySQL sungguhan, akses aplikasi melalui alamat PHP di atas, bukan dengan membuka file HTML secara langsung. Petunjuk lengkap dan cara membuat Super Admin pertama ada di `backend/php/README.md`. Modul Decision Tree kebutuhan pelatihan memakai 60 baris dari `dataset.xlsx`; analisis model tersedia untuk Super Admin dan form prediksi untuk User.

## Catatan
- Tampilan (HTML dan CSS) tidak diubah. Yang disesuaikan hanya tautan path
  (stylesheet, gambar, script, dan tautan ke `index.html`) mengikuti lokasi folder baru.
- Seluruh JavaScript dipindahkan ke `backend/js/`. Urutan pemanggilan script: core.js -> chips.js (bila perlu) -> common.js -> admin-common.js -> skrip halaman.
- Folder backend admin mengikuti frontend: satu skrip per halaman yang punya formulir, bagian bersama ada di shared/admin-common.js.
- Pemisahan folder bukan pengganti autentikasi dan pembatasan hak akses di server.
- Akun demo: `nama@email.com` / `Password123`.
- Pemetaan rubrik ke kode ada di `PANDUAN_PENILAIAN_JS.md`.
- Halaman Riwayat (user) punya dua aksi per baris: Lihat (ikon mata) dan Hapus (ikon tempat sampah, dengan dialog konfirmasi).
  Logikanya di `backend/js/user/list-filter.js`; penyimpanannya `Store.deleteHistory` di `backend/js/shared/core.js`.
  Versi database-nya ada di `backend/php/user/riwayat.php` (query `DELETE ... WHERE id AND user_id`).
- Versi PHP + MySQL ada di `backend/php/`. Cara import database dan menjalankannya: `backend/php/README.md`.
