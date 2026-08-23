import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { Plus, ShoppingBag, Store, ClipboardList, DollarSign } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import DataTable from '../components/DataTable';
import { mockListings } from '../lib/mock-data';
import { cn } from '../lib/utils';

const KPIS = [
  { label: 'Live Listings', value: '18 Items', icon: ShoppingBag, color: 'text-amber', change: '+3 this week', trend: 'up' as const },
  { label: 'Registered Vendors', value: '26 Co-ops', icon: Store, color: 'text-cyan', change: '12 verified', trend: 'up' as const },
  { label: 'Orders Fulfilled', value: '1,020', icon: ClipboardList, color: 'text-violet', change: '96.4% fulfilment rate', trend: 'up' as const },
  { label: 'Marketplace GMV', value: 'KES 2.1M', icon: DollarSign, color: 'text-emerald', change: '+21.7% vs July', trend: 'up' as const },
];

const TABS = ['all', 'active', 'featured', 'review', 'pending'] as const;

export default function Marketplace() {
  const [tab, setTab] = useState<(typeof TABS)[number]>('all');

  const data = useMemo(
    () => mockListings.filter((l) => tab === 'all' || l.status === tab),
    [tab]
  );

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Marketplace Listings</h1>
          <p className="text-sm text-text-muted">Vendor storefronts, experiences and product listings on the county marketplace</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Plus size={15} /> New Listing</button>
      </motion.div>

      {/* KPI Row */}
      <div className="grid grid-cols-4 gap-4">
        {KPIS.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </div>

      {/* Listings Table */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="glass-card p-5">
        <div className="flex items-center justify-between mb-4 flex-wrap gap-3">
          <div className="flex items-center gap-2 bg-slate-dark rounded-lg p-1">
            {TABS.map((t) => (
              <button
                key={t}
                onClick={() => setTab(t)}
                className={cn(
                  'px-3.5 py-1.5 rounded-md text-xs font-semibold capitalize transition-all',
                  tab === t ? 'bg-violet text-white shadow-sm' : 'text-text-muted hover:text-white'
                )}
              >
                {t}
              </button>
            ))}
          </div>
          <span className="text-xs text-text-muted">{data.length} listing{data.length !== 1 ? 's' : ''}</span>
        </div>
        <DataTable data={data} />
      </motion.div>
    </div>
  );
}
