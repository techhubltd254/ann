import React from 'react';
import {
  BarChart3, Image, CheckCircle2, Calendar, LogOut, PanelLeft,
} from 'lucide-react';

const navItems = [
  { label: 'Analytics', icon: BarChart3, section: 'analytics' },
  { label: 'Media', icon: Image, section: 'media' },
  { label: 'Approvals', icon: CheckCircle2, section: 'approvals' },
  { label: 'Bookings', icon: Calendar, section: 'bookings' },
];

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const [activeSection, setActiveSection] = React.useState('analytics');
  const [sidebarOpen, setSidebarOpen] = React.useState(true);

  React.useEffect(() => {
    const hash = window.location.hash.replace('#', '');
    if (hash) setActiveSection(hash);
  }, []);

  const navigate = (section: string) => {
    setActiveSection(section);
    window.location.hash = section;
  };

  return (
    <div className="flex h-screen overflow-hidden bg-kicc-bg">
      {sidebarOpen && (
        <aside className="w-64 bg-kicc-surface border-r border-kicc-border flex flex-col shrink-0">
          <div className="p-5 border-b border-kicc-border">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 bg-kicc-red rounded-lg flex items-center justify-center text-sm font-black">K</div>
              <div>
                <div className="text-sm font-bold text-white">KICC Admin</div>
                <div className="text-[10px] text-kicc-muted">Platform Control</div>
              </div>
            </div>
          </div>
          <nav className="flex-1 p-3 space-y-1">
            {navItems.map((item) => {
              const Icon = item.icon;
              return (
                <button
                  key={item.section}
                  onClick={() => navigate(item.section)}
                  className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all ${
                    activeSection === item.section
                      ? 'bg-kicc-red/15 text-kicc-gold border border-kicc-red/30'
                      : 'text-white/40 hover:text-white hover:bg-white/5 border border-transparent'
                  }`}
                >
                  <Icon size={18} className={activeSection === item.section ? 'fill-current' : ''} />
                  {item.label}
                </button>
              );
            })}
          </nav>
          <div className="p-3 border-t border-kicc-border">
            <div className="flex items-center gap-3 px-3 py-2 text-xs text-kicc-muted">
              <div className="w-7 h-7 rounded-full bg-kicc-card flex items-center justify-center text-[10px] font-bold text-white">SA</div>
              <div className="flex-1 truncate">
                <div className="text-white text-xs font-semibold truncate">Super Admin</div>
                <div className="text-[10px]">KICC · Full Access</div>
              </div>
              <button
                onClick={() => window.location.href = '/logout'}
                className="text-white/20 hover:text-kicc-red transition-colors"
                title="Logout"
              >
                <LogOut size={16} />
              </button>
            </div>
          </div>
        </aside>
      )}
      <button
        onClick={() => setSidebarOpen(!sidebarOpen)}
        className="fixed top-4 left-4 z-50 w-8 h-8 bg-kicc-surface border border-kicc-border rounded-lg flex items-center justify-center text-white/40 hover:text-white transition-all"
      >
        <PanelLeft size={16} />
      </button>
      <main className="flex-1 overflow-y-auto">
        <div className="p-6 lg:p-8 max-w-[1600px] mx-auto">
          {children}
        </div>
      </main>
    </div>
  );
}
