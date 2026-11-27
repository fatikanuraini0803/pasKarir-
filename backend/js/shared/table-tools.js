/**
 * table-tools.js - Pencarian dan pagination untuk tabel pada halaman admin.
 *
 * Dipakai oleh: Data Pelatihan, Lowongan dan Persyaratan (Super Admin),
 *               Kelola Lowongan dan Kelola Panduan (Sub Admin).
 *
 * Cara pakai:
 *   const tabel = Paskarir.TableTools.init({
 *     table: document.querySelector('.table'),       // wajib
 *     search: document.getElementById('cari'),       // kolom pencarian (opsional)
 *     pagination: document.querySelector('.pagination'), // nav tombol halaman (opsional)
 *     info: document.querySelector('.table-footer p'),   // teks "Menampilkan ..." (opsional)
 *     pageSize: 5,                                   // jumlah baris per halaman
 *     noun: 'data',                                  // kata benda di teks info
 *     filters: [{ select: el, column: 1 }],          // filter dropdown per kolom (opsional)
 *   });
 *
 * Baris dibaca langsung dari <tbody>, sehingga baris yang ditambah, diubah,
 * atau dihapus oleh skrip lain tetap ikut tercari dan terhitung.
 */
(function (global) {
  'use strict';

  const Paskarir = (global.Paskarir = global.Paskarir || {});

  /** Huruf kecil + hilangkan spasi ganda agar pencarian tidak sensitif huruf besar/kecil. */
  const normalize = (text) => String(text || '').toLowerCase().replace(/\s+/g, ' ').trim();

  const init = (options) => {
    const table = options.table;
    const tbody = table && table.tBodies[0];
    if (!tbody) return null;

    const pageSize = options.pageSize || 5;
    const noun = options.noun || 'data';
    const state = { query: '', page: 1, filters: {} };
    let emptyRow = null;
    let observer = null;

    /** Baris data asli (tanpa baris pesan "tidak ada hasil"). */
    const dataRows = () => Array.from(tbody.rows).filter((row) => row !== emptyRow);

    /** Teks yang bisa dicari: semua kolom kecuali kolom aksi (ikon). */
    const rowText = (row) => normalize(
      Array.from(row.cells)
        .filter((cell) => !cell.querySelector('.actions'))
        .map((cell) => cell.textContent)
        .join(' '));

    const matches = (row) => {
      if (state.query && !rowText(row).includes(state.query)) return false;
      return (options.filters || []).every((filter) => {
        const wanted = state.filters[filter.column];
        if (!wanted) return true;
        const cell = row.cells[filter.column];
        return cell && normalize(cell.textContent) === normalize(wanted);
      });
    };

    const buildEmptyRow = () => {
      const row = document.createElement('tr');
      row.setAttribute('data-empty-row', '');
      const cell = document.createElement('td');
      cell.className = 'empty-state';
      cell.colSpan = table.querySelectorAll('thead th').length || 1;
      cell.textContent = state.query
        ? `Tidak ada ${noun} yang cocok dengan pencarian "${state.query}".`
        : `Tidak ada ${noun} yang cocok.`;
      row.appendChild(cell);
      return row;
    };

    const pageButton = (label, page, extra) => {
      const link = document.createElement('a');
      link.className = 'page-btn';
      link.href = '#';
      link.dataset.page = String(page);
      link.textContent = label;
      if (extra.current) link.setAttribute('aria-current', 'page');
      if (extra.disabled) {
        link.setAttribute('aria-disabled', 'true');
        link.tabIndex = -1;
        link.style.opacity = '.45';
        link.style.pointerEvents = 'none';
      }
      return link;
    };

    const renderPagination = (totalPages) => {
      if (!options.pagination) return;
      const nav = options.pagination;
      nav.replaceChildren();
      nav.appendChild(pageButton('Sebelumnya', state.page - 1, { disabled: state.page <= 1 }));
      for (let page = 1; page <= totalPages; page += 1) {
        nav.appendChild(pageButton(String(page), page, { current: page === state.page }));
      }
      nav.appendChild(pageButton('Next', state.page + 1, { disabled: state.page >= totalPages }));
    };

    const render = () => {
      // Hentikan pengamat sementara agar perubahan kita sendiri tidak memicu render ulang
      if (observer) observer.disconnect();

      if (emptyRow) { emptyRow.remove(); emptyRow = null; }

      const rows = dataRows();
      const found = rows.filter(matches);
      const totalPages = Math.max(1, Math.ceil(found.length / pageSize));
      state.page = Math.min(Math.max(1, state.page), totalPages);
      const start = (state.page - 1) * pageSize;
      const visible = new Set(found.slice(start, start + pageSize));

      rows.forEach((row) => { row.style.display = visible.has(row) ? '' : 'none'; });

      if (!found.length) {
        emptyRow = buildEmptyRow();
        tbody.appendChild(emptyRow);
      }

      if (options.info) {
        options.info.textContent = found.length
          ? `Menampilkan ${start + 1}-${start + visible.size} dari ${found.length} ${noun}`
          : `Menampilkan 0 ${noun}`;
      }
      renderPagination(totalPages);

      if (observer) observer.observe(tbody, { childList: true });
    };

    /* ---------------- Event ---------------- */

    const setQuery = (value) => {
      state.query = normalize(value);
      state.page = 1;
      render();
    };

    if (options.search) {
      const apply = () => setQuery(options.search.value);
      options.search.addEventListener('input', apply);
      options.search.addEventListener('search', apply); // tombol "x" bawaan kolom search
      const form = options.search.closest('form');
      if (form) form.addEventListener('submit', (event) => event.preventDefault());
      options.search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') event.preventDefault();
      });
    }

    (options.filters || []).forEach((filter) => {
      filter.select.addEventListener('change', () => {
        // Pilihan pertama ("Semua ...") berarti tanpa filter
        state.filters[filter.column] = filter.select.selectedIndex === 0 ? '' : filter.select.value;
        state.page = 1;
        render();
      });
    });

    if (options.pagination) {
      options.pagination.addEventListener('click', (event) => {
        const link = event.target.closest('[data-page]');
        if (!link) return;
        event.preventDefault();
        if (link.getAttribute('aria-disabled') === 'true') return;
        state.page = Number(link.dataset.page);
        render();
      });
    }

    // Baris yang ditambah / dihapus oleh skrip lain (hapus, import, edit) -> hitung ulang
    observer = new MutationObserver(render);

    render();
    return { render, setQuery, getState: () => state };
  };

  Paskarir.TableTools = { init, normalize };
})(window);
