/**
 * KiccProductViewer — GLTF/GLB 3D product viewer with scroll-driven rotation.
 * Loads on the product detail page when a 3D tab is clicked.
 */
class KiccProductViewer {
    constructor(containerId, modelUrl, posterUrl) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;
        this._disposed = false;

        this._initScene();
        this._initLights();
        this._loadModel(modelUrl, posterUrl);
        this._initControls();
        this._initResizeObserver();
        this._animate();
    }

    _initScene() {
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, this.container.clientWidth / this.container.clientHeight, 0.1, 100);
        this.camera.position.set(0, 0.5, 4);

        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance',
        });
        this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.2;
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
        this.container.appendChild(this.renderer.domElement);
    }

    _initLights() {
        const ambient = new THREE.AmbientLight(0xffffff, 0.8);
        this.scene.add(ambient);

        const key = new THREE.DirectionalLight(0xffffff, 2.0);
        key.position.set(4, 5, 4);
        this.scene.add(key);

        const rim = new THREE.DirectionalLight(0x6366f1, 1.5);
        rim.position.set(-4, -2, -3);
        this.scene.add(rim);

        const fill = new THREE.DirectionalLight(0xffcd05, 0.4);
        fill.position.set(0, -3, 2);
        this.scene.add(fill);
    }

    _loadModel(url, posterUrl) {
        if (!url || !window.THREE) {
            this._showFallback(posterUrl);
            return;
        }

        const loader = new THREE.GLTFLoader();
        const draco = new THREE.DRACOLoader();
        draco.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.7/');
        loader.setDRACOLoader(draco);

        loader.load(
            url,
            (gltf) => {
                this.model = gltf.scene;
                const box = new THREE.Box3().setFromObject(this.model);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());
                const maxDim = Math.max(size.x, size.y, size.z);
                const scale = 2.5 / maxDim;
                this.model.scale.setScalar(scale);
                this.model.position.sub(center.multiplyScalar(scale));
                this.model.traverse((child) => {
                    if (child.isMesh) {
                        child.castShadow = true;
                        child.receiveShadow = true;
                    }
                });
                this.scene.add(this.model);
            },
            undefined,
            (err) => {
                console.warn('GLTF load failed, showing fallback:', err);
                this._showFallback(posterUrl);
            }
        );
    }

    _showFallback(posterUrl) {
        // Fallback: a rotating torus knot with video or image texture
        const geo = new THREE.TorusKnotGeometry(0.8, 0.3, 128, 16);
        const mat = new THREE.MeshStandardMaterial({
            color: 0x6366f1,
            metalness: 0.3,
            roughness: 0.7,
            wireframe: false,
        });
        this.model = new THREE.Mesh(geo, mat);
        this.model.position.y = 0.2;
        this.scene.add(this.model);
    }

    _initControls() {
        this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.08;
        this.controls.minDistance = 1.5;
        this.controls.maxDistance = 8;
        this.controls.autoRotate = true;
        this.controls.autoRotateSpeed = 2.0;
        this.controls.target.set(0, 0.2, 0);

        // Scroll-driven rotation
        this._onScroll = () => {
            const scrollY = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const progress = Math.min(scrollY / Math.max(maxScroll, 1), 1);
            if (this.model) {
                this.model.rotation.y = progress * Math.PI * 2;
            }
        };
        window.addEventListener('scroll', this._onScroll, { passive: true });
    }

    _initResizeObserver() {
        this._resizeObserver = new ResizeObserver(() => {
            const w = this.container.clientWidth;
            const h = this.container.clientHeight;
            this.camera.aspect = w / h;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(w, h);
        });
        this._resizeObserver.observe(this.container);
    }

    _animate() {
        if (this._disposed) return;
        requestAnimationFrame(() => this._animate());
        if (this.model && !this.controls.autoRotate) {
            // Scroll-driven rotation handled in _onScroll
        }
        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }

    dispose() {
        this._disposed = true;
        window.removeEventListener('scroll', this._onScroll);
        if (this._resizeObserver) this._resizeObserver.disconnect();
        if (this.controls) this.controls.dispose();
        if (this.renderer) {
            this.renderer.dispose();
            this.renderer.domElement.remove();
        }
    }
}