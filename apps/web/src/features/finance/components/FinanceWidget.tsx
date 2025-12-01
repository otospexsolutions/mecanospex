import { Link } from 'react-router-dom'
import { useFinanceSummary } from '../hooks/useFinanceSummary'
import { formatCurrency } from '@/lib/format'

export function FinanceWidget() {
  const { data, isLoading } = useFinanceSummary()

  if (isLoading) {
    return (
      <div className="rounded-lg border border-gray-200 bg-white p-6">
        <h3 className="mb-4 text-lg font-semibold">Finance Overview</h3>
        <div>Loading...</div>
      </div>
    )
  }

  return (
    <div className="rounded-lg border border-gray-200 bg-white p-6">
      <div className="mb-4 flex items-center justify-between">
        <h3 className="text-lg font-semibold">Finance Overview</h3>
        <Link
          to="/finance/balance-sheet"
          className="text-sm text-blue-600 hover:text-blue-700"
        >
          View Reports →
        </Link>
      </div>

      <div className="grid grid-cols-2 gap-4">
        {/* Total Assets */}
        <div className="rounded border border-gray-100 p-4">
          <div className="text-sm text-gray-500">Total Assets</div>
          <div className="mt-1 text-2xl font-bold text-gray-900">
            {formatCurrency(data?.total_assets || '0')}
          </div>
        </div>

        {/* Total Liabilities */}
        <div className="rounded border border-gray-100 p-4">
          <div className="text-sm text-gray-500">Total Liabilities</div>
          <div className="mt-1 text-2xl font-bold text-gray-900">
            {formatCurrency(data?.total_liabilities || '0')}
          </div>
        </div>

        {/* Net Income MTD */}
        <div className="rounded border border-gray-100 p-4">
          <div className="text-sm text-gray-500">Net Income (MTD)</div>
          <div
            className={`mt-1 text-2xl font-bold ${
              parseFloat(data?.net_income_mtd || '0') >= 0
                ? 'text-green-600'
                : 'text-red-600'
            }`}
          >
            {formatCurrency(data?.net_income_mtd || '0')}
          </div>
        </div>

        {/* Net Income YTD */}
        <div className="rounded border border-gray-100 p-4">
          <div className="text-sm text-gray-500">Net Income (YTD)</div>
          <div
            className={`mt-1 text-2xl font-bold ${
              parseFloat(data?.net_income_ytd || '0') >= 0
                ? 'text-green-600'
                : 'text-red-600'
            }`}
          >
            {formatCurrency(data?.net_income_ytd || '0')}
          </div>
        </div>

        {/* Accounts Receivable */}
        <div className="rounded border border-gray-100 p-4">
          <div className="text-sm text-gray-500">Accounts Receivable</div>
          <div className="mt-1 text-2xl font-bold text-blue-600">
            {formatCurrency(data?.accounts_receivable || '0')}
          </div>
        </div>

        {/* Accounts Payable */}
        <div className="rounded border border-gray-100 p-4">
          <div className="text-sm text-gray-500">Accounts Payable</div>
          <div className="mt-1 text-2xl font-bold text-orange-600">
            {formatCurrency(data?.accounts_payable || '0')}
          </div>
        </div>
      </div>
    </div>
  )
}
