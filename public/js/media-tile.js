function mediaTile() {
    return {
        active: false,
        videoReady: false,
        hoverTimer: null,
        observer: null,
        _video: null,
        mounted() {
            this._video = this.$el.querySelector('video');
            if (window.matchMedia('(max-width: 1024px)').matches) {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && entry.intersectionRatio > 0.55) {
                            this.activate();
                            this.tryPlay();
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
        },
        onHoverEnter() {
            if (window.matchMedia('(min-width: 1025px)').matches) {
                if (this.hoverTimer) clearTimeout(this.hoverTimer);
                this.tryPlay();
                this.hoverTimer = setTimeout(() => {
                    this.activate();
                }, 300);
            }
        },
        onHoverLeave() {
            if (this.hoverTimer) clearTimeout(this.hoverTimer);
            this.deactivate();
        },
        activate() {
            this.active = true;
            window.dispatchEvent(new CustomEvent('media-tile:activate', { detail: this }));
        },
        deactivate() {
            this.active = false;
            if (this._video) this._video.pause();
        },
        tryPlay() {
            if (this._video) {
                this._video.play().catch(() => {
                    setTimeout(() => {
                        if (this._video) this._video.play().catch(() => {});
                    }, 500);
                });
            }
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