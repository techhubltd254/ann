/**
 * Kicc3DBackground — interactive procedural particle mesh background.
 * Attaches to #kicc-3d-bg div on all pages.
 * Auto-pauses when not visible (IntersectionObserver).
 */
class Kicc3DBackground {
    constructor(containerId = 'kicc-3d-bg') {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this._disposed = false;
        this._mouseX = 0;
        this._mouseY = 0;

        this._initScene();
        this._initParticles();
        this._initMouseTracking();
        this._initVisibilityObserver();
        this._animate();
    }

    _initScene() {
        const w = window.innerWidth;
        const h = window.innerHeight;

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(60, w / h, 0.1, 100);
        this.camera.position.z = 20;

        this.renderer = new THREE.WebGLRenderer({
            antialias: false,
            alpha: true,
            powerPreference: 'high-performance',
        });
        this.renderer.setSize(w, h);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.container.appendChild(this.renderer.domElement);
    }

    _initParticles() {
        const count = 1200;
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(count * 3);
        const colors = new Float32Array(count * 3);

        const palette = [
            [0.04, 0.12, 0.34], // #0B1E57
            [0.56, 0.11, 0.12], // #901C1E
            [1.0, 0.80, 0.02],  // #FFCD05
            [0.04, 0.44, 0.75], // #0EA5E9
        ];

        for (let i = 0; i < count; i++) {
            const i3 = i * 3;
            positions[i3] = (Math.random() - 0.5) * 40;
            positions[i3 + 1] = (Math.random() - 0.5) * 30;
            positions[i3 + 2] = (Math.random() - 0.5) * 20;

            const color = palette[Math.floor(Math.random() * palette.length)];
            colors[i3] = color[0] + (Math.random() - 0.5) * 0.1;
            colors[i3 + 1] = color[1] + (Math.random() - 0.5) * 0.1;
            colors[i3 + 2] = color[2] + (Math.random() - 0.5) * 0.1;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        const material = new THREE.PointsMaterial({
            size: 0.12,
            vertexColors: true,
            transparent: true,
            opacity: 0.6,
            blending: THREE.AdditiveBlending,
            sizeAttenuation: true,
        });

        this.particles = new THREE.Points(geometry, material);
        this.scene.add(this.particles);
        this._initialPositions = new Float32Array(positions);
    }

    _initMouseTracking() {
        document.addEventListener('mousemove', (e) => {
            this._mouseX = (e.clientX / window.innerWidth) * 2 - 1;
            this._mouseY = -(e.clientY / window.innerHeight) * 2 + 1;
        });
    }

    _initVisibilityObserver() {
        this._observer = new IntersectionObserver((entries) => {
            this._visible = entries[0].isIntersecting;
        }, { threshold: 0 });
        this._observer.observe(this.container);
        this._visible = true;
    }

    _animate() {
        if (this._disposed) return;
        requestAnimationFrame(() => this._animate());

        if (!this._visible) return;

        // Gentle rotation based on mouse
        this.particles.rotation.x += (this._mouseY * 0.02 - this.particles.rotation.x) * 0.03;
        this.particles.rotation.y += (this._mouseX * 0.02 - this.particles.rotation.y) * 0.03;

        // Subtle floating motion
        const positions = this.particles.geometry.attributes.position.array;
        const time = Date.now() * 0.0001;
        for (let i = 0; i < positions.length; i += 3) {
            positions[i + 1] += Math.sin(time + positions[i]) * 0.001;
        }
        this.particles.geometry.attributes.position.needsUpdate = true;

        this.renderer.render(this.scene, this.camera);
    }

    dispose() {
        this._disposed = true;
        if (this._observer) this._observer.disconnect();
        if (this.renderer) {
            this.renderer.dispose();
            if (this.renderer.domElement.parentNode) {
                this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
            }
        }
        if (this.particles) {
            this.particles.geometry.dispose();
            this.particles.material.dispose();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.kiccBackground = new Kicc3DBackground();
});