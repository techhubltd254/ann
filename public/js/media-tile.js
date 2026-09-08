function mediaTile() {
    return {
        active: false,
        videoReady: false,
        hoverTimer: null,
        observer: null,
        _videoEl: null,
        mounted() {
            this._videoEl = this.$el.querySelector('video');
            // Desktop: hover-to-play. Mobile/tablet: auto-play when in view.
            if (window.matchMedia('(max-width: 1024px)').matches) {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && entry.intersectionRatio > 0.55) {
                            this.activate();
                            this.startVideo();
                        } else {
                            this.deactivate();
                        }
                    });
                }, { threshold: [0.55] });
                this.observer.observe(this.$el);
            }
        },
        destroyed() {
            if (this.observer) this.observer.disconnect();
            if (this.hoverTimer) clearTimeout(this.hoverTimer);
            this.pauseVideo();
        },
        onHoverEnter() {
            if (this.hoverTimer) clearTimeout(this.hoverTimer);
            this.activate();
            this.startVideo();
        },
        onHoverLeave() {
            if (this.hoverTimer) clearTimeout(this.hoverTimer);
            this.deactivate();
            this.pauseVideo();
        },
        activate() {
            this.active = true;
            window.dispatchEvent(new CustomEvent('media-tile:activate', { detail: this }));
        },
        deactivate() {
            this.active = false;
        },
        startVideo() {
            const video = this._videoEl || this.$el.querySelector('video');
            if (!video) return;
            video.muted = true;
            video.play().catch(() => {
                // Autoplay policy may block; retry once after a short delay.
                setTimeout(() => { if (video.play) video.play().catch(() => {}); }, 300);
            });
        },
        pauseVideo() {
            const video = this._videoEl || this.$el.querySelector('video');
            if (video) video.pause();
        },
        onVideoPlaying() {
            this.videoReady = true;
        },
        resetVideoReady() {
            this.videoReady = false;
        },
    };
}

document.addEventListener('media-tile:activate', (e) => {
    document.querySelectorAll('[x-data="mediaTile()"]').forEach((el) => {
        const tile = window.Alpine ? Alpine.$data(el) : null;
        if (tile === e.detail) return;
        if (tile) tile.active = false;
        const video = el.querySelector('video');
        if (video) video.pause();
    });
});