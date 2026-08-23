import { useState } from 'react';
import { motion } from 'framer-motion';
import { Plus, Save, Eye, FileText, Send } from 'lucide-react';
import DataTable from '../components/DataTable';
import { mockArticles } from '../lib/mock-data';
import { cn } from '../lib/utils';

export default function ContentCMS() {
  const [selectedId, setSelectedId] = useState(mockArticles[0].id);
  const [draft, setDraft] = useState(mockArticles[0].name);
  const [body, setBody] = useState(
    "Twin Falls has been recognised among Kenya's top hidden gems in the 2026 National Tourism Review. The iconic cascades in Kangema drew over 12,400 visitors last month, a 14% increase driven by the new KICC 4D volumetric walkthrough experience..."
  );
  const [published, setPublished] = useState(false);

  const selectArticle = (row: any) => {
    setSelectedId(row.id);
    setDraft(row.name);
    setPublished(false);
  };

  const handlePublish = () => {
    setPublished(true);
    setTimeout(() => setPublished(false), 2500);
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Content & Editorial CMS</h1>
          <p className="text-sm text-text-muted">News, stories and editorial content for the Murang'a county portal</p>
        </div>
        <button className="btn-primary flex items-center gap-2"><Plus size={15} /> New Article</button>
      </motion.div>

      <div className="grid grid-cols-5 gap-6">
        {/* Article List */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="col-span-3 glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Editorial Queue</h3>
          <p className="text-xs text-text-muted mb-4">Click a row to load it into the editor</p>
          <DataTable data={mockArticles} onRowClick={selectArticle} />
        </motion.div>

        {/* Editor Panel */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.15 }} className="col-span-2 glass-card p-5 flex flex-col">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <div className="kpi-icon-wrap bg-amber/15"><FileText size={15} className="text-amber" /></div>
              <h3 className="text-base font-bold text-white">Editor</h3>
            </div>
            <span className="pill-badge bg-surface-light text-text-muted border border-border font-mono !text-[10px]">{selectedId}</span>
          </div>

          <label className="block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5">Headline</label>
          <input
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
            className="w-full bg-slate-dark border border-border rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-violet/50 focus:ring-2 focus:ring-violet/10 transition-all mb-4"
          />

          <label className="block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5">Body</label>
          <textarea
            value={body}
            onChange={(e) => setBody(e.target.value)}
            rows={9}
            className="w-full flex-1 bg-slate-dark border border-border rounded-lg px-3 py-2 text-sm text-white leading-relaxed outline-none focus:border-violet/50 focus:ring-2 focus:ring-violet/10 transition-all resize-none mb-4"
          />

          <div className="flex items-center gap-2">
            <button
              onClick={handlePublish}
              className={cn('btn-primary flex-1 flex items-center justify-center gap-2', published && '!from-emerald !to-cyan')}
            >
              {published ? <><Send size={14} /> Published!</> : <><Send size={14} /> Publish to Portal</>}
            </button>
            <button className="btn-secondary flex items-center gap-2"><Save size={14} /> Draft</button>
            <button className="btn-secondary flex items-center gap-2"><Eye size={14} /> Preview</button>
          </div>
        </motion.div>
      </div>
    </div>
  );
}
