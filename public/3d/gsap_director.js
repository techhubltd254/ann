/**
 * KICC GSAP Director — cinematic animation layer for the 3D viewers.
 *
 * Two roles:
 *
 * 1. IMMEDIATE — camera choreography & micro-interactions for the existing
 *    viewers (flyTo on selection, staggered load-in, panel reveals).
 *
 * 2. PIPELINE INTEGRATION (permanent contract) — playPrompt() executes a
 *    Kimi K3 "3D Creation Prompt" in the browser. The pipeline emits the
 *    prompt JSON (pipeline/analyzer.py); the web viewer becomes a first-class
 *    render target alongside SBS video and splats:
 *
 *      prompt.camera_movement   -> camera path      (zoom_in_to_peak, pan_right, ...)
 *      prompt.animation_style   -> object animation (ken_burns, sequential_build, ...)
 *      prompt.transition_style  -> segment blending (crossfade, fade_black, cut)
 *      prompt.depth_strength    -> dolly intensity  (0.5 subtle .. 3.0 aggressive)
 *      prompt.clip_duration_per_image / total_duration_seconds -> pacing
 *      prompt.billboard_3d.border_break_objects    -> accent pulses on named meshes
 *
 * Requires the host importmap to map "gsap" (see the viewers for the URL).
 */

import { gsap } from 'gsap';

const CAMERA_MOVEMENTS = [
  'zoom_in', 'zoom_in_to_peak', 'pan_right', 'pan_left',
  'orbit_360', 'crane_up', 'dolly_in', 'flythrough', 'static',
];

export class GSAPDirector {
  /**
   * @param {object} opts
   * @param {THREE.PerspectiveCamera} opts.camera
   * @param {OrbitControls}           opts.controls
   */
  constructor({ camera, controls }) {
    this.camera = camera;
    this.controls = controls;
    this._camTween = null;
    this._promptTl = null;
  }

  /* ── Camera choreography ─────────────────────────────────────────────── */

  /**
   * Cinematic flight to a new camera position + look target.
   * @param {object} o
   * @param {{x:number,y:number,z:number}} o.position  camera destination
   * @param {{x:number,y:number,z:number}} o.target    look-at destination
   * @param {number} [o.duration=2]   seconds
   * @param {string} [o.ease]         gsap ease (default power3.inOut)
   * @param {function} [o.onArrive]   called on completion
   */
  flyTo({ position, target, duration = 2, ease = 'power3.inOut', onArrive = null }) {
    if (this._camTween) this._camTween.kill();
    const tl = gsap.timeline({ onComplete: () => { if (onArrive) onArrive(); } });
    if (position) {
      tl.to(this.camera.position,
        { x: position.x, y: position.y, z: position.z, duration, ease }, 0);
    }
    if (target) {
      tl.to(this.controls.target,
        { x: target.x, y: target.y, z: target.z, duration, ease,
          onUpdate: () => this.controls.update() }, 0);
    }
    this._camTween = tl;
    return tl;
  }

  /**
   * Fly toward a scene object (booth, county mesh, billboard) and stop at a
   * pleasant viewing distance — used when a user taps something interactive.
   * @param {THREE.Object3D} obj
   * @param {number} [o.distance=2.5]  final horizontal distance from object
   * @param {number} [o.height=1.3]    camera height above object base
   * @param {number} [o.lookHeight=0.6] look-at height above object base
   */
  flyToObject(obj, { distance = 2.5, height = 1.3, lookHeight = 0.6, duration = 2, onArrive = null } = {}) {
    const p = obj.position;
    // Approach from the camera's current side so the move feels continuous
    const dir = this.camera.position.clone().sub(p);
    dir.y = 0;
    if (dir.lengthSq() < 1e-6) dir.set(1, 0, 0);
    dir.normalize();
    return this.flyTo({
      position: { x: p.x + dir.x * distance, y: p.y + height, z: p.z + dir.z * distance },
      target: { x: p.x, y: p.y + lookHeight, z: p.z },
      duration,
      onArrive,
    });
  }

