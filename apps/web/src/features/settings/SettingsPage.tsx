import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Settings, Users, Shield, Building2, ChevronRight } from 'lucide-react'

interface SettingsSection {
  title: string
  description: string
  icon: React.ReactNode
  href: string
  color: string
}

const sections: SettingsSection[] = [
  {
    title: 'Users',
    description: 'Manage user accounts and access',
    icon: <Users className="h-6 w-6" />,
    href: '/settings/users',
    color: 'bg-blue-100 text-blue-600',
  },
  {
    title: 'Roles & Permissions',
    description: 'Configure roles and their permissions',
    icon: <Shield className="h-6 w-6" />,
    href: '/settings/roles',
    color: 'bg-purple-100 text-purple-600',
  },
  {
    title: 'Company',
    description: 'Company information and branding',
    icon: <Building2 className="h-6 w-6" />,
    href: '/settings/company',
    color: 'bg-green-100 text-green-600',
  },
]

export function SettingsPage() {
  const { t } = useTranslation()

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <Settings className="h-6 w-6 text-gray-400" />
          {t('navigation.settings', 'Settings')}
        </h1>
        <p className="text-gray-500">Manage your application settings and configuration</p>
      </div>

      {/* Settings Sections */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {sections.map((section) => (
          <Link
            key={section.href}
            to={section.href}
            className="group rounded-lg border border-gray-200 bg-white p-6 hover:border-blue-300 hover:shadow-md transition-all"
          >
            <div className="flex items-start gap-4">
              <div className={`rounded-lg p-3 ${section.color}`}>
                {section.icon}
              </div>
              <div className="flex-1">
                <div className="flex items-center justify-between">
                  <h3 className="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                    {section.title}
                  </h3>
                  <ChevronRight className="h-5 w-5 text-gray-400 group-hover:text-blue-600 transition-colors" />
                </div>
                <p className="mt-1 text-sm text-gray-500">{section.description}</p>
              </div>
            </div>
          </Link>
        ))}
      </div>

      {/* App Info */}
      <div className="rounded-lg border border-gray-200 bg-gray-50 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Application Info</h2>
        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <dt className="text-sm font-medium text-gray-500">Version</dt>
            <dd className="mt-1 text-sm text-gray-900">1.0.0</dd>
          </div>
          <div>
            <dt className="text-sm font-medium text-gray-500">Environment</dt>
            <dd className="mt-1 text-sm text-gray-900">
              {import.meta.env.MODE}
            </dd>
          </div>
          <div>
            <dt className="text-sm font-medium text-gray-500">API URL</dt>
            <dd className="mt-1 text-sm text-gray-900 font-mono text-xs truncate">
              {(import.meta.env['VITE_API_URL'] as string | undefined) ?? 'Not configured'}
            </dd>
          </div>
          <div>
            <dt className="text-sm font-medium text-gray-500">Build Date</dt>
            <dd className="mt-1 text-sm text-gray-900">
              {new Date().toLocaleDateString()}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  )
}
