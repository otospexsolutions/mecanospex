import { useTranslation } from 'react-i18next'
import type { CountingStatus } from '../types'
import { cn } from '@/lib/utils'

interface Props {
  status: CountingStatus
  className?: string
}

const statusColors: Record<CountingStatus, string> = {
  draft: 'bg-gray-100 text-gray-700',
  scheduled: 'bg-blue-100 text-blue-700',
  count_1_in_progress: 'bg-yellow-100 text-yellow-800',
  count_1_completed: 'bg-yellow-200 text-yellow-900',
  count_2_in_progress: 'bg-orange-100 text-orange-800',
  count_2_completed: 'bg-orange-200 text-orange-900',
  count_3_in_progress: 'bg-purple-100 text-purple-800',
  count_3_completed: 'bg-purple-200 text-purple-900',
  pending_review: 'bg-amber-100 text-amber-800',
  finalized: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-700',
}

export function CountingStatusBadge({ status, className }: Props) {
  const { t } = useTranslation('inventory')

  return (
    <span
      className={cn(
        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
        statusColors[status],
        className
      )}
    >
      {t(`counting.status.${status}`)}
    </span>
  )
}
