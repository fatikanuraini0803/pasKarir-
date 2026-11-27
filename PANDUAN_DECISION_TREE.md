# Panduan Decision Tree PasKarir

## 1. Sumber dan variabel

Sumber data adalah `dataset.xlsx`, sheet `Sheet1`, header berada pada baris ketiga. Dataset memuat 60 record, 9 kolom, tanpa nilai kosong dan tanpa ID/record duplikat.

- ID: identitas record saja, **tidak** dipakai sebagai fitur.
- Fitur: Usia, Pendidikan, Pengalaman Kerja, Bidang Minat, Skor Komputer, Skor Komunikasi, Skor Teknis.
- Target: Kebutuhan Pelatihan.

Distribusi target seimbang: Komputer Dasar 15; Keterampilan Teknis 15; Komunikasi 15; Siap Kerja/Lanjutan 15.

## 2. Pra-pemrosesan

Decision Tree pada aplikasi menggunakan ID3 dan atribut kategori. Skor numerik dibagi menjadi kategori tetap: Rendah (&lt;60), Sedang (60–79), Tinggi (80–100). Usia dibagi menjadi Di bawah 21, 21–25, dan Di atas 25. Nilai kategori pendidikan, pengalaman kerja, dan bidang minat mengikuti nilai pada dataset sumber. Batas kategori yang sama diterapkan saat prediksi agar proses training dan inference konsisten.

## 3. Rumus

Entropy untuk himpunan S:

`Entropy(S) = - Σ p(i) log2(p(i))`

Information Gain atribut A:

`Gain(S,A) = Entropy(S) - Σ (|S_v| / |S| × Entropy(S_v))`

Atribut dengan Information Gain tertinggi dipilih sebagai node. Proses diulang pada cabang sampai data pada cabang homogen, atribut habis, kedalaman maksimum tercapai, atau jumlah data cabang terlalu kecil.

## 4. Hasil eksperimen pada dataset ini

Pembagian data stratified deterministik 80:20 menghasilkan 48 data latih (12 per kelas) dan 12 data uji (3 per kelas). Entropy data latih = **2.000000 bit**.

| Atribut | Information Gain |
|---|---:|
| Skor Komputer | 1.314640 |
| Skor Teknis | 1.290584 |
| Pengalaman Kerja | 1.162598 |
| Skor Komunikasi | 1.138707 |
| Bidang Minat | 1.000480 |
| Pendidikan | 1.050200 |
| Usia | 0.892603 |

Atribut akar yang dipilih adalah **Skor Komputer** (Gain 1.314640). Cabang akar pada model: Rendah (&lt;60) → Komputer Dasar; Sedang (60–79) → Bidang Minat; Tinggi (80–100) → Skor Teknis. Node lanjutan dan daun lengkap ditampilkan di menu Analisis Decision Tree.

Hasil pengujian pada 12 data uji: **12 prediksi benar dari 12 (akurasi 100%)**. Confusion matrix menunjukkan 3 prediksi benar untuk masing-masing kelas dan tidak ada salah klasifikasi pada split ini.

## 5. Interpretasi dan keterbatasan

Akurasi 100% adalah hasil pada split uji kecil yang spesifik, bukan jaminan bahwa model akan mencapai akurasi yang sama pada data baru. Dataset hanya berisi 60 record. Label target diperlakukan sebagai label sumber; model mempelajari pola label yang sudah ada dan tidak memvalidasi secara independen apakah kebutuhan pelatihan seseorang benar secara objektif. Evaluasi yang lebih kuat memerlukan data tambahan dan validasi silang atau data uji eksternal.

## 6. Menjalankan di XAMPP

1. Salin folder `Paskarir` ke `C:\xampp\htdocs\`.
2. Nyalakan Apache dan MySQL.
3. Buka phpMyAdmin dan impor `backend/php/database.sql` pada database uji/kosong. Perhatian: skrip SQL ini menghapus dan membuat ulang tabel aplikasi yang bernama sama; cadangkan database yang sudah berisi data penting terlebih dahulu.
4. Pastikan konfigurasi database di `backend/php/shared/koneksi.php` sesuai dengan MySQL lokal.
5. Buka `http://localhost/Paskarir/backend/php/`.
6. Login sebagai Super Admin untuk menu **Analisis Decision Tree**. Login sebagai User untuk menu **Kebutuhan Pelatihan**.

Lihat `backend/php/README.md` untuk langkah pembuatan akun Super Admin pertama.
