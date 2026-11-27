/**
 * sub-admin/form-lowongan.js - Formulir tambah/ubah lowongan (Sub Admin).
 * Dimuat oleh frontend/sub-admin/form-lowongan.html.
 */
(function (global) {
  'use strict';

  const { Rules, bindForm, initChips } = global.Paskarir;
  const { byId, experienceRule, skillsRule, chipOptions } = global.Paskarir.AdminCommon;
  const MIN_DESCRIPTION = 10;
  const MAX_DESCRIPTION = 500;

  const initVacancyForm = () => {
    const form = byId('nama').closest('form');
    const chips = initChips(byId('keterampilan'));

    bindForm(form, {
      nama: [Rules.required('Nama lowongan wajib diisi.'), Rules.minLength(3, 'Nama lowongan minimal 3 karakter.')],
      bidang: [Rules.required('Pilih bidang lowongan.')],
      keterampilan: [skillsRule(chips)],
      pendidikan: [Rules.required('Pilih pendidikan minimal.')],
      pengalaman: [experienceRule()],
      deskripsi: [Rules.minLength(MIN_DESCRIPTION, `Deskripsi minimal ${MIN_DESCRIPTION} karakter.`),
        Rules.custom((value) => value.length <= MAX_DESCRIPTION, `Deskripsi maksimal ${MAX_DESCRIPTION} karakter.`)],
    }, () => { global.location.href = 'kelola-lowongan.html?status=lowongan-tersimpan'; },
    chipOptions(chips));

    // Penghitung karakter deskripsi, diperbarui setiap ketikan (event input)
    const description = byId('deskripsi');
    const counter = document.createElement('p');
    counter.className = 'field__hint';
    counter.setAttribute('aria-live', 'polite');
    description.closest('.form-group').appendChild(counter);
    const updateCounter = () => { counter.textContent = `${description.value.length} / ${MAX_DESCRIPTION} karakter`; };
    description.addEventListener('input', updateCounter);
    updateCounter();
  };

  document.addEventListener('DOMContentLoaded', initVacancyForm);
})(window);
