
/**
 * Dropdown Profil Paskarir
 * Menutup menu ketika pengguna klik di luar dropdown.
 */
(function () {
  'use strict';

  const profile = document.querySelector('.profile');

  if (!profile) return;

  // Klik di luar dropdown
  document.addEventListener('click', function (event) {
    if (
      profile.open &&
      !profile.contains(event.target)
    ) {
      profile.open = false;
    }
  });

// Menutup menu profil ketika klik di luar menu
document.addEventListener('click', function (event) {
  const profileMenu = document.querySelector('.profile-menu');

  if (!profileMenu) return;

  if (
    profileMenu.open &&
    !profileMenu.contains(event.target)
  ) {
    profileMenu.open = false;
  }
});

// Menutup menu profil ketika tombol Escape ditekan
document.addEventListener('keydown', function (event) {
  const profileMenu = document.querySelector('.profile-menu');

  if (event.key === 'Escape' && profileMenu?.open) {
    profileMenu.open = false;
  }
});


  // Tutup dropdown dengan tombol Escape
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && profile.open) {
      profile.open = false;
    }
  });

})();

/**
 * Tombol mata untuk menampilkan / menyembunyikan password.
 * Dipasang pada <button data-toggle-password="id-input">.
 */
(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-toggle-password]');
    if (!button) return;

    const input = document.getElementById(button.getAttribute('data-toggle-password'));
    const icon = button.querySelector('.icon');
    if (!input || !icon) return;

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.classList.toggle('icon--eye', !show);
    icon.classList.toggle('icon--eye-off', show);
    button.setAttribute('aria-pressed', String(show));
    button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  });
})();

/**
 * Bagian umum untuk semua halaman yang sudah punya data akun:
 *  1. Menampilkan banner ?status=... (mis. "Akun berhasil dibuat").
 *  2. Mengisi nama, email, dan role akun yang sedang login pada menu profil.
 *  3. Mengakhiri sesi login saat tombol "Ya, Keluar" ditekan.
 */
(function (global) {
  'use strict';

  const { Session, Feedback } = global.Paskarir || {};
  if (!Session || !Feedback) return;

  const setText = (selector, text) => {
    document.querySelectorAll(selector).forEach((el) => { el.textContent = text; });
  };

  const fillIdentity = () => {
    const user = Session.current();
    if (!user) return; // belum login: biarkan teks contoh bawaan halaman

    // Halaman user: <span class="profile__name"><strong>Nama</strong><span>Role</span></span>
    setText('.profile__name strong', user.nama);
    setText('.profile__name strong + span', user.role);
    // Halaman admin: <span class="profile__name">Nama</span> (tanpa elemen anak)
    document.querySelectorAll('.profile__name').forEach((el) => {
      if (!el.children.length) el.textContent = user.nama;
    });
    // Kepala menu profil (dropdown)
    setText('.profile__info strong, .profile-menu__head strong', user.nama);
    setText('.profile__info small, .profile-menu__head small', user.email);
    setText('.profile__info .badge, .profile-menu__head .badge', user.role);
    // Sapaan di dashboard user ditangani dashboard.js
  };

  const initLogout = () => {
    // Tombol konfirmasi di modal "Keluar dari akun?" (tombol utama pada dialog)
    document.addEventListener('click', (event) => {
      const confirmLink = event.target.closest('#keluar .btn--danger, #keluar .btn--primary');
      if (confirmLink) Session.end();
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    Feedback.showStatusFromUrl();
    fillIdentity();
    initLogout();
  });
})(window);
