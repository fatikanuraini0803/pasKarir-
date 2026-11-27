<?php
/**
 * classifier.php - Logika bisnis klasifikasi kesesuaian pelamar (Decision Tree sederhana).
 * Fungsi murni: tidak mengakses database maupun HTML, sehingga mudah diuji dan dijelaskan.
 */

const RASIO_LOLOS_KETERAMPILAN = 0.5;

function huruf_kecil(string $teks): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($teks, 'UTF-8') : strtolower($teks);
}

/**
 * Kata umum pada nama keterampilan. Kata ini sendirian tidak cukup untuk dianggap cocok,
 * sehingga "Microsoft" saja tidak sama dengan "Microsoft Excel", tetapi "Excel" saja cocok.
 */
const KATA_UMUM_KETERAMPILAN = [
    'microsoft', 'dan', 'atau', 'dengan', 'untuk', 'yang', 'di', 'ke', 'dari', 'pada',
    'dasar', 'basic', 'manajemen', 'administrasi', 'pengelolaan', 'kemampuan', 'keterampilan',
    'skill', 'kerja', 'umum', 'lanjutan', 'tingkat',
];

/** Singkatan yang disamakan sebelum dibandingkan (mis. "MS Excel" = "Microsoft Excel"). */
const ALIAS_KATA_KETERAMPILAN = ['ms' => 'microsoft'];

/** Memecah nama keterampilan menjadi kata-kata kecil tanpa tanda baca (daftar kata unik). */
function kata_keterampilan(string $teks): array
{
    $bersih = preg_replace('/[^\p{L}\p{N}]+/u', ' ', huruf_kecil($teks));
    $kata   = preg_split('/\s+/u', trim((string) $bersih), -1, PREG_SPLIT_NO_EMPTY);
    $hasil  = [];
    foreach ($kata as $k) {
        $hasil[] = ALIAS_KATA_KETERAMPILAN[$k] ?? $k;
    }
    return array_values(array_unique($hasil));
}

/**
 * Apakah satu keterampilan pelamar dianggap memenuhi satu syarat lowongan?
 * Cocok bila kata-katanya sama persis, atau bila seluruh kata di salah satu sisi termuat
 * di sisi lainnya dan ada minimal satu kata inti (bukan kata umum), contoh:
 *   pelamar "Excel"            -> syarat "Microsoft Excel"  : cocok
 *   pelamar "Microsoft Excel"  -> syarat "Excel"            : cocok
 *   pelamar "Microsoft"        -> syarat "Microsoft Excel"  : tidak cocok (kata umum saja)
 *   pelamar "Manajemen Waktu"  -> syarat "Manajemen Stok"   : tidak cocok
 */
function keterampilan_cocok(array $kataSyarat, array $kataPelamar): bool
{
    if (count($kataSyarat) === 0 || count($kataPelamar) === 0) {
        return false;
    }
    $irisan = array_values(array_intersect($kataSyarat, $kataPelamar));
    if (count($irisan) === 0) {
        return false;
    }
    $syaratTermuat  = count($irisan) === count($kataSyarat);
    $pelamarTermuat = count($irisan) === count($kataPelamar);
    if (!$syaratTermuat && !$pelamarTermuat) {
        return false;
    }
    if ($syaratTermuat && $pelamarTermuat) {
        return true; // kata-katanya sama persis (urutan dan tanda baca diabaikan)
    }
    foreach ($irisan as $k) {
        if (mb_strlen($k) >= 3 && !in_array($k, KATA_UMUM_KETERAMPILAN, true)) {
            return true;
        }
    }
    return false;
}

/** Rasio keterampilan pelamar yang cocok dengan syarat lowongan (0..1). */
function rasio_keterampilan(array $pelamar, array $syarat): float
{
    if (count($syarat) === 0) {
        return 1.0;
    }
    list($cocok) = pisah_keterampilan($pelamar, $syarat);
    return count($cocok) / count($syarat);
}

/**
 * Menilai satu pelamar terhadap satu lowongan.
 *
 * Node 1: rasio keterampilan >= 50%
 * Node 2: tingkat pendidikan >= minimal
 * Node 3: pengalaman >= minimal
 * Lolos ketiganya = SESUAI.
 *
 * @param array $pelamar  keterampilan (array), pendidikan, pengalaman (int), sertifikasi ('ya'/'tidak')
 * @param array $lowongan keterampilan (array), pendidikan_min, pengalaman_min (int)
 * @return array          label, sesuai, skor (0..100), alasan [[ok, teks], ...]
 */
