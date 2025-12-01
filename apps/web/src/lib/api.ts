import axios, { type AxiosError, type AxiosInstance, type AxiosResponse } from 'axios'
import { useAuthStore } from '../stores/authStore'
import { useCompanyStore } from '../stores/companyStore'

/**
 * API Response Format (per CLAUDE.md)
 */
export interface ApiResponse<T> {
  data: T
  meta: {
    timestamp: string
    request_id: string
  }
}

/**
 * API Error Format (per CLAUDE.md)
 */
export interface ApiError {
  error: {
    code: string
    message: string
    details?: Record<string, unknown>
  }
  meta: {
    timestamp: string
    request_id: string
  }
}

/**
 * Type guard for API errors
 */
export function isApiError(error: unknown): error is AxiosError<ApiError> {
  if (!axios.isAxiosError(error)) {
    return false
  }
  const data = error.response?.data as ApiError | undefined
  return data?.error !== undefined
}

/**
 * Extract error message from API error
 */
export function getErrorMessage(error: unknown): string {
  if (isApiError(error)) {
    const data = error.response?.data
    if (data) {
      return data.error.message
    }
    return 'An unexpected error occurred'
  }
  if (error instanceof Error) {
    return error.message
  }
  return 'An unexpected error occurred'
}

/**
 * Create the base API client with auth handling
 */
function createApiClient(): AxiosInstance {
  const client = axios.create({
    baseURL: '/api/v1',
    timeout: 30000,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    withCredentials: true, // For Sanctum cookie-based auth
  })

  // Request interceptor for auth token and company context
  client.interceptors.request.use(
    (config) => {
      // Get token from auth store and add to Authorization header
      const token = useAuthStore.getState().token
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }

      // Add company context header for multi-company support
      const companyId = useCompanyStore.getState().currentCompanyId
      if (companyId) {
        config.headers['X-Company-Id'] = companyId
      }

      return config
    },
    (error: unknown) => Promise.reject(new Error(String(error)))
  )

  // Response interceptor for error handling
  client.interceptors.response.use(
    (response: AxiosResponse) => response,
    async (error: unknown) => {
      if (isApiError(error)) {
        const response = error.response
        if (!response) {
          return Promise.reject(new Error('Network error'))
        }

        // Handle 401 Unauthorized
        // Don't redirect for /auth/me - that's expected when not logged in
        // The AuthProvider handles the redirect via React Router
        if (response.status === 401) {
          const url = error.config?.url ?? ''
          if (!url.includes('/auth/me')) {
            // For other endpoints, let the caller handle 401
            console.warn('Unauthorized request:', url)
          }
        }

        // Handle 403 Forbidden
        if (response.status === 403) {
          console.error('Access denied:', response.data.error.message)
        }

        // Handle 500+ Server Errors
        if (response.status >= 500) {
          console.error('Server error:', response.data.error.message)
        }
      }

      return Promise.reject(error instanceof Error ? error : new Error(String(error)))
    }
  )

  return client
}

/**
 * Singleton API client instance
 */
export const api = createApiClient()

/**
 * Helper for GET requests
 */
export async function apiGet<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  const response = await api.get<ApiResponse<T>>(url, { params })
  return response.data.data
}

/**
 * Helper for POST requests
 */
export async function apiPost<T>(url: string, data?: unknown): Promise<T> {
  const response = await api.post<ApiResponse<T>>(url, data)
  return response.data.data
}

/**
 * Helper for PATCH requests
 */
export async function apiPatch<T>(url: string, data?: unknown): Promise<T> {
  const response = await api.patch<ApiResponse<T>>(url, data)
  return response.data.data
}

/**
 * Helper for DELETE requests
 */
export async function apiDelete<T>(url: string): Promise<T> {
  const response = await api.delete<ApiResponse<T>>(url)
  return response.data.data
}
