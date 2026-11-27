/**
 * auth.js - Halaman login, daftar, dan lupa password.
 * Ketiganya dikenali dari id kolom yang ada di halaman.
 */
(function (global) {
  'use strict';

  const { Rules, Feedback, Store, Session, Validators, bindForm } = global.Paskarir;
  const MAX_LOGIN_ATTEMPTS_BEFORE_HINT = 3;
  const RESET_COOLDOWN_SECONDS = 30;

  const byId = (id) => document.getElementById(id);

  /* ---------------- Login ---------------- */
  const initLogin = () => {
    const form = byId('login-email').closest('form');
    const emailInput = byId('login-email');
    const passwordInput = byId('login-password');
    let failedAttempts = 0;

    const rules = {
      'login-email': [
        Rules.required('Email wajib diisi.'),
        Rules.email('Format email tidak valid. Contoh: nama@email.com'),
      ],
      'login-password': [Rules.required('Password wajib diisi.')],
    };

    bindForm(form, rules, (values) => {
      const user = Store.findUserByEmail(values.email);
      if (!user) {
        Feedback.setFieldError(emailInput, 'Email ini belum terdaftar.');
        Feedback.showAlert(form, 'error', 'Login gagal. Periksa kembali email kamu atau daftar akun baru.');
        emailInput.focus();
        return;
      }
      if (user.password !== values.password) {
        failedAttempts += 1;
        Feedback.setFieldError(passwordInput, 'Password salah.');
        const hint = failedAttempts >= MAX_LOGIN_ATTEMPTS_BEFORE_HINT
          ? ' Sudah beberapa kali salah? Gunakan tautan "Lupa password?".' : '';
        Feedback.showAlert(form, 'error', `Login gagal. Email atau password tidak cocok.${hint}`);
        passwordInput.select();
        return;
      }
      if (user.status === 'Nonaktif') {
        Feedback.showAlert(form, 'error', 'Akun ini sedang dinonaktifkan. Hubungi Super Admin untuk mengaktifkannya kembali.');
        return;
      }
      // Simpan sesi lalu arahkan sesuai role: User, Sub Admin, atau Super Admin
      Session.start(user);
      global.location.href = Store.homeFor(user.role);
    });

    // Daftar akun contoh: klik satu baris untuk mengisi email dan password otomatis
    document.querySelectorAll('[data-demo-email]').forEach((button) => {
      button.addEventListener('click', () => {
        emailInput.value = button.dataset.demoEmail;
        passwordInput.value = button.dataset.demoPassword;
        Feedback.clearFieldError(emailInput);
        Feedback.clearFieldError(passwordInput);
        passwordInput.focus();
      });
    });
  };

  /* ---------------- Daftar ---------------- */
  const initRegister = () => {
    const form = byId('daftar-nama').closest('form');
    const passwordInput = byId('daftar-password');
    const confirmInput = byId('daftar-konfirmasi');

    const rules = {
      'daftar-nama': [
        Rules.required('Nama lengkap wajib diisi.'),
        Rules.minLength(3, 'Nama minimal 3 karakter.'),
        Rules.personName('Nama hanya boleh berisi huruf dan spasi.'),
      ],
      'daftar-email': [
        Rules.required('Email wajib diisi.'),
        Rules.email('Format email tidak valid. Contoh: nama@email.com'),
        Rules.custom((value) => !Store.findUserByEmail(value), 'Email ini sudah terdaftar. Silakan masuk.'),
      ],
      'daftar-password': [
        Rules.required('Password wajib diisi.'),
        Rules.strongPassword('Password minimal 8 karakter dan memuat huruf serta angka.'),
      ],
      'daftar-konfirmasi': [
        Rules.required('Konfirmasi password wajib diisi.'),
        Rules.matches(() => passwordInput.value, 'Konfirmasi tidak sama dengan password.'),
      ],
    };

    const agreeBox = form.querySelector('input[name="setuju"]');
    agreeBox.id = 'daftar-setuju';
    rules['daftar-setuju'] = [Rules.required('Kamu harus menyetujui syarat dan ketentuan.')];

    bindForm(form, rules, (values) => {
      const users = Store.getUsers();
      users.push({
        nama: values.nama.trim(),
        email: values.email.trim().toLowerCase(),
        password: values.password,
        role: 'User',
        telepon: '-',
        status: 'Aktif',
        bergabung: new Date().toISOString().slice(0, 10),
      });
      Store.saveUsers(users);
      global.location.href = 'login.html?status=daftar-berhasil';
    });

    // Indikator kekuatan password muncul saat mengetik (event input)
    const strengthEl = document.createElement('p');
    strengthEl.className = 'field__hint';
    strengthEl.setAttribute('aria-live', 'polite');
    passwordInput.closest('.field').appendChild(strengthEl);
    passwordInput.addEventListener('input', () => {
      const { level, label } = Validators.passwordStrength(passwordInput.value);
      strengthEl.innerHTML = label ? `Kekuatan password: <span class="strength strength--${level}">${label}</span>` : '';
      // Konfirmasi dicek ulang bila sudah pernah diisi
      if (confirmInput.value !== '') confirmInput.dispatchEvent(new Event('change', { bubbles: true }));
    });
  };

  /* ---------------- Lupa password ---------------- */
  const initForgotPassword = () => {
    const form = byId('lupa-email').closest('form');
    const submitButton = form.querySelector('button[type="submit"]');
    const originalLabel = submitButton.textContent;

    /** Menonaktifkan tombol selama jeda agar permintaan tidak dikirim berulang. */
    const startCooldown = () => {
      let remaining = RESET_COOLDOWN_SECONDS;
      submitButton.disabled = true;
      const timer = setInterval(() => {
        remaining -= 1;
        submitButton.textContent = `Kirim ulang dalam ${remaining} detik`;
        if (remaining <= 0) {
          clearInterval(timer);
          submitButton.disabled = false;
          submitButton.textContent = originalLabel;
        }
      }, 1000);
    };

    bindForm(form, {
      'lupa-email': [
        Rules.required('Email wajib diisi.'),
        Rules.email('Format email tidak valid. Contoh: nama@email.com'),
      ],
    }, () => {
      // Pesan sama untuk email terdaftar maupun tidak, agar akun tidak bisa ditebak
      Feedback.showAlert(form, 'success', 'Jika email terdaftar, tautan reset password sudah dikirim. Cek kotak masuk kamu.');
      startCooldown();
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    if (byId('login-email')) initLogin();
    if (byId('daftar-nama')) initRegister();
    if (byId('lupa-email')) initForgotPassword();
  });
})(window);
