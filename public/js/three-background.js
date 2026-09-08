/**
 * Kicc3DBackground — optional particle background for #kicc-3d-bg.
 * Silently does nothing if THREE is unavailable.
 */
class Kicc3DBackground {
    constructor(containerId) {
        this.container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        if (!this.container || typeof THREE === 'undefined') return;
        try { this._init(); } catch (e) { console.warn('3D background unavailable'); }
    }

    _init() {
        const w = window.innerWidth, h = window.innerHeight;
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(60, w / h, 0.1, 100);
        this.camera.position.z = 20;
        this.renderer = new THREE.WebGLRenderer({ antialias: false, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setSize(w, h);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.container.appendChild(this.renderer.domElement);

        const count = 800;
        const geo = new THREE.BufferGeometry();
        const pos = new Float32Array(count * 3);
        const colors = new Float32Array(count * 3);
        const palette = [[0.04,0.12,0.34],[0.56,0.11,0.12],[1.0,0.80,0.02],[0.04,0.44,0.75]];
        for (let i = 0; i < count; i++) {
            const i3 = i * 3;
            pos[i3] = (Math.random() - 0.5) * 40;
            pos[i3+1] = (Math.random() - 0.5) * 30;
            pos[i3+2] = (Math.random() - 0.5) * 20;
            const c = palette[Math.floor(Math.random() * palette.length)];
            colors[i3] = c[0] + (Math.random() - 0.5) * 0.1;
            colors[i3+1] = c[1] + (Math.random() - 0.5) * 0.1;
            colors[i3+2] = c[2] + (Math.random() - 0.5) * 0.1;
        }
        geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
        geo.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        this.particles = new THREE.Points(geo, new THREE.PointsMaterial({ size: 0.12, vertexColors: true, transparent: true, opacity: 0.5, blending: THREE.AdditiveBlending, sizeAttenuation: true }));
        this.scene.add(this.particles);

        this._mouseX = 0; this._mouseY = 0;
        document.addEventListener('mousemove', (e) => { this._mouseX = (e.clientX / w) * 2 - 1; this._mouseY = -(e.clientY / h) * 2 + 1; });

        this._observer = new IntersectionObserver((entries) => { this._visible = entries[0].isIntersecting; }, { threshold: 0 });
        this._observer.observe(this.container);
        this._visible = true;

        this._animate();
    }

    _animate() {
        if (!this.renderer || !this.scene || !this.camera) return;
        requestAnimationFrame(() => this._animate());
        if (!this._visible) return;
        if (this.particles) {
            this.particles.rotation.x += (this._mouseY * 0.02 - this.particles.rotation.x) * 0.03;
            this.particles.rotation.y += (this._mouseX * 0.02 - this.particles.rotation.y) * 0.03;
        }
        this.renderer.render(this.scene, this.camera);
    }
}

document.addEventListener('DOMContentLoaded', () => { new Kicc3DBackground('kicc-3d-bg'); });