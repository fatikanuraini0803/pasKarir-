/**
 * super-admin/form-persyaratan.js - Formulir persyaratan lowongan (Super Admin).
 * Dimuat oleh frontend/super-admin/form-persyaratan.html.
 */
(function (global) {
  'use strict';

  const { Rules, bindForm, initChips } = global.Paskarir;
  const { byId, experienceRule, skillsRule, chipOptions } = global.Paskarir.AdminCommon;

  const initRequirementForm = () => {
    const form = byId('lowongan').closest('form');
    const chips = initChips(byId('keterampilan'));
    bindForm(form, {
      lowongan: [Rules.required('Pilih lowongan terlebih dahulu.')],
      keterampilan: [skillsRule(chips)],
      pendidikan: [Rules.required('Pilih pendidikan minimal.')],
      pengalaman: [experienceRule()],
    }, () => { global.location.href = 'lowongan-persyaratan.html?status=persyaratan-tersimpan'; },
    chipOptions(chips));
  };

  document.addEventListener('DOMContentLoaded', initRequirementForm);
})(window);
