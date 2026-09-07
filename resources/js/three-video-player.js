/**
 * Kicc3DVideoPlayer — renders any video as a 3D parallax scene using Three.js.
 *
 * Features:
 *   - Video mapped as texture on subdivided plane
 *   - Depth map drives vertex displacement (real 3D parallax)
 *   - Auto-rotation when idle (0.5 RPM)
 *   - OrbitControls for mouse/touch interaction
 *   - Falls back to flat video if no depth map
 *   - Always-on, always-playing, auto-loop
 *
 * Usage:
 *   const player = new Kicc3DVideoPlayer({
 *       container: document.getElementById('player'),
 *       videoUrl: 'https://.../video.mp4',
 *       depthMapUrl: 'https://.../depth.mp4',   // optional
 *       mode: 'parallax',
 *   });
 */
class Kicc3DVideoPlayer {
    constructor(options) {
        this.container = options.container;
        this.videoUrl = options.videoUrl;
        this.depthMapUrl = options.depthMapUrl || null;
        this.mode = options.mode || 'parallax';
        this.autoRotateSpeed = options.autoRotateSpeed || 0.008; // ~0.5 RPM
        this.autoRotateIdleDelay = options.autoRotateIdleDelay || 3000; // ms
        this._disposed = false;
        this._lastInteraction = Date.now();
        this._autoRotating = true;

        this._initScene();
        this._initLights();
        this._initVideoTexture();
        this._initGeometry();
        this._initOrbitControls();
        this._initResizeObserver();
        this._animate();

        // Click/touch toggles auto-rotation off temporarily
        this.container.addEventListener('pointerdown', () => this._onInteraction());
        this.container.addEventListener('wheel', () => this._onInteraction());
    }

    _onInteraction() {
        this._lastInteraction = Date.now();
        this._autoRotating = false;
        // Resume auto-rotation after idle delay
        clearTimeout(this._autoTimer);
        this._autoTimer = setTimeout(() => {
            this._autoRotating = true;
        }, this.autoRotateIdleDelay);
    }

    _initScene() {
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, this.container.clientWidth / this.container.clientHeight || 16/9, 0.1, 100);
        this.camera.position.set(0, 0, 3.5);

        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance',
        });
        this.renderer.setSize(this.container.clientWidth, this.container.clientHeight || this.container.clientWidth * 9/16);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.0;
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
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
        // Primary video
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

        // Depth map video (optional)
        this.depthVideo = null;
        this.depthTexture = null;
        if (this.depthMapUrl) {
            this.depthVideo = document.createElement('video');
            this.depthVideo.src = this.depthMapUrl;
            this.depthVideo.muted = true;
            this.depthVideo.loop = true;
            this.depthVideo.playsinline = true;
            this.depthVideo.crossOrigin = 'anonymous';
            this.depthVideo.setAttribute('playsinline', '');
            this.depthVideo.play().catch(() => {});

            this.depthTexture = new THREE.VideoTexture(this.depthVideo);
            this.depthTexture.minFilter = THREE.LinearFilter;
            this.depthTexture.magFilter = THREE.LinearFilter;
        }
    }

    _initGeometry() {
        const segments = 64;
        const geometry = new THREE.PlaneGeometry(2.4, 1.35, segments, segments);
        const position = geometry.attributes.position;
        this._vertexCount = position.count;
        this._originalPositions = new Float32Array(position.array);

        const material = new THREE.ShaderMaterial({
            uniforms: {
                uTexture: { value: this.videoTexture },
                uDepthMap: { value: this.depthTexture },
                uDisplacement: { value: 0.08 },
                uBrightness: { value: 1.0 },
            },
            vertexShader: `
                uniform float uDisplacement;
                uniform sampler2D uDepthMap;
                varying vec2 vUv;

                void main() {
                    vUv = uv;
                    vec3 pos = position;
                    if (uDisplacement > 0.001) {
                        float depth = texture2D(uDepthMap, uv).r;
                        float displacement = (depth - 0.5) * uDisplacement;
                        pos.z += displacement;
                    }
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

        this.mesh = new THREE.Mesh(geometry, material);
        this.scene.add(this.mesh);

        // Fallback: if no depth map, use a simpler approach with flat plane
        if (!this.depthMapUrl) {
            material.uniforms.uDisplacement.value = 0.0;
        }
    }

    _initOrbitControls() {
        this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.05;
        this.controls.autoRotate = false; // we handle it manually
        this.controls.minDistance = 1.5;
        this.controls.maxDistance = 8;
        this.controls.enablePan = false;
        this.controls.target.set(0, 0, 0);
    }

    _initResizeObserver() {
        if (this._resizeObserver) this._resizeObserver.disconnect();
        this._resizeObserver = new ResizeObserver(() => this._onResize());
        this._resizeObserver.observe(this.container);
    }

    _onResize() {
        if (this._disposed) return;
        const w = this.container.clientWidth;
        const h = this.container.clientHeight || w * 9/16;
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
    }

    _animate() {
        if (this._disposed) return;
        requestAnimationFrame(() => this._animate());

        // Update video textures each frame
        if (this.videoTexture) this.videoTexture.needsUpdate = true;
        if (this.depthTexture) this.depthTexture.needsUpdate = true;

        // Auto-rotation
        if (this._autoRotating && this.mesh) {
            this.mesh.rotation.y += this.autoRotateSpeed;
        }

        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }

    setVideo(videoUrl, depthMapUrl) {
        this.video.src = videoUrl;
        this.video.play().catch(() => {});
        if (depthMapUrl && this.depthVideo) {
            this.depthVideo.src = depthMapUrl;
            this.depthVideo.play().catch(() => {});
        }
    }

    dispose() {
        this._disposed = true;
        clearTimeout(this._autoTimer);
        if (this._resizeObserver) this._resizeObserver.disconnect();
        if (this.video) { this.video.pause(); this.video.src = ''; }
        if (this.depthVideo) { this.depthVideo.pause(); this.depthVideo.src = ''; }
        if (this.controls) this.controls.dispose();
        if (this.renderer) {
            this.renderer.dispose();
            if (this.renderer.domElement.parentNode) {
                this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
            }
        }
        if (this.mesh) {
            this.mesh.geometry.dispose();
            this.mesh.material.dispose();
        }
    }
}