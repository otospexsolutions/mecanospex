import { render, screen, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { TrialBalancePage } from './pages/TrialBalancePage'

const { mockApiGet } = vi.hoisted(() => ({
  mockApiGet: vi.fn(),
}))

vi.mock('@/lib/api', () => ({
  apiGet: mockApiGet,
}))

vi.mock('@/hooks/usePermissions', () => ({
  usePermissions: () => ({
    hasPermission: () => true,
  }),
}))

describe('TrialBalancePage', () => {
  let queryClient: QueryClient

  beforeEach(() => {
    queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false },
      },
    })
    vi.clearAllMocks()
  })

  it('renders page title', () => {
    mockApiGet.mockResolvedValue([])

    render(
      <QueryClientProvider client={queryClient}>
        <TrialBalancePage />
      </QueryClientProvider>
    )

    expect(screen.getByText('Trial Balance')).toBeInTheDocument()
  })

  it('shows loading state', () => {
    mockApiGet.mockImplementation(
      () => new Promise(() => {}) // Never resolves
    )

    render(
      <QueryClientProvider client={queryClient}>
        <TrialBalancePage />
      </QueryClientProvider>
    )

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('displays trial balance data', async () => {
    const mockData = [
      {
        account_code: '1000',
        account_name: 'Cash',
        account_type: 'asset',
        debit: '5000.00',
        credit: '0.00',
      },
      {
        account_code: '3000',
        account_name: 'Capital',
        account_type: 'equity',
        debit: '0.00',
        credit: '5000.00',
      },
    ]

    mockApiGet.mockResolvedValue(mockData)

    render(
      <QueryClientProvider client={queryClient}>
        <TrialBalancePage />
      </QueryClientProvider>
    )

    await waitFor(() => {
      expect(screen.getByText('1000')).toBeInTheDocument()
      expect(screen.getByText('Cash')).toBeInTheDocument()
      expect(screen.getByText('3000')).toBeInTheDocument()
      expect(screen.getByText('Capital')).toBeInTheDocument()
    })
  })

  it('displays totals row', async () => {
    const mockData = [
      {
        account_code: '1000',
        account_name: 'Cash',
        account_type: 'asset',
        debit: '5000.00',
        credit: '0.00',
      },
      {
        account_code: '3000',
        account_name: 'Capital',
        account_type: 'equity',
        debit: '0.00',
        credit: '5000.00',
      },
    ]

    mockApiGet.mockResolvedValue(mockData)

    render(
      <QueryClientProvider client={queryClient}>
        <TrialBalancePage />
      </QueryClientProvider>
    )

    await waitFor(() => {
      expect(screen.getByText('Total')).toBeInTheDocument()
      // Total debits and credits should both be 5000.00
      const amounts = screen.getAllByText('5,000.00')
      expect(amounts.length).toBeGreaterThanOrEqual(2)
    })
  })

  it('has export button', () => {
    mockApiGet.mockResolvedValue([])

    render(
      <QueryClientProvider client={queryClient}>
        <TrialBalancePage />
      </QueryClientProvider>
    )

    expect(screen.getByText(/export/i)).toBeInTheDocument()
  })

  it('has date filter', () => {
    mockApiGet.mockResolvedValue([])

    render(
      <QueryClientProvider client={queryClient}>
        <TrialBalancePage />
      </QueryClientProvider>
    )

    expect(screen.getByLabelText(/as of date/i)).toBeInTheDocument()
  })
})
