import { useQuery } from '@tanstack/react-query'
import { getAdminDashboardStats } from '../api'

export function useAdminDashboard() {
  return useQuery({
    queryKey: ['admin', 'dashboard'],
    queryFn: () => getAdminDashboardStats(),
  })
}
