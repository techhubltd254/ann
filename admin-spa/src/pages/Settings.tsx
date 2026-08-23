import { useState } from 'react';
import { motion } from 'framer-motion';
import { Settings as SettingsIcon, ShieldCheck, Key, Bell, Check } from 'lucide-react';
import { mockRoles } from '../lib/mock-data';
import { cn } from '../lib/utils';

const TABS = [
  { key: 'general', label: 'General', icon: SettingsIcon },
  { key: 'roles', label: 'Roles & Permissions', icon: ShieldCheck },
  { key: 'api', label: 'API & Integrations', icon: Key },
  { key: 'notifications', label: 'Notifications', icon: Bell },
] as const;

const PERMISSION_LABELS: Record<string, string> = {
  content: 'Content CMS',
  commerce: 'Commerce & Orders',
  media: 'Media & 4D Studio',
  settings: 'System Settings',
  billing: 'Billing & Payouts',
};

const inputCls =
  'w-full bg-slate-dark border border-border rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-violet/50 focus:ring-2 focus:ring-violet/10 transition-all';

function Toggle({ on, onClick }: { on: boolean; onClick: () => void }) {
  return (
    <button onClick={onClick} className={cn('w-10 h-6 rounded-full relative transition-colors', on ? 'bg-violet' : 'bg-surface-light')}>
      <motion.span
        animate={{ x: on ? 20 : 2 }}
        transition={{ type: 'spring', stiffness: 500, damping: 30 }}
        className="absolute top-1 w-4 h-4 rounded-full bg-white shadow"
      />
    </button>
  );
}

