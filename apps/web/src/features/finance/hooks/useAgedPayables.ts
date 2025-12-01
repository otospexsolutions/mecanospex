import { useQuery } from '@tanstack/react-query'
import { getAgedPayables } from '../api'
import type { AgedPayablesFilters } from '../types'

export function useAgedPayables(filters?: AgedPayablesFilters) {
  return useQuery({
    queryKey: ['aged-payables', filters],
    queryFn: () => getAgedPayables(filters),
  })
}
