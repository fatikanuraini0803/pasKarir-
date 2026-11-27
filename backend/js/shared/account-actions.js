/**
 * account-actions.js - Halaman Akun dan Hak Akses (Super Admin).
 *
 * Semua data akun dibaca dari dan disimpan ke Paskarir.Store (localStorage),
 * jadi perubahan tetap ada setelah halaman dimuat ulang dan akun yang ditambah
 * atau diubah di sini bisa langsung dipakai login.
 *
 * Fitur: tampil per halaman, cari nama/email, filter role,
 *        lihat detail, ubah (nama, email, telepon, role, status, reset password),
 *        dan hapus (dialog konfirmasi ada di admin-common.js).
 */
(function (global) {
  'use strict';

  const { Feedback, Store, Session, Validators } = global.Paskarir;
  const PAGE_SIZE = 6;

  const tbody = document.querySelector('.table tbody');
  const modal = document.getElementById('accountModal');
  if (!tbody || !modal) return;

  const modalTitle = document.getElementById('accountModalTitle');
  const modalContent = document.getElementById('accountModalContent');
  const searchInput = document.getElementById('cari');
  const roleSelect = document.getElementById('filter-role');
  const footerText = document.querySelector('.table-footer p');
  const pagination = document.querySelector('.table-footer .pagination');
  const main = document.querySelector('main');

  const state = { query: '', role: '', page: 1 };

  /* ---------------- Fungsi bantu ---------------- */

  /** Mengamankan teks sebelum dimasukkan ke innerHTML. */
  const esc = (value) => String(value).replace(/[&<>"']/g, (char) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));

  /** 2026-11-10 -> 10-11-2026 */
  const formatDate = (iso) => (iso ? String(iso).split('-').reverse().join('-') : '-');

  const currentEmail = () => {
    const session = Session.current();
    return session ? session.email : null;
  };

  /** Jumlah Super Admin aktif; dipakai agar sistem tidak kehilangan Super Admin terakhir. */
  const activeSuperAdmins = () =>
    Store.getUsers().filter((user) => user.role === 'Super Admin' && user.status === 'Aktif');

  const isLastSuperAdmin = (email) => {
    const list = activeSuperAdmins();
    return list.length === 1 && list[0].email === email;
  };

  /** Akun yang cocok dengan kata kunci dan filter role. */
  const filteredUsers = () => {
    const query = state.query.trim().toLowerCase();
    return Store.getUsers().filter((user) => {
      const matchText = !query
        || user.nama.toLowerCase().includes(query)
        || user.email.toLowerCase().includes(query);
      return matchText && (!state.role || user.role === state.role);
    });
  };

  /* ---------------- Tabel ---------------- */

  const rowHtml = (user) => `
    <tr data-email="${esc(user.email)}">
      <td>${esc(user.nama)}</td>
      <td>${esc(user.email)}</td>
      <td>
        <span class="badge badge--info">${esc(user.role)}</span>
        ${user.status === 'Nonaktif' ? '<span class="badge badge--danger">Nonaktif</span>' : ''}
      </td>
      <td>
        <div class="actions">
          <a aria-label="Lihat ${esc(user.nama)}" class="icon-btn" data-action="view" href="#" title="Lihat"><span aria-hidden="true" class="icon icon--eye"></span></a>
          <a aria-label="Ubah ${esc(user.nama)}" class="icon-btn" data-action="edit" href="#" title="Ubah"><span aria-hidden="true" class="icon icon--edit"></span></a>
          <button aria-label="Hapus ${esc(user.nama)}" class="icon-btn" title="Hapus" type="button"><span aria-hidden="true" class="icon icon--trash"></span></button>
        </div>
      </td>
    </tr>`;

  const renderPagination = (totalPages) => {
    if (!pagination) return;
    const pageButton = (label, page, current, disabled) =>
      `<a class="page-btn" data-page="${page}" href="#"${current ? ' aria-current="page"' : ''}${disabled ? ' aria-disabled="true" style="opacity:.45;pointer-events:none"' : ''}>${label}</a>`;
    let html = pageButton('Sebelumnya', state.page - 1, false, state.page <= 1);
    for (let page = 1; page <= totalPages; page += 1) {
      html += pageButton(page, page, page === state.page, false);
    }
    html += pageButton('Next', state.page + 1, false, state.page >= totalPages);
    pagination.innerHTML = html;
  };

  const render = () => {
    const users = filteredUsers();
    const totalPages = Math.max(1, Math.ceil(users.length / PAGE_SIZE));
    state.page = Math.min(state.page, totalPages);
    const start = (state.page - 1) * PAGE_SIZE;
    const pageUsers = users.slice(start, start + PAGE_SIZE);

    tbody.innerHTML = pageUsers.length
      ? pageUsers.map(rowHtml).join('')
      : '<tr><td class="empty-state" colspan="4">Tidak ada akun yang cocok.</td></tr>';

    if (footerText) {
      footerText.textContent = users.length
        ? `Menampilkan ${start + 1}-${start + pageUsers.length} dari ${users.length} akun`
        : 'Menampilkan 0 akun';
    }
    renderPagination(totalPages);
  };

  /* ---------------- Modal ---------------- */

  const openModal = () => {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  const detailItem = (label, value) => `
    <div class="account-detail__item">
      <span class="account-detail__label">${esc(label)}</span>
      <span class="account-detail__value">${esc(value)}</span>
    </div>`;

  const showDetail = (user) => {
    modalTitle.textContent = 'Detail Akun';
    modalContent.innerHTML = `
      ${detailItem('Nama Pengguna', user.nama)}
      ${detailItem('Email', user.email)}
      ${detailItem('Nomor Telepon', user.telepon || '-')}
      ${detailItem('Role', user.role)}
      ${detailItem('Tanggal Pendaftaran', formatDate(user.bergabung))}
      ${detailItem('Status Akun', user.status)}
      <div class="account-modal__actions">
        <button class="account-btn-primary" data-modal-close type="button">Tutup</button>
      </div>`;
    openModal();
  };

  const showEdit = (user) => {
    const options = (list, selected) => list.map((item) =>
      `<option value="${esc(item)}"${item === selected ? ' selected' : ''}>${esc(item)}</option>`).join('');

    modalTitle.textContent = 'Ubah Data Akun';
    modalContent.innerHTML = `
      <form class="account-form" data-old-email="${esc(user.email)}" id="accountEditForm" novalidate>
        <label>Nama Pengguna
          <input id="edit-nama" name="nama" required type="text" value="${esc(user.nama)}">
        </label>
        <label>Email
          <input id="edit-email" name="email" required type="email" value="${esc(user.email)}">
        </label>
        <label>Nomor Telepon
          <input id="edit-telepon" name="telepon" type="text" value="${esc(user.telepon && user.telepon !== '-' ? user.telepon : '')}" placeholder="08xxxxxxxxxx">
        </label>
        <label>Role
          <select id="edit-role" name="role">${options(Store.ROLES, user.role)}</select>
        </label>
        <label>Status Akun
          <select id="edit-status" name="status">${options(['Aktif', 'Nonaktif'], user.status)}</select>
        </label>
        <label>Reset Password (opsional)
          <span class="pw-wrap"><input id="edit-password" name="password" placeholder="Kosongkan bila tidak diubah" type="password" autocomplete="new-password"><button aria-controls="edit-password" aria-label="Tampilkan password" aria-pressed="false" class="pw-toggle" data-toggle-password="edit-password" type="button"><span aria-hidden="true" class="icon icon--eye"></span></button></span>
        </label>
        <div class="account-modal__actions">
          <button class="account-btn-cancel" data-modal-close type="button">Batal</button>
          <button class="account-btn-primary" type="submit">Simpan Perubahan</button>
        </div>
      </form>`;
    openModal();
    document.getElementById('edit-nama').focus();
  };

  /** Memeriksa isian form ubah akun. Mengembalikan true bila semua benar. */
  const validateEdit = (form, oldEmail) => {
    const field = (id) => document.getElementById(id);
    let valid = true;
    const check = (input, ok, message) => {
      if (ok) { Feedback.clearFieldError(input); return; }
      Feedback.setFieldError(input, message);
      if (valid) input.focus();
      valid = false;
    };

    const nama = field('edit-nama');
    const email = field('edit-email');
    const role = field('edit-role');
    const status = field('edit-status');
    const password = field('edit-password');
    const owner = Store.findUserByEmail(email.value);

    check(nama, nama.value.trim().length >= 3 && Validators.isPersonName(nama.value.trim()),
      'Nama minimal 3 karakter dan hanya berisi huruf dan spasi.');
    const emailOk = Validators.isEmail(email.value.trim());
    check(email, emailOk && (!owner || owner.email === oldEmail),
      emailOk ? 'Email ini sudah dipakai akun lain.' : 'Format email tidak valid.');
    check(password, !password.value || Validators.isStrongPassword(password.value),
      'Password minimal 8 karakter dan memuat huruf serta angka.');
    // Jangan sampai tidak ada Super Admin aktif yang tersisa
    const stillSuper = role.value === 'Super Admin' && status.value === 'Aktif';
    check(role, !isLastSuperAdmin(oldEmail) || stillSuper,
      'Ini Super Admin aktif terakhir. Role dan statusnya tidak bisa diubah.');
    return valid;
  };

  /* ---------------- Event ---------------- */

  // Klik ikon lihat / ubah pada baris tabel
  tbody.addEventListener('click', (event) => {
    const button = event.target.closest('a[data-action]');
    if (!button) return;
    event.preventDefault();
    const user = Store.findUserByEmail(button.closest('tr').dataset.email);
    if (!user) return;
    if (button.dataset.action === 'view') showDetail(user);
    else showEdit(user);
  });

  // Simpan perubahan akun
  modal.addEventListener('submit', (event) => {
    if (event.target.id !== 'accountEditForm') return;
    event.preventDefault();
    const oldEmail = event.target.dataset.oldEmail;
    if (!validateEdit(event.target, oldEmail)) return;

    const value = (id) => document.getElementById(id).value.trim();
    const changes = {
      nama: value('edit-nama'),
      email: value('edit-email').toLowerCase(),
      telepon: value('edit-telepon') || '-',
      role: value('edit-role'),
      status: value('edit-status'),
    };
    if (value('edit-password')) changes.password = value('edit-password');

    Store.updateUser(oldEmail, changes);
    // Bila yang diubah adalah akun yang sedang login, perbarui sesinya juga
    if (oldEmail === currentEmail()) Session.start(Store.findUserByEmail(changes.email));
    closeModal();
    render();
    Feedback.showAlert(main, 'success', `Akun ${changes.nama} berhasil diperbarui.`);
  });

  modal.addEventListener('click', (event) => {
    if (event.target.closest('[data-modal-close]')) closeModal();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  // Hapus: dialog konfirmasi ada di admin-common.js; di sini hanya aturan dan penyimpanannya
  tbody.addEventListener('paskarir:delete-request', (event) => {
    const email = event.target.dataset.email;
    if (email === currentEmail()) {
      event.preventDefault();
      Feedback.showAlert(main, 'error', 'Akun yang sedang kamu pakai tidak bisa dihapus.');
    } else if (isLastSuperAdmin(email)) {
      event.preventDefault();
      Feedback.showAlert(main, 'error', 'Super Admin aktif terakhir tidak bisa dihapus.');
    }
  });
  tbody.addEventListener('paskarir:deleted', (event) => {
    Store.deleteUser(event.target.dataset.email);
    render();
  });

  // Cari, filter, dan pindah halaman
  if (searchInput) {
    searchInput.addEventListener('input', () => { state.query = searchInput.value; state.page = 1; render(); });
    const searchForm = searchInput.closest('form');
    if (searchForm) searchForm.addEventListener('submit', (event) => event.preventDefault());
  }
  if (roleSelect) {
    roleSelect.addEventListener('change', () => {
      state.role = roleSelect.selectedIndex === 0 ? '' : roleSelect.value;
      state.page = 1;
      render();
    });
  }
  if (pagination) {
    pagination.addEventListener('click', (event) => {
      const link = event.target.closest('[data-page]');
      if (!link) return;
      event.preventDefault();
      state.page = Number(link.dataset.page);
      render();
    });
  }

  render();
})(window);
