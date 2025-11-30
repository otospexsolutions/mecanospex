import { Link, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { ArrowLeft } from 'lucide-react'
import { api, apiPost } from '../../lib/api'

interface PaymentMethod {
  id: string
  name: string
  is_physical: boolean
}

interface Partner {
  id: string
  name: string
}

interface PaymentMethodsResponse {
  data: PaymentMethod[]
}

interface PartnersResponse {
  data: Partner[]
}

interface Payment {
  id: string
  payment_number: string
  amount: number
}

interface PaymentFormData {
  amount: string
  payment_method_id: string
  partner_id: string
  payment_date: string
  reference: string
  notes: string
}

export function PaymentForm() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<PaymentFormData>({
    defaultValues: {
      amount: '',
      payment_method_id: '',
      partner_id: '',
      payment_date: new Date().toISOString().split('T')[0],
      reference: '',
      notes: '',
    },
  })

  // Fetch payment methods
  const { data: paymentMethodsData } = useQuery({
    queryKey: ['payment-methods'],
    queryFn: async () => {
      const response = await api.get<PaymentMethodsResponse>('/payment-methods')
      return response.data
    },
  })

  const paymentMethods = paymentMethodsData?.data ?? []

  // Fetch partners
  const { data: partnersData } = useQuery({
    queryKey: ['partners'],
    queryFn: async () => {
      const response = await api.get<PartnersResponse>('/partners')
      return response.data
    },
  })

  const partners = partnersData?.data ?? []

  const createMutation = useMutation({
    mutationFn: (data: PaymentFormData) =>
      apiPost<Payment>('/payments', {
        ...data,
        amount: parseFloat(data.amount),
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['payments'] })
      void navigate('/treasury/payments')
    },
  })

  const onSubmit = (data: PaymentFormData) => {
    createMutation.mutate(data)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link
          to="/treasury/payments"
          className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4" />
          Back
        </Link>
        <h1 className="text-2xl font-bold text-gray-900">Record Payment</h1>
      </div>

      {/* Form */}
      <form onSubmit={(e) => { void handleSubmit(onSubmit)(e) }} className="space-y-6">
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <div className="grid gap-6 sm:grid-cols-2">
            {/* Amount */}
            <div>
              <label
                htmlFor="amount"
                className="block text-sm font-medium text-gray-700"
              >
                Amount *
              </label>
              <div className="relative mt-1">
                <span className="absolute start-3 top-1/2 -translate-y-1/2 text-gray-500">
                  $
                </span>
                <input
                  type="number"
                  step="0.01"
                  id="amount"
                  {...register('amount', {
                    required: 'Amount is required',
                    min: { value: 0.01, message: 'Amount must be positive' },
                  })}
                  className="mt-1 block w-full rounded-lg border border-gray-300 ps-8 pe-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              {errors.amount && (
                <p className="mt-1 text-sm text-red-600">{errors.amount.message}</p>
              )}
            </div>

            {/* Payment Method */}
            <div>
              <label
                htmlFor="payment_method_id"
                className="block text-sm font-medium text-gray-700"
              >
                Payment Method *
              </label>
              <select
                id="payment_method_id"
                {...register('payment_method_id', {
                  required: 'Payment method is required',
                })}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="">Select method</option>
                {paymentMethods.map((method) => (
                  <option key={method.id} value={method.id}>
                    {method.name}
                  </option>
                ))}
              </select>
              {errors.payment_method_id && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.payment_method_id.message}
                </p>
              )}
            </div>

            {/* Partner */}
            <div>
              <label
                htmlFor="partner_id"
                className="block text-sm font-medium text-gray-700"
              >
                Partner *
              </label>
              <select
                id="partner_id"
                {...register('partner_id', { required: 'Partner is required' })}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="">Select partner</option>
                {partners.map((partner) => (
                  <option key={partner.id} value={partner.id}>
                    {partner.name}
                  </option>
                ))}
              </select>
              {errors.partner_id && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.partner_id.message}
                </p>
              )}
            </div>

            {/* Payment Date */}
            <div>
              <label
                htmlFor="payment_date"
                className="block text-sm font-medium text-gray-700"
              >
                Payment Date *
              </label>
              <input
                type="date"
                id="payment_date"
                {...register('payment_date', {
                  required: 'Payment date is required',
                })}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
              {errors.payment_date && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.payment_date.message}
                </p>
              )}
            </div>

            {/* Reference */}
            <div>
              <label
                htmlFor="reference"
                className="block text-sm font-medium text-gray-700"
              >
                Reference
              </label>
              <input
                type="text"
                id="reference"
                {...register('reference')}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="Check number, transaction ID, etc."
              />
            </div>

            {/* Notes */}
            <div className="sm:col-span-2">
              <label
                htmlFor="notes"
                className="block text-sm font-medium text-gray-700"
              >
                Notes
              </label>
              <textarea
                id="notes"
                rows={3}
                {...register('notes')}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="Additional notes..."
              />
            </div>
          </div>
        </div>

        {/* Form Actions */}
        <div className="flex items-center justify-end gap-4">
          <Link
            to="/treasury/payments"
            className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancel
          </Link>
          <button
            type="submit"
            disabled={isSubmitting}
            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {isSubmitting ? 'Saving...' : 'Save'}
          </button>
        </div>
      </form>
    </div>
  )
}
