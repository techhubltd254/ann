import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'
import Skeleton from '../components/Skeleton'
import ConfirmDialog from '../components/ConfirmDialog'

interface KeOrder {
  id: number
  orderReference: string
  userId: number
  status: string
  subtotal: number
  tax: number
  total: number
  notes: string | null
  countySlug: string | null
  createdAt: string
  updatedAt: string | null
}

interface OrderItem {
  id: number
  orderId: number
  productId: number
  productName: string
  quantity: number
  unitPrice: number
  subtotal: number
}

function fmt(n: number) { return n.toLocaleString('en-KE', { style: 'currency', currency: 'KES', minimumFractionDigits: 0 }) }

const STATUS_FLOW: Record<string, string[]> = {
  PENDING: ['CONFIRMED', 'CANCELLED'],
  CONFIRMED: ['PROCESSING', 'CANCELLED'],
  PROCESSING: ['SHIPPED', 'CANCELLED'],
  SHIPPED: ['DELIVERED', 'CANCELLED'],
  DELIVERED: [],
  CANCELLED: []
}

export default function Orders() {
  const { me } = useAuth()
  const [orders, setOrders] = useState<KeOrder[]>([])
  const [detail, setDetail] = useState<{ order: KeOrder; items: OrderItem[] } | null>(null)
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')
  const [confirm, setConfirm] = useState<{ title: string; message: string; run: () => void } | null>(null)

  const load = async () => {
    setLoading(true)
    try { setOrders(await api.get<KeOrder[]>('/admin/orders')) }
    catch (e) { setError((e as Error).message) }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [])

  const viewDetail = async (id: number) => {
    try { setDetail(await api.get<{ order: KeOrder; items: OrderItem[] }>(`/admin/orders/${id}`)) }
    catch (e) { setError((e as Error).message) }
  }

  const changeStatus = async (id: number, status: string) => {
    setBusy(true)
    setError('')
    try {
      await api.post(`/admin/orders/${id}/status`, { status })
      setMsg(`Order → ${status}`)
      await load()
      if (detail?.order?.id === id) {
        setDetail(await api.get(`/admin/orders/${id}`))
      }
    } catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const onStatusClick = (o: KeOrder, s: string) => {
    if (s === 'CANCELLED') {
      setConfirm({
        title: 'Cancel order',
        message: `Cancel order ${o.orderReference}? This cannot be undone.`,
        run: () => changeStatus(o.id, s)
      })
    } else {
      changeStatus(o.id, s)
    }
  }

  const badge = (s: string) => {
    const m: Record<string, string> = {
      PENDING: 'bg-yellow-800 text-yellow-200', CONFIRMED: 'bg-blue-800 text-blue-200',
      PROCESSING: 'bg-purple-800 text-purple-200', SHIPPED: 'bg-cyan-800 text-cyan-200',
      DELIVERED: 'bg-green-800 text-green-200', CANCELLED: 'bg-red-800 text-red-200'
    }
    return `rounded px-2 py-0.5 text-xs font-semibold ${m[s] || 'bg-slate-800 text-slate-300'}`
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Orders</h1>
      {msg && <div role="status" className="mb-4 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300">{msg}</div>}
      {error && <div role="alert" className="mb-4 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <div className="flex gap-6">
        <div className="flex-1 overflow-auto" aria-busy={loading ? 'true' : undefined}>
          {loading && orders.length === 0 ? (
            Array.from({ length: 5 }).map((_, i) => (
              <div key={i} className="mb-3 rounded-xl border border-slate-800 bg-slate-900 p-4">
                <Skeleton className="h-8 w-full rounded-lg" />
              </div>
            ))
          ) : orders.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
              <div className="text-3xl mb-2" aria-hidden>🗂️</div>
              <div className="text-sm text-slate-400">No orders found.</div>
            </div>
          ) : (
          orders.map(o => (
            <div key={o.id} onClick={() => viewDetail(o.id!)} className={`mb-3 rounded-xl border border-slate-800 bg-slate-900 p-4 cursor-pointer ${detail?.order?.id === o.id ? 'ring-1 ring-blue-500' : ''}`}>
              <div className="flex items-center justify-between flex-wrap gap-2">
                <div>
                  <span className="font-mono text-sm text-white">{o.orderReference}</span>
                  <span className="ml-2">{badge(o.status)}</span>
                </div>
                <div className="text-right">
                  <div className="text-white font-semibold">{fmt(o.total)}</div>
                  <div className="text-xs text-slate-400">{o.createdAt?.slice(0, 19)}</div>
                </div>
              </div>
              <div className="mt-1 text-xs text-slate-400">
                Subtotal: {fmt(o.subtotal)} · VAT: {fmt(o.tax)}
                {o.countySlug && <> · County: {o.countySlug}</>}
              </div>
              {o.notes && <div className="mt-1 text-xs text-slate-400">Notes: {o.notes}</div>}
            </div>
          ))
          )}
        </div>

        {detail && (
          <div className="w-96 flex-shrink-0 rounded-xl border border-slate-800 bg-slate-900 p-4">
            <h2 className="font-semibold text-white">{detail.order.orderReference}</h2>
            <div className="mt-2 text-xs text-slate-400 space-y-1">
              <div>Status: <span className={badge(detail.order.status)}>{detail.order.status}</span></div>
              <div>Subtotal: {fmt(detail.order.subtotal)} · VAT: {fmt(detail.order.tax)} · Total: <strong className="text-white">{fmt(detail.order.total)}</strong></div>
            </div>

            {STATUS_FLOW[detail.order.status]?.length > 0 && (
              <div className="mt-3 flex gap-2">
                {STATUS_FLOW[detail.order.status]?.map(s => (
                  <button key={s} onClick={() => onStatusClick(detail.order, s)} disabled={busy} className="target-min rounded bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-600 disabled:opacity-50">
                    → {s}
                  </button>
                ))}
              </div>
            )}

            <h3 className="mt-4 text-xs font-semibold text-slate-400 mb-2">Items</h3>
            {detail.items.map(item => (
              <div key={item.id} className="flex justify-between py-1.5 border-b border-slate-800 text-xs">
                <div>
                  <span className="text-slate-200">{item.productName}</span>
                  <span className="text-slate-500 ml-1">×{item.quantity}</span>
                </div>
                <span className="text-white">{fmt(item.subtotal)}</span>
              </div>
            ))}
          </div>
        )}
      </div>

      <ConfirmDialog
        open={confirm !== null}
        title={confirm?.title ?? ''}
        message={confirm?.message ?? ''}
        confirmLabel="Cancel"
        busy={busy}
        onConfirm={() => { const fn = confirm; setConfirm(null); if (fn) fn.run() }}
        onCancel={() => setConfirm(null)}
      />
    </div>
  )
}
