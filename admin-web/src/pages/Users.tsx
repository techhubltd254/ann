import { useState, useEffect, type ReactNode } from 'react'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'
import Skeleton from '../components/Skeleton'
import ConfirmDialog from '../components/ConfirmDialog'

interface UserView { id: number; email: string; fullName: string; tier: string; countySlug: string | null; sectorId: number | null; boothId: number | null; active: boolean }
const TIERS = ['KICC', 'NATIONAL', 'COUNTY', 'EXHIBITOR']

export default function Users() {
  const { me } = useAuth()
  const [users, setUsers] = useState<UserView[]>([])
  const [edit, setEdit] = useState<Partial<UserView> | null>(null)
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')
  const [pwd, setPwd] = useState<{ user: string; password: string } | null>(null)
  const [confirm, setConfirm] = useState<{ title: string; message: ReactNode; run: () => void } | null>(null)

  const load = async () => {
    setLoading(true)
    try { setUsers(await api.get<UserView[]>('/admin/users')) }
    catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }
  useEffect(() => { load() }, [])

  const save = async () => {
    if (!edit?.id) return
    setBusy(true)
    setError('')
    try {
      const body: Record<string, unknown> = {}
      if (edit.fullName) body.fullName = edit.fullName
      if (edit.tier) body.tier = edit.tier
      if (edit.countySlug !== undefined) body.countySlug = edit.countySlug || ''
      if (edit.sectorId !== undefined) body.sectorId = edit.sectorId || null
      if (edit.boothId !== undefined) body.boothId = edit.boothId || null
      if (edit.active !== undefined) body.active = edit.active
      await api.put(`/admin/users/${edit.id}`, body)
      setMsg('User updated')
      setEdit(null)
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const resetPwd = async (id: number, email: string) => {
    setBusy(true)
    setError('')
    try {
      const res = await api.post<{ userId: number; newPassword: string }>(`/admin/users/${id}/reset-password`, {})
      setPwd({ user: email, password: res.newPassword })
      setMsg('Password reset. Share this with the user — it will not be shown again.')
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const toggleActive = (u: UserView) => {
    setConfirm({
      title: u.active ? 'Deactivate user' : 'Reactivate user',
      message: <>{u.active ? 'Deactivate' : 'Reactivate'} <strong className="text-white">{u.email}</strong>?</>,
      run: () => doToggleActive(u)
    })
  }

  const doToggleActive = async (u: UserView) => {
    setBusy(true)
    setError('')
    try {
      if (u.active) {
        await api.delete(`/admin/users/${u.id}`)
        setMsg('User deactivated')
      } else {
        await api.post(`/admin/users/${u.id}/reactivate`, {})
        setMsg('User reactivated')
      }
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Users</h1>
      {msg && <div role="status" className="mb-4 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">{msg}</div>}
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}
      {pwd && (
        <div className="mb-4 rounded-lg border border-yellow-800 bg-yellow-950/40 px-4 py-3 text-sm text-yellow-300">
          New password for <strong>{pwd.user}</strong>: <code className="font-mono font-bold text-white">{pwd.password}</code>
          <button onClick={() => setPwd(null)} className="target-min ml-3 text-xs underline">Dismiss</button>
        </div>
      )}

      {edit && (
        <div className="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="text-lg font-semibold text-white mb-3">Edit: {edit.email}</h2>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            <label className="block">
              <span className="text-xs text-slate-400">Full Name</span>
              <input type="text" value={edit.fullName || ''} onChange={e => setEdit({ ...edit, fullName: e.target.value })} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500" />
            </label>
            <label className="block">
              <span className="text-xs text-slate-400">Tier</span>
              <select value={edit.tier || ''} onChange={e => setEdit({ ...edit, tier: e.target.value })} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
                {TIERS.map(t => <option key={t} value={t}>{t}</option>)}
              </select>
            </label>
            <label className="block">
              <span className="text-xs text-slate-400">County Slug</span>
              <input type="text" value={edit.countySlug || ''} onChange={e => setEdit({ ...edit, countySlug: e.target.value })} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500" />
            </label>
            <label className="flex items-end">
              <label className="flex items-center gap-2 text-sm text-slate-300 mb-1">
                <input type="checkbox" checked={edit.active !== undefined ? edit.active : true} onChange={e => setEdit({ ...edit, active: e.target.checked })} />
                Active
              </label>
            </label>
          </div>
          <div className="mt-4 flex gap-3">
            <button onClick={save} disabled={busy} className="target-min rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600 disabled:opacity-50">Save</button>
            <button onClick={() => setEdit(null)} className="target-min rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500">Cancel</button>
          </div>
        </div>
      )}

      <div className="overflow-auto" aria-busy={loading ? 'true' : undefined}>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-800 text-left text-slate-400">
              <th className="pb-2 pr-4">Email</th>
              <th className="pb-2 pr-4">Name</th>
              <th className="pb-2 pr-4">Tier</th>
              <th className="pb-2 pr-4">County</th>
              <th className="pb-2 pr-4">Status</th>
              <th className="pb-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading && users.length === 0 ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-slate-800/50">
                  <td colSpan={6} className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
                </tr>
              ))
            ) : users.length === 0 ? (
              <tr>
                <td colSpan={6} className="py-6">
                  <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <div className="text-3xl mb-2" aria-hidden>🗂️</div>
                    <div className="text-sm text-slate-400">No users found.</div>
                  </div>
                </td>
              </tr>
            ) : users.map(u => (
              <tr key={u.id} className="border-b border-slate-800/50 hover:bg-slate-900/50">
                <td className="py-2 pr-4 text-slate-300">{u.email}</td>
                <td className="py-2 pr-4 text-white">{u.fullName}</td>
                <td className="py-2 pr-4 text-xs text-slate-400">{u.tier}</td>
                <td className="py-2 pr-4 text-xs text-slate-400">{u.countySlug || '-'}</td>
                <td className="py-2 pr-4"><span className={`rounded px-2 py-0.5 text-xs font-semibold ${u.active ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200'}`}>{u.active ? 'Active' : 'Inactive'}</span></td>
                <td className="py-2">
                  <div className="flex gap-2">
                    <button onClick={() => setEdit({ id: u.id, email: u.email, fullName: u.fullName, tier: u.tier, countySlug: u.countySlug, active: u.active })} className="target-min rounded bg-blue-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-600">Edit</button>
                    <button onClick={() => resetPwd(u.id, u.email)} disabled={busy} className="target-min rounded bg-purple-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-purple-500 disabled:opacity-50">Reset PW</button>
                    <button onClick={() => toggleActive(u)} disabled={busy} className={`target-min rounded px-2.5 py-1.5 text-xs font-semibold text-white disabled:opacity-50 ${u.active ? 'bg-red-700 hover:bg-red-600' : 'bg-green-700 hover:bg-green-600'}`}>{u.active ? 'Deact.' : 'Activate'}</button>
                  </div>
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
        confirmLabel="Confirm"
        busy={busy}
        onConfirm={() => { const fn = confirm; setConfirm(null); if (fn) fn.run() }}
        onCancel={() => setConfirm(null)}
      />
    </div>
  )
}
