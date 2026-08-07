import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'
import Skeleton from '../components/Skeleton'

interface Complaint {
  id: number
  referenceNo: string
  category: string
  subject: string
  message: string
  contactName: string | null
  contactEmail: string | null
  priority: string
  status: string
  resolutionNote: string | null
  assigneeUserId: number | null
  slaDueAt: string
  createdAt: string
  updatedAt: string | null
  notesCount: number
  overdue: boolean
}

interface Note {
  id: number
  complaintId: number
  authorUserId: number
  authorEmail: string
  authorName: string
  note: string
  internal: boolean
  createdAt: string
}

interface UserView { id: number; email: string; fullName: string; tier: string }

const STATUS_FLOW: Record<string, string[]> = {
  OPEN: ['IN_PROGRESS'],
  IN_PROGRESS: ['OPEN', 'RESOLVED'],
  RESOLVED: ['CLOSED'],
  CLOSED: []
}

export default function Complaints() {
  const { me } = useAuth()
  const [complaints, setComplaints] = useState<Complaint[]>([])
  const [filterStatus, setFilterStatus] = useState('')
  const [filterPriority, setFilterPriority] = useState('')
  const [selected, setSelected] = useState<Complaint | null>(null)
  const [notes, setNotes] = useState<Note[]>([])
  const [users, setUsers] = useState<UserView[]>([])
  const [noteText, setNoteText] = useState('')
  const [internalNote, setInternalNote] = useState(true)
  const [resolution, setResolution] = useState('')
  const [assignId, setAssignId] = useState<string>('')
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')

  const load = async () => {
    setLoading(true)
    try {
      const params = new URLSearchParams()
      if (filterStatus) params.set('status', filterStatus)
      if (filterPriority) params.set('priority', filterPriority)
      setComplaints(await api.get<Complaint[]>(`/complaints?${params}`))
    } catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }

  const loadUsers = async () => {
    try { setUsers(await api.get<UserView[]>('/admin/users')) } catch {/* */}
  }

  useEffect(() => { load(); loadUsers() }, [filterStatus, filterPriority])

  const select = async (c: Complaint) => {
    setSelected(null)
    setNotes([])
    setMsg('')
    try {
      const detail = await api.get<Complaint>(`/complaints/${c.id}`)
      const ns = await api.get<Note[]>(`/complaints/${c.id}/notes`)
      setSelected(detail)
      setNotes(ns)
    } catch (e) { setError((e as Error).message) }
  }

  const changeStatus = async (id: number, status: string) => {
    setBusy(true)
    setError('')
    try {
      const body: Record<string, string> = { status }
      if (status === 'RESOLVED') body.resolutionNote = resolution || 'Resolved'
      await api.post(`/complaints/${id}/status`, body)
      setMsg(`Status → ${status}`)
      setSelected(await api.get<Complaint>(`/complaints/${id}`))
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const assign = async (id: number) => {
    if (!assignId) return
    setBusy(true)
    setError('')
    try {
      await api.post(`/complaints/${id}/assign`, { assigneeUserId: Number(assignId) })
      setMsg('Assigned')
      setSelected(await api.get<Complaint>(`/complaints/${id}`))
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const addNote = async (id: number) => {
    if (!noteText.trim()) return
    setBusy(true)
    setError('')
    try {
      await api.post(`/complaints/${id}/notes`, { note: noteText, internal: internalNote })
      setMsg('Note added')
      setNoteText('')
      setNotes(await api.get<Note[]>(`/complaints/${id}/notes`))
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const badge = (s: string, m: Record<string, string>) =>
    `rounded px-2 py-0.5 text-xs font-semibold ${m[s] || 'bg-slate-800 text-slate-300'}`

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Complaints</h1>
      {msg && <div role="status" className="mb-4 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">{msg}</div>}
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <div className="flex gap-3 mb-4">
        <select value={filterStatus} onChange={e => setFilterStatus(e.target.value)} className="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
          <option value="">All statuses</option>
          <option value="OPEN">Open</option>
          <option value="IN_PROGRESS">In Progress</option>
          <option value="RESOLVED">Resolved</option>
          <option value="CLOSED">Closed</option>
        </select>
        <select value={filterPriority} onChange={e => setFilterPriority(e.target.value)} className="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
          <option value="">All priorities</option>
          <option value="P1">P1</option>
          <option value="P2">P2</option>
          <option value="P3">P3</option>
        </select>
        <span className="text-sm text-slate-500 self-center">{complaints.length} results</span>
      </div>

      <div className="flex gap-6">
        <div className="flex-1 min-w-0 overflow-auto" aria-busy={loading ? 'true' : undefined}>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-800 text-left text-slate-400">
                <th className="pb-2 pr-4">Ref</th>
                <th className="pb-2 pr-4">Subject</th>
                <th className="pb-2 pr-4">Category</th>
                <th className="pb-2 pr-4">Priority</th>
                <th className="pb-2 pr-4">Status</th>
                <th className="pb-2 pr-4">SLA</th>
              </tr>
            </thead>
          <tbody>
            {loading && complaints.length === 0 ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-slate-800/50">
                  <td colSpan={6} className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
                </tr>
              ))
            ) : complaints.length === 0 ? (
              <tr>
                <td colSpan={6} className="py-6">
                  <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <div className="text-3xl mb-2" aria-hidden>🗂️</div>
                    <div className="text-sm text-slate-400">No complaints found.</div>
                  </div>
                </td>
              </tr>
            ) : complaints.map(c => (
              <tr
                key={c.id}
                onClick={() => select(c)}
                className={`border-b border-slate-800/50 hover:bg-slate-900/50 cursor-pointer ${selected?.id === c.id ? 'bg-slate-800' : ''}`}
              >
                <td className="py-2 pr-4 font-mono text-xs text-slate-400">{c.referenceNo}</td>
                <td className="py-2 pr-4 text-white truncate max-w-[200px]">{c.subject}</td>
                <td className="py-2 pr-4 text-xs text-slate-400">{c.category}</td>
                <td className="py-2 pr-4"><span className={badge(c.priority, { P1: 'bg-red-800 text-red-200', P2: 'bg-yellow-800 text-yellow-200', P3: 'bg-slate-700 text-slate-300' })}>{c.priority}</span></td>
                <td className="py-2 pr-4"><span className={badge(c.status, { OPEN: 'bg-yellow-800 text-yellow-200', IN_PROGRESS: 'bg-blue-800 text-blue-200', RESOLVED: 'bg-green-800 text-green-200', CLOSED: 'bg-slate-600 text-slate-300' })}>{c.status}</span></td>
                <td className="py-2 text-xs">{c.overdue ? <span className="text-red-400 font-semibold">OVERDUE</span> : <span className="text-slate-400">{c.slaDueAt?.slice(0, 10)}</span>}</td>
              </tr>
            ))}
          </tbody>
          </table>
        </div>

        {selected && (
          <div className="w-96 flex-shrink-0 rounded-xl border border-slate-800 bg-slate-900 p-4">
            <h2 className="font-semibold text-white">{selected.referenceNo}</h2>
            <h3 className="text-sm text-white mt-1">{selected.subject}</h3>
            <div className="mt-2 text-xs text-slate-400 space-y-1">
              <div>Category: {selected.category} · Priority: {selected.priority}</div>
              <div>Created: {selected.createdAt.slice(0, 19)}</div>
              <div>SLA due: {selected.slaDueAt.slice(0, 19)} {selected.overdue && <span className="text-red-400 font-semibold ml-1">OVERDUE</span>}</div>
              {selected.contactName && <div>Contact: {selected.contactName}{selected.contactEmail ? ` <${selected.contactEmail}>` : ''}</div>}
              {selected.resolutionNote && <div className="text-green-400">Resolution: {selected.resolutionNote}</div>}
            </div>
            <p className="mt-3 text-sm text-slate-300 whitespace-pre-wrap bg-slate-950 rounded p-3">{selected.message}</p>

            {selected.status !== 'CLOSED' && (
              <div className="mt-4 space-y-3">
                <div className="flex gap-2">
                  {STATUS_FLOW[selected.status]?.map(s => (
                    <button key={s} onClick={() => changeStatus(selected.id!, s)} disabled={busy} className="target-min rounded bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
                      → {s}
                    </button>
                  ))}
                </div>
                {selected.status === 'IN_PROGRESS' && (
                  <input value={resolution} onChange={e => setResolution(e.target.value)} placeholder="Resolution note" className="w-full rounded border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-white outline-none focus:border-blue-500" />
                )}

                <div className="flex gap-2 items-end">
                  <select value={assignId} onChange={e => setAssignId(e.target.value)} className="rounded border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-white outline-none flex-1">
                    <option value="">Assign to...</option>
                    {users.map(u => <option key={u.id} value={u.id}>{u.fullName} ({u.tier})</option>)}
                  </select>
                  <button onClick={() => assign(selected.id!)} disabled={busy || !assignId} className="target-min rounded bg-purple-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-600 disabled:opacity-50">
                    Assign
                  </button>
                </div>

                <div className="border-t border-slate-800 pt-3">
                  <textarea value={noteText} onChange={e => setNoteText(e.target.value)} rows={3} placeholder="Add a note..." className="w-full rounded border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-white outline-none focus:border-blue-500" />
                  <div className="flex items-center gap-3 mt-2">
                    <label className="flex items-center gap-1 text-xs text-slate-400">
                      <input type="checkbox" checked={internalNote} onChange={e => setInternalNote(e.target.checked)} />
                      Internal note
                    </label>
                    <button onClick={() => addNote(selected.id!)} disabled={busy || !noteText.trim()} className="target-min rounded bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
                      Add note
                    </button>
                  </div>
                </div>
              </div>
            )}

            {notes.length > 0 && (
              <div className="mt-4 border-t border-slate-800 pt-3">
                <h4 className="text-xs font-semibold text-slate-400 mb-2">Notes ({notes.length})</h4>
                {notes.map(n => (
                  <div key={n.id} className="mb-2 p-2 rounded bg-slate-800/50 text-xs">
                    <div className="text-slate-400">{n.authorName} · {n.createdAt.slice(0, 16)}{n.internal ? ' · internal' : ''}</div>
                    <div className="text-slate-200 mt-1 whitespace-pre-wrap">{n.note}</div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
