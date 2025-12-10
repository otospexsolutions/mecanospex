import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { format } from 'date-fns'
import { ArrowLeft, FileCheck, Clock } from 'lucide-react'
import { useJournalEntry } from '../hooks/useJournalEntries'
import { usePostJournalEntry } from '../hooks/useJournalEntryMutations'
import type { JournalEntryStatus } from '../types'

function StatusBadge({ status }: { status: JournalEntryStatus }) {
  const { t } = useTranslation('finance')

  const statusConfig: Record<JournalEntryStatus, { styles: string; icon: React.ReactNode }> = {
    draft: {
      styles: 'bg-yellow-100 text-yellow-800',
      icon: <Clock className="h-3.5 w-3.5" />,
    },
    posted: {
      styles: 'bg-green-100 text-green-800',
      icon: <FileCheck className="h-3.5 w-3.5" />,
    },
  }

  const config = statusConfig[status]

  return (
    <span
      className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ${config.styles}`}
    >
      {config.icon}
      {t(`journalEntry.status.${status}`)}
    </span>
  )
}

export function JournalEntryDetailPage() {
  const { t } = useTranslation(['finance', 'common'])
  const { id } = useParams<{ id: string }>()
  const { data: entry, isLoading } = useJournalEntry(id)
  const postMutation = usePostJournalEntry()

  const handlePost = () => {
    if (id) {
      postMutation.mutate(id)
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <p className="text-gray-500">{t('common:status.loading')}</p>
      </div>
    )
  }

  if (!entry) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px]">
        <p className="text-gray-500 mb-4">{t('finance:journalEntry.notFound')}</p>
        <Link
          to="/finance/journal-entries"
          className="text-blue-600 hover:text-blue-700"
        >
          {t('finance:journalEntry.backToList')}
        </Link>
      </div>
    )
  }

  const totalDebits = entry.lines.reduce(
    (sum, line) => sum + parseFloat(line.debit),
    0
  )
  const totalCredits = entry.lines.reduce(
    (sum, line) => sum + parseFloat(line.credit),
    0
  )

  const formatCurrency = (amount: number | string) => {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount
    return new Intl.NumberFormat('en-US', {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(num)
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link
            to="/finance/journal-entries"
            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
          >
            <ArrowLeft className="h-4 w-4 me-1" />
            {t('common:back')}
          </Link>
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-bold text-gray-900">
                {entry.entry_number}
              </h1>
              <StatusBadge status={entry.status} />
            </div>
            <p className="text-gray-500">
              {entry.description || t('finance:journalEntry.noDescription')}
            </p>
          </div>
        </div>

        {entry.status === 'draft' && (
          <button
            type="button"
            onClick={handlePost}
            disabled={postMutation.isPending}
            className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
          >
            <FileCheck className="h-4 w-4" />
            {postMutation.isPending
              ? t('common:status.processing')
              : t('finance:journalEntry.post')}
          </button>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h3 className="text-sm font-medium text-gray-500 mb-1">
            {t('finance:journalEntry.entryDate')}
          </h3>
          <p className="text-lg font-semibold text-gray-900">
            {format(new Date(entry.entry_date), 'MMMM d, yyyy')}
          </p>
        </div>
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h3 className="text-sm font-medium text-gray-500 mb-1">
            {t('common:fields.created')}
          </h3>
          <p className="text-lg font-semibold text-gray-900">
            {format(new Date(entry.created_at), 'MMMM d, yyyy h:mm a')}
          </p>
        </div>
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h3 className="text-sm font-medium text-gray-500 mb-1">
            {t('finance:journalEntry.linesCount')}
          </h3>
          <p className="text-lg font-semibold text-gray-900">
            {entry.lines.length} {t('finance:journalEntry.lines')}
          </p>
        </div>
      </div>

      <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">
            {t('finance:journalEntry.lines')}
          </h2>
        </div>
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('finance:journalEntry.account')}
              </th>
              <th className="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('finance:journalEntry.debit')}
              </th>
              <th className="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('finance:journalEntry.credit')}
              </th>
              <th className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                {t('finance:journalEntry.lineDescription')}
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {entry.lines.map((line) => (
              <tr key={line.id} className="hover:bg-gray-50">
                <td className="px-6 py-4">
                  <div>
                    <span className="font-mono text-sm text-gray-500">
                      {line.account_code}
                    </span>
                    <span className="ms-2 text-sm text-gray-900">
                      {line.account_name}
                    </span>
                  </div>
                </td>
                <td className="px-6 py-4 text-end text-sm">
                  {parseFloat(line.debit) > 0 ? (
                    <span className="font-medium text-gray-900">
                      {formatCurrency(line.debit)}
                    </span>
                  ) : (
                    <span className="text-gray-400">-</span>
                  )}
                </td>
                <td className="px-6 py-4 text-end text-sm">
                  {parseFloat(line.credit) > 0 ? (
                    <span className="font-medium text-gray-900">
                      {formatCurrency(line.credit)}
                    </span>
                  ) : (
                    <span className="text-gray-400">-</span>
                  )}
                </td>
                <td className="px-6 py-4 text-sm text-gray-500">
                  {line.description || '-'}
                </td>
              </tr>
            ))}
          </tbody>
          <tfoot className="bg-gray-50">
            <tr className="font-medium">
              <td className="px-6 py-4 text-sm text-gray-900">
                {t('finance:journalEntry.totals')}
              </td>
              <td className="px-6 py-4 text-end text-sm text-gray-900">
                {formatCurrency(totalDebits)}
              </td>
              <td className="px-6 py-4 text-end text-sm text-gray-900">
                {formatCurrency(totalCredits)}
              </td>
              <td className="px-6 py-4"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      {postMutation.error && (
        <div className="rounded-lg bg-red-50 p-4 text-sm text-red-700">
          {t('finance:journalEntry.postError')}
        </div>
      )}

      {postMutation.isSuccess && (
        <div className="rounded-lg bg-green-50 p-4 text-sm text-green-700">
          {t('finance:journalEntry.postSuccess')}
        </div>
      )}
    </div>
  )
}
