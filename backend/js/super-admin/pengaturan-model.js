/**
 * super-admin/pengaturan-model.js - Pengaturan model Decision Tree (Super Admin).
 * Dimuat oleh frontend/super-admin/pengaturan-model.html.
 */
(function (global) {
  'use strict';

  const { Rules, Feedback, bindForm } = global.Paskarir;
  const { byId } = global.Paskarir.AdminCommon;
  const TRAINING_DELAY_MS = 1200;

  const initModelSettings = () => {
    const form = byId('kedalaman').closest('form');
    const submitButton = form.querySelector('button[type="submit"]');
    const hint = form.querySelector('.form-hint');

    bindForm(form, {
      kedalaman: [Rules.integerInRange(1, 20, 'Kedalaman maksimum harus bilangan bulat 1 sampai 20.')],
      rasio: [Rules.integerInRange(1, 99, 'Rasio data uji harus bilangan bulat 1 sampai 99.')],
    }, () => {
      submitButton.disabled = true;
      Feedback.showAlert(form, 'info', 'Model sedang dilatih ulang...');
      setTimeout(() => {
        submitButton.disabled = false;
        hint.textContent = `Terakhir dilatih: ${new Date().toLocaleString('id-ID')}`;
        Feedback.showAlert(form, 'success', 'Pelatihan ulang selesai (simulasi).');
      }, TRAINING_DELAY_MS);
    });
  };

  document.addEventListener('DOMContentLoaded', initModelSettings);
})(window);
