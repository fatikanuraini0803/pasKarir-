/**
 * lowongan-filter.js - Pencarian dan filter bidang otomatis pada halaman Daftar Lowongan (PHP).
 * Filter bidang langsung dikirim saat diganti; pencarian dikirim otomatis setelah berhenti mengetik.
 * Tanpa JavaScript, tombol "Cari" tetap berfungsi.
 */
(function () {
  'use strict';
  var form = document.getElementById('filter-lowongan');
  if (!form) return;
  var input = document.getElementById('cari');
  var select = document.getElementById('bidang');
  var timer = null;

  select.addEventListener('change', function () { form.submit(); });

  input.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () { sessionStorage.setItem('lowongan-fokus', '1'); form.submit(); }, 500);
  });

  // Kembalikan fokus ke kolom pencarian setelah halaman dimuat ulang oleh pencarian otomatis
  try {
    if (sessionStorage.getItem('lowongan-fokus') === '1') {
      sessionStorage.removeItem('lowongan-fokus');
      input.focus();
      var n = input.value.length;
      input.setSelectionRange(n, n);
    }
  } catch (e) { /* penyimpanan tidak tersedia: abaikan */ }
})();
