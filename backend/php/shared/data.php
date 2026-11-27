<?php
/**
 * data.php - Query SQL yang dipakai ulang oleh banyak halaman (lowongan dan keterampilan).
 * Semua query memakai prepared statement dengan parameter terikat.
 */

/**
 * Mengambil lowongan beserta daftar keterampilannya (JOIN tiga tabel).
 *
 * @param array $opsi  hanya_aktif (bool), q (cari nama/bidang), bidang, id
 * @return array       tiap baris punya kunci 'keterampilan' berupa array nama
 */
function fetch_lowongan(array $opsi = []): array
{
    $where  = [];
    $params = [];

    if (!empty($opsi['hanya_aktif'])) {
        $where[] = "l.status = 'Aktif'";
    }
    if (!empty($opsi['q'])) {
        $like = '%' . addcslashes($opsi['q'], '%_\\') . '%';
        $where[] = '(l.nama LIKE :q1 OR l.bidang LIKE :q2)';
        $params[':q1'] = $like;
        $params[':q2'] = $like;
    }
    if (!empty($opsi['bidang'])) {
        $where[] = 'TRIM(l.bidang) = :bidang';
        $params[':bidang'] = rapikan_bidang($opsi['bidang']);
    }
    if (!empty($opsi['id'])) {
        $where[] = 'l.id = :id';
        $params[':id'] = (int) $opsi['id'];
    }

    $sql = "SELECT l.id, l.nama, l.bidang, l.deskripsi, l.pendidikan_min, l.pengalaman_min, l.status,
                   GROUP_CONCAT(k.nama ORDER BY k.nama SEPARATOR '|') AS syarat
            FROM lowongan l
            LEFT JOIN lowongan_keterampilan lk ON lk.lowongan_id = l.id
            LEFT JOIN keterampilan k ON k.id = lk.keterampilan_id"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' GROUP BY l.id, l.nama, l.bidang, l.deskripsi, l.pendidikan_min, l.pengalaman_min, l.status
            ORDER BY l.nama';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $baris = $stmt->fetchAll();

    foreach ($baris as &$r) {
        $r['id']             = (int) $r['id'];
        $r['pengalaman_min'] = (int) $r['pengalaman_min'];
        $r['keterampilan']   = $r['syarat'] === null ? [] : explode('|', $r['syarat']);
    }
    unset($r);
    return $baris;
}

/** Satu lowongan berdasarkan id, atau null. */
function lowongan_by_id(int $id): ?array
{
    $hasil = fetch_lowongan(['id' => $id]);
    return $hasil[0] ?? null;
}

/** Merapikan nama bidang: hapus spasi di tepi, ganda spasi jadi satu. */
function rapikan_bidang(string $bidang): string
{
    return trim(preg_replace('/\s+/u', ' ', $bidang));
}

/** Kunci pembanding bidang (tanpa beda huruf besar/kecil dan spasi). */
function kunci_bidang(string $bidang): string
{
    $rapi = rapikan_bidang($bidang);
    return function_exists('mb_strtolower') ? mb_strtolower($rapi, 'UTF-8') : strtolower($rapi);
}

/**
 * Daftar bidang yang ada (untuk dropdown filter dan form klasifikasi).
 * Penulisan berbeda dari bidang yang sama (mis. "konstruksi " dan "Konstruksi") digabung jadi satu.
 */
function daftar_bidang(): array
{
    $semua = db()->query('SELECT DISTINCT bidang FROM lowongan')->fetchAll(PDO::FETCH_COLUMN);
    $unik  = [];
    foreach ($semua as $b) {
        $rapi = rapikan_bidang((string) $b);
        if ($rapi !== '' && !isset($unik[kunci_bidang($rapi)])) {
            $unik[kunci_bidang($rapi)] = $rapi;
        }
    }
    $hasil = array_values($unik);
    sort($hasil, SORT_NATURAL | SORT_FLAG_CASE);
    return $hasil;
}

/**
 * Menyamakan penulisan bidang dengan bidang yang sudah ada di database.
 * Bila sudah ada (abaikan huruf besar/kecil), pakai penulisan yang ada agar tidak muncul bidang ganda.
 */
function samakan_bidang(string $bidang): string
{
    $rapi = rapikan_bidang($bidang);
    foreach (daftar_bidang() as $ada) {
        if (kunci_bidang($ada) === kunci_bidang($rapi)) {
            return $ada;
        }
    }
    return $rapi;
}

/**
 * Menyamakan syarat keterampilan satu lowongan dengan daftar baru:
 * hapus relasi lama, tambahkan keterampilan baru ke master bila belum ada, lalu hubungkan.
 * Harus dipanggil di dalam transaksi.
 */
function sinkron_keterampilan(PDO $pdo, int $lowonganId, array $daftar): void
{
    $pdo->prepare('DELETE FROM lowongan_keterampilan WHERE lowongan_id = :id')->execute([':id' => $lowonganId]);

    $tambah = $pdo->prepare('INSERT IGNORE INTO keterampilan (nama) VALUES (:nama)');
    $cari   = $pdo->prepare('SELECT id FROM keterampilan WHERE nama = :nama LIMIT 1');
    $hubung = $pdo->prepare('INSERT INTO lowongan_keterampilan (lowongan_id, keterampilan_id) VALUES (:l, :k)');

    foreach ($daftar as $nama) {
        $tambah->execute([':nama' => $nama]);
        $cari->execute([':nama' => $nama]);
        $kid = $cari->fetchColumn();
        if ($kid !== false) {
            $hubung->execute([':l' => $lowonganId, ':k' => (int) $kid]);
        }
    }
}
