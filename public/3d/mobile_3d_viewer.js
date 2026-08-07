/**
 * KICC Mobile 3D Viewer — shared mobile controls module.
 *
 * Adds phone-first interaction to any existing Three.js scene:
 *
 *   [Orbit]  classic drag/pinch OrbitControls (desktop + mobile default)
 *   [Gyro]   tilt phone to look around (DeviceOrientation) + joystick to move
 *   [VR]     split-screen stereo (Google Cardboard) + gyro + joystick
 *
 * Also provides quality auto-detection (RAM / CPU / network -> pixel ratio).
 *
 * Integration (3 lines):
 *   import { Mobile3DControls } from './mobile_3d_viewer.js';
 *   const mobile = new Mobile3DControls({ camera, renderer, controls });
 *   // in the animation loop:
 *   mobile.update();
 *   mobile.render(scene, camera);   // replaces renderer.render(scene, camera)
 *
 * Requires the host page to define the three.js importmap (three + three/addons).
 * No other dependencies — the virtual joystick is built in (works offline/PWA).
 */

import * as THREE from 'three';
import { StereoEffect } from 'three/addons/effects/StereoEffect.js';

/* ── Quality auto-detection ─────────────────────────────────────────────────
 * Returns 'low' | 'medium' | 'high' from device RAM, CPU cores and network.
 * Used to cap pixelRatio (and optionally splat density / video quality).
 */
export function detectQualityTier() {
  const mem = navigator.deviceMemory || 4;             // GB, Chrome/Android
  const cores = navigator.hardwareConcurrency || 4;
  const conn = navigator.connection || {};
  const slowNet = /2g/.test(conn.effectiveType || '');

  if (slowNet || mem <= 2 || cores <= 2) return 'low';
  if (mem <= 4 || cores <= 4) return 'medium';
  return 'high';
}

const TIER_PIXEL_RATIO = { low: 1, medium: 1.5, high: 2 };

export class Mobile3DControls {
  /**
   * @param {object} opts
   * @param {THREE.PerspectiveCamera} opts.camera
   * @param {THREE.WebGLRenderer}     opts.renderer
   * @param {OrbitControls}           opts.controls   existing OrbitControls
   * @param {string[]} [opts.modes]   subset of ['orbit','gyro','vr']
   * @param {number}   [opts.moveSpeed]  joystick movement speed (units/s)
   * @param {boolean}  [opts.autoQuality] cap pixelRatio by detected tier
   * @param {function} [opts.onModeChange] callback(mode)
   */
  constructor(opts) {
    this.camera = opts.camera;
    this.renderer = opts.renderer;
    this.controls = opts.controls;
    this.moveSpeed = opts.moveSpeed != null ? opts.moveSpeed : 2.5;
    this.modes = opts.modes || ['orbit', 'gyro', 'vr'];
    this.onModeChange = opts.onModeChange || null;

    this.mode = 'orbit';
    this._stereo = null;
    this._joy = { x: 0, y: 0, active: false, id: null };
    this._lastT = performance.now();

    // Gyroscope state (DeviceOrientationControls math)
    this._gyroQuat = new THREE.Quaternion();
    this._gyroReady = false;
    this._zee = new THREE.Vector3(0, 0, 1);
    this._euler = new THREE.Euler();
    this._q0 = new THREE.Quaternion();
    // -PI/2 around X: device "looks out" of its back camera side
    this._q1 = new THREE.Quaternion(-Math.sqrt(0.5), 0, 0, Math.sqrt(0.5));

    this._hasGyro = typeof window.DeviceOrientationEvent !== 'undefined';
    this._needsGyroPermission =
      this._hasGyro && typeof DeviceOrientationEvent.requestPermission === 'function';

    if (opts.autoQuality !== false) this.applyAutoQuality();

    this._buildUI();
    this._bindGyro();

    window.addEventListener('orientationchange', () => this._onOrientation());
    window.addEventListener('resize', () => this._onOrientation());
  }

  /* ── Quality ─────────────────────────────────────────────────────────── */

