import { api } from '../../lib/api'
import { useAuthStore } from '../../stores/authStore'

/**
 * useLogout hook for logging out users
 */
export function useLogout() {
  const logout = useAuthStore((state) => state.logout)

  const handleLogout = async () => {
    try {
      await api.post('/auth/logout')
    } catch {
      // Logout locally even if API call fails
    } finally {
      logout()
      window.location.href = '/login'
    }
  }

  return handleLogout
}
