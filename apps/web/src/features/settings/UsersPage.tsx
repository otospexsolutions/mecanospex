import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  ArrowLeft,
  Users,
  UserPlus,
  Mail,
  Phone,
  MoreVertical,
  CheckCircle,
  XCircle,
  KeyRound,
  Trash2,
} from 'lucide-react'
import { api, getErrorMessage } from '../../lib/api'
import { useAuthStore } from '../../stores/authStore'
import { SearchInput } from '../../components/ui/SearchInput'
import { FilterTabs } from '../../components/ui/FilterTabs'

interface User {
  id: string
  name: string
  email: string
  phone: string | null
  status: 'active' | 'inactive' | 'pending_verification' | 'locked'
  roles: string[]
  lastLoginAt: string | null
  createdAt: string
}

interface UsersResponse {
  data: User[]
  meta?: {
    total: number
    current_page: number
    per_page: number
    last_page: number
  }
}

interface Role {
  name: string
  permissions: string[]
}

interface CreateUserData {
  name: string
  email: string
  phone?: string | undefined
  role: string
  locale?: string | undefined
  timezone?: string | undefined
}

type StatusFilter = 'all' | 'active' | 'inactive' | 'pending_verification'

const statusColors: Record<string, string> = {
  active: 'bg-green-100 text-green-800',
  inactive: 'bg-gray-100 text-gray-800',
  pending_verification: 'bg-yellow-100 text-yellow-800',
  locked: 'bg-red-100 text-red-800',
}

const statusLabels: Record<string, string> = {
  active: 'Active',
  inactive: 'Inactive',
  pending_verification: 'Pending',
  locked: 'Locked',
}

const roleColors: Record<string, string> = {
  admin: 'bg-purple-100 text-purple-800',
  manager: 'bg-blue-100 text-blue-800',
  cashier: 'bg-green-100 text-green-800',
  accountant: 'bg-indigo-100 text-indigo-800',
  operator: 'bg-teal-100 text-teal-800',
  technician: 'bg-orange-100 text-orange-800',
  viewer: 'bg-gray-100 text-gray-800',
}

