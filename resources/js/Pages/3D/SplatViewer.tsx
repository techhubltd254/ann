import React from 'react';
import { Head } from '@inertiajs/react';
import { GaussianSplatViewer } from '@/Components/3D';

interface Props {
  splatName: string;
  splatUrl: string;
}

const SPLAT_TITLES: Record<string, string> = {
  university_orig: 'University Campus — Original',
  hospital: 'Hospital — Standard',
  hospital_netflix: 'Hospital — Netflix Quality',
  hospital_v3: 'Hospital — Version 3',
};

export default function SplatViewerPage({ splatName, splatUrl }: Props) {
  return (
    <>
      <Head title={`${SPLAT_TITLES[splatName] || splatName} — 3D Splat Viewer`} />
      <div className="min-h-screen bg-[#0b0b0b] flex flex-col">
        <div className="absolute top-4 left-4 z-10">
          <a href="/3d/splats" className="inline-flex items-center gap-1 text-white/60 hover:text-white text-sm bg-black/40 px-3 py-1.5 rounded-lg backdrop-blur">
            ← Splat Gallery
          </a>
        </div>
        <div className="flex-1">
          <GaussianSplatViewer splatUrl={splatUrl} title={SPLAT_TITLES[splatName]} />
        </div>
      </div>
    </>
  );
}