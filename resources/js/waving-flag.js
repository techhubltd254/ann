/**
 * KiccWavingFlag — 3D waving county flag using pure CSS + JS.
 * No WebGL, no Three.js, no canvas. Works everywhere.
 *
 * Uses CSS 3D perspective, vertical slice ribbons with sine-wave
 * transformY + translateZ, and mouse drag rotation.
 *
 * Usage:
 *   new KiccWavingFlag({ container: el, flagSvg: '<svg>...</svg>' });
 */
class KiccWavingFlag {
    constructor(options) {
        this.container = options.container;
        this.flagSvg = options.flagSvg;
        if (!this.container || !this.flagSvg) return;

        this._time = 0;
        this._windSpeed = 0.08;
        this._waveAmp = 14;
        this._isDragging = false;
        this._prevX = 0;
        this._rotationY = 15;
        this._slices = [];

        this._build();
        this._animate();
        this._initInteraction();
    }

    _build() {
        // Clear container
        this.container.innerHTML = '';
        this.container.style.cssText = `
            display: flex; align-items: center; cursor: grab;
            user-select: none; perspective: 1200px;
            width: 100%; height: 100%; justify-content: center;
        `;

        // Flag data URI
        const dataUri = 'data:image/svg+xml;utf8,' + encodeURIComponent(this.flagSvg);

        // Pole
        const pole = document.createElement('div');
        pole.style.cssText = `
            width: 10px; height: 220px;
            background: linear-gradient(90deg, #555, #ccc 45%, #fff 60%, #444 100%);
            border-radius: 4px; box-shadow: 3px 5px 12px rgba(0,0,0,0.5);
            position: relative; z-index: 10; flex-shrink: 0;
        `;
        const poleTop = document.createElement('div');
        poleTop.style.cssText = `
            position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
            width: 20px; height: 20px;
            background: radial-gradient(circle at 35% 35%, #ffd700, #b8860b);
            border-radius: 50%; box-shadow: 0 0 8px rgba(255,215,0,0.4);
        `;
        pole.appendChild(poleTop);
        this.container.appendChild(pole);

        // Flag container
        const flagContainer = document.createElement('div');
        flagContainer.id = 'flag-cloth';
        flagContainer.style.cssText = `
            display: flex; height: 130px; width: 220px;
            transform-style: preserve-3d; transform-origin: left center;
            box-shadow: 10px 12px 20px rgba(0,0,0,0.35);
            border-radius: 0 3px 3px 0; overflow: hidden;
            transform: rotateY(15deg);
        `;
        this._flagContainer = flagContainer;

        // Slice into vertical ribbons
        const SLICE_COUNT = 30;
        const sliceWidth = 220 / SLICE_COUNT;

        for (let i = 0; i < SLICE_COUNT; i++) {
            const slice = document.createElement('div');
            slice.style.cssText = `
                flex: none; width: ${sliceWidth}px; height: 100%;
                background-image: url("${dataUri}");
            background-size: 220px 130px;
            background-repeat: no-repeat;
            background-position: -${i * sliceWidth}px 0px;
            transform-style: preserve-3d;
            will-change: transform, filter;
        `;
        flagContainer.appendChild(slice);
        this._slices.push(slice);
    }

    this.container.appendChild(flagContainer);
}

_animate() {
    const loop = () => {
        this._time += this._windSpeed;
        const count = this._slices.length;
        for (let i = 0; i < count; i++) {
            const factor = i / count;
            const wave = Math.sin(this._time - i * 0.28) * (this._waveAmp * factor);
            const depth = Math.cos(this._time - i * 0.28) * (6 * factor);
            const light = 100 + (wave * 2.2);
            this._slices[i].style.transform =
                `translateY(${wave.toFixed(2)}px) translateZ(${depth.toFixed(2)}px)`;
            this._slices[i].style.filter =
                `brightness(${Math.min(Math.max(light, 70), 135)}%)`;
        }
        this._raf = requestAnimationFrame(loop);
    };
    loop();
}

_initInteraction() {
    const onDown = (e) => {
        this._isDragging = true;
        this._prevX = e.clientX || (e.touches && e.touches[0].clientX);
        this.container.style.cursor = 'grabbing';
    };
    const onMove = (e) => {
        if (!this._isDragging) return;
        const x = e.clientX || (e.touches && e.touches[0].clientX);
        const dx = x - this._prevX;
        this._rotationY = Math.max(-30, Math.min(45, this._rotationY + dx * 0.3));
        this._flagContainer.style.transform = `rotateY(${this._rotationY}deg)`;
        this._prevX = x;
    };
    const onUp = () => {
        this._isDragging = false;
        this.container.style.cursor = 'grab';
    };

    this.container.addEventListener('mousedown', onDown);
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
    this.container.addEventListener('touchstart', onDown, { passive: true });
    window.addEventListener('touchmove', onMove, { passive: true });
    window.addEventListener('touchend', onUp);
}

destroy() {
    if (this._raf) cancelAnimationFrame(this._raf);
    this.container.innerHTML = '';
}
}