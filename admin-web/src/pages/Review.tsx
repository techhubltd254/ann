import { useEffect, useState } from 'react'

interface Applicant {
  id: number
  orgName: string
  contactName: string
  email: string
  phone: string | null
  countySlug: string | null
  score: number
  route: string
  answers: Record<string, string>
  status: string
  createdAt: string
  provisionedUserId: number | null
}

export default function Review() {
  const [rows, setRows] = useState<Applicant[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [flash, setFlash] = useState('')

  const load = async () => {
    setLoading(true)
    try {
      const res = await fetch('/api/onboarding/applicants', { credentials: 'include' })
      const body = await res.json().catch(() => [])
      if (!res.ok) throw new Error(body.error ?? `HTTP ${res.status}`)
      setRows(body)
    } catch (e) {
      setError((e as Error).message)
    }
    setLoading(false)
  }

  useEffect(() => {
    load()
  }, [])

  const act = async (id: number, action: 'approve' | 'reject') => {
    setFlash('')
    try {
      const res = await fetch(`/api/onboarding/applicants/${id}/${action}`, {
        method: 'POST',
        credentials: 'include'
      })
      const body = await res.json().catch(() => ({}))
      if (!res.ok) throw new Error(body.error ?? `HTTP ${res.status}`)
      if (action === 'approve' && body.provisionedPassword) {
        setFlash(`Approved — ${body.provisionedEmail} / ${body.provisionedPassword} (share once)`)
      } else {
        setFlash('Done')
      }
      await load()
    } catch (e) {
      setError((e as Error).message)
    }
  }

  const badge = (s: string) =>
    s === 'APPROVED' ? 'bg-emerald-500/15 text-emerald-300' : s === 'REJECTED' ? 'bg-red-500/15 text-red-300' : 'bg-amber-500/15 text-amber-300'

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-800 px-6 py-5 flex items-center justify-between">
        <span className="font-semibold text-white">Onboarding review</span>
        <a href="/" className="text-sm text-slate-400 hover:text-white">Home</a>
      </header>

      <main className="mx-auto max-w-4xl px-6 py-8">
        {flash !== '' && <div className="mb-4 rounded-lg border border-emerald-800 bg-emerald-950/50 p-3 text-sm text-emerald-200">{flash}</div>}
        {error !== '' && <div className="mb-4 rounded-lg border border-red-800 bg-red-950/50 p-3 text-sm text-red-300">{error}</div>}

        {loading ? (
          <p className="text-slate-400">Loading…</p>
        ) : rows.length === 0 ? (
          <p className="text-slate-400">No applications yet.</p>
        ) : (
          <div className="space-y-4">
            {rows.map((a) => (
              <div key={a.id} className="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <h3 className="font-semibold text-white">{a.orgName}</h3>
                    <p className="text-sm text-slate-400">
                      {a.contactName} · {a.email} {a.countySlug ? `· ${a.countySlug}` : ''} · {new Date(a.createdAt).toLocaleString()}
                    </p>
                    <p className="mt-1 text-xs text-slate-500">
                      score {a.score} · route <span className="text-blue-300">{a.route}</span>
                    </p>
                  </div>
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold ${badge(a.status)}`}>{a.status}</span>
                </div>

                {a.status === 'PENDING' && (
                  <div className="mt-4 flex gap-3">
                    <button
                      onClick={() => act(a.id, 'approve')}
                      className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                    >
                      Approve & provision
                    </button>
                    <button
                      onClick={() => act(a.id, 'reject')}
                      className="rounded-lg border border-red-800 px-4 py-2 text-sm font-semibold text-red-300 hover:bg-red-950"
                    >
                      Reject
                    </button>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  )
}
