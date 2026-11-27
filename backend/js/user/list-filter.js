/**
 * list-filter.js - Pencarian dan filter pada halaman Lowongan dan Riwayat.
 * Halaman Riwayat juga punya aksi Lihat (ikon mata) dan Hapus (ikon tempat sampah + dialog konfirmasi).
 */
(function (global) {
  'use strict';

  const { Store, Feedback } = global.Paskarir;

  /**
   * Menghubungkan kolom cari + filter ke sekumpulan item.
     @param {Object} config
     @param {HTMLFormElement} config.form
     @param {() => HTMLElement[]} config.getItems daftar elemen yang disaring
     @param {(item: HTMLElement, query: string, filter: string) => boolean} config.matches
     @param {HTMLElement|null} config.countEl teks jumlah hasil (boleh null)
     @param {(shown: number, total: number) => string} config.countText
     @param {HTMLElement} config.emptyEl pesan "tidak ada hasil"
   */
  const bindFilter = ({ form, getItems, matches, countEl, countText, emptyEl }) => {
    const searchInput = form.querySelector('input[type="search"]');
    const filterSelect = form.querySelector('select');

    const apply = () => {
      const items = getItems();
      const query = searchInput.value.trim().toLowerCase();
      const filter = filterSelect.selectedIndex === 0 ? '' : filterSelect.value;
      let shown = 0;
      items.forEach((item) => {
        const visible = matches(item, query, filter);
        item.hidden = !visible;
        if (visible) shown += 1;
      });
      if (countEl) countEl.textContent = countText(shown, items.length); // opsional: tidak semua halaman punya teks jumlah
      emptyEl.hidden = shown !== 0;
    };

    searchInput.addEventListener('input', apply);
    filterSelect.addEventListener('change', apply);
    form.addEventListener('submit', (event) => { event.preventDefault(); apply(); });
    apply();
    return apply; // dipanggil ulang setelah isi tabel berubah (mis. setelah hapus)
  };

  /** Membuat elemen pesan kosong yang disisipkan setelah container. */
  const createEmptyMessage = (afterEl, tagName, text) => {
    const el = document.createElement(tagName);
    el.className = 'empty-state';
    el.textContent = text;
    el.hidden = true;
    afterEl.after(el);
    return el;
  };

  const initVacancies = () => {
    const grid = document.querySelector('.cards-grid');
    const form = document.querySelector('form[role="search"]');
    bindFilter({
      form,
      getItems: () => Array.from(grid.querySelectorAll('.job')),
      matches: (card, query, field) => {
        const text = card.textContent.toLowerCase();
        const cardField = card.querySelector('.job__field').textContent.trim();
        return (!query || text.includes(query)) && (!field || cardField === field);
      },
      countEl: form.querySelector('.toolbar__count'),
      countText: (shown, total) => `Menampilkan ${shown} dari ${total} lowongan`,
      emptyEl: createEmptyMessage(grid, 'p', 'Tidak ada lowongan yang cocok dengan pencarianmu.'),
    });
  };

  const MSG_NO_MATCH = 'Tidak ada riwayat yang cocok.';
  const MSG_EMPTY = 'Belum ada riwayat klasifikasi. Klik "Klasifikasi Baru" untuk memulai.';

  /**
   * Mengisi tabel dari riwayat tersimpan. Tiap baris punya dua aksi:
   * Lihat (ikon mata) dan Hapus (ikon tempat sampah).
   * @param {(record: Object, trigger: HTMLElement) => void} onDelete dipanggil saat tombol hapus ditekan
   */
  const renderHistoryRows = (tbody, history, onDelete) => {
    tbody.replaceChildren(...history.map((record, index) => {
      const row = document.createElement('tr');
      const isMatch = record.label === 'SESUAI';
      row.innerHTML = `<td>${index + 1}</td><td><time datetime="${record.tanggalIso}">${record.tanggal}</time></td>`
        + '<td></td><td><span class="badge"></span></td>'
        + '<td><div class="row-actions">'
        + '<a class="icon-btn" data-action="view" href="hasil.html"><span aria-hidden="true" class="icon icon--eye"></span></a>'
        + '<button class="icon-btn icon-btn--danger" data-action="delete" type="button"><span aria-hidden="true" class="icon icon--trash"></span></button>'
        + '</div></td>';
      row.children[2].textContent = record.lowongan;
      const badge = row.querySelector('.badge');
      badge.classList.add(isMatch ? 'badge--success' : 'badge--danger');
      badge.textContent = isMatch ? 'Sesuai' : 'Tidak Sesuai';

      // Aksi LIHAT: jadikan riwayat ini sebagai hasil yang ditampilkan di hasil.html
      const viewLink = row.querySelector('[data-action="view"]');
      viewLink.setAttribute('aria-label', `Lihat detail ${record.lowongan}`);
      viewLink.addEventListener('click', () => Store.write(Store.KEYS.lastResult, record));

      // Aksi HAPUS: minta konfirmasi dulu (lihat initDeleteDialog)
      const deleteButton = row.querySelector('[data-action="delete"]');
      deleteButton.setAttribute('aria-label', `Hapus riwayat ${record.lowongan}`);
      deleteButton.setAttribute('title', 'Hapus');
      deleteButton.addEventListener('click', () => onDelete(record, deleteButton));
      return row;
    }));
  };

  /** Memperbarui kartu ringkasan (Total / Sesuai / Tidak sesuai) dan teks jumlah data. */
  const updateSummary = (history) => {
    const values = document.querySelectorAll('.stats .stat__value');
    if (values.length >= 3) {
      const matched = history.filter((record) => record.label === 'SESUAI').length;
      values[0].textContent = String(history.length);
      values[1].textContent = String(matched);
      values[2].textContent = String(history.length - matched);
    }
    const info = document.querySelector('.pagination > span');
    if (info) info.textContent = `Menampilkan ${history.length} dari ${history.length} data`;
  };

  /**
   * Dialog konfirmasi hapus (modal #hapus-riwayat pada riwayat.html).
   * Bila modal tidak ada di halaman, dipakai window.confirm sebagai cadangan.
   */
  const initDeleteDialog = () => {
    const dialog = document.getElementById('hapus-riwayat');
    const text = document.getElementById('hapus-teks');
    const confirmButton = document.getElementById('hapus-ya');
    let onConfirm = null;
    let opener = null;

    const close = () => {
      if (dialog) dialog.classList.remove('is-open');
      onConfirm = null;
      if (opener) opener.focus();
    };

    if (dialog && confirmButton) {
      dialog.querySelectorAll('[data-modal-close]').forEach((el) => el.addEventListener('click', close));
      confirmButton.addEventListener('click', () => {
        const run = onConfirm;
        close();
        if (run) run();
      });
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && dialog.classList.contains('is-open')) close();
      });
    }

    return (record, trigger, run) => {
      const message = `Riwayat klasifikasi "${record.lowongan}" tanggal ${record.tanggal} akan dihapus dan tidak bisa dikembalikan.`;
      if (!dialog || !confirmButton) {
        if (global.confirm(message)) run();
        return;
      }
      opener = trigger;
      onConfirm = run;
      text.textContent = message;
      dialog.classList.add('is-open');
      confirmButton.focus();
    };
  };

  const initHistory = () => {
    const table = document.querySelector('.table');
    const tbody = table.querySelector('tbody');
    const form = document.querySelector('form[role="search"]');
    const main = document.querySelector('main');
    const emptyEl = createEmptyMessage(table, 'p', MSG_NO_MATCH);

    // null = riwayat belum pernah tersimpan (penyimpanan tidak tersedia): pakai baris contoh di HTML
    let history = Store.getHistory();
    let applyFilter = () => {};

    const refresh = () => {
      renderHistoryRows(tbody, history, askDelete);
      updateSummary(history);
      emptyEl.textContent = history.length === 0 ? MSG_EMPTY : MSG_NO_MATCH;
      applyFilter();
    };

    const removeRecord = (record) => {
      if (!Store.deleteHistory(record.id)) {
        Feedback.showAlert(main, 'error', 'Riwayat tidak dapat dihapus. Muat ulang halaman lalu coba lagi.');
        return;
      }
      history = history.filter((item) => item.id !== record.id);
      refresh();
      Feedback.showAlert(main, 'success', `Riwayat "${record.lowongan}" berhasil dihapus.`);
    };

    const confirmDelete = initDeleteDialog();
    const askDelete = (record, trigger) => confirmDelete(record, trigger, () => removeRecord(record));

    if (history !== null) {
      renderHistoryRows(tbody, history, askDelete);
      updateSummary(history);
      if (history.length === 0) emptyEl.textContent = MSG_EMPTY;
    }

    applyFilter = bindFilter({
      form,
      getItems: () => Array.from(tbody.querySelectorAll('tr')),
      matches: (row, query, result) => {
        const name = row.children[2].textContent.toLowerCase();
        const badge = row.children[3].textContent.trim().toLowerCase();
        return (!query || name.includes(query)) && (!result || badge === result.toLowerCase());
      },
      countEl: form.querySelector('.toolbar__count'),
      countText: (shown, total) => `Menampilkan ${shown} dari ${total} riwayat`,
      emptyEl,
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.cards-grid')) initVacancies();
    else if (document.querySelector('.table') && document.querySelector('form[role="search"]')) initHistory();
  });
})(window);
