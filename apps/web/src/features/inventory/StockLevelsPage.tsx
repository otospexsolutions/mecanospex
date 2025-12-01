import { useState, useMemo } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { Package, AlertTriangle, MapPin, Plus, Minus, RefreshCw, X } from 'lucide-react'
import { api, apiPost } from '../../lib/api'
import { SearchInput } from '../../components/ui/SearchInput'
import { FilterTabs } from '../../components/ui/FilterTabs'
import { LocationSelector } from '../location/LocationSelector'
import { useLocation } from '../../hooks/useLocation'

interface StockLevel {
  id: string
  product_id: string
  product_name: string | null
  location_id: string
  location_name: string | null
  quantity: number
  reserved: number
  available: number
  min_quantity: number | null
  max_quantity: number | null
}

interface StockLevelsResponse {
  data: StockLevel[]
  meta?: {
    total: number
    current_page: number
    per_page: number
    last_page: number
  }
}

interface StockMovement {
  id: string
  product_id: string
  product_name: string
  location_id: string
  location_name: string
  movement_type: string
  quantity: string
  quantity_before: string
  quantity_after: string
  reference: string
  notes: string | null
  user_name: string | null
  created_at: string
}

type StockFilter = 'all' | 'low' | 'out'
type AdjustmentType = 'adjust' | 'receive' | 'issue'

const adjustmentReasons = [
  { value: 'inventory_count', label: 'Inventory Count' },
  { value: 'damage', label: 'Damage/Loss' },
  { value: 'correction', label: 'Correction' },
  { value: 'other', label: 'Other' },
]

