import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'
import Skeleton from '../components/Skeleton'
import ConfirmDialog from '../components/ConfirmDialog'

interface Delegation {
  id: number
  subjectUserId: number
  subjectEmail: string
  subjectName: string
  role: string
  scopeType: string | null
  scopeValue: string | null
  expiresAt: string | null
  grantedByUserId: number
  grantedByEmail: string
  grantedAt: string
  revokedAt: string | null
  revokedByUserId: number | null
  extraPrivileges: string[]
}
interface UserView { id: number; email: string; fullName: string; tier: string }
interface County { id: number; name: string; slug: string }
interface Sector { id: number; name: string; slug: string }
interface Booth { id: number; boothNumber: string; name: string }

const ROLES = ['EXHIBITOR', 'COUNTY', 'NATIONAL']
const SCOPE_TYPES = ['ALL', 'COUNTY', 'SECTOR', 'BOOTH']
const EXTRA_PRIVS = ['CONTENT_MANAGE', 'BOOKINGS_MANAGE', 'PAYMENTS_MANAGE', 'MEDIA_MANAGE', 'ANALYTICS_VIEW', 'REPORTS_VIEW', 'SETTINGS_MANAGE', 'SYNC_MANAGE', 'COUNTY_MANAGE', 'SECTOR_MANAGE', 'USERS_MANAGE', 'DELEGATE']

