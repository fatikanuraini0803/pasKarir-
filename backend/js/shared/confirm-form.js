/**
 * confirm-form.js - Dialog konfirmasi hapus untuk form PHP (pengganti window.confirm bawaan browser).
 *
 * Pemakaian pada form:
 *   <form method="post" data-confirm="Hapus lowongan ini?" data-confirm-name="Staf IT"
 *         data-confirm-desc="Teks tambahan (opsional)">
 * Saat tombol Hapus ditekan, dialog bergaya aplikasi tampil. Form baru dikirim bila memilih "Ya, Hapus".
 */
(function () {
  'use strict';

  var TRASH_SVG = '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" ' +
    'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>';


  /* Gaya dialog disisipkan sendiri oleh skrip ini, supaya tetap tampil di tengah layar
     walaupun file CSS halaman masih tersimpan lama di cache browser. */
  var STYLE =
    '.del-modal{position:fixed!important;top:0!important;left:0!important;right:0!important;bottom:0!important;' +
    'width:100%!important;height:100%!important;z-index:2147483000!important;display:flex!important;' +
    'align-items:center!important;justify-content:center!important;margin:0!important;padding:16px!important;' +
    'box-sizing:border-box!important;background:rgba(24,36,90,.35)!important;-webkit-backdrop-filter:blur(4px);' +
    'backdrop-filter:blur(4px);opacity:0;transition:opacity .16s ease;font-family:Poppins,system-ui,sans-serif}' +
    '.del-modal.is-open{opacity:1}.del-modal.is-closing{opacity:0}' +
    'body.del-modal-open{overflow:hidden}' +
    '.del-modal__box{margin:auto;width:min(400px,100%);box-sizing:border-box;padding:32px 28px 24px;text-align:center;' +
    'background:#fff;color:#172033;border-radius:20px;border-top:5px solid #dc2626;box-shadow:0 24px 60px rgba(24,36,90,.28);' +
    'transform:translateY(12px) scale(.94);transition:transform .22s cubic-bezier(.2,1.3,.4,1)}' +
    '.del-modal.is-open .del-modal__box{transform:none}' +
    '.del-modal__icon{display:grid;place-items:center;width:68px;height:68px;margin:0 auto 16px;border-radius:50%;' +
    'background:#fee2e2;color:#dc2626}' +
    '.del-modal__title{margin:0;font-size:1.25rem;font-weight:700;color:#021a4a}' +
    '.del-modal__name{display:inline-block;max-width:100%;margin:12px 0 0;padding:5px 14px;border-radius:999px;' +
    'background:#f1f5f9;color:#172033;font-size:.875rem;font-weight:600;overflow-wrap:anywhere}' +
    '.del-modal__name[hidden]{display:none}' +
    '.del-modal__desc{margin:12px 0 0;font-size:.8125rem;line-height:1.5;color:#4b5563}' +
    '.del-modal__actions{display:flex;gap:12px;margin-top:24px}' +
    '.del-modal__actions .btn{flex:1;height:42px;justify-content:center;cursor:pointer}';

  function injectStyle() {
    if (document.getElementById('del-modal-style')) return;
    var el = document.createElement('style');
    el.id = 'del-modal-style';
    el.textContent = STYLE;
    document.head.appendChild(el);
  }

  function openDialog(form, submitter) {
    injectStyle();
    var opener = document.activeElement;
    var overlay = document.createElement('div');
    overlay.className = 'del-modal';
    overlay.innerHTML =
      '<div class="del-modal__box" role="alertdialog" aria-modal="true" aria-labelledby="del-title" aria-describedby="del-desc">' +
        '<div class="del-modal__icon">' + TRASH_SVG + '</div>' +
        '<h2 class="del-modal__title" id="del-title"></h2>' +
        '<p class="del-modal__name" hidden></p>' +
        '<p class="del-modal__desc" id="del-desc"></p>' +
        '<div class="del-modal__actions">' +
          '<button type="button" class="btn btn--outline del-modal__cancel">Batal</button>' +
          '<button type="button" class="btn btn--danger del-modal__ok">Ya, Hapus</button>' +
        '</div>' +
      '</div>';

    overlay.querySelector('.del-modal__title').textContent = form.getAttribute('data-confirm') || 'Hapus data ini?';
    var name = form.getAttribute('data-confirm-name');
    if (name) {
      var nameEl = overlay.querySelector('.del-modal__name');
      nameEl.textContent = name;
      nameEl.hidden = false;
    }
    overlay.querySelector('.del-modal__desc').textContent =
      form.getAttribute('data-confirm-desc') || 'Data yang dihapus tidak dapat dikembalikan.';

    document.body.appendChild(overlay);
    document.body.classList.add('del-modal-open');

    var cancelBtn = overlay.querySelector('.del-modal__cancel');
    var okBtn = overlay.querySelector('.del-modal__ok');

    function close(yes) {
      document.removeEventListener('keydown', onKey, true);
      overlay.classList.add('is-closing');
      document.body.classList.remove('del-modal-open');
      setTimeout(function () {
        overlay.remove();
        if (yes) {
          form.setAttribute('data-confirmed', '1');
          if (typeof form.requestSubmit === 'function') form.requestSubmit(submitter || undefined);
          else form.submit();
        } else if (opener && opener.focus) {
          opener.focus();
        }
      }, 160);
    }
    function onKey(e) {
      if (e.key === 'Escape') { e.preventDefault(); close(false); }
      if (e.key === 'Tab') {
        e.preventDefault();
        var items = [cancelBtn, okBtn];
        var i = items.indexOf(document.activeElement);
        items[(i + (e.shiftKey ? items.length - 1 : 1)) % items.length].focus();
      }
    }
    document.addEventListener('keydown', onKey, true);
    cancelBtn.addEventListener('click', function () { close(false); });
    okBtn.addEventListener('click', function () { close(true); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(false); });
    requestAnimationFrame(function () { overlay.classList.add('is-open'); cancelBtn.focus(); });
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches || !form.matches('form[data-confirm]')) return;
    if (form.getAttribute('data-confirmed') === '1') return;   // sudah dikonfirmasi: biarkan terkirim
    event.preventDefault();
    openDialog(form, event.submitter);
  });
})();
