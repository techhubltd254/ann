import { useState } from 'react';
import { motion } from 'framer-motion';
import { AreaChart, Area, PieChart, Pie, Cell, ResponsiveContainer, XAxis, YAxis, Tooltip, CartesianGrid } from 'recharts';
import { TrendingUp, TrendingDown, Package, MapPin, Hotel, ShoppingBag, ShoppingCart, DollarSign, Image, Crown, ArrowUpRight, ChevronRight, Video } from 'lucide-react';
import { formatKES, getStatusColor, cn } from '../lib/utils';
import { revenueData, sectorDistribution, mockAttractions, mockHotels, mockProducts, mockOrders, mock4DScenes } from '../lib/mock-data';
import KpiCard from '../components/KpiCard';
import QuickAction from '../components/QuickAction';
import DataTable from '../components/DataTable';

const revenue = [
  { label: 'County Products', value: '13 Active', icon: Package, color: 'text-violet', change: '+2 this month', trend: 'up' },
  { label: 'Attractions', value: '9 Sites', icon: MapPin, color: 'text-cyan', change: 'Twin Falls, Aberdare routes', trend: 'up' },
  { label: 'Hotels & Stays', value: '4 Partner Hotels', icon: Hotel, color: 'text-emerald', change: 'Avg. occupancy: 84%', trend: 'up' },
  { label: 'Marketplace Listings', value: '18 Active Items', icon: ShoppingBag, color: 'text-amber', change: '+3 this week', trend: 'up' },
  { label: 'Orders & Bookings', value: '142 Completed', icon: ShoppingCart, color: 'text-rose', change: 'KES 1.2M volume', trend: 'up' },
  { label: 'Total Revenue', value: formatKES(4820000), icon: DollarSign, color: 'text-emerald', change: '+18.4% vs Q3', trend: 'up' },
  { label: 'Managed Media', value: '128 High-Res Assets', icon: Image, color: 'text-violet', change: 'RAW/CR3 converted', trend: 'up' },
  { label: 'Available Packages', value: '4 Active Tiers', icon: Crown, color: 'text-amber', change: 'Tourism, Expo, Stays, Agri', trend: 'up' },
];

