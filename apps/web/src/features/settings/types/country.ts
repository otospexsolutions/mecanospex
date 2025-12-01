/**
 * Country and tax rate types for multi-country support
 */

export interface CountryTaxRate {
  id: string
  country_code: string
  name: string
  rate: string
  code: string
  is_default: boolean
  is_active: boolean
  created_at: string
}

export interface Country {
  code: string
  name: string
  native_name: string | null
  currency_code: string
  currency_symbol: string | null
  phone_prefix: string | null
  date_format: string
  default_locale: string | null
  default_timezone: string | null
  is_active: boolean
  tax_id_label: string | null
  tax_id_regex: string | null
  created_at: string
  tax_rates?: CountryTaxRate[]
}

export interface CountryFilters {
  is_active?: boolean
}
