/**
 * KiccScroll3D — Kasane-style scroll-driven 3D model viewer.
 *
 * Loads a Draco-compressed GLTF model from the media CDN and binds
 * scroll progress (0→1) to camera orbit keyframes via GSAP + ScrollTrigger.
 * Supports explode meshes (named sub-objects that translate on scroll)
 * and mouse-drag 360° spin during the interactive section.
 *
 * Usage:
 *   <x-scroll-model model-url="kicc/models/exhibit.glb" section-id="exhibit-3d" />
 *
 * Dependencies: THREE r170 (import map), GSAP/ScrollTrigger (global), GLTFLoader.
 */

import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js';

const STAGES = [
  { label: 'hero',     camera: { x: 0, y: 1.2, z: 4.5 }, rotation: { y: 0.3, x: 0.15 }, explode: 0,    progress: 0.0 },
  { label: 'overview', camera: { x: 2.0, y: 0.6, z: 3.0 }, rotation: { y: 1.2, x: 0.1  }, explode: 0.3,  progress: 0.35 },
  { label: 'detail',   camera: { x: -1.2, y: 0.3, z: 1.8}, rotation: { y: 2.4, x: -0.15}, explode: 0.7,  progress: 0.65 },
  { label: 'exploded', camera: { x: 0.6, y: 0.8, z: 2.5}, rotation: { y: 3.4, x: 0.05 }, explode: 1.0,  progress: 1.0 },
];

export class KiccScroll3D {
  constructor(opts) {
    this.modelUrl = opts.modelUrl || 'https://media.kicctest.org/models/exhibit_asset.glb';
    this.sectionId = opts.sectionId || 'scroll-3d-section';
    this.container = document.getElementById(opts.containerId || 'scroll-3d-canvas');
    this._disposed = false;
    this._isDragging = false;
    this._prevMouse = { x: 0, y: 0 };
    this._dragOffset = { y: 0, x: 0 };
    this._loadedModel = null;
    this._explodeParts = [];
    this._scrollTimeline = null;
    this._dpr = Math.min(window.devicePixelRatio, 2);

    if (!this.container) {
      console.warn('[KiccScroll3D] container not found:', opts.containerId);
      return;
    }

    this._init();
  }

  _init() {
    const W = this.container.clientWidth || window.innerWidth;
    const H = this.container.clientHeight || window.innerHeight;

    this.scene = new THREE.Scene();

    this.camera = new THREE.PerspectiveCamera(42, W / H, 0.1, 50);
    this.camera.position.copy(
      new THREE.Vector3(STAGES[0].camera.x, STAGES[0].camera.y, STAGES[0].camera.z)
    );

    this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    this.renderer.setSize(W, H);
    this.renderer.setPixelRatio(this._dpr);
    this.renderer.setClearColor(0x000000, 0);
    this.renderer.outputColorSpace = THREE.SRGBColorSpace;
    this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
    this.renderer.toneMappingExposure = 1.1;
    this.container.appendChild(this.renderer.domElement);

    // Lighting
    this.scene.add(new THREE.AmbientLight(0xffffff, 0.55));
    const key = new THREE.DirectionalLight(0xffffff, 2.2);
    key.position.set(4, 5, 4);
    this.scene.add(key);
    const rim = new THREE.DirectionalLight(0x4488cc, 1.0);
    rim.position.set(-3, 1, -2);
    this.scene.add(rim);

    // Load model
    this._loadModel();

    // Resize
    this._onResize = () => {
      const w = this.container.clientWidth || window.innerWidth;
      const h = this.container.clientHeight || window.innerHeight;
      this.camera.aspect = w / h;
      this.camera.updateProjectionMatrix();
      this.renderer.setSize(w, h);
    };
    window.addEventListener('resize', this._onResize);

    // Drag interaction for orbit override
    this.container.addEventListener('pointerdown', this._onPointerDown.bind(this));
    window.addEventListener('pointerup', this._onPointerUp.bind(this));
    window.addEventListener('pointermove', this._onPointerMove.bind(this));

    this._animId = requestAnimationFrame(() => this._animate());
  }

  _loadModel() {
    const dracoLoader = new DRACOLoader();
    dracoLoader.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.6/');

    const gltfLoader = new GLTFLoader();
    gltfLoader.setDRACOLoader(dracoLoader);

    gltfLoader.load(
      this.modelUrl,
      (gltf) => {
        this._loadedModel = gltf.scene;
        this.scene.add(this._loadedModel);

        // Find explode-able sub-meshes by name
        gltf.scene.traverse((child) => {
          if (child.isMesh && child.name && (
            child.name.includes('Detachable') ||
            child.name.includes('Component') ||
            child.name.includes('Canopy') ||
            child.name.includes('Interior')
          )) {
            this._explodeParts.push({
              mesh: child,
              originalPos: child.position.clone(),
            });
          }
        });

        this._setupScrollTimeline();
      },
      undefined,
      (err) => console.warn('[KiccScroll3D] model load failed:', err)
    );
  }

