import { apiGet, apiPost, apiPatch } from '@/lib/api'
import type {
  Account,
  CreateAccountData,
  UpdateAccountData,
  AccountFilters,
} from './types'

export async function getAccounts(filters?: AccountFilters): Promise<Account[]> {
  const params = new URLSearchParams()

  if (filters?.type) {
    params.append('type', filters.type)
  }

  if (filters?.active !== undefined) {
    params.append('active', filters.active ? '1' : '0')
  }

  if (filters?.search) {
    params.append('search', filters.search)
  }

  const queryString = params.toString()
  const url = queryString ? `/accounts?${queryString}` : '/accounts'

  return apiGet<Account[]>(url)
}

export async function getAccount(id: string): Promise<Account> {
  return apiGet<Account>(`/accounts/${id}`)
}

export async function createAccount(data: CreateAccountData): Promise<Account> {
  return apiPost<Account>('/accounts', data)
}

export async function updateAccount(
  id: string,
  data: UpdateAccountData
): Promise<Account> {
  return apiPatch<Account>(`/accounts/${id}`, data)
}
