/**
 * KiccWavingFlag — 3D waving county flag using Three.js.
 *
 * Renders county flag SVG as a texture on a subdivided plane with
 * sine-wave vertex displacement for realistic cloth motion.
 * Mouse/touch drag rotates the flag. Auto-waves when idle.
 *
 * Usage:
 *   new KiccWavingFlag({
 *       container: document.getElementById('flag-stage'),
 *       flagDataUri: 'data:image/svg+xml,...',
 *   });
 */
class KiccWavingFlag {
    constructor(options) {
        this.container = options.container;
        this.flagDataUri = options.flagDataUri;
        this._disposed = false;
        this._isDragging = false;
        this._prevMouse = { x: 0, y: 0 };
        this._time = 0;
        this._windSpeed = 0.08;
        this._waveAmp = 14;

        if (!this.container || !this.flagDataUri || typeof THREE === 'undefined') return;
        try { this._init(); } catch (e) { console.warn('Flag unavailable:', e); }
    }

    _init() {
        const w = 320, h = 240; // Fixed internal dimensions for flag
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(30, w / h, 0.1, 50);
        this.camera.position.set(-1.5, 0.5, 6);

        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance',
        });
        this.renderer.setSize(w, h);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping || THREE.LinearToneMapping;
        this.renderer.toneMappingExposure = 1.0;
        this.container.appendChild(this.renderer.domElement);

        // Load SVG as texture
        this._loadTexture();
        this._initFlagMesh();
        this._initInteraction();
        this._initResizeObserver();
        this._animate();
    }

    _loadTexture() {
        const img = new Image();
        img.src = this.flagDataUri;
        img.crossOrigin = 'anonymous';
        this.texture = new THREE.Texture(img);
        this.texture.minFilter = THREE.LinearFilter;
        this.texture.magFilter = THREE.LinearFilter;
        img.onload = () => { this.texture.needsUpdate = true; };
    }

    _initFlagMesh() {
        const segW = 36, segH = 20;
        const geo = new THREE.PlaneGeometry(3.2, 2.0, segW, segH);
        this.geo = geo;
        this._origPos = new Float32Array(geo.attributes.position.array);

        const mat = new THREE.ShaderMaterial({
            uniforms: {
                uTexture: { value: this.texture },
                uTime: { value: 0 },
                uWind: { value: this._windSpeed },
                uAmp: { value: this._waveAmp / 100 },
            },
            vertexShader: `
                uniform float uTime;
                uniform float uWind;
                uniform float uAmp;
                varying vec2 vUv;
                void main() {
                    vUv = uv;
                    vec3 pos = position;
                    float wave = sin(uTime * uWind * 30.0 - pos.x * 4.0) * uAmp * (pos.x + 1.6) * 0.6;
                    float wave2 = cos(uTime * uWind * 20.0 - pos.x * 3.0) * uAmp * 0.3 * (pos.x + 1.6) * 0.6;
                    pos.z += wave + wave2;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
                }
            `,
            fragmentShader: `
                uniform sampler2D uTexture;
                varying vec2 vUv;
                void main() {
                    vec4 color = texture2D(uTexture, vUv);
                    gl_FragColor = color;
                }
            `,
            side: THREE.DoubleSide,
        });

        this.mesh = new THREE.Mesh(geo, mat);
        this.mesh.position.set(-0.5, 0, 0);
        this.scene.add(this.mesh);

        // Pole
        const poleGeo = new THREE.CylinderGeometry(0.04, 0.05, 2.4, 8);
        const poleMat = new THREE.MeshStandardMaterial({ color: 0x888888, metalness: 0.4, roughness: 0.6 });
        this.pole = new THREE.Mesh(poleGeo, poleMat);
        this.pole.position.set(-1.8, -0.1, 0);
        this.scene.add(this.pole);

        // Pole top (ball)
        const ballGeo = new THREE.SphereGeometry(0.07, 12, 12);
        const ballMat = new THREE.MeshStandardMaterial({ color: 0xffd700, metalness: 0.6, roughness: 0.3 });
        this.ball = new THREE.Mesh(ballGeo, ballMat);
        this.ball.position.set(-1.8, 1.19, 0);
        this.scene.add(this.ball);

        // Ambient + directional light for pole
        this.scene.add(new THREE.AmbientLight(0xffffff, 0.6));
        const key = new THREE.DirectionalLight(0xffffff, 0.8);
        key.position.set(2, 3, 4);
        this.scene.add(key);
    }

    _initInteraction() {
        const canvas = this.renderer.domElement;
        const onDown = (e) => {
            this._isDragging = true;
            const p = e.touches ? e.touches[0] : e;
            this._prevMouse = { x: p.clientX, y: p.clientY };
        };
        const onMove = (e) => {
            if (!this._isDragging || !this.mesh) return;
            const p = e.touches ? e.touches[0] : e;
            const dx = p.clientX - this._prevMouse.x;
            const dy = p.clientY - this._prevMouse.y;
            this.mesh.rotation.y += dx * 0.008;
            this.mesh.rotation.x += dy * 0.004;
            this.mesh.rotation.x = Math.max(-0.3, Math.min(0.3, this.mesh.rotation.x));
            this._prevMouse = { x: p.clientX, y: p.clientY };
        };
        const onUp = () => { this._isDragging = false; };
        canvas.addEventListener('mousedown', onDown);
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);
        canvas.addEventListener('touchstart', onDown, { passive: true });
        window.addEventListener('touchmove', onMove, { passive: true });
        window.addEventListener('touchend', onUp);
    }

    _initResizeObserver() {
        this._ro = new ResizeObserver(() => {
            if (this._disposed) return;
            const rect = this.container.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
                this.renderer.setSize(rect.width, rect.height);
                this.camera.aspect = rect.width / rect.height;
                this.camera.updateProjectionMatrix();
            }
        });
        this._ro.observe(this.container);
    }

    _animate() {
        if (this._disposed) return;
        requestAnimationFrame(() => this._animate());

        this._time += 0.016;
        if (this.mesh && this.mesh.material.uniforms) {
            this.mesh.material.uniforms.uTime.value = this._time;
        }
        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }

    dispose() {
        this._disposed = true;
        if (this._ro) this._ro.disconnect();
        if (this.renderer) {
            this.renderer.dispose();
            const el = this.renderer.domElement;
            if (el && el.parentNode) el.parentNode.removeChild(el);
        }
        if (this.mesh) { this.mesh.geometry.dispose(); this.mesh.material.dispose(); }
    }
}