export function StockLevelsPage() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const { currentLocationId } = useLocation()
  const [searchQuery, setSearchQuery] = useState('')
  const [stockFilter, setStockFilter] = useState<StockFilter>('all')
  const [selectedStock, setSelectedStock] = useState<StockLevel | null>(null)
  const [adjustmentType, setAdjustmentType] = useState<AdjustmentType>('adjust')
  const [adjustmentQuantity, setAdjustmentQuantity] = useState('')
  const [adjustmentReason, setAdjustmentReason] = useState('inventory_count')
  const [adjustmentNotes, setAdjustmentNotes] = useState('')

  const { data, isLoading, error } = useQuery({
    queryKey: ['stock-levels', searchQuery, currentLocationId],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (searchQuery) params.append('search', searchQuery)
      if (currentLocationId) params.append('location_id', currentLocationId)
      const queryString = params.toString()
      const response = await api.get<StockLevelsResponse>(`/stock-levels${queryString ? `?${queryString}` : ''}`)
      return response.data
    },
  })

  const adjustMutation = useMutation({
    mutationFn: async (data: { product_id: string; location_id: string; new_quantity: string; reason: string }) => {
      return apiPost<StockMovement>('/stock-movements/adjust', data)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      closeModal()
    },
  })

  const receiveMutation = useMutation({
    mutationFn: async (data: { product_id: string; location_id: string; quantity: string; reference: string; notes?: string }) => {
      return apiPost<StockMovement>('/stock-movements/receive', data)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      closeModal()
    },
  })

  const issueMutation = useMutation({
    mutationFn: async (data: { product_id: string; location_id: string; quantity: string; reference: string; notes?: string }) => {
      return apiPost<StockMovement>('/stock-movements/issue', data)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      closeModal()
    },
  })

  const stockLevels = useMemo(() => data?.data ?? [], [data?.data])
  const total = data?.meta?.total ?? stockLevels.length

  const filteredStockLevels = useMemo(() => {
    return stockLevels.filter((stock) => {
      if (stockFilter === 'low') {
        return stock.min_quantity != null && stock.available <= stock.min_quantity && stock.available > 0
      }
      if (stockFilter === 'out') {
        return stock.available <= 0
      }
      return true
    })
  }, [stockLevels, stockFilter])

  const filterTabs = useMemo(() => {
    const lowStockCount = stockLevels.filter(
      (s) => s.min_quantity != null && s.available <= s.min_quantity && s.available > 0
    ).length
    const outOfStockCount = stockLevels.filter((s) => s.available <= 0).length

    return [
      { value: 'all' as StockFilter, label: t('filters.all'), count: total },
      { value: 'low' as StockFilter, label: 'Low Stock', count: lowStockCount },
      { value: 'out' as StockFilter, label: 'Out of Stock', count: outOfStockCount },
    ]
  }, [t, total, stockLevels])

  const getStockStatus = (stock: StockLevel): { label: string; color: string } => {
    if (stock.available <= 0) {
      return { label: 'Out of Stock', color: 'bg-red-100 text-red-800' }
    }
    if (stock.min_quantity != null && stock.available <= stock.min_quantity) {
      return { label: 'Low Stock', color: 'bg-yellow-100 text-yellow-800' }
    }
    return { label: 'In Stock', color: 'bg-green-100 text-green-800' }
  }

  const openAdjustModal = (stock: StockLevel, type: AdjustmentType) => {
    setSelectedStock(stock)
    setAdjustmentType(type)
    setAdjustmentQuantity(type === 'adjust' ? String(stock.quantity) : '')
    setAdjustmentReason('inventory_count')
    setAdjustmentNotes('')
  }

  const closeModal = () => {
    setSelectedStock(null)
    setAdjustmentQuantity('')
    setAdjustmentReason('inventory_count')
    setAdjustmentNotes('')
  }

  const handleSubmit = () => {
    if (!selectedStock) return

    const reasonLabel = adjustmentReasons.find(r => r.value === adjustmentReason)?.label ?? adjustmentReason
    const reference = adjustmentNotes ? `${reasonLabel}: ${adjustmentNotes}` : reasonLabel

    if (adjustmentType === 'adjust') {
      adjustMutation.mutate({
        product_id: selectedStock.product_id,
        location_id: selectedStock.location_id,
        new_quantity: adjustmentQuantity,
        reason: reference,
      })
    } else if (adjustmentType === 'receive') {
      const receiveData: { product_id: string; location_id: string; quantity: string; reference: string; notes?: string } = {
        product_id: selectedStock.product_id,
        location_id: selectedStock.location_id,
        quantity: adjustmentQuantity,
        reference: reference,
      }
      if (adjustmentNotes) {
        receiveData.notes = adjustmentNotes
      }
      receiveMutation.mutate(receiveData)
    } else {
      const issueData: { product_id: string; location_id: string; quantity: string; reference: string; notes?: string } = {
        product_id: selectedStock.product_id,
        location_id: selectedStock.location_id,
        quantity: adjustmentQuantity,
        reference: reference,
      }
      if (adjustmentNotes) {
        issueData.notes = adjustmentNotes
      }
      issueMutation.mutate(issueData)
    }
  }

  const isSubmitting = adjustMutation.isPending || receiveMutation.isPending || issueMutation.isPending
  const mutationError = adjustMutation.error ?? receiveMutation.error ?? issueMutation.error

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('navigation.stockLevels', 'Stock Levels')}</h1>
          <p className="text-gray-500">
            {total} product{total !== 1 ? 's' : ''} in stock
          </p>
        </div>
        <div className="flex items-center gap-3">
          <LocationSelector />
          <Link
            to="/inventory/movements"
            className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <RefreshCw className="h-4 w-4" />
            View Movements
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <FilterTabs tabs={filterTabs} value={stockFilter} onChange={setStockFilter} />
        <SearchInput
          value={searchQuery}
          onChange={setSearchQuery}
          placeholder={`${t('actions.search')} products...`}
          className="w-full sm:w-72"
        />
      </div>

      {/* Content */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <div className="text-gray-500">{t('status.loading')}</div>
        </div>
      ) : error ? (
        <div className="rounded-lg bg-red-50 p-4 text-red-700">
          {t('errors.loadingFailed', 'Error loading data. Please try again.')}
        </div>
      ) : filteredStockLevels.length === 0 ? (
        <div className="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
          <Package className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-semibold text-gray-900">
            {searchQuery || stockFilter !== 'all' ? t('status.noResults') : 'No stock data'}
          </h3>
          <p className="mt-1 text-sm text-gray-500">
            {searchQuery
              ? t('status.tryDifferentSearch', 'Try a different search term.')
              : stockFilter !== 'all'
                ? 'No products match the selected filter.'
                : 'Stock levels will appear here once products are added.'}
          </p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Product
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Location
                </th>
                <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                  Quantity
                </th>
                <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                  Reserved
                </th>
                <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                  Available
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  {t('fields.status', 'Status')}
                </th>
                <th className="relative px-6 py-3">
                  <span className="sr-only">{t('actions.actions')}</span>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 bg-white">
              {filteredStockLevels.map((stock) => {
                const status = getStockStatus(stock)
                const isLowOrOut = stock.available <= 0 || (stock.min_quantity != null && stock.available <= stock.min_quantity)

                return (
                  <tr key={stock.id} className={`hover:bg-gray-50 ${isLowOrOut ? 'bg-red-50/30' : ''}`}>
                    <td className="whitespace-nowrap px-6 py-4">
                      <Link
                        to={`/inventory/products/${stock.product_id}`}
                        className="font-medium text-gray-900 hover:text-blue-600"
                      >
                        {stock.product_name ?? 'Unknown Product'}
                      </Link>
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                      <div className="flex items-center gap-1">
                        <MapPin className="h-3.5 w-3.5 text-gray-400" />
                        {stock.location_name ?? 'Default'}
                      </div>
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-end text-sm text-gray-900">
                      {stock.quantity}
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-end text-sm text-gray-500">
                      {stock.reserved}
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-end">
                      <span className={`text-sm font-semibold ${isLowOrOut ? 'text-red-600' : 'text-gray-900'}`}>
                        {stock.available}
                      </span>
                      {stock.min_quantity != null && (
                        <span className="ml-1 text-xs text-gray-400">
                          / min {stock.min_quantity}
                        </span>
                      )}
                    </td>
                    <td className="whitespace-nowrap px-6 py-4">
                      <div className="flex items-center gap-2">
                        {isLowOrOut && <AlertTriangle className="h-4 w-4 text-yellow-500" />}
                        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${status.color}`}>
                          {status.label}
                        </span>
                      </div>
                    </td>
                    <td className="whitespace-nowrap px-6 py-4 text-end text-sm">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => { openAdjustModal(stock, 'receive') }}
                          className="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-50"
                          title="Receive stock"
                        >
                          <Plus className="h-3.5 w-3.5" />
                          In
                        </button>
                        <button
                          onClick={() => { openAdjustModal(stock, 'issue') }}
                          className="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50"
                          title="Issue stock"
                        >
                          <Minus className="h-3.5 w-3.5" />
                          Out
                        </button>
                        <button
                          onClick={() => { openAdjustModal(stock, 'adjust') }}
                          className="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50"
                          title="Adjust stock"
                        >
                          <RefreshCw className="h-3.5 w-3.5" />
                          Adjust
                        </button>
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* Summary Cards */}
      {stockLevels.length > 0 && (
        <div className="grid gap-4 sm:grid-cols-3">
          <div className="rounded-lg border border-gray-200 bg-white p-4">
            <div className="text-sm font-medium text-gray-500">Total Products</div>
            <div className="mt-1 text-2xl font-bold text-gray-900">{total}</div>
          </div>
          <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
            <div className="text-sm font-medium text-yellow-700">Low Stock</div>
            <div className="mt-1 text-2xl font-bold text-yellow-900">
              {stockLevels.filter((s) => s.min_quantity != null && s.available <= s.min_quantity && s.available > 0).length}
            </div>
          </div>
          <div className="rounded-lg border border-red-200 bg-red-50 p-4">
            <div className="text-sm font-medium text-red-700">Out of Stock</div>
            <div className="mt-1 text-2xl font-bold text-red-900">
              {stockLevels.filter((s) => s.available <= 0).length}
            </div>
          </div>
        </div>
      )}

      {/* Adjustment Modal */}
      {selectedStock && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-gray-900">
                {adjustmentType === 'adjust' && 'Adjust Stock'}
                {adjustmentType === 'receive' && 'Receive Stock'}
                {adjustmentType === 'issue' && 'Issue Stock'}
              </h2>
              <button
                onClick={closeModal}
                className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">Product</label>
                <p className="mt-1 text-sm text-gray-900">{selectedStock.product_name}</p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">Location</label>
                <p className="mt-1 text-sm text-gray-900">{selectedStock.location_name}</p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">Current Quantity</label>
                <p className="mt-1 text-sm text-gray-900">{selectedStock.quantity}</p>
              </div>

              <div>
                <label htmlFor="quantity" className="block text-sm font-medium text-gray-700">
                  {adjustmentType === 'adjust' ? 'New Quantity' : 'Quantity'}
                </label>
                <input
                  type="number"
                  id="quantity"
                  value={adjustmentQuantity}
                  onChange={(e) => { setAdjustmentQuantity(e.target.value) }}
                  min="0"
                  step="0.01"
                  className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  placeholder={adjustmentType === 'adjust' ? 'Enter new quantity' : 'Enter quantity'}
                />
                {adjustmentType === 'adjust' && adjustmentQuantity && (
                  <p className="mt-1 text-sm text-gray-500">
                    Change: {Number(adjustmentQuantity) - selectedStock.quantity >= 0 ? '+' : ''}
                    {(Number(adjustmentQuantity) - selectedStock.quantity).toFixed(2)}
                  </p>
                )}
              </div>

              <div>
                <label htmlFor="reason" className="block text-sm font-medium text-gray-700">
                  Reason
                </label>
                <select
                  id="reason"
                  value={adjustmentReason}
                  onChange={(e) => { setAdjustmentReason(e.target.value) }}
                  className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                  {adjustmentReasons.map((reason) => (
                    <option key={reason.value} value={reason.value}>
                      {reason.label}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label htmlFor="notes" className="block text-sm font-medium text-gray-700">
                  Notes (optional)
                </label>
                <textarea
                  id="notes"
                  value={adjustmentNotes}
                  onChange={(e) => { setAdjustmentNotes(e.target.value) }}
                  rows={2}
                  className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  placeholder="Add any additional notes..."
                />
              </div>

              {mutationError != null && (
                <div className="rounded-lg bg-red-50 p-3 text-sm text-red-700">
                  {mutationError instanceof Error ? mutationError.message : 'An error occurred. Please try again.'}
                </div>
              )}

              <div className="flex items-center justify-end gap-3 pt-4">
                <button
                  type="button"
                  onClick={closeModal}
                  className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleSubmit}
                  disabled={isSubmitting || !adjustmentQuantity}
                  className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                  {isSubmitting ? 'Saving...' : 'Save'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
