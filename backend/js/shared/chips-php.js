/**
 * chips-php.js - Menghubungkan input chip (chips.js) dengan formulir PHP.
 * Chip dikirim ke server sebagai satu input tersembunyi name="keterampilan" (dipisah koma),
 * sehingga validasi di PHP tidak berubah.
 */
(function (global) {
  'use strict';
  const { initChips } = global.Paskarir || {};
  if (!initChips) return;

  document.querySelectorAll('[data-chips]').forEach((box) => {
    const input = box.querySelector('.tag-input, .chip-input__field');
    const form = box.closest('form');
    const hidden = form && form.querySelector('input[type="hidden"][name="keterampilan"]');
    if (!input || !hidden) return;

    const chips = initChips(input, { onChange: (values) => { hidden.value = values.join(', '); } });
    hidden.value = chips.getValues().join(', ');

    // Teks yang belum ditekan Enter ikut dijadikan chip saat formulir dikirim
    form.addEventListener('submit', () => {
      if (input.value.trim() !== '') {
        if (!chips.addChip(input.value.replace(/,/g, ''))) input.value = '';
      }
      hidden.value = chips.getValues().join(', ');
    });
  });
})(window);
