
/**
 * PASKARIR - EDIT PANDUAN / REKOMENDASI
 * Panel edit hanya muncul setelah tombol Ubah diklik.
 */
document.addEventListener('DOMContentLoaded', function () {

  const tableBody = document.querySelector('.table tbody');

  const panel = document.getElementById('panelEditPanduan')
    || document.querySelector('.layout-split--guide > section.card');

  if (!tableBody || !panel) {
    console.error('Tabel atau panel edit tidak ditemukan.');
    return;
  }

  // Mendukung ID form versi baru maupun lama
  const form = document.getElementById('formPanduan')
    || panel.querySelector('form');

  const judul = document.getElementById('judulPanduan')
    || document.getElementById('judul')
    || form?.querySelector('[name="judul"]');

  const jenis = document.getElementById('jenisPanduan')
    || document.getElementById('jenis')
    || form?.querySelector('[name="jenis"]');

  const isi = document.getElementById('isiPanduan')
    || document.getElementById('isi')
    || form?.querySelector('[name="isi"]');

  const tampilkan = document.getElementById('tampilkanKeUser')
    || form?.querySelector('.switch__input');

  const tombolBatal = document.getElementById('btnBatalEditPanduan')
    || form?.querySelector('button[type="reset"]')
    || Array.from(form?.querySelectorAll('button') || [])
      .find(button => button.textContent.trim().toLowerCase() === 'batal');

  if (!form || !judul || !jenis || !isi ||
      !tampilkan || !tombolBatal) {
    console.error('Elemen input form edit tidak lengkap.', {
      form, judul, jenis, isi, tampilkan, tombolBatal
    });
    return;
  }

  let selectedRow = null;

  // Sembunyikan panel saat halaman pertama dibuka
  function hidePanel() {
    panel.hidden = true;
    panel.style.display = 'none';
  }

  // Tampilkan panel edit
  function showPanel() {
    panel.hidden = false;
    panel.style.display = 'block';

    panel.scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });
  }

  // Awal halaman: form disembunyikan
  hidePanel();

  console.log('Panduan Edit berhasil dimuat.');

  // Mengambil data dari baris tabel
  function getRowData(row) {
    return {
      judul: row.cells[0].textContent.trim(),
      jenis: row.cells[1].textContent.trim(),
      status: row.cells[2].textContent.trim(),
      isi: row.dataset.isi || '',
      tampil: row.dataset.tampilkan === 'true'
        || (
          row.dataset.tampilkan === undefined &&
          row.cells[2].textContent.trim().toLowerCase() === 'aktif'
        )
    };
  }

  // Memilih jenis pada dropdown
  function setJenis(value) {
    const option = Array.from(jenis.options).find(function (item) {
      return item.value.trim().toLowerCase() === value.toLowerCase()
        || item.textContent.trim().toLowerCase() === value.toLowerCase();
    });

    if (option) {
      jenis.value = option.value;
    }
  }

  // Klik tombol Ubah
  tableBody.addEventListener('click', function (event) {

    const tombol = event.target.closest(
      '.actions .icon-btn, .actions button, .actions a'
    );

    if (!tombol) return;

    // Pastikan yang diklik adalah ikon pensil
    const ikonPensil = tombol.querySelector('.icon--edit');

    if (!ikonPensil && tombol.dataset.action !== 'edit') {
      return;
    }

    event.preventDefault();

    const row = tombol.closest('tr');

    if (!row) return;

    // Ambil data baris yang dipilih
    const data = getRowData(row);

    selectedRow = row;

    // Isi form dengan data terpilih
    judul.value = data.judul;
    setJenis(data.jenis);
    isi.value = data.isi;
    tampilkan.checked = data.tampil;

    // Tandai baris yang diedit
    tableBody.querySelectorAll('tr').forEach(function (item) {
      item.classList.remove('is-editing');
    });

    row.classList.add('is-editing');

    // Tampilkan panel edit
    showPanel();

    console.log('Mengedit panduan:', data.judul);
  });

  // Simpan perubahan
  form.addEventListener('submit', function (event) {

    event.preventDefault();

    if (!selectedRow) {
      console.warn('Pilih data melalui ikon Ubah terlebih dahulu.');
      return;
    }

    const namaBaru = judul.value.trim();
    const jenisBaru = jenis.value;
    const isiBaru = isi.value.trim();
    const statusBaru = tampilkan.checked;

    if (!namaBaru || !jenisBaru || !isiBaru) {
      console.warn('Judul, jenis, dan isi harus diisi.');
      return;
    }

    // Perbarui baris tabel
    selectedRow.cells[0].textContent = namaBaru;
    selectedRow.cells[1].textContent = jenisBaru;

    selectedRow.cells[2].innerHTML = statusBaru
      ? '<span class="badge badge--success">Aktif</span>'
      : '<span class="badge badge--warning">Draft</span>';

    selectedRow.dataset.isi = isiBaru;
    selectedRow.dataset.tampilkan = String(statusBaru);

    // Sembunyikan panel setelah menyimpan
    selectedRow.classList.remove('is-editing');
    selectedRow = null;

    form.reset();
    hidePanel();

    console.log('Perubahan panduan berhasil diperbarui pada tabel.');
  });

  // Tombol Batal
  tombolBatal.addEventListener('click', function (event) {
    event.preventDefault();

    if (selectedRow) {
      selectedRow.classList.remove('is-editing');
    }

    selectedRow = null;
    form.reset();
    hidePanel();
  });

});
