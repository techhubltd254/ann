import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'
import Skeleton from '../components/Skeleton'

interface AuditLog {
  id: number
  actorUserId: number
  action: string
  targetUserId: number | null
  detail: string | null
  createdAt: string
}

interface ChainStatus {
  valid: boolean
  checked: number
  brokenAtId: number | null
  legacyRows: number
  tailHash: string
}

function csvEscape(s: string) { return '"' + s.replace(/"/g, '""') + '"' }

export default function AdminAudit() {
  const { me } = useAuth()
  const [rows, setRows] = useState<AuditLog[]>([])
  const [chain, setChain] = useState<ChainStatus | null>(null)
  const [offset, setOffset] = useState(0)
  const [limit] = useState(100)
  const [filterAction, setFilterAction] = useState('')
  const [filterUser, setFilterUser] = useState('')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [users, setUsers] = useState<{ id: number; email: string }[]>([])

  const hasDelegate = me?.privileges?.includes('DELEGATE')

  const load = async (userList = users) => {
    setBusy(true)
    try {
      const params = new URLSearchParams({ limit: String(limit), offset: String(offset) })
      if (filterUser) {
        const u = userList.find(x => x.email.toLowerCase().includes(filterUser.toLowerCase()))
        if (u) params.set('targetUserId', String(u.id))
      }
      setRows(await api.get<AuditLog[]>(`/admin/audit-logs?${params}`))
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const verifyChain = async () => {
    try { setChain(await api.get<ChainStatus>('/admin/audit-logs/verify')) }
    catch (e) { setError((e as Error).message) }
  }

  useEffect(() => {
    if (!hasDelegate) return
    verifyChain()
    let cancelled = false
    api.get<{ id: number; email: string }[]>('/admin/users')
      .then(u => { if (!cancelled) { setUsers(u); load(u) } })
      .catch(() => { if (!cancelled) load() })
    return () => { cancelled = true }
  }, [offset, filterUser])

  const filtered = filterAction ? rows.filter(r => r.action.toLowerCase().includes(filterAction.toLowerCase())) : rows

  const exportCsv = () => {
    const h = 'id,actorUserId,action,targetUserId,detail,createdAt\n'
    const body = rows.map(r => [r.id, r.actorUserId, csvEscape(r.action), r.targetUserId ?? '', csvEscape(r.detail || ''), csvEscape(r.createdAt)].join(',')).join('\n')
    const blob = new Blob([h + body], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'audit-export.csv'
    a.click()
    URL.revokeObjectURL(url)
  }

  if (!hasDelegate) return <div className="text-slate-400 text-sm p-6">DELEGATE privilege required</div>

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Audit Log</h1>
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      {chain && (
        <div className={`mb-4 rounded-lg border px-4 py-3 text-sm ${chain.valid ? 'border-green-800 bg-green-950/40 text-green-300' : 'border-red-800 bg-red-950/40 text-red-300'}`}>
          Chain: <strong>{chain.valid ? 'VALID' : 'BROKEN'}</strong> · {chain.checked} checked · broken at: {chain.brokenAtId ?? 'none'} · legacy: {chain.legacyRows}
          <span className="ml-2 text-xs text-slate-400 font-mono">tail: {(chain && chain.tailHash) ? `${chain.tailHash.slice(0, 16)}…` : '—'}</span>
          <button onClick={verifyChain} className="target-min ml-3 text-xs underline hover:text-white">Re-verify</button>
        </div>
      )}

      <div className="flex gap-3 mb-4 flex-wrap items-end">
        <label className="block">
          <span className="text-xs text-slate-400">Action filter</span>
          <input type="text" value={filterAction} onChange={e => setFilterAction(e.target.value)} placeholder="e.g. LOGIN_SUCCESS" className="mt-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500 w-56" />
        </label>
        <label className="block">
          <span className="text-xs text-slate-400">User email filter</span>
          <input type="text" value={filterUser} onChange={e => setFilterUser(e.target.value)} placeholder="admin@kicc…" className="mt-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500 w-56" />
        </label>
        <button onClick={() => load()} disabled={busy} className="target-min rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600 disabled:opacity-50">Search</button>
        <button onClick={exportCsv} className="target-min rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500">CSV Export</button>
      </div>

      <div className="flex gap-3 mb-4">
        <button onClick={() => setOffset(Math.max(0, offset - limit))} disabled={offset === 0} className="target-min rounded bg-slate-800 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-700 disabled:opacity-30">← Prev</button>
        <span className="text-sm text-slate-400 self-center">offset: {offset}, showing {filtered.length}</span>
        <button onClick={() => setOffset(offset + limit)} disabled={filtered.length < limit} className="target-min rounded bg-slate-800 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-700 disabled:opacity-30">Next →</button>
      </div>

      <div className="overflow-auto max-h-[60vh]" aria-busy={busy ? 'true' : undefined}>
        <table className="w-full text-xs">
          <thead className="sticky top-0 bg-slate-950">
            <tr className="border-b border-slate-800 text-left text-slate-400">
              <th className="pb-2 pr-4">ID</th>
              <th className="pb-2 pr-4">Actor</th>
              <th className="pb-2 pr-4">Action</th>
              <th className="pb-2 pr-4">Target</th>
              <th className="pb-2 pr-4">Detail</th>
              <th className="pb-2">Time</th>
            </tr>
          </thead>
          <tbody>
            {busy && rows.length === 0 ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-slate-800/50">
                  <td colSpan={6} className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
                </tr>
              ))
            ) : rows.length === 0 ? (
              <tr>
                <td colSpan={6} className="py-6">
                  <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <div className="text-3xl mb-2" aria-hidden>🗂️</div>
                    <div className="text-sm text-slate-400">No audit entries found.</div>
                  </div>
                </td>
              </tr>
            ) : filtered.map(r => (
              <tr key={r.id} className="border-b border-slate-800/50 hover:bg-slate-900/50">
                <td className="py-1.5 pr-4 text-slate-500 font-mono">{r.id}</td>
                <td className="py-1.5 pr-4 text-slate-400">{r.actorUserId}</td>
                <td className="py-1.5 pr-4 text-slate-300 font-mono">{r.action}</td>
                <td className="py-1.5 pr-4 text-slate-400">{r.targetUserId ?? '-'}</td>
                <td className="py-1.5 pr-4 text-slate-400 max-w-[300px] truncate">{r.detail || '-'}</td>
                <td className="py-1.5 text-slate-400">{r.createdAt?.slice(0, 19)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
