import { motion } from 'framer-motion';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from 'recharts';
import { Plus, Building2, User } from 'lucide-react';
import { mockSectors, sectorDistribution } from '../lib/mock-data';
import { cn, getStatusColor } from '../lib/utils';

export default function Sectors() {
  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Institutions & Sectors</h1>
          <p className="text-sm text-text-muted">County sector bodies, associations and institutional partners</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Plus size={15} /> Register Institution</button>
      </motion.div>

      <div className="grid grid-cols-3 gap-6">
        {/* Sector Cards */}
        <div className="col-span-2 grid grid-cols-2 gap-4">
          {mockSectors.map((sector, i) => (
            <motion.div
              key={sector.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.05 }}
              className="glass-card glass-card-hover p-5 cursor-pointer group"
            >
              <div className="flex items-start justify-between mb-3">
                <div className="w-11 h-11 rounded-xl bg-gradient-to-br from-surface-light to-slate-dark border border-border flex items-center justify-center text-xl">
                  {sector.emoji}
                </div>
                <span className={cn('pill-badge capitalize', getStatusColor(sector.status))}>{sector.status}</span>
              </div>
              <h4 className="text-sm font-bold text-white mb-2 group-hover:text-violet transition-colors">{sector.name}</h4>
              <div className="space-y-1.5 text-[11px]">
                <div className="flex items-center justify-between">
                  <span className="text-text-muted flex items-center gap-1"><Building2 size={10} /> Institutions</span>
                  <span className="text-white font-semibold">{sector.institutions}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text-muted flex items-center gap-1"><User size={10} /> Sector Lead</span>
                  <span className="text-white font-semibold">{sector.lead}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text-muted">Annual Budget</span>
                  <span className="text-emerald font-semibold">{sector.budget}</span>
                </div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Distribution Chart */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Economic Distribution</h3>
          <p className="text-xs text-text-muted mb-4">Sector share of county platform activity</p>
          <ResponsiveContainer width="100%" height={200}>
            <PieChart>
              <Pie
                data={sectorDistribution}
                cx="50%"
                cy="50%"
                innerRadius={55}
                outerRadius={80}
                paddingAngle={3}
                dataKey="value"
              >
                {sectorDistribution.map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={entry.color} />
                ))}
              </Pie>
              <Tooltip
                contentStyle={{ background: '#161A22', border: '1px solid rgba(255,255,255,0.07)', borderRadius: '8px' }}
                labelStyle={{ color: '#94A3B8' }}
                itemStyle={{ color: '#F1F5F9' }}
              />
            </PieChart>
          </ResponsiveContainer>
          <div className="space-y-2">
            {sectorDistribution.map((s) => (
              <div key={s.name} className="flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <div className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: s.color }} />
                  <span className="text-text-secondary">{s.name}</span>
                </div>
                <span className="text-white font-semibold">{s.value}%</span>
              </div>
            ))}
          </div>
          <div className="mt-5 p-3 rounded-lg bg-violet/5 border border-violet/20 text-[11px] text-text-secondary leading-relaxed">
            <span className="text-violet font-semibold">161 institutions</span> registered across 6 sectors · onboarding for ICT & Innovation Hub opens Sept 2026.
          </div>
        </motion.div>
      </div>
    </div>
  );
}
