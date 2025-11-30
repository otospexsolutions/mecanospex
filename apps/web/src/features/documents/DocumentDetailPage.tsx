import { Link, useParams, useLocation, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Edit, Calendar, Building2, FileText, Check, X, ArrowRight, Printer, Send, CreditCard, MinusCircle } from 'lucide-react'
import { api, apiPost } from '../../lib/api'
import type { DocumentType } from './DocumentListPage'

interface DocumentLine {
  id: string
  product_id: string
  product_name: string
  description: string
  quantity: number
  unit_price: number
  tax_rate: number
  line_total: number
}

interface Document {
  id: string
  document_number: string
  type: 'quote' | 'order' | 'invoice' | 'credit_note' | 'delivery_note' | 'sales_order' | 'purchase_order'
  status: 'draft' | 'confirmed' | 'posted' | 'cancelled'
  partner_id: string
  partner_name: string
  partner_email: string | null
  total_amount: number
  tax_amount: number
  net_amount: number
  issue_date: string
  due_date: string | null
  notes: string | null
  lines: DocumentLine[]
  created_at: string
  updated_at: string
}

const typeColors: Record<string, string> = {
  quote: 'bg-yellow-100 text-yellow-800',
  order: 'bg-blue-100 text-blue-800',
  sales_order: 'bg-blue-100 text-blue-800',
  purchase_order: 'bg-purple-100 text-purple-800',
  invoice: 'bg-green-100 text-green-800',
  credit_note: 'bg-red-100 text-red-800',
  delivery_note: 'bg-purple-100 text-purple-800',
}

const typeLabels: Record<string, string> = {
  quote: 'Quote',
  order: 'Order',
  sales_order: 'Sales Order',
  purchase_order: 'Purchase Order',
  invoice: 'Invoice',
  credit_note: 'Credit Note',
  delivery_note: 'Delivery Note',
}

const statusColors: Record<Document['status'], string> = {
  draft: 'bg-gray-100 text-gray-800',
  confirmed: 'bg-blue-100 text-blue-800',
  posted: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
}

const statusLabels: Record<Document['status'], string> = {
  draft: 'Draft',
  confirmed: 'Confirmed',
  posted: 'Posted',
  cancelled: 'Cancelled',
}

const documentTypeToPath: Record<DocumentType, string> = {
  quote: '/sales/quotes',
  sales_order: '/sales/orders',
  invoice: '/sales/invoices',
  purchase_order: '/purchases/orders',
  delivery_note: '/inventory/delivery-notes',
  credit_note: '/sales/credit-notes',
}

// Map document types to their API endpoints
const documentTypeToApiEndpoint: Record<DocumentType, string> = {
  quote: '/quotes',
  sales_order: '/orders',
  invoice: '/invoices',
  purchase_order: '/purchase-orders',
  delivery_note: '/delivery-notes',
  credit_note: '/credit-notes',
}

function getDocumentTypeFromPath(pathname: string): DocumentType | undefined {
  if (pathname.includes('/sales/quotes')) return 'quote'
  if (pathname.includes('/sales/orders')) return 'sales_order'
  if (pathname.includes('/sales/invoices')) return 'invoice'
  if (pathname.includes('/purchases/orders')) return 'purchase_order'
  if (pathname.includes('/inventory/delivery-notes')) return 'delivery_note'
  if (pathname.includes('/sales/credit-notes')) return 'credit_note'
  return undefined
}

// Conversion targets by document type
const conversionTargets: Partial<Record<DocumentType, { label: string; targetType: DocumentType; path: string }>> = {
  quote: { label: 'Convert to Order', targetType: 'sales_order', path: '/sales/orders' },
  sales_order: { label: 'Convert to Invoice', targetType: 'invoice', path: '/sales/invoices' },
}

// Create credit note targets (separate from conversion)
const creditNoteTargets: Partial<Record<DocumentType, { label: string; path: string }>> = {
  invoice: { label: 'Create Credit Note', path: '/sales/credit-notes/new' },
}

