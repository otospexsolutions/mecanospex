export interface SuperAdmin {
  id: string
  name: string
  email: string
  role: string
  is_active: boolean
  last_login_at: string | null
  last_login_ip: string | null
  notes: string | null
  created_at: string
  updated_at: string
}

export interface AdminDashboardStats {
  total_tenants: number
  active_tenants: number
  trial_tenants: number
  expired_tenants: number
  total_users: number
  total_companies: number
}

export interface TenantListItem {
  id: string
  name: string
  slug: string
  status: string
  plan: string
  email: string | null
  phone: string | null
  created_at: string
  subscription: {
    id: string
    status: string
    trial_ends_at: string | null
    current_period_end: string | null
    plan: {
      id: string
      code: string
      name: string
      price_monthly: string | null
    }
  } | null
}

export interface TenantDetail {
  tenant: TenantListItem
  stats: {
    users_count: number
    companies_count: number
    locations_count: number
  }
}

export interface AdminAuditLog {
  id: string
  super_admin_id: string
  tenant_id: string | null
  action: string
  entity_type: string | null
  entity_id: string | null
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  ip_address: string | null
  user_agent: string | null
  notes: string | null
  created_at: string
  admin_name: string
  admin_email: string
  tenant_name: string | null
}

export interface AdminAuthResponse {
  admin: SuperAdmin
  token: string
}
