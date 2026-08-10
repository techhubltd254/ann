/* KICC FX3D — self-contained 3D parallax + tilt + VFX engine.
   No dependencies. Works on every image/video card and hero media.
   Attributes:
     [data-tilt]        -> 3D tilt + moving glare on hover (data-tilt="8" = max deg)
     [data-depth]       -> mouse parallax layer (0.1–0.6)
     [data-reveal]      -> scroll reveal (up | left | right | zoom)
     .fx-kenburns       -> slow cinematic zoom-pan VFX
     .fx-sweep          -> periodic light-sweep VFX across the element
*/
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ── 1. SCROLL REVEAL ─────────────────────────────────────────── */
  function initReveal() {
    var els = document.querySelectorAll('[data-reveal]');
    if (!('IntersectionObserver' in window) || reduceMotion) {
      els.forEach(function (el) { el.classList.add('revealed'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          var d = e.target.getAttribute('data-reveal-delay');
          setTimeout(function () { e.target.classList.add('revealed'); }, d ? +d : 0);
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    els.forEach(function (el) { el.classList.add('reveal-init'); io.observe(el); });
  }

  /* ── 2. 3D TILT + GLARE ───────────────────────────────────────── */
  function initTilt() {
    if (reduceMotion || !window.matchMedia('(pointer: fine)').matches) return;
    document.querySelectorAll('[data-tilt]').forEach(function (card) {
      var max = parseFloat(card.getAttribute('data-tilt')) || 7;
      var glare = card.querySelector('.tilt-glare');
      if (!glare) {
        glare = document.createElement('div');
        glare.className = 'tilt-glare';
        card.appendChild(glare);
      }
      var rx = 0, ry = 0, tx = 0, ty = 0, raf = null;
      function frame() {
        rx += (tx - rx) * 0.16; ry += (ty - ry) * 0.16;
        card.style.transform = 'perspective(900px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg) translateZ(0)';
        if (Math.abs(tx - rx) > 0.02 || Math.abs(ty - ry) > 0.02) raf = requestAnimationFrame(frame);
        else raf = null;
      }
      card.addEventListener('pointermove', function (e) {
        var r = card.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
        ty = (px - 0.5) * max * 2;      // rotateY
        tx = (0.5 - py) * max * 2;      // rotateX
        glare.style.background = 'radial-gradient(circle at ' + (px * 100) + '% ' + (py * 100) + '%, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0) 55%)';
        glare.style.opacity = '1';
        if (!raf) raf = requestAnimationFrame(frame);
      });
      card.addEventListener('pointerleave', function () {
        tx = 0; ty = 0; glare.style.opacity = '0';
        if (!raf) raf = requestAnimationFrame(frame);
      });
    });
  }

  /* ── 3. MOUSE PARALLAX (hero + media layers) ──────────────────── */
  function initParallax() {
    if (reduceMotion) return;
    var layers = Array.prototype.slice.call(document.querySelectorAll('[data-depth]'));
    if (!layers.length) return;
    var mx = 0, my = 0, cx = 0, cy = 0, raf = null;
    function frame() {
      cx += (mx - cx) * 0.06; cy += (my - cy) * 0.06;
      layers.forEach(function (l) {
        var d = parseFloat(l.getAttribute('data-depth')) || 0.2;
        l.style.transform = 'translate3d(' + (cx * d * 60).toFixed(1) + 'px,' + (cy * d * 40).toFixed(1) + 'px,0) scale(' + (1 + d * 0.08) + ')';
      });
      if (Math.abs(mx - cx) > 0.001 || Math.abs(my - cy) > 0.001) raf = requestAnimationFrame(frame);
      else raf = null;
    }
    window.addEventListener('pointermove', function (e) {
      mx = (e.clientX / window.innerWidth - 0.5) * 2;
      my = (e.clientY / window.innerHeight - 0.5) * 2;
      if (!raf) raf = requestAnimationFrame(frame);
    }, { passive: true });

    /* scroll parallax on hero media */
    var heroes = document.querySelectorAll('video[data-parallax-scroll], img[data-parallax-scroll]');
    if (heroes.length) {
      var ticking = false;
      window.addEventListener('scroll', function () {
        if (ticking) return; ticking = true;
        requestAnimationFrame(function () {
          var y = window.scrollY;
          heroes.forEach(function (h) {
            h.style.transform = 'translate3d(0,' + (y * 0.28).toFixed(1) + 'px,0) scale(1.12)';
          });
          ticking = false;
        });
      }, { passive: true });
    }
  }

  /* ── 4. KEN BURNS + LIGHT SWEEP VFX ───────────────────────────── */
  function initVfx() {
    if (reduceMotion) return;
    document.querySelectorAll('.card-hover img, [data-tilt] img').forEach(function (img) {
      img.classList.add('fx-kenburns');
    });
  }

  /* ── 5. 3D WIGGLE MEDIA (photo-burst depth videos) ──────────────
     <div data-wiggle="/path/wiggle.mp4"> wrapping an <img> — on hover
     the real 3D burst video fades in and plays. */
  function initWiggle() {
    if (reduceMotion) return;
    document.querySelectorAll('[data-wiggle]').forEach(function (box) {
      var vid = null;
      box.addEventListener('pointerenter', function () {
        if (!vid) {
          vid = document.createElement('video');
          vid.muted = true; vid.loop = true; vid.playsInline = true;
          vid.src = box.getAttribute('data-wiggle');
          vid.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .45s ease';
          box.appendChild(vid);
        }
        vid.play().catch(function () {});
        requestAnimationFrame(function () { vid.style.opacity = '1'; });
      });
      box.addEventListener('pointerleave', function () {
        if (!vid) return;
        vid.style.opacity = '0';
        setTimeout(function () { vid.pause(); }, 450);
      });
    });
  }

  function boot() { initReveal(); initTilt(); initParallax(); initVfx(); initWiggle(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
