/**
 * Unified media tile — plays video on hover (desktop) or when in viewport (mobile).
 * Video play() is called directly via DOM querySelector inside mouseenter handler,
 * so it's always within the browser's user gesture context.
 *
 * x-effect only handles pause — play is done directly for reliability.
 */
function mediaTile() {
    return {
        active: false,
        videoReady: false,

        _el: null,

        mounted() { this._el = this.$el; },

        onHoverEnter() {
            const v = this._el.querySelector('video');
            if (v) {
                v.muted = true;
                v.play().catch(() => {
                    // If first play fails, reload source and retry once
                    v.load();
                    setTimeout(() => v.play().catch(() => {}), 200);
                });
            }
            this.active = true;
        },

        onHoverLeave() {
            this.active = false;
            const v = this._el.querySelector('video');
            if (v) v.pause();
        },

        onVideoPlaying() { this.videoReady = true; },
        resetVideoReady() { this.videoReady = false; },
    };
}