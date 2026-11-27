# Panduan Penilaian JavaScript - Paskarir

Dokumen ini memetakan rubrik penilaian ke kode di proyek, supaya mudah ditunjukkan saat sesi tanya jawab.

**Cara mencoba:** buka `user/login.html` di browser (tanpa server juga bisa).
Akun demo: `nama@email.com` / `Password123`, atau buat akun baru lewat `user/daftar.html`.

**Alur utama:** Daftar -> Login -> Dashboard -> Klasifikasi -> Hasil -> Riwayat.
Data disimpan di `localStorage` sebagai simulasi (prototipe, belum ada backend).

---

## 1. Event Handling
*Indikator: ketepatan event click, input, change, submit sesuai kebutuhan interaksi.*

| Event | Di mana | File : baris |
|---|---|---|
| `submit` | Semua formulir: `preventDefault()` lalu validasi | `backend/js/shared/core.js` : 226 (`bindForm`) |
| `input` | Validasi ulang langsung saat mengetik pada kolom yang salah | `backend/js/shared/core.js` : 218 |
| `input` | Indikator kekuatan password saat daftar | `backend/js/user/auth.js` : 99 |
| `input` | Pencarian lowongan / riwayat | `backend/js/user/list-filter.js` : 41 |
| `input` | Penghitung karakter deskripsi lowongan | `backend/js/sub-admin/form-lowongan.js` |
| `change` | Select pendidikan, filter bidang, filter hasil | `backend/js/shared/core.js` : 222, `backend/js/user/list-filter.js` : 42 |
| `focusout` (blur) | Validasi kolom saat ditinggalkan | `backend/js/shared/core.js` : 214 |
| `click` | Hapus chip keterampilan (event delegation) | `backend/js/shared/chips.js` : 86 |
| `click` | Ikon mata tampil/sembunyi password | `backend/js/shared/common.js` : 14 |
| `click` | Tombol Hapus pada tabel admin + konfirmasi | `backend/js/shared/admin-common.js` |
| `click` | Tombol "Ya, Keluar" menghapus sesi | `backend/js/shared/common.js` (`initLogout`) |
| `keydown` | Enter / koma menambah chip, Backspace menghapus chip terakhir | `backend/js/shared/chips.js` : 70 |
| `keydown` | Blok karakter e, +, -, titik pada kolom pengalaman | `backend/js/user/klasifikasi.js` |
| `reset` | Tombol Reset mengosongkan chip dan pesan error | `backend/js/user/klasifikasi.js` |

**Contoh pertanyaan penguji:** "Bagian mana yang jalan saat tombol Proses diklik?"
Jawab: `submit` pada form di `klasifikasi.js` (fungsi `init`), diteruskan lewat `bindForm`.

---

## 2. Validasi dan Pengolahan Input
*Indikator: validasi, pemeriksaan kondisi, input kosong/tidak valid, umpan balik.*

Semua aturan ada di `backend/js/shared/core.js`:
- `Validators`: `isEmpty`, `isEmail`, `isStrongPassword`, `isIntegerInRange`, dst.
- `Rules`: pembuat aturan yang mengembalikan **pesan error** (`required`, `email`, `minLength`, `strongPassword`, `matches`, `integerInRange`, `custom`).
- `Feedback`: pesan merah di bawah kolom (`setFieldError`), alert di atas form (`showAlert`), banner sukses dari URL `?status=`.

**Yang bisa diuji langsung:**

| Halaman | Uji | Hasil yang tampil |
|---|---|---|
| Daftar | Kirim form kosong | Pesan error di tiap kolom + alert "Ada N isian yang belum benar" |
| Daftar | Email `budi@mail` | "Format email tidak valid" |
| Daftar | Password `abc` | "Password minimal 8 karakter dan memuat huruf serta angka" |
| Daftar | Konfirmasi berbeda | "Konfirmasi tidak sama dengan password" |
| Daftar | Email `nama@email.com` | "Email ini sudah terdaftar" |
| Login | Email belum terdaftar / password salah | Pesan khusus per kasus; setelah 3 kali salah muncul saran "Lupa password?" |
| Klasifikasi | Lowongan di luar daftar | "Lowongan tidak ditemukan. Pilih dari daftar" |
| Klasifikasi | Tanpa keterampilan | "Tambahkan minimal satu keterampilan" |
| Klasifikasi | Pengalaman 99 | "bilangan bulat 0 sampai 50 tahun" |
| Chip | Ketik keterampilan yang sama dua kali | "sudah ada di daftar" |
| Ubah Password | Password lama salah / baru sama dengan lama | Pesan error masing-masing |
| Pengaturan Model | Kedalaman 50 | "bilangan bulat 1 sampai 20" |

Atribut `required` bawaan HTML tetap ada sebagai cadangan; `form.noValidate = true` dipakai agar pesan tampil dengan gaya sendiri.

