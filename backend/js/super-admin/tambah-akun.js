/**
 * super-admin/tambah-akun.js - Formulir tambah akun (Super Admin).
 * Dimuat oleh frontend/super-admin/tambah-akun.html.
 */
(function (global) {
  'use strict';

  const { Rules, Store, bindForm } = global.Paskarir;
  const { byId, nameRules, emailRules } = global.Paskarir.AdminCommon;

  const initAddAccount = () => {
    const form = byId('t-nama').closest('form');
    bindForm(form, {
      't-nama': nameRules(),
      't-email': [...emailRules(),
        Rules.custom((value) => !Store.findUserByEmail(value), 'Email ini sudah terdaftar.')],
      't-role': [Rules.required('Pilih role untuk akun ini.')],
      't-pw': [Rules.required('Password wajib diisi.'),
        Rules.strongPassword('Password minimal 8 karakter dan memuat huruf serta angka.')],
      't-konf': [Rules.required('Konfirmasi password wajib diisi.'),
        Rules.matches(() => byId('t-pw').value, 'Konfirmasi tidak sama dengan password.')],
    }, (values) => {
      // Simpan akun baru agar tampil di Akun dan Hak Akses dan bisa dipakai login
      Store.addUser({
        nama: values.nama.trim(),
        email: values.email.trim().toLowerCase(),
        password: values.password,
        role: values.role,
        telepon: '-',
        status: 'Aktif',
        bergabung: new Date().toISOString().slice(0, 10),
      });
      global.location.href = 'akun-hak-akses.html?status=akun-ditambah';
    });
  };

  document.addEventListener('DOMContentLoaded', initAddAccount);
})(window);
