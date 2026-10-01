/**
 * KiccShaderBackdrop — RFEQ-style generative vertex-shader ribbon meshes.
 *
 * Creates a fixed WebGL canvas (z-index: -1) that renders parametric
 * ribbons/helix geometries deformed via custom GLSL vertex shaders.
 * Scroll position (via KICC_MOTION.scrollPct + requestAnimationFrame time)
 * drives displacement uniforms in real time.
 *
 * Usage (Blade):
 *   <x-shader-backdrop variant="ribbon" />
 *
 * Variants: ribbon | helix | wireframe | minimal
 *
 * Dependencies: THREE r170 (import map), EffectComposer, UnrealBloomPass
 */

import * as THREE from 'three';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';

const SHADER_VARIANTS = {
  ribbon: {
    count: 4,
    radius: 2.4,
    tubeRadius: 0.04,
    tubularSegments: 180,
    radialSegments: 8,
    bloomStrength: 0.6,
    bloomRadius: 0.35,
    bloomThreshold: 0.8,
    colorShift: true,
  },
  helix: {
    count: 3,
    radius: 3.0,
    tubeRadius: 0.03,
    tubularSegments: 240,
    radialSegments: 6,
    bloomStrength: 0.5,
    bloomRadius: 0.3,
    bloomThreshold: 0.85,
    colorShift: false,
  },
  wireframe: {
    count: 6,
    radius: 2.0,
    tubeRadius: 0.02,
    tubularSegments: 120,
    radialSegments: 4,
    bloomStrength: 0.8,
    bloomRadius: 0.25,
    bloomThreshold: 0.9,
    colorShift: true,
  },
  minimal: {
    count: 2,
    radius: 2.8,
    tubeRadius: 0.05,
    tubularSegments: 100,
    radialSegments: 6,
    bloomStrength: 0.4,
    bloomRadius: 0.4,
    bloomThreshold: 0.75,
    colorShift: false,
  },
};

const VERTEX_SHADER = /* glsl */ `
uniform float uTime;
uniform float uScroll;
uniform float uIndex;
uniform float uCount;
varying vec3 vWorldPos;
varying vec2 vUv;
varying float vDisplacement;

// 2D simplex-like noise (hsv2rgb-style periodic)
float hash(float n) { return fract(sin(n) * 43758.5453123); }
float noise(vec3 x) {
    vec3 p = floor(x), f = fract(x);
    f = f * f * (3.0 - 2.0 * f);
    float n = p.x + p.y * 57.0 + 113.0 * p.z;
    return mix(mix(mix(hash(n + 0.0), hash(n + 1.0), f.x),
                   mix(hash(n + 57.0), hash(n + 58.0), f.x), f.y),
               mix(mix(hash(n + 113.0), hash(n + 114.0), f.x),
                   mix(hash(n + 170.0), hash(n + 171.0), f.x), f.y), f.z);
}

void main() {
    vec3 pos = position;
    float t = uTime * 0.6 + uIndex * 2.5;

    // Helical displacement along Y
    float angle = pos.y * 3.5 + t;
    float wave = sin(pos.y * 4.0 + t * 0.7) * cos(angle) * 0.35;

    // Scroll-driven amplitude
    float scrollAmp = 0.15 + uScroll * 0.85;

    // Per-vertex noise field
    float n = noise(vec3(pos * 1.8, t * 0.4)) * 0.5
            + noise(vec3(pos * 3.5, t * 0.8)) * 0.3;

    float displacement = (wave + n) * scrollAmp;
    pos += normal * displacement;

    vDisplacement = displacement;
    vWorldPos = (modelMatrix * vec4(pos, 1.0)).xyz;
    vUv = uv;
    gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
}
`;

const FRAGMENT_SHADER = /* glsl */ `
uniform float uTime;
uniform float uScroll;
uniform float uIndex;
uniform float uCount;
uniform vec3 uColorA;
uniform vec3 uColorB;
uniform bool uColorShift;
varying vec3 vWorldPos;
varying vec2 vUv;
varying float vDisplacement;

void main() {
    // Scroll-mapped color mixing
    float mixFactor = (vDisplacement * 1.5 + uScroll * 0.6 + uIndex / uCount) * 0.65 + 0.35;
    vec3 color = mix(uColorA, uColorB, clamp(mixFactor, 0.0, 1.0));

    // Fresnel-like edge glow
    float fresnel = pow(1.0 - abs(vUv.x * 2.0 - 1.0), 2.5) * 0.4
                  + pow(1.0 - abs(vUv.y * 2.0 - 1.0), 2.5) * 0.3;
    color += vec3(fresnel * 0.5);

    // Scroll pulse
    float pulse = sin(uScroll * 6.28318) * 0.08 + 0.08;
    color += vec3(pulse * 0.3, pulse * 0.15, pulse * 0.5);

    float alpha = 0.55 + fresnel * 1.2;
    gl_FragColor = vec4(color, clamp(alpha, 0.0, 1.0));
}
`;

const PALETTES = [
  { a: [0.05, 0.29, 0.73], b: [0.98, 0.04, 0.70] },   // blue → magenta
  { a: [0.06, 0.22, 0.34], b: [1.00, 0.80, 0.02] },    // navy → gold (KICC)
  { a: [0.56, 0.06, 0.12], b: [0.05, 0.29, 0.73] },    // crimson → blue
  { a: [0.02, 0.51, 0.41], b: [0.98, 0.80, 0.02] },    // green → gold
  { a: [0.10, 0.06, 0.22], b: [0.05, 0.75, 0.91] },    // deep navy → cyan
];

