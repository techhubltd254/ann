import React, { Suspense, useRef } from 'react';
import { useFrame, useLoader } from '@react-three/fiber';
import { TextureLoader, ShaderMaterial, Mesh } from 'three';
import Scene3D, { detectDeviceTier } from './Scene3D';

interface HologramViewerProps {
  rgbUrl: string;
  depthUrl: string;
  wiggleIntensity?: number;
}

function HologramPlane({ rgbUrl, depthUrl, intensity }: { rgbUrl: string; depthUrl: string; intensity: number }) {
  const meshRef = useRef<Mesh>(null);
  const rgb = useLoader(TextureLoader, rgbUrl);
  const depth = useLoader(TextureLoader, depthUrl);
  const tier = detectDeviceTier();

  useFrame(({ mouse }) => {
    if (!meshRef.current) return;
    const xOff = mouse.x * intensity;
    const yOff = mouse.y * intensity * 0.5;
    if (meshRef.current.material instanceof ShaderMaterial) {
      meshRef.current.material.uniforms.uOffset.value.set(xOff, yOff);
    }
  });

  const vertexShader = `
    varying vec2 vUv;
    void main() {
      vUv = uv;
      gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
    }
  `;

  const res = tier === 'low' ? 32 : 64;
  const fragmentShader = `
    uniform sampler2D uRGB;
    uniform sampler2D uDepth;
    uniform vec2 uOffset;
    varying vec2 vUv;
    void main() {
      float d = texture2D(uDepth, vUv).r;
      vec2 offset = uOffset * (1.0 - d) * ${tier === 'low' ? 0.5 : 1.0};
      vec4 color = texture2D(uRGB, vUv + offset);
      gl_FragColor = color;
    }
  `;

  return (
    <mesh ref={meshRef}>
      <planeGeometry args={[4, 3, res, res / 2]} />
      <shaderMaterial
        uniforms={{
          uRGB: { value: rgb },
          uDepth: { value: depth },
          uOffset: { value: [0, 0] },
        }}
        vertexShader={vertexShader}
        fragmentShader={fragmentShader}
      />
    </mesh>
  );
}

export default function HologramViewer({ rgbUrl, depthUrl, wiggleIntensity = 0.3 }: HologramViewerProps) {
  const tier = detectDeviceTier();
  return (
    <div className="relative w-full h-full min-h-[300px]">
      <Scene3D camera={{ position: [0, 0, 5], fov: 45 }}>
        <ambientLight intensity={1} />
        <Suspense fallback={null}>
          <HologramPlane rgbUrl={rgbUrl} depthUrl={depthUrl} intensity={wiggleIntensity} />
        </Suspense>
      </Scene3D>
      <div className="absolute bottom-2 left-2 bg-black/50 text-white/40 text-[10px] px-1.5 py-0.5 rounded">
        Holo · Tier: {tier}
      </div>
    </div>
  );
}