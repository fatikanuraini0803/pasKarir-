/**
 * klasifikasi-bidang.js - Pilihan lowongan mengikuti jenis lowongan (bidang) yang dipilih.
 * Data lowongan dibaca dari <script type="application/json" id="data-lowongan"> yang dibuat PHP.
 * Validasi tetap dilakukan di server (klasifikasi.php); skrip ini hanya mempermudah pengisian.
 */
(function () {
  'use strict';
  const dataEl = document.getElementById('data-lowongan');
  const bidang = document.getElementById('bidang');
  const lowongan = document.getElementById('lowongan_id');
  const hint = document.getElementById('hint-lowongan');
  if (!dataEl || !bidang || !lowongan) return;

  let semua = [];
  try { semua = JSON.parse(dataEl.textContent); } catch (e) { return; }

  const kunci = (t) => String(t || '').replace(/\s+/g, ' ').trim().toLowerCase();

  function isiLowongan() {
    const terpilih = lowongan.value;
    lowongan.innerHTML = '';
    lowongan.disabled = bidang.value === '';
    lowongan.appendChild(new Option(bidang.value ? 'Pilih lowongan' : 'Pilih jenis lowongan dulu', ''));
    semua.filter((l) => bidang.value !== '' && kunci(l.bidang) === kunci(bidang.value)).forEach((l) => {
      lowongan.appendChild(new Option(l.nama, String(l.id), false, String(l.id) === terpilih));
    });
    tampilkanSyarat();
  }

  function tampilkanSyarat() {
    if (!hint) return;
    const l = semua.find((x) => String(x.id) === lowongan.value);
    hint.textContent = l
      ? 'Syarat: ' + (l.syarat.length ? l.syarat.join(', ') : 'tanpa keterampilan khusus') +
        ' · min. ' + l.pendidikan + ' · pengalaman ' + l.pengalaman + ' th'
      : '';
  }

  bidang.addEventListener('change', function () { lowongan.value = ''; isiLowongan(); });
  lowongan.addEventListener('change', tampilkanSyarat);
  tampilkanSyarat();
})();
