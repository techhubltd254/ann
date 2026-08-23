import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Search, MapPin, Hotel, Package, ShoppingCart, Video, FileText, CornerDownLeft, X } from 'lucide-react';
import { useSearch } from '../lib/search-context';
import { mockAttractions, mockHotels, mockProducts, mockOrders, mock4DScenes, mockArticles } from '../lib/mock-data';
import { cn } from '../lib/utils';

interface SearchItem {
  id: string;
  name: string;
  meta: string;
  href: string;
  group: string;
  icon: any;
  emoji: string;
}

const INDEX: SearchItem[] = [
  ...mockAttractions.map((a) => ({ id: a.id, name: a.name, meta: `${a.category} · ${a.location}`, href: '/attractions', group: 'Attractions', icon: MapPin, emoji: a.emoji })),
  ...mockHotels.map((h) => ({ id: h.id, name: h.name, meta: `${h.stars}-star · ${h.location}`, href: '/hotels', group: 'Hotels', icon: Hotel, emoji: h.emoji })),
  ...mockProducts.map((p) => ({ id: p.id, name: p.name, meta: `${p.category} · ${p.vendor}`, href: '/products', group: 'Products', icon: Package, emoji: p.emoji })),
  ...mockOrders.map((o) => ({ id: o.id, name: o.name, meta: `${o.type} · ${o.customer}`, href: '/orders', group: 'Orders', icon: ShoppingCart, emoji: o.emoji })),
  ...mock4DScenes.map((s) => ({ id: s.id, name: s.title, meta: `${s.resolution} · ${s.duration}`, href: '/videos4d', group: '4D Scenes', icon: Video, emoji: s.emoji })),
  ...mockArticles.map((a) => ({ id: a.id, name: a.name, meta: `${a.category} · ${a.author}`, href: '/content', group: 'Editorial', icon: FileText, emoji: a.emoji })),
];

export default function SearchOverlay() {
  const { setOpen } = useSearch();
  const navigate = useNavigate();
  const [query, setQuery] = useState('');
  const [activeIndex, setActiveIndex] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    inputRef.current?.focus();
  }, []);

  const results = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return INDEX.slice(0, 8);
    return INDEX.filter(
      (item) =>
        item.name.toLowerCase().includes(q) ||
        item.meta.toLowerCase().includes(q) ||
        item.id.toLowerCase().includes(q)
    ).slice(0, 10);
  }, [query]);

  useEffect(() => setActiveIndex(0), [results.length]);

  const go = (item: SearchItem) => {
    setOpen(false);
    navigate(item.href);
  };

  const onKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') setOpen(false);
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveIndex((i) => Math.min(i + 1, results.length - 1));
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIndex((i) => Math.max(i - 1, 0));
    }
    if (e.key === 'Enter' && results[activeIndex]) go(results[activeIndex]);
  };

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-start justify-center pt-[12vh] px-4"
      onClick={() => setOpen(false)}
    >
      <motion.div
        initial={{ opacity: 0, scale: 0.96, y: -12 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        transition={{ type: 'spring', damping: 26, stiffness: 320 }}
        className="w-full max-w-xl glass-card !bg-slate-dark/95 shadow-2xl shadow-black/50 overflow-hidden"
        onClick={(e) => e.stopPropagation()}
        onKeyDown={onKeyDown}
      >
        {/* Input */}
        <div className="flex items-center gap-3 px-4 h-14 border-b border-border">
          <Search size={18} className="text-violet shrink-0" />
          <input
            ref={inputRef}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search attractions, hotels, products, orders, 4D scenes..."
            className="flex-1 bg-transparent text-sm text-white placeholder:text-text-muted outline-none"
          />
          <button
            onClick={() => setOpen(false)}
            className="p-1.5 rounded-md text-text-muted hover:text-white hover:bg-surface-hover transition-all"
          >
            <X size={14} />
          </button>
        </div>

        {/* Results */}
        <div className="max-h-80 overflow-y-auto py-2">
          {results.length === 0 && (
            <div className="py-10 text-center text-sm text-text-muted">
              No results for "<span className="text-white">{query}</span>"
            </div>
          )}
          {results.map((item, i) => {
            const Icon = item.icon;
            return (
              <button
                key={item.id}
                onMouseEnter={() => setActiveIndex(i)}
                onClick={() => go(item)}
                className={cn(
                  'w-full flex items-center gap-3 px-4 py-2.5 text-left transition-colors',
                  i === activeIndex ? 'bg-violet/10' : ''
                )}
              >
                <div className="w-8 h-8 rounded-lg bg-surface-light border border-border flex items-center justify-center text-sm shrink-0">
                  {item.emoji}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="text-sm font-semibold text-white truncate">{item.name}</div>
                  <div className="text-[11px] text-text-muted truncate">{item.meta}</div>
                </div>
                <span className="pill-badge bg-surface-light text-text-muted border border-border !text-[10px] shrink-0">
                  <Icon size={10} className="mr-1" />
                  {item.group}
                </span>
                {i === activeIndex && <CornerDownLeft size={13} className="text-violet shrink-0" />}
              </button>
            );
          })}
        </div>

        {/* Footer */}
        <div className="flex items-center gap-4 px-4 h-10 border-t border-border text-[10px] text-text-muted">
          <span className="flex items-center gap-1"><kbd className="px-1 py-0.5 bg-surface-light rounded font-mono">↑↓</kbd> Navigate</span>
          <span className="flex items-center gap-1"><kbd className="px-1 py-0.5 bg-surface-light rounded font-mono">↵</kbd> Open</span>
          <span className="flex items-center gap-1"><kbd className="px-1 py-0.5 bg-surface-light rounded font-mono">esc</kbd> Close</span>
          <span className="ml-auto">{results.length} result{results.length !== 1 ? 's' : ''}</span>
        </div>
      </motion.div>
    </motion.div>
  );
}
