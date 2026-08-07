/**
 * Booth overlay system for the WebGL viewer.
 *
 * Handles:
 * - Booth data loading from JSON
 * - Info card rendering
 * - SBS video playback within booths
 * - Accessibility features
 */

class BoothOverlaySystem {
    constructor() {
        this.booths = [];
        this.activeOverlay = null;
        this.videoPlayers = {};
        this.loadBoothData();
    }

    async loadBoothData() {
        try {
            const resp = await fetch('booth_data.json');
            if (resp.ok) {
                this.booths = await resp.json();
            }
        } catch {
            // Use defaults defined in each booth mesh userData
        }
    }

    showBoothCard(boothData) {
        // Main popup is handled by the DOM in index.html
        // This method extends functionality for video playback
        const popup = document.getElementById('booth-popup');
        if (!popup) return;

        document.getElementById('booth-title').textContent = boothData.title || 'Booth';
        document.getElementById('booth-desc').textContent =
            boothData.description || 'Explore this exhibition booth.';
        document.getElementById('booth-sector').textContent =
            `Sector: ${boothData.sector || 'General'}`;

        popup.style.display = 'block';
    }

    hideBoothCard() {
        const popup = document.getElementById('booth-popup');
        if (popup) popup.style.display = 'none';
    }
}

// Export for use in player.js
window.BoothOverlaySystem = BoothOverlaySystem;
