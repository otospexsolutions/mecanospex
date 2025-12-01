export interface Plan {
  id: string
  code: string
  name: string
  description: string | null
  limits: {
    max_companies: number
    max_locations: number
    max_users: number
  }
  price_monthly: string | null
  currency: string
  is_active: boolean
  display_order: number
  created_at: string
}

export interface Subscription {
  id: string
  tenant_id: string
  plan_id: string
  status: 'trial' | 'active' | 'expired' | 'suspended'
  trial_ends_at: string | null
  current_period_end: string | null
  notes: string | null
  created_at: string
  updated_at: string
  plan: Plan
}

export interface SubscriptionInfo {
  subscription: Subscription | null
  usage: {
    companies: number
    locations: number
    users: number
  }
  limits: {
    max_companies: number
    max_locations: number
    max_users: number
  }
  trial_days_remaining: number
}
