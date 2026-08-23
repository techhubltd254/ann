import { useEffect, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import Sidebar from './Sidebar';
import TopBar from './TopBar';
import SearchOverlay from './SearchOverlay';
import { SearchContext } from '../lib/search-context';

export default function AdminLayout() {
  const [searchOpen, setSearchOpen] = useState(false);
  const location = useLocation();
  const pathParts = location.pathname.split('/').filter(Boolean);
  const breadcrumb = pathParts.length > 0
    ? pathParts.map(p => p.replace(/-/g, ' ').replace(/^\w/, c => c.toUpperCase())).join(' / ')
    : 'Overview';

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        setSearchOpen((v) => !v);
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, []);

  return (
    <SearchContext.Provider value={{ open: searchOpen, setOpen: setSearchOpen }}>
      <div className="min-h-screen bg-deep flex">
        <Sidebar />
        <div className="flex-1 flex flex-col overflow-hidden">
          <TopBar breadcrumb={breadcrumb} />
          <main className="flex-1 overflow-y-auto">
            <Outlet />
          </main>
        </div>
      </div>
      {searchOpen && <SearchOverlay />}
    </SearchContext.Provider>
  );
}