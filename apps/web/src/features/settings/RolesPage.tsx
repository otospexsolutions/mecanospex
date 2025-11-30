import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Shield, Check, X } from 'lucide-react'
import { api } from '../../lib/api'

interface Role {
  id: number
  name: string
  permissions: string[]
}

interface RolesResponse {
  data: Role[]
}

interface PermissionsResponse {
  data: Record<string, string[]>
}

export function RolesPage() {
  const { t } = useTranslation()

  const { data: rolesData, isLoading: loadingRoles, error: rolesError } = useQuery({
    queryKey: ['roles'],
    queryFn: async () => {
      const response = await api.get<RolesResponse>('/roles')
      return response.data
    },
  })

  const { data: permissionsData, isLoading: loadingPermissions } = useQuery({
    queryKey: ['permissions'],
    queryFn: async () => {
      const response = await api.get<PermissionsResponse>('/permissions')
      return response.data
    },
  })

  const isLoading = loadingRoles || loadingPermissions
  const roles = rolesData?.data ?? []
  const permissionGroups = permissionsData?.data ?? {}

  // Get all unique permissions across all groups
  const allPermissions = Object.values(permissionGroups).flat()

  return (
    <div className="space-y-6">
      {/* Header */}
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
            <Shield className="h-6 w-6 text-purple-500" />
            Roles & Permissions
          </h1>
          <p className="text-gray-500">
            {String(roles.length)} roles | {String(allPermissions.length)} permissions
          </p>
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <div className="text-gray-500">{t('status.loading')}</div>
        </div>
      ) : rolesError ? (
        <div className="rounded-lg bg-red-50 p-4 text-red-700">
          {t('errors.loadingFailed', 'Error loading data. Please try again.')}
        </div>
      ) : (
        <div className="space-y-6">
          {/* Roles Overview */}
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {roles.map((role) => (
              <div key={role.id} className="rounded-lg border border-gray-200 bg-white p-4">
                <div className="flex items-center gap-3">
                  <div className="rounded-lg bg-purple-100 p-2 text-purple-600">
                    <Shield className="h-5 w-5" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 capitalize">{role.name}</h3>
                    <p className="text-sm text-gray-500">
                      {String(role.permissions.length)} permissions
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Permission Matrix */}
          <div className="rounded-lg border border-gray-200 bg-white overflow-hidden">
            <div className="p-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Permission Matrix</h2>
              <p className="text-sm text-gray-500">
                View which permissions are assigned to each role
              </p>
            </div>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="sticky left-0 bg-gray-50 px-4 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                      Permission
                    </th>
                    {roles.map((role) => (
                      <th
                        key={role.id}
                        className="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500"
                      >
                        {role.name}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                  {Object.entries(permissionGroups).map(([module, permissions]) => (
                    <>
                      {/* Module Header Row */}
                      <tr key={module} className="bg-gray-50">
                        <td
                          colSpan={roles.length + 1}
                          className="px-4 py-2 text-sm font-semibold text-gray-700 capitalize"
                        >
                          {module}
                        </td>
                      </tr>
                      {/* Permission Rows */}
                      {permissions.map((permission) => (
                        <tr key={permission} className="hover:bg-gray-50">
                          <td className="sticky left-0 bg-white whitespace-nowrap px-4 py-2 text-sm text-gray-700">
                            {permission.replace(`${module}.`, '')}
                          </td>
                          {roles.map((role) => {
                            const hasPermission = role.permissions.includes(permission)
                            return (
                              <td key={role.id} className="px-4 py-2 text-center">
                                {hasPermission ? (
                                  <Check className="inline-block h-4 w-4 text-green-600" />
                                ) : (
                                  <X className="inline-block h-4 w-4 text-gray-300" />
                                )}
                              </td>
                            )
                          })}
                        </tr>
                      ))}
                    </>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Info */}
          <div className="rounded-lg bg-blue-50 border border-blue-200 p-4">
            <h3 className="font-medium text-blue-900">Managing Roles</h3>
            <p className="mt-1 text-sm text-blue-700">
              Role management is configured at the system level. Contact your administrator
              to create new roles or modify existing permissions.
            </p>
          </div>
        </div>
      )}
    </div>
  )
}