export default function Settings() {
  const [tab, setTab] = useState<(typeof TABS)[number]['key']>('general');
  const [roles, setRoles] = useState(mockRoles);
  const [saved, setSaved] = useState(false);
  const [notifs, setNotifs] = useState({ orders: true, revenue: true, security: true, digest: false, renders: true });

  const togglePermission = (roleId: string, perm: string) => {
    setRoles((rs) =>
      rs.map((r) =>
        r.id === roleId
          ? { ...r, permissions: { ...r.permissions, [perm]: !r.permissions[perm as keyof typeof r.permissions] } }
          : r
      )
    );
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
          <h1 className="text-2xl font-black text-white">Settings & Roles</h1>
          <p className="text-sm text-text-muted">Workspace configuration, access control and integrations</p>
        </div>
        <button onClick={handleSave} className={cn('btn-primary flex items-center gap-2', saved && '!from-emerald !to-cyan')}>
          {saved ? <Check size={15} /> : <ShieldCheck size={15} />}
          {saved ? 'Saved' : 'Save Settings'}
        </button>
      </motion.div>

      {/* Tabs */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.05 }} className="flex items-center gap-2 bg-surface rounded-lg p-1 border border-border w-fit">
        {TABS.map(({ key, label, icon: Icon }) => (
          <button
            key={key}
            onClick={() => setTab(key)}
            className={cn(
              'px-4 py-2 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5',
              tab === key ? 'bg-violet text-white shadow-sm' : 'text-text-muted hover:text-white'
            )}
          >
            <Icon size={13} /> {label}
          </button>
        ))}
      </motion.div>

      {/* General */}
      {tab === 'general' && (
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="glass-card p-5 max-w-2xl">
          <h3 className="text-base font-bold text-white mb-4">Workspace Settings</h3>
          <div className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5">Workspace Name</label>
              <input defaultValue="Murang'a County Admin" className={inputCls} />
            </div>
            <div>
              <label className="block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5">Default Currency</label>
              <input defaultValue="KES — Kenyan Shilling" className={inputCls} />
            </div>
            <div>
              <label className="block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5">Timezone</label>
              <input defaultValue="Africa/Nairobi (EAT, UTC+3)" className={inputCls} />
            </div>
            <div>
              <label className="block text-[11px] font-bold text-text-muted uppercase tracking-wider mb-1.5">Public Portal URL</label>
              <input defaultValue="https://kicctest.org/counties/muranga" className={inputCls} />
            </div>
          </div>
        </motion.div>
      )}

      {/* Roles */}
      {tab === 'roles' && (
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="glass-card p-5">
          <h3 className="text-base font-bold text-white mb-1">Role Permission Matrix</h3>
          <p className="text-xs text-text-muted mb-4">Toggle module access per role · changes apply immediately</p>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border">
                  <th className="text-left py-3 px-3 text-[11px] font-bold text-text-muted uppercase tracking-wider">Role</th>
                  <th className="text-center py-3 px-3 text-[11px] font-bold text-text-muted uppercase tracking-wider">Members</th>
                  {Object.values(PERMISSION_LABELS).map((label) => (
                    <th key={label} className="text-center py-3 px-3 text-[11px] font-bold text-text-muted uppercase tracking-wider">{label}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {roles.map((role) => (
                  <tr key={role.id} className="border-b border-border/50 hover:bg-surface-hover/60 transition-colors">
                    <td className="py-3.5 px-3">
                      <div className="font-semibold text-white">{role.name}</div>
                      <div className="text-[10px] text-text-muted font-mono">{role.id}</div>
                    </td>
                    <td className="py-3.5 px-3 text-center">
                      <span className="pill-badge bg-surface-light text-text-secondary border border-border">{role.members}</span>
                    </td>
                    {Object.keys(PERMISSION_LABELS).map((perm) => (
                      <td key={perm} className="py-3.5 px-3 text-center">
                        <div className="flex justify-center">
                          <Toggle
                            on={role.permissions[perm as keyof typeof role.permissions]}
                            onClick={() => togglePermission(role.id, perm)}
                          />
                        </div>
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </motion.div>
      )}

      {/* API */}
      {tab === 'api' && (
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="glass-card p-5 max-w-2xl space-y-4">
          <h3 className="text-base font-bold text-white">API Credentials</h3>
          {[
            { name: 'Live API Key', value: 'kicc_live_mrg_7f3a9c2e81d4', scope: 'Full access' },
            { name: 'Webhook Secret', value: 'whsec_4bd81f0a92c34e77', scope: 'Event signing' },
            { name: 'M-Pesa Daraja Consumer Key', value: 'mpesa_dj_9e17bc05aa21', scope: 'Payments' },
          ].map((cred) => (
            <div key={cred.name} className="p-4 rounded-xl bg-slate-dark border border-border">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-semibold text-white">{cred.name}</span>
                <span className="pill-badge badge-featured !text-[10px]">{cred.scope}</span>
              </div>
              <div className="flex items-center gap-2">
                <code className="flex-1 text-xs font-mono text-cyan bg-deep rounded-lg px-3 py-2 border border-border truncate">
                  {cred.value}••••••••
                </code>
                <button className="btn-secondary !py-2 !px-3 !text-xs">Rotate</button>
              </div>
            </div>
          ))}
          <div className="p-3 rounded-lg bg-amber/5 border border-amber/20 text-[11px] text-text-secondary leading-relaxed">
            ⚠️ Rotating a key revokes the previous credential immediately. Update connected services to avoid downtime.
          </div>
        </motion.div>
      )}

      {/* Notifications */}
      {tab === 'notifications' && (
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="glass-card p-5 max-w-2xl space-y-3">
          <h3 className="text-base font-bold text-white mb-2">Notification Preferences</h3>
          {[
            { key: 'orders', label: 'New Orders & Bookings', desc: 'Instant alert for every confirmed transaction' },
            { key: 'revenue', label: 'Revenue Milestones', desc: 'Alert when daily revenue crosses thresholds' },
            { key: 'security', label: 'Security Events', desc: 'Failed logins, role changes, key rotations' },
            { key: 'renders', label: '4D Render Completion', desc: 'Notify when splat pipeline jobs finish' },
            { key: 'digest', label: 'Weekly Digest', desc: 'Monday summary of all county metrics' },
          ].map((n) => (
            <div key={n.key} className="flex items-center justify-between p-4 rounded-xl bg-slate-dark border border-border">
              <div>
                <div className="text-sm font-semibold text-white">{n.label}</div>
                <div className="text-[11px] text-text-muted">{n.desc}</div>
              </div>
              <Toggle on={notifs[n.key as keyof typeof notifs]} onClick={() => setNotifs((s) => ({ ...s, [n.key]: !s[n.key as keyof typeof s] }))} />
            </div>
          ))}
        </motion.div>
      )}
    </div>
  );
}
