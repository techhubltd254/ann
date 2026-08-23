import { useNavigate, useLocation } from 'react-router-dom';
import {
  LayoutDashboard, FileText, BarChart3, MapPin, Hotel, Package, ShoppingBag,
  Video, Film, Image, Edit3, ShoppingCart, DollarSign, Megaphone, Crown,
  Building2, FileBarChart, Settings, ScrollText, Globe, LogOut, ChevronRight
} from 'lucide-react';
import { cn } from '../lib/utils';

const navGroups = [
  {
    label: 'Overview',
    items: [
      { icon: LayoutDashboard, label: 'Overview / Dashboard', href: '/overview' },
      { icon: FileText, label: 'Details & General Info', href: '/details' },
      { icon: BarChart3, label: 'Analytics & Performance', href: '/analytics' },
    ],
  },
  {
    label: 'Tourism & Hospitality',
    items: [
      { icon: MapPin, label: 'Attractions & Sites', href: '/attractions' },
      { icon: Hotel, label: 'Hotels & Hospitality', href: '/hotels' },
      { icon: Package, label: 'County Products', href: '/products' },
      { icon: ShoppingBag, label: 'Marketplace Listings', href: '/marketplace' },
    ],
  },
  {
    label: 'Immersive Media & Content',
    items: [
      { icon: Video, label: '4D Gaussian Splatting', href: '/videos4d' },
      { icon: Film, label: 'Hero Videos & Cinematic', href: '/hero-videos' },
      { icon: Image, label: 'Photo Gallery & Images', href: '/gallery' },
      { icon: Edit3, label: 'Content & Editorial CMS', href: '/content' },
    ],
  },
  {
    label: 'Commercial Operations',
    items: [
      { icon: ShoppingCart, label: 'Orders & Transactions', href: '/orders' },
      { icon: DollarSign, label: 'Revenue & Pricing Engine', href: '/pricing' },
      { icon: Megaphone, label: 'Advertising & Campaigns', href: '/ads' },
      { icon: Crown, label: 'Subscription Packages', href: '/packages' },
    ],
  },
  {
    label: 'System & Administration',
    items: [
      { icon: Building2, label: 'Institutions & Sectors', href: '/sectors' },
      { icon: FileBarChart, label: 'Reports & Exports', href: '/reports' },
      { icon: Settings, label: 'Settings & Roles', href: '/settings' },
      { icon: ScrollText, label: 'Audit Logs', href: '/audit' },
    ],
  },
];

export default function Sidebar() {
  const navigate = useNavigate();
  const location = useLocation();

  return (
    <aside className="w-64 bg-slate-dark border-r border-border flex flex-col shrink-0 min-h-screen">
      {/* Header */}
      <div className="flex items-center gap-3 px-5 h-16 border-b border-border shrink-0">
        <div className="w-9 h-9 rounded-lg bg-gradient-to-br from-violet to-indigo flex items-center justify-center text-white font-black text-sm shadow-lg shadow-violet/20">M</div>
        <div>
          <div className="text-sm font-black text-white leading-tight">Murang'a County Admin</div>
          <div className="text-[10px] text-text-muted uppercase tracking-widest font-semibold">KICC Platform</div>
        </div>
      </div>

      {/* Nav */}
      <nav className="flex-1 overflow-y-auto py-3 px-3">
        {navGroups.map((group) => (
          <div key={group.label} className="mb-5">
            <div className="text-[10px] font-bold text-text-muted uppercase tracking-widest px-3 mb-2">{group.label}</div>
            {group.items.map((item) => {
              const isActive = location.pathname === item.href;
              const Icon = item.icon;
              return (
                <a
                  key={item.href}
                  href={item.href}
                  onClick={(e) => { e.preventDefault(); navigate(item.href); }}
                  className={cn(
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-0.5 transition-all',
                    isActive
                      ? 'text-white bg-gradient-to-r from-violet/20 to-indigo/10 border border-violet/30 shadow-sm shadow-violet/10'
                      : 'text-text-secondary hover:text-white hover:bg-surface-hover'
                  )}
                >
                  <Icon size={16} className={cn(isActive ? 'text-violet' : 'text-text-muted')} />
                  <span className="truncate">{item.label}</span>
                  {isActive && <ChevronRight size={14} className="ml-auto text-violet" />}
                </a>
              );
            })}
          </div>
        ))}
      </nav>

      {/* Plan Card */}
      <div className="p-4 mx-3 mb-3 glass-card">
        <div className="flex items-center gap-2 mb-3">
          <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald to-cyan flex items-center justify-center">
            <Crown size={14} className="text-white" />
          </div>
          <div>
            <div className="text-xs font-bold text-white">County Enterprise Tier</div>
            <div className="text-[10px] text-text-muted">Full access · 4D pipeline</div>
          </div>
        </div>
        <div className="space-y-1.5">
          <div className="flex justify-between text-[10px]">
            <span className="text-text-muted">API Calls</span>
            <span className="text-emerald">847 / 2,000</span>
          </div>
          <div className="w-full h-1.5 bg-surface rounded-full overflow-hidden">
            <div className="h-full bg-gradient-to-r from-emerald to-cyan rounded-full" style={{ width: '42%' }} />
          </div>
          <div className="flex justify-between text-[10px]">
            <span className="text-text-muted">Storage</span>
            <span className="text-cyan">128 / 200 GB</span>
          </div>
          <div className="w-full h-1.5 bg-surface rounded-full overflow-hidden">
            <div className="h-full bg-gradient-to-r from-cyan to-violet rounded-full" style={{ width: '64%' }} />
          </div>
        </div>
      </div>

      {/* Footer */}
      <div className="p-3 border-t border-border space-y-1">
        <a href="https://kicctest.org/counties/muranga" target="_blank" rel="noopener" className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-text-secondary hover:text-white hover:bg-surface-hover transition-all">
          <Globe size={16} className="text-text-muted" />
          <span>View Public County Portal</span>
        </a>
        <button className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-rose hover:text-rose hover:bg-rose/10 transition-all text-left">
          <LogOut size={16} />
          <span>Logout</span>
        </button>
      </div>
    </aside>
  );
}