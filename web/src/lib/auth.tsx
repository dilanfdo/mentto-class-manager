import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { api, getToken, setToken } from './api'

export type Profile = {
  user: { id: number; name: string; email: string; role: string }
  tenant: { id: number; name: string; slug: string; type: 'tutor' | 'institute'; status: string } | null
  features: string[]
  feature_names: Record<string, string>
}

type AuthState = {
  profile: Profile | null
  ready: boolean
  login: (email: string, password: string) => Promise<Profile>
  logout: () => Promise<void>
  has: (feature: string) => boolean
}

const AuthContext = createContext<AuthState | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [profile, setProfile] = useState<Profile | null>(null)
  const [ready, setReady] = useState(() => !getToken())

  useEffect(() => {
    if (!getToken()) return
    api<Profile>('/me')
      .then(setProfile)
      .catch(() => setToken(null))
      .finally(() => setReady(true))
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    const res = await api<Profile & { token: string }>('/login', { method: 'POST', body: { email, password } })
    setToken(res.token)
    setProfile(res)
    return res
  }, [])

  const logout = useCallback(async () => {
    await api('/logout', { method: 'POST' }).catch(() => undefined)
    setToken(null)
    setProfile(null)
  }, [])

  const has = useCallback((feature: string) => profile?.features.includes(feature) ?? false, [profile])

  return <AuthContext.Provider value={{ profile, ready, login, logout, has }}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider')
  return ctx
}
