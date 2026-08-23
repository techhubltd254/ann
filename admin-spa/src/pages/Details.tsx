import { useState } from 'react';
import { motion } from 'framer-motion';
import { Save, Check, MapPin, Users, Landmark, Phone, Globe, FileText } from 'lucide-react';
import { cn } from '../lib/utils';

const inputCls =
  'w-full bg-slate-dark border border-border rounded-lg px-3 py-2 text-sm text-white placeholder:text-text-muted outline-none focus:border-violet/50 focus:ring-2 focus:ring-violet/10 transition-all';

const labelCls = 'block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5';

interface CountyForm {
  name: string;
  code: string;
  region: string;
  headquarters: string;
  governor: string;
  deputyGovernor: string;
  population: string;
  area: string;
  subCounties: string;
  wards: string;
  email: string;
  phone: string;
  website: string;
  address: string;
  tagline: string;
  description: string;
}

const INITIAL: CountyForm = {
  name: "Murang'a County",
  code: 'KE-021',
  region: 'Central Kenya',
  headquarters: "Murang'a Town",
  governor: 'H.E. Governor of Murang\'a',
  deputyGovernor: 'Deputy Governor',
  population: '1,056,640',
  area: '2,558.8 km²',
  subCounties: '8',
  wards: '35',
  email: 'info@muranga.go.ke',
  phone: '+254 60 30213',
  website: 'https://muranga.go.ke',
  address: 'P.O. Box 52-10200, Murang\'a',
  tagline: 'The Highland Gem of Kenya',
  description:
    "Murang'a County lies in the central highlands of Kenya, bordered by the Aberdare ranges to the west. Renowned for its tea and coffee estates, waterfalls, eco-tourism trails and vibrant agribusiness economy, the county is a flagship destination on the KICC platform.",
};

function Field({ label, field, form, set, multiline = false, span = false }: {
  label: string;
  field: keyof CountyForm;
  form: CountyForm;
  set: (f: keyof CountyForm, v: string) => void;
  multiline?: boolean;
  span?: boolean;
}) {
  return (
    <div className={cn(span && 'sm:col-span-2')}>
      <label className={labelCls}>{label}</label>
      {multiline ? (
        <textarea
          rows={4}
          value={form[field]}
          onChange={(e) => set(field, e.target.value)}
          className={cn(inputCls, 'resize-none leading-relaxed')}
        />
      ) : (
        <input
          value={form[field]}
          onChange={(e) => set(field, e.target.value)}
          className={inputCls}
        />
      )}
    </div>
  );
}

export default function DetailsPage() {
  const [form, setForm] = useState<CountyForm>(INITIAL);
  const [saved, setSaved] = useState(false);

  const set = (field: keyof CountyForm, value: string) => {
    setForm((f) => ({ ...f, [field]: value }));
    setSaved(false);
  };

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2500);
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black text-white">Details & General Info</h1>
          <p className="text-sm text-text-muted">County identity, leadership and contact information shown on the public portal</p>
        </div>
        <button onClick={handleSave} className={cn('btn-primary flex items-center gap-2', saved && '!from-emerald !to-cyan')}>
          {saved ? <Check size={15} /> : <Save size={15} />}
          {saved ? 'Saved to Portal' : 'Save Changes'}
        </button>
      </motion.div>

      <div className="grid grid-cols-2 gap-6">
        {/* Identity */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.05 }} className="glass-card p-5">
          <div className="flex items-center gap-2 mb-4">
            <div className="kpi-icon-wrap bg-violet/15"><MapPin size={16} className="text-violet" /></div>
            <h3 className="text-base font-bold text-white">County Identity</h3>
          </div>
          <div className="grid sm:grid-cols-2 gap-4">
            <Field label="County Name" field="name" form={form} set={set} />
            <Field label="County Code" field="code" form={form} set={set} />
            <Field label="Region" field="region" form={form} set={set} />
            <Field label="Headquarters" field="headquarters" form={form} set={set} />
            <Field label="Portal Tagline" field="tagline" form={form} set={set} span />
          </div>
        </motion.div>

        {/* Leadership */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="glass-card p-5">
          <div className="flex items-center gap-2 mb-4">
            <div className="kpi-icon-wrap bg-indigo/15"><Landmark size={16} className="text-indigo" /></div>
            <h3 className="text-base font-bold text-white">Leadership</h3>
          </div>
          <div className="grid sm:grid-cols-2 gap-4">
            <Field label="Governor" field="governor" form={form} set={set} />
            <Field label="Deputy Governor" field="deputyGovernor" form={form} set={set} />
          </div>
          <div className="mt-4 p-3 rounded-lg bg-indigo/5 border border-indigo/20 text-xs text-text-secondary leading-relaxed">
            Leadership details appear on the public county portal "About" section and in official KICC event materials.
          </div>
        </motion.div>

        {/* Demographics */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.15 }} className="glass-card p-5">
          <div className="flex items-center gap-2 mb-4">
            <div className="kpi-icon-wrap bg-emerald/15"><Users size={16} className="text-emerald" /></div>
            <h3 className="text-base font-bold text-white">Demographics & Geography</h3>
          </div>
          <div className="grid sm:grid-cols-2 gap-4">
            <Field label="Population" field="population" form={form} set={set} />
            <Field label="Area" field="area" form={form} set={set} />
            <Field label="Sub-Counties" field="subCounties" form={form} set={set} />
            <Field label="Wards" field="wards" form={form} set={set} />
          </div>
        </motion.div>

        {/* Contact */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="glass-card p-5">
          <div className="flex items-center gap-2 mb-4">
            <div className="kpi-icon-wrap bg-cyan/15"><Phone size={16} className="text-cyan" /></div>
            <h3 className="text-base font-bold text-white">Contact & Presence</h3>
          </div>
          <div className="grid sm:grid-cols-2 gap-4">
            <Field label="Official Email" field="email" form={form} set={set} />
            <Field label="Phone" field="phone" form={form} set={set} />
            <Field label="Website" field="website" form={form} set={set} />
            <Field label="Postal Address" field="address" form={form} set={set} />
          </div>
        </motion.div>
      </div>

      {/* Description */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.25 }} className="glass-card p-5">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <div className="kpi-icon-wrap bg-amber/15"><FileText size={16} className="text-amber" /></div>
            <h3 className="text-base font-bold text-white">Portal Description</h3>
          </div>
          <span className="pill-badge badge-active flex items-center gap-1"><Globe size={10} /> Live on portal</span>
        </div>
        <Field label="About the County" field="description" form={form} set={set} multiline />
        <div className="mt-3 flex items-center justify-between text-[11px] text-text-muted">
          <span>{form.description.length} characters · rendered on /counties/muranga</span>
          <span>Last synced: Aug 24, 2026 · 08:14 EAT</span>
        </div>
      </motion.div>
    </div>
  );
}
