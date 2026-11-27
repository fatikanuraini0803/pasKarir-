/**
 * chips.js - Komponen input keterampilan (tag/chip).
 *
 * Event yang dipakai:
 *   - keydown (Enter / koma) : menambah chip dari teks di kolom
 *   - click pada tombol x     : menghapus chip (event delegation)
 *   - keydown Backspace       : menghapus chip terakhir bila kolom kosong
 */
(function (global) {
  'use strict';

  const Paskarir = (global.Paskarir = global.Paskarir || {});
  const MAX_CHIPS = 10;

  /**
   * Mengaktifkan input chip pada sebuah kolom teks.
   * Pembungkus chip adalah elemen induk dari kolom (.input--tags atau .chip-input).
   *
   * @param {HTMLInputElement} input kolom teks tempat mengetik keterampilan
   * @param {{onChange?: Function}} [options]
   * @returns {{getValues: Function, clear: Function, addChip: Function}}
   */
  const initChips = (input, options = {}) => {
    const box = input.parentElement;
    const notifyChange = () => { if (options.onChange) options.onChange(getValues()); };

    /** Mengambil teks semua chip yang tampil. */
    function getValues() {
      return Array.from(box.querySelectorAll('.chip')).map((chip) =>
        chip.firstChild.textContent.trim());
    }

    /** Membuat satu elemen chip beserta tombol hapus. */
    const buildChip = (text) => {
      const chip = document.createElement('span');
      chip.className = 'chip';
      chip.appendChild(document.createTextNode(`${text} `));
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'chip__remove';
      removeButton.setAttribute('aria-label', `Hapus ${text}`);
      removeButton.textContent = '×';
      chip.appendChild(removeButton);
      return chip;
    };

    /**
     * Menambah chip.
     * @returns {string} pesan error, atau string kosong bila berhasil
     */
    function addChip(rawText) {
      const text = rawText.trim().replace(/\s+/g, ' ');
      if (text === '') return 'Tulis nama keterampilan terlebih dahulu.';
      if (text.length > 30) return 'Nama keterampilan maksimal 30 karakter.';
      if (getValues().some((value) => value.toLowerCase() === text.toLowerCase())) {
        return `"${text}" sudah ada di daftar.`;
      }
      if (getValues().length >= MAX_CHIPS) return `Maksimal ${MAX_CHIPS} keterampilan.`;
      box.insertBefore(buildChip(text), input);
      notifyChange();
      return '';
    }

    /** Menghapus semua chip (dipakai saat tombol Reset ditekan). */
    function clear() {
      box.querySelectorAll('.chip').forEach((chip) => chip.remove());
      notifyChange();
    }

    input.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault(); // Enter tidak boleh mengirim formulir
        const errorMessage = addChip(input.value.replace(/,/g, ''));
        if (errorMessage) {
          Paskarir.Feedback.setFieldError(input, errorMessage);
        } else {
          input.value = '';
          Paskarir.Feedback.clearFieldError(input);
        }
      } else if (event.key === 'Backspace' && input.value === '') {
        const chips = box.querySelectorAll('.chip');
        if (chips.length) { chips[chips.length - 1].remove(); notifyChange(); }
      }
    });

    box.addEventListener('click', (event) => {
      const removeButton = event.target.closest('.chip__remove');
      if (!removeButton) return;
      removeButton.closest('.chip').remove();
      notifyChange();
      input.focus();
    });

    return { getValues, clear, addChip };
  };

  Paskarir.initChips = initChips;
})(window);
