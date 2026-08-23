import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { Plus, Package, Boxes, TrendingUp, DollarSign } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import DataTable from '../components/DataTable';
import { mockProducts } from '../lib/mock-data';
import { cn } from '../lib/utils';

const KPIS = [
  { label: 'Active Products', value: '13 SKUs', icon: Package, color: 'text-violet', change: '+2 this month', trend: 'up' as const },
  { label: 'Total Stock', value: '1,409 Units', icon: Boxes, color: 'text-cyan', change: '6 vendor co-ops', trend: 'up' as const },
  { label: 'Units Sold (Aug)', value: '5,390', icon: TrendingUp, color: 'text-emerald', change: '+24.6% vs July', trend: 'up' as const },
  { label: 'Product Revenue', value: 'KES 1.4M', icon: DollarSign, color: 'text-amber', change: '+19.3% vs July', trend: 'up' as const },
];

const CATEGORIES = ['all', 'Beverages', 'Farm Produce', 'Crafts'] as const;

export default function Products() {
  const [category, setCategory] = useState<(typeof CATEGORIES)[number]>('all');

  const data = useMemo(
    () => mockProducts.filter((p) => category === 'all' || p.category === category),
    [category]
  );

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">County Products</h1>
          <p className="text-sm text-text-muted">Authentic Murang'a produce, crafts and cooperative catalog</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Plus size={15} /> Add Product</button>
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
            {CATEGORIES.map((c) => (
              <button
                key={c}
                onClick={() => setCategory(c)}
                className={cn(
                  'px-3.5 py-1.5 rounded-md text-xs font-semibold capitalize transition-all',
                  category === c ? 'bg-violet text-white shadow-sm' : 'text-text-muted hover:text-white'
                )}
              >
                {c}
              </button>
            ))}
          </div>
          <span className="text-xs text-text-muted">{data.length} product{data.length !== 1 ? 's' : ''}</span>
        </div>
        <DataTable data={data} />
        <div className="mt-4 p-3 rounded-lg bg-emerald/5 border border-emerald/20 text-xs text-text-secondary leading-relaxed">
          💡 Products with the <span className="text-emerald font-semibold">KICC Certified Origin</span> badge sell 2.3× faster. Keep vendor cooperative details up to date for automatic certification.
        </div>
      </motion.div>
    </div>
  );
}
