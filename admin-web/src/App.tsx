import { Navigate, Route, Routes } from 'react-router-dom'
import { useAuth } from './lib/auth'
import { hasAdminAccess } from './lib/api'
import Landing from './pages/Landing'
import Apply from './pages/Apply'
import Result from './pages/Result'
import Login from './pages/Login'
import Complaint from './pages/Complaint'
import TrackComplaint from './pages/TrackComplaint'
import Review from './pages/Review'
import Settlements from './pages/Settlements'
import Payments from './pages/Payments'
import Counties from './pages/Counties'
import National from './pages/National'
import Sectors from './pages/Sectors'
import Complaints from './pages/Complaints'
import Users from './pages/Users'
import Bookings from './pages/Bookings'
import Analytics from './pages/Analytics'
import Delegations from './pages/Delegations'
import AdminAudit from './pages/Audit'
import Orders from './pages/Orders'
import DashboardPage from './pages/Dashboard'
import CountyDashboard from './pages/CountyDashboard'
import MediaLibrary from './pages/MediaLibrary'
import { AdminLayout, RequirePrivilege } from './pages/AdminLayout'
import AppSkeleton from './components/AppSkeleton'

function RequireAdmin({ children }: { children: React.ReactNode }) {
  const { me, loading } = useAuth()
  if (loading) return <AppSkeleton />
  if (!me) return <Navigate to="/login" replace />
  if (!hasAdminAccess(me.privileges)) return <Navigate to="/" replace />
  return <>{children}</>
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Landing />} />
      <Route path="/apply" element={<Apply />} />
      <Route path="/result" element={<Result />} />
      <Route path="/login" element={<Login />} />
      <Route path="/complaint" element={<Complaint />} />
      <Route path="/track" element={<TrackComplaint />} />
      <Route path="/track/:ref" element={<TrackComplaint />} />
      <Route path="/admin" element={<RequireAdmin><Navigate to="/admin/dashboard" replace /></RequireAdmin>} />
      <Route path="/admin/review" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="USERS_MANAGE"><Review /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/settlements" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="PAYMENTS_MANAGE"><Settlements /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/payments" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="PAYMENTS_MANAGE"><Payments /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/counties" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="CONTENT_MANAGE"><Counties /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/national" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="CONTENT_MANAGE"><National /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/sectors" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="CONTENT_MANAGE"><Sectors /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/complaints" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="USERS_MANAGE"><Complaints /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/users" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="USERS_MANAGE"><Users /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/bookings" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="BOOKINGS_MANAGE"><Bookings /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/analytics" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="ANALYTICS_VIEW"><Analytics /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/delegations" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="DELEGATE"><Delegations /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/audit" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="DELEGATE"><AdminAudit /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/orders" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="MARKETPLACE_MANAGE"><Orders /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="/admin/dashboard" element={<RequireAdmin><AdminLayout><DashboardPage /></AdminLayout></RequireAdmin>} />
      <Route path="/admin/county/:slug" element={<RequireAdmin><AdminLayout><CountyDashboard /></AdminLayout></RequireAdmin>} />
      <Route path="/admin/media" element={<RequireAdmin><AdminLayout><RequirePrivilege privilege="MEDIA_MANAGE"><MediaLibrary /></RequirePrivilege></AdminLayout></RequireAdmin>} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
