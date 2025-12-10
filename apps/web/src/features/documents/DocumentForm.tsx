import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams, useLocation } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Plus } from 'lucide-react'
import { toast } from 'sonner'
import { api, apiPost, apiPatch } from '../../lib/api'
import { DocumentLineEditor, type DocumentLine } from '../../components/documents/DocumentLineEditor'
import { PurchaseOrderAdditionalCosts } from './components/PurchaseOrderAdditionalCosts'
import { AddPartnerModal } from '../../components/organisms'
import type { DocumentType } from './DocumentListPage'

interface Partner {
  id: string
  name: string
  type: 'customer' | 'supplier' | 'both'
}

interface PartnersResponse {
  data: Partner[]
}

// Determine whether to filter by customer or supplier based on document type
function getPartnerTypeForDocument(docType: DocumentType | undefined): 'customer' | 'supplier' | undefined {
  if (!docType) return undefined
  // Sales documents need customers
  if (['quote', 'sales_order', 'invoice', 'credit_note', 'delivery_note'].includes(docType)) {
    return 'customer'
  }
  // Purchase documents need suppliers
  if (docType === 'purchase_order') {
    return 'supplier'
  }
  return undefined
}

interface DocumentApiLine {
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
  status: string
  partner_id: string
  partner_name: string
  total_amount: number
  tax_amount: number
  net_amount: number
  issue_date: string
  due_date: string | null
  notes: string | null
  lines?: DocumentApiLine[]
}

interface DocumentFormData {
  type: 'quote' | 'order' | 'invoice' | 'credit_note' | 'delivery_note' | 'sales_order' | 'purchase_order' | ''
  partner_id: string
  issue_date: string
  due_date: string
  notes: string
}

const documentTypeToPath: Record<DocumentType, string> = {
  quote: '/sales/quotes',
  sales_order: '/sales/orders',
  invoice: '/sales/invoices',
  purchase_order: '/purchases/orders',
  delivery_note: '/inventory/delivery-notes',
  credit_note: '/sales/credit-notes',
}

