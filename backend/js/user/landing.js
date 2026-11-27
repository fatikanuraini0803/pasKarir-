
/* landing.js - indikator nav, scroll reveal, parallax ilustrasi, hamburger menu */
(function () {
  'use strict';
  var doc = document;

  /* 1. Indikator garis navigasi: Beranda / Lowongan / Cara Kerja */
  var list = doc.querySelector('[data-nav]');
  if (list) {
    var links = [].slice.call(list.querySelectorAll('.site-nav__link'));
    var bar = doc.createElement('span');
    bar.className = 'site-nav__indicator';
    bar.setAttribute('aria-hidden', 'true');
    list.appendChild(bar);

    var targets = links.map(function (a) {
      var h = a.getAttribute('href');
      return h.charAt(0) === '#' ? doc.querySelector(h) : null;
    });

    var setActive = function (i) {
      if (!links[i]) return;

      links.forEach(function (a, n) {
        if (n === i) {
          a.setAttribute('aria-current', 'page');
        } else {
          a.removeAttribute('aria-current');
        }
      });

      var a = links[i];
      bar.style.width = a.offsetWidth + 'px';
      bar.style.transform = 'translateX(' + a.offsetLeft + 'px)';
      bar.style.opacity = 1;
    };

    var current = -1;

    var spy = function () {
      var y = window.scrollY + window.innerHeight * 0.35;
      var idx = 0;

      targets.forEach(function (t, n) {
        if (t && t.getBoundingClientRect().top + window.scrollY <= y) {
          idx = n;
        }
      });

      if (window.innerHeight + window.scrollY >=
          doc.documentElement.scrollHeight - 4) {
        idx = links.length - 1;
      }

      if (idx !== current) {
        current = idx;
        setActive(idx);
      }
    };

    var ticking = false;

    window.addEventListener('scroll', function () {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(function () {
          spy();
          ticking = false;
        });
      }
    }, { passive: true });

    window.addEventListener('resize', function () {
      setActive(current < 0 ? 0 : current);
    });

    links.forEach(function (a, n) {
      a.addEventListener('click', function () {
        current = n;
        setActive(n);
      });
    });

    spy();

    if (doc.fonts && doc.fonts.ready) {
      doc.fonts.ready.then(function () {
        setActive(current < 0 ? 0 : current);
      });
    }
  }

  /* 2. Scroll reveal */
  var items = [].slice.call(doc.querySelectorAll('.reveal'));

  items.forEach(function (el, i) {
    el.style.setProperty('--d', (i % 4) * 0.08 + 's');
  });

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('is-visible');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.15 });

    items.forEach(function (el) {
      io.observe(el);
    });
  } else {
    items.forEach(function (el) {
      el.classList.add('is-visible');
    });
  }

  /* 3. Ilustrasi Decision Tree mengikuti kursor dengan halus */
  var media = doc.querySelector('.hero__media');

  if (media && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    media.addEventListener('mousemove', function (e) {
      var r = media.getBoundingClientRect();

      media.style.setProperty(
        '--mx',
        ((e.clientX - r.left) / r.width - 0.5) * 2
      );

      media.style.setProperty(
        '--my',
        ((e.clientY - r.top) / r.height - 0.5) * 2
      );
    });

    media.addEventListener('mouseleave', function () {
      media.style.setProperty('--mx', 0);
      media.style.setProperty('--my', 0);
    });
  }

  /* 4. Hamburger Menu */
  var hamburger = doc.querySelector('.hamburger');
  var navMenu = doc.querySelector('.site-nav');

  if (hamburger && navMenu) {

    // Fungsi membuka dan menutup menu
    hamburger.addEventListener('click', function () {
      var isOpen = navMenu.classList.toggle('is-open');

      hamburger.classList.toggle('is-active', isOpen);
      hamburger.setAttribute('aria-expanded', String(isOpen));
    });

    // Menutup menu ketika link navigasi diklik
    var menuLinks = navMenu.querySelectorAll('a');

    menuLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        navMenu.classList.remove('is-open');
        hamburger.classList.remove('is-active');
        hamburger.setAttribute('aria-expanded', 'false');
      });
    });

    // Menutup menu ketika pengguna menekan tombol Escape
    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        navMenu.classList.remove('is-open');
        hamburger.classList.remove('is-active');
        hamburger.setAttribute('aria-expanded', 'false');
      }
    });
  }

})();
