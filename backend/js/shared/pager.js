/**
 * pager.js - Tombol Sebelumnya / 1 / 2 / Next mengganti isi tabel di tempat.
 * Halaman tidak dimuat ulang dan tidak pindah ke halaman lain: hanya baris tabel dan tombolnya yang berubah,
 * lalu tampilan diarahkan ke tabel. Bila fetch gagal, tombol bekerja sebagai tautan biasa.
 */
(function () {
  'use strict';
  var SEL = 'nav[aria-label="Halaman tabel"]';
  var sekarang = location.pathname + location.search;

  function akar(nav) { return nav.closest('.table-footer') || nav; }

  /* Tabel terdekat yang berada sebelum tombol pagination */
  function tabelUntuk(root, doc) {
    var hasil = null;
    doc.querySelectorAll('table.table').forEach(function (t) {
      if (t.compareDocumentPosition(root) & Node.DOCUMENT_POSITION_FOLLOWING) hasil = t;
    });
    return hasil;
  }

  function arahkanKeTabel(tabel) {
    var kotak = tabel.closest('.table-card, .card, .table-wrap') || tabel;
    var top = kotak.getBoundingClientRect().top;
    if (top < 70 || top > window.innerHeight * 0.6) {
      kotak.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function muat(url, nav, dorong) {
    var semua = Array.prototype.slice.call(document.querySelectorAll(SEL));
    var idx = semua.indexOf(nav);
    var root = akar(nav);
    var tabel = tabelUntuk(root, document);
    if (idx < 0 || !tabel) { location.href = url; return; }
    tabel.style.opacity = '.5';

    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error('http'); return r.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var navBaru = doc.querySelectorAll(SEL)[idx];
        if (!navBaru) throw new Error('struktur');
        var rootBaru = akar(navBaru);
        var tabelBaru = tabelUntuk(rootBaru, doc);
        if (!tabelBaru) throw new Error('tabel');
        tabel.querySelector('tbody').innerHTML = tabelBaru.querySelector('tbody').innerHTML;
        root.innerHTML = rootBaru.innerHTML;
        tabel.style.opacity = '';
        if (dorong) history.pushState({ pager: 1 }, '', url);
        sekarang = location.pathname + location.search;
        arahkanKeTabel(tabel);
      })
      .catch(function () { location.href = url; });
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest(SEL + ' a[href]') : null;
    if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;
    e.preventDefault();
    if (a.getAttribute('aria-current') === 'page') return;   // sudah di halaman ini
    muat(a.href, a.closest(SEL), true);
  });

  /* Tombol Back/Forward browser: muat ulang bila alamat (bukan hash) berubah */
  window.addEventListener('popstate', function () {
    if (location.pathname + location.search !== sekarang) location.reload();
  });
})();