  _setupScrollTimeline() {
    if (!window.gsap || !window.ScrollTrigger) {
      console.warn('[KiccScroll3D] GSAP/ScrollTrigger not loaded');
      return;
    }

    // Build camera+rotation+explode timeline from STAGES
    const cam = { x: STAGES[0].camera.x, y: STAGES[0].camera.y, z: STAGES[0].camera.z };
    const rot = { x: STAGES[0].rotation.x, y: STAGES[0].rotation.y };
    const explode = { value: 0 };

    const cview = { ...STAGES[0].camera };
    const rview = { ...STAGES[0].rotation };

    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: `#${this.sectionId}`,
        start: 'top top',
        end: 'bottom bottom',
        scrub: 1.0,
        invalidateOnRefresh: true,
      },
    });

    for (let i = 1; i < STAGES.length; i++) {
      const s = STAGES[i];
      const prev = STAGES[i - 1];
      const segLen = s.progress - prev.progress;

      tl.to(cam, {
        x: s.camera.x,
        y: s.camera.y,
        z: s.camera.z,
        duration: segLen * 4,
        ease: 'power1.inOut',
      }, prev.progress * 4)
      .to(rot, {
        x: s.rotation.x,
        y: s.rotation.y,
        duration: segLen * 4,
        ease: 'power1.inOut',
      }, prev.progress * 4)
      .to(explode, {
        value: s.explode,
        duration: segLen * 4,
        ease: 'power2.out',
        onUpdate: () => {
          if (this._isDragging) return;
          this.camera.position.set(
            cam.x + Math.sin(this._dragOffset.y) * 0.6,
            cam.y + this._dragOffset.x * 0.4,
            cam.z
          );
          if (this._loadedModel) {
            this._loadedModel.rotation.set(
              rot.x + this._dragOffset.x * 0.3,
              rot.y + this._dragOffset.y * 0.5,
              0
            );
          }
        },
      }, prev.progress * 4);
    }

    this._scrollTimeline = tl;
    // Set up a tick to update explode parts independently
    gsap.ticker.add(() => {
      if (this._isDragging || !this._loadedModel) return;
      this._applyExplode(explode.value);
    });
  }

  _applyExplode(val) {
    for (const part of this._explodeParts) {
      part.mesh.position.set(
        part.originalPos.x + val * 1.8,
        part.originalPos.y + val * 0.5,
        part.originalPos.z + val * 0.9
      );
    }
  }

  _onPointerDown(e) {
    if (!this._loadedModel) return;
    this._isDragging = true;
    this._prevMouse = { x: e.clientX, y: e.clientY };
    this.container.style.cursor = 'grabbing';
  }

  _onPointerUp() {
    if (!this._isDragging) return;
    this._isDragging = false;
    this.container.style.cursor = '';
    // Smooth return to scroll-driven position
    gsap.to(this._dragOffset, { x: 0, y: 0, duration: 0.8, ease: 'power2.out' });
  }

  _onPointerMove(e) {
    if (!this._isDragging) return;
    this._dragOffset.x += (e.clientX - this._prevMouse.x) * 0.01;
    this._dragOffset.y += (e.clientY - this._prevMouse.y) * 0.01;
    this._dragOffset.x = Math.max(-1.5, Math.min(1.5, this._dragOffset.x));
    this._dragOffset.y = Math.max(-3.0, Math.min(3.0, this._dragOffset.y));
    this._prevMouse = { x: e.clientX, y: e.clientY };
  }

  _animate() {
    if (this._disposed) return;
    this._animId = requestAnimationFrame(() => this._animate());
    if (!this._loadedModel || this._isDragging) return;

    // Idle: when scroll is inactive, slowly auto-rotate if no timeline active
    if (!this._scrollTimeline || !this._scrollTimeline.scrollTrigger) {
      if (this._loadedModel) this._loadedModel.rotation.y += 0.003;
    }
  }

  dispose() {
    this._disposed = true;
    if (this._animId) cancelAnimationFrame(this._animId);
    if (this._scrollTimeline) this._scrollTimeline.kill();
    window.removeEventListener('resize', this._onResize);
    window.removeEventListener('pointerup', this._onPointerUp);
    window.removeEventListener('pointermove', this._onPointerMove);
    if (this._loadedModel) this.scene.remove(this._loadedModel);
    this.scene.clear();
    this.renderer.dispose();
    if (this.renderer.domElement.parentNode) {
      this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
    }
  }
}

// Auto-init from data attributes
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', () => {
    const entries = document.querySelectorAll('[data-scroll-model]');
    entries.forEach((el) => {
      try {
        new KiccScroll3D({
          modelUrl: el.dataset.scrollModel,
          sectionId: el.dataset.scrollSection || 'scroll-3d-section',
          containerId: el.dataset.scrollContainer || el.id || undefined,
        });
      } catch (e) {
        console.warn('[KiccScroll3D] init failed for', el, e);
      }
    });
  });
}