/**
 * Always-playing media tile.
 * Desktop: plays when visible in viewport, pauses when scrolled out.
 * Mobile: same via IntersectionObserver.
 * Multiple tiles play simultaneously (no single-active constraint).
 */
function mediaTile() {
    return {
        active: true,
        videoReady: false,
        observer: null,
        mounted() {
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
        },
        destroyed() {
            if (this.observer) this.observer.disconnect();
        },
        onHoverEnter() {
            // no-op — videos already play automatically
        },
        onHoverLeave() {
            // no-op — videos keep playing, only pause on scroll-out
        },
        activate() {
            this.active = true;
        },
        deactivate() {
            this.active = false;
        },
        onVideoPlaying() {
            this.videoReady = true;
        },
    };
}