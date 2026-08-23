import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { Upload, Image as ImageIcon, Check, Trash2, Tag, Download } from 'lucide-react';
import { mockGalleryImages } from '../lib/mock-data';
import { cn, getStatusColor } from '../lib/utils';

const CATEGORIES = ['all', 'Attractions', 'Hotels', 'Products', 'Culture'] as const;

export default function Gallery() {
  const [category, setCategory] = useState<(typeof CATEGORIES)[number]>('all');
  const [selected, setSelected] = useState<Set<string>>(new Set());

  const images = useMemo(
    () => mockGalleryImages.filter((img) => category === 'all' || img.category === category),
    [category]
  );

  const toggle = (id: string) => {
    const next = new Set(selected);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setSelected(next);
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Photo Gallery & Images</h1>
          <p className="text-sm text-text-muted">High-resolution asset vault · RAW/CR3 auto-converted for web delivery</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Upload size={15} /> Batch Upload</button>
      </motion.div>

      {/* Filter + Batch Bar */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="flex items-center justify-between flex-wrap gap-3">
        <div className="flex items-center gap-2 bg-surface rounded-lg p-1 border border-border">
          {CATEGORIES.map((c) => (
            <button
              key={c}
              onClick={() => setCategory(c)}
              className={cn(
                'px-3.5 py-1.5 rounded-md text-xs font-semibold capitalize transition-all',
                category === c ? 'bg-violet text-white shadow-sm' : 'text-text-muted hover:text-white'
              )}
            >
              {c}
            </button>
          ))}
        </div>
        {selected.size > 0 && (
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-violet/10 border border-violet/25"
          >
            <span className="text-xs text-violet font-semibold">{selected.size} selected</span>
            <button className="btn-secondary !py-1 !px-2.5 !text-xs flex items-center gap-1"><Tag size={11} /> Tag</button>
            <button className="btn-secondary !py-1 !px-2.5 !text-xs flex items-center gap-1"><Download size={11} /> Export</button>
            <button
              onClick={() => setSelected(new Set())}
              className="btn-secondary !py-1 !px-2.5 !text-xs flex items-center gap-1 hover:!border-rose/40 hover:!text-rose"
            >
              <Trash2 size={11} /> Clear
            </button>
          </motion.div>
        )}
      </motion.div>

      {/* Image Grid */}
      <div className="grid grid-cols-4 gap-4">
        {images.map((img, i) => {
          const isSelected = selected.has(img.id);
          return (
            <motion.div
              key={img.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.03 }}
              onClick={() => toggle(img.id)}
              className={cn(
                'glass-card glass-card-hover overflow-hidden cursor-pointer group relative',
                isSelected && '!border-violet/60 ring-2 ring-violet/30'
              )}
            >
              <div className="h-32 bg-gradient-to-br from-surface-light to-deep flex items-center justify-center text-4xl relative">
                {img.emoji}
                <div
                  className={cn(
                    'absolute top-2 left-2 w-5 h-5 rounded-md border flex items-center justify-center transition-all',
                    isSelected ? 'bg-violet border-violet' : 'border-white/30 bg-black/40 group-hover:border-violet/60'
                  )}
                >
                  {isSelected && <Check size={12} className="text-white" />}
                </div>
                <span className={cn('pill-badge absolute top-2 right-2 !text-[9px] capitalize', getStatusColor(img.status))}>
                  {img.status}
                </span>
              </div>
              <div className="p-3">
                <div className="text-xs font-semibold text-white font-mono truncate mb-1 group-hover:text-violet transition-colors">
                  {img.name}
                </div>
                <div className="flex items-center justify-between text-[10px] text-text-muted">
                  <span className="flex items-center gap-1"><ImageIcon size={9} /> {img.dimensions}</span>
                  <span>{img.size}</span>
                </div>
              </div>
            </motion.div>
          );
        })}
      </div>

      <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ delay: 0.4 }} className="text-center text-xs text-text-muted">
        {images.length} of 128 assets · Vault usage 96 GB / 200 GB
      </motion.div>
    </div>
  );
}
