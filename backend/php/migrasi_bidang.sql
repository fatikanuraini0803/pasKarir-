-- Merapikan penulisan bidang lowongan agar form Klasifikasi (bidang -> lowongan) konsisten.
-- Cara pakai: phpMyAdmin > pilih database paskarir_exam > tab SQL > tempel > Kirim.
-- Aman: hanya mengubah kolom lowongan.bidang, tidak ada DROP/TRUNCATE.

-- 1. Hapus spasi di awal/akhir dan spasi ganda
UPDATE lowongan SET bidang = TRIM(REGEXP_REPLACE(bidang, '[[:space:]]+', ' '));

-- 2. Cek hasil: tiap bidang harus muncul sekali dengan penulisan yang sama
SELECT bidang, COUNT(*) AS jumlah_lowongan FROM lowongan GROUP BY bidang ORDER BY bidang;

-- 3. Jika ada bidang yang masih salah tulis, contoh perbaikan manual:
-- UPDATE lowongan SET bidang = 'Konstruksi dan Teknik Sipil' WHERE bidang LIKE 'konstruksi%';