---

## 3. Logika dan Perilaku Interaksi
*Indikator: alur logika, kondisi, fungsi, respons antarmuka. Cek di workflow tahap 2.*

| Alur | Logika | File : baris |
|---|---|---|
| Daftar -> Login | Simpan akun, pindah ke login dengan banner sukses | `backend/js/user/auth.js` : 52 |
| Login | Cari akun -> cocokkan password -> mulai sesi -> dashboard | `backend/js/user/auth.js` : 15 |
| Lupa password | Pesan sama untuk email terdaftar atau tidak; tombol nonaktif 30 detik | `backend/js/user/auth.js` : 108 |
| Klasifikasi | Validasi -> hitung -> simpan riwayat -> tombol "Memproses..." -> hasil | `backend/js/user/klasifikasi.js` : 39 |
| **Decision Tree** | Node 1 keterampilan >= 50%, Node 2 pendidikan >= minimal, Node 3 pengalaman >= minimal; lolos semua = SESUAI | `backend/js/user/classifier.js` : 49 |
| Rekomendasi | Lowongan lain diurutkan berdasarkan skor, ambil 3 teratas | `backend/js/user/classifier.js` : 77 |
| Hasil | Badge, skor, alasan (centang/silang), rekomendasi dirender dari data tersimpan | `backend/js/user/hasil.js` : 33 |
| Riwayat | Tabel dari riwayat tersimpan + filter hasil + pencarian | `backend/js/user/list-filter.js` : 75 |
| Dashboard | Sapaan dan angka ringkasan dari sesi dan riwayat | `backend/js/user/dashboard.js` |
| Admin | Validasi form -> simpan (simulasi) -> kembali ke daftar dengan banner sukses | `backend/js/sub-admin/*.js`, `backend/js/super-admin/*.js` |

Respons antarmuka: tombol dinonaktifkan saat proses (`aria-busy`), pesan kosong saat filter tanpa hasil, banner sukses setelah redirect.

---

## 4. Kualitas Kode JavaScript
*Indikator: keterbacaan, variabel dan fungsi, penamaan, pengorganisasian.*

**Pengorganisasian (satu file satu tanggung jawab):**
```
backend/js/shared/
  core.js         Validators, Rules, Feedback, bindForm, Store, Session
  chips.js        Komponen input keterampilan
  common.js       Perilaku umum (ikon mata, keluar, nama pengguna, banner)
  admin-common.js Aturan bersama admin, formulir profil, tombol Hapus
backend/js/sub-admin/
  form-lowongan.js   Form lowongan + penghitung karakter
  kelola-panduan.js  Form panduan/rekomendasi
backend/js/super-admin/
  tambah-akun.js          Form tambah akun
  form-persyaratan.js     Form persyaratan lowongan
  form-data-pelatihan.js  Form data pelatihan
  pengaturan-model.js     Pengaturan dan latih ulang model
backend/js/user/
  classifier.js   Aturan Decision Tree (fungsi murni, tanpa DOM)
  auth.js         Login, daftar, lupa password
  account.js      Profil dan ubah password
  klasifikasi.js  Form klasifikasi
  hasil.js        Tampilan hasil
  list-filter.js  Pencarian + filter lowongan dan riwayat
  dashboard.js    Ringkasan dashboard
  landing.js      (bawaan) animasi landing page
```

**Praktik yang dipakai:**
- Setiap file dibungkus IIFE + `'use strict'`; tidak ada variabel global kecuali `window.Paskarir`.
- Dokumentasi JSDoc pada fungsi utama (parameter, tipe, nilai kembali).
- Penamaan deskriptif: `validateOne`, `buildRecord`, `skillMatchRatio`, `startCooldown`.
- Konstanta bernama, bukan angka mentah: `MAX_EXPERIENCE_YEARS`, `RESET_COOLDOWN_SECONDS`, `SKILL_PASS_RATIO`.
- Fungsi murni dipisah dari DOM (`classifier.js`, `Validators`) sehingga mudah diuji.
- Tidak ada kode yang diulang: aturan validasi dipakai ulang lewat `Rules`, filter lewat `bindFilter`, chip lewat `initChips`.

**Urutan pemuatan skrip** (dipasang di akhir `<body>` tiap halaman):
`core.js` -> `chips.js` (bila perlu) -> `common.js` -> `admin-common.js` (halaman admin) -> skrip halaman.

---

## Catatan keterbatasan (sebaiknya disampaikan jujur saat presentasi)
- Penyimpanan memakai `localStorage` dan password disimpan apa adanya hanya untuk simulasi. Sistem asli wajib memakai backend dan hash password.
- Halaman admin hanya memvalidasi dan mensimulasikan penyimpanan; tabel admin belum terhubung ke database.
- Halaman tidak dikunci per peran (user/admin) karena itu tugas backend.
