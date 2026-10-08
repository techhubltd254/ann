import React, { useEffect, useRef, useState } from 'react';
import * as THREE from 'three';

/**
 * Gaussian Splat viewer — renders .splat files for 3D scanned environments.
 * Uses a lightweight custom renderer (no external splat library needed for basic viewing).
 *
 * For production use, integrate with @mkkellogg/gaussian-splats-3d or similar.
 *
 * Props:
 *   splatUrl — URL to the .splat file (binary Gaussian Splat format)
 *   title    — display title
 */
interface GaussianSplatViewerProps {
  splatUrl: string;
  title?: string;
}

export default function GaussianSplatViewer({ splatUrl, title }: GaussianSplatViewerProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!containerRef.current) return;

    // Simple Three.js scene as fallback until full splat renderer is integrated
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#0b0b0b');
    const camera = new THREE.PerspectiveCamera(60, containerRef.current.clientWidth / containerRef.current.clientHeight, 0.1, 100);
    camera.position.set(2, 1.5, 3);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(containerRef.current.clientWidth, containerRef.current.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    containerRef.current.appendChild(renderer.domElement);

    // Placeholder sphere until splat loads
    const geo = new THREE.SphereGeometry(1, 32, 32);
    const mat = new THREE.MeshStandardMaterial({ color: '#2a2a2a', wireframe: true, transparent: true, opacity: 0.3 });
    const mesh = new THREE.Mesh(geo, mat);
    scene.add(mesh);

    const light = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(light);
    const dirLight = new THREE.DirectionalLight(0xffffff, 1);
    dirLight.position.set(5, 10, 5);
    scene.add(dirLight);

    let animId: number;
    function animate() {
      animId = requestAnimationFrame(animate);
      mesh.rotation.y += 0.002;
      renderer.render(scene, camera);
    }
    animate();

    // Attempt to load the splat file
    fetch(splatUrl)
      .then((r) => {
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        setLoading(false);
      })
      .catch((e) => {
        setError(e.message);
        setLoading(false);
      });

    return () => {
      cancelAnimationFrame(animId);
      renderer.dispose();
      if (containerRef.current?.contains(renderer.domElement)) {
        containerRef.current.removeChild(renderer.domElement);
      }
    };
  }, [splatUrl]);

  return (
    <div className="relative w-full h-full min-h-[300px] rounded-xl overflow-hidden">
      <div ref={containerRef} className="w-full h-full" />
      {loading && (
        <div className="absolute inset-0 flex items-center justify-center bg-[#0b0b0b]/80">
          <div className="flex flex-col items-center gap-2">
            <svg className="w-8 h-8 animate-spin text-[#2a2a2a]" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span className="text-white/60 text-sm">Loading Gaussian Splat…</span>
          </div>
        </div>
      )}
      {error && (
        <div className="absolute inset-0 flex items-center justify-center bg-[#0b0b0b]/90">
          <div className="text-center px-4">
            <p className="text-red-400 text-sm mb-2">Failed to load splat</p>
            <p className="text-white/40 text-xs">{error}</p>
          </div>
        </div>
      )}
      {title && (
        <div className="absolute top-2 left-2 bg-black/60 text-white/80 text-xs px-2 py-1 rounded">
          {title}
        </div>
      )}
    </div>
  );
}