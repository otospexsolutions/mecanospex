import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { Plus, Vault, Building2, CreditCard, Wallet, X } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { api, apiPost } from '../../lib/api'

interface Repository {
  id: string
  code: string
  name: string
  type: 'cash_register' | 'safe' | 'bank_account' | 'virtual'
  bank_name: string | null
  account_number: string | null
  iban: string | null
  bic: string | null
  balance: string
  is_active: boolean
}

interface RepositoriesResponse {
  data: Repository[]
}

interface RepositoryFormData {
  code: string
  name: string
  type: string
  bank_name: string
  account_number: string
  iban: string
  bic: string
}

const typeIcons: Record<Repository['type'], React.ComponentType<{ className?: string }>> = {
  cash_register: CreditCard,
  safe: Vault,
  bank_account: Building2,
  virtual: Wallet,
}

const typeLabels: Record<Repository['type'], string> = {
  cash_register: 'Cash Register',
  safe: 'Safe',
  bank_account: 'Bank Account',
  virtual: 'Virtual',
}

const typeColors: Record<Repository['type'], string> = {
  cash_register: 'bg-green-100 text-green-800',
  safe: 'bg-purple-100 text-purple-800',
  bank_account: 'bg-blue-100 text-blue-800',
  virtual: 'bg-gray-100 text-gray-800',
}

export function RepositoryListPage() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const [showAddModal, setShowAddModal] = useState(false)

  const { data, isLoading, error } = useQuery({
    queryKey: ['payment-repositories'],
    queryFn: async () => {
      const response = await api.get<RepositoriesResponse>('/payment-repositories')
      return response.data
    },
  })

  const repositories = data?.data ?? []

  const formatCurrency = (amount: string | number) => {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(num)
  }

  // Group by type
  const groupedRepos = repositories.reduce<Partial<Record<Repository['type'], Repository[]>>>(
    (acc, repo) => {
      const existing = acc[repo.type]
      if (existing === undefined) {
        acc[repo.type] = [repo]
      } else {
        existing.push(repo)
      }
      return acc
    },
    {}
  )

  // Calculate total balance
  const totalBalance = repositories.reduce(
    (sum, repo) => sum + parseFloat(repo.balance),
    0
  )

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('navigation.repositories', 'Repositories')}</h1>
          <p className="text-gray-500">
            {repositories.length} {repositories.length === 1 ? 'repository' : 'repositories'} | Total: {formatCurrency(totalBalance)}
          </p>
        </div>
        <button
          onClick={() => { setShowAddModal(true) }}
          className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          Add Repository
        </button>
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
      ) : repositories.length === 0 ? (
        <div className="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
          <Vault className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-semibold text-gray-900">No repositories</h3>
          <p className="mt-1 text-sm text-gray-500">
            Get started by adding a cash register, safe, or bank account.
          </p>
          <div className="mt-6">
            <button
              onClick={() => { setShowAddModal(true) }}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
              <Plus className="h-4 w-4" />
              Add Repository
            </button>
          </div>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Summary Cards */}
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {(['cash_register', 'safe', 'bank_account', 'virtual'] as const).map((type) => {
              const repos = groupedRepos[type] ?? [] as Repository[]
              const Icon = typeIcons[type]
              const typeBalance = repos.reduce((sum, r) => sum + parseFloat(r.balance), 0)
              return (
                <div key={type} className="rounded-lg border border-gray-200 bg-white p-4">
                  <div className="flex items-center gap-3">
                    <div className={`rounded-lg p-2 ${typeColors[type]}`}>
                      <Icon className="h-5 w-5" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">{typeLabels[type]}</p>
                      <p className="text-lg font-semibold text-gray-900">
                        {formatCurrency(typeBalance)}
                      </p>
                    </div>
                  </div>
                  <p className="mt-2 text-xs text-gray-500">
                    {repos.length} {repos.length === 1 ? 'account' : 'accounts'}
                  </p>
                </div>
              )
            })}
          </div>

          {/* Repository List */}
          <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                    Repository
                  </th>
                  <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                    Type
                  </th>
                  <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                    Bank Info
                  </th>
                  <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                    Status
                  </th>
                  <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                    Balance
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 bg-white">
                {repositories.map((repo) => {
                  const Icon = typeIcons[repo.type]
                  return (
                    <tr key={repo.id} className="hover:bg-gray-50">
                      <td className="whitespace-nowrap px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div className={`rounded-lg p-2 ${typeColors[repo.type]}`}>
                            <Icon className="h-4 w-4" />
                          </div>
                          <div>
                            <p className="font-medium text-gray-900">{repo.name}</p>
                            <p className="text-sm text-gray-500 font-mono">{repo.code}</p>
                          </div>
                        </div>
                      </td>
                      <td className="whitespace-nowrap px-6 py-4">
                        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${typeColors[repo.type]}`}>
                          {typeLabels[repo.type]}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-500">
                        {repo.bank_name ? (
                          <div>
                            <p>{repo.bank_name}</p>
                            {repo.account_number && (
                              <p className="font-mono text-xs">{repo.account_number}</p>
                            )}
                          </div>
                        ) : (
                          <span className="text-gray-400">-</span>
                        )}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4">
                        <span
                          className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                            repo.is_active
                              ? 'bg-green-100 text-green-800'
                              : 'bg-gray-100 text-gray-800'
                          }`}
                        >
                          {repo.is_active ? 'Active' : 'Inactive'}
                        </span>
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-end">
                        <span className={`text-sm font-semibold ${
                          parseFloat(repo.balance) >= 0 ? 'text-green-600' : 'text-red-600'
                        }`}>
                          {formatCurrency(repo.balance)}
                        </span>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Add Repository Modal */}
      {showAddModal && (
        <AddRepositoryModal
          onClose={() => { setShowAddModal(false) }}
          onSuccess={() => {
            setShowAddModal(false)
            void queryClient.invalidateQueries({ queryKey: ['payment-repositories'] })
          }}
        />
      )}
    </div>
  )
}

