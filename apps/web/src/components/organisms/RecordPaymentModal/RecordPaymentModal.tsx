import { useEffect, useState, useMemo } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { Loader2, Plus, Lock, CheckCircle } from 'lucide-react'
import { Modal, ModalHeader, ModalContent, ModalFooter } from '../Modal'
import { FormField } from '../../atoms/FormField'
import { Input } from '../../atoms/Input'
import { Select } from '../../atoms/Select'
import { Textarea } from '../../atoms/Textarea'
import { Button } from '../../atoms/Button'
import { api, apiPost } from '../../../lib/api'
import { AddRepositoryModal } from '../AddRepositoryModal'
import type { PaymentAllocationPreview } from '../../../types/treasury'

interface PaymentMethod {
  id: string
  code: string
  name: string
  is_physical: boolean
  has_maturity: boolean
}

interface Repository {
  id: string
  code: string
  name: string
  type: 'cash_register' | 'safe' | 'bank_account' | 'virtual'
}

interface PaymentMethodsResponse {
  data: PaymentMethod[]
}

interface RepositoriesResponse {
  data: Repository[]
}

interface Payment {
  id: string
  payment_number: string
  amount: number
}

interface PaymentFormData {
  amount: string
  payment_method_id: string
  repository_id: string
  partner_id: string
  payment_date: string
  reference: string
  notes: string
}

export interface InvoicePrefill {
  partner_id: string
  partner_name: string
  amount: number          // amount_residual, NOT total
  reference: string       // Invoice number
  document_id: string
  document_type: 'invoice' | 'sales_order'
}

export interface RecordPaymentModalProps {
  /**
   * Controls modal visibility
   */
  isOpen: boolean

  /**
   * Callback when modal should close
   */
  onClose: () => void

  /**
   * Callback after successful payment creation
   */
  onSuccess?: () => void

  /**
   * Prefill data from the invoice/document
   */
  prefill: InvoicePrefill
}

/**
 * RecordPaymentModal - Modal for recording payments against invoices
 *
 * Pre-fills payment form with invoice data (partner, amount, reference).
 * Partner field is locked when prefilled from an invoice.
 * Modal stays in context - no page navigation.
 *
 * @example
 * ```tsx
 * <RecordPaymentModal
 *   isOpen={showPaymentModal}
 *   onClose={() => setShowPaymentModal(false)}
 *   onSuccess={() => {
 *     queryClient.invalidateQueries(['invoice', invoiceId])
 *     toast.success('Payment recorded')
 *   }}
 *   prefill={{
 *     partner_id: invoice.partner_id,
 *     partner_name: invoice.partner_name,
 *     amount: invoice.amount_residual,
 *     reference: invoice.document_number,
 *     document_id: invoice.id,
 *     document_type: 'invoice',
 *   }}
 * />
 * ```
 */
