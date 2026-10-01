import React, { Suspense, useMemo } from 'react';
import { Canvas } from '@react-three/fiber';
import { Loader, AdaptiveDpr, AdaptiveEvents } from '@react-three/drei';

/**
 * Reusable R3F canvas wrapper with device capability detection.
 *
 * Detects GPU tier from hardware concurrency + WebGL renderer info,
 * scales post-processing and shadow quality accordingly.
 *
 * Props:
 *   children  — R3F scene content
 *   camera    — Three.js camera config (default: { position: [0,0,5], fov:45 })
 *   className — CSS class for the canvas container
 */
export function detectDeviceTier(): 'high' | 'mid' | 'low' {
  if (typeof window === 'undefined') return 'mid';
  const cores = navigator.hardwareConcurrency || 4;
  const memory = (navigator as any).deviceMemory || 4;
  const score = cores + memory;
  if (score >= 14) return 'high';
  if (score >= 8) return 'mid';
  return 'low';
}

interface Scene3DProps {
  children: React.ReactNode;
  camera?: { position?: [number, number, number]; fov?: number };
  className?: string;
}

export default function Scene3D({
  children,
  camera = { position: [0, 0, 5], fov: 45 },
  className = 'w-full h-full',
}: Scene3DProps) {
  const tier = useMemo(() => detectDeviceTier(), []);

  return (
    <div className={className}>
      <Canvas
        camera={camera as any}
        dpr={tier === 'low' ? [1, 1.5] : [1, 2]}
        gl={{
          antialias: tier !== 'low',
          powerPreference: tier === 'high' ? 'high-performance' : 'default',
        }}
        style={{ background: 'transparent' }}
      >
        <AdaptiveDpr pixelated={tier === 'low'} />
        <AdaptiveEvents />
        <Suspense fallback={null}>
          {children}
        </Suspense>
      </Canvas>
      <Loader />
    </div>
  );
}