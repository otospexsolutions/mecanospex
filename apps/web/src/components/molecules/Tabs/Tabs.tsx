import { createContext, useContext, useState, useCallback, type ReactNode } from 'react'

interface TabsContextValue {
  activeTab: string
  setActiveTab: (tab: string) => void
}

const TabsContext = createContext<TabsContextValue | null>(null)

function useTabsContext() {
  const context = useContext(TabsContext)
  if (!context) {
    throw new Error('Tabs components must be used within a Tabs provider')
  }
  return context
}

interface TabsProps {
  defaultValue: string
  value?: string
  onChange?: (value: string) => void
  children: ReactNode
  className?: string
}

export function Tabs({ defaultValue, value, onChange, children, className = '' }: TabsProps) {
  const [internalValue, setInternalValue] = useState(defaultValue)
  const activeTab = value ?? internalValue

  const setActiveTab = useCallback(
    (tab: string) => {
      if (onChange) {
        onChange(tab)
      } else {
        setInternalValue(tab)
      }
    },
    [onChange]
  )

  return (
    <TabsContext.Provider value={{ activeTab, setActiveTab }}>
      <div className={className}>{children}</div>
    </TabsContext.Provider>
  )
}

interface TabsListProps {
  children: ReactNode
  className?: string
}

export function TabsList({ children, className = '' }: TabsListProps) {
  return (
    <div
      className={`flex border-b border-gray-200 ${className}`}
      role="tablist"
      aria-orientation="horizontal"
    >
      {children}
    </div>
  )
}

interface TabsTriggerProps {
  value: string
  children: ReactNode
  className?: string
  disabled?: boolean
}

export function TabsTrigger({ value, children, className = '', disabled = false }: TabsTriggerProps) {
  const { activeTab, setActiveTab } = useTabsContext()
  const isActive = activeTab === value

  return (
    <button
      type="button"
      role="tab"
      aria-selected={isActive}
      aria-controls={`tabpanel-${value}`}
      disabled={disabled}
      onClick={() => {
        setActiveTab(value)
      }}
      className={`relative px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ${
        isActive
          ? 'text-blue-600'
          : disabled
            ? 'cursor-not-allowed text-gray-400'
            : 'text-gray-500 hover:text-gray-700'
      } ${className}`}
    >
      {children}
      {isActive && (
        <span className="absolute inset-x-0 bottom-0 h-0.5 bg-blue-600" aria-hidden="true" />
      )}
    </button>
  )
}

interface TabsContentProps {
  value: string
  children: ReactNode
  className?: string
}

export function TabsContent({ value, children, className = '' }: TabsContentProps) {
  const { activeTab } = useTabsContext()

  if (activeTab !== value) {
    return null
  }

  return (
    <div
      role="tabpanel"
      id={`tabpanel-${value}`}
      aria-labelledby={`tab-${value}`}
      className={className}
    >
      {children}
    </div>
  )
}
