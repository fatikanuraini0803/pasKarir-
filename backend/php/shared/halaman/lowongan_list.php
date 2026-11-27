<?php
/**
 * lowongan_list.php - Daftar lowongan (READ) dan hapus lowongan (DELETE).
 * Dipanggil dari sub-admin/lowongan.php dan super-admin/lowongan.php; role dibatasi oleh wrapper.
 */
require_once __DIR__ . '/../helpers.php';

if (!isset($area, $peran)) {   // hanya boleh dipanggil dari wrapper di folder role
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../data.php';

$user = require_role([$peran]);

/* ---- DELETE: hanya lewat POST + CSRF ---- */
if (is_post()) {
    csrf_check();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false) {
        flash('error', 'Data yang dihapus tidak valid.');
    } else {
        try {
            $stmt = db()->prepare('DELETE FROM lowongan WHERE id = :id');
            $stmt->execute([':id' => $id]);
            flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Lowongan berhasil dihapus.' : 'Lowongan tidak ditemukan.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {   // masih dipakai tabel riwayat_klasifikasi (RESTRICT)
                flash('error', 'Lowongan ini sudah dipakai di riwayat klasifikasi, jadi tidak bisa dihapus. Ubah statusnya menjadi Ditutup.');
            } else {
                throw $e;
            }
        }
    }
    redirect(url($area . '/lowongan.php'));
}

$q      = get_str('q');
$bidang = get_str('bidang');
$daftar = fetch_lowongan(['q' => $q, 'bidang' => $bidang]);
$pg     = paginasi($daftar);

$judul = 'Kelola Lowongan';
$halamanAktif = $area . '/lowongan.php';
require __DIR__ . '/../header.php';
?>
<header class="page-header">
<h1>Kelola Lowongan</h1>
<p class="page-header__desc">Lihat, tambah, ubah, atau hapus data lowongan. Tombol Hapus akan meminta konfirmasi.</p>
</header>
<form class="toolbar" method="get">
<div class="search">
<label class="visually-hidden" for="cari">Cari lowongan</label>
<span aria-hidden="true" class="icon icon--search"></span>
<input class="search__input" id="cari" name="q" placeholder="Cari lowongan" type="search" value="<?= esc($q) ?>">
</div>
<div>
<label class="visually-hidden" for="filter-bidang">Filter bidang</label>
<select class="select-pill" id="filter-bidang" name="bidang" onchange="this.form.submit()">
<option value="">Semua bidang</option>
<?php foreach (daftar_bidang() as $b): ?><option value="<?= esc($b) ?>" <?= $b === $bidang ? 'selected' : '' ?>><?= esc($b) ?></option><?php endforeach; ?>
</select>
</div>
<div class="toolbar__end">
<a class="btn btn--primary" href="<?= esc(url($area . '/lowongan_form.php')) ?>"><span aria-hidden="true" class="icon icon--plus"></span> Tambah Lowongan</a>
</div>
</form>
<div class="table-card">
<table class="table">
<caption class="visually-hidden">Daftar lowongan</caption>
<thead><tr><th scope="col">Nama lowongan</th><th scope="col">Bidang</th><th scope="col">Syarat keterampilan</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
<tbody>
<?php foreach ($pg['items'] as $l): ?>
<tr data-pendidikan="<?= esc($l['pendidikan_min']) ?>" data-pengalaman="<?= (int) $l['pengalaman_min'] ?> tahun" data-deskripsi="<?= esc($l['deskripsi']) ?>">
<td><?= esc($l['nama']) ?></td>
<td><?= esc($l['bidang']) ?></td>
<td><?= esc(implode(', ', $l['keterampilan'])) ?></td>
<td><span class="badge <?= $l['status'] === 'Aktif' ? 'badge--success' : 'badge--warning' ?>"><?= esc($l['status']) ?></span></td>
<td><div class="actions">
<a aria-label="Lihat lowongan <?= esc($l['nama']) ?>" class="icon-btn" href="#" title="Lihat"><span aria-hidden="true" class="icon icon--eye"></span></a>
<a aria-label="Ubah lowongan <?= esc($l['nama']) ?>" class="icon-btn" href="<?= esc(url($area . '/lowongan_form.php?id=' . (int) $l['id'])) ?>" title="Ubah"><span aria-hidden="true" class="icon icon--edit"></span></a>
<form method="post" data-confirm="Hapus lowongan ini?" data-confirm-name="<?= esc($l['nama']) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><button aria-label="Hapus lowongan <?= esc($l['nama']) ?>" class="icon-btn" title="Hapus data" type="submit"><span aria-hidden="true" class="icon icon--trash"></span></button></form>
</div></td>
</tr>
<?php endforeach; ?>
<?php if (!$daftar): ?><tr><td colspan="5" class="empty-state">Tidak ada lowongan.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<p class="legend"><span class="legend__title">Keterangan aksi:</span><span class="legend__item"><span aria-hidden="true" class="icon icon--eye icon--sm"></span> Lihat</span><span class="legend__item"><span aria-hidden="true" class="icon icon--edit icon--sm"></span> Ubah</span><span class="legend__item"><span aria-hidden="true" class="icon icon--trash icon--sm"></span> Hapus (butuh konfirmasi)</span></p>
<?= paginasi_html($pg, 'lowongan') ?>
<script src="<?= esc(url('../../backend/js/shared/lowongan-view.js')) ?>?v=3" defer></script>
<?php require __DIR__ . '/../footer.php'; ?>
