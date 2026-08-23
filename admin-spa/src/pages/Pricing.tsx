import { useState } from 'react';
import { motion } from 'framer-motion';
import { AreaChart, Area, ResponsiveContainer, XAxis, YAxis, Tooltip, CartesianGrid } from 'recharts';
import { Plus, DollarSign, Percent, Zap, TrendingUp } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import { mockPricingRules, revenueData } from '../lib/mock-data';
import { cn, getStatusColor } from '../lib/utils';

const KPIS = [
  { label: 'Total Revenue (Aug)', value: 'KES 4.8M', icon: DollarSign, color: 'text-emerald', change: '+18.4% vs Q3', trend: 'up' as const },
  { label: 'Active Price Rules', value: '5 Rules', icon: Percent, color: 'text-violet', change: '3 automated', trend: 'up' as const },
  { label: 'Dynamic Uplift', value: '+KES 280K', icon: Zap, color: 'text-amber', change: 'From peak pricing', trend: 'up' as const },
  { label: 'Avg. Yield / Order', value: 'KES 8,450', icon: TrendingUp, color: 'text-cyan', change: '+6.1% vs July', trend: 'up' as const },
];

export default function Pricing() {
  const [enabled, setEnabled] = useState<Record<string, boolean>>(
    Object.fromEntries(mockPricingRules.map((r) => [r.id, r.status === 'active']))
  );

  const toggleRule = (id: string) => setEnabled((e) => ({ ...e, [id]: !e[id] }));

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Revenue & Pricing Engine</h1>
          <p className="text-sm text-text-muted">Dynamic pricing rules, seasonal modifiers and revenue intelligence</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Plus size={15} /> New Price Rule</button>
      </motion.div>

      {/* KPI Row */}
      <div className="grid grid-cols-4 gap-4">
        {KPIS.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </div>

      <div className="grid grid-cols-5 gap-6">
        {/* Rules */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.15 }} className="col-span-3 glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Pricing Rules</h3>
          <p className="text-xs text-text-muted mb-4">Toggle rules to apply them across the booking and marketplace engines</p>
          <div className="space-y-3">
            {mockPricingRules.map((rule) => {
              const on = enabled[rule.id];
              return (
                <div
                  key={rule.id}
                  className={cn(
                    'flex items-center gap-4 p-4 rounded-xl border transition-all',
                    on ? 'border-violet/30 bg-violet/5' : 'border-border bg-slate-dark/50 opacity-70'
                  )}
                >
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="text-sm font-bold text-white">{rule.name}</span>
                      <span className={cn('pill-badge capitalize', getStatusColor(rule.status))}>{rule.status}</span>
                    </div>
                    <div className="text-[11px] text-text-muted">
                      {rule.target} · {rule.window}
                    </div>
                    <div className="text-[11px] text-emerald font-semibold mt-1">{rule.impact}</div>
                  </div>
                  <span
                    className={cn(
                      'text-lg font-black shrink-0',
                      rule.modifier.startsWith('+') ? 'text-emerald' : 'text-amber'
                    )}
                  >
                    {rule.modifier}
                  </span>
                  <button
                    onClick={() => toggleRule(rule.id)}
                    className={cn(
                      'w-11 h-6 rounded-full relative transition-colors shrink-0',
                      on ? 'bg-violet' : 'bg-surface-light'
                    )}
                  >
                    <motion.span
                      animate={{ x: on ? 22 : 2 }}
                      transition={{ type: 'spring', stiffness: 500, damping: 30 }}
                      className="absolute top-1 w-4 h-4 rounded-full bg-white shadow"
                    />
                  </button>
                </div>
              );
            })}
          </div>
        </motion.div>

        {/* Revenue Impact Chart */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="col-span-2 glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Revenue Trajectory</h3>
          <p className="text-xs text-text-muted mb-4">Combined monthly revenue · KES</p>
          <ResponsiveContainer width="100%" height={220}>
            <AreaChart data={revenueData.map((d) => ({ month: d.month, total: d.tourism + d.marketplace + d.advertising }))}>
              <defs>
                <linearGradient id="prcTotal" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#10B981" stopOpacity={0.3} />
                  <stop offset="95%" stopColor="#10B981" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" />
              <XAxis dataKey="month" stroke="#64748B" fontSize={10} />
              <YAxis stroke="#64748B" fontSize={10} tickFormatter={(v) => `${(v / 1000000).toFixed(1)}M`} />
              <Tooltip
                contentStyle={{ background: '#161A22', border: '1px solid rgba(255,255,255,0.07)', borderRadius: '8px' }}
                labelStyle={{ color: '#94A3B8' }}
                itemStyle={{ color: '#F1F5F9' }}
                formatter={(v: number) => [`KES ${(v / 1000).toFixed(0)}K`, 'Total']}
              />
              <Area type="monotone" dataKey="total" stroke="#10B981" fill="url(#prcTotal)" strokeWidth={2} dot={false} />
            </AreaChart>
          </ResponsiveContainer>
          <div className="mt-4 p-3 rounded-lg bg-emerald/5 border border-emerald/20 text-[11px] text-text-secondary leading-relaxed">
            <span className="text-emerald font-semibold">Projection:</span> enabling the Harvest Season Promo is forecast to lift Q4 marketplace revenue by <span className="text-white font-semibold">KES 410K</span>.
          </div>
        </motion.div>
      </div>
    </div>
  );
}
