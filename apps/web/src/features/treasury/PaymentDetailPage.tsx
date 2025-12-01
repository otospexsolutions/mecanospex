import { Link, useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, CreditCard, Calendar, User, Receipt, FileText } from 'lucide-react'
import { api, apiDelete } from '../../lib/api'

interface PaymentAllocation {
  id: string
  document_id: string
  document_number: string
  amount: string
}

interface Payment {
  id: string
  partner_id: string
  partner: {
    id: string
    name: string
  } | null
  payment_method_id: string
  payment_method: {
    id: string
    code: string
    name: string
  } | null
  instrument_id: string | null
  repository_id: string | null
  amount: string
  currency: string
  payment_date: string
  status: 'pending' | 'completed' | 'cancelled'
  reference: string | null
  notes: string | null
  allocations: PaymentAllocation[]
  created_at: string
}

interface PaymentResponse {
  data: Payment
}

const statusColors: Record<Payment['status'], string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  completed: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
}

const statusLabels: Record<Payment['status'], string> = {
  pending: 'Pending',
  completed: 'Completed',
  cancelled: 'Cancelled',
}

export function PaymentDetailPage() {
  const { t } = useTranslation()
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data, isLoading, error } = useQuery({
    queryKey: ['payment', id],
    queryFn: async () => {
      if (!id) throw new Error('No payment ID')
      const response = await api.get<PaymentResponse>(`/payments/${id}`)
      return response.data
    },
    enabled: Boolean(id),
  })

  const deleteMutation = useMutation({
    mutationFn: async () => {
      if (!id) throw new Error('No payment ID')
      return apiDelete(`/payments/${id}`)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['payments'] })
      void navigate('/treasury/payments')
    },
  })

  const handleDelete = () => {
    if (window.confirm('Are you sure you want to delete this payment?')) {
      void deleteMutation.mutateAsync()
    }
  }

  const formatCurrency = (amount: string | number) => {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: data?.data.currency ?? 'USD',
    }).format(num)
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-gray-500">{t('status.loading')}</div>
      </div>
    )
  }

  if (error || !data?.data) {
    return (
      <div className="space-y-6">
        <Link
          to="/treasury/payments"
          className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4" />
          {t('actions.back')}
        </Link>
        <div className="rounded-lg bg-red-50 p-4 text-red-700">
          Payment not found or error loading data.
        </div>
      </div>
    )
  }

  const payment = data.data

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link
            to="/treasury/payments"
            className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
          >
            <ArrowLeft className="h-4 w-4" />
            {t('actions.back')}
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              Payment Details
            </h1>
            <div className="flex items-center gap-3 mt-1">
              <span className="text-gray-500">{formatCurrency(payment.amount)}</span>
              <span
                className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColors[payment.status]}`}
              >
                {statusLabels[payment.status]}
              </span>
            </div>
          </div>
        </div>
        <div className="flex items-center gap-2">
          {payment.status === 'pending' && (
            <button
              onClick={handleDelete}
              disabled={deleteMutation.isPending}
              className="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50"
            >
              Cancel Payment
            </button>
          )}
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Payment Info */}
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CreditCard className="h-5 w-5 text-gray-400" />
            Payment Information
          </h2>
          <dl className="grid grid-cols-2 gap-4">
            <div>
              <dt className="text-sm font-medium text-gray-500">Amount</dt>
              <dd className="mt-1 text-lg font-semibold text-gray-900">
                {formatCurrency(payment.amount)}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Date</dt>
              <dd className="mt-1 text-sm text-gray-900 flex items-center gap-1">
                <Calendar className="h-4 w-4 text-gray-400" />
                {new Date(payment.payment_date).toLocaleDateString()}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Method</dt>
              <dd className="mt-1 text-sm text-gray-900">
                {payment.payment_method?.name ?? '-'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Status</dt>
              <dd className="mt-1">
                <span
                  className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColors[payment.status]}`}
                >
                  {statusLabels[payment.status]}
                </span>
              </dd>
            </div>
            {payment.reference && (
              <div className="col-span-2">
                <dt className="text-sm font-medium text-gray-500">Reference</dt>
                <dd className="mt-1 text-sm text-gray-900 font-mono">
                  {payment.reference}
                </dd>
              </div>
            )}
          </dl>
        </div>

        {/* Partner Info */}
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <User className="h-5 w-5 text-gray-400" />
            Partner
          </h2>
          {payment.partner ? (
            <div>
              <Link
                to={`/sales/customers/${payment.partner.id}`}
                className="text-blue-600 hover:text-blue-800 font-medium"
              >
                {payment.partner.name}
              </Link>
            </div>
          ) : (
            <p className="text-sm text-gray-500">No partner linked</p>
          )}
        </div>
      </div>

      {/* Allocations */}
      {payment.allocations.length > 0 && (
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <FileText className="h-5 w-5 text-gray-400" />
            Invoice Allocations
          </h2>
          <div className="overflow-hidden rounded-lg border border-gray-200">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                    Document
                  </th>
                  <th className="px-4 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                    Amount
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 bg-white">
                {payment.allocations.map((allocation) => (
                  <tr key={allocation.id}>
                    <td className="whitespace-nowrap px-4 py-3">
                      <Link
                        to={`/sales/invoices/${allocation.document_id}`}
                        className="font-medium text-blue-600 hover:text-blue-800"
                      >
                        {allocation.document_number}
                      </Link>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-end text-sm font-medium text-gray-900">
                      {formatCurrency(allocation.amount)}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-gray-50">
                <tr>
                  <td className="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-900">
                    Total Allocated
                  </td>
                  <td className="whitespace-nowrap px-4 py-3 text-end text-sm font-semibold text-gray-900">
                    {formatCurrency(
                      payment.allocations.reduce(
                        (sum, a) => sum + parseFloat(a.amount),
                        0
                      )
                    )}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      )}

      {/* Notes */}
      {payment.notes && (
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Receipt className="h-5 w-5 text-gray-400" />
            Notes
          </h2>
          <p className="text-sm text-gray-700 whitespace-pre-wrap">{payment.notes}</p>
        </div>
      )}

      {/* Metadata */}
      <div className="text-sm text-gray-500">
        <p>Created: {new Date(payment.created_at).toLocaleString()}</p>
      </div>
    </div>
  )
}
