
/**
 * PASKARIR - DETAIL LOWONGAN
 * Fungsi ikon mata pada halaman Kelola Lowongan.
 */
document.addEventListener('DOMContentLoaded', function () {

  const tableBody = document.querySelector('.table tbody');

  if (!tableBody) {
    console.warn('Tabel lowongan tidak ditemukan.');
    return;
  }

  // Membuat CSS modal
  const style = document.createElement('style');

  style.textContent = `
    .lowongan-view-modal {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 99999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .lowongan-view-modal.open {
      display: flex;
    }

    .lowongan-view-overlay {
      position: absolute;
      inset: 0;
      background: rgba(20, 30, 55, .50);
      backdrop-filter: blur(3px);
    }

    .lowongan-view-box {
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
      animation: lowonganViewIn .2s ease;
    }

    @keyframes lowonganViewIn {
      from {
        opacity: 0;
        transform: translateY(12px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .lowongan-view-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 15px;
      padding-bottom: 16px;
      margin-bottom: 20px;
      border-bottom: 1px solid #e5e9f2;
    }

    .lowongan-view-header h2 {
      margin: 0;
      color: #202638;
      font-size: 20px;
      font-weight: 700;
    }

    .lowongan-view-close {
      width: 35px;
      height: 35px;
      border: 0;
      border-radius: 9px;
      background: #f1f3f8;
      color: #475569;
      font-size: 23px;
      cursor: pointer;
    }

    .lowongan-view-item {
      margin-bottom: 17px;
    }

    .lowongan-view-label {
      display: block;
      margin-bottom: 7px;
      color: #7b8495;
      font-size: 13px;
    }

    .lowongan-view-value {
      display: block;
      padding: 12px 14px;
      border: 1px solid #e2e7f0;
      border-radius: 10px;
      color: #202638;
      font-size: 14px;
      line-height: 1.7;
      overflow-wrap: anywhere;
      white-space: pre-wrap;
    }

    .lowongan-view-footer {
      display: flex;
      justify-content: flex-end;
      margin-top: 22px;
    }

    .lowongan-view-button {
      padding: 11px 22px;
      border: 0;
      border-radius: 9px;
      background: #2563eb;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }

    @media(max-width: 480px) {
      .lowongan-view-box {
        padding: 20px;
      }
    }
  `;

  document.head.appendChild(style);

  // Membuat modal
  const modal = document.createElement('div');

  modal.className = 'lowongan-view-modal';
  modal.setAttribute('aria-hidden', 'true');

  modal.innerHTML = `
    <div class="lowongan-view-overlay" data-close></div>

    <div class="lowongan-view-box"
         role="dialog"
         aria-modal="true"
         aria-labelledby="lowonganViewTitle">

      <div class="lowongan-view-header">
        <h2 id="lowonganViewTitle">Detail Lowongan</h2>

        <button type="button"
                class="lowongan-view-close"
                data-close
                aria-label="Tutup">
          &times;
        </button>
      </div>

      <div id="lowonganViewContent"></div>

      <div class="lowongan-view-footer">
        <button type="button"
                class="lowongan-view-button"
                data-close>
          Tutup
        </button>
      </div>

    </div>
  `;

  document.body.appendChild(modal);

  const content = modal.querySelector('#lowonganViewContent');

  // Mengamankan teks sebelum dimasukkan ke HTML
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

  function addDetail(label, value) {
    return `
      <div class="lowongan-view-item">
        <span class="lowongan-view-label">
          ${escapeHTML(label)}
        </span>
        <div class="lowongan-view-value">
          ${escapeHTML(value || '-')}
        </div>
      </div>
    `;
  }

  function openModal() {
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // Menangani klik ikon mata
  tableBody.addEventListener('click', function (event) {

    const button = event.target.closest('.actions .icon-btn');

    if (!button) return;

    // Pastikan yang diklik ikon mata
    const eyeIcon = button.querySelector('.icon--eye');

    if (!eyeIcon) return;

    event.preventDefault();

    const row = button.closest('tr');

    if (!row) return;

    // Mengambil informasi dari kolom tabel
    const nama = row.cells[0]?.textContent.trim() || '-';
    const bidang = row.cells[1]?.textContent.trim() || '-';
    const keterampilan = row.cells[2]?.textContent.trim() || '-';

    // Informasi tambahan jika tersedia pada atribut data
    const pendidikan = row.dataset.pendidikan || '-';
    const pengalaman = row.dataset.pengalaman || '-';
    const sertifikasi = row.dataset.sertifikasi || '-';
    const deskripsi = row.dataset.deskripsi || '-';

    // Menampilkan detail lowongan
    content.innerHTML =
      addDetail('Nama Lowongan', nama) +
      addDetail('Bidang', bidang) +
      addDetail('Syarat Keterampilan', keterampilan) +
      addDetail('Pendidikan Minimal', pendidikan) +
      addDetail('Pengalaman Minimal', pengalaman) +
      addDetail('Sertifikasi', sertifikasi) +
      addDetail('Deskripsi', deskripsi);

    openModal();
  });

  // Menutup modal dengan tombol atau area luar
  modal.addEventListener('click', function (event) {
    if (event.target.closest('[data-close]')) {
      closeModal();
    }
  });

  // Menutup modal dengan Escape
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && modal.classList.contains('open')) {
      closeModal();
    }
  });

});
