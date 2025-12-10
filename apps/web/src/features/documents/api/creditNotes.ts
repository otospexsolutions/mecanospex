/**
 * Credit Note API Functions
 * Document Module - Credit Note Features
 */

import { apiGet, apiPost } from '@/lib/api'
import type {
  CreditNote,
  CreateCreditNoteRequest,
} from '@/types/creditNote'

/**
 * Get credit notes list.
 *
 * GET /api/v1/credit-notes?source_invoice_id=uuid
 *
 * Fetches all credit notes, optionally filtered by source invoice.
 */
export async function getCreditNotes(params?: {
  source_invoice_id?: string
}): Promise<CreditNote[]> {
  return apiGet<CreditNote[]>('/credit-notes', params)
}

/**
 * Get a single credit note by ID.
 *
 * GET /api/v1/credit-notes/{id}
 *
 * Fetches full credit note details including lines.
 */
export async function getCreditNote(id: string): Promise<CreditNote> {
  return apiGet<CreditNote>(`/credit-notes/${id}`)
}

/**
 * Create a credit note from an invoice.
 *
 * POST /api/v1/credit-notes
 *
 * Creates a credit note as a draft.
 * **Financial operation** - uses pessimistic UI pattern.
 *
 * Validates:
 * - Source invoice is posted
 * - Amount does not exceed invoice total
 * - Cumulative credit notes do not exceed invoice total
 */
export async function createCreditNote(
  request: CreateCreditNoteRequest
): Promise<CreditNote> {
  return apiPost<CreditNote>('/credit-notes', request)
}
