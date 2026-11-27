/**
 * hasil.js - Menampilkan hasil klasifikasi terakhir pada hasil.html.
 */
(function (global) {
  'use strict';

  const { Store, Feedback } = global.Paskarir;

  /** Membuat <li> alasan dengan ikon centang (lolos) atau silang (gagal). */
  const buildReasonItem = (reason) => {
    const item = document.createElement('li');
    item.className = 'item';
    const iconName = reason.ok ? 'check' : 'close';
    item.innerHTML = `<span class="num num--lg"><span aria-hidden="true" class="icon icon--${iconName}"></span></span>`;
    item.appendChild(document.createTextNode(reason.teks));
    return item;
  };

  /** Membuat <li> rekomendasi lowongan. */
  const buildRecommendationItem = (recommendation) => {
    const item = document.createElement('li');
    item.className = 'item';
    item.innerHTML = '<span class="icon-tile"><span aria-hidden="true" class="icon icon--briefcase"></span></span>'
      + '<div class="item__body"><p class="item__title"></p><p class="item__sub"></p></div>'
      + '<a class="btn btn--outline btn--sm" href="lowongan.html">Lihat</a>';
    item.querySelector('.item__title').textContent = recommendation.nama;
    item.querySelector('.item__sub').textContent = `Cocok ${recommendation.skor}%`;
    return item;
  };

  const render = (record) => {
    const resultBox = document.querySelector('.result');
    resultBox.classList.toggle('result--no', record.label !== 'SESUAI');
    resultBox.querySelector('.result__badge').lastChild.textContent = ` ${record.label}`;
    resultBox.querySelector('.result__title').textContent = `Lowongan: ${record.lowongan}`;
    resultBox.querySelector('.result__note').textContent = `Dicek pada ${record.tanggal}`;
    resultBox.querySelector('.result__score strong').textContent = `${record.skor}%`;

    const lists = document.querySelectorAll('.item-list--boxed');
    lists[0].replaceChildren(...record.alasan.map(buildReasonItem));
    lists[1].replaceChildren(...record.rekomendasi.map(buildRecommendationItem));
  };

  document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.result')) return;
    const record = Store.read(Store.KEYS.lastResult, null);
    if (record) render(record);
    else Feedback.showAlert(document.querySelector('main'), 'info', 'Belum ada hasil klasifikasi. Yang tampil di bawah hanya contoh. Isi form klasifikasi untuk melihat hasilmu.');
  });
})(window);
