import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import {
  BarChart3,
  TrendingUp,
  TrendingDown,
  DollarSign,
  FileText,
  Package,
  Users,
  CreditCard,
  AlertCircle
} from 'lucide-react'
import { api } from '../../lib/api'

interface Document {
  id: string
  type: string
  status: string
  total_amount: string
  created_at: string
}

interface DocumentsResponse {
  data: Document[]
  meta?: { total: number }
}

interface Partner {
  id: string
  name: string
  partner_types?: string[]
}

interface PartnersResponse {
  data: Partner[]
  meta?: { total: number }
}

interface Product {
  id: string
  name: string
  quantity_on_hand: number
  reorder_point: number | null
}

interface ProductsResponse {
  data: Product[]
  meta?: { total: number }
}

interface Payment {
  id: string
  amount: string
  status: string
  payment_date: string
}

interface PaymentsResponse {
  data: Payment[]
  meta?: { total: number }
}

export function ReportsPage() {
  const { t } = useTranslation()

  // Fetch documents
  const { data: documentsData, isLoading: loadingDocs } = useQuery({
    queryKey: ['documents'],
    queryFn: async () => {
      const response = await api.get<DocumentsResponse>('/documents')
      return response.data
    },
  })

  // Fetch partners
  const { data: partnersData, isLoading: loadingPartners } = useQuery({
    queryKey: ['partners'],
    queryFn: async () => {
      const response = await api.get<PartnersResponse>('/partners')
      return response.data
    },
  })

  // Fetch products
  const { data: productsData, isLoading: loadingProducts } = useQuery({
    queryKey: ['products'],
    queryFn: async () => {
      const response = await api.get<ProductsResponse>('/products')
      return response.data
    },
  })

  // Fetch payments
  const { data: paymentsData, isLoading: loadingPayments } = useQuery({
    queryKey: ['payments'],
    queryFn: async () => {
      const response = await api.get<PaymentsResponse>('/payments')
      return response.data
    },
  })

  const isLoading = loadingDocs || loadingPartners || loadingProducts || loadingPayments

  const documents = documentsData?.data ?? []
  const partners = partnersData?.data ?? []
  const products = productsData?.data ?? []
  const payments = paymentsData?.data ?? []

  // Calculate metrics
  const invoices = documents.filter(d => d.type === 'invoice')
  const quotes = documents.filter(d => d.type === 'quote')
  const salesOrders = documents.filter(d => d.type === 'sales_order')

  const totalInvoiced = invoices.reduce((sum, inv) => sum + parseFloat(inv.total_amount), 0)
  const totalQuoted = quotes.reduce((sum, q) => sum + parseFloat(q.total_amount), 0)

  const completedPayments = payments.filter(p => p.status === 'completed')
  const totalCollected = completedPayments.reduce((sum, p) => sum + parseFloat(p.amount), 0)

  const customers = partners.filter(p => p.partner_types?.includes('customer'))
  const suppliers = partners.filter(p => p.partner_types?.includes('supplier'))

  const lowStockProducts = products.filter(p => {
    if (p.reorder_point === null) return false
    return p.quantity_on_hand <= p.reorder_point
  })

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">{t('navigation.reports', 'Reports')}</h1>
        <p className="text-gray-500">Overview of your business performance</p>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <div className="text-gray-500">{t('status.loading')}</div>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Revenue Summary */}
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <MetricCard
              title="Total Invoiced"
              value={formatCurrency(totalInvoiced)}
              icon={<DollarSign className="h-5 w-5" />}
              color="blue"
              subtitle={`${String(invoices.length)} invoices`}
            />
            <MetricCard
              title="Total Collected"
              value={formatCurrency(totalCollected)}
              icon={<CreditCard className="h-5 w-5" />}
              color="green"
              subtitle={`${String(completedPayments.length)} payments`}
            />
            <MetricCard
              title="Outstanding"
              value={formatCurrency(Math.max(0, totalInvoiced - totalCollected))}
              icon={totalInvoiced > totalCollected ? <TrendingDown className="h-5 w-5" /> : <TrendingUp className="h-5 w-5" />}
              color={totalInvoiced > totalCollected ? 'yellow' : 'green'}
              subtitle="To collect"
            />
            <MetricCard
              title="Quotes Pending"
              value={formatCurrency(totalQuoted)}
              icon={<FileText className="h-5 w-5" />}
              color="purple"
              subtitle={`${String(quotes.length)} quotes`}
            />
          </div>

          {/* Document Summary */}
          <div className="rounded-lg border border-gray-200 bg-white p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <BarChart3 className="h-5 w-5 text-gray-400" />
              Document Summary
            </h2>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <SummaryItem label="Quotes" value={quotes.length} color="purple" />
              <SummaryItem label="Sales Orders" value={salesOrders.length} color="blue" />
              <SummaryItem label="Invoices" value={invoices.length} color="green" />
              <SummaryItem label="Total Documents" value={documents.length} color="gray" />
            </div>
          </div>

          {/* Partners & Inventory */}
          <div className="grid gap-6 lg:grid-cols-2">
            {/* Partner Summary */}
            <div className="rounded-lg border border-gray-200 bg-white p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <Users className="h-5 w-5 text-gray-400" />
                Partners
              </h2>
              <div className="grid gap-4 sm:grid-cols-2">
                <SummaryItem label="Customers" value={customers.length} color="blue" />
                <SummaryItem label="Suppliers" value={suppliers.length} color="orange" />
              </div>
            </div>

            {/* Inventory Summary */}
            <div className="rounded-lg border border-gray-200 bg-white p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <Package className="h-5 w-5 text-gray-400" />
                Inventory
              </h2>
              <div className="grid gap-4 sm:grid-cols-2">
                <SummaryItem label="Total Products" value={products.length} color="green" />
                <SummaryItem
                  label="Low Stock"
                  value={lowStockProducts.length}
                  color={lowStockProducts.length > 0 ? 'red' : 'green'}
                />
              </div>
            </div>
          </div>

          {/* Low Stock Alert */}
          {lowStockProducts.length > 0 && (
            <div className="rounded-lg border border-orange-200 bg-orange-50 p-6">
              <h2 className="text-lg font-semibold text-orange-900 mb-4 flex items-center gap-2">
                <AlertCircle className="h-5 w-5 text-orange-600" />
                Low Stock Alert
              </h2>
              <div className="overflow-x-auto">
                <table className="min-w-full">
                  <thead>
                    <tr>
                      <th className="text-start text-xs font-medium uppercase tracking-wider text-orange-700 pb-2">
                        Product
                      </th>
                      <th className="text-end text-xs font-medium uppercase tracking-wider text-orange-700 pb-2">
                        Current Stock
                      </th>
                      <th className="text-end text-xs font-medium uppercase tracking-wider text-orange-700 pb-2">
                        Reorder Point
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {lowStockProducts.slice(0, 5).map(product => (
                      <tr key={product.id}>
                        <td className="py-2 text-sm text-orange-800">{product.name}</td>
                        <td className="py-2 text-sm text-orange-800 text-end font-medium">
                          {product.quantity_on_hand}
                        </td>
                        <td className="py-2 text-sm text-orange-600 text-end">
                          {product.reorder_point}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {lowStockProducts.length > 5 && (
                  <p className="mt-2 text-sm text-orange-600">
                    And {lowStockProducts.length - 5} more products...
                  </p>
                )}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  )
}

function MetricCard({
  title,
  value,
  icon,
  color,
  subtitle,
}: {
  title: string
  value: string
  icon: React.ReactNode
  color: 'blue' | 'green' | 'yellow' | 'purple' | 'red' | 'orange' | 'gray'
  subtitle: string
}) {
  const colorClasses: Record<typeof color, { bg: string; text: string }> = {
    blue: { bg: 'bg-blue-100', text: 'text-blue-600' },
    green: { bg: 'bg-green-100', text: 'text-green-600' },
    yellow: { bg: 'bg-yellow-100', text: 'text-yellow-600' },
    purple: { bg: 'bg-purple-100', text: 'text-purple-600' },
    red: { bg: 'bg-red-100', text: 'text-red-600' },
    orange: { bg: 'bg-orange-100', text: 'text-orange-600' },
    gray: { bg: 'bg-gray-100', text: 'text-gray-600' },
  }

  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4">
      <div className="flex items-center gap-3">
        <div className={`rounded-lg p-2 ${colorClasses[color].bg} ${colorClasses[color].text}`}>
          {icon}
        </div>
        <div className="flex-1">
          <p className="text-sm text-gray-500">{title}</p>
          <p className="text-xl font-semibold text-gray-900">{value}</p>
          <p className="text-xs text-gray-400">{subtitle}</p>
        </div>
      </div>
    </div>
  )
}

function SummaryItem({
  label,
  value,
  color,
}: {
  label: string
  value: number
  color: 'blue' | 'green' | 'yellow' | 'purple' | 'red' | 'orange' | 'gray'
}) {
  const colorClasses: Record<typeof color, string> = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    yellow: 'text-yellow-600',
    purple: 'text-purple-600',
    red: 'text-red-600',
    orange: 'text-orange-600',
    gray: 'text-gray-600',
  }

  return (
    <div className="flex items-center justify-between rounded-lg bg-gray-50 p-3">
      <span className="text-sm text-gray-600">{label}</span>
      <span className={`text-lg font-semibold ${colorClasses[color]}`}>{value}</span>
    </div>
  )
}
