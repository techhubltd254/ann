import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { Plus, MapPin, Users, Star, DollarSign, Download } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import DataTable from '../components/DataTable';
import { mockAttractions } from '../lib/mock-data';
import { cn } from '../lib/utils';

const KPIS = [
  { label: 'Total Sites', value: '9 Attractions', icon: MapPin, color: 'text-cyan', change: '5 managed · 4 partner', trend: 'up' as const },
  { label: 'Monthly Visitors', value: '35,900', icon: Users, color: 'text-violet', change: '+14.2% vs July', trend: 'up' as const },
  { label: 'Avg. Rating', value: '4.7 / 5.0', icon: Star, color: 'text-amber', change: 'Across 2,140 reviews', trend: 'up' as const },
  { label: 'Ticket Revenue', value: 'KES 842K', icon: DollarSign, color: 'text-emerald', change: '+22.8% vs July', trend: 'up' as const },
];

const FILTERS = ['all', 'active', 'featured', 'review', 'pending'] as const;

export default function Attractions() {
  const [filter, setFilter] = useState<(typeof FILTERS)[number]>('all');
  const [query, setQuery] = useState('');

  const data = useMemo(
    () =>
      mockAttractions.filter(
        (a) =>
          (filter === 'all' || a.status === filter) &&
          (a.name.toLowerCase().includes(query.toLowerCase()) ||
            a.location.toLowerCase().includes(query.toLowerCase()))
      ),
    [filter, query]
  );

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Attractions & Sites</h1>
          <p className="text-sm text-text-muted">Manage tourism attractions across Murang'a County</p>
        </div>
        <div className="flex gap-2">
          <button className="btn-secondary flex items-center gap-2"><Download size={14} /> Export</button>
          <button className="btn-primary flex items-center gap-2"><Plus size={15} /> Add Attraction</button>
        </div>
      </motion.div>

      {/* KPI Row */}
      <div className="grid grid-cols-4 gap-4">
        {KPIS.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </div>

      {/* Table */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="glass-card p-5">
        <div className="flex items-center justify-between mb-4 flex-wrap gap-3">
          <div className="flex items-center gap-2 bg-slate-dark rounded-lg p-1">
            {FILTERS.map((f) => (
              <button
                key={f}
                onClick={() => setFilter(f)}
                className={cn(
                  'px-3.5 py-1.5 rounded-md text-xs font-semibold capitalize transition-all',
                  filter === f ? 'bg-violet text-white shadow-sm' : 'text-text-muted hover:text-white'
                )}
              >
                {f}
              </button>
            ))}
          </div>
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Filter by name or location..."
            className="bg-slate-dark border border-border rounded-lg px-3 py-2 text-sm text-white placeholder:text-text-muted outline-none focus:border-violet/50 w-64 transition-all"
          />
        </div>
        <DataTable data={data} />
      </motion.div>
    </div>
  );
}
