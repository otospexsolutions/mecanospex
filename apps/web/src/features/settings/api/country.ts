import { apiGet } from '@/lib/api'
import type { Country, CountryFilters } from '../types/country'

export async function getCountries(filters?: CountryFilters): Promise<Country[]> {
  const params = new URLSearchParams()

  if (filters?.is_active !== undefined) {
    params.append('is_active', filters.is_active ? '1' : '0')
  }

  const queryString = params.toString()
  const url = queryString ? `/countries?${queryString}` : '/countries'

  const response = await apiGet<{ data: Country[] }>(url)
  return response.data
}

export async function getCountry(code: string): Promise<Country> {
  const response = await apiGet<{ data: Country }>(`/countries/${code}`)
  return response.data
}
