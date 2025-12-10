import { useTranslation } from 'react-i18next'

/**
 * Document status types from backend
 */
export type DocumentStatus = 'draft' | 'confirmed' | 'posted' | 'cancelled'

/**
 * Partner status types
 */
export type PartnerStatus = 'active' | 'inactive'

/**
 * General status types
 */
export type GeneralStatus = 'active' | 'inactive' | 'pending' | 'completed' | 'cancelled'

/**
 * Payment status types
 */
export type PaymentStatus = 'pending' | 'completed' | 'failed' | 'refunded' | 'cancelled'

/**
 * Mapping of status values to translation keys
 */
export const STATUS_TRANSLATION_KEYS: Record<string, string> = {
  // Document statuses
  draft: 'status.draft',
  confirmed: 'status.confirmed',
  posted: 'status.posted',
  cancelled: 'status.cancelled',

  // General statuses
  active: 'status.active',
  inactive: 'status.inactive',
  pending: 'status.pending',
  completed: 'status.completed',

  // Payment statuses
  failed: 'status.failed',
  refunded: 'status.refunded',
}

/**
 * Hook to get translated status label
 * @param status - The status value from the backend
 * @returns Translated status label
 */
export function useStatusLabel(status: string | null | undefined): string {
  const { t } = useTranslation()

  if (!status) return ''

  const key = STATUS_TRANSLATION_KEYS[status.toLowerCase()]
  return key ? t(key) : status
}

/**
 * Non-hook version for utilities and non-component contexts
 * Returns the translation key for a given status
 * @param status - The status value from the backend
 * @returns Translation key
 */
export function getStatusTranslationKey(status: string | null | undefined): string {
  if (!status) return ''
  return STATUS_TRANSLATION_KEYS[status.toLowerCase()] ?? status
}

/**
 * Get CSS classes for status badge styling
 * @param status - The status value
 * @returns Tailwind CSS classes for the badge
 */
export function getStatusBadgeClasses(status: string | null | undefined): string {
  if (!status) return 'bg-gray-100 text-gray-800'

  const statusLower = status.toLowerCase()

  const colorMap: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    active: 'bg-green-100 text-green-800',
    confirmed: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    posted: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    inactive: 'bg-gray-100 text-gray-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-orange-100 text-orange-800',
  }

  return colorMap[statusLower] ?? 'bg-gray-100 text-gray-800'
}
