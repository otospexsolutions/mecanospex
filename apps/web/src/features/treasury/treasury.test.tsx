import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter } from 'react-router-dom'
import { PaymentListPage } from './PaymentListPage'
import { PaymentForm } from './PaymentForm'
import { InstrumentListPage } from './InstrumentListPage'

// Mock the API
const { mockApiGet, mockApiPost } = vi.hoisted(() => ({
  mockApiGet: vi.fn(),
  mockApiPost: vi.fn(),
}))

vi.mock('../../lib/api', () => ({
  apiGet: mockApiGet,
  apiPost: mockApiPost,
  api: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

const createTestQueryClient = () =>
  new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  })

function TestWrapper({ children }: { children: React.ReactNode }) {
  return (
    <QueryClientProvider client={createTestQueryClient()}>
      <BrowserRouter>{children}</BrowserRouter>
    </QueryClientProvider>
  )
}

const mockPayments = [
  {
    id: '1',
    payment_number: 'PAY-2025-0001',
    amount: 1500.0,
    payment_date: '2025-01-15',
    payment_method_id: 'method-1',
    payment_method_name: 'Cash',
    partner_id: 'partner-1',
    partner_name: 'Acme Corp',
    status: 'completed',
    created_at: '2025-01-15T10:00:00Z',
  },
  {
    id: '2',
    payment_number: 'PAY-2025-0002',
    amount: 2500.0,
    payment_date: '2025-01-16',
    payment_method_id: 'method-2',
    payment_method_name: 'Check',
    partner_id: 'partner-2',
    partner_name: 'Client Inc',
    status: 'pending',
    created_at: '2025-01-16T10:00:00Z',
  },
]

const mockInstruments = [
  {
    id: '1',
    instrument_number: 'CHK-001',
    type: 'check',
    amount: 2500.0,
    issue_date: '2025-01-16',
    maturity_date: '2025-02-16',
    partner_id: 'partner-2',
    partner_name: 'Client Inc',
    status: 'received',
    repository_id: 'repo-1',
    repository_name: 'Main Cash Register',
    created_at: '2025-01-16T10:00:00Z',
  },
  {
    id: '2',
    instrument_number: 'CHK-002',
    type: 'check',
    amount: 1000.0,
    issue_date: '2025-01-10',
    maturity_date: '2025-02-10',
    partner_id: 'partner-1',
    partner_name: 'Acme Corp',
    status: 'deposited',
    repository_id: 'repo-2',
    repository_name: 'Bank Account',
    created_at: '2025-01-10T10:00:00Z',
  },
]

const mockPaymentMethods = [
  { id: 'method-1', name: 'Cash', is_physical: false },
  { id: 'method-2', name: 'Check', is_physical: true },
  { id: 'method-3', name: 'Bank Transfer', is_physical: false },
]

const mockPartners = [
  { id: 'partner-1', name: 'Acme Corp' },
  { id: 'partner-2', name: 'Client Inc' },
]

describe('Treasury Management', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('PaymentListPage', () => {
    it('renders the payment list page with title', () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<PaymentListPage />, { wrapper: TestWrapper })

      expect(screen.getByRole('heading', { name: /payments/i })).toBeInTheDocument()
    })

    it('displays loading state initially', () => {
      mockApiGet.mockImplementation(
        () => new Promise((resolve) => setTimeout(resolve, 1000))
      )

      render(<PaymentListPage />, { wrapper: TestWrapper })

      expect(screen.getByText(/loading/i)).toBeInTheDocument()
    })

    it('displays list of payments', async () => {
      mockApiGet.mockResolvedValue({ data: mockPayments, meta: { total: 2 } })

      render(<PaymentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('PAY-2025-0001')).toBeInTheDocument()
        expect(screen.getByText('PAY-2025-0002')).toBeInTheDocument()
      })
    })

    it('displays empty state when no payments', async () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<PaymentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText(/no payments/i)).toBeInTheDocument()
      })
    })

    it('has a button to record new payment', () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<PaymentListPage />, { wrapper: TestWrapper })

      expect(screen.getByRole('link', { name: /record payment/i })).toBeInTheDocument()
    })

    it('displays payment method for each payment', async () => {
      mockApiGet.mockResolvedValue({ data: mockPayments, meta: { total: 2 } })

      render(<PaymentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('Cash')).toBeInTheDocument()
        expect(screen.getByText('Check')).toBeInTheDocument()
      })
    })

    it('displays payment status badges', async () => {
      mockApiGet.mockResolvedValue({ data: mockPayments, meta: { total: 2 } })

      render(<PaymentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('Completed')).toBeInTheDocument()
        expect(screen.getByText('Pending')).toBeInTheDocument()
      })
    })
  })

  describe('PaymentForm', () => {
    it('renders payment form with required fields', () => {
      mockApiGet.mockImplementation((url: string) => {
        if (url.includes('/payment-methods')) {
          return Promise.resolve({ data: mockPaymentMethods })
        }
        if (url.includes('/partners')) {
          return Promise.resolve({ data: mockPartners })
        }
        return Promise.resolve({ data: [] })
      })

      render(<PaymentForm />, { wrapper: TestWrapper })

      expect(screen.getByLabelText(/amount/i)).toBeInTheDocument()
      expect(screen.getByRole('button', { name: /save/i })).toBeInTheDocument()
    })

    it('shows validation errors for required fields', async () => {
      const user = userEvent.setup()
      mockApiGet.mockImplementation((url: string) => {
        if (url.includes('/payment-methods')) {
          return Promise.resolve({ data: mockPaymentMethods })
        }
        if (url.includes('/partners')) {
          return Promise.resolve({ data: mockPartners })
        }
        return Promise.resolve({ data: [] })
      })

      render(<PaymentForm />, { wrapper: TestWrapper })

      const submitButton = screen.getByRole('button', { name: /save/i })
      await user.click(submitButton)

      await waitFor(() => {
        expect(screen.getByText(/amount is required/i)).toBeInTheDocument()
      })
    })

    it('has cancel button', () => {
      mockApiGet.mockImplementation((url: string) => {
        if (url.includes('/payment-methods')) {
          return Promise.resolve({ data: mockPaymentMethods })
        }
        if (url.includes('/partners')) {
          return Promise.resolve({ data: mockPartners })
        }
        return Promise.resolve({ data: [] })
      })

      render(<PaymentForm />, { wrapper: TestWrapper })

      expect(screen.getByRole('link', { name: /cancel/i })).toBeInTheDocument()
    })

    it('allows selecting payment method', async () => {
      const user = userEvent.setup()
      mockApiGet.mockImplementation((url: string) => {
        if (url.includes('/payment-methods')) {
          return Promise.resolve({ data: mockPaymentMethods })
        }
        if (url.includes('/partners')) {
          return Promise.resolve({ data: mockPartners })
        }
        return Promise.resolve({ data: [] })
      })

      render(<PaymentForm />, { wrapper: TestWrapper })

      // Wait for the options to be populated
      await waitFor(() => {
        expect(screen.getByRole('option', { name: 'Cash' })).toBeInTheDocument()
      })

      const methodSelect = screen.getByLabelText(/payment method/i)
      await user.selectOptions(methodSelect, 'method-1')

      expect(methodSelect).toHaveValue('method-1')
    })
  })

  describe('InstrumentListPage', () => {
    it('renders the instrument list page with title', () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<InstrumentListPage />, { wrapper: TestWrapper })

      expect(screen.getByRole('heading', { name: /instruments/i })).toBeInTheDocument()
    })

    it('displays loading state initially', () => {
      mockApiGet.mockImplementation(
        () => new Promise((resolve) => setTimeout(resolve, 1000))
      )

      render(<InstrumentListPage />, { wrapper: TestWrapper })

      expect(screen.getByText(/loading/i)).toBeInTheDocument()
    })

    it('displays list of instruments', async () => {
      mockApiGet.mockResolvedValue({ data: mockInstruments, meta: { total: 2 } })

      render(<InstrumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('CHK-001')).toBeInTheDocument()
        expect(screen.getByText('CHK-002')).toBeInTheDocument()
      })
    })

    it('displays empty state when no instruments', async () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<InstrumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText(/no instruments/i)).toBeInTheDocument()
      })
    })

    it('displays instrument status badges', async () => {
      mockApiGet.mockResolvedValue({ data: mockInstruments, meta: { total: 2 } })

      render(<InstrumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('Received')).toBeInTheDocument()
        expect(screen.getByText('Deposited')).toBeInTheDocument()
      })
    })

    it('displays maturity dates', async () => {
      mockApiGet.mockResolvedValue({ data: mockInstruments, meta: { total: 2 } })

      render(<InstrumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        // Check that dates are displayed
        expect(screen.getAllByText(/2025/i).length).toBeGreaterThan(0)
      })
    })
  })
})
