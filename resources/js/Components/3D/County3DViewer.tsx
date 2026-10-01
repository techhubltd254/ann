import React, { Suspense, useRef } from 'react';
import { useFrame } from '@react-three/fiber';
import { OrbitControls, Environment, useGLTF } from '@react-three/drei';
import { Group } from 'three';
import Scene3D, { detectDeviceTier } from './Scene3D';

interface County3DViewerProps {
  modelUrl: string;
  autoRotate?: boolean;
}

function CountyModel({ url, autoRotate }: { url: string; autoRotate: boolean }) {
  const group = useRef<Group>(null);
  const { scene } = useGLTF(url);

  useFrame((_state, delta) => {
    if (group.current && autoRotate) {
      group.current.rotation.y += delta * 0.1;
    }
  });

  return <primitive ref={group} object={scene} scale={1} />;
}

export default function County3DViewer({
  modelUrl,
  autoRotate = true,
}: County3DViewerProps) {
  const tier = detectDeviceTier();

  return (
    <div className="relative w-full h-full min-h-[400px]">
      <Scene3D camera={{ position: [0, 2, 8], fov: 50 }}>
        <ambientLight intensity={tier === 'low' ? 0.5 : 1} />
        <directionalLight
          position={[5, 10, 5]}
          intensity={tier === 'high' ? 1.5 : 1}
          castShadow={tier === 'high'}
          shadow-mapSize-width={tier === 'high' ? 1024 : 512}
          shadow-mapSize-height={tier === 'high' ? 1024 : 512}
        />
        <Suspense fallback={null}>
          <CountyModel url={modelUrl} autoRotate={autoRotate} />
        </Suspense>
        <OrbitControls
          enableDamping
          dampingFactor={0.1}
          autoRotate={autoRotate}
          autoRotateSpeed={0.5}
          minDistance={2}
          maxDistance={20}
          maxPolarAngle={Math.PI / 1.5}
        />
        <Environment preset="sunset" />
      </Scene3D>
      <div className="absolute bottom-3 left-3 bg-black/60 text-white/60 text-xs px-2 py-1 rounded">
        🖱️ Drag to orbit · Scroll to zoom · Tier: {tier}
      </div>
    </div>
  );
}