  /** Dolly toward/away from the current target. factor < 1 zooms in. */
  dolly(factor, { duration = 1.2, ease = 'power2.inOut' } = {}) {
    const offset = this.camera.position.clone().sub(this.controls.target).multiplyScalar(factor);
    return this.flyTo({
      position: this.controls.target.clone().add(offset),
      duration, ease,
    });
  }

  /** Accent pulse — used for billboard_3d.border_break_objects highlights. */
  pulse(obj, { scale = 1.15, duration = 0.35 } = {}) {
    const s = obj.scale.x;
    return gsap.to(obj.scale, {
      x: s * scale, y: s * scale, z: s * scale,
      duration, yoyo: true, repeat: 1, ease: 'power2.inOut',
    });
  }

  /* ── Load-in choreography ────────────────────────────────────────────── */

  /**
   * Staggered scale-in for a set of objects (county pins, booths, billboards).
   * Animates scale only — safe alongside per-frame position animations.
   */
  intro(objects, { stagger = 0.025, duration = 0.85, ease = 'back.out(1.7)', delay = 0.15 } = {}) {
    const finals = objects.map((o) => o.scale.clone());
    objects.forEach((o) => o.scale.setScalar(0.001));
    const tweens = objects.map((o, i) =>
      gsap.to(o.scale, {
        x: finals[i].x, y: finals[i].y, z: finals[i].z,
        duration, delay: delay + i * stagger, ease,
      })
    );
    return tweens;
  }

  /* ── DOM micro-interactions ──────────────────────────────────────────── */

  /** Fade + rise reveal for a panel/popup element. */
  reveal(el, { y = 18, duration = 0.45 } = {}) {
    return gsap.fromTo(el, { autoAlpha: 0, y },
      { autoAlpha: 1, y: 0, duration, ease: 'power2.out', clearProps: 'transform' });
  }

  /** Staggered reveal of matching children (panel contents). */
  revealChildren(selector, { stagger = 0.05, y = 14, duration = 0.4, delay = 0.08 } = {}) {
    const els = document.querySelectorAll(selector);
    if (!els.length) return null;
    return gsap.fromTo(els, { autoAlpha: 0, y },
      { autoAlpha: 1, y: 0, duration, delay, stagger, ease: 'power2.out', clearProps: 'transform' });
  }

  /* ══════════════════ PIPELINE CONTRACT ══════════════════ */

