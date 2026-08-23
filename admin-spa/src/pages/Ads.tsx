import { useState } from 'react';
import { motion } from 'framer-motion';
import { Plus, Megaphone, Eye, MousePointerClick, DollarSign, Pause, Play } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import { mockCampaigns } from '../lib/mock-data';
import { cn, formatKES, getStatusColor } from '../lib/utils';

const KPIS = [
  { label: 'Impressions (Aug)', value: '2.12M', icon: Eye, color: 'text-violet', change: '+31% vs July', trend: 'up' as const },
  { label: 'Clicks', value: '69.4K', icon: MousePointerClick, color: 'text-cyan', change: 'CTR 3.3% avg', trend: 'up' as const },
  { label: 'Ad Spend', value: 'KES 309K', icon: DollarSign, color: 'text-amber', change: 'of KES 870K budget', trend: 'up' as const },
  { label: 'Attributed Revenue', value: 'KES 1.1M', icon: Megaphone, color: 'text-emerald', change: 'ROAS 3.6×', trend: 'up' as const },
];

export default function Ads() {
  const [paused, setPaused] = useState<Record<string, boolean>>({});

  const togglePause = (id: string) => setPaused((p) => ({ ...p, [id]: !p[id] }));

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Advertising & Campaigns</h1>
          <p className="text-sm text-text-muted">Destination marketing campaigns promoting Murang'a experiences</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Plus size={15} /> Launch Campaign</button>
      </motion.div>

      {/* KPI Row */}
      <div className="grid grid-cols-4 gap-4">
        {KPIS.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </div>

      {/* Campaigns */}
      <div className="grid grid-cols-2 gap-4">
        {mockCampaigns.map((camp, i) => {
          const isPaused = paused[camp.id];
          const pct = camp.budget > 0 ? Math.round((camp.spent / camp.budget) * 100) : 0;
          return (
            <motion.div
              key={camp.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.15 + i * 0.05 }}
              className={cn('glass-card p-5 transition-all', isPaused && 'opacity-60')}
            >
              <div className="flex items-start justify-between mb-3">
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <h4 className="text-sm font-bold text-white">{camp.name}</h4>
                    <span className={cn('pill-badge capitalize', getStatusColor(camp.status))}>
                      {isPaused ? 'paused' : camp.status}
                    </span>
                  </div>
                  <span className="text-[11px] text-text-muted">{camp.channel} · {camp.id}</span>
                </div>
                <button
                  onClick={() => togglePause(camp.id)}
                  className={cn(
                    'p-2 rounded-lg transition-all',
                    isPaused
                      ? 'bg-emerald/15 text-emerald hover:bg-emerald hover:text-white'
                      : 'bg-amber/15 text-amber hover:bg-amber hover:text-white'
                  )}
                  title={isPaused ? 'Resume campaign' : 'Pause campaign'}
                >
                  {isPaused ? <Play size={14} /> : <Pause size={14} />}
                </button>
              </div>

              <div className="grid grid-cols-3 gap-3 mb-4">
                <div className="p-2.5 rounded-lg bg-slate-dark border border-border">
                  <div className="text-[10px] text-text-muted uppercase tracking-wider">Impressions</div>
                  <div className="text-sm font-black text-white">{camp.impressions}</div>
                </div>
                <div className="p-2.5 rounded-lg bg-slate-dark border border-border">
                  <div className="text-[10px] text-text-muted uppercase tracking-wider">Clicks</div>
                  <div className="text-sm font-black text-white">{camp.clicks}</div>
                </div>
                <div className="p-2.5 rounded-lg bg-slate-dark border border-border">
                  <div className="text-[10px] text-text-muted uppercase tracking-wider">CTR</div>
                  <div className="text-sm font-black text-cyan">{camp.ctr}%</div>
                </div>
              </div>

              <div className="flex justify-between text-[11px] mb-1.5">
                <span className="text-text-muted">Budget spent</span>
                <span className="text-white font-semibold">
                  {formatKES(camp.spent)} <span className="text-text-muted font-normal">/ {formatKES(camp.budget)}</span>
                </span>
              </div>
              <div className="w-full h-2 bg-slate-dark rounded-full overflow-hidden">
                <motion.div
                  initial={{ width: 0 }}
                  animate={{ width: `${pct}%` }}
                  transition={{ delay: 0.3 + i * 0.05, duration: 0.7 }}
                  className={cn(
                    'h-full rounded-full',
                    pct > 80 ? 'bg-gradient-to-r from-amber to-rose' : 'bg-gradient-to-r from-violet to-cyan'
                  )}
                />
              </div>
              <div className="text-right text-[10px] text-text-muted mt-1">{pct}% utilized</div>
            </motion.div>
          );
        })}
      </div>
    </div>
  );
}
