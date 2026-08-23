import { motion } from 'framer-motion';
import { Plus, Hotel, BedDouble, Percent, DollarSign, MapPin, Star } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import DataTable from '../components/DataTable';
import { mockHotels } from '../lib/mock-data';
import { cn, formatKES, getStatusColor } from '../lib/utils';

const KPIS = [
  { label: 'Partner Hotels', value: '4 Properties', icon: Hotel, color: 'text-emerald', change: '190 rooms total', trend: 'up' as const },
  { label: 'Avg. Occupancy', value: '81.8%', icon: Percent, color: 'text-cyan', change: '+6.2 pts vs July', trend: 'up' as const },
  { label: 'Room Nights (Aug)', value: '4,214', icon: BedDouble, color: 'text-violet', change: '+11.4% vs July', trend: 'up' as const },
  { label: 'Booking Revenue', value: 'KES 1.9M', icon: DollarSign, color: 'text-amber', change: '+16.9% vs July', trend: 'up' as const },
];

export default function Hotels() {
  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Hotels & Hospitality</h1>
          <p className="text-sm text-text-muted">Partner properties, occupancy and booking performance</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Plus size={15} /> Onboard Hotel</button>
      </motion.div>

      {/* KPI Row */}
      <div className="grid grid-cols-4 gap-4">
        {KPIS.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </div>

      {/* Hotel Cards */}
      <div className="grid grid-cols-4 gap-4">
        {mockHotels.map((hotel, i) => (
          <motion.div
            key={hotel.id}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.15 + i * 0.05 }}
            className="glass-card glass-card-hover overflow-hidden group cursor-pointer"
          >
            <div className="h-28 bg-gradient-to-br from-surface-light to-slate-dark flex items-center justify-center text-5xl relative">
              {hotel.emoji}
              <span className={cn('pill-badge absolute top-2 right-2 capitalize', getStatusColor(hotel.status))}>
                {hotel.status}
              </span>
            </div>
            <div className="p-4">
              <div className="flex items-center justify-between mb-1">
                <h4 className="text-sm font-bold text-white group-hover:text-violet transition-colors truncate">{hotel.name}</h4>
                <span className="flex items-center gap-0.5 text-amber text-xs font-semibold shrink-0">
                  <Star size={11} fill="currentColor" /> {hotel.stars}.0
                </span>
              </div>
              <div className="flex items-center gap-1 text-[11px] text-text-muted mb-3">
                <MapPin size={10} /> {hotel.location} · {hotel.rooms} rooms
              </div>
              <div className="flex justify-between text-[10px] mb-1.5">
                <span className="text-text-muted">Occupancy</span>
                <span className={cn('font-bold', hotel.occupancy >= 85 ? 'text-emerald' : hotel.occupancy >= 75 ? 'text-cyan' : 'text-amber')}>
                  {hotel.occupancy}%
                </span>
              </div>
              <div className="w-full h-1.5 bg-slate-dark rounded-full overflow-hidden mb-3">
                <motion.div
                  initial={{ width: 0 }}
                  animate={{ width: `${hotel.occupancy}%` }}
                  transition={{ delay: 0.4 + i * 0.05, duration: 0.6 }}
                  className={cn(
                    'h-full rounded-full',
                    hotel.occupancy >= 85 ? 'bg-gradient-to-r from-emerald to-cyan' : 'bg-gradient-to-r from-cyan to-violet'
                  )}
                />
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm font-black text-white">{formatKES(hotel.price)}</span>
                <span className="text-[10px] text-text-muted">per night</span>
              </div>
            </div>
          </motion.div>
        ))}
      </div>

      {/* Table */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.35 }} className="glass-card p-5">
        <h3 className="text-base font-bold text-white mb-1">Property Directory</h3>
        <p className="text-xs text-text-muted mb-4">All registered hospitality partners</p>
        <DataTable data={mockHotels} />
      </motion.div>
    </div>
  );
}
