import { Link } from 'react-router-dom';
import { useCountingDashboard } from '../api/queries';
import { Plus, Activity, Clock, CheckCircle, AlertTriangle } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export function CountingDashboardPage() {
  const { t } = useTranslation(['inventory']);
  const { data, isLoading, error } = useCountingDashboard();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-gray-500">{t('common:loading')}</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-red-600">{t('common:error')}</div>
      </div>
    );
  }

  if (!data) {
    return null;
  }

  const { summary } = data;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('inventory:counting.title')}</h1>
          <p className="text-gray-600">
            {t('inventory:counting.subtitle')}
          </p>
        </div>
        <Link
          to="/inventory/counting/create"
          className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <Plus className="w-4 h-4 mr-2" />
          {t('inventory:counting.newCount')}
        </Link>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <SummaryCard
          icon={Activity}
          label={t('inventory:counting.active')}
          value={summary.active}
          href="/inventory/counting/list?status=active"
          iconClassName="text-blue-600"
        />
        <SummaryCard
          icon={Clock}
          label={t('inventory:counting.pendingReview')}
          value={summary.pending_review}
          href="/inventory/counting/list?status=pending_review"
          iconClassName="text-amber-600"
        />
        <SummaryCard
          icon={CheckCircle}
          label={t('inventory:counting.completedThisMonth')}
          value={summary.completed_this_month}
          href="/inventory/counting/list?status=finalized"
          iconClassName="text-green-600"
        />
        <SummaryCard
          icon={AlertTriangle}
          label={t('inventory:counting.overdue')}
          value={summary.overdue}
          href="/inventory/counting/list?overdue=true"
          iconClassName="text-red-600"
          highlight={summary.overdue > 0}
        />
      </div>

      <div className="bg-white rounded-lg border p-6 text-center text-gray-500">
        <p>{t('inventory:counting.detailsComingSoon')}</p>
        <p className="text-sm mt-2">{t('inventory:counting.fullImplementationInProgress')}</p>
      </div>
    </div>
  );
}

interface SummaryCardProps {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  value: number;
  href: string;
  iconClassName?: string;
  highlight?: boolean;
}

function SummaryCard({
  icon: Icon,
  label,
  value,
  href,
  iconClassName,
  highlight = false,
}: SummaryCardProps) {
  return (
    <Link
      to={href}
      className={'block bg-white rounded-lg border p-6 hover:shadow-md transition-shadow ' + (highlight ? 'border-red-300 bg-red-50' : '')}
    >
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-600">{label}</p>
          <p className="text-3xl font-bold mt-1">{value}</p>
        </div>
        <Icon className={'w-8 h-8 ' + iconClassName} />
      </div>
    </Link>
  );
}
