import { motion } from 'framer-motion';
import { Upload, Video, Cpu, Clock, Database, Play, Settings2 } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import { mock4DScenes } from '../lib/mock-data';
import { cn, getStatusColor } from '../lib/utils';

const KPIS = [
  { label: 'Volumetric Scenes', value: '4 Published', icon: Video, color: 'text-violet', change: '2 rendering in queue', trend: 'up' as const },
  { label: 'Render Capacity', value: 'WebGPU ×4', icon: Cpu, color: 'text-cyan', change: 'Cluster: kicc-gpu-01', trend: 'up' as const },
  { label: 'Total Runtime', value: '8m 36s', icon: Clock, color: 'text-emerald', change: 'Across all scenes', trend: 'up' as const },
  { label: 'Splat Storage', value: '14.7 GB', icon: Database, color: 'text-amber', change: 'of 200 GB vault', trend: 'up' as const },
];

const PIPELINE = [
  { stage: 'Capture Ingest', detail: 'RAW video frames · drone & gimbal rigs', progress: 100, state: 'complete' },
  { stage: 'Splat Training', detail: '3D Gaussian optimization · 30K steps', progress: 72, state: 'running' },
  { stage: 'Compression', detail: 'WebGPU-ready .splat packaging', progress: 12, state: 'queued' },
  { stage: 'Portal Publish', detail: 'Scroll-sync embed on county portal', progress: 0, state: 'queued' },
];

export default function Video4D() {
  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">4D Gaussian Splatting Studio</h1>
          <p className="text-sm text-text-muted">Volumetric capture pipeline and cinematic scene publishing</p>
        </div>
        <div className="flex items-center gap-2">
          <span className="pill-badge badge-active">Engine: WebGPU</span>
          <button className="btn-primary flex items-center gap-2"><Upload size={15} /> New Capture</button>
        </div>
      </motion.div>

      {/* KPI Row */}
      <div className="grid grid-cols-4 gap-4">
        {KPIS.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </div>

      {/* Upload Dropzone */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.15 }}
        className="glass-card border-dashed !border-violet/30 p-8 text-center cursor-pointer hover:!border-violet/60 hover:bg-violet/5 transition-all group"
      >
        <div className="w-14 h-14 mx-auto rounded-2xl bg-violet/15 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
          <Upload size={22} className="text-violet" />
        </div>
        <div className="text-sm font-bold text-white mb-1">Drop volumetric capture footage</div>
        <div className="text-xs text-text-muted">MP4 / MOV sequences, drone flight logs, or COLMAP exports · max 40 GB per job</div>
      </motion.div>

      <div className="grid grid-cols-3 gap-6">
        {/* Scenes */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="col-span-2 glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Published Scenes</h3>
          <p className="text-xs text-text-muted mb-4">Volumetric experiences live on the portal</p>
          <div className="space-y-3">
            {mock4DScenes.map((scene) => (
              <div key={scene.id} className="flex items-center gap-4 p-3 rounded-xl glass-card-hover group cursor-pointer">
                <div className="w-14 h-14 rounded-lg bg-gradient-to-br from-violet/20 to-indigo/10 border border-violet/20 flex items-center justify-center text-2xl shrink-0">
                  {scene.emoji}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="text-sm font-bold text-white truncate group-hover:text-violet transition-colors">{scene.title}</div>
                  <div className="text-[11px] text-text-muted truncate">{scene.subtitle}</div>
                  <div className="flex items-center gap-3 mt-1.5 text-[10px] text-text-muted">
                    <span className="pill-badge bg-surface-light border border-border !text-[9px]">{scene.resolution}</span>
                    <span>{scene.duration}</span>
                    <span>{scene.frames.toLocaleString()} frames</span>
                    <span>{scene.size}</span>
                  </div>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                  <span className={cn('pill-badge capitalize', getStatusColor(scene.status))}>{scene.status}</span>
                  <button className="p-2 rounded-lg bg-violet/15 text-violet hover:bg-violet hover:text-white transition-all">
                    <Play size={14} />
                  </button>
                  <button className="p-2 rounded-lg text-text-muted hover:text-white hover:bg-surface-hover transition-all">
                    <Settings2 size={14} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </motion.div>

        {/* Render Pipeline */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.25 }} className="glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Render Pipeline</h3>
          <p className="text-xs text-text-muted mb-4">Job: sagana-rapids-flow-04</p>
          <div className="space-y-4">
            {PIPELINE.map((stage) => (
              <div key={stage.stage}>
                <div className="flex items-center justify-between mb-1.5">
                  <span className="text-xs font-semibold text-white">{stage.stage}</span>
                  <span
                    className={cn(
                      'text-[10px] font-bold uppercase tracking-wider',
                      stage.state === 'complete' && 'text-emerald',
                      stage.state === 'running' && 'text-cyan',
                      stage.state === 'queued' && 'text-text-muted'
                    )}
                  >
                    {stage.state === 'running' ? `${stage.progress}%` : stage.state}
                  </span>
                </div>
                <div className="w-full h-1.5 bg-slate-dark rounded-full overflow-hidden">
                  <motion.div
                    initial={{ width: 0 }}
                    animate={{ width: `${stage.progress}%` }}
                    transition={{ duration: 0.8 }}
                    className={cn(
                      'h-full rounded-full',
                      stage.state === 'complete' && 'bg-emerald',
                      stage.state === 'running' && 'bg-gradient-to-r from-cyan to-violet animate-pulse',
                      stage.state === 'queued' && 'bg-surface-light'
                    )}
                  />
                </div>
                <div className="text-[10px] text-text-muted mt-1">{stage.detail}</div>
              </div>
            ))}
          </div>
          <div className="mt-5 p-3 rounded-lg bg-cyan/5 border border-cyan/20 text-[11px] text-text-secondary leading-relaxed">
            <span className="text-cyan font-semibold">ETA 42 min</span> — Sagana Rapids scene will auto-publish to the portal hero slot on completion.
          </div>
        </motion.div>
      </div>
    </div>
  );
}
