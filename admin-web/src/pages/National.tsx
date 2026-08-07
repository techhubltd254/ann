import { useEffect, useMemo, useState } from 'react'
import { api } from '../lib/api'
import Skeleton from '../components/Skeleton'
import ConfirmDialog from '../components/ConfirmDialog'

interface Ministry {
  id: number
  name: string
  slug: string
  code: string
  logo: string | null
  color: string | null
  description: string | null
  website: string | null
  contact_email: string | null
  contact_phone: string | null
  is_active: boolean
}

interface Agency {
  id: number
  ministry_id: number
  name: string
  slug: string
  code: string
  logo: string | null
  description: string | null
  website: string | null
  contact_email: string | null
  is_active: boolean
}

const MINISTRY_FIELDS: [string, string, string][] = [
  ['name', 'Ministry name', 'text'],
  ['code', 'Code (e.g. MOT)', 'text'],
  ['color', 'Brand color (#RRGGBB)', 'text'],
  ['website', 'Website', 'url'],
  ['contact_email', 'Contact email', 'email'],
  ['contact_phone', 'Contact phone', 'tel'],
  ['logo', 'Logo URL', 'url'],
]

const AGENCY_FIELDS: [string, string, string][] = [
  ['name', 'Agency name', 'text'],
  ['code', 'Code', 'text'],
  ['website', 'Website', 'url'],
  ['contact_email', 'Contact email', 'email'],
  ['logo', 'Logo URL', 'url'],
]

