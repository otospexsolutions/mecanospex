import { useQuery } from '@tanstack/react-query'
import { getAdminAuditLogs } from '../api'

export function useAdminAuditLogs(params?: {
  tenant_id?: string
  action?: string
}) {
  return useQuery({
    queryKey: ['admin', 'audit-logs', params],
    queryFn: () => getAdminAuditLogs(params),
  })
}
