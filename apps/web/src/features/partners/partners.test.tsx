import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, MemoryRouter, Routes, Route } from 'react-router-dom'
import { PartnerListPage } from './PartnerListPage'
import { PartnerDetailPage } from './PartnerDetailPage'
import { PartnerForm } from './PartnerForm'

// Mock the API - must use vi.hoisted for variables used in vi.mock factory
const { mockApiGet, mockApiPost, mockApiPatch, mockApiDelete } = vi.hoisted(() => ({
  mockApiGet: vi.fn(),
  mockApiPost: vi.fn(),
  mockApiPatch: vi.fn(),
  mockApiDelete: vi.fn(),
}))

vi.mock('../../lib/api', () => ({
  apiGet: mockApiGet,
  apiPost: mockApiPost,
  apiPatch: mockApiPatch,
  apiDelete: mockApiDelete,
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

const mockPartners = [
  {
    id: '1',
    name: 'Acme Corp',
    type: 'customer',
    email: 'contact@acme.com',
    phone: '+1234567890',
    created_at: '2025-01-01T00:00:00Z',
  },
  {
    id: '2',
    name: 'Supplier Inc',
    type: 'supplier',
    email: 'info@supplier.com',
    phone: '+0987654321',
    created_at: '2025-01-02T00:00:00Z',
  },
]

describe('Partner Management', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('PartnerListPage', () => {
    it('renders the partner list page with title', () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<PartnerListPage />, { wrapper: TestWrapper })

      expect(screen.getByRole('heading', { name: /partners/i })).toBeInTheDocument()
    })

    it('displays loading state initially', () => {
      mockApiGet.mockImplementation(
        () => new Promise((resolve) => setTimeout(resolve, 1000))
      )

      render(<PartnerListPage />, { wrapper: TestWrapper })

      expect(screen.getByText(/loading/i)).toBeInTheDocument()
    })

    it('displays list of partners', async () => {
      mockApiGet.mockResolvedValue({ data: mockPartners, meta: { total: 2 } })

      render(<PartnerListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('Acme Corp')).toBeInTheDocument()
        expect(screen.getByText('Supplier Inc')).toBeInTheDocument()
      })
    })

    it('displays empty state when no partners', async () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<PartnerListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText(/no partners/i)).toBeInTheDocument()
      })
    })

    it('has a button to add new partner', () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<PartnerListPage />, { wrapper: TestWrapper })

      expect(screen.getByRole('link', { name: /add partner/i })).toBeInTheDocument()
    })

    it('displays partner type badges', async () => {
      mockApiGet.mockResolvedValue({ data: mockPartners, meta: { total: 2 } })

      render(<PartnerListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('Customer')).toBeInTheDocument()
        expect(screen.getByText('Supplier')).toBeInTheDocument()
      })
    })
  })

  describe('PartnerDetailPage', () => {
    it('displays partner details', async () => {
      mockApiGet.mockResolvedValue(mockPartners[0])

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/partners/1']}>
            <Routes>
              <Route path="/partners/:id" element={<PartnerDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByText('Acme Corp')).toBeInTheDocument()
        expect(screen.getByText('contact@acme.com')).toBeInTheDocument()
      })
    })

    it('shows loading state while fetching', () => {
      mockApiGet.mockImplementation(
        () => new Promise((resolve) => setTimeout(resolve, 1000))
      )

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/partners/1']}>
            <Routes>
              <Route path="/partners/:id" element={<PartnerDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      expect(screen.getByText(/loading/i)).toBeInTheDocument()
    })

    it('shows error state when partner not found', async () => {
      mockApiGet.mockRejectedValue({ response: { status: 404 } })

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/partners/999']}>
            <Routes>
              <Route path="/partners/:id" element={<PartnerDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByText(/not found/i)).toBeInTheDocument()
      })
    })

    it('has edit and back buttons', async () => {
      mockApiGet.mockResolvedValue(mockPartners[0])

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/partners/1']}>
            <Routes>
              <Route path="/partners/:id" element={<PartnerDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByRole('link', { name: /edit/i })).toBeInTheDocument()
        expect(screen.getByRole('link', { name: /back/i })).toBeInTheDocument()
      })
    })
  })

  describe('PartnerForm', () => {
    it('renders empty form for creating partner', () => {
      render(<PartnerForm />, { wrapper: TestWrapper })

      expect(screen.getByLabelText(/name/i)).toHaveValue('')
      expect(screen.getByLabelText(/email/i)).toHaveValue('')
      expect(screen.getByRole('button', { name: /save/i })).toBeInTheDocument()
    })

    it('shows validation errors for required fields', async () => {
      const user = userEvent.setup()
      render(<PartnerForm />, { wrapper: TestWrapper })

      const submitButton = screen.getByRole('button', { name: /save/i })
      await user.click(submitButton)

      await waitFor(() => {
        expect(screen.getByText(/name is required/i)).toBeInTheDocument()
        expect(screen.getByText(/type is required/i)).toBeInTheDocument()
      })
    })

    it('populates form with partner data when editing', async () => {
      mockApiGet.mockResolvedValue(mockPartners[0])

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/partners/1/edit']}>
            <Routes>
              <Route path="/partners/:id/edit" element={<PartnerForm />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByLabelText(/name/i)).toHaveValue('Acme Corp')
        expect(screen.getByLabelText(/email/i)).toHaveValue('contact@acme.com')
      })
    })

    it('submits form data on save', async () => {
      const user = userEvent.setup()
      mockApiPost.mockResolvedValue({ id: '3', name: 'New Partner' })

      render(<PartnerForm />, { wrapper: TestWrapper })

      await user.type(screen.getByLabelText(/name/i), 'New Partner')
      await user.selectOptions(screen.getByLabelText(/type/i), 'customer')
      await user.type(screen.getByLabelText(/email/i), 'new@partner.com')

      const submitButton = screen.getByRole('button', { name: /save/i })
      await user.click(submitButton)

      await waitFor(() => {
        expect(mockApiPost).toHaveBeenCalledWith('/partners', expect.objectContaining({
          name: 'New Partner',
          type: 'customer',
          email: 'new@partner.com',
        }))
      })
    })

    it('has cancel button that navigates back', () => {
      render(<PartnerForm />, { wrapper: TestWrapper })

      expect(screen.getByRole('link', { name: /cancel/i })).toBeInTheDocument()
    })
  })
})
