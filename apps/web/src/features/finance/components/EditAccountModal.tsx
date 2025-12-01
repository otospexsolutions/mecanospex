import { useState, useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { X } from 'lucide-react'
import { useUpdateAccount } from '../hooks/useAccounts'
import type { Account } from '../types'

interface EditAccountModalProps {
  account: Account
  open: boolean
  onClose: () => void
  onSuccess: () => void
}

export function EditAccountModal({ account, open, onClose, onSuccess }: EditAccountModalProps) {
  const { t } = useTranslation(['common', 'validation'])
  const updateMutation = useUpdateAccount()

  const [formData, setFormData] = useState({
    name: account.name,
    description: account.description || '',
    is_active: account.is_active,
  })

  const [errors, setErrors] = useState<Record<string, string>>({})

  // Note: Initial formData set from props, updates when account changes
  // This is acceptable since we're synchronizing with an external prop change
  useEffect(() => {
    setFormData({
      name: account.name,
      description: account.description || '',
      is_active: account.is_active,
    })
    // Intentionally resetting form when account prop changes
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [account.id])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    const newErrors: Record<string, string> = {}

    if (!formData.name.trim()) {
      newErrors['name'] = t('validation:required')
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors)
      return
    }

    void updateMutation
      .mutateAsync({
        id: account.id,
        data: {
          name: formData.name,
          description: formData.description || null,
          is_active: formData.is_active,
        },
      })
      .then(() => {
        setErrors({})
        onSuccess()
      })
      .catch((error: unknown) => {
        console.error('Failed to update account:', error)
      })
  }

  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" role="dialog">
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">Edit Account</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Account Code
            </label>
            <input
              type="text"
              value={account.code}
              disabled
              className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
            />
            <p className="mt-1 text-xs text-gray-500">Account code cannot be changed</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Account Type
            </label>
            <input
              type="text"
              value={account.type}
              disabled
              className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 capitalize"
            />
            <p className="mt-1 text-xs text-gray-500">Account type cannot be changed</p>
          </div>

          <div>
            <label htmlFor="edit-name" className="block text-sm font-medium text-gray-700 mb-1">
              Account Name
            </label>
            <input
              id="edit-name"
              name="name"
              type="text"
              value={formData.name}
              onChange={(e) => { setFormData({ ...formData, name: e.target.value }); }}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            {errors['name'] && <p className="mt-1 text-sm text-red-600">{errors['name']}</p>}
          </div>

          <div>
            <label htmlFor="edit-description" className="block text-sm font-medium text-gray-700 mb-1">
              Description (Optional)
            </label>
            <textarea
              id="edit-description"
              name="description"
              rows={3}
              value={formData.description}
              onChange={(e) => { setFormData({ ...formData, description: e.target.value }); }}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div className="flex items-center gap-2">
            <input
              id="is_active"
              name="is_active"
              type="checkbox"
              checked={formData.is_active}
              onChange={(e) => { setFormData({ ...formData, is_active: e.target.checked }); }}
              className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
            />
            <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
              Active
            </label>
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
              disabled={updateMutation.isPending}
              className="flex-1 px-4 py-2 bg-blue-600 rounded-lg text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
            >
              {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
