function mediaTile() {
    return {
        active: false,
        videoReady: false,
        _el: null,
        _videoEl: null,

        mounted() {
            this._el = this.$el;
            this._videoEl = this._el.querySelector('video');
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