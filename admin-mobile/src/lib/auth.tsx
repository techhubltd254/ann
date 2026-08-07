import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { api, getAccessToken, login as apiLogin, logout as apiLogout, type LoginResult } from './api'
import { clearTokens } from './api'

interface MeResponse {
  email: string
  fullName: string
  tier: string
  countySlug: string | null
  privileges: string[]
}

interface AuthState {
  me: MeResponse | null
  loading: boolean
  login: (email: string, password: string) => Promise<LoginResult>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthState | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [me, setMe] = useState<MeResponse | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    ;(async () => {
      try {
        if (!(await getAccessToken())) {
          setMe(null)
          return
        }
        const res = await api.get<MeResponse>('/api/me')
        setMe(res)
      } catch {
        await clearTokens()
        setMe(null)
      } finally {
        setLoading(false)
      }
    })()
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    const tokens = await apiLogin(email, password)
    const res = await api.get<MeResponse>('/api/me')
    setMe(res)
    return tokens
  }, [])

  const logout = useCallback(async () => {
    await apiLogout()
    setMe(null)
  }, [])

  return <AuthContext.Provider value={{ me, loading, login, logout }}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
