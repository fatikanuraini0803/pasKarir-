/**
 * super-admin/data-pelatihan.js - Halaman Data Pelatihan (Super Admin).
 *
 * Fitur: pencarian, tombol Sebelumnya / Next, ringkasan angka, dan Import CSV.
 * Format CSV: keterampilan, pendidikan, pengalaman, sertifikasi, label
 * (pemisah koma atau titik koma; baris judul kolom boleh ada atau tidak).
 * Data hasil import disimpan di localStorage agar tidak hilang saat halaman dimuat ulang.
 */
(function (global) {
  'use strict';

  const { Feedback, Store } = global.Paskarir;
  const byId = (id) => document.getElementById(id);

  const table = document.querySelector('.table');
  const tbody = table && table.tBodies[0];
  const main = document.querySelector('main');
  if (!tbody) return;

  const STORAGE_KEY = 'paskarir.pelatihan.import';
  const MAX_FILE_BYTES = 2 * 1024 * 1024;   // 2 MB
  const EDUCATION = ['SMA', 'D3', 'S1', 'S2'];
  const MAX_EXPERIENCE = 50;

  const ACTIONS_HTML =
    '<div class="actions">' +
    '<a aria-label="Lihat data pelatihan" class="icon-btn" href="#" title="Lihat"><span aria-hidden="true" class="icon icon--eye"></span></a>' +
    '<a aria-label="Ubah data pelatihan" class="icon-btn" href="form-data-pelatihan.html" title="Ubah"><span aria-hidden="true" class="icon icon--edit"></span></a>' +
    '<button aria-label="Hapus data pelatihan" class="icon-btn" title="Hapus" type="button"><span aria-hidden="true" class="icon icon--trash"></span></button>' +
    '</div>';

  /* ---------------- Penyimpanan data hasil import ---------------- */

  const loadSaved = () => {
    const saved = Store.read(STORAGE_KEY, { rows: [], updated: '' });
    return { rows: Array.isArray(saved.rows) ? saved.rows : [], updated: saved.updated || '' };
  };
  const saved = loadSaved();
  const persist = () => Store.write(STORAGE_KEY, saved);

  /* ---------------- Baris tabel ---------------- */

  const buildRow = (item) => {
    const tr = document.createElement('tr');
    tr.dataset.importId = item.id;

    [item.keterampilan, item.pendidikan, `${item.pengalaman} th`, item.sertifikasi].forEach((text) => {
      const td = document.createElement('td');
      td.textContent = text;
      tr.appendChild(td);
    });

    const labelTd = document.createElement('td');
    const badge = document.createElement('span');
    const sesuai = item.label === 'Sesuai';
    badge.className = `badge ${sesuai ? 'badge--success' : 'badge--danger'}`;
    badge.textContent = item.label;
    labelTd.appendChild(badge);
    tr.appendChild(labelTd);

    const actionTd = document.createElement('td');
    actionTd.innerHTML = ACTIONS_HTML;        // HTML tetap, tanpa data dari file
    tr.appendChild(actionTd);
    return tr;
  };

  /** Menaruh data hasil import di bagian atas tabel agar langsung terlihat. */
  const prependRows = (items) => {
    const fragment = document.createDocumentFragment();
    items.forEach((item) => fragment.appendChild(buildRow(item)));
    tbody.insertBefore(fragment, tbody.firstChild);
  };

  /* ---------------- Ringkasan angka ---------------- */

  const updateStats = () => {
    const rows = Array.from(tbody.rows).filter((row) => !row.hasAttribute('data-empty-row'));
    const tidak = rows.filter((row) => /tidak/i.test(row.cells[4] ? row.cells[4].textContent : '')).length;
    const set = (id, value) => { const el = byId(id); if (el) el.textContent = value; };
    set('statTotal', rows.length);
    set('statSesuai', rows.length - tidak);
    set('statTidak', tidak);
    if (saved.updated) set('statUpdate', saved.updated);
  };

  /* ---------------- Parser CSV ---------------- */

  /** Pemisah yang dipakai: koma atau titik koma (Excel versi Indonesia), dilihat dari baris pertama. */
  const detectDelimiter = (text) => {
    let commas = 0;
    let semicolons = 0;
    let quoted = false;
    for (const char of text) {
      if (char === '"') quoted = !quoted;
      else if (!quoted && char === '\n') break;
      else if (!quoted && char === ',') commas += 1;
      else if (!quoted && char === ';') semicolons += 1;
    }
    return semicolons > commas ? ';' : ',';
  };

  /** Mengubah teks CSV menjadi array baris (mendukung tanda kutip dan koma di dalam kutip). */
  const parseCsv = (raw) => {
    const text = raw.replace(/^\uFEFF/, '');
    const delimiter = detectDelimiter(text);
    const rows = [];
    let row = [];
    let field = '';
    let quoted = false;

    const endField = () => { row.push(field); field = ''; };
    const endRow = () => {
      endField();
      if (row.some((cell) => cell.trim() !== '')) rows.push(row);
      row = [];
    };

    for (let i = 0; i < text.length; i += 1) {
      const char = text[i];
      if (quoted) {
        if (char === '"' && text[i + 1] === '"') { field += '"'; i += 1; }
        else if (char === '"') quoted = false;
        else field += char;
      } else if (char === '"') quoted = true;
      else if (char === delimiter) endField();
      else if (char === '\n') endRow();
      else if (char !== '\r') field += char;
    }
    endRow();
    return rows;
  };

  /** Memeriksa satu baris CSV. Mengembalikan { item } atau { error }. */
  const validateRow = (cells) => {
    if (cells.length !== 5) return { error: `jumlah kolom ${cells.length}, seharusnya 5` };
    const [skills, edu, exp, cert, label] = cells.map((cell) => cell.trim());

    const keterampilan = skills.split(/[;,|]/).map((item) => item.trim()).filter(Boolean).join(', ');
    if (!keterampilan) return { error: 'keterampilan kosong' };

    const pendidikan = EDUCATION.find((item) => item.toLowerCase() === edu.toLowerCase());
    if (!pendidikan) return { error: `pendidikan "${edu}" tidak dikenal (${EDUCATION.join(', ')})` };

    const years = Number(exp.replace(/\s*(th|tahun)\s*$/i, ''));
    if (exp === '' || !Number.isInteger(years) || years < 0 || years > MAX_EXPERIENCE) {
      return { error: `pengalaman harus bilangan bulat 0-${MAX_EXPERIENCE}` };
    }

    const certKey = cert.toLowerCase();
    if (certKey !== 'ya' && certKey !== 'tidak') return { error: 'sertifikasi harus Ya atau Tidak' };

    const labelKey = label.toLowerCase().replace(/[-_]/g, ' ');
    if (labelKey !== 'sesuai' && labelKey !== 'tidak sesuai') return { error: 'label harus Sesuai atau Tidak Sesuai' };

    return {
      item: {
        id: `imp-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`,
        keterampilan,
        pendidikan,
        pengalaman: years,
        sertifikasi: certKey === 'ya' ? 'Ya' : 'Tidak',
        label: labelKey === 'sesuai' ? 'Sesuai' : 'Tidak Sesuai',
      },
    };
  };

  const isHeaderRow = (cells) => /keterampilan/i.test(cells[0] || '') && /pendidikan/i.test(cells[1] || '');

  /* ---------------- Import ---------------- */

  const todayLabel = () => {
    const now = new Date();
    return `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}`;
  };

  const importText = (raw, tableTools, searchInput) => {
    const parsed = parseCsv(raw);
    const startIndex = parsed.length && isHeaderRow(parsed[0]) ? 1 : 0;
    const items = [];
    const problems = [];

    parsed.slice(startIndex).forEach((cells, index) => {
      const lineNumber = index + startIndex + 1;
      const result = validateRow(cells);
      if (result.item) items.push(result.item);
      else problems.push(`baris ${lineNumber}: ${result.error}`);
    });

    if (!items.length) {
      const detail = problems.length ? ` ${problems[0]}.` : ' File tidak berisi data.';
      Feedback.showAlert(main, 'error', `Import gagal, tidak ada data yang valid.${detail} Format: keterampilan, pendidikan, pengalaman, sertifikasi, label.`);
      return;
    }

    saved.rows = items.concat(saved.rows);
    saved.updated = todayLabel();
    persist();
    prependRows(items);

    // Kosongkan pencarian supaya data baru langsung terlihat di halaman pertama
    if (searchInput) searchInput.value = '';
    tableTools.setQuery('');
    updateStats();

    let message = `${items.length} data berhasil diimpor.`;
    if (problems.length) {
      const shown = problems.slice(0, 3).join('; ');
      message += ` ${problems.length} baris dilewati (${shown}${problems.length > 3 ? '; ...' : ''}).`;
    }
    Feedback.showAlert(main, problems.length ? 'info' : 'success', message);
  };

  /* ---------------- Mulai ---------------- */

  if (saved.rows.length) prependRows(saved.rows);

  const searchInput = byId('cari');
  const tableTools = global.Paskarir.TableTools.init({
    table,
    search: searchInput,
    pagination: document.querySelector('.table-footer .pagination'),
    info: byId('infoTabel'),
    pageSize: 3,
    noun: 'data',
  });

  updateStats();
  new MutationObserver(updateStats).observe(tbody, { childList: true });

  // Data hasil import yang dihapus ikut dihapus dari penyimpanan
  tbody.addEventListener('paskarir:deleted', (event) => {
    const id = event.target.dataset.importId;
    if (!id) return;
    saved.rows = saved.rows.filter((item) => item.id !== id);
    persist();
  });

  const importButton = byId('btnImportCsv');
  const fileInput = byId('inputImportCsv');
  if (importButton && fileInput) {
    importButton.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0];
      fileInput.value = '';                       // agar file yang sama bisa dipilih lagi
      if (!file) return;

      if (!/\.csv$/i.test(file.name) && file.type !== 'text/csv') {
        Feedback.showAlert(main, 'error', 'File harus berformat CSV (.csv).');
        return;
      }
      if (file.size > MAX_FILE_BYTES) {
        Feedback.showAlert(main, 'error', 'Ukuran file terlalu besar. Maksimal 2 MB.');
        return;
      }

      const reader = new FileReader();
      reader.onload = () => importText(String(reader.result), tableTools, searchInput);
      reader.onerror = () => Feedback.showAlert(main, 'error', 'File tidak bisa dibaca. Coba pilih file lain.');
      reader.readAsText(file);
    });
  }
})(window);
