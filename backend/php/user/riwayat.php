<?php
/**
 * riwayat.php - Riwayat klasifikasi milik User.
 * READ  : daftar dengan filter, pencarian, dan halaman.
 * DELETE: tombol Hapus per baris (POST + CSRF), hanya untuk riwayat milik sendiri.
 */
require_once __DIR__ . '/../shared/helpers.php';

$user  = require_role(['User']);

/* ---- DELETE: hapus satu riwayat milik sendiri ----
 * Kondisi "AND user_id = :uid" memastikan User tidak bisa menghapus riwayat orang lain
 * walaupun mengubah nilai id di form. */
if (is_post()) {
    csrf_check();
    $hapusId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($hapusId === false) {
        flash('error', 'Data yang dihapus tidak valid.');
    } else {
        $stmt = db()->prepare('DELETE FROM riwayat_klasifikasi WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $hapusId, ':uid' => $user['id']]);
        if ($stmt->rowCount() > 0) {
            flash('success', 'Riwayat klasifikasi berhasil dihapus.');
        } else {
            flash('error', 'Riwayat tidak ditemukan atau bukan milikmu.');
        }
    }
    // Kembali ke halaman yang sama dengan pencarian dan filter semula
    $balik = [];
    if (post_str('q') !== '') {
        $balik['q'] = post_str('q');
    }
    if (in_array(post_str('label'), ['SESUAI', 'TIDAK SESUAI'], true)) {
        $balik['label'] = post_str('label');
    }
    $halBalik = filter_var(post_str('hal'), FILTER_VALIDATE_INT);
    if ($halBalik !== false && $halBalik > 1) {
        $balik['hal'] = $halBalik;
    }
    redirect(url('user/riwayat.php' . ($balik ? '?' . http_build_query($balik) : '')));
}

$ringkasan = db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(label='SESUAI'),0) sesuai, COALESCE(SUM(label='TIDAK SESUAI'),0) tidak FROM riwayat_klasifikasi WHERE user_id=:uid");
$ringkasan->execute([':uid'=>$user['id']]); $ringkasan=$ringkasan->fetch();

$q     = get_str('q');
$label = get_str('label');
if (!in_array($label, ['SESUAI', 'TIDAK SESUAI'], true)) {
    $label = '';
}
$hal    = max(1, get_int('hal') ?? 1);
$perHal = PER_HALAMAN;

$where  = ['r.user_id = :uid'];
$params = [':uid' => $user['id']];
if ($q !== '') {
    $where[] = 'l.nama LIKE :q';
    $params[':q'] = '%' . addcslashes($q, '%_\\') . '%';
}
if ($label !== '') {
    $where[] = 'r.label = :label';
    $params[':label'] = $label;
}
$kondisi = implode(' AND ', $where);

$hitung = db()->prepare("SELECT COUNT(*) FROM riwayat_klasifikasi r JOIN lowongan l ON l.id = r.lowongan_id WHERE $kondisi");
$hitung->execute($params);
$total   = (int) $hitung->fetchColumn();
$perHal  = per_halaman($total);   // maksimal 2 halaman
$jmlHal  = max(1, (int) ceil($total / $perHal));
$hal     = min($hal, $jmlHal);
$offset  = ($hal - 1) * $perHal;   // sudah bilangan bulat, aman dipasang langsung di LIMIT