export function RecordPaymentModal({
  isOpen,
  onClose,
  onSuccess,
  prefill,
}: RecordPaymentModalProps) {
  const { t } = useTranslation(['treasury', 'common'])
  const queryClient = useQueryClient()
  const [showRepositoryModal, setShowRepositoryModal] = useState(false)
  const [paymentCreated, setPaymentCreated] = useState<Payment | null>(null)
  const [allocationResult, setAllocationResult] = useState<PaymentAllocationPreview | null>(null)

  // Form state with React Hook Form
  const {
    register,
    handleSubmit,
    reset,
    setValue,
    control,
    formState: { errors },
  } = useForm<PaymentFormData>({
    defaultValues: {
      amount: prefill.amount.toString(),
      payment_method_id: '',
      repository_id: '',
      partner_id: prefill.partner_id,
      payment_date: new Date().toISOString().split('T')[0],
      reference: prefill.reference,
      notes: `Payment for ${prefill.document_type} ${prefill.reference}`,
    },
  })

  // Watch the selected payment method
  const selectedPaymentMethodId = useWatch({ control, name: 'payment_method_id' })
  const selectedRepositoryId = useWatch({ control, name: 'repository_id' })

  // Reset form when modal opens with new prefill
  useEffect(() => {
    if (isOpen) {
      reset({
        amount: prefill.amount.toString(),
        payment_method_id: '',
        repository_id: '',
        partner_id: prefill.partner_id,
        payment_date: new Date().toISOString().split('T')[0],
        reference: prefill.reference,
        notes: `Payment for ${prefill.document_type} ${prefill.reference}`,
      })
      // Clear success state when reopening
      setPaymentCreated(null)
      setAllocationResult(null)
    }
  }, [isOpen, prefill, reset])

  // Fetch payment methods
  const { data: paymentMethodsData } = useQuery({
    queryKey: ['payment-methods'],
    queryFn: async () => {
      const response = await api.get<PaymentMethodsResponse>('/payment-methods')
      return response.data
    },
    enabled: isOpen,
  })

  const paymentMethods = paymentMethodsData?.data ?? []

  // Fetch repositories
  const { data: repositoriesData } = useQuery({
    queryKey: ['payment-repositories'],
    queryFn: async () => {
      const response = await api.get<RepositoriesResponse>('/payment-repositories')
      return response.data
    },
    enabled: isOpen,
  })

  const repositories = repositoriesData?.data ?? []

  // Get compatible repository types based on payment method
  const getCompatibleRepositoryTypes = (method: PaymentMethod | undefined): Repository['type'][] => {
    if (!method) return ['cash_register', 'safe', 'bank_account', 'virtual']

    const code = method.code.toUpperCase()

    // Cash payments go to cash registers or safes
    if (code === 'CASH' || code === 'ESPECES') {
      return ['cash_register', 'safe']
    }

    // Checks/instruments with maturity go to safes
    if (method.has_maturity || code === 'CHECK' || code === 'CHEQUE') {
      return ['safe']
    }

    // Bank transfers and cards go to bank accounts
    if (code === 'BANK_TRANSFER' || code === 'VIREMENT' || code === 'CARD' || code === 'CARTE') {
      return ['bank_account']
    }

    // Virtual/online payments
    if (code === 'PAYPAL' || code === 'STRIPE' || code === 'ONLINE') {
      return ['virtual', 'bank_account']
    }

    // Default: show all if no specific match
    return ['cash_register', 'safe', 'bank_account', 'virtual']
  }

  // Filter repositories based on selected payment method
  const selectedPaymentMethod = paymentMethods.find(m => m.id === selectedPaymentMethodId)
  const compatibleTypes = getCompatibleRepositoryTypes(selectedPaymentMethod)
  const filteredRepositories = useMemo(() =>
    repositories.filter(repo => compatibleTypes.includes(repo.type)),
    [repositories, compatibleTypes]
  )

  // Reset repository when payment method changes
  useEffect(() => {
    if (selectedPaymentMethodId && selectedRepositoryId) {
      // Check if current repository is still compatible
      const isStillCompatible = filteredRepositories.some(r => r.id === selectedRepositoryId)
      if (!isStillCompatible) {
        setValue('repository_id', '')
      }
    }
  }, [selectedPaymentMethodId, filteredRepositories, setValue, selectedRepositoryId])

  // Create payment mutation
  const mutation = useMutation({
    mutationFn: (data: PaymentFormData) =>
      apiPost<Payment>('/payments', {
        ...data,
        amount: parseFloat(data.amount),
        // Backend expects allocations array, not invoice_id
        allocations: prefill.document_id ? [
          {
            document_id: prefill.document_id,
            amount: parseFloat(data.amount),
          },
        ] : undefined,
      }),
    onSuccess: async (payment) => {
      // Invalidate queries
      void queryClient.invalidateQueries({ queryKey: ['payments'] })
      void queryClient.invalidateQueries({ queryKey: ['invoice', prefill.document_id] })
      void queryClient.invalidateQueries({ queryKey: ['invoices'] })
      void queryClient.invalidateQueries({ queryKey: ['documents'] })

      // Store payment info
      setPaymentCreated(payment)

      // Fetch allocation result to show user what happened
      try {
        const allocationResponse = await api.get<{ data: PaymentAllocationPreview }>(
          `/payments/${payment.id}/allocation`
        )
        setAllocationResult(allocationResponse.data.data)
      } catch (error) {
        // If allocation fetch fails, just close modal - payment was still successful
        console.error('Failed to fetch allocation result:', error)
        onSuccess?.()
        onClose()
      }
    },
  })

  const onSubmit = (data: PaymentFormData) => {
    mutation.mutate(data)
  }

  const handleFinalClose = () => {
    setPaymentCreated(null)
    setAllocationResult(null)
    onSuccess?.()
    onClose()
  }

  // Format currency
  const formatAmount = (amount: string | number): string => {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(num)
  }

  // Show success view if payment was created and we have allocation results
  const showSuccessView = paymentCreated !== null && allocationResult !== null

  return (
    <>
      <Modal isOpen={isOpen} onClose={showSuccessView ? handleFinalClose : onClose} size="lg">
        <ModalHeader
          title={showSuccessView
            ? t('treasury:payments.paymentRecorded', 'Payment Recorded Successfully')
            : t('treasury:payments.recordPayment', 'Record Payment')}
          onClose={showSuccessView ? handleFinalClose : onClose}
        />

        {/* Success View - Show allocation results */}
        {showSuccessView ? (
          <>
            <ModalContent>
              {/* Success message */}
              <div className="rounded-lg bg-green-50 p-4 mb-4">
                <div className="flex items-start gap-3">
                  <CheckCircle className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <h4 className="text-sm font-medium text-green-900">
                      {t('treasury:payments.recordedSuccess', 'Payment recorded successfully')}
                    </h4>
                    <p className="mt-1 text-sm text-green-700">
                      Payment #{paymentCreated.payment_number} for {formatAmount(paymentCreated.amount)}
                    </p>
                  </div>
                </div>
              </div>

              {/* Allocation Results */}
              <div className="space-y-4">
                <h4 className="font-medium text-gray-900">
                  {t('treasury:payments.allocationSummary', 'Payment Allocation Summary')}
                </h4>

                {allocationResult.allocations.length > 0 ? (
                  <div className="rounded-lg border border-gray-200">
                    {/* Allocations Table */}
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            {t('treasury:payments.invoice', 'Invoice')}
                          </th>
                          <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            {t('treasury:payments.balanceBefore', 'Balance Before')}
                          </th>
                          <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            {t('treasury:payments.allocated', 'Allocated')}
                          </th>
                          <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            {t('treasury:payments.remaining', 'Remaining')}
                          </th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-200 bg-white">
                        {allocationResult.allocations.map((allocation) => {
                          const originalBalance = parseFloat(allocation.original_balance ?? '0')
                          const allocated = parseFloat(allocation.amount)
                          const remaining = originalBalance - allocated

                          return (
                            <tr key={allocation.document_id}>
                              <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                {allocation.document_number}
                                {allocation.days_overdue && allocation.days_overdue > 0 && (
                                  <span className="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                                    {t('common:status.overdue', 'Overdue')} ({allocation.days_overdue}d)
                                  </span>
                                )}
                              </td>
                              <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">
                                {formatAmount(originalBalance)}
                              </td>
                              <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-green-600">
                                {formatAmount(allocated)}
                              </td>
                              <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">
                                {remaining === 0 ? (
                                  <span className="text-green-600 font-medium">
                                    {t('treasury:payments.fullyPaid', 'Paid in Full')}
                                  </span>
                                ) : (
                                  formatAmount(remaining)
                                )}
                              </td>
                            </tr>
                          )
                        })}
                      </tbody>
                    </table>

                    {/* Summary Footer */}
                    <div className="border-t border-gray-200 bg-gray-50 px-4 py-3 space-y-2">
                      <div className="flex justify-between items-center">
                        <span className="text-sm font-medium text-gray-700">
                          {t('treasury:payments.totalAllocatedToInvoices', 'Total Allocated to Invoices')}
                        </span>
                        <span className="text-sm font-semibold text-gray-900">
                          {formatAmount(allocationResult.total_to_invoices)}
                        </span>
                      </div>

                      {/* Excess amount handling */}
                      {parseFloat(allocationResult.excess_amount) > 0 && (
                        <div className={`rounded-md p-2 ${
                          allocationResult.excess_handling === 'tolerance_writeoff'
                            ? 'bg-blue-50'
                            : 'bg-yellow-50'
                        }`}>
                          <div className="flex justify-between items-center">
                            <span className={`text-sm font-medium ${
                              allocationResult.excess_handling === 'tolerance_writeoff'
                                ? 'text-blue-900'
                                : 'text-yellow-900'
                            }`}>
                              {allocationResult.excess_handling === 'tolerance_writeoff'
                                ? t('treasury:payments.toleranceWriteoff', 'Tolerance Write-off')
                                : t('treasury:payments.excessCreditBalance', 'Excess (Credit Balance)')}
                            </span>
                            <span className={`text-sm font-semibold ${
                              allocationResult.excess_handling === 'tolerance_writeoff'
                                ? 'text-blue-900'
                                : 'text-yellow-900'
                            }`}>
                              {formatAmount(allocationResult.excess_amount)}
                            </span>
                          </div>
                          <p className={`mt-1 text-xs ${
                            allocationResult.excess_handling === 'tolerance_writeoff'
                              ? 'text-blue-700'
                              : 'text-yellow-700'
                          }`}>
                            {allocationResult.excess_handling === 'tolerance_writeoff'
                              ? t('treasury:payments.toleranceWriteoffExplanation', 'Small difference written off (within tolerance)')
                              : t('treasury:payments.excessCreditExplanation', 'Excess amount kept as credit balance for future invoices')}
                          </p>
                        </div>
                      )}
                    </div>
                  </div>
                ) : (
                  <div className="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center">
                    <p className="text-sm text-gray-600">
                      {t('treasury:payments.noInvoicesAllocated', 'Payment recorded but not allocated to any invoices')}
                    </p>
                  </div>
                )}
              </div>
            </ModalContent>

            <ModalFooter>
              <Button
                type="button"
                variant="primary"
                onClick={handleFinalClose}
              >
                {t('common:actions.close', 'Close')}
              </Button>
            </ModalFooter>
          </>
        ) : (
          /* Payment Form */
          <form onSubmit={(e) => { void handleSubmit(onSubmit)(e) }}>
          <ModalContent>
            {/* Invoice context info */}
            <div className="rounded-lg bg-blue-50 p-4 mb-4">
              <div className="flex items-center gap-2 text-sm text-blue-800">
                <span className="font-medium">{t('treasury:payments.payingFor', 'Paying for')}:</span>
                <span>{prefill.reference}</span>
                <span className="text-blue-600">({prefill.partner_name})</span>
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              {/* Amount */}
              <FormField
                label={t('treasury:payments.amount', 'Amount')}
                htmlFor="payment-amount"
                required
                error={errors.amount?.message}
              >
                <div className="relative">
                  <span className="absolute start-3 top-1/2 -translate-y-1/2 text-gray-500">
                    $
                  </span>
                  <Input
                    id="payment-amount"
                    type="number"
                    step="0.01"
                    className="ps-8"
                    {...register('amount', {
                      required: t('common:validation.required'),
                      min: { value: 0.01, message: t('treasury:payments.amountPositive', 'Amount must be positive') },
                    })}
                  />
                </div>
              </FormField>

              {/* Payment Date */}
              <FormField
                label={t('treasury:payments.date', 'Payment Date')}
                htmlFor="payment-date"
                required
                error={errors.payment_date?.message}
              >
                <Input
                  id="payment-date"
                  type="date"
                  {...register('payment_date', {
                    required: t('common:validation.required'),
                  })}
                />
              </FormField>

              {/* Payment Method */}
              <FormField
                label={t('treasury:payments.method', 'Payment Method')}
                htmlFor="payment-method"
                required
                error={errors.payment_method_id?.message}
              >
                <Select
                  id="payment-method"
                  {...register('payment_method_id', {
                    required: t('common:validation.required'),
                  })}
                  error={!!errors.payment_method_id}
                >
                  <option value="">{t('common:actions.select')}</option>
                  {paymentMethods.map((method) => (
                    <option key={method.id} value={method.id}>
                      {method.name}
                    </option>
                  ))}
                </Select>
              </FormField>

              {/* Repository */}
              <FormField
                label={t('treasury:repositories.title', 'Repository')}
                htmlFor="payment-repository"
                required
                error={errors.repository_id?.message}
              >
                <div className="flex gap-2">
                  <Select
                    id="payment-repository"
                    {...register('repository_id', {
                      required: t('common:validation.required'),
                    })}
                    error={!!errors.repository_id}
                    className="flex-1"
                  >
                    <option value="">{t('common:actions.select')}</option>
                    {filteredRepositories.map((repo) => (
                      <option key={repo.id} value={repo.id}>
                        {repo.name} ({repo.code})
                      </option>
                    ))}
                  </Select>
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={() => { setShowRepositoryModal(true) }}
                    title={t('treasury:repositories.add', 'Add repository')}
                  >
                    <Plus className="h-4 w-4" />
                  </Button>
                </div>
              </FormField>

              {/* Partner (locked) */}
              <FormField
                label={t('treasury:payments.partner', 'Partner')}
                htmlFor="payment-partner"
              >
                <div className="relative">
                  <Input
                    id="payment-partner"
                    value={prefill.partner_name}
                    disabled
                    className="pe-10 bg-gray-50"
                  />
                  <Lock className="absolute end-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                </div>
                <input type="hidden" {...register('partner_id')} />
              </FormField>

              {/* Reference */}
              <FormField
                label={t('treasury:payments.reference', 'Reference')}
                htmlFor="payment-reference"
              >
                <Input
                  id="payment-reference"
                  {...register('reference')}
                  placeholder={t('treasury:payments.referencePlaceholder', 'Check number, transaction ID...')}
                />
              </FormField>
            </div>

            {/* Notes */}
            <FormField
              label={t('treasury:payments.notes', 'Notes')}
              htmlFor="payment-notes"
            >
              <Textarea
                id="payment-notes"
                rows={2}
                {...register('notes')}
                placeholder={t('treasury:payments.notesPlaceholder', 'Additional notes...')}
              />
            </FormField>

            {/* Error message */}
            {mutation.isError && (
              <div className="rounded-lg bg-red-50 p-3 text-sm text-red-700">
                {mutation.error instanceof Error
                  ? mutation.error.message
                  : t('common:error.generic')}
              </div>
            )}
          </ModalContent>

          <ModalFooter>
            <Button
              type="button"
              variant="secondary"
              onClick={onClose}
              disabled={mutation.isPending}
            >
              {t('common:actions.cancel')}
            </Button>
            <Button
              type="submit"
              variant="primary"
              disabled={mutation.isPending}
            >
              {mutation.isPending && <Loader2 className="h-4 w-4 animate-spin" />}
              {t('treasury:payments.record', 'Record Payment')}
            </Button>
          </ModalFooter>
          </form>
        )}
      </Modal>

      {/* Add Repository Modal */}
      <AddRepositoryModal
        isOpen={showRepositoryModal}
        onClose={() => { setShowRepositoryModal(false) }}
        onSuccess={(repository) => {
          setValue('repository_id', repository.id)
          void queryClient.invalidateQueries({ queryKey: ['payment-repositories'] })
        }}
      />
    </>
  )
}
