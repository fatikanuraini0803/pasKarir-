/**
 * core.js - Modul inti Paskarir (dimuat paling awal di semua halaman).
 *
 * Isi modul:
 *   1. Validators : aturan validasi input (fungsi murni, mudah diuji)
 *   2. Feedback   : pesan error per kolom, alert formulir, banner status
 *   3. Form       : penghubung event blur / input / submit ke aturan validasi
 *   4. Store      : penyimpanan data prototipe di localStorage
 *   5. Session    : login, logout, dan data pengguna aktif
 *
 * Catatan: ini prototipe front-end. Password disimpan apa adanya di
 * localStorage hanya untuk simulasi; pada sistem asli autentikasi
 * harus dikerjakan di backend.
 */
(function (global) {
  'use strict';

  const Paskarir = (global.Paskarir = global.Paskarir || {});

  /* ------------------------------------------------------------------ */
  /* 1. VALIDATORS                                                      */
  /* ------------------------------------------------------------------ */

  const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  const NAME_PATTERN = /^[A-Za-zÀ-ÿ' .-]+$/;

  /** @param {string} value @returns {boolean} true bila kosong / hanya spasi */
  const isEmpty = (value) => String(value == null ? '' : value).trim() === '';

  /** @param {string} value @returns {boolean} true bila format email benar */
  const isEmail = (value) => EMAIL_PATTERN.test(String(value).trim());

  /** @param {string} value @returns {boolean} true bila nama hanya huruf dan tanda baca nama */
  const isPersonName = (value) => NAME_PATTERN.test(String(value).trim());

  /** @returns {boolean} true bila panjang teks (tanpa spasi tepi) >= min */
  const hasMinLength = (value, min) => String(value).trim().length >= min;

  /** @returns {boolean} true bila password >= 8 karakter dan memuat huruf + angka */
  const isStrongPassword = (value) =>
    String(value).length >= 8 && /[A-Za-z]/.test(value) && /\d/.test(value);

  /** @returns {boolean} true bila value berupa bilangan bulat dalam rentang min..max */
  const isIntegerInRange = (value, min, max) => {
    if (isEmpty(value)) return false;
    const number = Number(value);
    return Number.isInteger(number) && number >= min && number <= max;
  };

  /**
   * Menilai kekuatan password.
   * @param {string} value
   * @returns {{level: 'kosong'|'lemah'|'sedang'|'kuat', label: string}}
   */
  const passwordStrength = (value) => {
    if (!value) return { level: 'kosong', label: '' };
    let score = 0;
    if (value.length >= 8) score += 1;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
    if (/\d/.test(value)) score += 1;
    if (/[^A-Za-z0-9]/.test(value)) score += 1;
    if (score <= 1) return { level: 'lemah', label: 'Lemah' };
    if (score <= 2) return { level: 'sedang', label: 'Sedang' };
    return { level: 'kuat', label: 'Kuat' };
  };

  /**
   * Pembuat aturan (rule). Setiap rule menerima nilai kolom dan
   * mengembalikan pesan error, atau string kosong bila lolos.
   */
  const Rules = {
    required: (message) => (value) => (isEmpty(value) ? message : ''),
    email: (message) => (value) => (isEmpty(value) || isEmail(value) ? '' : message),
    personName: (message) => (value) => (isEmpty(value) || isPersonName(value) ? '' : message),
    minLength: (min, message) => (value) => (isEmpty(value) || hasMinLength(value, min) ? '' : message),
    strongPassword: (message) => (value) => (isEmpty(value) || isStrongPassword(value) ? '' : message),
    matches: (getOtherValue, message) => (value) => (value === getOtherValue() ? '' : message),
    differsFrom: (getOtherValue, message) => (value) => (value !== getOtherValue() ? '' : message),
    integerInRange: (min, max, message) => (value) => (isIntegerInRange(value, min, max) ? '' : message),
    custom: (testFn, message) => (value) => (testFn(value) ? '' : message),
  };

  /** Menjalankan daftar rule berurutan; mengembalikan pesan error pertama. */
  const runRules = (value, rules) => {
    for (const rule of rules) {
      const message = rule(value);
      if (message) return message;
    }
    return '';
  };

  /* ------------------------------------------------------------------ */
  /* 2. FEEDBACK                                                        */
  /* ------------------------------------------------------------------ */

  const STATUS_MESSAGES = {
    'daftar-berhasil': 'Akun berhasil dibuat. Silakan masuk dengan email dan password kamu.',
    'password-diubah': 'Password berhasil diubah.',
    'profil-diperbarui': 'Perubahan profil berhasil disimpan.',
    'akun-ditambah': 'Akun baru berhasil ditambahkan.',
    'persyaratan-tersimpan': 'Persyaratan lowongan berhasil disimpan.',
    'pelatihan-tersimpan': 'Data pelatihan berhasil disimpan.',
    'lowongan-tersimpan': 'Data lowongan berhasil disimpan.',
    'keluar': 'Kamu sudah keluar dari akun.',
  };

  /** Wrapper kolom: .field (halaman user) atau .form-group (halaman admin). */
  const fieldWrapper = (input) => input.closest('.field, .form-group') || input.parentElement;

  /** Menampilkan pesan error di bawah kolom dan menandai kolom tidak valid. */
  const setFieldError = (input, message) => {
    const wrapper = fieldWrapper(input);
    const errorId = `${input.id || input.name}-error`;
    let errorEl = wrapper.querySelector(`[data-error-for="${input.id || input.name}"]`);
    if (!errorEl) {
      errorEl = document.createElement('p');
      errorEl.className = 'field__error';
      errorEl.id = errorId;
      errorEl.setAttribute('data-error-for', input.id || input.name);
      errorEl.setAttribute('role', 'alert');
      wrapper.appendChild(errorEl);
    }
    errorEl.textContent = message;
    input.classList.add('is-invalid');
    input.setAttribute('aria-invalid', 'true');
    input.setAttribute('aria-describedby', errorId);
  };

  /** Menghapus pesan error dan penanda tidak valid dari sebuah kolom. */
  const clearFieldError = (input) => {
    const wrapper = fieldWrapper(input);
    const errorEl = wrapper.querySelector(`[data-error-for="${input.id || input.name}"]`);
    if (errorEl) errorEl.remove();
    input.classList.remove('is-invalid');
    input.removeAttribute('aria-invalid');
    input.removeAttribute('aria-describedby');
  };

  /**
   * Menampilkan alert di bagian atas formulir.
   * @param {HTMLElement} container elemen tempat alert disisipkan di awal
   * @param {'error'|'success'|'info'} type
   * @param {string} message
   */
  const showAlert = (container, type, message) => {
    clearAlert(container);
    const alertEl = document.createElement('div');
    alertEl.className = `alert alert--${type}`;
    alertEl.setAttribute('role', type === 'error' ? 'alert' : 'status');
    alertEl.setAttribute('data-alert', '');
    alertEl.textContent = message;
    container.insertBefore(alertEl, container.firstChild);
    return alertEl;
  };

  /** Menghapus alert yang dibuat showAlert pada container. */
  const clearAlert = (container) => {
    container.querySelectorAll(':scope > [data-alert]').forEach((el) => el.remove());
  };

  /** Membaca ?status=... pada URL dan menampilkan banner yang sesuai di <main>. */
  const showStatusFromUrl = () => {
    const status = new URLSearchParams(global.location.search).get('status');
    const main = document.querySelector('main');
    if (!status || !main || !STATUS_MESSAGES[status]) return;
    showAlert(main, 'success', STATUS_MESSAGES[status]);
  };

  /* ------------------------------------------------------------------ */
  /* 3. FORM                                                            */
  /* ------------------------------------------------------------------ */

  /**
   * Menghubungkan validasi ke sebuah formulir.
   *  - blur   : validasi kolom saat ditinggalkan
   *  - input  : bila kolom sudah ditandai salah, validasi ulang saat diketik
   *  - change : validasi ulang untuk select / checkbox / radio
   *  - submit : validasi semua kolom; bila ada yang salah, batalkan pengiriman
   *
   * @param {HTMLFormElement} form
   * @param {Object<string, Function[]>} rulesById peta id kolom -> daftar rule
   * @param {(values: Object) => void} onValid dipanggil bila semua kolom valid
   * @param {{getValue?: Object<string, Function>}} [options] pembaca nilai khusus (mis. chip)
   * @returns {{validateAll: Function, validateOne: Function}}
   */
  const bindForm = (form, rulesById, onValid, options = {}) => {
    const getValue = options.getValue || {};
    form.noValidate = true; // pesan error memakai gaya sendiri, bukan balon bawaan browser

    const readValue = (input) => {
      if (getValue[input.id]) return getValue[input.id]();
      if (input.type === 'checkbox') return input.checked ? 'ya' : '';
      return input.value;
    };

    const validateOne = (input) => {
      const rules = rulesById[input.id];
      if (!rules) return true;
      const message = runRules(readValue(input), rules);
      if (message) setFieldError(input, message);
      else clearFieldError(input);
      return !message;
    };

    const inputOf = (id) => form.querySelector(`#${id}`);

    const validateAll = () => {
      const invalidInputs = Object.keys(rulesById)
        .map(inputOf)
        .filter((input) => input && !validateOne(input));
      return invalidInputs;
    };

    form.addEventListener('focusout', (event) => {
      if (rulesById[event.target.id]) validateOne(event.target);
    });

    form.addEventListener('input', (event) => {
      if (event.target.classList.contains('is-invalid')) validateOne(event.target);
    });

    form.addEventListener('change', (event) => {
      if (rulesById[event.target.id]) validateOne(event.target);
    });

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      clearAlert(form);
      const invalidInputs = validateAll();
      if (invalidInputs.length > 0) {
        showAlert(form, 'error', `Ada ${invalidInputs.length} isian yang belum benar. Periksa pesan di bawah kolom.`);
        invalidInputs[0].focus();
        return;
      }
      const values = {};
      new FormData(form).forEach((value, key) => { values[key] = value; });
      Object.keys(getValue).forEach((id) => { values[id] = getValue[id](); });
      onValid(values);
    });

    return { validateAll, validateOne };
  };

  /* ------------------------------------------------------------------ */
  /* 4. STORE                                                           */
  /* ------------------------------------------------------------------ */

  const KEYS = {
    users: 'paskarir.users',
    session: 'paskarir.session',
    history: 'paskarir.history',
    lastResult: 'paskarir.lastResult',
    seedVersion: 'paskarir.seedVersion',
  };

  /** Naikkan angka ini bila data contoh di bawah diubah; data baru ikut ditambahkan sekali. */
  const SEED_VERSION = 1;

  /** Peran akun. Urutannya dipakai di dropdown. */
  const ROLES = ['User', 'Sub Admin', 'Super Admin'];

  /** Halaman tujuan setelah login (relatif terhadap folder frontend/user/). */
  const ROLE_HOME = {
    'User': 'dashboard.html',
    'Sub Admin': '../sub-admin/dashboard-sub-admin.html',
    'Super Admin': '../super-admin/dashboard-super-admin.html',
  };

  /**
   * AKUN CONTOH. Semuanya bisa dipakai login.
   *   Super Admin : budi@email.com / Super12345   (juga superadmin@email.com)
   *   Sub Admin   : siti@email.com / Admin12345   (juga subadmin@email.com)
   *   User        : andi@email.com / User12345    (nama@email.com / Password123)
   */
  const SEED_USERS = [
    { nama: 'Budi Santoso',  email: 'budi@email.com',       password: 'Super12345',  role: 'Super Admin', telepon: '081234567803', bergabung: '2026-11-08', status: 'Aktif' },
    { nama: 'Nama Super',    email: 'superadmin@email.com', password: 'Super12345',  role: 'Super Admin', telepon: '081234567810', bergabung: '2026-09-01', status: 'Aktif' },
    { nama: 'Siti Rahma',    email: 'siti@email.com',       password: 'Admin12345',  role: 'Sub Admin',   telepon: '081234567802', bergabung: '2026-11-09', status: 'Aktif' },
    { nama: 'Nama Admin',    email: 'subadmin@email.com',   password: 'Admin12345',  role: 'Sub Admin',   telepon: '081234567811', bergabung: '2026-09-01', status: 'Aktif' },
    { nama: 'Andi Pratama',  email: 'andi@email.com',       password: 'User12345',   role: 'User',        telepon: '081234567801', bergabung: '2026-11-10', status: 'Aktif' },
    { nama: 'Dewi Lestari',  email: 'dewi@email.com',       password: 'User12345',   role: 'User',        telepon: '081234567804', bergabung: '2026-11-07', status: 'Aktif' },
    { nama: 'Rizky Maulana', email: 'rizky@email.com',      password: 'User12345',   role: 'User',        telepon: '081234567805', bergabung: '2026-11-06', status: 'Aktif' },
    { nama: 'Nadia Putri',   email: 'nadia@email.com',      password: 'User12345',   role: 'User',        telepon: '081234567806', bergabung: '2026-11-05', status: 'Nonaktif' },
    { nama: 'Nama User',     email: 'nama@email.com',       password: 'Password123', role: 'User',        telepon: '-',            bergabung: '2026-09-01', status: 'Aktif' },
  ];

  /** RIWAYAT KLASIFIKASI CONTOH (terbaru di atas), bentuknya sama dengan hasil form klasifikasi. */
  const SEED_HISTORY = [
    {
      id: 1, tanggalIso: '2026-11-14', tanggal: '14/11/2026', lowongan: 'Staf Pemrograman', label: 'SESUAI', skor: 92,
      alasan: [
        { ok: true, teks: 'Keterampilan utama cocok dengan syarat' },
        { ok: true, teks: 'Pengalaman memenuhi minimal' },
        { ok: true, teks: 'Pendidikan memenuhi syarat' },
      ],
      rekomendasi: [{ nama: 'Analis Data', skor: 85 }, { nama: 'Staf Administrasi', skor: 70 }, { nama: 'Admin Perkantoran', skor: 64 }],
    },
    {
      id: 2, tanggalIso: '2026-11-12', tanggal: '12/11/2026', lowongan: 'Analis Data', label: 'TIDAK SESUAI', skor: 58,
      alasan: [
        { ok: false, teks: 'Keterampilan utama belum cocok dengan syarat' },
        { ok: false, teks: 'Pengalaman kurang dari minimal (2 tahun)' },
        { ok: true, teks: 'Pendidikan memenuhi syarat' },
      ],
      rekomendasi: [{ nama: 'Staf Administrasi', skor: 74 }, { nama: 'Asisten Operasional', skor: 68 }, { nama: 'Staf Layanan', skor: 61 }],
    },
    {
      id: 3, tanggalIso: '2026-11-10', tanggal: '10/11/2026', lowongan: 'Staf Administrasi', label: 'SESUAI', skor: 88,
      alasan: [
        { ok: true, teks: 'Keterampilan utama cocok dengan syarat' },
        { ok: true, teks: 'Pengalaman memenuhi minimal' },
        { ok: true, teks: 'Pendidikan memenuhi syarat' },
      ],
      rekomendasi: [{ nama: 'Admin Perkantoran', skor: 90 }, { nama: 'Asisten Operasional', skor: 76 }, { nama: 'Staf Layanan', skor: 72 }],
    },
    {
      id: 4, tanggalIso: '2026-11-08', tanggal: '08/11/2026', lowongan: 'Asisten Operasional', label: 'SESUAI', skor: 95,
      alasan: [
        { ok: true, teks: 'Keterampilan utama cocok dengan syarat' },
        { ok: true, teks: 'Pengalaman memenuhi minimal' },
        { ok: true, teks: 'Pendidikan memenuhi syarat' },
      ],
      rekomendasi: [{ nama: 'Staf Layanan', skor: 91 }, { nama: 'Staf Administrasi', skor: 80 }, { nama: 'Admin Perkantoran', skor: 77 }],
    },
  ];

  /** Membaca JSON dari localStorage; mengembalikan fallback bila gagal. */
  const readStore = (key, fallback) => {
    try {
      const raw = global.localStorage.getItem(key);
      return raw === null ? fallback : JSON.parse(raw);
    } catch (error) {
      return fallback;
    }
  };

  /** Menulis JSON ke localStorage; mengembalikan false bila penyimpanan gagal. */
  const writeStore = (key, value) => {
    try {
      global.localStorage.setItem(key, JSON.stringify(value));
      return true;
    } catch (error) {
      return false;
    }
  };

  /**
   * Mengisi data contoh satu kali (per SEED_VERSION). Akun yang sudah ada di browser
   * tidak ditimpa; hanya akun contoh yang belum ada yang ditambahkan.
   */
  const ensureSeed = () => {
    if (readStore(KEYS.seedVersion, 0) >= SEED_VERSION) return;
    const stored = readStore(KEYS.users, []);
    const known = new Set(stored.map((user) => String(user.email).toLowerCase()));
    writeStore(KEYS.users, stored.concat(SEED_USERS.filter((user) => !known.has(user.email))));
    if (readStore(KEYS.history, null) === null) {
      writeStore(KEYS.history, SEED_HISTORY);
      writeStore(KEYS.lastResult, SEED_HISTORY[0]);
    }
    writeStore(KEYS.seedVersion, SEED_VERSION);
  };

  /** Melengkapi akun lama yang belum punya role/status/telepon. */
  const normalizeUser = (user) => Object.assign({ role: 'User', status: 'Aktif', telepon: '-' }, user);

  /** Daftar semua akun (data contoh + akun yang dibuat lewat Daftar / Tambah Akun). */
  const getUsers = () => {
    ensureSeed();
    return readStore(KEYS.users, []).map(normalizeUser);
  };

  const findUserByEmail = (email) =>
    getUsers().find((user) => user.email.toLowerCase() === String(email).trim().toLowerCase());

  const saveUsers = (users) => writeStore(KEYS.users, users);

  /** Menambah satu akun baru. */
  const addUser = (user) => saveUsers(getUsers().concat(normalizeUser(user)));

  /** Memperbarui satu akun (dicari lewat email lama) lalu menyimpan semua akun. */
  const updateUser = (oldEmail, changes) => {
    const users = getUsers().map((user) =>
      user.email === oldEmail ? Object.assign({}, user, changes) : user);
    return saveUsers(users);
  };

  /** Menghapus satu akun berdasarkan email. */
  const deleteUser = (email) => saveUsers(getUsers().filter((user) => user.email !== email));

  /* ---- Riwayat klasifikasi ---- */

  /** Daftar riwayat milik browser ini. Mengembalikan null bila belum pernah tersimpan. */
  const getHistory = () => {
    ensureSeed();
    return readStore(KEYS.history, null);
  };

  /**
   * Menghapus satu riwayat berdasarkan id (aksi "Hapus" di halaman Riwayat).
   * Bila yang dihapus adalah hasil terakhir, hasil terakhir diganti riwayat terbaru yang tersisa.
   * @returns {boolean} true bila riwayat ditemukan dan berhasil dihapus
   */
  const deleteHistory = (id) => {
    const history = readStore(KEYS.history, []);
    const remaining = history.filter((record) => record.id !== id);
    if (remaining.length === history.length) return false; // id tidak ditemukan
    if (!writeStore(KEYS.history, remaining)) return false;

    const last = readStore(KEYS.lastResult, null);
    if (last && last.id === id) {
      if (remaining.length > 0) writeStore(KEYS.lastResult, remaining[0]);
      else {
        try { global.localStorage.removeItem(KEYS.lastResult); } catch (error) { /* abaikan */ }
      }
    }
    return true;
  };

  /** Halaman tujuan setelah login sesuai role. */
  const homeFor = (role) => ROLE_HOME[role] || ROLE_HOME.User;

  /** Mengembalikan semua data ke data contoh awal (dipanggil manual dari Console). */
  const resetDemo = () => {
    Object.values(KEYS).forEach((key) => {
      try { global.localStorage.removeItem(key); } catch (error) { /* abaikan */ }
    });
    ensureSeed();
  };

  /* ------------------------------------------------------------------ */
  /* 5. SESSION                                                         */
  /* ------------------------------------------------------------------ */

  const Session = {
    /** Menyimpan sesi login pengguna. */
    start(user) { return writeStore(KEYS.session, { email: user.email, nama: user.nama, role: user.role || 'User' }); },
    /** Mengambil akun yang sedang login, atau null. */
    current() {
      const session = readStore(KEYS.session, null);
      return session ? findUserByEmail(session.email) || null : null;
    },
    /** Menghapus sesi login. */
    end() {
      try { global.localStorage.removeItem(KEYS.session); } catch (error) { /* abaikan */ }
    },
  };

  /* ------------------------------------------------------------------ */
  /* Ekspor                                                             */
  /* ------------------------------------------------------------------ */

  Paskarir.Validators = { isEmpty, isEmail, isPersonName, hasMinLength, isStrongPassword, isIntegerInRange, passwordStrength };
  Paskarir.Rules = Rules;
  Paskarir.Feedback = { setFieldError, clearFieldError, showAlert, clearAlert, showStatusFromUrl };
  Paskarir.bindForm = bindForm;
  Paskarir.Store = {
    KEYS, ROLES, read: readStore, write: writeStore,
    getUsers, findUserByEmail, saveUsers, addUser, updateUser, deleteUser, getHistory, deleteHistory, homeFor, resetDemo,
  };
  Paskarir.Session = Session;
})(window);