  applyAutoQuality() {
    this.tier = detectQualityTier();
    const cap = TIER_PIXEL_RATIO[this.tier] || 2;
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, cap));
    return this.tier;
  }

  /* ── Mode management ─────────────────────────────────────────────────── */

  setMode(mode) {
    if (this.modes.indexOf(mode) === -1) return;
    if (mode === 'gyro' || mode === 'vr') {
      if (!this._hasGyro) return this._toast('No gyroscope on this device');
      if (this._needsGyroPermission) {
        // iOS 13+ requires a user-gesture permission request
        DeviceOrientationEvent.requestPermission()
          .then((res) => {
            if (res === 'granted') this._activateMode(mode);
            else this._toast('Gyroscope permission denied');
          })
          .catch(() => this._toast('Gyroscope unavailable'));
        return;
      }
    }
    this._activateMode(mode);
  }

  _activateMode(mode) {
    this.mode = mode;

    const orbiting = mode === 'orbit';
    this.controls.enabled = orbiting;

    if (orbiting) {
      // Resume orbit from where the gyro left off: look 3 units ahead
      const fwd = new THREE.Vector3();
      this.camera.getWorldDirection(fwd);
      this.controls.target.copy(this.camera.position).addScaledVector(fwd, 3);
      this.controls.update();
    }

    if (mode === 'vr' && !this._stereo) {
      this._stereo = new StereoEffect(this.renderer);
      this._stereo.setSize(window.innerWidth, window.innerHeight);
    }

    this._syncUI();
    if (this.onModeChange) this.onModeChange(mode);
  }

  getMode() { return this.mode; }

  /* ── Gyroscope ───────────────────────────────────────────────────────── */

  _bindGyro() {
    if (!this._hasGyro) return;
    window.addEventListener('deviceorientation', (e) => {
      if (e.alpha === null && e.beta === null && e.gamma === null) return;
      const deg = Math.PI / 180;
      this._euler.set(e.beta * deg, e.alpha * deg, -e.gamma * deg, 'YXZ');
      this._gyroQuat.setFromEuler(this._euler);
      this._gyroQuat.multiply(this._q1);
      const orient = ((window.orientation || 0) * deg);
      this._gyroQuat.multiply(this._q0.setFromAxisAngle(this._zee, -orient));
      this._gyroReady = true;
    }, true);
  }

  _onOrientation() {
    if (this._stereo) this._stereo.setSize(window.innerWidth, window.innerHeight);
    this._syncUI();
  }

  /* ── Per-frame update ────────────────────────────────────────────────── */

  update() {
    const now = performance.now();
    const dt = Math.min((now - this._lastT) / 1000, 0.1);
    this._lastT = now;

    if (this.mode === 'orbit') return;

    // Look with gyro
    if (this._gyroReady) this.camera.quaternion.copy(this._gyroQuat);

    // Move with joystick (on the ground plane, relative to view yaw)
    if (this._joy.active || Math.abs(this._joy.x) > 0.01 || Math.abs(this._joy.y) > 0.01) {
      const fwd = new THREE.Vector3();
      this.camera.getWorldDirection(fwd);
      fwd.y = 0;
      if (fwd.lengthSq() > 1e-6) fwd.normalize();
      const right = new THREE.Vector3().crossVectors(fwd, new THREE.Vector3(0, 1, 0));

      const move = new THREE.Vector3()
        .addScaledVector(fwd, -this._joy.y)
        .addScaledVector(right, this._joy.x)
        .multiplyScalar(this.moveSpeed * dt);

      this.camera.position.add(move);
      this.controls.target.add(move); // keep OrbitControls in sync
    }
  }

  /* ── Render (stereo in VR mode, plain otherwise) ─────────────────────── */

  render(scene, camera) {
    if (this.mode === 'vr' && this._stereo) this._stereo.render(scene, camera);
    else this.renderer.render(scene, camera);
  }

  /* ── UI: mode buttons + virtual joystick ─────────────────────────────── */

  _buildUI() {
    const style = document.createElement('style');
    style.textContent = `
      .kicc-m3d-modes {
        position: fixed; right: 12px; bottom: 14px; z-index: 500;
        display: flex; flex-direction: column; gap: 8px;
      }
      .kicc-m3d-btn {
        min-width: 52px; min-height: 44px; padding: 8px 14px;
        border-radius: 22px; border: 1px solid rgba(255,215,0,0.35);
        background: rgba(10,10,18,0.72); color: rgba(255,255,255,0.75);
        font: 600 12px/1 system-ui, sans-serif; letter-spacing: 0.4px;
        backdrop-filter: blur(6px); cursor: pointer; touch-action: manipulation;
        display: flex; align-items: center; justify-content: center; gap: 6px;
      }
      .kicc-m3d-btn.active { background: #FFD700; color: #111; border-color: #FFD700; }
      .kicc-m3d-btn:disabled { opacity: 0.3; }
      .kicc-m3d-joy {
        position: fixed; left: 18px; bottom: 18px; z-index: 500;
        width: 116px; height: 116px; border-radius: 50%;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.18);
        backdrop-filter: blur(4px); display: none; touch-action: none;
      }
      .kicc-m3d-joy.on { display: block; }
      .kicc-m3d-knob {
        position: absolute; left: 50%; top: 50%;
        width: 52px; height: 52px; border-radius: 50%;
        background: rgba(255,215,0,0.85); box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        transform: translate(-50%, -50%); pointer-events: none;
      }
      .kicc-m3d-toast {
        position: fixed; bottom: 90px; left: 50%; transform: translateX(-50%);
        z-index: 600; background: rgba(0,0,0,0.85); color: #FFD700;
        padding: 10px 18px; border-radius: 18px; font: 500 13px system-ui;
        opacity: 0; transition: opacity 0.3s; pointer-events: none;
      }
      .kicc-m3d-rotate {
        position: fixed; inset: 0; z-index: 550; display: none;
        align-items: center; justify-content: center; text-align: center;
        background: rgba(0,0,0,0.8); color: #FFD700; font: 600 15px system-ui;
        pointer-events: none;
      }
    `;
    document.head.appendChild(style);

    // Mode buttons
    const bar = document.createElement('div');
    bar.className = 'kicc-m3d-modes';
    const defs = [
      ['orbit', '🖱', 'Orbit'],
      ['gyro', '🌀', 'Gyro'],
      ['vr', '🥽', 'VR'],
    ];
    this._btns = {};
    defs.forEach(([mode, icon, label]) => {
      if (this.modes.indexOf(mode) === -1) return;
      const b = document.createElement('button');
      b.className = 'kicc-m3d-btn';
      b.innerHTML = `<span>${icon}</span>${label}`;
      b.addEventListener('click', () => this.setMode(mode));
      if ((mode === 'gyro' || mode === 'vr') && !this._hasGyro) b.disabled = true;
      bar.appendChild(b);
      this._btns[mode] = b;
    });
    document.body.appendChild(bar);

    // Virtual joystick
    const joy = document.createElement('div');
    joy.className = 'kicc-m3d-joy';
    const knob = document.createElement('div');
    knob.className = 'kicc-m3d-knob';
    joy.appendChild(knob);
    document.body.appendChild(joy);
    this._joyEl = joy;
    this._knobEl = knob;
    this._bindJoystick(joy, knob);

    // Rotate-to-landscape hint (VR mode, portrait)
    const rot = document.createElement('div');
    rot.className = 'kicc-m3d-rotate';
    rot.textContent = '🔄 Rotate your phone to landscape for VR';
    document.body.appendChild(rot);
    this._rotateEl = rot;

    this._syncUI();
  }

  _bindJoystick(zone, knob) {
    const RADIUS = 34;
    const setKnob = (dx, dy) => {
      knob.style.transform = `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px))`;
    };
    const handle = (t) => {
      const rect = zone.getBoundingClientRect();
      const cx = rect.left + rect.width / 2;
      const cy = rect.top + rect.height / 2;
      let dx = t.clientX - cx;
      let dy = t.clientY - cy;
      const dist = Math.hypot(dx, dy);
      if (dist > RADIUS) { dx = (dx / dist) * RADIUS; dy = (dy / dist) * RADIUS; }
      this._joy.x = dx / RADIUS;
      this._joy.y = dy / RADIUS;
      setKnob(dx, dy);
    };

    zone.addEventListener('touchstart', (e) => {
      e.preventDefault();
      const t = e.changedTouches[0];
      this._joy.id = t.identifier;
      this._joy.active = true;
      handle(t);
    }, { passive: false });

    zone.addEventListener('touchmove', (e) => {
      e.preventDefault();
      for (const t of e.changedTouches) {
        if (t.identifier === this._joy.id) handle(t);
      }
    }, { passive: false });

    const end = (e) => {
      for (const t of e.changedTouches) {
        if (t.identifier === this._joy.id) {
          this._joy = { x: 0, y: 0, active: false, id: null };
          setKnob(0, 0);
        }
      }
    };
    zone.addEventListener('touchend', end);
    zone.addEventListener('touchcancel', end);
  }

  _syncUI() {
    Object.keys(this._btns).forEach((m) => {
      this._btns[m].classList.toggle('active', m === this.mode);
    });
    this._joyEl.classList.toggle('on', this.mode !== 'orbit');
    const portrait = window.innerHeight > window.innerWidth;
    this._rotateEl.style.display = (this.mode === 'vr' && portrait) ? 'flex' : 'none';
  }

  _toast(msg) {
    let el = document.querySelector('.kicc-m3d-toast');
    if (!el) {
      el = document.createElement('div');
      el.className = 'kicc-m3d-toast';
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.style.opacity = '1';
    clearTimeout(this._toastT);
    this._toastT = setTimeout(() => { el.style.opacity = '0'; }, 2200);
  }

  dispose() {
    document.querySelectorAll('.kicc-m3d-modes, .kicc-m3d-joy, .kicc-m3d-rotate')
      .forEach((el) => el.remove());
  }
}