export default function Delegations() {
  const { me } = useAuth()
  const [delegations, setDelegations] = useState<Delegation[]>([])
  const [users, setUsers] = useState<UserView[]>([])
  const [counties, setCounties] = useState<County[]>([])
  const [sectors, setSectors] = useState<Sector[]>([])
  const [activeFilter, setActiveFilter] = useState(true)
  const [grant, setGrant] = useState({ subjectEmail: '', role: 'COUNTY', scopeType: 'COUNTY', scopeValue: '', expiresDays: '' })
  const [extraPrivs, setExtraPrivs] = useState<Set<string>>(new Set())
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')
  const [confirm, setConfirm] = useState<{ title: string; message: string; run: () => void } | null>(null)

  const hasDelegate = me?.privileges?.includes('DELEGATE')

  const load = async () => {
    setLoading(true)
    try {
      const params = new URLSearchParams()
      if (activeFilter) params.set('activeOnly', 'true')
      setDelegations(await api.get<Delegation[]>(`/admin/delegations?${params}`))
    } catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }

  useEffect(() => {
    if (!hasDelegate) return
    load()
    api.get<UserView[]>('/admin/users').then(setUsers).catch(() => {})
    api.get<County[]>('/data/counties').then(setCounties).catch(() => {})
    api.get<Sector[]>('/data/sectors').then(setSectors).catch(() => {})
  }, [activeFilter])

  const grantDelegation = async () => {
    const target = users.find(u => u.email.toLowerCase() === grant.subjectEmail.toLowerCase())
    if (!target) { setError('User not found'); return }
    setBusy(true)
    setError('')
    try {
      const body: Record<string, unknown> = {
        subjectUserId: target.id, role: grant.role,
        scopeType: grant.scopeType,
        scopeValue: grant.scopeType === 'ALL' ? null : grant.scopeValue
      }
      if (grant.expiresDays) {
        const d = new Date()
        d.setDate(d.getDate() + Number(grant.expiresDays))
        body.expiresAt = d.toISOString()
      }
      if (extraPrivs.size > 0) body['extraPrivileges'] = [...extraPrivs]
      await api.post('/admin/delegations', body)
      setMsg('Delegation granted')
      setGrant({ subjectEmail: '', role: 'COUNTY', scopeType: 'COUNTY', scopeValue: '', expiresDays: '' })
      setExtraPrivs(new Set())
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const revoke = (id: number) => {
    setConfirm({
      title: 'Revoke delegation',
      message: 'Revoke this delegation?',
      run: () => doRevoke(id)
    })
  }

  const doRevoke = async (id: number) => {
    setBusy(true)
    setError('')
    try {
      await api.post(`/admin/delegations/${id}/revoke`, {})
      setMsg('Revoked')
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  if (!hasDelegate) return <div className="text-slate-400 text-sm p-6">DELEGATE privilege required</div>

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Delegations</h1>
      {msg && <div role="status" className="mb-4 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">{msg}</div>}
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <div className="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
        <h2 className="text-lg font-semibold text-white mb-3">Grant Delegation</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          <label className="block">
            <span className="text-xs text-slate-400">User Email</span>
            <input type="text" value={grant.subjectEmail} onChange={e => setGrant({ ...grant, subjectEmail: e.target.value })} placeholder="user@example.com" className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500" />
          </label>
          <label className="block">
            <span className="text-xs text-slate-400">Role</span>
            <select value={grant.role} onChange={e => setGrant({ ...grant, role: e.target.value })} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
              {ROLES.map(r => <option key={r} value={r}>{r}</option>)}
            </select>
          </label>
          <label className="block">
            <span className="text-xs text-slate-400">Scope</span>
            <select value={grant.scopeType} onChange={e => setGrant({ ...grant, scopeType: e.target.value, scopeValue: '' })} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
              {SCOPE_TYPES.map(s => <option key={s} value={s}>{s}</option>)}
            </select>
          </label>
          {grant.scopeType !== 'ALL' && (
            <>
              {grant.scopeType === 'COUNTY' && (
                <label className="block">
                  <span className="text-xs text-slate-400">County</span>
                  <select value={grant.scopeValue} onChange={e => setGrant({ ...grant, scopeValue: e.target.value })} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
                    <option value="">Select…</option>
                    {counties.map(c => <option key={c.slug} value={c.slug}>{c.name} ({c.slug})</option>)}
                  </select>
                </label>
              )}
              {grant.scopeType === 'SECTOR' && (
                <label className="block">
                  <span className="text-xs text-slate-400">Sector</span>
                  <select value={grant.scopeValue} onChange={e => setGrant({ ...grant, scopeValue: e.target.value })} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
                    <option value="">Select…</option>
                    {sectors.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                  </select>
                </label>
              )}
              {grant.scopeType === 'BOOTH' && (
                <label className="block">
                  <span className="text-xs text-slate-400">Booth ID</span>
                  <input type="number" value={grant.scopeValue} onChange={e => setGrant({ ...grant, scopeValue: e.target.value })} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500" />
                </label>
              )}
            </>
          )}
          <label className="block">
            <span className="text-xs text-slate-400">Expires (days)</span>
            <input type="number" value={grant.expiresDays} onChange={e => setGrant({ ...grant, expiresDays: e.target.value })} placeholder="blank = never" className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500" />
          </label>
        </div>
        <details className="mt-3">
          <summary className="text-xs text-slate-400 cursor-pointer hover:text-slate-300">Extra privileges</summary>
          <div className="flex flex-wrap gap-2 mt-2">
            {EXTRA_PRIVS.map(p => (
              <label key={p} className={`rounded px-2 py-1 text-xs cursor-pointer border ${extraPrivs.has(p) ? 'bg-blue-800 border-blue-600 text-blue-100' : 'border-slate-700 text-slate-400 hover:border-slate-500'}`}>
                <input type="checkbox" className="hidden" checked={extraPrivs.has(p)} onChange={() => {
                  const n = new Set(extraPrivs)
                  n.has(p) ? n.delete(p) : n.add(p)
                  setExtraPrivs(n)
                }} />
                {p}
              </label>
            ))}
          </div>
        </details>
        <button onClick={grantDelegation} disabled={busy || !grant.subjectEmail || !grant.role || (grant.scopeType !== 'ALL' && !grant.scopeValue)} className="target-min mt-4 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
          Grant delegation
        </button>
      </div>

      <div className="flex gap-3 mb-4">
        <label className="flex items-center gap-2 text-sm text-slate-300">
          <input type="checkbox" checked={activeFilter} onChange={e => setActiveFilter(e.target.checked)} />
          Active only
        </label>
      </div>

      <div className="overflow-auto" aria-busy={loading ? 'true' : undefined}>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-800 text-left text-slate-400">
              <th className="pb-2 pr-4">Subject</th>
              <th className="pb-2 pr-4">Role</th>
              <th className="pb-2 pr-4">Scope</th>
              <th className="pb-2 pr-4">Extra</th>
              <th className="pb-2 pr-4">Expires</th>
              <th className="pb-2 pr-4">Status</th>
              <th className="pb-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading && delegations.length === 0 ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-slate-800/50">
                  <td colSpan={7} className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
                </tr>
              ))
            ) : delegations.length === 0 ? (
              <tr>
                <td colSpan={7} className="py-6">
                  <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <div className="text-3xl mb-2" aria-hidden>🗂️</div>
                    <div className="text-sm text-slate-400">No delegations found.</div>
                  </div>
                </td>
              </tr>
            ) : delegations.map(d => (
              <tr key={d.id} className="border-b border-slate-800/50 hover:bg-slate-900/50">
                <td className="py-2 pr-4 text-slate-300 text-xs">{d.subjectEmail}</td>
                <td className="py-2 pr-4 text-xs text-slate-400">{d.role}</td>
                <td className="py-2 pr-4 text-xs text-slate-400">{d.scopeType || 'ALL'}{d.scopeValue ? `: ${d.scopeValue}` : ''}</td>
                <td className="py-2 pr-4 text-xs text-slate-500">{d.extraPrivileges?.join(', ') || '-'}</td>
                <td className="py-2 pr-4 text-xs text-slate-500">{d.expiresAt?.slice(0, 10) || 'never'}</td>
                <td className="py-2 pr-4"><span className={`rounded px-2 py-0.5 text-xs font-semibold ${d.revokedAt ? 'bg-red-800 text-red-200' : 'bg-green-800 text-green-200'}`}>{d.revokedAt ? 'Revoked' : 'Active'}</span></td>
                <td className="py-2">
                  {!d.revokedAt && <button onClick={() => revoke(d.id)} disabled={busy} className="target-min rounded bg-red-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-600 disabled:opacity-50">Revoke</button>}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <ConfirmDialog
        open={confirm !== null}
        title={confirm?.title ?? ''}
        message={confirm?.message ?? ''}
        confirmLabel="Revoke"
        busy={busy}
        onConfirm={() => { const fn = confirm; setConfirm(null); if (fn) fn.run() }}
        onCancel={() => setConfirm(null)}
      />
    </div>
  )
}
