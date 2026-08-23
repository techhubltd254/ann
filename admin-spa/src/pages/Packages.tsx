import { useState } from 'react';
import { motion } from 'framer-motion';
import { Crown, Check, Users } from 'lucide-react';
import { mockPackages } from '../lib/mock-data';
import { cn, formatKES } from '../lib/utils';

export default function Packages() {
  const [active, setActive] = useState<Record<string, boolean>>(
    Object.fromEntries(mockPackages.map((p) => [p.id, p.status === 'active' || p.status === 'featured']))
  );

  const totalMRR = mockPackages
    .filter((p) => active[p.id])
    .reduce((sum, p) => sum + p.price * p.subscribers, 0);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Subscription Packages</h1>
          <p className="text-sm text-text-muted">Vendor and institution subscription tiers on the KICC platform</p>
        </div>
        <div className="glass-card px-4 py-2 flex items-center gap-3">
          <Crown size={16} className="text-amber" />
          <div>
            <div className="text-[10px] text-text-muted uppercase tracking-wider">Active MRR</div>
            <div className="text-sm font-black text-white">{formatKES(totalMRR)}</div>
          </div>
        </div>
      </motion.div>

      {/* Tier Cards */}
      <div className="grid grid-cols-4 gap-4">
        {mockPackages.map((pkg, i) => {
          const isFeatured = pkg.status === 'featured';
          const isActive = active[pkg.id];
          return (
            <motion.div
              key={pkg.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.06 }}
              className={cn(
                'glass-card p-5 relative flex flex-col transition-all',
                isFeatured && '!border-violet/40 shadow-xl shadow-violet/10',
                !isActive && 'opacity-60'
              )}
            >
              {isFeatured && (
                <span className="absolute -top-2.5 left-1/2 -translate-x-1/2 pill-badge badge-featured !text-[10px]">
                  Most Popular
                </span>
              )}
              <div className="flex items-center justify-between mb-3">
                <span className="text-3xl">{pkg.emoji}</span>
                <button
                  onClick={() => setActive((a) => ({ ...a, [pkg.id]: !a[pkg.id] }))}
                  className={cn('w-11 h-6 rounded-full relative transition-colors', isActive ? 'bg-violet' : 'bg-surface-light')}
                  title={isActive ? 'Deactivate tier' : 'Activate tier'}
                >
                  <motion.span
                    animate={{ x: isActive ? 22 : 2 }}
                    transition={{ type: 'spring', stiffness: 500, damping: 30 }}
                    className="absolute top-1 w-4 h-4 rounded-full bg-white shadow"
                  />
                </button>
              </div>
              <h4 className="text-base font-bold text-white mb-1">{pkg.name}</h4>
              <div className="flex items-baseline gap-1 mb-4">
                <span className={cn('text-2xl font-black', isFeatured ? 'text-gradient-violet' : 'text-white')}>
                  {formatKES(pkg.price)}
                </span>
                <span className="text-xs text-text-muted">{pkg.period}</span>
              </div>
              <ul className="space-y-2 flex-1 mb-4">
                {pkg.features.map((f) => (
                  <li key={f} className="flex items-start gap-2 text-xs text-text-secondary">
                    <Check size={12} className={cn('mt-0.5 shrink-0', isFeatured ? 'text-violet' : 'text-emerald')} />
                    {f}
                  </li>
                ))}
              </ul>
              <div className="flex items-center justify-between pt-3 border-t border-border">
                <span className="flex items-center gap-1.5 text-[11px] text-text-muted">
                  <Users size={11} /> {pkg.subscribers} subscribers
                </span>
                <span className="text-[11px] font-semibold text-emerald">
                  {formatKES(pkg.price * pkg.subscribers)}/mo
                </span>
              </div>
            </motion.div>
          );
        })}
      </div>

      {/* Subscriber Mix */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="glass-card p-5">
        <h3 className="text-base font-bold text-white mb-1">Subscriber Mix</h3>
        <p className="text-xs text-text-muted mb-4">270 active subscriptions across all tiers</p>
        <div className="flex h-4 rounded-full overflow-hidden gap-0.5">
          {[
            { name: 'Tourism Starter', n: 128, color: '#06B6D4' },
            { name: 'Agri Market Plus', n: 84, color: '#10B981' },
            { name: 'Hospitality Pro', n: 46, color: '#6366F1' },
            { name: 'County Enterprise', n: 12, color: '#F59E0B' },
          ].map((seg) => (
            <motion.div
              key={seg.name}
              initial={{ flexGrow: 0 }}
              animate={{ flexGrow: seg.n }}
              transition={{ duration: 0.8 }}
              style={{ backgroundColor: seg.color }}
              className="h-full rounded-sm"
              title={`${seg.name}: ${seg.n}`}
            />
          ))}
        </div>
        <div className="flex items-center gap-5 mt-3 text-xs">
          {[
            { name: 'Starter', n: 128, color: '#06B6D4' },
            { name: 'Agri Plus', n: 84, color: '#10B981' },
            { name: 'Hospitality Pro', n: 46, color: '#6366F1' },
            { name: 'Enterprise', n: 12, color: '#F59E0B' },
          ].map((seg) => (
            <span key={seg.name} className="flex items-center gap-1.5 text-text-secondary">
              <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: seg.color }} />
              {seg.name} <span className="text-white font-semibold">{seg.n}</span>
            </span>
          ))}
        </div>
      </motion.div>
    </div>
  );
}
