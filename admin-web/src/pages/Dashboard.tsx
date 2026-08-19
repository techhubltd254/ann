import { useState, useEffect } from 'react'
import { useAuth } from '../lib/auth'
import { api } from '../lib/api'
import { DashAdmin, DashCounty, DashExhibitor, DashBuyer, DashSeller } from '../shared/dashboards'

export default function DashboardPage() {
  const { me, logout } = useAuth()
  const [analytics, setAnalytics] = useState<any>(null)
  const [settlements, setSettlements] = useState<any[]>([])
  const [complaints, setComplaints] = useState<any[]>([])
  const [users, setUsers] = useState<any[]>([])

  useEffect(() => {
    if (!me) return
    api.get('/analytics').then(setAnalytics).catch(() => {})
    api.get<any[]>('/payments/settlements').then(setSettlements).catch(() => {})
    api.get<any[]>('/complaints').then(setComplaints).catch(() => {})
    if (me.privileges?.includes('USERS_MANAGE')) api.get<any[]>('/admin/users').then(setUsers).catch(() => {})
  }, [me])

  const navigateTo = (path: string) => { window.location.href = path }

  if (!me) return <div className="flex min-h-screen items-center justify-center bg-[#07090F] text-white/40">Loading...</div>

  const shared = { analytics, settlements, complaints, users, realCounties: null as any, realProducts: null as any }

  switch (me.tier) {
    case 'KICC': return <DashAdminWrapper {...shared} logout={logout} navigate={navigateTo} />
    case 'NATIONAL':
    case 'COUNTY': return <DashCountyWrapper {...shared} logout={logout} navigate={navigateTo} />
    case 'EXHIBITOR': return <DashExhibitorWrapper {...shared} logout={logout} navigate={navigateTo} />
    default: return <DashBuyerWrapper {...shared} logout={logout} navigate={navigateTo} />
  }
}

function DashAdminWrapper(props: any) {
  return (
    <div className="min-h-screen bg-[#07090F]" style={{ fontFamily: "'Montserrat', sans-serif" }}>
      <style>{`* { font-family: 'Montserrat', sans-serif; }`}</style>
      <div className="p-4 border-b border-white/10 flex justify-between items-center">
        <span className="text-white font-bold">KICC Platform Admin</span>
        <button onClick={props.logout} className="text-xs text-white/40 hover:text-white">Sign out</button>
      </div>
      <DashAdmin navigate={props.navigate} />
    </div>
  )
}

function DashCountyWrapper(props: any) {
  return (
    <div className="min-h-screen bg-[#07090F]" style={{ fontFamily: "'Montserrat', sans-serif" }}>
      <style>{`* { font-family: 'Montserrat', sans-serif; }`}</style>
      <div className="p-4 border-b border-white/10 flex justify-between items-center">
        <span className="text-white font-bold">County Dashboard</span>
        <button onClick={props.logout} className="text-xs text-white/40 hover:text-white">Sign out</button>
      </div>
      <DashCounty navigate={props.navigate} />
    </div>
  )
}

function DashExhibitorWrapper(props: any) {
  return (
    <div className="min-h-screen bg-[#07090F]" style={{ fontFamily: "'Montserrat', sans-serif" }}>
      <style>{`* { font-family: 'Montserrat', sans-serif; }`}</style>
      <div className="p-4 border-b border-white/10 flex justify-between items-center">
        <span className="text-white font-bold">Exhibitor Dashboard</span>
        <button onClick={props.logout} className="text-xs text-white/40 hover:text-white">Sign out</button>
      </div>
      <DashExhibitor navigate={props.navigate} />
    </div>
  )
}

function DashBuyerWrapper(props: any) {
  return <div className="min-h-screen bg-[#07090F] flex items-center justify-center text-white/40">Buyer dashboard — coming soon</div>
}
