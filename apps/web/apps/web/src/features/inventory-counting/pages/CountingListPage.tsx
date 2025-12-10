import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Plus } from 'lucide-react';

export function CountingListPage() {
  const { t } = useTranslation(['inventory']);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('inventory:counting.list.title')}</h1>
          <p className="text-gray-600">
            {t('inventory:counting.list.description')}
          </p>
        </div>
        <Link
          to="/inventory/counting/create"
          className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <Plus className="w-4 h-4 mr-2" />
          {t('inventory:counting.new')}
        </Link>
      </div>

      <div className="bg-white rounded-lg border p-8 text-center text-gray-500">
        <p>{t('inventory:counting.list.empty')}</p>
        <p className="text-sm mt-2">Full implementation in progress</p>
      </div>
    </div>
  );
}