export default function National() {
  const [tab, setTab] = useState<'ministries' | 'agencies'>('ministries')
  const [ministries, setMinistries] = useState<Ministry[]>([])
  const [agencies, setAgencies] = useState<Agency[]>([])
  const [ministryFilter, setMinistryFilter] = useState('')
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')
  const [edit, setEdit] = useState<Partial<Ministry & Agency> & { id?: number } | null>(null)
  const [confirm, setConfirm] = useState<{ title: string; message: string; run: () => void } | null>(null)

  const load = async () => {
    setLoading(true)
    setError('')
    try {
      const [m, a] = await Promise.all([
        api.get<Ministry[]>('/national/ministries'),
        api.get<Agency[]>('/national/agencies'),
      ])
      setMinistries(m)
      setAgencies(a)
    } catch (e) {
      setError((e as Error).message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  // Derived state — never duplicated into state (per spec).
  const visibleAgencies = useMemo(
    () => (ministryFilter ? agencies.filter(a => String(a.ministry_id) === ministryFilter) : agencies),
    [agencies, ministryFilter],
  )
  const ministryName = useMemo(() => {
    const map = new Map<number, string>()
    ministries.forEach(m => map.set(m.id, m.name))
    return (id: number) => map.get(id) ?? `#${id}`
  }, [ministries])

  const save = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    if (!edit) return
    setBusy(true)
    setError('')
    setMsg('')
    const isAgency = tab === 'agencies'
    const base = isAgency ? '/national/agencies' : '/national/ministries'
    try {
      if (edit.id) {
        await api.put(`${base}/${edit.id}`, edit)
        setMsg('Saved.')
      } else {
        await api.post(base, edit)
        setMsg('Created.')
      }
      setEdit(null)
      await load()
    } catch (err) {
      setError((err as Error).message)
    } finally {
      setBusy(false)
    }
  }

  const softDelete = (row: Ministry | Agency) => {
    const isAgency = tab === 'agencies'
    setConfirm({
      title: `Deactivate ${row.name}?`,
      message: 'This hides it from the public site. The record is kept (soft delete) and can be reactivated.',
      run: async () => {
        const base = isAgency ? '/national/agencies' : '/national/ministries'
        await api.delete(`${base}/${row.id}`)
        setConfirm(null)
        await load()
      },
    })
  }

  const fields = tab === 'agencies' ? AGENCY_FIELDS : MINISTRY_FIELDS
  const rows: (Ministry | Agency)[] = tab === 'agencies' ? visibleAgencies : ministries

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-xl font-bold text-white font-sans">National Government Content</h1>
          <p className="text-sm text-white/50">Ministries and agencies shown on the public platform. County sector data is managed by county admins.</p>
        </div>
        <button
          type="button"
          onClick={() => setEdit({ is_active: true })}
          className="min-h-11 rounded-xl bg-kicc-gold px-5 py-2.5 text-sm font-bold text-kicc-navy hover:brightness-110 active:scale-[0.98] transition-all focus-visible:outline-2 focus-visible:outline-kicc-gold"
        >
          + New {tab === 'agencies' ? 'Agency' : 'Ministry'}
        </button>
      </div>

      {/* Tabs — native buttons, aria-selected */}
      <div role="tablist" aria-label="National content" className="flex gap-1 rounded-xl bg-white/5 p-1 w-fit">
        {(['ministries', 'agencies'] as const).map(t2 => (
          <button
            key={t2}
            type="button"
            role="tab"
            aria-selected={tab === t2}
            onClick={() => setTab(t2)}
            className={`min-h-11 rounded-lg px-5 py-2 text-sm font-semibold capitalize transition-colors focus-visible:outline-2 focus-visible:outline-kicc-gold ${
              tab === t2 ? 'bg-kicc-gold text-kicc-navy' : 'text-white/60 hover:text-white'
            }`}
          >
            {t2} ({t2 === 'ministries' ? ministries.length : agencies.length})
          </button>
        ))}
      </div>

      {tab === 'agencies' && (
        <div>
          <label htmlFor="ministry-filter" className="mb-1.5 block text-xs font-semibold text-white/60">
            Filter by ministry
          </label>
          <select
            id="ministry-filter"
            value={ministryFilter}
            onChange={e => setMinistryFilter(e.target.value)}
            className="min-h-11 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-kicc-gold focus:outline-2 focus:outline-kicc-gold/40"
          >
            <option value="">All ministries</option>
            {ministries.map(m => (
              <option key={m.id} value={String(m.id)}>{m.name}</option>
            ))}
          </select>
        </div>
      )}

      {error && <p role="alert" className="rounded-xl bg-red-500/10 border border-red-500/30 px-4 py-3 text-sm text-red-300">{error}</p>}
      {msg && <p role="status" className="rounded-xl bg-kicc-green/10 border border-kicc-green/30 px-4 py-3 text-sm text-kicc-green">{msg}</p>}

      {loading ? (
        <Skeleton className="h-72 w-full" />
      ) : (
        <div className="overflow-hidden rounded-2xl border border-white/10">
          <table className="w-full text-sm">
            <thead className="bg-white/5 text-left text-xs uppercase tracking-wide text-white/40">
              <tr>
                <th className="px-4 py-3 font-semibold" scope="col">Name</th>
                <th className="px-4 py-3 font-semibold" scope="col">Code</th>
                {tab === 'agencies' && <th className="px-4 py-3 font-semibold" scope="col">Ministry</th>}
                <th className="px-4 py-3 font-semibold" scope="col">Status</th>
                <th className="px-4 py-3 font-semibold text-right" scope="col">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {rows.map(row => (
                <tr key={row.id} className="text-white/80 hover:bg-white/5 transition-colors">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2.5">
                      {'color' in row && row.color && (
                        <span aria-hidden className="size-3 rounded-full" style={{ backgroundColor: row.color }} />
                      )}
                      <span className="font-semibold">{row.name}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 font-mono text-xs text-white/50">{row.code}</td>
                  {tab === 'agencies' && (
                    <td className="px-4 py-3 text-white/60">{ministryName((row as Agency).ministry_id)}</td>
                  )}
                  <td className="px-4 py-3">
                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${row.is_active ? 'bg-kicc-green/15 text-kicc-green' : 'bg-white/10 text-white/40'}`}>
                      {row.is_active ? 'Live' : 'Hidden'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="inline-flex gap-2">
                      <button
                        type="button"
                        onClick={() => setEdit(row)}
                        className="min-h-11 rounded-lg px-4 py-2 text-xs font-semibold text-kicc-gold hover:bg-kicc-gold/10 transition-colors focus-visible:outline-2 focus-visible:outline-kicc-gold"
                      >
                        Edit
                      </button>
                      {row.is_active && (
                        <button
                          type="button"
                          onClick={() => softDelete(row)}
                          className="min-h-11 rounded-lg px-4 py-2 text-xs font-semibold text-white/40 hover:bg-white/10 hover:text-white/70 transition-colors focus-visible:outline-2 focus-visible:outline-white/40"
                        >
                          Deactivate
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && (
                <tr><td colSpan={5} className="px-4 py-10 text-center text-white/40">No records yet.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* Create/Edit dialog — native form semantics */}
      {edit && (
        <div role="dialog" aria-modal="true" aria-labelledby="national-edit-title" className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
          <form
            onSubmit={save}
            className="w-full max-w-md space-y-4 rounded-2xl border border-white/10 bg-kicc-dark p-6 shadow-2xl"
          >
            <h2 id="national-edit-title" className="text-lg font-bold text-white">
              {edit.id ? 'Edit' : 'New'} {tab === 'agencies' ? 'Agency' : 'Ministry'}
            </h2>

            {tab === 'agencies' && (
              <div>
                <label htmlFor="agency-ministry" className="mb-1.5 block text-xs font-semibold text-white/60">
                  Ministry
                </label>
                <select
                  id="agency-ministry"
                  name="ministry_id"
                  required
                  value={edit.ministry_id ?? ''}
                  onChange={e => setEdit({ ...edit, ministry_id: Number(e.target.value) })}
                  className="w-full min-h-11 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-kicc-gold focus:outline-2 focus:outline-kicc-gold/40"
                >
                  <option value="" disabled>Choose ministry…</option>
                  {ministries.map(m => (
                    <option key={m.id} value={m.id}>{m.name}</option>
                  ))}
                </select>
              </div>
            )}

            {fields.map(([key, label, type]) => (
              <div key={key}>
                <label htmlFor={`f-${key}`} className="mb-1.5 block text-xs font-semibold text-white/60">
                  {label}
                </label>
                <input
                  id={`f-${key}`}
                  name={key}
                  type={type}
                  required={key === 'name'}
                  autoComplete="off"
                  value={(edit as Record<string, unknown>)[key] as string ?? ''}
                  onChange={e => setEdit({ ...edit, [key]: e.target.value })}
                  className="w-full min-h-11 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-kicc-gold focus:outline-2 focus:outline-kicc-gold/40"
                />
              </div>
            ))}

            <div>
              <label htmlFor="f-description" className="mb-1.5 block text-xs font-semibold text-white/60">
                Description
              </label>
              <textarea
                id="f-description"
                name="description"
                rows={3}
                value={edit.description ?? ''}
                onChange={e => setEdit({ ...edit, description: e.target.value })}
                className="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-kicc-gold focus:outline-2 focus:outline-kicc-gold/40"
              />
            </div>

            <fieldset>
              <legend className="mb-1.5 text-xs font-semibold text-white/60">Visibility</legend>
              <div className="flex gap-4">
                {[{ v: true, l: 'Live on site' }, { v: false, l: 'Hidden' }].map(o => (
                  <label key={String(o.v)} className="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-sm text-white/80 has-checked:border-kicc-gold has-checked:bg-kicc-gold/10">
                    <input
                      type="radio"
                      name="is_active"
                      className="size-4 accent-kicc-gold"
                      checked={edit.is_active === o.v}
                      onChange={() => setEdit({ ...edit, is_active: o.v })}
                    />
                    {o.l}
                  </label>
                ))}
              </div>
            </fieldset>

            <div className="flex justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setEdit(null)}
                className="min-h-11 rounded-xl px-5 py-2.5 text-sm font-semibold text-white/60 hover:text-white transition-colors focus-visible:outline-2 focus-visible:outline-white/40"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={busy}
                className="min-h-11 rounded-xl bg-kicc-gold px-6 py-2.5 text-sm font-bold text-kicc-navy hover:brightness-110 active:scale-[0.98] transition-all disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-kicc-gold"
              >
                {busy ? 'Saving…' : 'Looks Good — Save'}
              </button>
            </div>
          </form>
        </div>
      )}

      {confirm && (
        <ConfirmDialog open={!!confirm} title={confirm.title} message={confirm.message} onConfirm={confirm.run} onCancel={() => setConfirm(null)} />
      )}
    </div>
  )
}
