/**
 * mediaTile — autoplay-grid video tile (Pornhub-style).
 * Plays muted when tile enters viewport, pauses when it leaves.
 * Preloads 300px before viewport for instant playback.
 * Hover triggers sound-unmute (desktop only).
 */
function mediaTile() {
    return {
        active: false,
        videoReady: false,
        intersecting: false,
        _el: null,
        _videoEl: null,
        _observer: null,

        mounted() {
            this._el = this.$el;
            this._videoEl = this._el.querySelector('video');

            if (!this._videoEl) return;

            // Preload video when tile approaches viewport
            const preloadObs = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    this._videoEl.preload = 'auto';
                    this._videoEl.load();
                    preloadObs.disconnect();
                }
            }, { rootMargin: '300px' });
            preloadObs.observe(this._el);

            // Autoplay when visible, pause when hidden
            this._observer = new IntersectionObserver((entries) => {
                const e = entries[0];
                this.intersecting = e.isIntersecting;
                if (e.isIntersecting) {
                    this._playVideo();
                } else {
                    this._pauseVideo();
                }
            }, { threshold: 0.3 });
            this._observer.observe(this._el);
        },

        destroy() {
            if (this._observer) this._observer.disconnect();
        },

        onHoverEnter() {
            this.active = true;
            // Hover just ensures video keeps playing (already playing if visible)
            const v = this._videoEl;
            if (v && v.paused && this.intersecting) {
                this._playVideo();
            }
        },

        onHoverLeave() {
            this.active = false;
            // If still intersecting, keep playing; otherwise pause handles it
        },

        onVideoPlaying() {
            this.videoReady = true;
        },

        resetVideoReady() {
            this.videoReady = false;
        },

        _playVideo() {
            const v = this._videoEl;
            if (!v || v.paused === false) return;
            v.muted = true;
            this._playWithRetry(v, 2);
        },

        _pauseVideo() {
            const v = this._videoEl;
            if (v) v.pause();
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