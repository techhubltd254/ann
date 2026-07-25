/* ============================================================
   KICC Motion System — scroll reveal, 3D tilt, parallax,
   animated counters, magnetic buttons, marquee
   ============================================================ */
(function () {
  'use strict';

  /* ---------- Scroll Reveal (IntersectionObserver) ---------- */
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const delay = parseInt(el.dataset.revealDelay || '0', 10);
        setTimeout(() => {
          el.classList.add('revealed');
        }, delay);
        revealObserver.unobserve(el);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  function initReveal(root = document) {
    root.querySelectorAll('[data-reveal]').forEach((el) => {
      if (!el.classList.contains('reveal-init')) {
        el.classList.add('reveal-init');
        revealObserver.observe(el);
      }
    });
  }

  /* ---------- 3D Tilt Cards ---------- */
  function initTilt(root = document) {
    root.querySelectorAll('[data-tilt]').forEach((card) => {
      if (card.dataset.tiltBound) return;
      card.dataset.tiltBound = '1';
      const strength = parseFloat(card.dataset.tilt || '8');
      let raf = null;

      card.addEventListener('mousemove', (e) => {
        if (raf) return;
        raf = requestAnimationFrame(() => {
          const rect = card.getBoundingClientRect();
          const px = (e.clientX - rect.left) / rect.width - 0.5;
          const py = (e.clientY - rect.top) / rect.height - 0.5;
          card.style.transform =
            `perspective(900px) rotateY(${px * strength}deg) rotateX(${-py * strength}deg) translateZ(0)`;
          card.style.transition = 'transform 0.08s ease-out';
          const glare = card.querySelector('.tilt-glare');
          if (glare) {
            glare.style.background =
              `radial-gradient(circle at ${(px + 0.5) * 100}% ${(py + 0.5) * 100}%, rgba(255,255,255,0.14), transparent 55%)`;
          }
          raf = null;
        });
      });

      card.addEventListener('mouseleave', () => {
        card.style.transition = 'transform 0.5s cubic-bezier(0.22, 1, 0.36, 1)';
        card.style.transform = 'perspective(900px) rotateY(0deg) rotateX(0deg)';
        const glare = card.querySelector('.tilt-glare');
        if (glare) glare.style.background = 'transparent';
      });
    });
  }

  /* ---------- Parallax Layers ---------- */
  const parallaxEls = [];
  function initParallax(root = document) {
    root.querySelectorAll('[data-parallax]').forEach((el) => {
      if (!parallaxEls.includes(el)) parallaxEls.push(el);
    });
  }
  let parallaxRaf = null;
  function parallaxTick() {
    const scrollY = window.scrollY;
    parallaxEls.forEach((el) => {
      const speed = parseFloat(el.dataset.parallax || '0.15');
      const rect = el.getBoundingClientRect();
      const centerOffset = rect.top + rect.height / 2 - window.innerHeight / 2;
      el.style.transform = `translate3d(0, ${centerOffset * speed * -1}px, 0)`;
    });
    parallaxRaf = null;
  }
  window.addEventListener('scroll', () => {
    if (!parallaxRaf) parallaxRaf = requestAnimationFrame(parallaxTick);
  }, { passive: true });

  /* ---------- Animated Counters ---------- */
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      counterObserver.unobserve(el);
      const target = parseFloat(el.dataset.count || '0');
      const duration = parseInt(el.dataset.countDuration || '1600', 10);
      const decimals = parseInt(el.dataset.countDecimals || '0', 10);
      const start = performance.now();
      function step(now) {
        const t = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = (target * eased).toFixed(decimals)
          .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        if (t < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    });
  }, { threshold: 0.5 });

  function initCounters(root = document) {
    root.querySelectorAll('[data-count]').forEach((el) => counterObserver.observe(el));
  }

  /* ---------- Magnetic Buttons ---------- */
  function initMagnetic(root = document) {
    root.querySelectorAll('[data-magnetic]').forEach((btn) => {
      if (btn.dataset.magneticBound) return;
      btn.dataset.magneticBound = '1';
      const strength = parseFloat(btn.dataset.magnetic || '14');
      btn.addEventListener('mousemove', (e) => {
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        btn.style.transform = `translate(${(x / rect.width) * strength}px, ${(y / rect.height) * strength}px)`;
        btn.style.transition = 'transform 0.1s ease-out';
      });
      btn.addEventListener('mouseleave', () => {
        btn.style.transition = 'transform 0.4s cubic-bezier(0.22, 1, 0.36, 1)';
        btn.style.transform = 'translate(0, 0)';
      });
    });
  }

  /* ---------- Scroll Progress Bar ---------- */
  function initProgress() {
    let bar = document.getElementById('scroll-progress');
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'scroll-progress';
      bar.style.cssText =
        'position:fixed;top:0;left:0;height:2px;width:0;z-index:9999;' +
        'background:linear-gradient(90deg,#FFCD05,#901C1E);transition:width 0.08s linear;';
      document.body.appendChild(bar);
    }
    window.addEventListener('scroll', () => {
      const h = document.documentElement;
      const pct = (h.scrollTop / (h.scrollHeight - h.clientHeight)) * 100;
      bar.style.width = pct + '%';
    }, { passive: true });
  }

  /* ---------- Smooth anchor scroll ---------- */
  function initAnchors() {
    document.querySelectorAll('a[href^="#"]').forEach((a) => {
      a.addEventListener('click', (e) => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  /* ---------- Auto Reveal (grids, sections, cards without attributes) ---------- */
  function initAutoReveal(root = document) {
    // Stagger direct children of grids
    root.querySelectorAll('main .grid').forEach((grid) => {
      if (grid.dataset.autoRevealed) return;
      grid.dataset.autoRevealed = '1';
      Array.from(grid.children).forEach((child, i) => {
        if (child.dataset.reveal || child.classList.contains('reveal-init')) return;
        child.setAttribute('data-reveal', '');
        child.setAttribute('data-reveal-delay', String((i % 6) * 60));
      });
    });
    // Reveal major content blocks
    root.querySelectorAll('main > section, main > div > section').forEach((section) => {
      if (section.dataset.reveal || section.dataset.autoRevealed) return;
      section.dataset.autoRevealed = '1';
      section.setAttribute('data-reveal', '');
    });
    initReveal(root);
  }

  /* ---------- Boot ---------- */
  function boot() {
    initReveal();
    initTilt();
    initParallax();
    initCounters();
    initMagnetic();
    initProgress();
    initAnchors();
    initAutoReveal();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // Re-init on dynamic content (Turbo/Livewire style navigation)
  window.KICCMotion = { refresh: boot };
})();
