import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import Skeleton from '../components/Skeleton'
import ConfirmDialog from '../components/ConfirmDialog'

interface Payment {
  id: number
  bookingId: number | null
  amount: number
  currency: string
  provider: string
  method: string
  status: 'INITIATED' | 'PENDING' | 'SUCCESS' | 'FAILED' | 'REFUNDED'
  providerRef: string | null
  phone: string | null
  description: string | null
  failureReason: string | null
  initiatedByUserId: number
  initiatedAt: string
  completedAt: string | null
}

function fmt(n: number) { return n.toLocaleString('en-KE', { style: 'currency', currency: 'KES', minimumFractionDigits: 0 }) }

export default function Payments() {
  const [payments, setPayments] = useState<Payment[]>([])
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [confirm, setConfirm] = useState<{ title: string; message: string; run: () => void } | null>(null)

  const load = async () => {
    setLoading(true)
    try {
      setPayments(await api.get<Payment[]>('/payments'))
    } catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [])

  const act = async (id: number, action: 'verify' | 'refund') => {
    setBusy(true)
    setError('')
    try {
      await api.post(`/payments/${id}/${action}`, {})
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const confirmRefund = (p: Payment) => {
    setConfirm({
      title: 'Refund payment',
      message: `Refund ${fmt(p.amount)} for payment #${p.id}? This sends money back to the payer.`,
      run: () => act(p.id, 'refund')
    })
  }

  const statusBadge = (s: string) => {
    const map: Record<string, string> = {
      INITIATED: 'bg-slate-800 text-slate-300', PENDING: 'bg-yellow-800 text-yellow-200',
      SUCCESS: 'bg-green-800 text-green-200', FAILED: 'bg-red-800 text-red-200', REFUNDED: 'bg-purple-800 text-purple-200'
    }
    return `rounded px-2 py-0.5 text-xs font-semibold ${map[s] || 'bg-slate-800 text-slate-300'}`
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Payments</h1>
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <div className="overflow-auto" aria-busy={loading ? 'true' : undefined}>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-800 text-left text-slate-400">
              <th className="pb-2 pr-4">ID</th>
              <th className="pb-2 pr-4">Booking</th>
              <th className="pb-2 pr-4">Method</th>
              <th className="pb-2 pr-4 text-right">Amount</th>
              <th className="pb-2 pr-4">Status</th>
              <th className="pb-2 pr-4">Date</th>
              <th className="pb-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading && payments.length === 0 ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-slate-800/50">
                  <td colSpan={7} className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
                </tr>
              ))
            ) : payments.length === 0 ? (
              <tr>
                <td colSpan={7} className="py-6">
                  <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <div className="text-3xl mb-2" aria-hidden>🗂️</div>
                    <div className="text-sm text-slate-400">No payments found.</div>
                  </div>
                </td>
              </tr>
            ) : payments.map(p => (
              <tr key={p.id} className="border-b border-slate-800/50 hover:bg-slate-900/50">
                <td className="py-2 pr-4 text-slate-400">{p.id}</td>
                <td className="py-2 pr-4 text-slate-300">{p.bookingId ? `#${p.bookingId}` : '-'}</td>
                <td className="py-2 pr-4 text-slate-400 text-xs">{p.method?.toUpperCase()}</td>
                <td className="py-2 pr-4 text-right text-white">{fmt(p.amount)}</td>
                <td className="py-2 pr-4"><span className={statusBadge(p.status)}>{p.status}</span></td>
                <td className="py-2 pr-4 text-slate-400 text-xs">{p.initiatedAt?.slice(0, 16) || '-'}</td>
                <td className="py-2">
                  <div className="flex gap-2">
                    {(p.status === 'INITIATED' || p.status === 'PENDING') && (
                      <button onClick={() => act(p.id, 'verify')} disabled={busy} className="target-min rounded bg-blue-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
                        Verify
                      </button>
                    )}
                    {p.status === 'SUCCESS' && (
                      <button onClick={() => confirmRefund(p)} disabled={busy} className="target-min rounded bg-red-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-600 disabled:opacity-50">
                        Refund
                      </button>
                    )}
                    {p.failureReason && <span className="text-xs text-red-300">{p.failureReason}</span>}
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
        confirmLabel="Refund"
        busy={busy}
        onConfirm={() => { const fn = confirm; setConfirm(null); if (fn) fn.run() }}
        onCancel={() => setConfirm(null)}
      />
    </div>
  )
}
