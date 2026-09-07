/**
 * Kicc3DVideoPlayer — renders any video as a 3D parallax scene using Three.js.
 * No external dependencies beyond THREE.js (r128+ global build).
 *
 * Features:
 *   - Video mapped as texture on subdivided plane
 *   - Depth map drives vertex displacement (real 3D parallax)
 *   - Auto-rotation when idle (0.5 RPM)
 *   - Simple pointer drag to rotate (no OrbitControls dependency)
 *   - Falls back to flat video if no depth map
 *   - Always-on, always-playing, auto-loop
 *
 * Usage:
 *   new Kicc3DVideoPlayer({ container: el, videoUrl: '...', depthMapUrl: '...', mode: 'parallax' });
 */
class Kicc3DVideoPlayer {
    constructor(options) {
        this.container = options.container;
        this.videoUrl = options.videoUrl;
        this.depthMapUrl = options.depthMapUrl || null;
        this.mode = options.mode || 'parallax';
        this.autoRotateSpeed = options.autoRotateSpeed || 0.006;
        this.autoRotateIdleDelay = options.autoRotateIdleDelay || 3000;
        this._disposed = false;
        this._lastInteraction = Date.now();
        this._autoRotating = true;
        this._isDragging = false;
        this._prevMouse = { x: 0, y: 0 };
        this._rotationVelocity = { x: 0, y: 0 };

        if (!this.container || !this.videoUrl) return;
        this._init();
    }

    _init() {
        if (typeof THREE === 'undefined') {
            console.warn('THREE not loaded, falling back to flat video');
            this._fallbackToFlat();
            return;
        }

        try {
            this._initScene();
            this._initLights();
            this._initVideoTexture();
            this._initGeometry();
            this._initInteraction();
            this._initResizeObserver();
            this._animate();
        } catch (e) {
            console.warn('3D player init failed, falling back to flat:', e);
            this._fallbackToFlat();
        }
    }

    _fallbackToFlat() {
        if (!this.videoUrl) return;
        this.container.innerHTML = '';
        const video = document.createElement('video');
        video.src = this.videoUrl;
        video.muted = true;
        video.loop = true;
        video.playsinline = true;
        video.autoplay = true;
        video.setAttribute('playsinline', '');
        video.className = 'absolute inset-0 w-full h-full object-cover';
        this.container.appendChild(video);
    }

