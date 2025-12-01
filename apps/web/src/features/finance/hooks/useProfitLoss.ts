import { useQuery } from '@tanstack/react-query'
import { getProfitLoss } from '../api'
import type { ProfitLossFilters } from '../types'

export function useProfitLoss(filters?: ProfitLossFilters) {
  return useQuery({
    queryKey: ['profit-loss', filters],
    queryFn: () => getProfitLoss(filters),
  })
}
