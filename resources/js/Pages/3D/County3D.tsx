import React from 'react';
import { Head } from '@inertiajs/react';
import { County3DViewer } from '@/Components/3D';

interface Props {
  county: { id: number; name: string; slug: string };
  modelUrl: string;
}

export default function County3DPage({ county, modelUrl }: Props) {
  return (
    <>
      <Head title={`${county.name} — 3D Map`} />
      <div className="min-h-screen bg-[#0b0b0b] flex flex-col">
        <div className="flex-1 relative">
          <County3DViewer modelUrl={modelUrl} autoRotate={true} />
        </div>
        <div className="absolute top-4 left-4 z-10">
          <a href={`/counties/${county.slug}`} className="inline-flex items-center gap-1 text-white/60 hover:text-white text-sm bg-black/40 px-3 py-1.5 rounded-lg backdrop-blur">
            ← Back to {county.name}
          </a>
        </div>
      </div>
    </>
  );
}