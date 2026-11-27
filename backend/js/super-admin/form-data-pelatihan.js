/**
 * super-admin/form-data-pelatihan.js - Formulir data pelatihan model (Super Admin).
 * Dimuat oleh frontend/super-admin/form-data-pelatihan.html.
 */
(function (global) {
  'use strict';

  const { Rules, bindForm, initChips } = global.Paskarir;
  const { byId, experienceRule, skillsRule, chipOptions } = global.Paskarir.AdminCommon;

  const initTrainingForm = () => {
    const form = byId('keterampilan').closest('form');
    const chips = initChips(byId('keterampilan'));
    bindForm(form, {
      keterampilan: [skillsRule(chips)],
      pendidikan: [Rules.required('Pilih pendidikan.')],
      pengalaman: [experienceRule()],
    }, () => { global.location.href = 'data-pelatihan.html?status=pelatihan-tersimpan'; },
    chipOptions(chips));
  };

  document.addEventListener('DOMContentLoaded', initTrainingForm);
})(window);
