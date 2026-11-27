
/**
 * PASKARIR - DASHBOARD SUB ADMIN
 * Mengaktifkan ikon mata pada bagian Lowongan Terbaru.
 */
document.addEventListener('DOMContentLoaded', function () {

  // Mengambil tabel Lowongan Terbaru
  const tableBody = document.querySelector(
    'section[aria-labelledby="judul-terbaru"] .table tbody'
  );

  if (!tableBody) {
    console.error('Tabel Lowongan Terbaru tidak ditemukan.');
    return;
  }

  // Membuat CSS modal secara otomatis
  const style = document.createElement('style');

  style.textContent = `
    .dashboard-eye-modal {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 99999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .dashboard-eye-modal.is-open {
      display: flex;
    }

    .dashboard-eye-overlay {
      position: absolute;
      inset: 0;
      background: rgba(20, 30, 55, .50);
      backdrop-filter: blur(3px);
    }

    .dashboard-eye-box {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 480px;
      max-height: 85vh;
      overflow-y: auto;
      padding: 26px;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 15px 50px rgba(0,0,0,.18);
      animation: dashboardEyeIn .2s ease;
    }

    @keyframes dashboardEyeIn {
      from {
        opacity: 0;
        transform: translateY(12px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .dashboard-eye-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e5e9f2;
    }

    .dashboard-eye-header h2 {
      margin: 0;
      color: #202638;
      font-size: 20px;
      font-weight: 700;
    }

    .dashboard-eye-close {
      width: 35px;
      height: 35px;
      border: 0;
      border-radius: 9px;
      background: #f1f3f8;
      color: #475569;
      font-size: 23px;
      cursor: pointer;
    }

    .dashboard-eye-item {
      margin-bottom: 16px;
    }

    .dashboard-eye-label {
      display: block;
      margin-bottom: 7px;
      color: #7b8495;
      font-size: 13px;
    }

    .dashboard-eye-value {
      display: block;
      padding: 12px 14px;
      border: 1px solid #e2e7f0;
      border-radius: 10px;
      color: #202638;
      font-size: 14px;
      line-height: 1.7;
      overflow-wrap: anywhere;
    }

    .dashboard-eye-footer {
      display: flex;
      justify-content: flex-end;
      margin-top: 22px;
    }

    .dashboard-eye-button {
      padding: 11px 23px;
      border: 0;
      border-radius: 9px;
      background: #2563eb;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }

    @media (max-width: 480px) {
      .dashboard-eye-box {
        padding: 20px;
      }
    }
  `;

  document.head.appendChild(style);

  // Membuat modal
  const modal = document.createElement('div');

  modal.className = 'dashboard-eye-modal';
  modal.setAttribute('aria-hidden', 'true');

  modal.innerHTML = `
    <div class="dashboard-eye-overlay" data-close></div>

    <div class="dashboard-eye-box"
         role="dialog"
         aria-modal="true"
         aria-labelledby="dashboardEyeTitle">

      <div class="dashboard-eye-header">
        <h2 id="dashboardEyeTitle">Detail Lowongan</h2>

        <button
          type="button"
          class="dashboard-eye-close"
          data-close
          aria-label="Tutup">
          &times;
        </button>
      </div>

      <div id="dashboardEyeContent"></div>

      <div class="dashboard-eye-footer">
        <button
          type="button"
          class="dashboard-eye-button"
          data-close>
          Tutup
        </button>
      </div>

    </div>
  `;

  document.body.appendChild(modal);

  const content = modal.querySelector('#dashboardEyeContent');

  // Mengamankan data sebelum ditampilkan
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

  // Membuat tampilan detail
  function detailItem(label, value) {
    return `
      <div class="dashboard-eye-item">
        <span class="dashboard-eye-label">
          ${escapeHTML(label)}
        </span>

        <div class="dashboard-eye-value">
          ${escapeHTML(value || '-')}
        </div>
      </div>
    `;
  }

  // Membuka modal
  function openModal() {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  // Menutup modal
  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // Menangani klik ikon mata
  tableBody.addEventListener('click', function (event) {

    const tombol = event.target.closest(
      'a.icon-btn[title="Lihat"]'
    );

    if (!tombol) return;

    event.preventDefault();

    const row = tombol.closest('tr');

    if (!row) return;

    // Mengambil data dari tabel yang dipilih
    const nama = row.cells[0]?.textContent.trim() || '-';
    const bidang = row.cells[1]?.textContent.trim() || '-';
    const status = row.cells[2]?.textContent.trim() || '-';

    // Informasi tambahan jika tersedia
    const deskripsi = row.dataset.deskripsi || '';

    // Menampilkan data ke modal
    content.innerHTML =
      detailItem('Nama Lowongan', nama) +
      detailItem('Bidang', bidang) +
      detailItem('Status', status) +
      (deskripsi
        ? detailItem('Deskripsi', deskripsi)
        : '');

    openModal();

    console.log('Detail lowongan ditampilkan:', nama);
  });

  // Menutup modal
  modal.addEventListener('click', function (event) {
    if (event.target.closest('[data-close]')) {
      closeModal();
    }
  });

  // Menutup dengan tombol Escape
  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape' &&
      modal.classList.contains('is-open')
    ) {
      closeModal();
    }
  });

  console.log('Dashboard View berhasil dimuat.');

});
