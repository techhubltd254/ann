import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'
import Skeleton from '../components/Skeleton'
import ConfirmDialog from '../components/ConfirmDialog'

interface County {
  id: number
  name: string
  slug: string
  capital: string
  code: string
  formerProvince: string
  economicZone: string
  population2024: number | null
  areaKm2: number | null
  tagline: string | null
  description: string | null
  tourismHighlights: string | null
  iconEmoji: string | null
  warmestMonth: string | null
  coolestMonth: string | null
  rainySeason: string | null
  drySeason: string | null
  isActive: boolean
  profileImage: string | null
  sceneType: string
  region: string | null
}

const FIELDS: [string, string, string][] = [
  ['name', 'Name', 'text'], ['slug', 'Slug', 'text'], ['capital', 'Capital', 'text'],
  ['code', 'Code', 'text'], ['formerProvince', 'Former Province', 'text'], ['economicZone', 'Economic Zone', 'text'],
  ['population2024', 'Population (2024)', 'number'], ['areaKm2', 'Area km²', 'number'],
  ['tagline', 'Tagline', 'text'], ['iconEmoji', 'Icon Emoji', 'text'],
  ['warmestMonth', 'Warmest Month', 'text'], ['coolestMonth', 'Coolest Month', 'text'],
  ['rainySeason', 'Rainy Season', 'text'], ['drySeason', 'Dry Season', 'text'],
  ['region', 'Region', 'text'], ['sceneType', 'Scene Type', 'text'],
  ['profileImage', 'Profile Image URL', 'text'],
]

export default function Counties() {
  const { me } = useAuth()
  const [counties, setCounties] = useState<County[]>([])
  const [edit, setEdit] = useState<Partial<County> & { id?: number }>({})
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')
  const [confirm, setConfirm] = useState<{ title: string; message: string; run: () => void } | null>(null)

  const canCreate = me?.privileges?.includes('COUNTY_MANAGE')

  const load = async () => {
    setLoading(true)
    try { setCounties(await api.get<County[]>('/data/counties')) }
    catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [])

  const save = async () => {
    setBusy(true)
    setError('')
    setMsg('')
    try {
      const body: Record<string, unknown> = {}
      for (const [k] of FIELDS) { if (edit[k as keyof typeof edit] !== undefined && edit[k as keyof typeof edit] !== '') body[k] = edit[k as keyof typeof edit] }
      if (edit.id) {
        await api.put(`/data/counties/${edit.id}`, body)
        setMsg('Updated')
      } else {
        await api.post('/data/counties', body)
        setMsg('Created')
      }
      setEdit({})
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const del = (id: number) => {
    setConfirm({
      title: 'Delete county',
      message: 'Delete this county? This will fail if the county has any data, users, exhibitions or venues.',
      run: () => doDel(id)
    })
  }

  const doDel = async (id: number) => {
    setBusy(true)
    setError('')
    try {
      await api.delete(`/data/counties/${id}`)
      setMsg('Deleted')
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Counties</h1>
      {msg && <div role="status" className="mb-4 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">{msg}</div>}
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      {canCreate && (
        <button onClick={() => setEdit({})} className="target-min mb-4 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
          + New county
        </button>
      )}

      {(edit.id !== undefined || (!edit.id && edit.name !== undefined)) && (
        <div className="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="text-lg font-semibold text-white mb-3">{edit.id ? 'Edit County' : 'New County'}</h2>
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
              <span className="text-xs text-slate-400">Description (markdown)</span>
              <textarea
                value={edit.description?.toString() ?? ''}
                onChange={e => setEdit({ ...edit, description: e.target.value })}
                rows={3}
                className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-white outline-none focus:border-blue-500"
              />
            </label>
            <label className="block">
              <span className="text-xs text-slate-400">Tourism Highlights</span>
              <textarea
                value={edit.tourismHighlights?.toString() ?? ''}
                onChange={e => setEdit({ ...edit, tourismHighlights: e.target.value })}
                rows={3}
                className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-white outline-none focus:border-blue-500"
              />
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
              <th className="pb-2 pr-4">Capital</th>
              <th className="pb-2 pr-4">Code</th>
              <th className="pb-2 pr-4">Status</th>
              <th className="pb-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading && counties.length === 0 ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-slate-800/50">
                  <td colSpan={6} className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
                </tr>
              ))
            ) : counties.length === 0 ? (
              <tr>
                <td colSpan={6} className="py-6">
                  <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <div className="text-3xl mb-2" aria-hidden>🗂️</div>
                    <div className="text-sm text-slate-400">No counties found.{canCreate && ' Create the first one.'}</div>
                  </div>
                </td>
              </tr>
            ) : counties.map(c => (
              <tr key={c.id} className="border-b border-slate-800/50 hover:bg-slate-900/50">
                <td className="py-2 pr-4 text-white">{c.iconEmoji} {c.name}</td>
                <td className="py-2 pr-4 text-slate-400 font-mono text-xs">{c.slug}</td>
                <td className="py-2 pr-4 text-slate-300">{c.capital}</td>
                <td className="py-2 pr-4 text-slate-400">{c.code}</td>
                <td className="py-2 pr-4"><span className={`rounded px-2 py-0.5 text-xs font-semibold ${c.isActive ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200'}`}>{c.isActive ? 'Active' : 'Inactive'}</span></td>
                <td className="py-2">
                  <div className="flex gap-2">
                    <button onClick={() => setEdit({ ...c })} className="target-min rounded bg-blue-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-600">
                      Edit
                    </button>
                    {canCreate && (
                      <button onClick={() => del(c.id)} disabled={busy} className="target-min rounded bg-red-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-600 disabled:opacity-50">
                        Delete
                      </button>
                    )}
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
        confirmLabel="Delete"
        busy={busy}
        onConfirm={() => { const fn = confirm; setConfirm(null); if (fn) fn.run() }}
        onCancel={() => setConfirm(null)}
      />
    </div>
  )
}
