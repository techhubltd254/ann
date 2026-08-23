import { useSearch } from '../lib/search-context';
import {
  Search, Plus, Upload, Download, Bell, ChevronDown,
  Calendar, Clock, MapPin
} from 'lucide-react';
import { useEffect, useState } from 'react';

const QUICK_ACTIONS = [
  { icon: Plus, label: 'Add Attraction', color: 'from-emerald to-cyan' },
  { icon: Upload, label: 'Upload 4D Media', color: 'from-violet to-indigo' },
  { icon: Download, label: 'Export Report', color: 'from-amber to-rose' },
];

export default function TopBar({ breadcrumb }: { breadcrumb: string }) {
  const { setOpen } = useSearch();
  const [time, setTime] = useState(new Date());

  useEffect(() => {
    const timer = setInterval(() => setTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  return (
    <header className="h-16 bg-slate-dark border-b border-border flex items-center justify-between px-6 shrink-0">
      {/* Left: Breadcrumb */}
      <div className="flex items-center gap-3">
        <div className="flex items-center gap-1.5 text-sm text-text-muted">
          <span className="text-white font-semibold">Murang'a</span>
          <span>/</span>
          <span className="text-text-secondary">{breadcrumb}</span>
        </div>
      </div>

      {/* Center: Search */}
      <button
        onClick={() => setOpen(true)}
        className="flex items-center gap-2.5 w-96 px-4 py-2 bg-surface border border-border rounded-lg text-text-muted hover:border-border-glow transition-all"
      >
        <Search size={16} />
        <span className="text-sm">Search products, hotels, 4D scenes...</span>
        <kbd className="ml-auto px-1.5 py-0.5 bg-surface-hover rounded text-[10px] font-mono">⌘K</kbd>
      </button>

      {/* Right: Actions + Tray */}
      <div className="flex items-center gap-3">
        {QUICK_ACTIONS.map(({ icon: Icon, label, color }) => (
          <button
            key={label}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gradient-to-r ${color} shadow-lg hover:opacity-90 transition-opacity`}
          >
            <Icon size={14} />
            <span>{label}</span>
          </button>
        ))}

        <div className="flex items-center gap-2 ml-4 pl-4 border-l border-border">
          <div className="flex items-center gap-1.5 text-text-muted">
            <Calendar size={14} />
            <span className="text-xs">{time.toLocaleDateString('en-KE', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
          </div>
          <div className="flex items-center gap-1.5 text-text-muted">
            <Clock size={14} />
            <span className="text-xs font-mono">{time.toLocaleTimeString('en-KE', { hour: '2-digit', minute: '2-digit' })}</span>
          </div>

          <div className="relative ml-2">
            <button className="p-2 rounded-lg text-text-secondary hover:text-white hover:bg-surface-hover transition-all relative">
              <Bell size={18} />
              <span className="absolute -top-0.5 -right-0.5 w-4 h-4 bg-rose rounded-full text-[9px] text-white font-bold flex items-center justify-center">3</span>
            </button>
          </div>

          <div className="flex items-center gap-2.5 ml-2">
            <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-violet to-indigo flex items-center justify-center text-white text-xs font-bold">MC</div>
            <div className="hidden lg:block">
              <div className="text-xs font-semibold text-white">Murang'a County Admin</div>
              <div className="text-[10px] text-text-muted flex items-center gap-1"><MapPin size={8} />Kenya, Murang'a</div>
            </div>
            <ChevronDown size={14} className="text-text-muted" />
          </div>
        </div>
      </div>
    </header>
  );
}