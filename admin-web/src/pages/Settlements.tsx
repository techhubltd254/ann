import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import Skeleton from '../components/Skeleton'

interface Settlement {
  id: number
  provider: string
  periodStart: string | null
  periodEnd: string | null
  totalAmount: number
  paymentCount: number
  status: 'PENDING' | 'APPROVED' | 'PAID' | 'FAILED'
  createdAt: string
  approvedAt: string | null
  approvedByUserId: number | null
  paidAt: string | null
  paidByUserId: number | null
  payoutRef: string | null
  failureReason: string | null
}

interface County { id: number; name: string; slug: string }

interface StatementLine { bookingReference: string; total: number; kiccShare: number; countyShare: number; paidAt: string }
interface CountyStatement { countySlug: string; countyName: string; kiccShare: number; countyShare: number; total: number; lines: StatementLine[] }

function fmt(n: number) { return n.toLocaleString('en-KE', { style: 'currency', currency: 'KES', minimumFractionDigits: 0 }) }

export default function Settlements() {
  const [settlements, setSettlements] = useState<Settlement[]>([])
  const [counties, setCounties] = useState<County[]>([])
  const [statementCounty, setStatementCounty] = useState('')
  const [statementFrom, setStatementFrom] = useState('')
  const [statementTo, setStatementTo] = useState('')
  const [statement, setStatement] = useState<CountyStatement | null>(null)
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [payoutRefs, setPayoutRefs] = useState<Record<number, string>>({})
  const [failReasons, setFailReasons] = useState<Record<number, string>>({})

  const load = async () => {
    setLoading(true)
    try {
      setSettlements(await api.get<Settlement[]>('/payments/settlements'))
    } catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }

  const loadCounties = async () => {
    try {
      setCounties(await api.get<County[]>('/data/counties'))
    } catch { /* ignore */ }
  }

  useEffect(() => { load(); loadCounties() }, [])

  const act = async (id: number, action: 'approve' | 'pay' | 'fail', body?: unknown) => {
    setBusy(true)
    setError('')
    try {
      await api.post(`/payments/settlements/${id}/${action}`, body ?? {})
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const loadStatement = async () => {
    setBusy(true)
    setError('')
    try {
      const params = new URLSearchParams()
      if (statementCounty) params.set('countySlug', statementCounty)
      if (statementFrom) params.set('from', statementFrom)
      if (statementTo) params.set('to', statementTo)
      setStatement(await api.get<CountyStatement>(`/payments/statements?${params}`))
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const statusBadge = (s: string) => {
    const map: Record<string, string> = { PENDING: 'bg-yellow-800 text-yellow-200', APPROVED: 'bg-blue-800 text-blue-200', PAID: 'bg-green-800 text-green-200', FAILED: 'bg-red-800 text-red-200' }
    return `rounded px-2 py-0.5 text-xs font-semibold ${map[s] || 'bg-slate-800 text-slate-300'}`
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Settlements</h1>
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <div className="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
        <h2 className="text-lg font-semibold text-white mb-3">Revenue Statement</h2>
        <div className="flex flex-wrap gap-3 items-end">
          <label className="block">
            <span className="text-xs text-slate-400">County</span>
            <select value={statementCounty} onChange={e => setStatementCounty(e.target.value)} className="mt-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500">
              <option value="">All counties</option>
              {counties.map(c => <option key={c.slug} value={c.slug}>{c.name}</option>)}
            </select>
          </label>
          <label className="block">
            <span className="text-xs text-slate-400">From</span>
            <input type="date" value={statementFrom} onChange={e => setStatementFrom(e.target.value)} className="mt-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500" />
          </label>
          <label className="block">
            <span className="text-xs text-slate-400">To</span>
            <input type="date" value={statementTo} onChange={e => setStatementTo(e.target.value)} className="mt-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500" />
          </label>
          <button onClick={loadStatement} disabled={busy} className="target-min rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
            View
          </button>
        </div>
        {statement && (
          <div className="mt-4 space-y-2 text-sm">
            <div className="flex gap-6 text-xs text-slate-400">
              <span>Total: <strong className="text-white">{fmt(statement.total)}</strong></span>
              <span>KICC (80%): <strong className="text-green-300">{fmt(statement.kiccShare)}</strong></span>
              <span>County (20%): <strong className="text-blue-300">{fmt(statement.countyShare)}</strong></span>
              <span>Lines: {statement.lines.length}</span>
            </div>
            {statement.lines.slice(0, 30).map((l, i) => (
              <div key={i} className="flex justify-between border-b border-slate-800 py-1 text-xs">
                <span className="text-slate-300">{l.bookingReference}</span>
                <span className="text-white">{fmt(l.total)}</span>
                <span className="text-green-400">{fmt(l.kiccShare)}</span>
                <span className="text-blue-400">{fmt(l.countyShare)}</span>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="overflow-auto" aria-busy={loading ? 'true' : undefined}>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-800 text-left text-slate-400">
              <th className="pb-2 pr-4">ID</th>
              <th className="pb-2 pr-4">Provider</th>
              <th className="pb-2 pr-4">Period</th>
              <th className="pb-2 pr-4 text-right">Amount</th>
              <th className="pb-2 pr-4 text-right">Payments</th>
              <th className="pb-2 pr-4">Status</th>
              <th className="pb-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading && settlements.length === 0 ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-slate-800/50">
                  <td colSpan={7} className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
                </tr>
              ))
            ) : settlements.length === 0 ? (
              <tr>
                <td colSpan={7} className="py-6">
                  <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <div className="text-3xl mb-2" aria-hidden>🗂️</div>
                    <div className="text-sm text-slate-400">No settlements found.</div>
                  </div>
                </td>
              </tr>
            ) : settlements.map(s => (
              <tr key={s.id} className="border-b border-slate-800/50 hover:bg-slate-900/50">
                <td className="py-2 pr-4 text-slate-400">{s.id}</td>
                <td className="py-2 pr-4 text-slate-300">{s.provider}</td>
                <td className="py-2 pr-4 text-slate-400 text-xs">
                  {s.periodStart?.slice(0, 10) || '-'} → {s.periodEnd?.slice(0, 10) || '-'}
                </td>
                <td className="py-2 pr-4 text-right text-white">{fmt(s.totalAmount)}</td>
                <td className="py-2 pr-4 text-right text-slate-400">{s.paymentCount}</td>
                <td className="py-2 pr-4"><span className={statusBadge(s.status)}>{s.status}</span></td>
                <td className="py-2">
                  <div className="flex gap-2 items-center">
                    {s.status === 'PENDING' && (
                      <button onClick={() => act(s.id, 'approve')} disabled={busy} className="target-min rounded bg-blue-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
                        Approve
                      </button>
                    )}
                    {s.status === 'APPROVED' && (
                      <>
                        <input
                          type="text"
                          placeholder="Payout reference"
                          value={payoutRefs[s.id] || ''}
                          onChange={e => setPayoutRefs({ ...payoutRefs, [s.id]: e.target.value })}
                          className="w-32 rounded border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-white outline-none focus:border-blue-500"
                        />
                        <button onClick={() => act(s.id, 'pay', { payoutRef: payoutRefs[s.id] || '' })} disabled={busy || !payoutRefs[s.id]} className="target-min rounded bg-green-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-green-600 disabled:opacity-50">
                          Pay
                        </button>
                      </>
                    )}
                    {s.status !== 'PAID' && s.status !== 'FAILED' && (
                      <>
                        <input
                          type="text"
                          placeholder="Reason"
                          value={failReasons[s.id] || ''}
                          onChange={e => setFailReasons({ ...failReasons, [s.id]: e.target.value })}
                          className="w-32 rounded border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-white outline-none focus:border-red-500"
                        />
                        <button onClick={() => act(s.id, 'fail', { reason: failReasons[s.id] || '' })} disabled={busy || !failReasons[s.id]} className="target-min rounded bg-red-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-600 disabled:opacity-50">
                          Fail
                        </button>
                      </>
                    )}
                    {s.status === 'PAID' && s.payoutRef && <span className="text-xs text-green-400">{s.payoutRef}</span>}
                    {s.status === 'FAILED' && s.failureReason && <span className="text-xs text-red-300">{s.failureReason}</span>}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
