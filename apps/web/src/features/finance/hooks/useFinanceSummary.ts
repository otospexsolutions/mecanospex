import { useQuery } from '@tanstack/react-query'
import { getFinanceSummary } from '../api'

export function useFinanceSummary() {
  return useQuery({
    queryKey: ['finance-summary'],
    queryFn: () => getFinanceSummary(),
  })
}
