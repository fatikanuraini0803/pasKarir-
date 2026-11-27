# PasKarir - Versi PHP + MySQL

Versi server (PHP + MySQL) dari PasKarir. Folder ini diletakkan di `backend/php/` dan dipisah per role,
mengikuti struktur `backend/js/`.

```
backend/php/
├── database.sql            skema 5 tabel kosong (import lewat phpMyAdmin)
├── index.php               pintu masuk: arahkan ke login atau dashboard sesuai role
├── assets/style.css
├── shared/                 kode bersama (tidak bisa dibuka dari browser)
│   ├── koneksi.php         koneksi PDO ke MySQL
│   ├── helpers.php         sesi, role, CSRF, escape, validasi
│   ├── data.php            query lowongan + keterampilan
│   ├── classifier.php      logika Decision Tree
│   ├── header.php footer.php
│   └── halaman/            halaman yang dipakai bersama lewat wrapper
├── user/                   role User
│   ├── login.php daftar.php logout.php
│   ├── dashboard.php lowongan.php klasifikasi.php hasil.php
│   ├── riwayat.php         lihat + HAPUS riwayat
│   └── profil.php ubah_password.php
├── sub-admin/              role Sub Admin
│   ├── index.php lowongan.php lowongan_form.php
│   └── profil.php ubah_password.php
└── super-admin/            role Super Admin
    ├── index.php lowongan.php lowongan_form.php
    ├── akun.php akun_form.php            akun dan hak akses (khusus Super Admin)
    └── profil.php ubah_password.php
```

Halaman yang sama untuk beberapa role (dashboard admin, kelola lowongan, profil, ubah password) ditulis sekali di
`shared/halaman/`. Tiap folder role hanya berisi wrapper kecil yang menetapkan `$area` dan `$peran`, lalu halaman
memanggil `require_role([$peran])`. Jadi Sub Admin tidak bisa membuka halaman Super Admin dan sebaliknya (HTTP 403).

## Cara menjalankan (XAMPP / Laragon)
1. Letakkan folder proyek `Paskarir` di `htdocs`, sehingga ada `htdocs/Paskarir/backend/php/`.
2. Nyalakan Apache dan MySQL.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, lalu impor `backend/php/database.sql`. Database `paskarir_exam` dan 5 tabel kosong akan dibuat. (Jika database/tabel lama berisi data penting, cadangkan dahulu karena skrip ini menghapus tabel dengan nama yang sama.)
4. Pastikan konfigurasi di `shared/koneksi.php` sesuai dengan XAMPP. Default XAMPP: host `localhost`, user `root`, password kosong, database `paskarir_exam`.
5. Buka aplikasi melalui `http://localhost/Paskarir/backend/php/`. **Jangan membuka `index.html` atau folder `frontend` untuk menjalankan aplikasi database**, karena itu adalah prototipe JavaScript/localStorage.
6. Buat akun melalui menu **Daftar**. Pendaftaran umum menghasilkan role `User`.
7. Untuk membuat akun pertama sebagai Super Admin: setelah mendaftar, buka phpMyAdmin > database `paskarir_exam` > tab SQL, jalankan perintah berikut dengan email akun yang baru dibuat:

   ```sql
   UPDATE users SET role = 'Super Admin' WHERE email = 'emailanda@example.com';
   ```

   Ganti email tersebut dengan email yang benar-benar didaftarkan. Keluar lalu masuk kembali agar hak akses Super Admin aktif.
8. Setelah masuk sebagai Super Admin, tambahkan akun admin dan data lowongan melalui menu aplikasi. Data yang ditambahkan akan disimpan ke MySQL.

Kebutuhan: PHP 7.4+ (PDO MySQL dan mbstring), MySQL 5.7+ atau MariaDB 10.3+.

## Data aplikasi
Database diimpor dalam keadaan kosong. Akun, lowongan, keterampilan, dan riwayat klasifikasi diisi melalui aplikasi dan disimpan di MySQL.

## Modul Decision Tree kebutuhan pelatihan
Dataset sumber `dataset.xlsx` (60 baris) sudah disertakan dalam `database.sql` pada tabel `dataset_pelatihan`. Saat impor, tabel ini akan dibuat dan diisi otomatis.

- User: menu **Kebutuhan Pelatihan** atau `user/klasifikasi-pelatihan.php` untuk prediksi.
- Super Admin: menu **Analisis Decision Tree** atau `super-admin/model-decision-tree.php` untuk melihat distribusi kelas, entropy, information gain, pohon, dan confusion matrix.
- ID data tidak digunakan sebagai fitur. Fitur model: usia, pendidikan, pengalaman kerja, bidang minat, skor komputer, skor komunikasi, skor teknis. Target: kebutuhan pelatihan.
- Skor dibagi ke kategori rendah (&lt;60), sedang (60–79), tinggi (80–100); usia dibagi menjadi di bawah 21, 21–25, dan di atas 25 tahun. Pembagian evaluasi stratified deterministik 80:20 (48 latih, 12 uji).
- Akurasi evaluasi bukan jaminan generalisasi: dataset hanya 60 baris dan label mengikuti data sumber.
