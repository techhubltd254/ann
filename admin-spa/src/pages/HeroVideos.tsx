import { useState } from 'react';
import { motion } from 'framer-motion';
import { Plus, Film, Eye, Play, Check } from 'lucide-react';
import { mockHeroVideos } from '../lib/mock-data';
import { cn, getStatusColor } from '../lib/utils';

export default function HeroVideos() {
  const [activeId, setActiveId] = useState('HRV-001');

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Hero Videos & Cinematic</h1>
          <p className="text-sm text-text-muted">Full-bleed cinematic loops powering each portal section hero</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Plus size={15} /> Upload Hero Video</button>
      </motion.div>

      {/* Video Grid */}
      <div className="grid grid-cols-3 gap-4">
        {mockHeroVideos.map((video, i) => {
          const isLive = activeId === video.id;
          return (
            <motion.div
              key={video.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.05 }}
              className={cn(
                'glass-card glass-card-hover overflow-hidden group cursor-pointer',
                isLive && '!border-emerald/40 shadow-lg shadow-emerald/10'
              )}
            >
              <div className="relative h-40 bg-gradient-to-br from-surface-light to-deep flex items-center justify-center">
                <span className="text-5xl">{video.emoji}</span>
                <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
                  <div className="w-12 h-12 rounded-full bg-white/15 backdrop-blur flex items-center justify-center">
                    <Play size={18} className="text-white ml-0.5" fill="currentColor" />
                  </div>
                </div>
                <span className="absolute bottom-2 left-2 pill-badge bg-black/60 text-white !text-[10px]">{video.duration}</span>
                <span className="absolute bottom-2 right-2 pill-badge bg-black/60 text-white !text-[10px]">{video.resolution}</span>
                {isLive && (
                  <span className="absolute top-2 left-2 pill-badge badge-active">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald animate-pulse" /> Live
                  </span>
                )}
              </div>
              <div className="p-4">
                <div className="flex items-start justify-between gap-2 mb-1">
                  <h4 className="text-sm font-bold text-white group-hover:text-violet transition-colors leading-snug">{video.name}</h4>
                  <span className={cn('pill-badge capitalize shrink-0', getStatusColor(video.status))}>{video.status}</span>
                </div>
                <div className="flex items-center justify-between text-[11px] text-text-muted mb-3">
                  <span className="flex items-center gap-1"><Film size={10} /> {video.placement}</span>
                  <span className="flex items-center gap-1"><Eye size={10} /> {video.views}</span>
                </div>
                <button
                  onClick={() => setActiveId(video.id)}
                  className={cn(
                    'w-full py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1.5',
                    isLive
                      ? 'bg-emerald/15 text-emerald border border-emerald/30'
                      : 'bg-surface-hover border border-border text-text-secondary hover:border-violet/40 hover:text-white'
                  )}
                >
                  {isLive ? <><Check size={12} /> Active on Portal</> : 'Set as Active'}
                </button>
              </div>
            </motion.div>
          );
        })}
      </div>

      {/* Encoding Queue */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="glass-card p-5">
        <h3 className="text-base font-bold text-white mb-1">Encoding Queue</h3>
        <p className="text-xs text-text-muted mb-4">Adaptive bitrate renditions for portal delivery</p>
        <div className="space-y-3">
          {[
            { name: 'sagana-adventure-hero.mp4', renditions: '4K · 1080p · 720p', progress: 68 },
            { name: 'coffee-journey-loop.mov', renditions: '4K HDR · 1080p', progress: 24 },
          ].map((job) => (
            <div key={job.name} className="flex items-center gap-4">
              <div className="w-9 h-9 rounded-lg bg-cyan/15 flex items-center justify-center shrink-0">
                <Film size={15} className="text-cyan" />
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex justify-between text-xs mb-1">
                  <span className="text-white font-semibold font-mono truncate">{job.name}</span>
                  <span className="text-cyan font-bold shrink-0">{job.progress}%</span>
                </div>
                <div className="w-full h-1.5 bg-slate-dark rounded-full overflow-hidden">
                  <motion.div
                    initial={{ width: 0 }}
                    animate={{ width: `${job.progress}%` }}
                    transition={{ duration: 0.8 }}
                    className="h-full bg-gradient-to-r from-cyan to-violet rounded-full"
                  />
                </div>
                <div className="text-[10px] text-text-muted mt-1">Renditions: {job.renditions}</div>
              </div>
            </div>
          ))}
        </div>
      </motion.div>
    </div>
  );
}
