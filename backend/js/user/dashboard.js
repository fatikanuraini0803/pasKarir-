/**
 * dashboard.js - Ringkasan dashboard user dari data sesi dan riwayat.
 */
(function (global) {
  'use strict';

  const { Store, Session } = global.Paskarir;

  document.addEventListener('DOMContentLoaded', () => {
    const greeting = document.getElementById('halo');
    if (!greeting) return;

    const user = Session.current();
    if (user) greeting.textContent = `Halo, ${user.nama}`;

    const history = Store.getHistory();
    if (history === null) return; // riwayat belum pernah tersimpan: pakai angka contoh bawaan halaman

    const values = document.querySelectorAll('.stat__value');
    if (history.length === 0) { // semua riwayat sudah dihapus
      values[0].textContent = '0';
      values[1].textContent = '-';
      values[2].textContent = '0';
      return;
    }
    values[0].textContent = String(history.length);
    values[1].textContent = history[0].label === 'SESUAI' ? 'Sesuai' : 'Tidak Sesuai';
    values[2].textContent = String(history[0].rekomendasi.length);
  });
})(window);
