import { apiGet, apiPost, apiPatch, apiDelete } from '../../lib/api'
import type { LocationType } from '../../stores/locationStore'

/**
 * API response format for location (snake_case from backend)
 */
export interface LocationApiResponse {
  id: string
  company_id: string
  name: string
  code: string
  type: LocationType
  phone: string | null
  email: string | null
  address_street: string | null
  address_city: string | null
  address_postal_code: string | null
  address_country: string | null
  is_default: boolean
  is_active: boolean
  pos_enabled: boolean
  created_at: string
  updated_at: string
}

/**
 * Input for creating a new location
 */
export interface CreateLocationInput {
  name: string
  type: LocationType
  code?: string | undefined
  phone?: string | undefined
  email?: string | undefined
  addressStreet?: string | undefined
  addressCity?: string | undefined
  addressPostalCode?: string | undefined
  addressCountry?: string | undefined
  posEnabled?: boolean | undefined
}

/**
 * Input for updating a location
 */
export interface UpdateLocationInput {
  name?: string
  type?: LocationType
  code?: string
  phone?: string
  email?: string
  addressStreet?: string
  addressCity?: string
  addressPostalCode?: string
  addressCountry?: string
  isActive?: boolean
  posEnabled?: boolean
}

/**
 * API payload format (snake_case for backend)
 */
interface CreateLocationPayload {
  name: string
  type: LocationType
  code?: string | undefined
  phone?: string | undefined
  email?: string | undefined
  address_street?: string | undefined
  address_city?: string | undefined
  address_postal_code?: string | undefined
  address_country?: string | undefined
  pos_enabled?: boolean | undefined
}

interface UpdateLocationPayload {
  name?: string
  type?: LocationType
  code?: string
  phone?: string
  email?: string
  address_street?: string
  address_city?: string
  address_postal_code?: string
  address_country?: string
  is_active?: boolean
  pos_enabled?: boolean
}

/**
 * Fetches all locations for the current company
 */
export async function fetchLocations(): Promise<LocationApiResponse[]> {
  return apiGet<LocationApiResponse[]>('/locations')
}

/**
 * Fetches a single location by ID
 */
export async function fetchLocation(id: string): Promise<LocationApiResponse> {
  return apiGet<LocationApiResponse>(`/locations/${id}`)
}

/**
 * Creates a new location
 */
export async function createLocation(input: CreateLocationInput): Promise<LocationApiResponse> {
  const payload: CreateLocationPayload = {
    name: input.name,
    type: input.type,
    code: input.code,
    phone: input.phone,
    email: input.email,
    address_street: input.addressStreet,
    address_city: input.addressCity,
    address_postal_code: input.addressPostalCode,
    address_country: input.addressCountry,
    pos_enabled: input.posEnabled,
  }

  return apiPost<LocationApiResponse>('/locations', payload)
}

/**
 * Updates an existing location
 */
export async function updateLocation(id: string, input: UpdateLocationInput): Promise<LocationApiResponse> {
  const payload: UpdateLocationPayload = {}

  if (input.name !== undefined) payload.name = input.name
  if (input.type !== undefined) payload.type = input.type
  if (input.code !== undefined) payload.code = input.code
  if (input.phone !== undefined) payload.phone = input.phone
  if (input.email !== undefined) payload.email = input.email
  if (input.addressStreet !== undefined) payload.address_street = input.addressStreet
  if (input.addressCity !== undefined) payload.address_city = input.addressCity
  if (input.addressPostalCode !== undefined) payload.address_postal_code = input.addressPostalCode
  if (input.addressCountry !== undefined) payload.address_country = input.addressCountry
  if (input.isActive !== undefined) payload.is_active = input.isActive
  if (input.posEnabled !== undefined) payload.pos_enabled = input.posEnabled

  return apiPatch<LocationApiResponse>(`/locations/${id}`, payload)
}

/**
 * Deletes a location (soft delete)
 */
export async function deleteLocation(id: string): Promise<unknown> {
  return apiDelete(`/locations/${id}`)
}

/**
 * Sets a location as the default
 */
export async function setDefaultLocation(id: string): Promise<LocationApiResponse> {
  return apiPost<LocationApiResponse>(`/locations/${id}/set-default`, {})
}

/**
 * Transform API response to store format (snake_case to camelCase)
 */
export function transformLocationResponse(response: LocationApiResponse) {
  return {
    id: response.id,
    companyId: response.company_id,
    name: response.name,
    code: response.code,
    type: response.type,
    phone: response.phone,
    email: response.email,
    addressStreet: response.address_street,
    addressCity: response.address_city,
    addressPostalCode: response.address_postal_code,
    addressCountry: response.address_country,
    isDefault: response.is_default,
    isActive: response.is_active,
    posEnabled: response.pos_enabled,
    createdAt: response.created_at,
    updatedAt: response.updated_at,
  }
}