    _initScene() {
        const w = this.container.clientWidth || 640;
        const h = this.container.clientHeight || (w * 9 / 16);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, w / h, 0.1, 100);
        this.camera.position.set(0, 0, 3.2);

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
    }

    _initLights() {
        const ambient = new THREE.AmbientLight(0xffffff, 0.7);
        this.scene.add(ambient);
        const key = new THREE.DirectionalLight(0xffffff, 1.5);
        key.position.set(3, 4, 3);
        this.scene.add(key);
        const rim = new THREE.DirectionalLight(0x6366f1, 0.8);
        rim.position.set(-3, -1, -2);
        this.scene.add(rim);
    }

    _initVideoTexture() {
        this.video = document.createElement('video');
        this.video.src = this.videoUrl;
        this.video.muted = true;
        this.video.loop = true;
        this.video.playsinline = true;
        this.video.crossOrigin = 'anonymous';
        this.video.setAttribute('playsinline', '');
        this.video.play().catch(() => {});

        this.videoTexture = new THREE.VideoTexture(this.video);
        this.videoTexture.minFilter = THREE.LinearFilter;
        this.videoTexture.magFilter = THREE.LinearFilter;
        this.videoTexture.colorSpace = THREE.SRGBColorSpace;

        this.depthVideo = null;
        this.depthTexture = null;
        if (this.depthMapUrl) {
            this.depthVideo = document.createElement('video');
            this.depthVideo.src = this.depthMapUrl;
            this.depthVideo.muted = true;
            this.depthVideo.loop = true;
            this.depthVideo.playsinline = true;
            this.depthVideo.crossOrigin = 'anonymous';
            this.depthVideo.play().catch(() => {});
            this.depthTexture = new THREE.VideoTexture(this.depthVideo);
            this.depthTexture.minFilter = THREE.LinearFilter;
            this.depthTexture.magFilter = THREE.LinearFilter;
        }
    }

    _initGeometry() {
        const seg = 48;
        const geo = new THREE.PlaneGeometry(2.4, 1.35, seg, seg);
        const vertCount = geo.attributes.position.count;

        const material = new THREE.ShaderMaterial({
            uniforms: {
                uTexture: { value: this.videoTexture },
                uDepthMap: { value: this.depthTexture || this.videoTexture },
                uDisplacement: { value: this.depthMapUrl ? 0.05 : 0 },
                uBrightness: { value: 1.0 },
            },
            vertexShader: `
                uniform float uDisplacement;
                uniform sampler2D uDepthMap;
                varying vec2 vUv;
                void main() {
                    vUv = uv;
                    vec3 pos = position;
                    float depth = texture2D(uDepthMap, uv).r;
                    pos.z += (depth - 0.5) * uDisplacement;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
                }
            `,
            fragmentShader: `
                uniform sampler2D uTexture;
                uniform float uBrightness;
                varying vec2 vUv;
                void main() {
                    vec4 color = texture2D(uTexture, vUv);
                    gl_FragColor = vec4(color.rgb * uBrightness, color.a);
                }
            `,
            side: THREE.DoubleSide,
        });

        this.mesh = new THREE.Mesh(geo, material);
        this.scene.add(this.mesh);
    }

    _initInteraction() {
        const canvas = this.renderer.domElement;

        const onDown = (e) => {
            this._isDragging = true;
            const p = e.touches ? e.touches[0] : e;
            this._prevMouse = { x: p.clientX, y: p.clientY };
            this._lastInteraction = Date.now();
            this._autoRotating = false;
            clearTimeout(this._autoTimer);
        };

        const onMove = (e) => {
            if (!this._isDragging) return;
            const p = e.touches ? e.touches[0] : e;
            const dx = p.clientX - this._prevMouse.x;
            const dy = p.clientY - this._prevMouse.y;
            if (this.mesh) {
                this.mesh.rotation.y += dx * 0.01;
                this.mesh.rotation.x += dy * 0.005;
                this.mesh.rotation.x = Math.max(-0.5, Math.min(0.5, this.mesh.rotation.x));
            }
            this._prevMouse = { x: p.clientX, y: p.clientY };
            this._rotationVelocity = { x: dx * 0.01, y: dy * 0.005 };
        };

        const onUp = () => {
            this._isDragging = false;
            this._autoTimer = setTimeout(() => { this._autoRotating = true; }, this.autoRotateIdleDelay);
        };

        canvas.addEventListener('mousedown', onDown);
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);
        canvas.addEventListener('touchstart', onDown, { passive: true });
        window.addEventListener('touchmove', onMove, { passive: true });
        window.addEventListener('touchend', onUp);

        canvas.addEventListener('wheel', (e) => {
            this._lastInteraction = Date.now();
            this._autoRotating = false;
            clearTimeout(this._autoTimer);
            if (this.camera) {
                this.camera.position.z += e.deltaY * 0.005;
                this.camera.position.z = Math.max(1.5, Math.min(8, this.camera.position.z));
            }
            this._autoTimer = setTimeout(() => { this._autoRotating = true; }, this.autoRotateIdleDelay);
        }, { passive: true });
    }

    _initResizeObserver() {
        if (this._ro) this._ro.disconnect();
        this._ro = new ResizeObserver(() => this._onResize());
        this._ro.observe(this.container);
    }

    _onResize() {
        if (this._disposed) return;
        const w = this.container.clientWidth;
        const h = this.container.clientHeight || (w * 9 / 16);
        if (w > 0 && h > 0) {
            this.camera.aspect = w / h;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(w, h);
        }
    }

    _animate() {
        if (this._disposed) return;
        requestAnimationFrame(() => this._animate());

        if (this.videoTexture) this.videoTexture.needsUpdate = true;
        if (this.depthTexture) this.depthTexture.needsUpdate = true;

        if (this._autoRotating && this.mesh) {
            this.mesh.rotation.y += this.autoRotateSpeed;
        }

        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }

    dispose() {
        this._disposed = true;
        clearTimeout(this._autoTimer);
        if (this._ro) this._ro.disconnect();
        if (this.video) { this.video.pause(); this.video.src = ''; }
        if (this.depthVideo) { this.depthVideo.pause(); this.depthVideo.src = ''; }
        if (this.renderer) {
            this.renderer.dispose();
            const el = this.renderer.domElement;
            if (el && el.parentNode) el.parentNode.removeChild(el);
        }
        if (this.mesh) {
            this.mesh.geometry.dispose();
            this.mesh.material.dispose();
        }
    }
}