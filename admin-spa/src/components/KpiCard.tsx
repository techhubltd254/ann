import { motion } from 'framer-motion';
import { TrendingUp, TrendingDown, LucideIcon } from 'lucide-react';
import { cn } from '../lib/utils';

// Literal class lookup so Tailwind JIT picks them up
const COLOR_MAP: Record<string, { text: string; bg: string; stroke: string }> = {
  violet: { text: 'text-violet', bg: 'bg-violet/15', stroke: '#6366F1' },
  indigo: { text: 'text-indigo', bg: 'bg-indigo/15', stroke: '#7C3AED' },
  emerald: { text: 'text-emerald', bg: 'bg-emerald/15', stroke: '#10B981' },
  cyan: { text: 'text-cyan', bg: 'bg-cyan/15', stroke: '#06B6D4' },
  amber: { text: 'text-amber', bg: 'bg-amber/15', stroke: '#F59E0B' },
  rose: { text: 'text-rose', bg: 'bg-rose/15', stroke: '#F43F5E' },
};

interface KpiCardProps {
  label: string;
  value: string;
  icon: LucideIcon;
  /** e.g. "text-violet" */
  color: string;
  change: string;
  trend: 'up' | 'down';
  index?: number;
}

/** Deterministic pseudo-random sparkline so each card looks alive but stable */
function makeSpark(seed: number, trend: 'up' | 'down'): number[] {
  const pts: number[] = [];
  let v = 40 + ((seed * 13) % 25);
  for (let i = 0; i < 12; i++) {
    const noise = ((seed * 31 + i * 17) % 15) - 7;
    v += (trend === 'up' ? 3.5 : -3.5) + noise;
    v = Math.max(10, Math.min(90, v));
    pts.push(v);
  }
  return pts;
}

export default function KpiCard({ label, value, icon: Icon, color, change, trend, index = 0 }: KpiCardProps) {
  const key = color.replace('text-', '');
  const palette = COLOR_MAP[key] ?? COLOR_MAP.violet;
  const spark = makeSpark(index + 7, trend);
  const points = spark
    .map((v, i) => `${(i / (spark.length - 1)) * 96},${34 - (v / 100) * 30}`)
    .join(' ');

  return (
    <motion.div
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.05, duration: 0.35 }}
      className="metric-card group cursor-default"
    >
      <div className="flex items-start justify-between mb-3">
        <div className={cn('kpi-icon-wrap', palette.bg)}>
          <Icon size={18} className={palette.text} />
        </div>
        <span
          className={cn(
            'pill-badge',
            trend === 'up'
              ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
              : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
          )}
        >
          {trend === 'up' ? <TrendingUp size={11} /> : <TrendingDown size={11} />}
        </span>
      </div>

      <div className="text-[11px] font-semibold text-text-muted uppercase tracking-wider mb-1">{label}</div>
      <div className="text-xl font-black text-white mb-2 leading-tight group-hover:text-gradient-violet transition-all">{value}</div>

      <div className="flex items-end justify-between gap-2">
        <span className="text-[11px] text-text-secondary leading-snug">{change}</span>
        <svg width="96" height="34" viewBox="0 0 96 34" className="shrink-0 opacity-80">
          <defs>
            <linearGradient id={`spark-fill-${index}`} x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={palette.stroke} stopOpacity="0.35" />
              <stop offset="100%" stopColor={palette.stroke} stopOpacity="0" />
            </linearGradient>
          </defs>
          <polygon points={`0,34 ${points} 96,34`} fill={`url(#spark-fill-${index})`} />
          <polyline
            points={points}
            fill="none"
            stroke={palette.stroke}
            strokeWidth="1.5"
            strokeLinejoin="round"
            strokeLinecap="round"
          />
        </svg>
      </div>
    </motion.div>
  );
}
