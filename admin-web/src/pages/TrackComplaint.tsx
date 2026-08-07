import { useState } from 'react'
import { useParams } from 'react-router-dom'

interface ComplaintStatus {
  referenceNo: string
  status: string
  resolutionNote: string | null
  createdAt: string
  updatedAt: string | null
}

export default function TrackComplaint() {
  const { ref } = useParams()
  const [searchRef, setSearchRef] = useState(ref || '')
  const [result, setResult] = useState<ComplaintStatus | null>(null)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const lookup = async (reference: string) => {
    setBusy(true)
    setError('')
    setResult(null)
    try {
      const res = await fetch(`/api/public/complaints/${reference}`)
      if (!res.ok) {
        const body = await res.json().catch(() => ({}))
        throw new Error(body.error ?? 'Complaint not found')
      }
      setResult(await res.json())
    } catch (e) {
      setError((e as Error).message)
    } finally {
      setBusy(false)
    }
  }

  const statusColor = (s: string) => {
    const m: Record<string, string> = { OPEN: 'text-yellow-300', IN_PROGRESS: 'text-blue-300', RESOLVED: 'text-green-300', CLOSED: 'text-slate-300' }
    return m[s] || 'text-slate-300'
  }

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-800 px-6 py-5">
        <a href="/" className="text-sm text-slate-400 hover:text-white">← Back to home</a>
      </header>
      <main className="mx-auto max-w-xl px-6 py-10">
        <h1 className="text-2xl font-bold text-white">Track a complaint</h1>
        <p className="mt-2 text-sm text-slate-400">Enter your reference number to check the status of your complaint.</p>

        <div className="mt-6 rounded-xl border border-slate-800 bg-slate-900 p-6">
          <div className="flex gap-3">
            <input
              type="text"
              value={searchRef}
              onChange={e => setSearchRef(e.target.value)}
              placeholder="e.g. KICC-CMP-9PLM3Z"
              className="flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500 font-mono"
            />
            <button
              onClick={() => lookup(searchRef)}
              disabled={busy || !searchRef.trim()}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {busy ? '…' : 'Lookup'}
            </button>
          </div>

          {error && <div className="mt-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

          {result && (
            <div className="mt-4 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs text-slate-400 font-mono">{result.referenceNo}</span>
                <span className={`rounded px-2 py-0.5 text-xs font-semibold bg-slate-800 ${statusColor(result.status)}`}>{result.status}</span>
              </div>
              <div className="text-xs text-slate-500">
                Submitted: {result.createdAt?.slice(0, 19)}
                {result.updatedAt && <> · Updated: {result.updatedAt.slice(0, 19)}</>}
              </div>
              {result.resolutionNote && (
                <div className="rounded border border-green-800 bg-green-950/40 p-3 text-sm text-green-300">
                  {result.resolutionNote}
                </div>
              )}
              {result.status === 'RESOLVED' && (
                <div className="text-xs text-slate-400">Your complaint has been resolved. If you are not satisfied, you may <a href="/complaint" className="text-blue-400 hover:text-blue-300 underline">submit a new complaint</a>.</div>
              )}
              {result.status === 'CLOSED' && (
                <div className="text-xs text-slate-400">This complaint has been closed. <a href="/complaint" className="text-blue-400 hover:text-blue-300 underline">File a new one</a> if needed.</div>
              )}
            </div>
          )}
        </div>

        <div className="mt-4 text-sm text-slate-500">
          Don't have a reference? <a href="/complaint" className="text-blue-400 hover:text-blue-300 underline">Submit a new complaint</a>
        </div>
      </main>
    </div>
  )
}
