/// <reference types="vite/client" />

const API_BASE = import.meta.env.VITE_API_BASE ?? ''

export const SESSION_EXPIRED_EVENT = 'kicc:session-expired'

export interface AuthUser {
  id: number
  email: string
  fullName: string
  tier: string
  countySlug: string | null
  boothId: number | null
}

export interface MeResponse {
  id: number
  uuid: string
  name: string
  email: string
  account_type: string
  phone: string | null
  county_id: number | null
  ministry_id: number | null
  privileges?: string[]
  [key: string]: unknown
}

function getToken(): string | null {
  const raw = sessionStorage.getItem('kicc_token') ?? localStorage.getItem('kicc_token')
  return raw || null
}

function setToken(token: string): void {
  sessionStorage.setItem('kicc_token', token)
}

function clearToken(): void {
  sessionStorage.removeItem('kicc_token')
  localStorage.removeItem('kicc_token')
}

async function fetchApi<T>(
  method: string,
  path: string,
  body?: unknown,
  params?: Record<string, string>,
): Promise<T> {
  const url = new URL(path.startsWith('http') ? path : `${API_BASE}${path}`, window.location.origin)
  if (params) {
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v))
  }

  const headers: Record<string, string> = { Accept: 'application/json' }
  const token = getToken()
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }
  if (body !== undefined && !(body instanceof FormData)) {
    headers['Content-Type'] = 'application/json'
  }

  const res = await fetch(url.toString(), {
    method,
    headers,
    body: body instanceof FormData ? body : body !== undefined ? JSON.stringify(body) : undefined,
  })

  if (res.status === 401) {
    clearToken()
    window.dispatchEvent(new CustomEvent(SESSION_EXPIRED_EVENT))
    throw new ApiError(401, 'Session expired')
  }

  if (!res.ok) {
    const text = await res.text()
    let msg: string
    try {
      const json = JSON.parse(text)
      msg = json.message ?? json.error ?? `HTTP ${res.status}`
    } catch {
      msg = `HTTP ${res.status}: ${text.slice(0, 200)}`
    }
    throw new ApiError(res.status, msg)
  }

  const ct = res.headers.get('content-type') ?? ''
  if (ct.includes('application/json')) {
    return (await res.json()) as T
  }
  return (await res.text()) as unknown as T
}

export class ApiError extends Error {
  status: number
  constructor(status: number, message: string) {
    super(message)
    this.status = status
    this.name = 'ApiError'
  }
}

export const api = {
  get<T>(path: string, params?: Record<string, string>): Promise<T> {
    return fetchApi<T>('GET', path, undefined, params)
  },
  post<T>(path: string, body?: unknown): Promise<T> {
    return fetchApi<T>('POST', path, body)
  },
  put<T>(path: string, body?: unknown): Promise<T> {
    return fetchApi<T>('PUT', path, body)
  },
  patch<T>(path: string, body?: unknown): Promise<T> {
    return fetchApi<T>('PATCH', path, body)
  },
  delete<T = void>(path: string): Promise<T> {
    return fetchApi<T>('DELETE', path)
  },
  postForm<T>(path: string, formData: FormData): Promise<T> {
    return fetchApi<T>('POST', path, formData)
  },
}

export async function login(email: string, password: string): Promise<MeResponse> {
  const data = await api.post<{ user: MeResponse; token: string }>('/auth/login', { email, password })
  setToken(data.token)
  return data.user
}

export async function logout(): Promise<void> {
  try {
    await api.post('/auth/logout')
  } finally {
    clearToken()
  }
}

export function hasAdminAccess(me: unknown): boolean {
  if (!me || typeof me !== 'object') return false
  const m = me as Record<string, unknown>
  const tier = String(m.tier ?? '').toLowerCase()
  const role = String(m.role ?? '').toLowerCase()
  const accountType = String(m.account_type ?? '').toLowerCase()
  return ['admin', 'superadmin', 'kicc_admin'].includes(tier)
    || ['admin', 'superadmin', 'kicc_admin'].includes(role)
    || tier === 'admin'
    || accountType === 'admin'
    || accountType === 'superadmin'
}