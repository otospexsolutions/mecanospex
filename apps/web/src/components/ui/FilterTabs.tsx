interface FilterTab<T extends string> {
  value: T
  label: string
  count?: number
}

interface FilterTabsProps<T extends string> {
  tabs: FilterTab<T>[]
  value: T
  onChange: (value: T) => void
  className?: string
}

export function FilterTabs<T extends string>({
  tabs,
  value,
  onChange,
  className = '',
}: FilterTabsProps<T>) {
  return (
    <div className={`flex gap-1 rounded-lg bg-gray-100 p-1 ${className}`}>
      {tabs.map((tab) => (
        <button
          key={tab.value}
          type="button"
          onClick={() => {
            onChange(tab.value)
          }}
          className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
            value === tab.value
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-600 hover:text-gray-900'
          }`}
        >
          {tab.label}
          {tab.count !== undefined && (
            <span
              className={`ms-1.5 rounded-full px-1.5 py-0.5 text-xs ${
                value === tab.value ? 'bg-gray-100 text-gray-600' : 'bg-gray-200 text-gray-500'
              }`}
            >
              {tab.count}
            </span>
          )}
        </button>
      ))}
    </div>
  )
}