export class KiccShaderBackdrop {
  constructor(opts = {}) {
    this.container = opts.container || null;
    this.variant = opts.variant || 'ribbon';
    this.paletteIndex = opts.palette ?? Math.floor(Math.random() * PALETTES.length);
    this.dpr = Math.min(window.devicePixelRatio, 2);
    this._disposed = false;
    this._visible = true;
    this._animId = null;

    this.config = { ...SHADER_VARIANTS[this.variant] || SHADER_VARIANTS.ribbon };
    this.palette = PALETTES[this.paletteIndex % PALETTES.length];

    this._init();
  }

  _init() {
    const W = window.innerWidth;
    const H = window.innerHeight;

    this.scene = new THREE.Scene();

    this.camera = new THREE.PerspectiveCamera(50, W / H, 0.1, 50);
    this.camera.position.set(0, 0, 5.5);

    this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    this.renderer.setSize(W, H);
    this.renderer.setPixelRatio(this.dpr);
    this.renderer.setClearColor(0x000000, 0);
    this.renderer.domElement.classList.add('kicc-canvas-3d');
    this.renderer.domElement.style.zIndex = '-1';

    if (this.container) {
      this.container.appendChild(this.renderer.domElement);
    } else {
      document.body.prepend(this.renderer.domElement);
    }

    // Post-processing
    this.composer = new EffectComposer(this.renderer);
    this.composer.addPass(new RenderPass(this.scene, this.camera));
    this.bloomPass = new UnrealBloomPass(
      new THREE.Vector2(W, H),
      this.config.bloomStrength,
      this.config.bloomRadius,
      this.config.bloomThreshold
    );
    this.composer.addPass(this.bloomPass);

    // Fog
    this.scene.fog = new THREE.FogExp2(0x0a0a14, 0.015);

    // Ribbon meshes
    this.ribbons = [];
    const shaderMat = new THREE.ShaderMaterial({
      vertexShader: VERTEX_SHADER,
      fragmentShader: FRAGMENT_SHADER,
      uniforms: {
        uTime: { value: 0 },
        uScroll: { value: 0 },
        uIndex: { value: 0 },
        uCount: { value: this.config.count },
        uColorA: { value: new THREE.Color(...this.palette.a) },
        uColorB: { value: new THREE.Color(...this.palette.b) },
        uColorShift: { value: this.config.colorShift },
      },
      transparent: true,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
    });

    for (let i = 0; i < this.config.count; i++) {
      const mat = shaderMat.clone();
      mat.uniforms.uIndex.value = i;
      mat.uniforms.uCount.value = this.config.count;

      const geom = new THREE.TorusKnotGeometry(
        this.config.radius,
        this.config.tubeRadius,
        this.config.tubularSegments,
        this.config.radialSegments
      );

      const mesh = new THREE.Mesh(geom, mat);
      mesh.rotation.set(
        (i / this.config.count) * Math.PI * 0.8,
        (i / this.config.count) * Math.PI * 1.2,
        i * 0.3
      );
      mesh.position.z = -1.5 + i * 0.4;

      this.scene.add(mesh);
      this.ribbons.push({ mesh, mat });
    }

    // Ambient point lights for subtle highlights
    this.lights = [];
    for (let i = 0; i < 2; i++) {
      const l = new THREE.PointLight(0x4488cc, 1.5, 8);
      l.position.set(Math.cos(i * Math.PI) * 3, 0, 1);
      this.scene.add(l);
      this.lights.push(l);
    }

    // Resize
    this._onResize = () => {
      const w = window.innerWidth;
      const h = window.innerHeight;
      this.camera.aspect = w / h;
      this.camera.updateProjectionMatrix();
      this.renderer.setSize(w, h);
      this.composer.setSize(w, h);
    };
    window.addEventListener('resize', this._onResize, { passive: true });

    // Visibility observer
    this._observer = new IntersectionObserver(
      ([entry]) => {
        this._visible = entry.isIntersecting;
      },
      { threshold: 0.05 }
    );
    this._observer.observe(this.renderer.domElement);

    // Render loop
    this._startTime = performance.now();
    this._animate();
  }

  _animate() {
    if (this._disposed) return;
    this._animId = requestAnimationFrame(() => this._animate());
    if (!this._visible) return;

    const elapsed = (performance.now() - this._startTime) * 0.001;
    const scrollPct = (window.KICC_MOTION && window.KICC_MOTION.scrollPct != null)
      ? window.KICC_MOTION.scrollPct
      : 0;

    for (const { mesh, mat } of this.ribbons) {
      mat.uniforms.uTime.value = elapsed;
      mat.uniforms.uScroll.value = scrollPct;
      mesh.rotation.y += 0.0012;
      mesh.rotation.x += 0.0004;
    }

    // Drift lights
    for (let i = 0; i < this.lights.length; i++) {
      this.lights[i].position.y = Math.sin(elapsed * 0.4 + i * 1.5) * 2.5;
      this.lights[i].position.x = Math.cos(elapsed * 0.35 + i) * 3.5;
    }

    this.camera.position.x = Math.sin(elapsed * 0.15) * 0.6;
    this.camera.position.y = Math.cos(elapsed * 0.2) * 0.3;

    this.composer.render();
  }

  dispose() {
    this._disposed = true;
    if (this._animId) cancelAnimationFrame(this._animId);
    window.removeEventListener('resize', this._onResize);
    if (this._observer) this._observer.disconnect();
    for (const { mesh, mat } of this.ribbons) {
      mesh.geometry.dispose();
      mat.dispose();
    }
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
    const el = document.querySelector('[data-shader-backdrop]');
    if (el) {
      const variant = el.dataset.shaderBackdrop || 'ribbon';
      const palette = el.dataset.shaderPalette != null ? parseInt(el.dataset.shaderPalette) : undefined;
      window._kiccShader = new KiccShaderBackdrop({ container: el, variant, palette });
    }
  });
}