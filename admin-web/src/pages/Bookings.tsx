import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import Skeleton from '../components/Skeleton'
import ConfirmDialog from '../components/ConfirmDialog'

interface Booking {
  id: number
  bookingReference: string
  userId: number
  boothId: number | null
  exhibitionId: number | null
  quantity: number
  subtotal: number
  tax: number
  total: number
  currency: string
  status: string
  notes: string | null
  createdAt: string
  paidAt: string | null
  cancelledAt: string | null
}

interface Payment {
  id: number
  bookingId: number
  amount: number
  method: string
  status: string
  providerRef: string | null
  phone: string | null
  initiatedAt: string
  completedAt: string | null
}

const STATUS_FLOW: Record<string, string[]> = {
  PENDING: ['CONFIRMED', 'CANCELLED'],
  CONFIRMED: ['PAID', 'CANCELLED'],
  PAID: ['REFUNDED'],
  CANCELLED: [],
  REFUNDED: []
}

function fmt(n: number) { return n.toLocaleString('en-KE', { style: 'currency', currency: 'KES', minimumFractionDigits: 0 }) }

export default function Bookings() {
  const [bookings, setBookings] = useState<Booking[]>([])
  const [payments, setPayments] = useState<Payment[]>([])
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')
  const [confirm, setConfirm] = useState<{ title: string; message: string; run: () => void } | null>(null)

  const load = async () => {
    setLoading(true)
    try {
      const [b, p] = await Promise.all([
        api.get<Booking[]>('/bookings'),
        api.get<Payment[]>('/payments')
      ])
      setBookings(b)
      setPayments(p)
    } catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [])

  const changeStatus = async (id: number, status: string) => {
    setBusy(true)
    setError('')
    try {
      await api.patch(`/bookings/${id}/status`, { status })
      setMsg(`Booking → ${status}`)
      await load()
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const onStatusClick = (b: Booking, s: string) => {
    if (s === 'CANCELLED' || s === 'REFUNDED') {
      setConfirm({
        title: s === 'CANCELLED' ? 'Cancel booking' : 'Refund booking',
        message: `Change booking ${b.bookingReference} to ${s}?${s === 'CANCELLED' ? ' This cannot be undone.' : ' This sends money back to the payer.'}`,
        run: () => changeStatus(b.id!, s)
      })
    } else {
      changeStatus(b.id!, s)
    }
  }

  const paymentsFor = (bookingId: number) => payments.filter(p => p.bookingId === bookingId)

  const badge = (s: string) => {
    const m: Record<string, string> = {
      PENDING: 'bg-yellow-800 text-yellow-200', CONFIRMED: 'bg-blue-800 text-blue-200',
      PAID: 'bg-green-800 text-green-200', CANCELLED: 'bg-red-800 text-red-200', REFUNDED: 'bg-purple-800 text-purple-200',
      INITIATED: 'bg-slate-700 text-slate-300', SUCCESS: 'bg-green-800 text-green-200', FAILED: 'bg-red-800 text-red-200'
    }
    return `rounded px-2 py-0.5 text-xs font-semibold ${m[s] || 'bg-slate-800 text-slate-300'}`
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Bookings & Payments</h1>
      {msg && <div role="status" className="mb-4 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">{msg}</div>}
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <div className="overflow-auto" aria-busy={loading ? 'true' : undefined}>
        {loading && bookings.length === 0 ? (
          Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="mb-3 rounded-xl border border-slate-800 bg-slate-900 p-4">
              <Skeleton className="h-8 w-full rounded-lg" />
            </div>
          ))
        ) : bookings.length === 0 ? (
          <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
            <div className="text-3xl mb-2" aria-hidden>🗂️</div>
            <div className="text-sm text-slate-400">No bookings found.</div>
          </div>
        ) : (
        bookings.map(b => (
          <div key={b.id} className="mb-3 rounded-xl border border-slate-800 bg-slate-900 p-4">
            <div className="flex items-center justify-between flex-wrap gap-2">
              <div>
                <span className="font-mono text-sm text-white">{b.bookingReference}</span>
                <span className="ml-2 text-xs text-slate-400">booth #{b.boothId} · qty {b.quantity} · {fmt(b.total)}</span>
                <span className="ml-2">{badge(b.status)}</span>
              </div>
              <div className="flex gap-2">
                {STATUS_FLOW[b.status]?.map(s => (
                  <button key={s} onClick={() => onStatusClick(b, s)} disabled={busy} className="target-min rounded bg-blue-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
                    {s}
                  </button>
                ))}
              </div>
            </div>
            <div className="mt-1 text-xs text-slate-400">
              Created: {b.createdAt?.slice(0, 19)}
              {b.paidAt && <> · Paid: {b.paidAt.slice(0, 19)}</>}
              {b.cancelledAt && <> · Cancelled: {b.cancelledAt.slice(0, 19)}</>}
            </div>
            {paymentsFor(b.id!).length > 0 && (
              <div className="mt-3 border-t border-slate-800 pt-2">
                {paymentsFor(b.id!).map(p => (
                  <div key={p.id} className="flex items-center gap-3 text-xs py-1">
                    <span className="text-slate-400">#{p.id} {p.method?.toUpperCase()}</span>
                    <span className="text-white">{fmt(p.amount)}</span>
                    <span className={badge(p.status)}>{p.status}</span>
                    <span className="text-slate-500">{p.initiatedAt?.slice(0, 10)}</span>
                  </div>
                ))}
              </div>
            )}
          </div>
        ))
        )}
      </div>

      <ConfirmDialog
        open={confirm !== null}
        title={confirm?.title ?? ''}
        message={confirm?.message ?? ''}
        confirmLabel={confirm?.title.startsWith('Cancel') ? 'Cancel' : 'Refund'}
        busy={busy}
        onConfirm={() => { const fn = confirm; setConfirm(null); if (fn) fn.run() }}
        onCancel={() => setConfirm(null)}
      />
    </div>
  )
}