const documentTypeToTitle: Record<DocumentType, string> = {
  quote: 'Quote',
  sales_order: 'Sales Order',
  invoice: 'Invoice',
  purchase_order: 'Purchase Order',
  delivery_note: 'Delivery Note',
  credit_note: 'Credit Note',
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

interface DocumentFormProps {
  documentType?: DocumentType
}

export function DocumentForm({ documentType }: DocumentFormProps) {
  const { t } = useTranslation()
  const { id = '' } = useParams<{ id: string }>()
  const location = useLocation()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const isEditing = id.length > 0

  // Track if initial lines have been loaded
  const [hasInitializedLines, setHasInitializedLines] = useState(false)
  // Lines state (managed separately from form)
  const [lines, setLines] = useState<DocumentLine[]>([])
  // Partner modal state
  const [showPartnerModal, setShowPartnerModal] = useState(false)

  // Determine document type from props or URL
  const effectiveType = documentType ?? getDocumentTypeFromPath(location.pathname)
  const basePath = effectiveType ? documentTypeToPath[effectiveType] : '/documents'
  const apiEndpoint = effectiveType ? documentTypeToApiEndpoint[effectiveType] : '/documents'
  const entityName = effectiveType ? documentTypeToTitle[effectiveType] : 'Document'

  const {
    register,
    handleSubmit,
    reset,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<DocumentFormData>({
    defaultValues: {
      type: effectiveType ?? '',
      partner_id: '',
      issue_date: new Date().toISOString().split('T')[0],
      due_date: '',
      notes: '',
    },
  })

  // Determine partner type to filter based on document type
  const partnerTypeFilter = getPartnerTypeForDocument(effectiveType)

  // Fetch partners for dropdown - filtered by document type
  const { data: partnersData } = useQuery({
    queryKey: ['partners', partnerTypeFilter],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (partnerTypeFilter) {
        params.append('type', partnerTypeFilter)
      }
      const query = params.toString()
      const response = await api.get<PartnersResponse>(`/partners${query ? `?${query}` : ''}`)
      return response.data
    },
  })

  const partners = partnersData?.data ?? []

  // Fetch document data when editing
  const { data: document, isLoading } = useQuery({
    queryKey: ['document', effectiveType, id],
    queryFn: async () => {
      const response = await api.get<{ data: Document }>(`${apiEndpoint}/${id}`)
      return response.data.data
    },
    enabled: isEditing && apiEndpoint !== '/documents',
  })

  // Populate form when document data loads
  useEffect(() => {
    if (document) {
      reset({
        type: document.type,
        partner_id: document.partner_id,
        issue_date: document.issue_date,
        due_date: document.due_date ?? '',
        notes: document.notes ?? '',
      })
    }
  }, [document, reset])

  // Initialize lines from document (only once when document first loads)
  if (document?.lines && !hasInitializedLines) {
    setLines(document.lines)
    setHasInitializedLines(true)
  }

  const createMutation = useMutation({
    mutationFn: (data: DocumentFormData) => apiPost<Document>(apiEndpoint, data),
    onSuccess: (response) => {
      toast.success(t('status.success'))
      void queryClient.invalidateQueries({ queryKey: ['documents'] })
      void queryClient.invalidateQueries({ queryKey: [effectiveType] })
      // Navigate to the newly created document's detail page
      const documentId = response?.id
      if (documentId) {
        void navigate(`${basePath}/${documentId}`)
      }
    },
    onError: (error: Error & { response?: { data?: { message?: string; error?: { message?: string } } } }) => {
      const message = error.response?.data?.error?.message
        ?? error.response?.data?.message
        ?? error.message
      toast.error(message)
    },
  })

  const updateMutation = useMutation({
    mutationFn: (data: DocumentFormData) =>
      apiPatch<Document>(`${apiEndpoint}/${id}`, data),
    onSuccess: () => {
      toast.success(t('status.success'))
      void queryClient.invalidateQueries({ queryKey: ['documents'] })
      void queryClient.invalidateQueries({ queryKey: [effectiveType] })
      void queryClient.invalidateQueries({ queryKey: ['document', effectiveType, id] })
      void navigate(`${basePath}/${id}`)
    },
    onError: (error: Error & { response?: { data?: { message?: string; error?: { message?: string } } } }) => {
      const message = error.response?.data?.error?.message
        ?? error.response?.data?.message
        ?? error.message
      toast.error(message)
    },
  })

  const onSubmit = (data: DocumentFormData) => {
    // Ensure the type is set from context if not in form
    const submitData = {
      ...data,
      type: data.type || effectiveType || '',
      lines: lines.map((line) => ({
        product_id: line.product_id,
        description: line.description,
        quantity: line.quantity,
        unit_price: line.unit_price,
        tax_rate: line.tax_rate,
      })),
    }
    if (isEditing) {
      updateMutation.mutate(submitData as DocumentFormData)
    } else {
      createMutation.mutate(submitData as DocumentFormData)
    }
  }

  if (isEditing && isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-gray-500">{t('status.loading')}</div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link
          to={basePath}
          className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4" />
          {t('actions.back')}
        </Link>
        <h1 className="text-2xl font-bold text-gray-900">
          {isEditing ? `${t('actions.edit')} ${entityName}` : `${t('actions.add')} ${entityName}`}
        </h1>
      </div>

      {/* Form */}
      <form onSubmit={(e) => { void handleSubmit(onSubmit)(e) }} className="space-y-6">
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <div className="grid gap-6 sm:grid-cols-2">
            {/* Type - hidden if type is set from context */}
            {!effectiveType && (
              <div>
                <label
                  htmlFor="type"
                  className="block text-sm font-medium text-gray-700"
                >
                  Type *
                </label>
                <select
                  id="type"
                  {...register('type', { required: 'Type is required' })}
                  className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  disabled={isEditing}
                >
                  <option value="">Select type</option>
                  <option value="quote">Quote</option>
                  <option value="sales_order">Sales Order</option>
                  <option value="invoice">Invoice</option>
                  <option value="purchase_order">Purchase Order</option>
                  <option value="credit_note">Credit Note</option>
                  <option value="delivery_note">Delivery Note</option>
                </select>
                {errors.type && (
                  <p className="mt-1 text-sm text-red-600">{errors.type.message}</p>
                )}
              </div>
            )}

            {/* Partner */}
            <div>
              <label
                htmlFor="partner_id"
                className="block text-sm font-medium text-gray-700"
              >
                {partnerTypeFilter === 'customer' ? 'Customer' : partnerTypeFilter === 'supplier' ? 'Supplier' : 'Partner'} *
              </label>
              <div className="mt-1 flex gap-2">
                <select
                  id="partner_id"
                  {...register('partner_id', { required: `${partnerTypeFilter === 'customer' ? 'Customer' : partnerTypeFilter === 'supplier' ? 'Supplier' : 'Partner'} is required` })}
                  className="block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                  <option value="">Select {partnerTypeFilter === 'customer' ? 'customer' : partnerTypeFilter === 'supplier' ? 'supplier' : 'partner'}</option>
                  {partners.map((partner) => (
                    <option key={partner.id} value={partner.id}>
                      {partner.name}
                    </option>
                  ))}
                </select>
                <button
                  type="button"
                  onClick={() => { setShowPartnerModal(true) }}
                  className="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                  title={`Add new ${partnerTypeFilter === 'customer' ? 'customer' : partnerTypeFilter === 'supplier' ? 'supplier' : 'partner'}`}
                >
                  <Plus className="h-4 w-4" />
                </button>
              </div>
              {errors.partner_id && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.partner_id.message}
                </p>
              )}
            </div>

            {/* Issue Date */}
            <div>
              <label
                htmlFor="issue_date"
                className="block text-sm font-medium text-gray-700"
              >
                Issue Date *
              </label>
              <input
                type="date"
                id="issue_date"
                {...register('issue_date', { required: 'Issue date is required' })}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
              {errors.issue_date && (
                <p className="mt-1 text-sm text-red-600">
                  {errors.issue_date.message}
                </p>
              )}
            </div>

            {/* Due Date */}
            <div>
              <label
                htmlFor="due_date"
                className="block text-sm font-medium text-gray-700"
              >
                Due Date
              </label>
              <input
                type="date"
                id="due_date"
                {...register('due_date')}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
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
                rows={4}
                {...register('notes')}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="Additional notes..."
              />
            </div>
          </div>
        </div>

        {/* Document Lines */}
        <DocumentLineEditor lines={lines} onChange={setLines} />

        {/* Additional Costs (Purchase Orders only - after document is created) */}
        {effectiveType === 'purchase_order' && isEditing && id && (
          <PurchaseOrderAdditionalCosts
            documentId={id}
            disabled={document?.status !== 'draft'}
            currency="TND"
          />
        )}

        {/* Form Actions */}
        <div className="flex items-center justify-end gap-4">
          <Link
            to={basePath}
            className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          >
            {t('actions.cancel')}
          </Link>
          <button
            type="submit"
            disabled={isSubmitting || createMutation.isPending || updateMutation.isPending}
            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {(isSubmitting || createMutation.isPending || updateMutation.isPending) ? t('status.saving') : t('actions.save')}
          </button>
        </div>
      </form>

      {/* Add Partner Modal */}
      <AddPartnerModal
        isOpen={showPartnerModal}
        onClose={() => { setShowPartnerModal(false) }}
        partnerType={partnerTypeFilter}
        onSuccess={(partner) => {
          // Set the form value immediately
          setValue('partner_id', partner.id)
          // Invalidate partners query to refresh the dropdown with new partner
          void queryClient.invalidateQueries({ queryKey: ['partners'] })
        }}
      />
    </div>
  )
}
