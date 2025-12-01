import { useQuery } from '@tanstack/react-query'
import { getBalanceSheet } from '../api'
import type { BalanceSheetFilters } from '../types'

export function useBalanceSheet(filters?: BalanceSheetFilters) {
  return useQuery({
    queryKey: ['balance-sheet', filters],
    queryFn: () => getBalanceSheet(filters),
  })
}
