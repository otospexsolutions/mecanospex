import { apiGet, apiPost } from '@/lib/api'
import type {
  AdminAuthResponse,
  AdminDashboardStats,
  TenantListItem,
  TenantDetail,
  AdminAuditLog,
} from '../types'

// Authentication
export async function loginSuperAdmin(
  email: string,
  password: string
): Promise<AdminAuthResponse> {
  const response = await apiPost<{ data: AdminAuthResponse }>(
    '/admin/auth/login',
    { email, password }
  )
  return response.data
}

export async function logoutSuperAdmin(): Promise<void> {
  await apiPost('/admin/auth/logout', {})
}

export async function getSuperAdminProfile(): Promise<AdminAuthResponse> {
  const response = await apiGet<{ data: AdminAuthResponse }>('/admin/auth/me')
  return response.data
}

// Dashboard
export async function getAdminDashboardStats(): Promise<AdminDashboardStats> {
  const response = await apiGet<{ data: AdminDashboardStats }>('/admin/dashboard')
  return response.data
}

// Tenants
export async function getTenants(params?: {
  search?: string
  status?: string
}): Promise<{ data: TenantListItem[] }> {
  const queryParams = new URLSearchParams()
  if (params?.search) queryParams.append('search', params.search)
  if (params?.status) queryParams.append('status', params.status)

  const query = queryParams.toString()
  const response = await apiGet<{ data: { data: TenantListItem[] } }>(
    `/admin/tenants${query ? `?${query}` : ''}`
  )
  return response.data
}

export async function getTenant(id: string): Promise<TenantDetail> {
  const response = await apiGet<{ data: TenantDetail }>(`/admin/tenants/${id}`)
  return response.data
}

export async function extendTrial(
  tenantId: string,
  days: number
): Promise<void> {
  await apiPost(`/admin/tenants/${tenantId}/extend-trial`, { days })
}

export async function changePlan(
  tenantId: string,
  planId: string
): Promise<void> {
  await apiPost(`/admin/tenants/${tenantId}/change-plan`, { plan_id: planId })
}

export async function suspendTenant(
  tenantId: string,
  reason?: string
): Promise<void> {
  await apiPost(`/admin/tenants/${tenantId}/suspend`, { reason })
}

export async function activateTenant(tenantId: string): Promise<void> {
  await apiPost(`/admin/tenants/${tenantId}/activate`, {})
}

// Audit Logs
export async function getAdminAuditLogs(params?: {
  tenant_id?: string
  action?: string
}): Promise<{ data: AdminAuditLog[] }> {
  const queryParams = new URLSearchParams()
  if (params?.tenant_id) queryParams.append('tenant_id', params.tenant_id)
  if (params?.action) queryParams.append('action', params.action)

  const query = queryParams.toString()
  const response = await apiGet<{ data: { data: AdminAuditLog[] } }>(
    `/admin/audit-logs${query ? `?${query}` : ''}`
  )
  return response.data
}
