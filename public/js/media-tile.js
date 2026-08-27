/**
 * YouTube-style single-active media tile (Tier 1 poster -> Tier 2 hover/in-view loop)
 * Desktop: plays when hovered (250ms delay).
 * Mobile: plays when centered in the viewport (IntersectionObserver).
 * Only ONE tile plays at a time across the page.
 */
function mediaTile() {
    return {
        active: false,
        hoverTimer: null,
        observer: null,
        mounted() {
            // Mobile/tablet: play when this tile is centered in the viewport (YouTube style)
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
            // Desktop: 250ms delay to avoid accidental playback while scrolling
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
            // Only one video plays at a time (YouTube style)
            window.dispatchEvent(new CustomEvent('media-tile:activate', { detail: this }));
        },
        deactivate() {
            this.active = false;
        },
    };
}

document.addEventListener('media-tile:activate', (e) => {
    document.querySelectorAll('[x-data="mediaTile()"]').forEach((el) => {
        const tile = window.Alpine ? Alpine.$data(el) : null;
        if (tile && tile !== e.detail) tile.active = false;
    });
});