export function UsersPage() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const currentUser = useAuthStore((state) => state.user)
  const [searchQuery, setSearchQuery] = useState('')
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all')
  const [showAddModal, setShowAddModal] = useState(false)
  const [showActionMenu, setShowActionMenu] = useState<string | null>(null)
  const [actionLoading, setActionLoading] = useState<string | null>(null)
  const [notification, setNotification] = useState<{
    type: 'success' | 'error'
    message: string
  } | null>(null)

  // Fetch users
  const { data, isLoading, error } = useQuery({
    queryKey: ['users', searchQuery, statusFilter],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (searchQuery) params.append('search', searchQuery)
      if (statusFilter !== 'all') params.append('status', statusFilter)
      const queryString = params.toString()
      const response = await api.get<UsersResponse>(`/users${queryString ? `?${queryString}` : ''}`)
      return response.data
    },
  })

  // Fetch roles for the create user form
  const { data: rolesData } = useQuery({
    queryKey: ['roles'],
    queryFn: async () => {
      const response = await api.get<{ data: Role[] }>('/roles')
      return response.data.data
    },
  })

  // Create user mutation
  const createUserMutation = useMutation({
    mutationFn: async (userData: CreateUserData): Promise<User> => {
      const response = await api.post<{ data: User }>('/users', userData)
      return response.data.data
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['users'] })
      setShowAddModal(false)
      showNotification('success', 'User created successfully. An invitation email has been sent.')
    },
    onError: (error) => {
      showNotification('error', getErrorMessage(error))
    },
  })

  // Action mutations
  const activateMutation = useMutation({
    mutationFn: async (userId: string): Promise<void> => {
      setActionLoading(userId)
      await api.post(`/users/${userId}/activate`)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['users'] })
      showNotification('success', 'User activated successfully.')
    },
    onError: (error) => {
      showNotification('error', getErrorMessage(error))
    },
    onSettled: () => {
      setActionLoading(null)
      setShowActionMenu(null)
    },
  })

  const deactivateMutation = useMutation({
    mutationFn: async (userId: string): Promise<void> => {
      setActionLoading(userId)
      await api.post(`/users/${userId}/deactivate`)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['users'] })
      showNotification('success', 'User deactivated successfully.')
    },
    onError: (error) => {
      showNotification('error', getErrorMessage(error))
    },
    onSettled: () => {
      setActionLoading(null)
      setShowActionMenu(null)
    },
  })

  const resetPasswordMutation = useMutation({
    mutationFn: async (userId: string): Promise<void> => {
      setActionLoading(userId)
      await api.post(`/users/${userId}/reset-password`)
    },
    onSuccess: () => {
      showNotification('success', 'Password reset email sent successfully.')
    },
    onError: (error) => {
      showNotification('error', getErrorMessage(error))
    },
    onSettled: () => {
      setActionLoading(null)
      setShowActionMenu(null)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: async (userId: string): Promise<void> => {
      setActionLoading(userId)
      await api.delete(`/users/${userId}`)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['users'] })
      showNotification('success', 'User deleted successfully.')
    },
    onError: (error) => {
      showNotification('error', getErrorMessage(error))
    },
    onSettled: () => {
      setActionLoading(null)
      setShowActionMenu(null)
    },
  })

  const users = data?.data ?? []
  const total = data?.meta?.total ?? users.length
  const roles = rolesData ?? []

  const filterTabs = [
    { value: 'all' as StatusFilter, label: 'All', count: total },
    { value: 'active' as StatusFilter, label: 'Active' },
    { value: 'inactive' as StatusFilter, label: 'Inactive' },
    { value: 'pending_verification' as StatusFilter, label: 'Pending' },
  ]

  const showNotification = (type: 'success' | 'error', message: string) => {
    setNotification({ type, message })
    setTimeout(() => { setNotification(null) }, 5000)
  }

  const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Never'
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    })
  }

  const handleAction = (action: string, userId: string) => {
    switch (action) {
      case 'activate':
        activateMutation.mutate(userId)
        break
      case 'deactivate':
        if (confirm('Are you sure you want to deactivate this user?')) {
          deactivateMutation.mutate(userId)
        } else {
          setShowActionMenu(null)
        }
        break
      case 'reset-password':
        resetPasswordMutation.mutate(userId)
        break
      case 'delete':
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
          deleteMutation.mutate(userId)
        } else {
          setShowActionMenu(null)
        }
        break
    }
  }

  return (
    <div className="space-y-6">
      {/* Notification */}
      {notification && (
        <div
          className={`fixed top-4 right-4 z-50 rounded-lg p-4 shadow-lg ${
            notification.type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
          }`}
        >
          <div className="flex items-center gap-2">
            {notification.type === 'success' ? (
              <CheckCircle className="h-5 w-5" />
            ) : (
              <XCircle className="h-5 w-5" />
            )}
            {notification.message}
          </div>
        </div>
      )}

      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link
            to="/settings"
            className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
          >
            <ArrowLeft className="h-4 w-4" />
            {t('actions.back')}
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
              <Users className="h-6 w-6 text-blue-500" />
              User Management
            </h1>
            <p className="text-gray-500">
              {total} {total === 1 ? 'user' : 'users'} total
            </p>
          </div>
        </div>
        <button
          onClick={() => { setShowAddModal(true) }}
          className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
        >
          <UserPlus className="h-4 w-4" />
          Add User
        </button>
      </div>

      {/* Filters */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <FilterTabs tabs={filterTabs} value={statusFilter} onChange={(value) => { setStatusFilter(value) }} />
        <SearchInput
          value={searchQuery}
          onChange={(value) => { setSearchQuery(value) }}
          placeholder="Search users..."
          className="w-full sm:w-72"
        />
      </div>

      {/* Content */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <div className="text-gray-500">{t('status.loading')}</div>
        </div>
      ) : error ? (
        <div className="rounded-lg bg-red-50 p-4 text-red-700">
          Error loading users. Please try again.
        </div>
      ) : users.length === 0 ? (
        <div className="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
          <Users className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-semibold text-gray-900">
            {searchQuery ? 'No results found' : 'No users'}
          </h3>
          <p className="mt-1 text-sm text-gray-500">
            {searchQuery
              ? 'Try a different search term.'
              : 'Get started by adding a new user.'}
          </p>
          {!searchQuery && (
            <div className="mt-6">
              <button
                onClick={() => { setShowAddModal(true) }}
                className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
              >
                <UserPlus className="h-4 w-4" />
                Add User
              </button>
            </div>
          )}
        </div>
      ) : (
        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  User
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Role
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Status
                </th>
                <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                  Last Login
                </th>
                <th className="relative px-6 py-3">
                  <span className="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 bg-white">
              {users.map((user) => (
                <tr key={user.id} className="hover:bg-gray-50">
                  <td className="whitespace-nowrap px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <span className="text-sm font-semibold text-blue-600">
                          {user.name.charAt(0).toUpperCase()}
                        </span>
                      </div>
                      <div>
                        <div className="font-medium text-gray-900">{user.name}</div>
                        <div className="text-sm text-gray-500 flex items-center gap-1">
                          <Mail className="h-3.5 w-3.5" />
                          {user.email}
                        </div>
                        {user.phone && (
                          <div className="text-sm text-gray-500 flex items-center gap-1">
                            <Phone className="h-3.5 w-3.5" />
                            {user.phone}
                          </div>
                        )}
                      </div>
                    </div>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4">
                    <div className="flex flex-wrap gap-1">
                      {user.roles.map((role) => (
                        <span
                          key={role}
                          className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${
                            roleColors[role] ?? 'bg-gray-100 text-gray-800'
                          }`}
                        >
                          {role}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4">
                    <span
                      className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        statusColors[user.status] ?? 'bg-gray-100 text-gray-800'
                      }`}
                    >
                      {statusLabels[user.status] ?? user.status}
                    </span>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                    {formatDate(user.lastLoginAt)}
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-end text-sm">
                    <div className="relative">
                      <button
                        onClick={() => {
                          setShowActionMenu(showActionMenu === user.id ? null : user.id)
                        }}
                        disabled={actionLoading === user.id}
                        className="text-gray-400 hover:text-gray-600 p-1 rounded"
                      >
                        {actionLoading === user.id ? (
                          <div className="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-blue-600" />
                        ) : (
                          <MoreVertical className="h-5 w-5" />
                        )}
                      </button>
                      {showActionMenu === user.id && (
                        <div className="absolute right-0 mt-2 w-48 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 z-10">
                          <div className="py-1">
                            {user.status !== 'active' && (
                              <button
                                onClick={() => { handleAction('activate', user.id) }}
                                className="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                              >
                                <CheckCircle className="h-4 w-4 text-green-500" />
                                Activate
                              </button>
                            )}
                            {user.status === 'active' && user.id !== currentUser?.id && (
                              <button
                                onClick={() => { handleAction('deactivate', user.id) }}
                                className="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                              >
                                <XCircle className="h-4 w-4 text-yellow-500" />
                                Deactivate
                              </button>
                            )}
                            <button
                              onClick={() => { handleAction('reset-password', user.id) }}
                              className="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                              <KeyRound className="h-4 w-4 text-blue-500" />
                              Reset Password
                            </button>
                            {user.id !== currentUser?.id && (
                              <button
                                onClick={() => { handleAction('delete', user.id) }}
                                className="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                              >
                                <Trash2 className="h-4 w-4" />
                                Delete
                              </button>
                            )}
                          </div>
                        </div>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Add User Modal */}
      {showAddModal && (
        <AddUserModal
          roles={roles}
          onClose={() => { setShowAddModal(false) }}
          onSubmit={(data) => { createUserMutation.mutate(data) }}
          isLoading={createUserMutation.isPending}
        />
      )}

      {/* Click outside to close action menu */}
      {showActionMenu && (
        <div className="fixed inset-0 z-0" onClick={() => { setShowActionMenu(null) }} />
      )}
    </div>
  )
}

interface AddUserModalProps {
  roles: Role[]
  onClose: () => void
  onSubmit: (data: CreateUserData) => void
  isLoading: boolean
}

function AddUserModal({ roles, onClose, onSubmit, isLoading }: AddUserModalProps) {
  const [formData, setFormData] = useState<CreateUserData>({
    name: '',
    email: '',
    phone: '',
    role: 'operator',
  })
  const [errors, setErrors] = useState<Record<string, string>>({})

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {}

    if (!formData.name.trim()) {
      newErrors['name'] = 'Name is required'
    }
    if (!formData.email.trim()) {
      newErrors['email'] = 'Email is required'
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors['email'] = 'Invalid email format'
    }
    if (!formData.role) {
      newErrors['role'] = 'Role is required'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (validate()) {
      onSubmit({
        ...formData,
        phone: formData.phone || undefined,
      })
    }
  }

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex min-h-full items-center justify-center p-4">
        <div className="fixed inset-0 bg-black bg-opacity-25" onClick={onClose} />
        <div className="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Add New User</h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                Name *
              </label>
              <input
                type="text"
                id="name"
                value={formData.name}
                onChange={(e) => { setFormData({ ...formData, name: e.target.value }) }}
                className={`mt-1 block w-full rounded-md border px-3 py-2 shadow-sm focus:outline-none focus:ring-1 ${
                  errors['name']
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                }`}
                placeholder="John Doe"
              />
              {errors['name'] && <p className="mt-1 text-sm text-red-600">{errors['name']}</p>}
            </div>

            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                Email *
              </label>
              <input
                type="email"
                id="email"
                value={formData.email}
                onChange={(e) => { setFormData({ ...formData, email: e.target.value }) }}
                className={`mt-1 block w-full rounded-md border px-3 py-2 shadow-sm focus:outline-none focus:ring-1 ${
                  errors['email']
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                }`}
                placeholder="john@example.com"
              />
              {errors['email'] && <p className="mt-1 text-sm text-red-600">{errors['email']}</p>}
            </div>

            <div>
              <label htmlFor="phone" className="block text-sm font-medium text-gray-700">
                Phone
              </label>
              <input
                type="tel"
                id="phone"
                value={formData.phone}
                onChange={(e) => { setFormData({ ...formData, phone: e.target.value }) }}
                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="+33 1 23 45 67 89"
              />
            </div>

            <div>
              <label htmlFor="role" className="block text-sm font-medium text-gray-700">
                Role *
              </label>
              <select
                id="role"
                value={formData.role}
                onChange={(e) => { setFormData({ ...formData, role: e.target.value }) }}
                className={`mt-1 block w-full rounded-md border px-3 py-2 shadow-sm focus:outline-none focus:ring-1 ${
                  errors['role']
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                }`}
              >
                {roles.map((role) => (
                  <option key={role.name} value={role.name}>
                    {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                  </option>
                ))}
              </select>
              {errors['role'] && <p className="mt-1 text-sm text-red-600">{errors['role']}</p>}
            </div>

            <p className="text-sm text-gray-500">
              An invitation email will be sent to the user with instructions to set their password.
            </p>

            <div className="flex justify-end gap-3 pt-4">
              <button
                type="button"
                onClick={onClose}
                className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isLoading}
                className="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
              >
                {isLoading ? (
                  <>
                    <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                    Creating...
                  </>
                ) : (
                  <>
                    <UserPlus className="h-4 w-4" />
                    Create User
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
