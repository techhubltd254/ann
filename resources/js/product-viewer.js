/**
 * KiccProductViewer — GLTF/GLB 3D product viewer.
 * Simple pointer-drag rotation, no OrbitControls dependency.
 */
class KiccProductViewer {
    constructor(containerId, modelUrl, posterUrl) {
        this.container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        if (!this.container) return;
        this.modelUrl = modelUrl;
        this._disposed = false;
        this._isDragging = false;
        this._prevMouse = { x: 0, y: 0 };

        if (typeof THREE === 'undefined') {
            console.warn('THREE not available for product viewer');
            return;
        }

        try {
            this._initScene();
            this._initLights();
            this._loadModel();
            this._initInteraction();
            this._initResizeObserver();
            this._animate();
        } catch (e) {
            console.warn('Product viewer init failed:', e);
        }
    }

    _initScene() {
        const w = this.container.clientWidth || 640;
        const h = this.container.clientHeight || 480;
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, w / h, 0.1, 100);
        this.camera.position.set(0, 0.5, 4);
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setSize(w, h);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping || THREE.LinearToneMapping;
        this.renderer.toneMappingExposure = 1.2;
        this.container.appendChild(this.renderer.domElement);
    }

    _initLights() {
        this.scene.add(new THREE.AmbientLight(0xffffff, 0.8));
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

    _loadModel() {
        // Fallback: rotating torus knot (works without any model file)
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

        // If model URL provided, try to load GLTF
        if (this.modelUrl && window.GLTFLoader) {
            const loader = new window.GLTFLoader();
            loader.load(this.modelUrl, (gltf) => {
                this.scene.remove(this.model);
                this.model = gltf.scene;
                const box = new THREE.Box3().setFromObject(this.model);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());
                const maxDim = Math.max(size.x, size.y, size.z);
                const scale = 2.5 / maxDim;
                this.model.scale.setScalar(scale);
                this.model.position.sub(center.multiplyScalar(scale));
                this.scene.add(this.model);
            }, undefined, () => {
                // Keep fallback on error
            });
        }
    }

    _initInteraction() {
        const canvas = this.renderer.domElement;
        const onDown = (e) => {
            this._isDragging = true;
            const p = e.touches ? e.touches[0] : e;
            this._prevMouse = { x: p.clientX, y: p.clientY };
        };
        const onMove = (e) => {
            if (!this._isDragging || !this.model) return;
            const p = e.touches ? e.touches[0] : e;
            const dx = p.clientX - this._prevMouse.x;
            const dy = p.clientY - this._prevMouse.y;
            this.model.rotation.y += dx * 0.01;
            this.model.rotation.x += dy * 0.005;
            this.model.rotation.x = Math.max(-1, Math.min(1, this.model.rotation.x));
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
            const w = this.container.clientWidth;
            const h = this.container.clientHeight;
            if (w > 0 && h > 0) {
                this.camera.aspect = w / h;
                this.camera.updateProjectionMatrix();
                this.renderer.setSize(w, h);
            }
        });
        this._ro.observe(this.container);
    }

    _animate() {
        if (this._disposed) return;
        requestAnimationFrame(() => this._animate());
        if (this.model && !this._isDragging) {
            this.model.rotation.y += 0.008;
        }
        this.renderer.render(this.scene, this.camera);
    }

    dispose() {
        this._disposed = true;
        if (this._ro) this._ro.disconnect();
        if (this.renderer) {
            this.renderer.dispose();
            const el = this.renderer.domElement;
            if (el && el.parentNode) el.parentNode.removeChild(el);
        }
    }
}