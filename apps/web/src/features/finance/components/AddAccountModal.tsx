import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { X } from 'lucide-react'
import { useCreateAccount, useAccounts } from '../hooks/useAccounts'
import type { AccountType } from '../types'

interface AddAccountModalProps {
  open: boolean
  onClose: () => void
  onSuccess: () => void
}

export function AddAccountModal({ open, onClose, onSuccess }: AddAccountModalProps) {
  const { t } = useTranslation(['common', 'validation'])
  const { data: accounts } = useAccounts()
  const createMutation = useCreateAccount()

  const [formData, setFormData] = useState({
    code: '',
    name: '',
    type: 'asset' as AccountType,
    description: '',
    parent_id: '',
  })

  const [errors, setErrors] = useState<Record<string, string>>({})

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    const newErrors: Record<string, string> = {}

    if (!formData.code.trim()) {
      newErrors['code'] = t('validation:required')
    }

    if (!formData.name.trim()) {
      newErrors['name'] = t('validation:required')
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors)
      return
    }

    void createMutation
      .mutateAsync({
        code: formData.code,
        name: formData.name,
        type: formData.type,
        description: formData.description || undefined,
        parent_id: formData.parent_id || undefined,
      })
      .then(() => {
        setFormData({
          code: '',
          name: '',
          type: 'asset',
          description: '',
          parent_id: '',
        })
        setErrors({})
        onSuccess()
      })
      .catch((error: unknown) => {
        console.error('Failed to create account:', error)
      })
  }

  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" role="dialog">
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">Add Account</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label htmlFor="code" className="block text-sm font-medium text-gray-700 mb-1">
              Account Code
            </label>
            <input
              id="code"
              name="code"
              type="text"
              value={formData.code}
              onChange={(e) => { setFormData({ ...formData, code: e.target.value }); }}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            {errors['code'] && <p className="mt-1 text-sm text-red-600">{errors['code']}</p>}
          </div>

          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
              Account Name
            </label>
            <input
              id="name"
              name="name"
              type="text"
              value={formData.name}
              onChange={(e) => { setFormData({ ...formData, name: e.target.value }); }}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            {errors['name'] && <p className="mt-1 text-sm text-red-600">{errors['name']}</p>}
          </div>

          <div>
            <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-1">
              Account Type
            </label>
            <select
              id="type"
              name="type"
              value={formData.type}
              onChange={(e) => { setFormData({ ...formData, type: e.target.value as AccountType }); }}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="asset">Asset</option>
              <option value="liability">Liability</option>
              <option value="equity">Equity</option>
              <option value="revenue">Revenue</option>
              <option value="expense">Expense</option>
            </select>
          </div>

          <div>
            <label htmlFor="parent_id" className="block text-sm font-medium text-gray-700 mb-1">
              Parent Account (Optional)
            </label>
            <select
              id="parent_id"
              name="parent_id"
              value={formData.parent_id}
              onChange={(e) => { setFormData({ ...formData, parent_id: e.target.value }); }}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">None</option>
              {accounts?.filter((a) => a.type === formData.type).map((account) => (
                <option key={account.id} value={account.id}>
                  {account.code} - {account.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
              Description (Optional)
            </label>
            <textarea
              id="description"
              name="description"
              rows={3}
              value={formData.description}
              onChange={(e) => { setFormData({ ...formData, description: e.target.value }); }}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={createMutation.isPending}
              className="flex-1 px-4 py-2 bg-blue-600 rounded-lg text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
            >
              {createMutation.isPending ? 'Creating...' : 'Create Account'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
