/**
 * Smart Payment API Functions
 * Treasury Module - Payment Allocation & Tolerance Features
 */

import { apiGet, apiPost } from '@/lib/api'
import type {
  ToleranceSettings,
  PaymentAllocationPreview,
  PaymentAllocationPreviewRequest,
  ApplyAllocationRequest,
  ApplyAllocationResponse,
} from '@/types/treasury'

/**
 * Get tolerance settings for the current company.
 *
 * GET /api/v1/smart-payment/tolerance-settings
 *
 * Returns the effective tolerance settings (company → country → system defaults).
 */
export async function getToleranceSettings(): Promise<ToleranceSettings> {
  return apiGet<ToleranceSettings>('/smart-payment/tolerance-settings')
}

/**
 * Preview payment allocation before applying.
 *
 * POST /api/v1/smart-payment/preview-allocation
 *
 * Shows which invoices would be allocated and how much,
 * including tolerance write-offs and excess handling.
 */
export async function previewPaymentAllocation(
  request: PaymentAllocationPreviewRequest
): Promise<PaymentAllocationPreview> {
  return apiPost<PaymentAllocationPreview>('/smart-payment/preview-allocation', request)
}

/**
 * Apply payment allocation to a payment record.
 *
 * POST /api/v1/smart-payment/apply-allocation
 *
 * Creates PaymentAllocation records linking the payment to invoices.
 * This is a financial operation - uses pessimistic UI pattern.
 */
export async function applyPaymentAllocation(
  request: ApplyAllocationRequest
): Promise<ApplyAllocationResponse> {
  return apiPost<ApplyAllocationResponse>('/smart-payment/apply-allocation', request)
}
