import { useAuth } from '../lib/auth'
import { useT } from '../lib/i18n'
import { useNavigate, Link, useLocation, Navigate } from 'react-router-dom'
import type { ReactNode } from 'react'

function hasAnyPrivilege(privileges: string[]): boolean {
  return privileges.some(p => ['USERS_MANAGE', 'PAYMENTS_MANAGE', 'CONTENT_MANAGE', 'COUNTY_MANAGE', 'SECTOR_MANAGE', 'DELEGATE', 'ANALYTICS_VIEW', 'MARKETPLACE_MANAGE', 'BOOKINGS_MANAGE'].includes(p))
}

export function RequirePrivilege({ privilege, children }: { privilege: string; children: ReactNode }) {
  const { me, loading } = useAuth()
  if (loading) return <div className="flex min-h-screen items-center justify-center bg-kicc-dark text-white/40 font-sans">Loading…</div>
  if (!me) return <Navigate to="/login" replace />
  if (!me.privileges.includes(privilege)) return <Navigate to="/" replace />
  return <>{children}</>
}

function NavItem({ href, label, active, icon }: { href: string; label: string; active: boolean; icon?: string }) {
  return (
    <Link
      to={href}
      className={`flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200 font-sans ${
        active
          ? 'bg-kicc-gold/15 text-kicc-gold border border-kicc-gold/30 shadow-sm shadow-kicc-gold/10'
          : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
      }`}
    >
      {icon && <span className="text-base w-5 text-center">{icon}</span>}
      {label}
    </Link>
  )
}

function SectionTitle({ label }: { label: string }) {
  return <div className="text-[10px] font-bold text-kicc-gold/40 uppercase tracking-[0.15em] px-4 mt-6 mb-2 font-sans">{label}</div>
}

