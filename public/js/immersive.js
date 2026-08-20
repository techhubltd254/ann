document.addEventListener('DOMContentLoaded', function () {
    // ── Tilt effect on cards ──
    var tiltEls = document.querySelectorAll('[data-tilt]');
    tiltEls.forEach(function (el) {
        var max = parseInt(el.getAttribute('data-tilt')) || 10;
        el.addEventListener('mousemove', function (e) {
            var rect = el.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width;
            var y = (e.clientY - rect.top) / rect.height;
            var rotateX = (y - 0.5) * -max;
            var rotateY = (x - 0.5) * max;
            el.style.transform = 'perspective(600px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)';
        });
        el.addEventListener('mouseleave', function () {
            el.style.transform = 'perspective(600px) rotateX(0deg) rotateY(0deg)';
        });
    });

    // ── Float animation ──
    var floatEls = document.querySelectorAll('[data-float]');
    floatEls.forEach(function (el) {
        var amp = parseFloat(el.getAttribute('data-float-amplitude')) || 8;
        var duration = 3000 + Math.random() * 2000;
        el.style.animation = 'float-slow ' + (duration / 1000) + 's ease-in-out infinite';
    });

    // ── Magnetic button effect ──
    var magneticEls = document.querySelectorAll('[data-magnetic]');
    magneticEls.forEach(function (el) {
        el.addEventListener('mousemove', function (e) {
            var rect = el.getBoundingClientRect();
            var x = e.clientX - rect.left - rect.width / 2;
            var y = e.clientY - rect.top - rect.height / 2;
            el.style.transform = 'translate(' + (x * 0.2) + 'px, ' + (y * 0.2) + 'px)';
        });
        el.addEventListener('mouseleave', function () {
            el.style.transform = 'translate(0, 0)';
        });
    });
});