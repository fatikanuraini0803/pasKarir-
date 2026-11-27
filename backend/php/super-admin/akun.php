<?php
/**
 * super-admin/akun.php - Akun dan hak akses (READ daftar akun, DELETE akun). Khusus Super Admin.
 */
require_once __DIR__ . '/../shared/helpers.php';

$user = require_role(['Super Admin']);
$pdo  = db();

/** Jumlah Super Admin aktif selain akun $kecualiId. */
function jumlah_super_admin_lain(PDO $pdo, int $kecualiId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'Super Admin' AND status = 'Aktif' AND id <> :id");
    $stmt->execute([':id' => $kecualiId]);
    return (int) $stmt->fetchColumn();
}

/* ---- DELETE ---- */
if (is_post()) {
    csrf_check();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id === false) {
        flash('error', 'Data yang dihapus tidak valid.');
    } elseif ($id === $user['id']) {
        flash('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
    } else {
        $stmt = $pdo->prepare('SELECT role, status FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $target = $stmt->fetch();
        if (!$target) {
            flash('error', 'Akun tidak ditemukan.');
        } elseif ($target['role'] === 'Super Admin' && $target['status'] === 'Aktif' && jumlah_super_admin_lain($pdo, $id) === 0) {
            flash('error', 'Ini Super Admin aktif terakhir dan tidak bisa dihapus.');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $id]);
            flash('success', 'Akun berhasil dihapus.');
        }
    }
    redirect(url('super-admin/akun.php'));
}

