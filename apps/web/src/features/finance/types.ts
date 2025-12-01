export type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense'

export interface Account {
  id: string
  tenant_id: string
  parent_id: string | null
  code: string
  name: string
  type: AccountType
  description: string | null
  is_active: boolean
  is_system: boolean
  balance: string
  created_at: string
  updated_at: string
}

export interface CreateAccountData {
  code: string
  name: string
  type: AccountType
  description?: string | undefined
  parent_id?: string | undefined
  is_active?: boolean
}

export interface UpdateAccountData {
  name?: string
  description?: string | null
  is_active?: boolean
}

export interface AccountFilters {
  type?: AccountType
  active?: boolean
  search?: string
}
