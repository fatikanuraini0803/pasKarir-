/**
 * sub-admin/kelola-panduan.js - Formulir panduan/rekomendasi (Sub Admin).
 * Dimuat oleh frontend/sub-admin/kelola-panduan.html.
 */
(function (global) {
  'use strict';

  const { Rules, Feedback, bindForm } = global.Paskarir;
  const { byId } = global.Paskarir.AdminCommon;

  const initGuideForm = () => {
    const form = byId('judul').closest('form');
    bindForm(form, {
      judul: [Rules.required('Judul wajib diisi.'), Rules.minLength(5, 'Judul minimal 5 karakter.')],
      isi: [Rules.required('Isi wajib diisi.'), Rules.minLength(20, 'Isi minimal 20 karakter.')],
    }, () => {
      Feedback.showAlert(form, 'success', 'Panduan berhasil disimpan.');
      form.reset();
    });
    form.addEventListener('reset', () => {
      form.querySelectorAll('.is-invalid').forEach(Feedback.clearFieldError);
    });
  };

  document.addEventListener('DOMContentLoaded', initGuideForm);
})(window);