export default function Overview() {
  const [activeTab, setActiveTab] = useState('attractions');

  const tableData = {
    attractions: mockAttractions,
    hotels: mockHotels,
    products: mockProducts,
    orders: mockOrders,
  };

  const tabs = [
    { key: 'attractions', label: 'Top Attractions' },
    { key: 'hotels', label: 'Partner Hotels' },
    { key: 'products', label: 'Marketplace Products' },
    { key: 'orders', label: 'Recent Orders' },
  ];

  return (
    <div className="p-6 space-y-6">
      {/* KPI Grid */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        className="grid grid-cols-4 gap-4"
      >
        {revenue.map((kpi, i) => (
          <KpiCard key={kpi.label} {...kpi} index={i} />
        ))}
      </motion.div>

      {/* 4D Media Studio */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="glass-card p-6"
      >
        <div className="flex items-center justify-between mb-4">
          <div>
            <h3 className="text-lg font-bold text-white">4D Volumetric Media & Cinematic Journey Studio</h3>
            <p className="text-sm text-text-muted">Gaussian splatting & scroll-triggered cinematic experiences</p>
          </div>
          <div className="flex items-center gap-2">
            <span className="pill-badge badge-active">Engine: WebGPU</span>
            <span className="pill-badge badge-featured">60 FPS</span>
            <span className="pill-badge bg-cyan-500/15 text-cyan-400 border border-cyan-500/30">Scroll-Sync</span>
          </div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          {mock4DScenes.map((scene) => (
            <div key={scene.id} className="glass-card-hover p-4 rounded-xl cursor-pointer group">
              <div className="relative overflow-hidden rounded-lg mb-3 h-40 bg-gradient-to-br from-slate-dark to-deep">
                <div className="absolute inset-0 flex items-center justify-center">
                  <div className="w-16 h-16 rounded-full bg-violet/20 flex items-center justify-center">
                    <div className="w-10 h-10 rounded-full bg-violet/40 flex items-center justify-center animate-pulse">
                      <Video size={20} className="text-violet" />
                    </div>
                  </div>
                </div>
                <div className="absolute bottom-2 left-2 right-2 flex justify-between">
                  <span className="pill-badge bg-black/60 text-white text-[10px]">{scene.duration}</span>
                  <span className="pill-badge bg-black/60 text-white text-[10px]">{scene.resolution}</span>
                </div>
              </div>
              <h4 className="text-sm font-semibold text-white mb-1 group-hover:text-violet transition-colors">{scene.title}</h4>
              <p className="text-xs text-text-muted mb-2">{scene.subtitle}</p>
              <div className="flex items-center justify-between text-[10px] text-text-muted">
                <span>{scene.engine}</span>
                <span>{scene.frames} frames</span>
              </div>
            </div>
          ))}
        </div>
      </motion.div>

      {/* Operations Center */}
      <div className="grid grid-cols-3 gap-6">
        {/* Revenue Chart */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="col-span-2 glass-card p-5"
        >
          <div className="flex items-center justify-between mb-4">
            <div>
              <h3 className="text-base font-bold text-white">Revenue & Booking Performance</h3>
              <p className="text-xs text-text-muted">Monthly revenue split across all channels (KES)</p>
            </div>
            <div className="flex items-center gap-4 text-xs">
              <span className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-emerald"></span> Tourism Bookings</span>
              <span className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-cyan"></span> Marketplace</span>
              <span className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-violet"></span> Advertising</span>
            </div>
          </div>
          <ResponsiveContainer width="100%" height={280}>
            <AreaChart data={revenueData}>
              <defs>
                <linearGradient id="colorRev" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#10B981" stopOpacity={0.2}/>
                  <stop offset="95%" stopColor="#10B981" stopOpacity={0}/>
                </linearGradient>
                <linearGradient id="colorMarket" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#06B6D4" stopOpacity={0.2}/>
                  <stop offset="95%" stopColor="#06B6D4" stopOpacity={0}/>
                </linearGradient>
                <linearGradient id="colorAds" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#6366F1" stopOpacity={0.2}/>
                  <stop offset="95%" stopColor="#6366F1" stopOpacity={0}/>
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" />
              <XAxis dataKey="month" stroke="#64748B" fontSize={11} />
              <YAxis stroke="#64748B" fontSize={11} tickFormatter={(v) => `${v/1000}K`} />
              <Tooltip
                contentStyle={{ background: '#161A22', border: '1px solid rgba(255,255,255,0.07)', borderRadius: '8px' }}
                labelStyle={{ color: '#94A3B8' }}
                itemStyle={{ color: '#F1F5F9' }}
              />
              <Area type="monotone" dataKey="tourism" stroke="#10B981" fill="url(#colorRev)" strokeWidth={2} dot={false} />
              <Area type="monotone" dataKey="marketplace" stroke="#06B6D4" fill="url(#colorMarket)" strokeWidth={2} dot={false} />
              <Area type="monotone" dataKey="advertising" stroke="#6366F1" fill="url(#colorAds)" strokeWidth={2} dot={false} />
            </AreaChart>
          </ResponsiveContainer>
        </motion.div>

        {/* Sector Distribution */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="glass-card p-5"
        >
          <h3 className="text-base font-bold text-white mb-1">Sector Distribution</h3>
          <p className="text-xs text-text-muted mb-4">Allocation across economic sectors</p>
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
        </motion.div>
      </div>

      {/* Quick Actions */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.4 }}
        className="glass-card p-4"
      >
        <div className="flex items-center gap-3 flex-wrap">
          <QuickAction icon="✏️" label="Edit Editorial CMS" href="/content" />
          <QuickAction icon="🖼️" label="Batch Media Manager" href="/gallery" />
          <QuickAction icon="💰" label="Adjust Dynamic Pricing" href="/pricing" />
          <QuickAction icon="📢" label="Launch Ad Campaign" href="/ads" />
          <QuickAction icon="🚀" label="Manage Subscriptions" href="/packages" />
          <QuickAction icon="🎬" label="New 4D Scene" href="/videos4d" variant="primary" />
        </div>
      </motion.div>

      {/* Data Table */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.5 }}
        className="glass-card p-5"
      >
        <div className="flex items-center justify-between mb-4">
          <div>
            <h3 className="text-base font-bold text-white">Resource Management</h3>
            <p className="text-xs text-text-muted">Manage attractions, hotels, products, and orders</p>
          </div>
          <div className="flex items-center gap-2 bg-slate-dark rounded-lg p-1">
            {tabs.map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key)}
                className={cn(
                  'px-4 py-1.5 rounded-md text-xs font-semibold transition-all',
                  activeTab === tab.key
                    ? 'bg-violet text-white shadow-sm'
                    : 'text-text-muted hover:text-white'
                )}
              >
                {tab.label}
              </button>
            ))}
          </div>
        </div>
        <DataTable data={tableData[activeTab as keyof typeof tableData]} />
      </motion.div>
    </div>
  );
}