/**
 * CreateCreditNoteForm Component
 * Form for creating credit notes from posted invoices
 */

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useTranslation } from 'react-i18next'
import { AlertCircle } from 'lucide-react'
import { useCreateCreditNote } from '../hooks/useCreditNotes'
import type { InvoiceForCreditNote } from '@/types/creditNote'

// Zod validation schema
const createCreditNoteSchema = z.object({
  amount: z
    .string()
    .min(1, 'sales.creditNotes.form.amountRequired')
    .refine((val) => parseFloat(val) > 0, 'sales.creditNotes.form.amountPositive'),
  reason: z.enum([
    'return',
    'price_adjustment',
    'billing_error',
    'damaged_goods',
    'service_issue',
    'other',
  ]),
  notes: z.string().optional(),
})

type CreditNoteFormData = z.infer<typeof createCreditNoteSchema>

interface CreateCreditNoteFormProps {
  invoice: InvoiceForCreditNote
  onSuccess?: () => void
  onCancel?: () => void
}

/**
 * Form component for creating credit notes
 *
 * Features:
 * - Amount validation (positive, not exceeding invoice total/remaining)
 * - Reason selection (6 predefined reasons)
 * - Notes field
 * - Full refund quick action
 * - Pessimistic form submission
 */
export function CreateCreditNoteForm({
  invoice,
  onSuccess,
  onCancel,
}: CreateCreditNoteFormProps) {
  const { t } = useTranslation(['sales', 'common'])
  const createCreditNote = useCreateCreditNote()

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<CreditNoteFormData>({
    resolver: zodResolver(createCreditNoteSchema),
    defaultValues: {
      amount: '',
      notes: '',
    },
  })

  const amountValue = watch('amount')

  // Calculate remaining creditable amount
  const remainingCreditable = parseFloat(invoice.balance_due || invoice.total)
  const invoiceTotal = parseFloat(invoice.total)

  // Validate amount against invoice total and remaining creditable
  const validateAmount = (amount: string): string | null => {
    const numAmount = parseFloat(amount)

    if (isNaN(numAmount) || numAmount <= 0) {
      return t('sales:creditNotes.form.amountPositive')
    }

    if (numAmount > invoiceTotal) {
      return t('sales:creditNotes.errors.exceedsInvoiceTotal')
    }

    if (numAmount > remainingCreditable) {
      return t('sales:creditNotes.errors.exceedsRemainingBalance')
    }

    return null
  }

  const customAmountError = amountValue ? validateAmount(amountValue) : null

  // Handle form submission
  const onSubmit = (data: CreditNoteFormData) => {
    // Additional validation
    const amountError = validateAmount(data.amount)
    if (amountError) {
      return
    }

    createCreditNote.mutate(
      {
        source_invoice_id: invoice.id,
        amount: parseFloat(data.amount).toFixed(4),
        reason: data.reason as any,
        ...(data.notes ? { notes: data.notes } : {}),
      },
      {
        onSuccess: () => {
          onSuccess?.()
        },
      }
    )
  }

  // Handle full refund button
  const handleFullRefund = () => {
    setValue('amount', remainingCreditable.toFixed(2))
  }

  const isSubmitting = createCreditNote.isPending

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {/* Header */}
      <div className="border-b border-gray-200 pb-4">
        <h2 className="text-lg font-medium text-gray-900">
          {t('sales:creditNotes.createFromInvoice')}
        </h2>
        <div className="mt-2 text-sm text-gray-600">
          <p>
            <span className="font-medium">{t('sales:documents.number')}:</span> {invoice.document_number}
          </p>
          <p>
            <span className="font-medium">{t('sales:documents.total')}:</span>{' '}
            {invoiceTotal.toFixed(2)}
          </p>
          <p className="text-blue-700">
            {t('sales:creditNotes.form.remainingCreditable', {
              amount: remainingCreditable.toFixed(2),
            })}
          </p>
        </div>
      </div>

      {/* Amount Field */}
      <div>
        <label htmlFor="amount" className="block text-sm font-medium text-gray-700">
          {t('sales:creditNotes.amount')}
        </label>
        <div className="mt-1 flex gap-2">
          <input
            {...register('amount')}
            type="number"
            step="0.01"
            id="amount"
            className={`flex-1 rounded-md border ${
              errors.amount || customAmountError
                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
            } px-3 py-2 text-sm`}
            disabled={isSubmitting}
            placeholder="0.00"
          />
          <button
            type="button"
            onClick={handleFullRefund}
            className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            disabled={isSubmitting}
          >
            {t('sales:creditNotes.messages.fullRefund', 'Full Refund')}
          </button>
        </div>
        {(errors.amount || customAmountError) && (
          <p className="mt-1 text-sm text-red-600">
            {errors.amount ? t(errors.amount.message!) : customAmountError}
          </p>
        )}
        <p className="mt-1 text-xs text-gray-500">
          {t('sales:creditNotes.form.maxAmount', { amount: remainingCreditable.toFixed(2) })}
        </p>
      </div>

      {/* Reason Field */}
      <div>
        <label htmlFor="reason" className="block text-sm font-medium text-gray-700">
          {t('sales:creditNotes.reason.title')}
        </label>
        <select
          {...register('reason')}
          id="reason"
          className={`mt-1 block w-full rounded-md border ${
            errors.reason
              ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
              : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
          } px-3 py-2 text-sm`}
          disabled={isSubmitting}
        >
          <option value="">{t('common:select', 'Select...')}</option>
          <option value="return">{t('sales:creditNotes.reason.return')}</option>
          <option value="price_adjustment">{t('sales:creditNotes.reason.priceAdjustment')}</option>
          <option value="billing_error">{t('sales:creditNotes.reason.billingError')}</option>
          <option value="damaged_goods">{t('sales:creditNotes.reason.damagedGoods')}</option>
          <option value="service_issue">{t('sales:creditNotes.reason.serviceIssue')}</option>
          <option value="other">{t('sales:creditNotes.reason.other')}</option>
        </select>
        {errors.reason && (
          <p className="mt-1 text-sm text-red-600">{t(errors.reason.message!)}</p>
        )}
      </div>

      {/* Notes Field */}
      <div>
        <label htmlFor="notes" className="block text-sm font-medium text-gray-700">
          {t('sales:creditNotes.notes')}
        </label>
        <textarea
          {...register('notes')}
          id="notes"
          rows={3}
          className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder={t('sales:creditNotes.notesPlaceholder')}
          disabled={isSubmitting}
        />
      </div>

      {/* Error Display */}
      {createCreditNote.isError && (
        <div className="rounded-md bg-red-50 p-4">
          <div className="flex">
            <AlertCircle className="h-5 w-5 text-red-400" />
            <div className="ml-3">
              <p className="text-sm text-red-800">
                {createCreditNote.error?.message ||
                  t('sales:creditNotes.messages.createFailed')}
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Action Buttons */}
      <div className="flex justify-end gap-3">
        <button
          type="button"
          onClick={onCancel}
          className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          disabled={isSubmitting}
        >
          {t('common:actions.cancel')}
        </button>
        <button
          type="submit"
          className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:bg-gray-400"
          disabled={isSubmitting || !!customAmountError}
        >
          {isSubmitting ? t('common:status.saving', 'Saving...') : t('common:actions.save')}
        </button>
      </div>
    </form>
  )
}
