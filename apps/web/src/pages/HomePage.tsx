import { FileText, Users, Package, TrendingUp } from 'lucide-react'

interface StatCardProps {
  title: string
  value: string
  change: string
  changeType: 'positive' | 'negative' | 'neutral'
  icon: React.ComponentType<{ className?: string }>
}

function StatCard({ title, value, change, changeType, icon: Icon }: StatCardProps) {
  const changeColors = {
    positive: 'text-green-600',
    negative: 'text-red-600',
    neutral: 'text-gray-600',
  }

  return (
    <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-200">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-500">{title}</p>
          <p className="mt-2 text-3xl font-bold text-gray-900">{value}</p>
          <p className={`mt-1 text-sm ${changeColors[changeType]}`}>{change}</p>
        </div>
        <div className="rounded-lg bg-blue-50 p-3">
          <Icon className="h-6 w-6 text-blue-600" />
        </div>
      </div>
    </div>
  )
}

export function HomePage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-500">Welcome back! Here's what's happening today.</p>
      </div>

      {/* Stats grid */}
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Total Revenue"
          value="€24,500"
          change="+12% from last month"
          changeType="positive"
          icon={TrendingUp}
        />
        <StatCard
          title="Active Customers"
          value="342"
          change="+5 new this week"
          changeType="positive"
          icon={Users}
        />
        <StatCard
          title="Pending Invoices"
          value="18"
          change="3 overdue"
          changeType="negative"
          icon={FileText}
        />
        <StatCard
          title="Low Stock Items"
          value="7"
          change="Reorder needed"
          changeType="neutral"
          icon={Package}
        />
      </div>

      {/* Placeholder for future content */}
      <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-200">
        <h2 className="text-lg font-semibold text-gray-900">Recent Activity</h2>
        <p className="mt-2 text-gray-500">
          Activity feed will be implemented in a future phase.
        </p>
      </div>
    </div>
  )
}
