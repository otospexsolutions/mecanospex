import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { FileCheck, Calendar } from 'lucide-react'
import { api } from '../../lib/api'

interface Instrument {
  id: string
  instrument_number: string
  type: 'check' | 'promissory_note' | 'voucher'
  amount: number
  issue_date: string
  maturity_date: string | null
  partner_id: string
  partner_name: string
  status: 'received' | 'deposited' | 'cleared' | 'bounced' | 'cancelled'
  repository_id: string
  repository_name: string
  created_at: string
}

interface InstrumentsResponse {
  data: Instrument[]
  meta?: { total: number }
}

const statusColors: Record<Instrument['status'], string> = {
  received: 'bg-yellow-100 text-yellow-800',
  deposited: 'bg-blue-100 text-blue-800',
  cleared: 'bg-green-100 text-green-800',
  bounced: 'bg-red-100 text-red-800',
  cancelled: 'bg-gray-100 text-gray-800',
}

const statusLabels: Record<Instrument['status'], string> = {
  received: 'Received',
  deposited: 'Deposited',
  cleared: 'Cleared',
  bounced: 'Bounced',
  cancelled: 'Cancelled',
}

const typeLabels: Record<Instrument['type'], string> = {
  check: 'Check',
  promissory_note: 'Promissory Note',
  voucher: 'Voucher',
}

export function InstrumentListPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['instruments'],
    queryFn: async () => {
      const response = await api.get<InstrumentsResponse>('/payment-instruments')
      return response.data
    },
  })

  const instruments = data?.data ?? []
  const total = data?.meta?.total ?? instruments.length

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Payment Instruments</h1>
          <p className="text-gray-500">
            {total} {total === 1 ? 'instrument' : 'instruments'} total
          </p>
        </div>
      </div>

      {/* Content */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <div className="text-gray-500">Loading...</div>
        </div>
      ) : error ? (
        <div className="rounded-lg bg-red-50 p-4 text-red-700">
          Error loading instruments. Please try again.
        </div>
      ) : instruments.length === 0 ? (
        <div className="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
          <FileCheck className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-semibold text-gray-900">
            No instruments
          </h3>
          <p className="mt-1 text-sm text-gray-500">
            Payment instruments like checks will appear here.
          </p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Number
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Type
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Partner
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Maturity
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Location
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Status
                </th>
                <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                  Amount
                </th>
                <th className="relative px-6 py-3">
                  <span className="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 bg-white">
              {instruments.map((instrument) => (
                <tr key={instrument.id} className="hover:bg-gray-50">
                  <td className="whitespace-nowrap px-6 py-4">
                    <Link
                      to={`/treasury/instruments/${instrument.id}`}
                      className="font-medium text-gray-900 hover:text-blue-600"
                    >
                      {instrument.instrument_number}
                    </Link>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                    {typeLabels[instrument.type]}
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                    {instrument.partner_name}
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                    {instrument.maturity_date ? (
                      <div className="flex items-center gap-1">
                        <Calendar className="h-3.5 w-3.5" />
                        {new Date(instrument.maturity_date).toLocaleDateString()}
                      </div>
                    ) : (
                      '-'
                    )}
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                    {instrument.repository_name}
                  </td>
                  <td className="whitespace-nowrap px-6 py-4">
                    <span
                      className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColors[instrument.status]}`}
                    >
                      {statusLabels[instrument.status]}
                    </span>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-end text-sm font-medium text-gray-900">
                    {formatCurrency(instrument.amount)}
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-end text-sm">
                    <Link
                      to={`/treasury/instruments/${instrument.id}`}
                      className="text-blue-600 hover:text-blue-900"
                    >
                      View
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
