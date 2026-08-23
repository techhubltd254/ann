import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { ScrollText, Download, ShieldAlert, Info, AlertTriangle } from 'lucide-react';
import { mockAuditLogs } from '../lib/mock-data';
import { cn } from '../lib/utils';

const LEVELS = ['all', 'info', 'warning', 'critical'] as const;

const LEVEL_STYLE: Record<string, { badge: string; icon: any }> = {
  info: { badge: 'bg-cyan-500/15 text-cyan-400 border border-cyan-500/30', icon: Info },
  warning: { badge: 'bg-amber-500/15 text-amber-400 border border-amber-500/30', icon: AlertTriangle },
  critical: { badge: 'bg-rose-500/15 text-rose-400 border border-rose-500/30', icon: ShieldAlert },
};

export default function AuditLogs() {
  const [level, setLevel] = useState<(typeof LEVELS)[number]>('all');

  const logs = useMemo(
    () => mockAuditLogs.filter((l) => level === 'all' || l.level === level),
    [level]
  );

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Audit Logs</h1>
          <p className="text-sm text-text-muted">Immutable activity trail across the Murang'a county workspace</p>
        </div>
        <button className="btn-secondary flex items-center gap-2"><Download size={14} /> Export Trail</button>
      </motion.div>

      {/* Log Table */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="glass-card p-5">
        <div className="flex items-center justify-between mb-4 flex-wrap gap-3">
          <div className="flex items-center gap-2 bg-slate-dark rounded-lg p-1">
            {LEVELS.map((l) => (
              <button
                key={l}
                onClick={() => setLevel(l)}
                className={cn(
                  'px-3.5 py-1.5 rounded-md text-xs font-semibold capitalize transition-all',
                  level === l ? 'bg-violet text-white shadow-sm' : 'text-text-muted hover:text-white'
                )}
              >
                {l}
              </button>
            ))}
          </div>
          <span className="text-xs text-text-muted flex items-center gap-1.5">
            <ScrollText size={12} /> {logs.length} events · retained 12 months
          </span>
        </div>

        <div className="space-y-2">
          {logs.map((log, i) => {
            const LevelIcon = LEVEL_STYLE[log.level].icon;
            return (
              <motion.div
                key={log.id}
                initial={{ opacity: 0, x: -12 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: i * 0.04 }}
                className="flex items-center gap-4 p-3.5 rounded-xl bg-slate-dark/60 border border-border hover:border-border-glow transition-all"
              >
                <span className={cn('pill-badge !text-[10px] shrink-0', LEVEL_STYLE[log.level].badge)}>
                  <LevelIcon size={10} className="mr-1" /> {log.level}
                </span>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-sm font-bold text-white font-mono">{log.action}</span>
                    <span className="text-xs text-text-secondary truncate">{log.target}</span>
                  </div>
                  <div className="text-[11px] text-text-muted mt-0.5">{log.detail}</div>
                </div>
                <div className="text-right shrink-0">
                  <div className="text-[11px] text-cyan font-mono">{log.actor}</div>
                  <div className="text-[10px] text-text-muted mt-0.5">
                    {log.time} · {log.ip}
                  </div>
                </div>
              </motion.div>
            );
          })}
        </div>
      </motion.div>
    </div>
  );
}
