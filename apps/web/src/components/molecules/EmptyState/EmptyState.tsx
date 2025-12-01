import { Construction } from 'lucide-react'

interface EmptyStateProps {
  title: string
  description?: string
  icon?: React.ReactNode
}

export function EmptyState({ title, description, icon }: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center min-h-96 text-center">
      {icon ?? <Construction className="h-16 w-16 text-gray-400 mb-4" />}
      <h1 className="text-2xl font-semibold text-gray-900 mb-2">{title}</h1>
      <p className="text-gray-500 max-w-md">
        {description ?? 'This feature is coming soon. Check back later for updates.'}
      </p>
    </div>
  )
}

// Backwards compatibility alias
export { EmptyState as PlaceholderPage }
