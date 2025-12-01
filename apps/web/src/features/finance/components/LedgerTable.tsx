import type { LedgerLine } from '../types'

interface LedgerTableProps {
  lines: LedgerLine[]
}

export function LedgerTable({ lines }: LedgerTableProps) {
  if (lines.length === 0) {
    return (
      <div className="px-6 py-12 text-center text-sm text-gray-500">
        No ledger entries found. Adjust your filters or create journal entries.
      </div>
    )
  }

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date
            </th>
            <th className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
              Entry #
            </th>
            <th className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
              Account
            </th>
            <th className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
              Description
            </th>
            <th className="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
              Debit
            </th>
            <th className="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
              Credit
            </th>
            <th className="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
              Balance
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {lines.map((line) => (
            <tr key={line.id} className="hover:bg-gray-50">
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {new Date(line.date).toLocaleDateString()}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {line.entry_number}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div>
                  <div className="font-medium">{line.account_code}</div>
                  <div className="text-gray-500">{line.account_name}</div>
                </div>
              </td>
              <td className="px-6 py-4 text-sm text-gray-900">
                {line.description}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-end font-mono">
                {line.debit !== '0.00' && line.debit !== '0' ? `$${line.debit}` : ''}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-end font-mono">
                {line.credit !== '0.00' && line.credit !== '0' ? `$${line.credit}` : ''}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-end font-mono">
                ${line.balance}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
