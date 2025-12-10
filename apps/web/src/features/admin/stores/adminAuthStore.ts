import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface SuperAdmin {
  id: string
  email: string
  name: string
  role: 'super_admin'
}

interface AdminAuthState {
  admin: SuperAdmin | null
  token: string | null
  isAuthenticated: boolean
  setAuth: (admin: SuperAdmin, token: string) => void
  logout: () => void
}

export const useAdminAuthStore = create<AdminAuthState>()(
  persist(
    (set) => ({
      admin: null,
      token: null,
      isAuthenticated: false,
      setAuth: (admin, token) => {
        set({ admin, token, isAuthenticated: true })
      },
      logout: () => {
        set({ admin: null, token: null, isAuthenticated: false })
      },
    }),
    {
      name: 'admin-auth-storage',
    }
  )
)