export function DocumentDetailPage() {
  const { t } = useTranslation()
  const { id = '' } = useParams<{ id: string }>()
  const location = useLocation()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  // Determine document type from URL path first
  const contextType = getDocumentTypeFromPath(location.pathname)
  const apiEndpoint = contextType != null ? documentTypeToApiEndpoint[contextType] : null

  const { data: document, isLoading, error } = useQuery({
    queryKey: ['document', contextType, id],
    queryFn: async () => {
      if (apiEndpoint == null) throw new Error('No API endpoint')
      const response = await api.get<{ data: Document }>(`${apiEndpoint}/${id}`)
      return response.data.data
    },
    enabled: id.length > 0 && apiEndpoint != null,
  })

  // Determine effective type and paths
  const effectiveType = contextType ?? (document?.type as DocumentType | undefined)
  const effectiveApiEndpoint = effectiveType != null ? documentTypeToApiEndpoint[effectiveType] : '/documents'
  const basePath = effectiveType != null ? documentTypeToPath[effectiveType] : '/documents'

  // Status transition mutations
  const confirmMutation = useMutation({
    mutationFn: () => apiPost<Document>(`${effectiveApiEndpoint}/${id}/confirm`, {}),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['document', contextType, id] })
      void queryClient.invalidateQueries({ queryKey: ['documents'] })
    },
  })

  const cancelMutation = useMutation({
    mutationFn: () => apiPost<Document>(`${effectiveApiEndpoint}/${id}/cancel`, {}),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['document', contextType, id] })
      void queryClient.invalidateQueries({ queryKey: ['documents'] })
    },
  })

  const postMutation = useMutation({
    mutationFn: () => apiPost<Document>(`${effectiveApiEndpoint}/${id}/post`, {}),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['document', contextType, id] })
      void queryClient.invalidateQueries({ queryKey: ['documents'] })
    },
  })

  const convertMutation = useMutation({
    mutationFn: (targetType: DocumentType) =>
      apiPost<{ data: Document }>(`${effectiveApiEndpoint}/${id}/convert-to-${targetType === 'sales_order' ? 'order' : 'invoice'}`, {}),
    onSuccess: (data) => {
      void queryClient.invalidateQueries({ queryKey: ['documents'] })
      const target = conversionTargets[document?.type as DocumentType]
      if (target != null) {
        void navigate(`${target.path}/${data.data.id}`)
      }
    },
  })

  const isActionPending =
    confirmMutation.isPending ||
    cancelMutation.isPending ||
    postMutation.isPending ||
    convertMutation.isPending

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount)
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-gray-500">{t('status.loading')}</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="space-y-4">
        <Link
          to={basePath}
          className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4" />
          {t('actions.back')}
        </Link>
        <div className="rounded-lg bg-red-50 p-4 text-red-700">
          Document not found or an error occurred.
        </div>
      </div>
    )
  }

  if (!document) {
    return null
  }

  // Recalculate basePath now that we have the document
  const finalBasePath = documentTypeToPath[document.type as DocumentType]
  const conversionTarget = conversionTargets[document.type as DocumentType]
  const creditNoteTarget = creditNoteTargets[document.type as DocumentType]

  // Determine available actions based on status and type
  const canEdit = document.status === 'draft'
  const canConfirm = document.status === 'draft'
  const canCancel = document.status === 'draft' || document.status === 'confirmed'
  const canPost = document.status === 'confirmed' && (document.type === 'invoice' || document.type === 'credit_note')
  const canConvert = (document.status === 'confirmed' || document.status === 'draft') && conversionTarget != null
  const canRecordPayment = document.type === 'invoice' && document.status === 'posted'

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link
            to={finalBasePath}
            className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
          >
            <ArrowLeft className="h-4 w-4" />
            {t('actions.back')}
          </Link>
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-bold text-gray-900">
                {document.document_number}
              </h1>
              <span
                className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${typeColors[document.type]}`}
              >
                {typeLabels[document.type]}
              </span>
              <span
                className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColors[document.status]}`}
              >
                {statusLabels[document.status]}
              </span>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-2">
          {/* Print button - always available */}
          <button
            type="button"
            className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            title="Print"
          >
            <Printer className="h-4 w-4" />
          </button>

          {/* Send button - always available */}
          <button
            type="button"
            className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            title="Send"
          >
            <Send className="h-4 w-4" />
          </button>

          {/* Cancel button */}
          {canCancel && (
            <button
              type="button"
              disabled={isActionPending}
              onClick={() => {
                cancelMutation.mutate()
              }}
              className="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50 transition-colors"
            >
              <X className="h-4 w-4" />
              Cancel
            </button>
          )}

          {/* Edit button */}
          {canEdit && (
            <Link
              to={`${finalBasePath}/${document.id}/edit`}
              className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            >
              <Edit className="h-4 w-4" />
              {t('actions.edit')}
            </Link>
          )}

          {/* Confirm button */}
          {canConfirm && (
            <button
              type="button"
              disabled={isActionPending}
              onClick={() => {
                confirmMutation.mutate()
              }}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
            >
              <Check className="h-4 w-4" />
              {t('actions.confirm')}
            </button>
          )}

          {/* Post button - for invoices and credit notes */}
          {canPost && (
            <button
              type="button"
              disabled={isActionPending}
              onClick={() => {
                postMutation.mutate()
              }}
              className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 transition-colors"
            >
              <Check className="h-4 w-4" />
              Post
            </button>
          )}

          {/* Convert button */}
          {conversionTarget != null && canConvert && (
            <button
              type="button"
              disabled={isActionPending}
              onClick={() => {
                convertMutation.mutate(conversionTarget.targetType)
              }}
              className="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50 transition-colors"
            >
              <ArrowRight className="h-4 w-4" />
              {conversionTarget.label}
            </button>
          )}

          {/* Record Payment button - for posted invoices */}
          {canRecordPayment && (
            <Link
              to={`/treasury/payments/new?invoice=${document.id}`}
              className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors"
            >
              <CreditCard className="h-4 w-4" />
              Record Payment
            </Link>
          )}

          {/* Create Credit Note button - for posted invoices */}
          {creditNoteTarget != null && document.status === 'posted' && (
            <Link
              to={`${creditNoteTarget.path}?invoice=${document.id}`}
              className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors"
            >
              <MinusCircle className="h-4 w-4" />
              {creditNoteTarget.label}
            </Link>
          )}
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Partner Info */}
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="mb-4 text-lg font-semibold text-gray-900">
            Partner Information
          </h2>
          <dl className="space-y-3">
            <div className="flex items-start gap-3">
              <Building2 className="mt-0.5 h-5 w-5 text-gray-400" />
              <div>
                <dt className="text-sm font-medium text-gray-500">Partner</dt>
                <dd className="text-gray-900">{document.partner_name}</dd>
              </div>
            </div>
            {document.partner_email && (
              <div>
                <dt className="text-sm font-medium text-gray-500">Email</dt>
                <dd className="text-gray-900">{document.partner_email}</dd>
              </div>
            )}
          </dl>
        </div>

        {/* Document Info */}
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="mb-4 text-lg font-semibold text-gray-900">
            Document Information
          </h2>
          <dl className="space-y-3">
            <div className="flex items-start gap-3">
              <Calendar className="mt-0.5 h-5 w-5 text-gray-400" />
              <div>
                <dt className="text-sm font-medium text-gray-500">Issue Date</dt>
                <dd className="text-gray-900">
                  {new Date(document.issue_date).toLocaleDateString()}
                </dd>
              </div>
            </div>
            {document.due_date && (
              <div className="flex items-start gap-3">
                <Calendar className="mt-0.5 h-5 w-5 text-gray-400" />
                <div>
                  <dt className="text-sm font-medium text-gray-500">Due Date</dt>
                  <dd className="text-gray-900">
                    {new Date(document.due_date).toLocaleDateString()}
                  </dd>
                </div>
              </div>
            )}
          </dl>
        </div>

        {/* Totals */}
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="mb-4 text-lg font-semibold text-gray-900">Totals</h2>
          <dl className="space-y-3">
            <div className="flex justify-between">
              <dt className="text-sm text-gray-500">Subtotal</dt>
              <dd className="text-sm font-medium text-gray-900">
                {formatCurrency(document.net_amount)}
              </dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-sm text-gray-500">Tax</dt>
              <dd className="text-sm font-medium text-gray-900">
                {formatCurrency(document.tax_amount)}
              </dd>
            </div>
            <div className="border-t border-gray-200 pt-3">
              <div className="flex justify-between">
                <dt className="text-base font-semibold text-gray-900">Total</dt>
                <dd className="text-base font-semibold text-gray-900">
                  {formatCurrency(document.total_amount)}
                </dd>
              </div>
            </div>
          </dl>
        </div>
      </div>

      {/* Lines */}
      <div className="rounded-lg border border-gray-200 bg-white">
        <div className="border-b border-gray-200 px-6 py-4">
          <h2 className="text-lg font-semibold text-gray-900">
            <FileText className="me-2 inline h-5 w-5" />
            Line Items
          </h2>
        </div>
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                Item
              </th>
              <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                Description
              </th>
              <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                Qty
              </th>
              <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                Unit Price
              </th>
              <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                Tax
              </th>
              <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                Total
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200 bg-white">
            {document.lines.map((line) => (
              <tr key={line.id}>
                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                  {line.product_name}
                </td>
                <td className="px-6 py-4 text-sm text-gray-500">
                  {line.description}
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-end text-sm text-gray-900">
                  {line.quantity}
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-end text-sm text-gray-900">
                  {formatCurrency(line.unit_price)}
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-end text-sm text-gray-500">
                  {line.tax_rate}%
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-end text-sm font-medium text-gray-900">
                  {formatCurrency(line.line_total)}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Notes */}
      {document.notes && (
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="mb-4 text-lg font-semibold text-gray-900">Notes</h2>
          <p className="whitespace-pre-wrap text-gray-700">{document.notes}</p>
        </div>
      )}
    </div>
  )
}
