/**
 * klasifikasi.js - Form Klasifikasi (alur utama aplikasi).
 */
(function (global) {
  'use strict';

  const { Rules, Feedback, Store, Classifier, initChips, bindForm } = global.Paskarir;
  const PROCESS_DELAY_MS = 600; // jeda singkat agar tombol terlihat "memproses"
  const MAX_EXPERIENCE_YEARS = 50;

  const byId = (id) => document.getElementById(id);

  /** Mengubah tanggal hari ini menjadi format dd/mm/yyyy untuk tampilan. */
  const todayLabel = () => new Date().toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });

  /** Menyusun objek riwayat dari isian form dan hasil klasifikasi. */
  const buildRecord = (applicant, vacancy, result) => ({
    id: Date.now(),
    tanggalIso: new Date().toISOString().slice(0, 10),
    tanggal: todayLabel(),
    lowongan: vacancy.nama,
    label: result.label,
    skor: result.skor,
    alasan: result.alasan,
    rekomendasi: Classifier.recommend(applicant, vacancy.nama),
  });

  /** Mengubah tombol Proses menjadi status sedang memproses. */
  const setProcessing = (button, isProcessing) => {
    button.disabled = isProcessing;
    button.setAttribute('aria-busy', String(isProcessing));
    if (isProcessing) { button.dataset.label = button.innerHTML; button.textContent = 'Memproses...'; }
    else if (button.dataset.label) button.innerHTML = button.dataset.label;
  };

  const init = () => {
    const form = byId('lowongan').closest('form');
    const skillInput = byId('skill-baru');
    const submitButton = form.querySelector('button[type="submit"]');
    const chips = initChips(skillInput, {
      // Setiap perubahan chip: bila sudah ada isi, hapus pesan error keterampilan
      onChange: (values) => { if (values.length > 0) Feedback.clearFieldError(skillInput); },
    });

    const rules = {
      lowongan: [
        Rules.required('Pilih lowongan yang dituju.'),
        Rules.custom((value) => Boolean(Classifier.findVacancy(value)), 'Lowongan tidak ditemukan. Pilih dari daftar yang tersedia.'),
      ],
      'skill-baru': [
        Rules.custom(() => chips.getValues().length > 0, 'Tambahkan minimal satu keterampilan (tekan Enter setelah mengetik).'),
      ],
      pendidikan: [Rules.required('Pilih tingkat pendidikan.')],
      pengalaman: [
        Rules.integerInRange(0, MAX_EXPERIENCE_YEARS, `Pengalaman harus bilangan bulat 0 sampai ${MAX_EXPERIENCE_YEARS} tahun.`),
      ],
    };

    // Kolom keterampilan dinilai dari chip, bukan dari teks yang sedang diketik
    bindForm(form, rules, (values) => {
      const vacancy = Classifier.findVacancy(values.lowongan);
      const applicant = {
        keterampilan: chips.getValues(),
        pendidikan: values.pendidikan,
        pengalaman: Number(values.pengalaman),
        sertifikasi: values.sertifikasi,
      };
      const result = Classifier.classify(applicant, vacancy);
      const record = buildRecord(applicant, vacancy, result);

      setProcessing(submitButton, true);
      setTimeout(() => {
        const history = Store.read(Store.KEYS.history, []);
        history.unshift(record);
        const saved = Store.write(Store.KEYS.history, history) && Store.write(Store.KEYS.lastResult, record);
        if (!saved) {
          setProcessing(submitButton, false);
          Feedback.showAlert(form, 'error', 'Hasil tidak dapat disimpan di browser ini. Coba lagi.');
          return;
        }
        global.location.href = 'hasil.html';
      }, PROCESS_DELAY_MS);
    });

    // Pengalaman hanya bilangan bulat: blok karakter e, +, -, dan titik saat mengetik (event keydown)
    byId('pengalaman').addEventListener('keydown', (event) => {
      if (['e', 'E', '+', '-', '.', ','].includes(event.key)) event.preventDefault();
    });

    // Tombol Reset: kosongkan chip dan semua pesan error
    form.addEventListener('reset', () => {
      chips.clear();
      Feedback.clearAlert(form);
      form.querySelectorAll('.is-invalid').forEach(Feedback.clearFieldError);
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    if (byId('lowongan') && byId('skill-baru')) init();
  });
})(window);
