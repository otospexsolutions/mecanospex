import { useState, useCallback } from 'react'
import { Search, X } from 'lucide-react'
import { useTranslation } from 'react-i18next'

interface SearchInputProps {
  value: string
  onChange: (value: string) => void
  placeholder?: string
  className?: string
  debounceMs?: number
}

export function SearchInput({
  value,
  onChange,
  placeholder,
  className = '',
  debounceMs = 300,
}: SearchInputProps) {
  const { t } = useTranslation()
  const [localValue, setLocalValue] = useState(value)
  const [debounceTimeout, setDebounceTimeout] = useState<NodeJS.Timeout | null>(null)

  const handleChange = useCallback(
    (newValue: string) => {
      setLocalValue(newValue)

      if (debounceTimeout) {
        clearTimeout(debounceTimeout)
      }

      const timeout = setTimeout(() => {
        onChange(newValue)
      }, debounceMs)

      setDebounceTimeout(timeout)
    },
    [onChange, debounceMs, debounceTimeout]
  )

  const handleClear = useCallback(() => {
    setLocalValue('')
    onChange('')
  }, [onChange])

  return (
    <div className={`relative ${className}`}>
      <div className="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
        <Search className="h-4 w-4 text-gray-400" />
      </div>
      <input
        type="text"
        value={localValue}
        onChange={(e) => {
          handleChange(e.target.value)
        }}
        placeholder={placeholder ?? t('actions.search')}
        className="block w-full rounded-lg border border-gray-300 bg-white py-2 pe-10 ps-10 text-sm placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
      />
      {localValue && (
        <button
          type="button"
          onClick={handleClear}
          className="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600"
          aria-label={t('actions.clear')}
        >
          <X className="h-4 w-4" />
        </button>
      )}
    </div>
  )
}
