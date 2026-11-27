/**
 * detail-modal.js - Dialog "Lihat detail" untuk ikon mata di tabel (Panduan, Dashboard Sub Admin).
 * Tombol: <button class="js-detail" data-judul data-sub data-rows='[["Label","Nilai"],...]' data-ubah="url">
 * Semua teks dimasukkan lewat textContent, jadi aman dari XSS.
 */
(function () {
  'use strict';
  var modal = null, pemicu = null;

  function el(tag, cls, txt) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (txt != null) e.textContent = txt;
    return e;
  }

  function bangun() {
    modal = el('div', 'dtl-modal');
    modal.hidden = true;
    modal.innerHTML =
      '<div class="dtl-modal__box" role="dialog" aria-modal="true" aria-labelledby="dtl-judul">' +
      '<button type="button" class="dtl-modal__close" aria-label="Tutup" data-tutup>&times;</button>' +
      '<h2 class="dtl-modal__judul" id="dtl-judul"></h2><p class="dtl-modal__sub"></p>' +
      '<dl class="dtl-modal__list"></dl>' +
      '<div class="dtl-modal__actions"><a class="btn btn--outline" data-ubah href="#"><span aria-hidden="true" class="icon icon--edit"></span> Ubah</a>' +
      '<button type="button" class="btn btn--primary" data-tutup><span aria-hidden="true" class="icon icon--close"></span> Tutup</button></div></div>';
    document.body.appendChild(modal);
  }

  function buka(tombol) {
    if (!modal) bangun();
    pemicu = tombol;
    var d = tombol.dataset, rows = [];
    try { rows = JSON.parse(d.rows || '[]'); } catch (e) { rows = []; }
    modal.querySelector('.dtl-modal__judul').textContent = d.judul || '';
    modal.querySelector('.dtl-modal__sub').textContent = d.sub || '';
    var dl = modal.querySelector('.dtl-modal__list');
    dl.textContent = '';
    rows.forEach(function (r) {
      var baris = el('div', 'dtl-modal__row');
      baris.appendChild(el('dt', null, r[0]));
      var dd = el('dd');
      if (r[0] === 'Status') {
        var aktif = r[1] === 'Aktif' || r[1] === 'Ditampilkan';
        dd.appendChild(el('span', 'badge ' + (aktif ? 'badge--success' : 'badge--warning'), r[1]));
      } else {
        dd.textContent = r[1] === '' || r[1] == null ? '-' : r[1];
      }
      baris.appendChild(dd);
      dl.appendChild(baris);
    });
    var ubah = modal.querySelector('[data-ubah]');
    if (d.ubah) { ubah.setAttribute('href', d.ubah); ubah.hidden = false; } else { ubah.hidden = true; }
    modal.hidden = false;
    document.body.classList.add('dtl-modal-open');
    requestAnimationFrame(function () { modal.classList.add('is-open'); });
    modal.querySelector('.btn--primary').focus();
  }

  function tutup() {
    if (!modal || modal.hidden) return;
    modal.classList.remove('is-open');
    document.body.classList.remove('dtl-modal-open');
    setTimeout(function () { modal.hidden = true; }, 160);
    if (pemicu) pemicu.focus();
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target.closest('.js-detail') : null;
    if (t) { buka(t); return; }
    if (modal && !modal.hidden && (e.target === modal || (e.target.closest && e.target.closest('[data-tutup]')))) tutup();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutup(); });
})();
