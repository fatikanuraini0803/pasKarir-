
/**
 * PASKARIR - HAMBURGER MENU
 * Untuk halaman Super Admin dan Sub Admin.
 */

(function () {
    'use strict';

    function initHamburger() {

        const sidebar = document.querySelector('.sidebar');
        const app = document.querySelector('.app');
        const tombol = document.querySelector('.menu-btn');

        if (!sidebar || !app || !tombol) {
            console.error('Hamburger: elemen tidak ditemukan.');
            return;
        }

        // Mencegah inisialisasi berulang
        if (document.body.dataset.adminMenuReady === 'true') {
            return;
        }

        document.body.dataset.adminMenuReady = 'true';

        const mobile = window.matchMedia('(max-width: 720px)');

        // Buat overlay untuk perangkat mobile
        let overlay = document.querySelector('.paskarir-hamburger-overlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'paskarir-hamburger-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlay);
        }

        // Memperbarui status tombol
        function updateStatus() {

            const terbuka = mobile.matches
                ? document.body.classList.contains('paskarir-sidebar-open')
                : !document.body.classList.contains('paskarir-sidebar-hidden');

            tombol.setAttribute('aria-expanded', String(terbuka));
            tombol.setAttribute('aria-controls', sidebar.id || 'paskarir-sidebar');

            if (!sidebar.id) {
                sidebar.id = 'paskarir-sidebar';
            }
        }

        // Menutup sidebar mobile
        function tutupSidebar() {
            document.body.classList.remove('paskarir-sidebar-open');
            updateStatus();
        }

        // Klik tombol hamburger
        tombol.addEventListener('click', function (event) {

            event.preventDefault();

            if (mobile.matches) {

                document.body.classList.toggle('paskarir-sidebar-open');

            } else {

                document.body.classList.toggle('paskarir-sidebar-hidden');

            }

            updateStatus();

            console.log('Tombol hamburger berhasil diklik.');

        });

        // Klik overlay untuk menutup sidebar
        overlay.addEventListener('click', tutupSidebar);

        // Klik menu navigasi pada mobile
        sidebar.addEventListener('click', function (event) {

            if (
                mobile.matches &&
                event.target.closest('a.nav__link')
            ) {
                tutupSidebar();
            }

        });

        // Tutup dengan Escape
        document.addEventListener('keydown', function (event) {

            if (event.key === 'Escape' && mobile.matches) {
                tutupSidebar();
            }

        });

        // Atur ulang status ketika ukuran layar berubah
        function resetMenu() {

            document.body.classList.remove(
                'paskarir-sidebar-open',
                'paskarir-sidebar-hidden'
            );

            updateStatus();
        }

        if (typeof mobile.addEventListener === 'function') {
            mobile.addEventListener('change', resetMenu);
        } else {
            mobile.addListener(resetMenu);
        }

        updateStatus();

        console.log('Hamburger Super Admin / Sub Admin aktif.');

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHamburger);
    } else {
        initHamburger();
    }

})();
