import { useQuery } from '@tanstack/react-query'
import { getTrialBalance } from '../api'
import type { TrialBalanceFilters } from '../types'

export function useTrialBalance(filters?: TrialBalanceFilters) {
  return useQuery({
    queryKey: ['trial-balance', filters],
    queryFn: () => getTrialBalance(filters),
  })
}
