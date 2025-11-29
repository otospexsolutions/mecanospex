import { create } from 'zustand'
import { persist } from 'zustand/middleware'

/**
 * User type (will be replaced with generated type from backend)
 */
interface User {
  id: string
  name: string
  email: string
  tenant_id: string
  roles: string[]
}

/**
 * Auth state interface
 */
interface AuthState {
  user: User | null
  isAuthenticated: boolean
  isLoading: boolean
}

/**
 * Auth actions interface
 */
interface AuthActions {
  setUser: (user: User | null) => void
  setLoading: (loading: boolean) => void
  logout: () => void
}

/**
 * Auth store type
 */
type AuthStore = AuthState & AuthActions

/**
 * Initial state
 */
const initialState: AuthState = {
  user: null,
  isAuthenticated: false,
  isLoading: true,
}

/**
 * Auth store with persistence
 *
 * Uses Zustand for minimal client state (per CLAUDE.md).
 * Server state is managed by TanStack Query.
 */
export const useAuthStore = create<AuthStore>()(
  persist(
    (set) => ({
      ...initialState,

      setUser: (user) =>
        set({
          user,
          isAuthenticated: user !== null,
          isLoading: false,
        }),

      setLoading: (isLoading) => set({ isLoading }),

      logout: () =>
        set({
          user: null,
          isAuthenticated: false,
          isLoading: false,
        }),
    }),
    {
      name: 'autoerp-auth',
      partialize: (state) => ({
        user: state.user,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
)