function AddRepositoryModal({
  onClose,
  onSuccess,
}: {
  onClose: () => void
  onSuccess: () => void
}) {
  const { t } = useTranslation()
  const { register, handleSubmit, watch, formState: { errors } } = useForm<RepositoryFormData>({
    defaultValues: {
      code: '',
      name: '',
      type: 'cash_register',
      bank_name: '',
      account_number: '',
      iban: '',
      bic: '',
    },
  })

  const selectedType = watch('type')
  const isBankAccount = selectedType === 'bank_account'

  const createMutation = useMutation({
    mutationFn: (data: RepositoryFormData) =>
      apiPost<Repository>('/payment-repositories', {
        code: data.code,
        name: data.name,
        type: data.type,
        bank_name: data.bank_name || null,
        account_number: data.account_number || null,
        iban: data.iban || null,
        bic: data.bic || null,
      }),
    onSuccess,
  })

  const onSubmit = (data: RepositoryFormData) => {
    createMutation.mutate(data)
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="w-full max-w-lg rounded-lg bg-white shadow-xl">
        <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
          <h2 className="text-lg font-semibold text-gray-900">Add Repository</h2>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
            <X className="h-5 w-5" />
          </button>
        </div>

        <form onSubmit={(e) => void handleSubmit(onSubmit)(e)} className="p-6 space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="code" className="block text-sm font-medium text-gray-700">
                Code *
              </label>
              <input
                type="text"
                id="code"
                {...register('code', { required: 'Code is required' })}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="CASH-01"
              />
              {errors.code && (
                <p className="mt-1 text-sm text-red-600">{errors.code.message}</p>
              )}
            </div>

            <div>
              <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                Name *
              </label>
              <input
                type="text"
                id="name"
                {...register('name', { required: 'Name is required' })}
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="Main Cash Register"
              />
              {errors.name && (
                <p className="mt-1 text-sm text-red-600">{errors.name.message}</p>
              )}
            </div>
          </div>

          <div>
            <label htmlFor="type" className="block text-sm font-medium text-gray-700">
              Type *
            </label>
            <select
              id="type"
              {...register('type', { required: 'Type is required' })}
              className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="cash_register">Cash Register</option>
              <option value="safe">Safe</option>
              <option value="bank_account">Bank Account</option>
              <option value="virtual">Virtual</option>
            </select>
          </div>

          {isBankAccount && (
            <>
              <div>
                <label htmlFor="bank_name" className="block text-sm font-medium text-gray-700">
                  Bank Name
                </label>
                <input
                  type="text"
                  id="bank_name"
                  {...register('bank_name')}
                  className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  placeholder="Bank of America"
                />
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label htmlFor="account_number" className="block text-sm font-medium text-gray-700">
                    Account Number
                  </label>
                  <input
                    type="text"
                    id="account_number"
                    {...register('account_number')}
                    className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label htmlFor="iban" className="block text-sm font-medium text-gray-700">
                    IBAN
                  </label>
                  <input
                    type="text"
                    id="iban"
                    {...register('iban')}
                    className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div>
                <label htmlFor="bic" className="block text-sm font-medium text-gray-700">
                  BIC/SWIFT
                </label>
                <input
                  type="text"
                  id="bic"
                  {...register('bic')}
                  className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
            </>
          )}

          {createMutation.error && (
            <div className="rounded-lg bg-red-50 p-3 text-sm text-red-700">
              {createMutation.error instanceof Error
                ? createMutation.error.message
                : 'An error occurred. Please try again.'}
            </div>
          )}

          <div className="flex justify-end gap-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              {t('actions.cancel')}
            </button>
            <button
              type="submit"
              disabled={createMutation.isPending}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            >
              {createMutation.isPending ? t('status.saving', 'Saving...') : t('actions.save')}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
