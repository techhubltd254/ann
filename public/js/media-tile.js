function mediaTile() {
    return {
        active: false,
        videoReady: false,
        hoverTimer: null,
        observer: null,
        mounted() {
            if (window.matchMedia('(max-width: 1024px)').matches) {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && entry.intersectionRatio > 0.55) {
                            this.activate();
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
                this.hoverTimer = setTimeout(() => this.activate(), 250);
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
            if (this.$refs.video) {
                this.$refs.video.pause();
                this.$refs.video.currentTime = 0;
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
        if (tile && tile !== e.detail) {
            tile.active = false;
            if (tile.$refs?.video) {
                tile.$refs.video.pause();
                tile.$refs.video.currentTime = 0;
            }
        }
    });
});