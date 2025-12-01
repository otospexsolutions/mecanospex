import { useTranslation } from 'react-i18next'
import { Loader2 } from 'lucide-react'

interface SpinnerProps {
  size?: 'sm' | 'md' | 'lg'
  fullScreen?: boolean
  message?: string
}

const sizeClasses = {
  sm: 'h-4 w-4',
  md: 'h-8 w-8',
  lg: 'h-12 w-12',
}

export function Spinner({ size = 'md', fullScreen = false, message }: SpinnerProps) {
  const { t } = useTranslation()
  const displayMessage = message ?? t('status.loading')

  if (fullScreen) {
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Loader2 className={`${sizeClasses[size]} animate-spin text-blue-600`} />
        <p className="mt-4 text-sm text-gray-500">{displayMessage}</p>
      </div>
    )
  }

  return (
    <div className="flex items-center gap-2">
      <Loader2 className={`${sizeClasses[size]} animate-spin text-blue-600`} />
      {message && <span className="text-sm text-gray-500">{message}</span>}
    </div>
  )
}

// Re-export as LoadingSpinner for backwards compatibility
export { Spinner as LoadingSpinner }