function klasifikasi(array $pelamar, array $lowongan): array
{
    $rasio   = rasio_keterampilan($pelamar['keterampilan'], $lowongan['keterampilan']);
    $okSkill = $rasio >= RASIO_LOLOS_KETERAMPILAN;                                                   // Node 1
    $okPend  = TINGKAT_PENDIDIKAN[$pelamar['pendidikan']] >= TINGKAT_PENDIDIKAN[$lowongan['pendidikan_min']]; // Node 2
    $okPeng  = (int) $pelamar['pengalaman'] >= (int) $lowongan['pengalaman_min'];                    // Node 3

    $minPeng   = (int) $lowongan['pengalaman_min'];
    $rasioPeng = $minPeng === 0 ? 1.0 : min(1.0, (int) $pelamar['pengalaman'] / $minPeng);

    $skor = 50 * $rasio + 25 * ($okPend ? 1 : 0) + 25 * $rasioPeng;
    if ($pelamar['sertifikasi'] === 'ya') {
        $skor += 5; // bonus sertifikasi
    }
    $skor = (int) min(100, round($skor));

    $sesuai = $okSkill && $okPend && $okPeng;
    return [
        'label'  => $sesuai ? 'SESUAI' : 'TIDAK SESUAI',
        'sesuai' => $sesuai,
        'skor'   => $skor,
        'alasan' => [
            ['ok' => $okSkill, 'teks' => $okSkill ? 'Keterampilan utama cocok dengan syarat' : 'Keterampilan utama belum cocok dengan syarat'],
            ['ok' => $okPeng,  'teks' => $okPeng ? 'Pengalaman memenuhi minimal' : "Pengalaman kurang dari minimal ($minPeng tahun)"],
            ['ok' => $okPend,  'teks' => $okPend ? 'Pendidikan memenuhi syarat' : 'Pendidikan di bawah minimal (' . $lowongan['pendidikan_min'] . ')'],
        ],
    ];
}

/**
 * Memisahkan syarat lowongan menjadi keterampilan yang sudah dimiliki pelamar dan yang belum.
 * Pencocokan memakai keterampilan_cocok(), jadi "Excel" sudah dianggap memenuhi "Microsoft Excel".
 * @return array [cocok[], kurang[]]
 */
function pisah_keterampilan(array $pelamar, array $syarat): array
{
    $kataPelamar = array_map('kata_keterampilan', $pelamar);
    $cocok  = [];
    $kurang = [];
    foreach ($syarat as $s) {
        $kataSyarat = kata_keterampilan($s);
        $dimiliki   = false;
        foreach ($kataPelamar as $kp) {
            if (keterampilan_cocok($kataSyarat, $kp)) {
                $dimiliki = true;
                break;
            }
        }
        if ($dimiliki) {
            $cocok[] = $s;
        } else {
            $kurang[] = $s;
        }
    }
    return [$cocok, $kurang];
}

/**
 * Rekomendasi lowongan berdasarkan keterampilan yang dipilih pelamar.
 *
 * Setiap lowongan aktif (selain yang sedang dicek) diproses dengan Decision Tree yang sama
 * seperti klasifikasi(). Hanya lowongan yang minimal satu syarat keterampilannya dimiliki
 * pelamar yang masuk daftar. Urutan: yang SESUAI dulu, lalu skor tertinggi, lalu jumlah
 * keterampilan cocok terbanyak.
 *
 * @return array [['id','nama','bidang','skor','sesuai','cocok'[],'kurang'[]], ...] maksimal $batas item
 */
function rekomendasi(array $pelamar, int $kecualiId, int $batas = 5): array
{
    $hasil = [];
    foreach (fetch_lowongan(['hanya_aktif' => true]) as $l) {
        if ($l['id'] === $kecualiId) {
            continue;
        }
        list($cocok, $kurang) = pisah_keterampilan($pelamar['keterampilan'], $l['keterampilan']);
        if (count($cocok) === 0) {
            continue; // tidak ada keterampilan pelamar yang dibutuhkan lowongan ini
        }
        $k = klasifikasi($pelamar, $l);
        $hasil[] = [
            'id'     => $l['id'],
            'nama'   => $l['nama'],
            'bidang' => $l['bidang'],
            'skor'   => $k['skor'],
            'sesuai' => $k['sesuai'],
            'cocok'  => $cocok,
            'kurang' => $kurang,
        ];
    }
    usort($hasil, function ($a, $b) {
        return [(int) $b['sesuai'], $b['skor'], count($b['cocok'])] <=> [(int) $a['sesuai'], $a['skor'], count($a['cocok'])];
    });
    return array_slice($hasil, 0, $batas);
}