export function AdminLayout({ children }: { children: ReactNode }) {
  const { me, logout } = useAuth()
  const { t, locale, setLocale } = useT()
  const nav = useNavigate()
  const loc = useLocation()

  if (!me || !hasAnyPrivilege(me.privileges)) return <Navigate to="/login" replace />

  const tierLabel = me.tier === 'KICC' ? 'Platform Admin' : me.tier === 'NATIONAL' ? 'National Admin' : me.tier === 'COUNTY' ? `${me.countySlug || 'County'} Admin` : 'Exhibitor'

  return (
    <div className="flex min-h-screen bg-kicc-dark text-white font-sans" style={{ fontFamily: "'Montserrat', sans-serif" }}>
      <aside className="w-64 border-r border-white/[0.06] bg-kicc-card flex flex-col shrink-0 overflow-y-auto">
        <div className="px-5 py-6 border-b border-white/[0.06]">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-kicc-navy flex items-center justify-center font-black text-kicc-gold text-lg shadow-lg shadow-kicc-navy/40">K</div>
            <div>
              <div className="font-black text-white text-sm tracking-tight">KICC Platform</div>
              <div className="text-[10px] text-white/30 font-semibold uppercase tracking-wider">{tierLabel}</div>
            </div>
          </div>
          {me.countySlug && (
            <div className="mt-3 flex items-center gap-2 px-1.5 py-1.5 rounded-lg bg-kicc-green/10 border border-kicc-green/20">
              <span className="text-xs">📍</span>
              <span className="text-[11px] font-semibold text-kicc-green capitalize">{me.countySlug}</span>
            </div>
          )}
        </div>

        <nav className="flex-1 py-3 space-y-0.5 overflow-y-auto">
          <NavItem href="/admin/dashboard" label={t('dashboard')} active={loc.pathname === '/admin/dashboard'} icon="📊" />

          <SectionTitle label="People" />
          {me.privileges.includes('USERS_MANAGE') && (
            <NavItem href="/admin/users" label={t('users')} active={loc.pathname === '/admin/users'} icon="👥" />
          )}
          {me.privileges.includes('USERS_MANAGE') && (
            <NavItem href="/admin/review" label={t('onboarding')} active={loc.pathname === '/admin/review'} icon="📋" />
          )}
          {me.privileges.includes('DELEGATE') && (
            <NavItem href="/admin/delegations" label={t('delegations')} active={loc.pathname === '/admin/delegations'} icon="🔑" />
          )}

          <SectionTitle label="Commerce" />
          {me.privileges.includes('BOOKINGS_MANAGE') && (
            <NavItem href="/admin/bookings" label={t('bookings')} active={loc.pathname === '/admin/bookings'} icon="🎪" />
          )}
          {me.privileges.includes('PAYMENTS_MANAGE') && (
            <NavItem href="/admin/payments" label={t('payments')} active={loc.pathname === '/admin/payments'} icon="💳" />
          )}
          {me.privileges.includes('PAYMENTS_MANAGE') && (
            <NavItem href="/admin/settlements" label={t('settlements')} active={loc.pathname === '/admin/settlements'} icon="💰" />
          )}
          {me.privileges.includes('MARKETPLACE_MANAGE') && (
            <NavItem href="/admin/orders" label={t('orders')} active={loc.pathname === '/admin/orders'} icon="📦" />
          )}

          <SectionTitle label="Content" />
          {(me.tier === 'KICC' || me.tier === 'NATIONAL') && me.privileges.includes('CONTENT_MANAGE') && (
            <NavItem href="/admin/national" label="National Gov" active={loc.pathname === '/admin/national'} icon="🇰🇪" />
          )}
          {me.privileges.includes('CONTENT_MANAGE') && (
            <NavItem href="/admin/counties" label={t('counties')} active={loc.pathname === '/admin/counties'} icon="🗺️" />
          )}
          {me.privileges.includes('CONTENT_MANAGE') && (
            <NavItem href="/admin/sectors" label={t('sectors')} active={loc.pathname === '/admin/sectors'} icon="🏭" />
          )}
          {me.countySlug && (
            <NavItem href={`/admin/county/${me.countySlug}`} label={`${me.countySlug} Admin`} active={loc.pathname.startsWith('/admin/county/')} icon="🏛️" />
          )}
          {!me.countySlug && (me.tier === 'KICC' || me.tier === 'NATIONAL') && (
            <>
              <SectionTitle label="County Admin" />
              <div className="space-y-0.5 max-h-40 overflow-y-auto">
                {['mombasa','kilifi','nairobi-city','kisumu','nakuru','machakos','uasin-gishu','meru','kisii','kakamega'].map(cs => (
                  <NavItem key={cs} href={`/admin/county/${cs}`} label={cs.replace('-',' ')} active={loc.pathname.includes(cs)} />
                ))}
              </div>
            </>
          )}

          <SectionTitle label="Operations" />
          {me.privileges.includes('USERS_MANAGE') && (
            <NavItem href="/admin/complaints" label={t('complaints')} active={loc.pathname === '/admin/complaints'} icon="🎫" />
          )}
          {me.privileges.includes('DELEGATE') && (
            <NavItem href="/admin/audit" label={t('audit_log')} active={loc.pathname === '/admin/audit'} icon="🔍" />
          )}
          {me.privileges.includes('ANALYTICS_VIEW') && (
            <NavItem href="/admin/analytics" label={t('analytics')} active={loc.pathname === '/admin/analytics'} icon="📈" />
          )}
        </nav>

        <div className="px-4 py-4 border-t border-white/[0.06] space-y-2">
          <div className="flex gap-1">
            <button onClick={() => setLocale('en')} className={`rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-colors ${locale === 'en' ? 'bg-kicc-gold text-kicc-navy' : 'text-white/30 hover:text-white/60'}`}>EN</button>
            <button onClick={() => setLocale('sw')} className={`rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-colors ${locale === 'sw' ? 'bg-kicc-gold text-kicc-navy' : 'text-white/30 hover:text-white/60'}`}>SW</button>
          </div>
          <button onClick={() => logout().then(() => nav('/login'))} className="w-full text-left rounded-xl px-4 py-2.5 text-sm text-white/40 hover:text-red-400 hover:bg-red-400/5 transition-all font-semibold font-sans">
            Sign out
          </button>
        </div>
      </aside>
      <main className="flex-1 overflow-auto bg-kicc-dark">
        <div className="p-6 max-w-[1600px] mx-auto">
          {children}
        </div>
      </main>
    </div>
  )
}
