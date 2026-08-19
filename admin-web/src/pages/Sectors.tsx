import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'
import Skeleton from '../components/Skeleton'
import ConfirmDialog from '../components/ConfirmDialog'

interface Sector { id: number; name: string; slug: string; code: string; emoji: string | null; description: string | null; parentId: number | null; icon: string | null; isActive: boolean; sortOrder: number }
interface County { id: number; name: string; slug: string }
interface Link { id: number; countyId: number; sectorId: number; subSectors: string | null }

const FIELDS: [string, string, string][] = [
  ['name', 'Name', 'text'], ['slug', 'Slug', 'text'], ['code', 'Code', 'text'],
  ['emoji', 'Emoji', 'text'], ['parentId', 'Parent ID', 'number'], ['icon', 'Icon', 'text'],
  ['sortOrder', 'Sort Order', 'number'],
]

export default function Sectors() {
  const { me } = useAuth()
  const [sectors, setSectors] = useState<Sector[]>([])
  const [counties, setCounties] = useState<County[]>([])
  const [edit, setEdit] = useState<Partial<Sector> & { id?: number }>({})
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')
  const [confirm, setConfirm] = useState<{ title: string; message: string; run: () => void } | null>(null)
  const [linkCountyId, setLinkCountyId] = useState<string>('')
  const [linkSectorId, setLinkSectorId] = useState<string>('')
  const [links, setLinks] = useState<Link[]>([])

  const canManage = me?.privileges?.includes('SECTOR_MANAGE')

  const load = async () => {
    setLoading(true)
    try { setSectors(await api.get<Sector[]>('/data/sectors')) } catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }
  const loadCounties = async () => {
    try { setCounties(await api.get<County[]>('/data/counties')) } catch { /* */ }
  }

  useEffect(() => { load(); loadCounties() }, [])

  const save = async () => {
    setBusy(true)
    setError('')
    setMsg('')
    try {
      const body: Record<string, unknown> = {}
      for (const [k] of FIELDS) { if (edit[k as keyof typeof edit] !== undefined && edit[k as keyof typeof edit] !== '') body[k] = edit[k as keyof typeof edit] }
      if (edit.isActive !== undefined) body['isActive'] = edit.isActive
      if (edit.id) {
        await api.put(`/data/sectors/${edit.id}`, body)
        setMsg('Updated')
      } else {
        await api.post('/data/sectors', body)
        setMsg('Created')
      }
      setEdit({})
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const del = (id: number) => {
    setConfirm({
      title: 'Delete sector',
      message: 'Delete this sector?',
      run: () => doDel(id)
    })
  }

  const doDel = async (id: number) => {
    setBusy(true)
    setError('')
    try {
      await api.delete(`/data/sectors/${id}`)
      setMsg('Deleted')
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const addLink = async () => {
    setBusy(true)
    setError('')
    try {
      await api.post('/data/county-sectors', { countyId: Number(linkCountyId), sectorId: Number(linkSectorId) })
      setMsg('Link created')
      setLinkCountyId('')
      setLinkSectorId('')
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const removeLink = async (id: number) => {
    setBusy(true)
    setError('')
    try {
      await api.delete(`/data/county-sectors/${id}`)
      setMsg('Link removed')
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  // Load links for a county
  const viewLinks = async (countySlug: string) => {
    try { setLinks(await api.get<Link[]>(`/data/county-sectors?countySlug=${countySlug}`)) }
    catch (e) { setError((e as Error).message) }
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Sectors</h1>
      {msg && <div role="status" className="mb-4 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">{msg}</div>}
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      {canManage && (
        <>
          <button onClick={() => setEdit({})} className="target-min mb-4 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
            + New sector
          </button>

          <div className="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
            <h2 className="text-lg font-semibold text-white mb-3">Link County → Sector</h2>
            <div className="flex gap-3 items-end">
              <label className="block">
                <span className="text-xs text-slate-400">County</span>
                <select value={linkCountyId} onChange={e => setLinkCountyId(e.target.value)} className="mt-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
                  <option value="">Select county</option>
                  {counties.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </label>
              <label className="block">
                <span className="text-xs text-slate-400">Sector</span>
                <select value={linkSectorId} onChange={e => setLinkSectorId(e.target.value)} className="mt-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
                  <option value="">Select sector</option>
                  {sectors.filter(s => s.isActive).map(s => <option key={s.id} value={s.id}>{s.emoji} {s.name}</option>)}
                </select>
              </label>
              <button onClick={addLink} disabled={busy || !linkCountyId || !linkSectorId} className="target-min rounded-lg bg-green-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-600 disabled:opacity-50">
                Link
              </button>
            </div>
            <div className="mt-3 flex gap-2">
              {counties.slice(0, 10).map(c => (
                <button key={c.slug} onClick={() => viewLinks(c.slug)} className="target-min rounded bg-slate-800 px-2.5 py-1.5 text-xs text-slate-300 hover:bg-slate-700">
                  {c.name} links
                </button>
              ))}
            </div>
            {links.length > 0 && (
              <div className="mt-3 text-xs text-slate-400">
                {links.map(l => {
                  const cs = counties.find(c => c.id === l.countyId)
                  const ss = sectors.find(s => s.id === l.sectorId)
                  return (
                    <div key={l.id} className="flex items-center justify-between py-1 border-b border-slate-800">
                      <span>{cs?.name || l.countyId} → {ss?.emoji} {ss?.name || l.sectorId}</span>
                      {canManage && <button onClick={() => removeLink(l.id)} className="target-min text-red-400 hover:text-red-300">Remove</button>}
                    </div>
                  )
                })}
              </div>
            )}
          </div>
        </>
      )}

      {(edit.id !== undefined || (!edit.id && edit.name !== undefined)) && (
        <div className="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="text-lg font-semibold text-white mb-3">{edit.id ? 'Edit Sector' : 'New Sector'}</h2>
          <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
            {FIELDS.map(([k, label, type]) => (
              <label key={k} className="block">
                <span className="text-xs text-slate-400">{label}</span>
                <input
                  type={type}
                  value={edit[k as keyof typeof edit]?.toString() ?? ''}
                  onChange={e => setEdit({ ...edit, [k]: e.target.value })}
                  className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-white outline-none focus:border-blue-500"
                />
              </label>
            ))}
            <label className="block">
              <span className="text-xs text-slate-400">Description</span>
              <textarea value={edit.description?.toString() ?? ''} onChange={e => setEdit({ ...edit, description: e.target.value })} rows={2} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-white outline-none focus:border-blue-500" />
            </label>
            <label className="flex items-end">
              <label className="flex items-center gap-2 text-sm text-slate-300 mb-1">
                <input type="checkbox" checked={edit.isActive !== false} onChange={e => setEdit({ ...edit, isActive: e.target.checked })} />
                Active
              </label>
            </label>
          </div>
          <div className="mt-4 flex gap-3">
            <button onClick={save} disabled={busy} className="target-min rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
              {edit.id ? 'Save' : 'Create'}
            </button>
            <button onClick={() => setEdit({})} className="target-min rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500">
              Cancel
            </button>
          </div>
        </div>
      )}

      <div className="overflow-auto" aria-busy={loading ? 'true' : undefined}>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-800 text-left text-slate-400">
              <th className="pb-2 pr-4">Name</th>
              <th className="pb-2 pr-4">Slug</th>
              <th className="pb-2 pr-4">Code</th>
              <th className="pb-2">Sort</th>
              <th className="pb-2">Status</th>
              {canManage && <th className="pb-2">Actions</th>}
            </tr>
          </thead>
          <tbody>
            {loading && sectors.length === 0 ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-slate-800/50">
                  <td colSpan={6} className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
                </tr>
              ))
            ) : sectors.length === 0 ? (
              <tr>
                <td colSpan={6} className="py-6">
                  <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <div className="text-3xl mb-2" aria-hidden>🗂️</div>
                    <div className="text-sm text-slate-400">No sectors found.{canManage && ' Create the first one.'}</div>
                  </div>
                </td>
              </tr>
            ) : sectors.map(s => (
              <tr key={s.id} className="border-b border-slate-800/50 hover:bg-slate-900/50">
                <td className="py-2 pr-4 text-white">{s.emoji} {s.name}</td>
                <td className="py-2 pr-4 text-slate-400 font-mono text-xs">{s.slug}</td>
                <td className="py-2 pr-4 text-slate-400">{s.code}</td>
                <td className="py-2 pr-4 text-slate-400">{s.sortOrder}</td>
                <td className="py-2 pr-4"><span className={`rounded px-2 py-0.5 text-xs font-semibold ${s.isActive ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200'}`}>{s.isActive ? 'Active' : 'Inactive'}</span></td>
                {canManage && (
                  <td className="py-2">
                    <div className="flex gap-2">
                      <button onClick={() => setEdit({ ...s })} className="target-min rounded bg-blue-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-600">Edit</button>
                      <button onClick={() => del(s.id)} disabled={busy} className="target-min rounded bg-red-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-600 disabled:opacity-50">Delete</button>
                    </div>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <ConfirmDialog
        open={confirm !== null}
        title={confirm?.title ?? ''}
        message={confirm?.message ?? ''}
        confirmLabel="Delete"
        busy={busy}
        onConfirm={() => { const fn = confirm; setConfirm(null); if (fn) fn.run() }}
        onCancel={() => setConfirm(null)}
      />
    </div>
  )
}
