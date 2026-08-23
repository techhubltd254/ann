import { motion } from 'framer-motion';
import { AreaChart, Area, BarChart, Bar, ResponsiveContainer, XAxis, YAxis, Tooltip, CartesianGrid, Cell } from 'recharts';
import { Users, Eye, MousePointerClick, Clock } from 'lucide-react';
import KpiCard from '../components/KpiCard';
import { trafficData, channelData } from '../lib/mock-data';

const KPIS = [
  { label: 'Portal Visitors (7d)', value: '27,450', icon: Users, color: 'text-violet', change: '+12.6% vs last week', trend: 'up' as const },
  { label: 'Page Views (7d)', value: '81,390', icon: Eye, color: 'text-cyan', change: '+9.2% vs last week', trend: 'up' as const },
  { label: 'Booking Conversions', value: '482', icon: MousePointerClick, color: 'text-emerald', change: '+18.1% vs last week', trend: 'up' as const },
  { label: 'Avg. Session', value: '4m 32s', icon: Clock, color: 'text-amber', change: '-0.4% vs last week', trend: 'down' as const },
];

const TOP_PAGES = [
  { path: '/counties/muranga/attractions/twin-falls', views: '12,480', unique: '9,820', bounce: '28%' },
  { path: '/counties/muranga', views: '10,214', unique: '8,104', bounce: '34%' },
  { path: '/counties/muranga/hotels/elipa-hotel', views: '7,402', unique: '5,912', bounce: '31%' },
  { path: '/counties/muranga/products/muranga-tea', views: '6,188', unique: '5,207', bounce: '25%' },
  { path: '/counties/muranga/experiences/sagana-rafting', views: '4,960', unique: '4,311', bounce: '37%' },
];

export default function Analytics() {
  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Analytics & Performance</h1>
          <p className="text-sm text-text-muted">Portal traffic, engagement and conversion metrics for Murang'a County</p>
        </div>
        <div className="flex items-center gap-2">
          <span className="pill-badge badge-active">Live data</span>
          <button className="btn-secondary">Export Analytics</button>
        </div>
      </motion.div>

      {/* KPI Row */}
      <div className="grid grid-cols-4 gap-4">
        {KPIS.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </div>

      {/* Traffic Chart */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="glass-card p-5">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h3 className="text-base font-bold text-white">Weekly Traffic & Bookings</h3>
            <p className="text-xs text-text-muted">Visitors, pageviews and booking conversions per day</p>
          </div>
          <div className="flex items-center gap-4 text-xs">
            <span className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-violet"></span> Visitors</span>
            <span className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-cyan"></span> Pageviews</span>
          </div>
        </div>
        <ResponsiveContainer width="100%" height={260}>
          <AreaChart data={trafficData}>
            <defs>
              <linearGradient id="anaVis" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#6366F1" stopOpacity={0.25} />
                <stop offset="95%" stopColor="#6366F1" stopOpacity={0} />
              </linearGradient>
              <linearGradient id="anaPv" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#06B6D4" stopOpacity={0.2} />
                <stop offset="95%" stopColor="#06B6D4" stopOpacity={0} />
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" />
            <XAxis dataKey="day" stroke="#64748B" fontSize={11} />
            <YAxis stroke="#64748B" fontSize={11} />
            <Tooltip
              contentStyle={{ background: '#161A22', border: '1px solid rgba(255,255,255,0.07)', borderRadius: '8px' }}
              labelStyle={{ color: '#94A3B8' }}
              itemStyle={{ color: '#F1F5F9' }}
            />
            <Area type="monotone" dataKey="pageviews" stroke="#06B6D4" fill="url(#anaPv)" strokeWidth={2} dot={false} />
            <Area type="monotone" dataKey="visitors" stroke="#6366F1" fill="url(#anaVis)" strokeWidth={2} dot={false} />
          </AreaChart>
        </ResponsiveContainer>
      </motion.div>

      <div className="grid grid-cols-5 gap-6">
        {/* Channels */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.25 }} className="col-span-2 glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Acquisition Channels</h3>
          <p className="text-xs text-text-muted mb-4">Sessions by source · last 30 days</p>
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={channelData} layout="vertical">
              <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" horizontal={false} />
              <XAxis type="number" stroke="#64748B" fontSize={11} />
              <YAxis type="category" dataKey="channel" stroke="#64748B" fontSize={10} width={90} />
              <Tooltip
                contentStyle={{ background: '#161A22', border: '1px solid rgba(255,255,255,0.07)', borderRadius: '8px' }}
                labelStyle={{ color: '#94A3B8' }}
                itemStyle={{ color: '#F1F5F9' }}
              />
              <Bar dataKey="sessions" radius={[0, 4, 4, 0]}>
                {channelData.map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={entry.color} />
                ))}
              </Bar>
            </BarChart>
          </ResponsiveContainer>
          <div className="mt-3 space-y-1.5">
            {channelData.map((c) => (
              <div key={c.channel} className="flex items-center justify-between text-xs">
                <span className="text-text-secondary">{c.channel}</span>
                <span className="text-emerald font-semibold">{c.conversion}% conv.</span>
              </div>
            ))}
          </div>
        </motion.div>

        {/* Top Pages */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="col-span-3 glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Top Pages</h3>
          <p className="text-xs text-text-muted mb-4">Most visited county portal routes</p>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left py-2.5 text-[11px] font-bold text-text-muted uppercase tracking-wider">Path</th>
                <th className="text-right py-2.5 text-[11px] font-bold text-text-muted uppercase tracking-wider">Views</th>
                <th className="text-right py-2.5 text-[11px] font-bold text-text-muted uppercase tracking-wider">Unique</th>
                <th className="text-right py-2.5 text-[11px] font-bold text-text-muted uppercase tracking-wider">Bounce</th>
              </tr>
            </thead>
            <tbody>
              {TOP_PAGES.map((p) => (
                <tr key={p.path} className="border-b border-border/50 hover:bg-surface-hover/60 transition-colors">
                  <td className="py-3 font-mono text-xs text-cyan truncate max-w-[280px]">{p.path}</td>
                  <td className="py-3 text-right text-white font-semibold">{p.views}</td>
                  <td className="py-3 text-right text-text-secondary">{p.unique}</td>
                  <td className="py-3 text-right text-text-secondary">{p.bounce}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </motion.div>
      </div>
    </div>
  );
}
