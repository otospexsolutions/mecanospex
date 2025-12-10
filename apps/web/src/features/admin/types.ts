export interface AdminAuthResponse {
  id: string
  email: string
  name: string
  role: 'super_admin'
  token?: string
}

export interface AdminDashboardStats {
  total_tenants: number
  active_tenants: number
  trial_tenants: number
  suspended_tenants: number
  expired_tenants: number
  total_users: number
  total_companies: number
  total_revenue: number
  monthly_revenue: number
  new_signups_this_month: number
}

export interface TenantListItem {
  id: string
  name: string
  email: string | null
  status: 'active' | 'trial' | 'suspended' | 'expired'
  subscription: {
    plan: {
      id: string
      name: string
    }
    status: 'active' | 'trial' | 'cancelled' | 'expired'
    trial_ends_at: string | null
    current_period_end: string | null
  } | null
  created_at: string
}

export interface TenantDetail extends TenantListItem {
  owner: {
    id: string
    name: string
    email: string
  } | null
  users_count: number
  locations_count: number
  storage_used: number
  updated_at: string
}

export interface AdminAuditLog {
  id: string
  admin_id: string
  admin_name: string
  tenant_id: string | null
  tenant_name: string | null
  action: string
  details: Record<string, unknown>
  ip_address: string | null
  created_at: string
}
