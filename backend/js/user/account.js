
/**
 * account.js - Halaman Profil Saya dan Ubah Password (user).
 */
(function (global) {
  'use strict';

  const { Rules, Feedback, Store, Session, bindForm } = global.Paskarir;
  const byId = (id) => document.getElementById(id);

  /** Menyimpan perubahan nama/email; email tidak boleh dipakai akun lain. */
  const initProfile = () => {
    const form = byId('nama').closest('form');
    const user = Session.current();

    if (user) {
      byId('nama').value = user.nama;
      byId('email').value = user.email;
      byId('gabung').value = user.bergabung.split('-').reverse().join('/');

      document.querySelector('.profile-card .card__title').textContent = user.nama;
      document.querySelector('.profile-card .muted').textContent = user.email;
    }

    bindForm(form, {
      nama: [
        Rules.required('Nama lengkap wajib diisi.'),
        Rules.minLength(3, 'Nama minimal 3 karakter.'),
        Rules.personName('Nama hanya boleh berisi huruf dan spasi.'),
      ],
      email: [
        Rules.required('Email wajib diisi.'),
        Rules.email('Format email tidak valid.'),
        Rules.custom((value) => {
          const owner = Store.findUserByEmail(value);
          return !owner || (user && owner.email === user.email);
        }, 'Email ini sudah dipakai akun lain.'),
      ],
    }, (values) => {
      if (!user) {
        Feedback.showAlert(
          form,
          'info',
          'Masuk terlebih dahulu agar perubahan profil bisa disimpan.'
        );
        return;
      }

      Store.updateUser(user.email, {
        nama: values.nama.trim(),
        email: values.email.trim().toLowerCase()
      });

      Session.start({
        nama: values.nama.trim(),
        email: values.email.trim().toLowerCase()
      });

      global.location.href = 'profil.html?status=profil-diperbarui';
    });

    // Tombol Ganti Foto belum memiliki fitur unggah.
    const photoButton = document.querySelector('.profile-card button');

    if (photoButton) {
      photoButton.addEventListener('click', () => {
        Feedback.showAlert(
          document.querySelector('main'),
          'info',
          'Fitur ganti foto belum tersedia pada prototipe ini.'
        );
      });
    }
  };

  /** Mengubah password: lama harus benar, baru harus kuat dan berbeda. */
  const initChangePassword = () => {
    const form = byId('lama').closest('form');
    const user = Session.current();

    bindForm(form, {
      lama: [
        Rules.required('Password lama wajib diisi.'),
        Rules.custom(
          (value) => !user || value === user.password,
          'Password lama tidak sesuai.'
        ),
      ],
      baru: [
        Rules.required('Password baru wajib diisi.'),
        Rules.strongPassword(
          'Password minimal 8 karakter dan memuat huruf serta angka.'
        ),
        Rules.differsFrom(
          () => byId('lama').value,
          'Password baru tidak boleh sama dengan password lama.'
        ),
      ],
      ulang: [
        Rules.required('Konfirmasi password wajib diisi.'),
        Rules.matches(
          () => byId('baru').value,
          'Konfirmasi tidak sama dengan password baru.'
        ),
      ],
    }, (values) => {
      if (!user) {
        Feedback.showAlert(
          form,
          'info',
          'Masuk terlebih dahulu agar password bisa diubah.'
        );
        return;
      }

      Store.updateUser(user.email, { password: values.baru });
      global.location.href = 'profil.html?status=password-diubah';
    });
  };

  /**
   * Menutup dropdown profil ketika pengguna mengklik di luar menu.
   */
  const initProfileDropdown = () => {
    const profile = document.querySelector('.profile');

    // Jika dropdown profil tidak ditemukan, hentikan fungsi.
    if (!profile) return;

    // Mendeteksi klik di seluruh halaman.
    document.addEventListener('click', (event) => {

      // Jika klik berada di luar dropdown profil.
      if (!profile.contains(event.target)) {

        // Tutup dropdown profil.
        profile.removeAttribute('open');
      }
    });

    // Menutup dropdown ketika tombol Escape ditekan.
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        profile.removeAttribute('open');
      }
    });
  };

  /** Menjalankan fungsi ketika halaman selesai dimuat. */
  document.addEventListener('DOMContentLoaded', () => {

    // Menjalankan fungsi halaman profil.
    if (byId('nama') && byId('gabung')) {
      initProfile();
    }

    // Menjalankan fungsi ubah password.
    if (byId('lama') && byId('ulang')) {
      initChangePassword();
    }

    // Mengaktifkan fungsi klik di luar dropdown profil.
    initProfileDropdown();

  });

})(window);
