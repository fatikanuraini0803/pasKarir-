
/**
 * PASKARIR - DETAIL PANDUAN / REKOMENDASI
 * Fungsi ikon mata untuk melihat detail data.
 */
document.addEventListener('DOMContentLoaded', function () {

  const tableBody = document.querySelector('.table tbody');

  // Pastikan tabel tersedia
  if (!tableBody) {
    console.warn('Tabel panduan tidak ditemukan.');
    return;
  }

  // CSS modal
  const style = document.createElement('style');

  style.textContent = `
    .panduan-view-modal {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 99999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .panduan-view-modal.open {
      display: flex;
    }

    .panduan-view-overlay {
      position: absolute;
      inset: 0;
      background: rgba(20, 30, 55, .50);
      backdrop-filter: blur(3px);
    }

    .panduan-view-box {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 520px;
      max-height: 85vh;
      overflow-y: auto;
      padding: 26px;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 15px 50px rgba(0,0,0,.18);
      animation: panduanViewIn .2s ease;
    }

    @keyframes panduanViewIn {
      from {
        opacity: 0;
        transform: translateY(12px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .panduan-view-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 15px;
      padding-bottom: 16px;
      margin-bottom: 20px;
      border-bottom: 1px solid #e5e9f2;
    }

    .panduan-view-header h2 {
      margin: 0;
      color: #202638;
      font-size: 20px;
      font-weight: 700;
    }

    .panduan-view-close {
      width: 35px;
      height: 35px;
      border: 0;
      border-radius: 9px;
      background: #f1f3f8;
      color: #475569;
      font-size: 23px;
      cursor: pointer;
    }

    .panduan-view-item {
      margin-bottom: 17px;
    }

    .panduan-view-label {
      display: block;
      margin-bottom: 7px;
      color: #7b8495;
      font-size: 13px;
    }

    .panduan-view-value {
      display: block;
      padding: 12px 14px;
      border: 1px solid #e2e7f0;
      border-radius: 10px;
      color: #202638;
      font-size: 14px;
      line-height: 1.7;
      overflow-wrap: anywhere;
    }

    .panduan-view-footer {
      display: flex;
      justify-content: flex-end;
      margin-top: 22px;
    }

    .panduan-view-button {
      padding: 11px 22px;
      border: 0;
      border-radius: 9px;
      background: #2563eb;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }

    @media(max-width: 480px) {
      .panduan-view-box {
        padding: 20px;
      }
    }
  `;

  document.head.appendChild(style);

  // Membuat modal secara otomatis
  const modal = document.createElement('div');

  modal.className = 'panduan-view-modal';
  modal.setAttribute('aria-hidden', 'true');

  modal.innerHTML = `
    <div class="panduan-view-overlay" data-close></div>

    <div class="panduan-view-box"
         role="dialog"
         aria-modal="true"
         aria-labelledby="panduanViewTitle">

      <div class="panduan-view-header">
        <h2 id="panduanViewTitle">Detail Panduan</h2>

        <button type="button"
                class="panduan-view-close"
                data-close
                aria-label="Tutup">
          &times;
        </button>
      </div>

      <div id="panduanViewContent"></div>

      <div class="panduan-view-footer">
        <button type="button"
                class="panduan-view-button"
                data-close>
          Tutup
        </button>
      </div>

    </div>
  `;

  document.body.appendChild(modal);

  const content = modal.querySelector('#panduanViewContent');

  // Mengamankan teks sebelum ditampilkan sebagai HTML
  function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char];
    });
  }

  // Membuka modal
  function openModal() {
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  // Menutup modal
  function closeModal() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // Klik ikon mata
  tableBody.addEventListener('click', function (event) {

    const button = event.target.closest('.actions .icon-btn');

    if (!button) return;

    // Pastikan yang diklik adalah ikon mata
    const eyeIcon = button.querySelector('.icon--eye');

    if (!eyeIcon) return;

    event.preventDefault();

    const row = button.closest('tr');

    if (!row) return;

    // Mengambil data dari tabel
    const judul = row.cells[0]?.textContent.trim() || '-';
    const jenis = row.cells[1]?.textContent.trim() || '-';
    const status = row.cells[2]?.textContent.trim() || '-';

    // Isi tambahan bila tersedia di atribut data-isi
    const isi = row.dataset.isi ||
                row.dataset.content ||
                'Isi panduan belum tersedia pada data tabel.';

    // Menampilkan informasi
    content.innerHTML = `
      <div class="panduan-view-item">
        <span class="panduan-view-label">Judul</span>
        <div class="panduan-view-value">
          ${escapeHTML(judul)}
        </div>
      </div>

      <div class="panduan-view-item">
        <span class="panduan-view-label">Jenis</span>
        <div class="panduan-view-value">
          ${escapeHTML(jenis)}
        </div>
      </div>

      <div class="panduan-view-item">
        <span class="panduan-view-label">Status</span>
        <div class="panduan-view-value">
          ${escapeHTML(status)}
        </div>
      </div>

      <div class="panduan-view-item">
        <span class="panduan-view-label">Isi Panduan / Rekomendasi</span>
        <div class="panduan-view-value">
          ${escapeHTML(isi)}
        </div>
      </div>
    `;

    openModal();
  });

  // Menutup modal melalui tombol atau area luar
  modal.addEventListener('click', function (event) {
    if (event.target.closest('[data-close]')) {
      closeModal();
    }
  });

  // Menutup modal menggunakan Escape
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && modal.classList.contains('open')) {
      closeModal();
    }
  });

});
