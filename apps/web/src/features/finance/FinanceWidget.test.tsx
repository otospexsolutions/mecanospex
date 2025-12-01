import { render, screen, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { MemoryRouter } from 'react-router-dom'
import { FinanceWidget } from './components/FinanceWidget'

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

describe('FinanceWidget', () => {
  let queryClient: QueryClient

  beforeEach(() => {
    queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false },
      },
    })
    vi.clearAllMocks()
  })

  it('renders widget title', () => {
    mockApiGet.mockResolvedValue({
      total_assets: '0.00',
      total_liabilities: '0.00',
      total_equity: '0.00',
      net_income_mtd: '0.00',
      net_income_ytd: '0.00',
      accounts_receivable: '0.00',
      accounts_payable: '0.00',
    })

    render(
      <MemoryRouter>
        <QueryClientProvider client={queryClient}>
          <FinanceWidget />
        </QueryClientProvider>
      </MemoryRouter>
    )

    expect(screen.getByText(/Finance Overview/i)).toBeInTheDocument()
  })

  it('shows loading state', () => {
    mockApiGet.mockImplementation(
      () => new Promise(() => {}) // Never resolves
    )

    render(
      <MemoryRouter>
        <QueryClientProvider client={queryClient}>
          <FinanceWidget />
        </QueryClientProvider>
      </MemoryRouter>
    )

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('displays total assets', async () => {
    const mockData = {
      total_assets: '50000.00',
      total_liabilities: '20000.00',
      total_equity: '30000.00',
      net_income_mtd: '5000.00',
      net_income_ytd: '25000.00',
      accounts_receivable: '10000.00',
      accounts_payable: '8000.00',
    }

    mockApiGet.mockResolvedValue(mockData)

    render(
      <MemoryRouter>
        <QueryClientProvider client={queryClient}>
          <FinanceWidget />
        </QueryClientProvider>
      </MemoryRouter>
    )

    await waitFor(() => {
      expect(screen.getByText(/Total Assets/i)).toBeInTheDocument()
      expect(screen.getByText((_content, element) => {
        return element?.textContent === '$50,000.00'
      })).toBeInTheDocument()
    })
  })

  it('displays total liabilities', async () => {
    const mockData = {
      total_assets: '50000.00',
      total_liabilities: '20000.00',
      total_equity: '30000.00',
      net_income_mtd: '5000.00',
      net_income_ytd: '25000.00',
      accounts_receivable: '10000.00',
      accounts_payable: '8000.00',
    }

    mockApiGet.mockResolvedValue(mockData)

    render(
      <MemoryRouter>
        <QueryClientProvider client={queryClient}>
          <FinanceWidget />
        </QueryClientProvider>
      </MemoryRouter>
    )

    await waitFor(() => {
      expect(screen.getByText(/Total Liabilities/i)).toBeInTheDocument()
      expect(screen.getByText((_content, element) => {
        return element?.textContent === '$20,000.00'
      })).toBeInTheDocument()
    })
  })

  it('displays net income MTD', async () => {
    const mockData = {
      total_assets: '50000.00',
      total_liabilities: '20000.00',
      total_equity: '30000.00',
      net_income_mtd: '5000.00',
      net_income_ytd: '25000.00',
      accounts_receivable: '10000.00',
      accounts_payable: '8000.00',
    }

    mockApiGet.mockResolvedValue(mockData)

    render(
      <MemoryRouter>
        <QueryClientProvider client={queryClient}>
          <FinanceWidget />
        </QueryClientProvider>
      </MemoryRouter>
    )

    await waitFor(() => {
      expect(screen.getByText(/Net Income.*MTD/i)).toBeInTheDocument()
      expect(screen.getByText((_content, element) => {
        return element?.textContent === '$5,000.00'
      })).toBeInTheDocument()
    })
  })

  it('displays accounts receivable', async () => {
    const mockData = {
      total_assets: '50000.00',
      total_liabilities: '20000.00',
      total_equity: '30000.00',
      net_income_mtd: '5000.00',
      net_income_ytd: '25000.00',
      accounts_receivable: '10000.00',
      accounts_payable: '8000.00',
    }

    mockApiGet.mockResolvedValue(mockData)

    render(
      <MemoryRouter>
        <QueryClientProvider client={queryClient}>
          <FinanceWidget />
        </QueryClientProvider>
      </MemoryRouter>
    )

    await waitFor(() => {
      expect(screen.getByText(/Accounts Receivable/i)).toBeInTheDocument()
      expect(screen.getByText((_content, element) => {
        return element?.textContent === '$10,000.00'
      })).toBeInTheDocument()
    })
  })

  it('displays accounts payable', async () => {
    const mockData = {
      total_assets: '50000.00',
      total_liabilities: '20000.00',
      total_equity: '30000.00',
      net_income_mtd: '5000.00',
      net_income_ytd: '25000.00',
      accounts_receivable: '10000.00',
      accounts_payable: '8000.00',
    }

    mockApiGet.mockResolvedValue(mockData)

    render(
      <MemoryRouter>
        <QueryClientProvider client={queryClient}>
          <FinanceWidget />
        </QueryClientProvider>
      </MemoryRouter>
    )

    await waitFor(() => {
      expect(screen.getByText(/Accounts Payable/i)).toBeInTheDocument()
      expect(screen.getByText((_content, element) => {
        return element?.textContent === '$8,000.00'
      })).toBeInTheDocument()
    })
  })

  it('has link to full finance reports', async () => {
    mockApiGet.mockResolvedValue({
      total_assets: '0.00',
      total_liabilities: '0.00',
      total_equity: '0.00',
      net_income_mtd: '0.00',
      net_income_ytd: '0.00',
      accounts_receivable: '0.00',
      accounts_payable: '0.00',
    })

    render(
      <MemoryRouter>
        <QueryClientProvider client={queryClient}>
          <FinanceWidget />
        </QueryClientProvider>
      </MemoryRouter>
    )

    await waitFor(() => {
      expect(screen.getByText(/View Reports/i)).toBeInTheDocument()
    })
  })
})
