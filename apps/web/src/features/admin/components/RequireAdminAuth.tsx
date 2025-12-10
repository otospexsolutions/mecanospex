import { Navigate, useLocation } from 'react-router-dom'
import { useAdminAuthStore } from '../stores/adminAuthStore'

interface RequireAdminAuthProps {
  children: React.ReactNode
}

export function RequireAdminAuth({ children }: RequireAdminAuthProps) {
  const isAuthenticated = useAdminAuthStore((state) => state.isAuthenticated)
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to="/admin/login" state={{ from: location }} replace />
  }

  return <>{children}</>
}
