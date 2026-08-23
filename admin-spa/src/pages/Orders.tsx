import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { ShoppingCart, DollarSign, Clock, CheckCircle2, Download } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import DataTable from '../components/DataTable';
import { mockOrders } from '../lib/mock-data';
import { cn } from '../lib/utils';

const KPIS = [
  { label: 'Total Orders (Aug)', value: '142', icon: ShoppingCart, color: 'text-rose', change: '+18 vs July', trend: 'up' as const },
  { label: 'Order Volume', value: 'KES 1.2M', icon: DollarSign, color: 'text-emerald', change: '+22.4% vs July', trend: 'up' as const },
  { label: 'Pending Fulfilment', value: '7 Orders', icon: Clock, color: 'text-amber', change: '-3 vs yesterday', trend: 'down' as const },
  { label: 'Completed', value: '128 Orders', icon: CheckCircle2, color: 'text-cyan', change: '90.1% completion', trend: 'up' as const },
];

const TABS = ['all', 'active', 'review', 'pending'] as const;

export default function Orders() {
  const [tab, setTab] = useState<(typeof TABS)[number]>('all');
  const [typeFilter, setTypeFilter] = useState<'all' | 'Booking' | 'Product'>('all');

  const data = useMemo(
    () =>
      mockOrders.filter(
        (o) => (tab === 'all' || o.status === tab) && (typeFilter === 'all' || o.type === typeFilter)
      ),
    [tab, typeFilter]
  );

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Orders & Transactions</h1>
          <p className="text-sm text-text-muted">Bookings, product orders and payment transactions in KES</p>
        </div>
        <button className="btn-secondary flex items-center gap-2"><Download size={14} /> Export CSV</button>
      </motion.div>

      {/* KPI Row */}
      <div className="grid grid-cols-4 gap-4">
        {KPIS.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </div>

      {/* Orders Table */}
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
          <div className="flex items-center gap-2 bg-slate-dark rounded-lg p-1">
            {(['all', 'Booking', 'Product'] as const).map((t) => (
              <button
                key={t}
                onClick={() => setTypeFilter(t)}
                className={cn(
                  'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all',
                  typeFilter === t ? 'bg-cyan text-white shadow-sm' : 'text-text-muted hover:text-white'
                )}
              >
                {t}
              </button>
            ))}
          </div>
        </div>
        <DataTable data={data} />
        <div className="mt-4 flex items-center justify-between text-[11px] text-text-muted">
          <span>Payments settled via M-Pesa Daraja · KES</span>
          <span>Auto-reconciled nightly at 02:00 EAT</span>
        </div>
      </motion.div>
    </div>
  );
}
