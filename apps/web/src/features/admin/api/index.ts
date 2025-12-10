import { apiPost } from '@/lib/api'
import { adminApiGet, adminApiPost } from '../lib/adminApi'
import type {
  AdminAuthResponse,
  AdminDashboardStats,
  TenantListItem,
  TenantDetail,
  AdminAuditLog,
} from '../types'

// Authentication (uses regular API since not authenticated yet)
export async function loginSuperAdmin(
  email: string,
  password: string
): Promise<AdminAuthResponse> {
  const response = await apiPost<{ admin: AdminAuthResponse; token: string }>(
    '/admin/auth/login',
    { email, password }
  )
  return {
    ...response.admin,
    token: response.token,
  }
}

export async function logoutSuperAdmin(): Promise<void> {
  await adminApiPost('/admin/auth/logout', {})
}

export async function getSuperAdminProfile(): Promise<AdminAuthResponse> {
  return adminApiGet<AdminAuthResponse>('/admin/auth/me')
}

// Dashboard
export async function getAdminDashboardStats(): Promise<AdminDashboardStats> {
  return adminApiGet<AdminDashboardStats>('/admin/dashboard')
}

// Tenants - API returns paginated data
interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export async function getTenants(params?: {
  search?: string
  status?: string
}): Promise<{ data: TenantListItem[] }> {
  const queryParams = new URLSearchParams()
  if (params?.search) queryParams.append('search', params.search)
  if (params?.status) queryParams.append('status', params.status)

  const query = queryParams.toString()
  const response = await adminApiGet<PaginatedResponse<TenantListItem>>(
    `/admin/tenants${query ? `?${query}` : ''}`
  )
  // Extract data array from paginated response
  return { data: response.data }
}

export async function getTenant(id: string): Promise<TenantDetail> {
  return adminApiGet<TenantDetail>(`/admin/tenants/${id}`)
}

export async function extendTrial(
  tenantId: string,
  days: number
): Promise<void> {
  await adminApiPost(`/admin/tenants/${tenantId}/extend-trial`, { days })
}

export async function changePlan(
  tenantId: string,
  planId: string
): Promise<void> {
  await adminApiPost(`/admin/tenants/${tenantId}/change-plan`, { plan_id: planId })
}

export async function suspendTenant(
  tenantId: string,
  reason?: string
): Promise<void> {
  await adminApiPost(`/admin/tenants/${tenantId}/suspend`, { reason })
}

export async function activateTenant(tenantId: string): Promise<void> {
  await adminApiPost(`/admin/tenants/${tenantId}/activate`, {})
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
  const response = await adminApiGet<PaginatedResponse<AdminAuditLog>>(
    `/admin/audit-logs${query ? `?${query}` : ''}`
  )
  return { data: response.data }
}
