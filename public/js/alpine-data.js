document.addEventListener('alpine:init', () => {
    Alpine.data('scrollState', () => ({
        scrolled: false,
        init() { window.addEventListener('scroll', () => { this.scrolled = window.scrollY > 50; }, { passive: true }); }
    }));
    
    Alpine.data('toggleState', () => ({
        open: false,
        toggle() { this.open = !this.open; }
    }));
    
    Alpine.data('loadingState', () => ({
        loading: false,
        start() { this.loading = true; },
        stop() { this.loading = false; }
    }));
});