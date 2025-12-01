import { apiGet, apiPost, apiPatch } from '@/lib/api'
import type {
  Account,
  CreateAccountData,
  UpdateAccountData,
  AccountFilters,
  JournalEntry,
  LedgerLine,
  LedgerFilters,
  TrialBalanceLine,
  TrialBalanceFilters,
  ProfitLossData,
  ProfitLossFilters,
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

export async function getJournalEntries(page = 1): Promise<{
  data: JournalEntry[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}> {
  return apiGet(`/journal-entries?page=${page}`)
}

export async function getLedger(filters?: LedgerFilters): Promise<LedgerLine[]> {
  const params = new URLSearchParams()

  if (filters?.account_id) {
    params.append('account_id', filters.account_id)
  }

  if (filters?.date_from) {
    params.append('date_from', filters.date_from)
  }

  if (filters?.date_to) {
    params.append('date_to', filters.date_to)
  }

  if (filters?.min_amount !== undefined) {
    params.append('min_amount', filters.min_amount.toString())
  }

  if (filters?.max_amount !== undefined) {
    params.append('max_amount', filters.max_amount.toString())
  }

  const queryString = params.toString()
  const url = queryString ? `/ledger?${queryString}` : '/ledger'

  return apiGet<LedgerLine[]>(url)
}

export async function getTrialBalance(
  filters?: TrialBalanceFilters
): Promise<TrialBalanceLine[]> {
  const params = new URLSearchParams()

  if (filters?.as_of_date) {
    params.append('as_of_date', filters.as_of_date)
  }

  const queryString = params.toString()
  const url = queryString ? `/reports/trial-balance?${queryString}` : '/reports/trial-balance'

  return apiGet<TrialBalanceLine[]>(url)
}

export async function getProfitLoss(
  filters?: ProfitLossFilters
): Promise<ProfitLossData> {
  const params = new URLSearchParams()

  if (filters?.date_from) {
    params.append('date_from', filters.date_from)
  }

  if (filters?.date_to) {
    params.append('date_to', filters.date_to)
  }

  const queryString = params.toString()
  const url = queryString ? `/reports/profit-loss?${queryString}` : '/reports/profit-loss'

  return apiGet<ProfitLossData>(url)
}