$q    = get_str('q');
$role = get_str('role');
if (!in_array($role, ROLES, true)) {
    $role = '';
}

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(nama LIKE :q1 OR email LIKE :q2)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
}
if ($role !== '') {
    $where[] = 'role = :role';
    $params[':role'] = $role;
}
$stmt = $pdo->prepare('SELECT id, nama, email, telepon, role, status, created_at FROM users'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC, id DESC');
$stmt->execute($params);
$akun = $stmt->fetchAll();
$pg = paginasi($akun);
$filterRole = $role; // header.php menimpa $role, jadi simpan filternya

$judul = 'Akun dan Hak Akses';
$halamanAktif = 'super-admin/akun.php';
require __DIR__ . '/../shared/header.php';
?>
<header class="page-header">
<h1>Akun dan Hak Akses</h1>
<p class="page-header__desc">Kelola akun pengguna dan atur peran (User, Sub Admin, Super Admin).</p>
</header>
<form class="toolbar" method="get">
<div class="search">
<label class="visually-hidden" for="cari">Cari nama atau email</label>
<span aria-hidden="true" class="icon icon--search"></span>
<input class="search__input" id="cari" name="q" placeholder="Cari nama / email" type="search" value="<?= esc($q) ?>">
</div>
<div>
<label class="visually-hidden" for="filter-role">Filter role</label>
<select class="select-pill" id="filter-role" name="role" onchange="this.form.submit()">
<option value="">Semua role</option>
<?php foreach (ROLES as $r): ?><option value="<?= esc($r) ?>" <?= $r === $filterRole ? 'selected' : '' ?>><?= esc($r) ?></option><?php endforeach; ?>
</select>
</div>
<div class="toolbar__end">
<a class="btn btn--primary" href="<?= esc(url('super-admin/akun_form.php')) ?>"><span aria-hidden="true" class="icon icon--plus"></span> Tambah Akun</a>
</div>
</form>
<div class="table-card">
<table class="table">
<caption class="visually-hidden">Daftar akun pengguna</caption>
<thead><tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
<tbody>
<?php foreach ($pg['items'] as $a): ?>
<tr>
<td><?= esc($a['nama']) ?></td>
<td><?= esc($a['email']) ?></td>
<td><span class="badge badge--info"><?= esc($a['role']) ?></span></td>
<td><span class="badge <?= $a['status'] === 'Aktif' ? 'badge--success' : 'badge--danger' ?>"><?= esc($a['status']) ?></span></td>
<td><div class="actions">
<button type="button" class="icon-btn js-lihat-akun" aria-label="Lihat detail akun <?= esc($a['nama']) ?>" title="Lihat detail"
 data-id="<?= (int) $a['id'] ?>" data-nama="<?= esc($a['nama']) ?>" data-email="<?= esc($a['email']) ?>" data-telepon="<?= esc($a['telepon']) ?>"
 data-role="<?= esc($a['role']) ?>" data-status="<?= esc($a['status']) ?>" data-bergabung="<?= esc(date('d/m/Y H:i', strtotime((string) $a['created_at']))) ?>"
 data-ubah="<?= esc(url('super-admin/akun_form.php?id=' . (int) $a['id'])) ?>"><span aria-hidden="true" class="icon icon--eye"></span></button>
<a aria-label="Ubah akun <?= esc($a['nama']) ?>" class="icon-btn" href="<?= esc(url('super-admin/akun_form.php?id=' . (int) $a['id'])) ?>" title="Ubah"><span aria-hidden="true" class="icon icon--edit"></span></a>
<?php if ((int) $a['id'] !== $user['id']): ?>
<form method="post" data-confirm="Hapus akun ini?" data-confirm-name="<?= esc($a['nama']) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><button aria-label="Hapus akun <?= esc($a['nama']) ?>" class="icon-btn" title="Hapus data" type="submit"><span aria-hidden="true" class="icon icon--trash"></span></button></form>
<?php else: ?><span aria-disabled="true" aria-label="Akun yang sedang dipakai tidak bisa dihapus" class="icon-btn is-disabled" title="Akun yang sedang dipakai tidak bisa dihapus"><span aria-hidden="true" class="icon icon--trash"></span></span>
<?php endif; ?>
</div></td>
</tr>
<?php endforeach; ?>
<?php if (!$akun): ?><tr><td colspan="5" class="empty-state">Tidak ada akun.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= paginasi_html($pg, 'akun') ?>
<aside class="note"><p>Hanya Super Admin yang dapat mengubah role dan menghapus akun. Akun Sub Admin dibuat di halaman ini.</p></aside>

<!-- Dialog detail akun (muncul saat ikon mata diklik) -->
<style>
.akun-modal{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;
background:rgba(24,36,90,.35);-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);opacity:0;transition:opacity .16s ease;font-family:Poppins,system-ui,sans-serif}
.akun-modal[hidden]{display:none}
.akun-modal.is-open{opacity:1}
.akun-modal__box{position:relative;width:min(440px,100%);box-sizing:border-box;padding:28px;background:#fff;color:#172033;border-radius:20px;
border-top:5px solid #2563eb;box-shadow:0 24px 60px rgba(24,36,90,.28);transform:translateY(12px) scale(.96);transition:transform .22s cubic-bezier(.2,1.3,.4,1)}
.akun-modal.is-open .akun-modal__box{transform:none}
.akun-modal__close{position:absolute;top:12px;right:12px;width:34px;height:34px;border:0;border-radius:50%;background:#f1f5f9;color:#172033;font-size:20px;line-height:1;cursor:pointer}
.akun-modal__close:hover{background:#e2e8f0}
.akun-modal__head{display:flex;align-items:center;gap:14px;margin-bottom:18px}
.akun-modal__avatar{display:grid;place-items:center;flex:none;width:56px;height:56px;border-radius:50%;background:#dbeafe;color:#1d4ed8;font-size:1.4rem;font-weight:700}
.akun-modal__nama{margin:0;font-size:1.15rem;font-weight:700;color:#021a4a;overflow-wrap:anywhere}
.akun-modal__email{margin:2px 0 0;font-size:.85rem;color:#4b5563;overflow-wrap:anywhere}
.akun-modal__list{margin:0;padding:0;display:grid;gap:0}
.akun-modal__row{display:flex;justify-content:space-between;gap:16px;padding:11px 0;border-top:1px solid #e5e7eb;font-size:.875rem}
.akun-modal__row dt{color:#6b7280}
.akun-modal__row dd{margin:0;font-weight:600;text-align:right;overflow-wrap:anywhere}
.akun-modal__actions{display:flex;gap:12px;margin-top:20px}
.akun-modal__actions .btn{flex:1;justify-content:center;height:42px;cursor:pointer}
body.akun-modal-open{overflow:hidden}
</style>
<div class="akun-modal" id="akun-modal" hidden>
<div class="akun-modal__box" role="dialog" aria-modal="true" aria-labelledby="akun-modal-nama">
<button type="button" class="akun-modal__close" id="akun-modal-x" aria-label="Tutup">&times;</button>
<div class="akun-modal__head">
<div class="akun-modal__avatar" id="akun-modal-avatar" aria-hidden="true">?</div>
<div><h2 class="akun-modal__nama" id="akun-modal-nama"></h2><p class="akun-modal__email" id="akun-modal-email"></p></div>
</div>
<dl class="akun-modal__list">
<div class="akun-modal__row"><dt>Role</dt><dd><span class="badge badge--info" id="akun-modal-role"></span></dd></div>
<div class="akun-modal__row"><dt>Status</dt><dd><span class="badge" id="akun-modal-status"></span></dd></div>
<div class="akun-modal__row"><dt>Telepon</dt><dd id="akun-modal-telepon"></dd></div>
<div class="akun-modal__row"><dt>Bergabung</dt><dd id="akun-modal-bergabung"></dd></div>
</dl>
<div class="akun-modal__actions">
<a class="btn btn--outline" id="akun-modal-ubah" href="#"><span aria-hidden="true" class="icon icon--edit"></span> Ubah Akun</a>
<button type="button" class="btn btn--primary" id="akun-modal-tutup"><span aria-hidden="true" class="icon icon--close"></span> Tutup</button>
</div>
</div>
</div>
<script>
(function () {
  'use strict';
  var modal = document.getElementById('akun-modal');
  if (!modal) return;
  var $ = function (id) { return document.getElementById(id); };
  var pemicu = null;

  function buka(tombol) {
    var d = tombol.dataset;
    pemicu = tombol;
    $('akun-modal-nama').textContent = d.nama;
    $('akun-modal-email').textContent = d.email;
    $('akun-modal-avatar').textContent = (d.nama || '?').charAt(0).toUpperCase();
    $('akun-modal-role').textContent = d.role;
    var st = $('akun-modal-status');
    st.textContent = d.status;
    st.className = 'badge ' + (d.status === 'Aktif' ? 'badge--success' : 'badge--danger');
    $('akun-modal-telepon').textContent = d.telepon && d.telepon !== '-' ? d.telepon : 'Belum diisi';
    $('akun-modal-bergabung').textContent = d.bergabung;
    $('akun-modal-ubah').setAttribute('href', d.ubah);
    modal.hidden = false;
    document.body.classList.add('akun-modal-open');
    requestAnimationFrame(function () { modal.classList.add('is-open'); });
    $('akun-modal-tutup').focus();
  }

  function tutup() {
    modal.classList.remove('is-open');
    document.body.classList.remove('akun-modal-open');
    setTimeout(function () { modal.hidden = true; }, 160);
    if (pemicu) pemicu.focus();
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target.closest('.js-lihat-akun') : null;
    if (t) { buka(t); return; }
    if (e.target === modal || (e.target.closest && e.target.closest('#akun-modal-x, #akun-modal-tutup'))) tutup();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) tutup();
  });
})();
</script>
<?php require __DIR__ . '/../shared/footer.php'; ?>
