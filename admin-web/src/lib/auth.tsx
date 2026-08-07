import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { api, login as apiLogin, logout as apiLogout, SESSION_EXPIRED_EVENT, type AuthUser, type MeResponse } from './api'

interface AuthState {
  me: MeResponse | null
  user: AuthUser | null
  loading: boolean
  login: (email: string, password: string) => Promise<unknown>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthState | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [me, setMe] = useState<MeResponse | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api
      .get<MeResponse>('/me')
      .then(setMe)
      .catch(() => setMe(null))
      .finally(() => setLoading(false))
  }, [])

  // Session expired (401 survived refresh) → clear state; the router-level
  // listener (main.tsx) redirects to /login?expired=1.
  useEffect(() => {
    const onExpired = () => setMe(null)
    window.addEventListener(SESSION_EXPIRED_EVENT, onExpired)
    return () => window.removeEventListener(SESSION_EXPIRED_EVENT, onExpired)
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    await apiLogin(email, password)
    const meResponse = await api.get<MeResponse>('/me')
    setMe(meResponse)
    return meResponse
  }, [])

  const logout = useCallback(async () => {
    await apiLogout()
    setMe(null)
  }, [])

  const value: AuthState = {
    me,
    user: me
      ? { id: (me as any).id ?? 0, email: me.email, fullName: me.fullName, tier: me.tier, countySlug: me.countySlug, boothId: me.boothId }
      : null,
    loading,
    login,
    logout
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
