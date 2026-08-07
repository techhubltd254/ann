import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import Skeleton from '../components/Skeleton'

interface Analytics {
  bookings: Record<string, number>
  payments: { byStatus: Record<string, number>; byMethod: Record<string, number> }
  contentByCounty: Array<{ county: string; slug: string; attractions: number; hotels: number; farms: number; products: number }>
  complaints: { byStatus: Record<string, number>; byPriority: Record<string, number> }
  totalRevenue: number
}

function fmt(n: number) { return n.toLocaleString('en-KE', { style: 'currency', currency: 'KES', minimumFractionDigits: 0 }) }

export default function Analytics() {
  const [data, setData] = useState<Analytics | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    api.get<Analytics>('/analytics').then(setData).catch(e => setError((e as Error).message)).finally(() => setLoading(false))
  }, [])

  if (error) return <div role="alert" className="text-red-400 text-sm p-6">{error}</div>

  if (loading || !data) return (
    <div aria-busy="true">
      <h1 className="text-2xl font-bold text-white mb-4">Analytics</h1>
      <div className="grid grid-cols-4 gap-4 mb-6">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <Skeleton className="h-3 w-1/3 mb-3" />
            <Skeleton className="h-7 w-1/2" />
          </div>
        ))}
      </div>
      <Skeleton className="h-6 w-48 mb-3" />
      <div className="overflow-auto">
        <table className="w-full text-sm">
          <tbody>
            {Array.from({ length: 5 }).map((_, i) => (
              <tr key={i} className="border-b border-slate-800/50">
                <td className="py-3 pr-4"><Skeleton className="h-8 w-full rounded-lg" /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )

  return (
    <div>
      <h1 className="text-2xl font-bold text-white mb-4">Analytics</h1>

      <div className="grid grid-cols-4 gap-4 mb-6">
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <div className="text-xs text-slate-400">Total Revenue</div>
          <div className="text-xl font-bold text-green-300 mt-1">{fmt(data.totalRevenue)}</div>
        </div>
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <div className="text-xs text-slate-400">Bookings</div>
          <div className="text-xl font-bold text-white mt-1">{Object.values(data.bookings).reduce((a, b) => a + b, 0)}</div>
          <div className="text-xs text-slate-400 mt-1">
            {Object.entries(data.bookings).map(([k, v]) => `${k} ${v}`).join(' · ')}
          </div>
        </div>
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <div className="text-xs text-slate-400">Payments</div>
          <div className="text-xs text-slate-400 mt-2">
            {Object.entries(data.payments.byStatus).map(([k, v]) => <div key={k}>{k}: {v}</div>)}
          </div>
        </div>
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <div className="text-xs text-slate-400">Complaints</div>
          <div className="text-xs text-slate-400 mt-2">
            {Object.entries(data.complaints.byStatus).map(([k, v]) => <div key={k}>{k}: {v}</div>)}
          </div>
        </div>
      </div>

      <h2 className="text-lg font-semibold text-white mb-3">Content by County</h2>
      <div className="overflow-auto">
        {data.contentByCounty.length === 0 ? (
          <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
            <div className="text-3xl mb-2" aria-hidden>🗂️</div>
            <div className="text-sm text-slate-400">No counties found.</div>
          </div>
        ) : (
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-800 text-left text-slate-400">
              <th className="pb-2 pr-4">County</th>
              <th className="pb-2 pr-4 text-right">Attractions</th>
              <th className="pb-2 pr-4 text-right">Hotels</th>
              <th className="pb-2 pr-4 text-right">Farms</th>
              <th className="pb-2 pr-4 text-right">Products</th>
            </tr>
          </thead>
          <tbody>
            {data.contentByCounty.map(c => (
              <tr key={c.slug} className="border-b border-slate-800/50 hover:bg-slate-900/50">
                <td className="py-2 pr-4 text-white">{c.county}</td>
                <td className="py-2 pr-4 text-right text-slate-300">{c.attractions}</td>
                <td className="py-2 pr-4 text-right text-slate-300">{c.hotels}</td>
                <td className="py-2 pr-4 text-right text-slate-300">{c.farms}</td>
                <td className="py-2 pr-4 text-right text-slate-300">{c.products}</td>
              </tr>
            ))}
          </tbody>
        </table>
        )}
      </div>
    </div>
  )
}