  /**
   * Execute a Kimi K3 "3D Creation Prompt" as a live camera/object timeline.
   *
   * @param {object} prompt   The prompt JSON from pipeline/analyzer.py
   * @param {object} [context]
   * @param {THREE.Vector3[]} [context.focusPoints]  Keyframe world positions
   *        (e.g. county/booth positions matching prompt.keyframe_indices)
   * @param {Object<string,THREE.Object3D>} [context.objects]  Named meshes for
   *        billboard_3d.border_break_objects accents
   * @param {HTMLElement} [context.fadeEl]  Fullscreen overlay for fade_black
   * @param {function} [context.onDone]
   * @returns {gsap.core.Timeline}
   */
  playPrompt(prompt = {}, context = {}) {
    if (this._promptTl) this._promptTl.kill();

    const movement = CAMERA_MOVEMENTS.includes(prompt.camera_movement)
      ? prompt.camera_movement : 'zoom_in';
    const transition = prompt.transition_style || 'crossfade';
    const depth = Math.min(Math.max(prompt.depth_strength || 1.5, 0.5), 3.0);
    const dollyFactor = 1 - (0.10 + depth * 0.08); // 0.5->0.86 .. 3.0->0.66
    const segDur = Math.max(prompt.clip_duration_per_image || 4, 1.5);
    const total = Math.max(prompt.total_duration_seconds || segDur * 3, segDur);

    const focus = context.focusPoints && context.focusPoints.length
      ? context.focusPoints : [this.controls.target.clone()];
    const overlap = transition === 'crossfade' ? Math.min(segDur * 0.25, 1) : 0;

    const tl = gsap.timeline({
      defaults: { ease: 'power2.inOut' },
      onComplete: () => { if (context.onDone) context.onDone(); },
    });

    /* Camera path per camera_movement */
    if (movement === 'orbit_360') {
      const c = focus[0];
      const r = this.camera.position.clone().sub(c);
      const angle0 = Math.atan2(r.z, r.x);
      const radius = Math.hypot(r.x, r.z);
      const steps = 24;
      for (let i = 1; i <= steps; i++) {
        const a = angle0 + (i / steps) * Math.PI * 2;
        tl.to(this.camera.position, {
          x: c.x + Math.cos(a) * radius,
          z: c.z + Math.sin(a) * radius,
          duration: total / steps, ease: 'none',
          onUpdate: () => this.controls.update(),
        }, (i - 1) * (total / steps));
      }
    } else if (movement === 'flythrough' && focus.length > 1) {
      focus.forEach((f, i) => {
        const dir = this.camera.position.clone().sub(f);
        dir.y = 0;
        if (dir.lengthSq() < 1e-6) dir.set(1, 0, 0);
        dir.normalize();
        tl.to(this.camera.position, {
          x: f.x + dir.x * 3, y: f.y + 1.6, z: f.z + dir.z * 3,
          duration: segDur,
          onUpdate: () => this.controls.update(),
        }, i === 0 ? 0 : `-=${overlap}`);
        tl.to(this.controls.target, { x: f.x, y: f.y + 0.6, z: f.z, duration: segDur }, '<');
      });
    } else if (movement === 'pan_right' || movement === 'pan_left') {
      const sign = movement === 'pan_right' ? 1 : -1;
      const dist = 2 * depth;
      tl.to(this.camera.position, { x: `+=${sign * dist}`, duration: total, ease: 'none' }, 0);
      tl.to(this.controls.target, {
        x: `+=${sign * dist}`, duration: total, ease: 'none',
        onUpdate: () => this.controls.update(),
      }, 0);
    } else if (movement === 'crane_up') {
      tl.to(this.camera.position, { y: `+=${1.5 * depth}`, duration: total }, 0);
      tl.to(this.controls.target, { y: `-=${0.3 * depth}`, duration: total,
        onUpdate: () => this.controls.update() }, 0);
    } else if (movement !== 'static') {
      // zoom_in / zoom_in_to_peak / dolly_in: dolly toward the focus point
      const f = focus[0];
      const offset = this.camera.position.clone().sub(f).multiplyScalar(dollyFactor);
      tl.to(this.camera.position, {
        x: f.x + offset.x, y: f.y + offset.y, z: f.z + offset.z, duration: total,
      }, 0);
      tl.to(this.controls.target, { x: f.x, y: f.y + 0.5, z: f.z, duration: total,
        onUpdate: () => this.controls.update() }, 0);
    }

    /* fade_black transition: dip a fullscreen overlay at each segment boundary */
    if (transition === 'fade_black' && context.fadeEl) {
      const el = context.fadeEl;
      for (let t = segDur; t < total; t += segDur) {
        tl.to(el, { autoAlpha: 1, duration: 0.3 }, t - 0.3)
          .to(el, { autoAlpha: 0, duration: 0.3 }, t);
      }
    }

    /* animation_style accents */
    if (prompt.animation_style === 'sequential_build' && context.objects) {
      const objs = Object.values(context.objects);
      objs.forEach((o) => o.scale.setScalar(0.001));
      tl.add(this.intro(objs, { stagger: segDur / Math.max(objs.length, 1) }), 0);
    } else if (prompt.animation_style === 'ken_burns' && context.objects) {
      // subtle breathing zoom on featured meshes
      Object.values(context.objects).forEach((o, i) => {
        tl.to(o.scale, {
          x: o.scale.x * 1.06, y: o.scale.y * 1.06, z: o.scale.z * 1.06,
          duration: total, ease: 'none',
        }, i * 0.05);
      });
    }

    /* billboard_3d.border_break_objects: accent pulses at segment starts */
    const breakers = (prompt.billboard_3d && prompt.billboard_3d.border_break_objects) || [];
    if (breakers.length && context.objects) {
      breakers.forEach((name, i) => {
        const obj = context.objects[name];
        if (obj) tl.add(() => this.pulse(obj), i * segDur);
      });
    }

    this._promptTl = tl;
    return tl;
  }

  /** Stop the running prompt timeline, if any. */
  stopPrompt() {
    if (this._promptTl) { this._promptTl.kill(); this._promptTl = null; }
  }
}
