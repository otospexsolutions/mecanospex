import { apiPost } from '../../lib/api'

/**
 * Input for creating a new company
 */
export interface CreateCompanyInput {
  name: string
  legalName?: string | undefined
  countryCode: string
  currency: string
  locale: string
  timezone: string
  taxId?: string | undefined
  email?: string | undefined
  phone?: string | undefined
  addressStreet?: string | undefined
  addressCity?: string | undefined
  addressPostalCode?: string | undefined
}

/**
 * API response for company creation
 */
interface CreateCompanyResponse {
  id: string
  tenant_id: string
  name: string
  legal_name: string | null
  country_code: string
  tax_id: string | null
  email: string | null
  phone: string | null
  currency: string
  locale: string
  timezone: string
  status: string
  address_street: string | null
  address_city: string | null
  address_postal_code: string | null
  created_at: string
  updated_at: string
}

/**
 * API payload format (snake_case)
 */
interface CreateCompanyPayload {
  name: string
  legal_name?: string | undefined
  country_code: string
  currency: string
  locale: string
  timezone: string
  tax_id?: string | undefined
  email?: string | undefined
  phone?: string | undefined
  address_street?: string | undefined
  address_city?: string | undefined
  address_postal_code?: string | undefined
}

/**
 * Creates a new company
 *
 * This will also:
 * - Create a default location for the company
 * - Create owner membership for the current user
 * - Initialize hash chains for fiscal compliance
 */
export async function createCompany(input: CreateCompanyInput): Promise<CreateCompanyResponse> {
  const payload: CreateCompanyPayload = {
    name: input.name,
    legal_name: input.legalName,
    country_code: input.countryCode,
    currency: input.currency,
    locale: input.locale,
    timezone: input.timezone,
    tax_id: input.taxId,
    email: input.email,
    phone: input.phone,
    address_street: input.addressStreet,
    address_city: input.addressCity,
    address_postal_code: input.addressPostalCode,
  }

  return apiPost<CreateCompanyResponse>('/companies', payload)
}
