import { useQuery } from '@tanstack/react-query'
import { getCountries, getCountry } from '../api/country'
import type { CountryFilters } from '../types/country'

export function useCountries(filters?: CountryFilters) {
  return useQuery({
    queryKey: ['countries', filters],
    queryFn: () => getCountries(filters),
  })
}

export function useCountry(code: string) {
  return useQuery({
    queryKey: ['country', code],
    queryFn: () => getCountry(code),
    enabled: !!code,
  })
}
