/**
 * Kicc3DVideoPlayer — enhances a video with 3D parallax via THREE.js.
 * Falls back silently to flat video if THREE is unavailable or init fails.
 *
 * Usage:
 *   try { new Kicc3DVideoPlayer({ container, videoUrl, depthMapUrl, mode }); } catch(e) {}
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
        this._isDragging = false;
        this._prevMouse = { x: 0, y: 0 };
        this._lastInteraction = Date.now();
        this._autoRotating = true;
        this._fallbackActive = false;

        if (!this.container || !this.videoUrl || typeof THREE === 'undefined') return;
        try { this._init(); } catch (e) { console.warn('3D init error, using flat fallback:', e); }
    }

    _init() {
        const w = this.container.clientWidth || 640;
        const h = this.container.clientHeight || (w * 9 / 16);
        if (w < 1 || h < 1) return;

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, w / h, 0.1, 100);
        this.camera.position.set(0, 0, 3.2);

        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setSize(w, h);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.container.appendChild(this.renderer.domElement);

        this.scene.add(new THREE.AmbientLight(0xffffff, 0.7));
        const key = new THREE.DirectionalLight(0xffffff, 1.5);
        key.position.set(3, 4, 3);
        this.scene.add(key);
        const rim = new THREE.DirectionalLight(0x6366f1, 0.8);
        rim.position.set(-3, -1, -2);
        this.scene.add(rim);

        this.video = document.createElement('video');
        this.video.src = this.videoUrl;
        this.video.muted = true;
        this.video.loop = true;
        this.video.playsinline = true;
        this.video.crossOrigin = 'anonymous';
        this.video.play().catch(() => {});

        this.videoTexture = new THREE.VideoTexture(this.video);
        this.videoTexture.minFilter = THREE.LinearFilter;
        this.videoTexture.magFilter = THREE.LinearFilter;
        this.videoTexture.colorSpace = THREE.SRGBColorSpace;

        let bumpTexture = this.videoTexture;
        if (this.depthMapUrl) {
            this.depthVideo = document.createElement('video');
            this.depthVideo.src = this.depthMapUrl;
            this.depthVideo.muted = true;
            this.depthVideo.loop = true;
            this.depthVideo.playsinline = true;
            this.depthVideo.play().catch(() => {});
            this.depthTexture = new THREE.VideoTexture(this.depthVideo);
            this.depthTexture.minFilter = THREE.LinearFilter;
            this.depthTexture.magFilter = THREE.LinearFilter;
            bumpTexture = this.depthTexture;
        }

        const geo = new THREE.PlaneGeometry(2.4, 1.35, 32, 32);
        const mat = new THREE.MeshStandardMaterial({
            map: this.videoTexture,
            bumpMap: bumpTexture,
            bumpScale: 0.05,
            side: THREE.DoubleSide,
        });
        this.mesh = new THREE.Mesh(geo, mat);
        this.scene.add(this.mesh);

        // Interaction
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
            if (!this._isDragging || !this.mesh) return;
            const p = e.touches ? e.touches[0] : e;
            const dx = p.clientX - this._prevMouse.x;
            const dy = p.clientY - this._prevMouse.y;
            this.mesh.rotation.y += dx * 0.01;
            this.mesh.rotation.x += dy * 0.005;
            this.mesh.rotation.x = Math.max(-0.5, Math.min(0.5, this.mesh.rotation.x));
            this._prevMouse = { x: p.clientX, y: p.clientY };
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
            this.camera.position.z = Math.max(1.5, Math.min(8, this.camera.position.z + e.deltaY * 0.005));
            this._autoTimer = setTimeout(() => { this._autoRotating = true; }, this.autoRotateIdleDelay);
        }, { passive: true });

        // Resize
        this._ro = new ResizeObserver(() => {
            if (this._disposed) return;
            const w2 = this.container.clientWidth, h2 = this.container.clientHeight || (w2 * 9/16);
            if (w2 > 0 && h2 > 0) { this.camera.aspect = w2 / h2; this.camera.updateProjectionMatrix(); this.renderer.setSize(w2, h2); }
        });
        this._ro.observe(this.container);

        this._animate();
    }

    _animate() {
        if (this._disposed) return;
        requestAnimationFrame(() => this._animate());
        if (this.videoTexture) this.videoTexture.needsUpdate = true;
        if (this.depthTexture) this.depthTexture.needsUpdate = true;
        if (this._autoRotating && this.mesh) this.mesh.rotation.y += this.autoRotateSpeed;
        if (this.renderer && this.scene && this.camera) this.renderer.render(this.scene, this.camera);
    }

    dispose() {
        this._disposed = true;
        clearTimeout(this._autoTimer);
        if (this._ro) this._ro.disconnect();
        if (this.video) { this.video.pause(); this.video.src = ''; }
        if (this.depthVideo) { this.depthVideo.pause(); this.depthVideo.src = ''; }
        if (this.renderer) { this.renderer.dispose(); const el = this.renderer.domElement; if (el && el.parentNode) el.parentNode.removeChild(el); }
        if (this.mesh) { this.mesh.geometry.dispose(); this.mesh.material.dispose(); }
    }
}