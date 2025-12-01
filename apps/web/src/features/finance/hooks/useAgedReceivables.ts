import { useQuery } from '@tanstack/react-query'
import { getAgedReceivables } from '../api'
import type { AgedReceivablesFilters } from '../types'

export function useAgedReceivables(filters?: AgedReceivablesFilters) {
  return useQuery({
    queryKey: ['aged-receivables', filters],
    queryFn: () => getAgedReceivables(filters),
  })
}
