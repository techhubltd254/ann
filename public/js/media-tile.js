/**
 * mediaTile — hover-to-play video tile.
 * Uses direct DOM querySelector (safe across Alpine versions).
 * Retries play() with load() if autoplay is rejected.
 * Preloads video on scroll-into-view for instant playback.
 */
function mediaTile() {
    return {
        active: false,
        videoReady: false,
        _el: null,
        _videoEl: null,

        mounted() {
            this._el = this.$el;
            this._videoEl = this._el.querySelector('video');
            // Preload video when tile scrolls into view
            if (this._videoEl && 'IntersectionObserver' in window) {
                const obs = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) {
                        this._videoEl.preload = 'auto';
                        this._videoEl.load();
                        obs.disconnect();
                    }
                }, { rootMargin: '200px' });
                obs.observe(this._el);
            }
        },

        onHoverEnter() {
            const v = this._videoEl;
            if (!v) return;
            v.muted = true;
            this._playWithRetry(v, 3);
            this.active = true;
        },

        onHoverLeave() {
            this.active = false;
            const v = this._videoEl;
            if (v) v.pause();
        },

        onVideoPlaying() {
            this.videoReady = true;
        },

        resetVideoReady() {
            this.videoReady = false;
        },

        _playWithRetry(v, attempts) {
            if (attempts <= 0) return;
            v.play().catch(() => {
                v.load();
                setTimeout(() => this._playWithRetry(v, attempts - 1), 300);
            });
        },
    };
}