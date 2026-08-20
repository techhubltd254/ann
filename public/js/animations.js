document.addEventListener('DOMContentLoaded', function () {
    // ── Count-up animation ──
    function animateCount(el) {
        var target = parseInt(el.getAttribute('data-count')) || 0;
        var duration = parseInt(el.getAttribute('data-count-duration')) || 1500;
        var delay = parseInt(el.getAttribute('data-count-delay')) || 0;
        var start = 0;
        var startTime = null;

        setTimeout(function () {
            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                var current = Math.floor(eased * target);
                el.textContent = current.toLocaleString();
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = target.toLocaleString();
                }
            }
            requestAnimationFrame(step);
        }, delay);
    }

    var countEls = document.querySelectorAll('[data-count]');
    var countObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCount(entry.target);
                countObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });

    countEls.forEach(function (el) { countObserver.observe(el); });

    // ── Scroll reveal ──
    var revealEls = document.querySelectorAll('[data-reveal]');
    var revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                var delay = parseInt(entry.target.getAttribute('data-reveal-delay')) || 0;
                setTimeout(function () {
                    entry.target.classList.add('revealed');
                }, delay);
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    revealEls.forEach(function (el) {
        el.classList.add('reveal-init');
        revealObserver.observe(el);
    });

    // ── Section transition (kicc-section) ──
    var sectionEls = document.querySelectorAll('.section-transition');
    var sectionObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('kicc-section-visible');
                sectionObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.05 });

    sectionEls.forEach(function (el) {
        el.classList.add('kicc-section-hidden');
        sectionObserver.observe(el);
    });

    // ── Split text reveal ──
    var splitEls = document.querySelectorAll('[data-split]');
    splitEls.forEach(function (el) {
        var text = el.textContent.trim();
        var words = text.split(' ');
        el.innerHTML = '';
        words.forEach(function (word, i) {
            var span = document.createElement('span');
            span.className = 'kicc-split-word';
            span.textContent = word + (i < words.length - 1 ? '\u00A0' : '');
            span.style.transitionDelay = (i * 0.035) + 's';
            el.appendChild(span);
        });

        var splitObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var spans = entry.target.querySelectorAll('.kicc-split-word');
                    spans.forEach(function (s) { s.classList.add('kicc-split-visible'); });
                    splitObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        splitObserver.observe(el);
    });

    // ── Video play fix (ensure autoplay works everywhere - YouTube/Netflix style) ──
    function ensureVideoPlays(video) {
        if (!video) return;
        var playPromise = video.play();
        if (playPromise !== undefined) {
            playPromise.catch(function () {
                // Autoplay blocked — show controls and try on interaction
                video.controls = true;
                function tryPlay() {
                    video.play();
                    document.removeEventListener('click', tryPlay);
                    document.removeEventListener('touchstart', tryPlay);
                    document.removeEventListener('scroll', tryPlay);
                }
                document.addEventListener('click', tryPlay, { once: true });
                document.addEventListener('touchstart', tryPlay, { once: true });
                document.addEventListener('scroll', tryPlay, { once: true });
            });
        }
    }

    var heroVideos = document.querySelectorAll('video[id$="hero-video"], video[autoplay]');
    heroVideos.forEach(ensureVideoPlays);
});