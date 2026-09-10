/**
 * Media tile — plays video on hover (desktop) or when centered in viewport (all widths).
 * Play() is called SYNCHRONOUSLY in the user gesture handler so the browser
 * does not reject it per autoplay policy. The visual activation is delayed
 * by 150ms to prevent accidental flicker on rapid mouse passes.
 * Only ONE tile plays at a time (single-active constraint).
 */
function mediaTile() {
    return {
        active: false,
        videoReady: false,
        hoverTimer: null,
        observer: null,
        mounted() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && entry.intersectionRatio > 0.55) {
                        this.startVideo();
                        this.activate();
                    } else {
                        this.pauseVideo();
                        this.deactivate();
                    }
                });
            }, { threshold: [0.55] });
            this.observer.observe(this.$el);
        },
        destroyed() {
            if (this.observer) this.observer.disconnect();
            if (this.hoverTimer) clearTimeout(this.hoverTimer);
        },
        onHoverEnter() {
            if (this.hoverTimer) clearTimeout(this.hoverTimer);
            // Play immediately in the mouseenter user gesture — browser allows it.
            // Then delay the visual activation to prevent flicker.
            this.startVideo();
            this.hoverTimer = setTimeout(() => this.activate(), 150);
        },
        onHoverLeave() {
            if (this.hoverTimer) clearTimeout(this.hoverTimer);
            this.pauseVideo();
            this.deactivate();
        },
        startVideo() {
            const v = this.$el.querySelector('video');
            if (v) v.play().catch(() => {});
        },
        pauseVideo() {
            const v = this.$el.querySelector('video');
            if (v) v.pause();
        },
        activate() {
            this.active = true;
            window.dispatchEvent(new CustomEvent('media-tile:activate', { detail: this }));
        },
        deactivate() {
            this.active = false;
        },
        onVideoPlaying() {
            this.videoReady = true;
        },
    };
}

document.addEventListener('media-tile:activate', (e) => {
    document.querySelectorAll('[x-data="mediaTile()"]').forEach((el) => {
        if (e.detail && el === e.detail.$el) return;
        const tile = window.Alpine ? Alpine.$data(el) : null;
        if (tile) tile.active = false;
        const v = el.querySelector('video');
        if (v) v.pause();
    });
});