$stmt = db()->prepare("SELECT r.id, l.nama AS lowongan, r.skor, r.label, r.created_at
                       FROM riwayat_klasifikasi r JOIN lowongan l ON l.id = r.lowongan_id
                       WHERE $kondisi ORDER BY r.created_at DESC, r.id DESC LIMIT $perHal OFFSET $offset");
$stmt->execute($params);
$baris = $stmt->fetchAll();

$judul = 'Riwayat';
$halamanAktif = 'user/riwayat.php';
require __DIR__ . '/../shared/header.php';
?>
<div class="page-head"><h1>Riwayat Klasifikasi</h1><p>Daftar semua hasil klasifikasi yang pernah kamu lakukan. Klik ikon mata untuk melihat detail atau ikon tempat sampah untuk menghapus.</p></div>
<section aria-label="Ringkasan riwayat" class="stats"><article class="card stat"><span class="icon-tile"><span aria-hidden="true" class="icon icon--chart"></span></span><div><p class="stat__label">Total</p><p class="stat__value"><?= (int) $ringkasan['total'] ?></p></div></article><article class="card card--primary stat"><span class="icon-tile"><span aria-hidden="true" class="icon icon--check"></span></span><div><p class="stat__label">Sesuai</p><p class="stat__value"><?= (int) $ringkasan['sesuai'] ?></p></div></article><article class="card stat"><span class="icon-tile"><span aria-hidden="true" class="icon icon--doc"></span></span><div><p class="stat__label">Tidak sesuai</p><p class="stat__value"><?= (int) $ringkasan['tidak'] ?></p></div></article></section>
<section aria-labelledby="skala-judul" class="card"><h2 class="card__title card__title--gap" id="skala-judul">Skala kesesuaian</h2><?= skala_html((int) $ringkasan['sesuai'], (int) $ringkasan['tidak']) ?></section>
<form class="toolbar" method="get" role="search">
<span class="input-wrap toolbar__grow"><label class="visually-hidden" for="cari">Cari lowongan</label><span aria-hidden="true" class="icon icon--search"></span><input class="input" id="cari" name="q" placeholder="Cari lowongan" type="search" value="<?= esc($q) ?>"></span>
<label class="visually-hidden" for="hasil">Filter hasil</label><select class="input input--auto" id="hasil" name="label" onchange="this.form.submit()"><option value="">Semua hasil</option><option value="SESUAI" <?= $label === 'SESUAI' ? 'selected' : '' ?>>Sesuai</option><option value="TIDAK SESUAI" <?= $label === 'TIDAK SESUAI' ? 'selected' : '' ?>>Tidak sesuai</option></select>
<a class="btn btn--primary btn--lg" href="<?= esc(url('user/klasifikasi.php')) ?>"><span aria-hidden="true" class="icon icon--plus"></span> Klasifikasi Baru</a>
</form>
<section aria-label="Tabel riwayat" class="card"><div class="table-wrap"><table class="table"><caption class="visually-hidden">Riwayat klasifikasi</caption>
<thead><tr><th scope="col">No</th><th scope="col">Tanggal</th><th scope="col">Lowongan</th><th scope="col">Hasil</th><th scope="col">Kecocokan</th><th scope="col">Aksi</th></tr></thead><tbody>
<?php foreach ($baris as $i => $r): ?>
<tr><td><?= ($hal - 1) * $perHal + $i + 1 ?></td><td><time datetime="<?= esc(substr($r['created_at'], 0, 10)) ?>"><?= esc(date('d/m/Y', strtotime($r['created_at']))) ?></time></td><td><?= esc($r['lowongan']) ?></td><td><span class="badge <?= $r['label'] === 'SESUAI' ? 'badge--success' : 'badge--danger' ?>"><?= $r['label'] === 'SESUAI' ? 'Sesuai' : 'Tidak Sesuai' ?></span></td>
<td><span class="skor-mini"><span aria-hidden="true" class="skor-mini__bar"><span style="width:<?= (int) $r['skor'] ?>%"></span></span><b><?= (int) $r['skor'] ?>%</b></span></td>
<td><div style="display:flex;gap:6px;align-items:center">
<a aria-label="Lihat detail <?= esc($r['lowongan']) ?>" class="icon-btn" href="<?= esc(url('user/hasil.php?id=' . (int) $r['id'])) ?>"><span aria-hidden="true" class="icon icon--eye"></span></a>
<form method="post" style="margin:0" data-confirm="Hapus riwayat ini?" data-confirm-name="<?= esc($r['lowongan']) ?>" data-confirm-desc="Riwayat klasifikasi ini akan dihapus dan tidak bisa dikembalikan."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="q" value="<?= esc($q) ?>"><input type="hidden" name="label" value="<?= esc($label) ?>"><input type="hidden" name="hal" value="<?= (int) $hal ?>"><button aria-label="Hapus riwayat <?= esc($r['lowongan']) ?>" class="icon-btn" title="Hapus data" type="submit"><span aria-hidden="true" class="icon icon--trash"></span></button></form>
</div></td></tr>
<?php endforeach; ?>
<?php if (!$baris): ?><tr><td colspan="6" class="muted">Belum ada riwayat yang cocok.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?= paginasi_html(paginasi_info($total, $hal, $perHal), 'riwayat', 'user') ?>
<?php require __DIR__ . '/../shared/footer.php'; ?>
