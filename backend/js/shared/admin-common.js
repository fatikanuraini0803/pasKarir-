/**
 * admin-common.js - Bagian yang dipakai bersama oleh Sub Admin dan Super Admin.
 *
 * Isi: aturan validasi yang dipakai ulang (pengalaman, keterampilan, nama, email),
 * formulir profil admin, dan tombol Hapus pada tabel.
 * Skrip khusus tiap peran ada di backend/js/sub-admin/ dan backend/js/super-admin/.
 */
(function (global) {
  'use strict';

  const Paskarir = (global.Paskarir = global.Paskarir || {});
  const { Rules, Feedback, bindForm } = Paskarir;
  const MAX_EXPERIENCE_YEARS = 50;
  const byId = (id) => document.getElementById(id);

  const experienceRule = () => Rules.integerInRange(0, MAX_EXPERIENCE_YEARS,
    `Pengalaman harus bilangan bulat 0 sampai ${MAX_EXPERIENCE_YEARS} tahun.`);

  /** Aturan "minimal satu keterampilan" yang dinilai dari chip. */
  const skillsRule = (chips) => Rules.custom(
    () => chips.getValues().length > 0,
    'Tambahkan minimal satu keterampilan (tekan Enter setelah mengetik).');

  /** Rule dasar untuk kolom nama/email pada profil dan tambah akun. */
  const nameRules = () => [
    Rules.required('Nama lengkap wajib diisi.'),
    Rules.minLength(3, 'Nama minimal 3 karakter.'),
    Rules.personName('Nama hanya boleh berisi huruf dan spasi.'),
  ];
  const emailRules = () => [
    Rules.required('Email wajib diisi.'),
    Rules.email('Format email tidak valid. Contoh: nama@email.com'),
  ];

  /** Menyusun form chip + kolom lain; chip dikirim sebagai nilai terpisah. */
  const chipOptions = (chips) => ({ getValue: { keterampilan: () => chips.getValues() } });

  /* ---------------- Profil admin (Sub Admin & Super Admin) ---------------- */
  const initAdminProfile = () => {
    const { Store, Session } = Paskarir;
    const user = Session.current();
    const NEED_LOGIN = 'Masuk terlebih dahulu agar perubahan bisa disimpan.';

    // Isi form dan kartu profil dengan data akun yang sedang login
    if (user) {
      byId('p-nama').value = user.nama;
      byId('p-email').value = user.email;
      if (byId('p-role')) byId('p-role').value = user.role;
      const name = document.querySelector('.profile-card__name');
      const mail = document.querySelector('.profile-card .muted');
      const avatar = document.querySelector('.profile-card__avatar');
      if (name) name.textContent = user.nama;
      if (mail) mail.textContent = user.email;
      if (avatar) avatar.textContent = user.nama.trim().charAt(0).toUpperCase();
    }

    const profileForm = byId('p-nama').closest('form');
    bindForm(profileForm, {
      'p-nama': nameRules(),
      'p-email': [...emailRules(), Rules.custom((value) => {
        const owner = Store.findUserByEmail(value);
        return !owner || (user && owner.email === user.email);
      }, 'Email ini sudah dipakai akun lain.')],
    }, (values) => {
      if (!user) { Feedback.showAlert(profileForm, 'info', NEED_LOGIN); return; }
      const changes = { nama: values.nama.trim(), email: values.email.trim().toLowerCase() };
      Store.updateUser(user.email, changes);
      Session.start(Object.assign({}, user, changes));
      global.location.href = 'profil.html?status=profil-diperbarui';
    });

    const passwordForm = byId('pw-lama').closest('form');
    bindForm(passwordForm, {
      'pw-lama': [Rules.required('Password lama wajib diisi.'),
        Rules.custom((value) => !user || value === user.password, 'Password lama tidak sesuai.')],
      'pw-baru': [Rules.required('Password baru wajib diisi.'),
        Rules.strongPassword('Password minimal 8 karakter dan memuat huruf serta angka.'),
        Rules.differsFrom(() => byId('pw-lama').value, 'Password baru tidak boleh sama dengan password lama.')],
      'pw-konf': [Rules.required('Konfirmasi password wajib diisi.'),
        Rules.matches(() => byId('pw-baru').value, 'Konfirmasi tidak sama dengan password baru.')],
    }, (values) => {
      if (!user) { Feedback.showAlert(passwordForm, 'info', NEED_LOGIN); return; }
      Store.updateUser(user.email, { password: values.baru });
      global.location.href = 'profil.html?status=password-diubah';
    });
  };

  /* ---------------- Tombol Hapus pada tabel (dialog konfirmasi) ---------------- */
  const TRASH_SVG = '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" ' +
    'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>';

  /** Menampilkan dialog konfirmasi hapus; mengembalikan Promise<boolean>. */
  const confirmDelete = (name) => new Promise((resolve) => {
    const opener = document.activeElement;
    const overlay = document.createElement('div');
    overlay.className = 'del-modal';
    overlay.innerHTML =
      '<div class="del-modal__box" role="alertdialog" aria-modal="true" ' +
      'aria-labelledby="del-title" aria-describedby="del-desc">' +
        '<div class="del-modal__icon">' + TRASH_SVG + '</div>' +
        '<h2 class="del-modal__title" id="del-title">Hapus data ini?</h2>' +
        '<p class="del-modal__name"></p>' +
        '<p class="del-modal__desc" id="del-desc">Data yang dihapus tidak dapat dikembalikan.</p>' +
        '<div class="del-modal__actions">' +
          '<button type="button" class="btn btn--outline del-modal__cancel">Batal</button>' +
          '<button type="button" class="btn btn--danger del-modal__ok">Ya, Hapus</button>' +
        '</div>' +
      '</div>';
    overlay.querySelector('.del-modal__name').textContent = name;
    document.body.appendChild(overlay);
    document.body.classList.add('del-modal-open');

    const cancelBtn = overlay.querySelector('.del-modal__cancel');
    const okBtn = overlay.querySelector('.del-modal__ok');

    const close = (result) => {
      document.removeEventListener('keydown', onKey, true);
      overlay.classList.add('is-closing');
      document.body.classList.remove('del-modal-open');
      setTimeout(() => {
        overlay.remove();
        if (opener && opener.focus) opener.focus();
        resolve(result);
      }, 160);
    };
    const onKey = (e) => {
      if (e.key === 'Escape') { e.preventDefault(); close(false); }
      if (e.key === 'Tab') {            // fokus tetap di dalam dialog
        const items = [cancelBtn, okBtn];
        const i = items.indexOf(document.activeElement);
        e.preventDefault();
        items[(i + (e.shiftKey ? items.length - 1 : 1)) % items.length].focus();
      }
    };
    document.addEventListener('keydown', onKey, true);
    cancelBtn.addEventListener('click', () => close(false));
    okBtn.addEventListener('click', () => close(true));
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(false); });
    requestAnimationFrame(() => { overlay.classList.add('is-open'); cancelBtn.focus(); });
  });

  const initDeleteButtons = () => {
    document.addEventListener('click', async (event) => {
      const button = event.target.closest('button.icon-btn[title="Hapus"]');
      if (!button) return;
      const row = button.closest('tr, li, article');
      if (!row) return;
      const label = (button.getAttribute('aria-label') || 'Hapus data ini').replace(/^Hapus\s+/i, '');
      // Halaman boleh membatalkan penghapusan (event.preventDefault) dengan pesannya sendiri
      const request = new CustomEvent('paskarir:delete-request', { bubbles: true, cancelable: true });
      if (!row.dispatchEvent(request)) return;
      if (!(await confirmDelete(label))) return;
      row.classList.add('row-removing');
      setTimeout(() => {
        // Halaman yang menyimpan datanya (mis. daftar akun) menghapus dari penyimpanan di sini
        row.dispatchEvent(new CustomEvent('paskarir:deleted', { bubbles: true }));
        row.remove();
        Feedback.showAlert(document.querySelector('main'), 'success', 'Data berhasil dihapus.');
      }, 250);
    });
  };

  Paskarir.AdminCommon = { byId, experienceRule, skillsRule, nameRules, emailRules, chipOptions };

  document.addEventListener('DOMContentLoaded', () => {
    if (byId('p-nama')) initAdminProfile();
    initDeleteButtons();
  });
})(window);
