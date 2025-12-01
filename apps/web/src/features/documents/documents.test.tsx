import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, MemoryRouter, Routes, Route } from 'react-router-dom'
import { DocumentListPage } from './DocumentListPage'
import { DocumentDetailPage } from './DocumentDetailPage'
import { DocumentForm } from './DocumentForm'

// Mock the API
const { mockApiGet, mockApiPost, mockApiPatch } = vi.hoisted(() => ({
  mockApiGet: vi.fn(),
  mockApiPost: vi.fn(),
  mockApiPatch: vi.fn(),
}))

vi.mock('../../lib/api', () => ({
  apiGet: mockApiGet,
  apiPost: mockApiPost,
  apiPatch: mockApiPatch,
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

const mockDocuments = [
  {
    id: '1',
    document_number: 'INV-2025-0001',
    type: 'invoice',
    status: 'draft',
    partner_id: 'partner-1',
    partner_name: 'Acme Corp',
    total_amount: 1500.0,
    tax_amount: 300.0,
    net_amount: 1200.0,
    issue_date: '2025-01-15',
    due_date: '2025-02-15',
    created_at: '2025-01-15T10:00:00Z',
  },
  {
    id: '2',
    document_number: 'QUO-2025-0001',
    type: 'quote',
    status: 'draft',
    partner_id: 'partner-2',
    partner_name: 'Client Inc',
    total_amount: 2500.0,
    tax_amount: 500.0,
    net_amount: 2000.0,
    issue_date: '2025-01-10',
    due_date: null,
    created_at: '2025-01-10T10:00:00Z',
  },
]

const mockDocumentDetail = {
  id: '1',
  document_number: 'INV-2025-0001',
  type: 'invoice',
  status: 'draft',
  partner_id: 'partner-1',
  partner_name: 'Acme Corp',
  partner_email: 'contact@acme.com',
  total_amount: 1500.0,
  tax_amount: 300.0,
  net_amount: 1200.0,
  issue_date: '2025-01-15',
  due_date: '2025-02-15',
  notes: 'Thank you for your business',
  lines: [
    {
      id: 'line-1',
      product_id: 'prod-1',
      product_name: 'Oil Filter',
      description: 'Premium oil filter',
      quantity: 2,
      unit_price: 45.0,
      tax_rate: 20,
      line_total: 108.0,
    },
    {
      id: 'line-2',
      product_id: 'prod-2',
      product_name: 'Labor',
      description: 'Installation',
      quantity: 1,
      unit_price: 60.0,
      tax_rate: 20,
      line_total: 72.0,
    },
  ],
  created_at: '2025-01-15T10:00:00Z',
  updated_at: '2025-01-15T10:00:00Z',
}

const mockPartners = [
  { id: 'partner-1', name: 'Acme Corp' },
  { id: 'partner-2', name: 'Client Inc' },
]

describe('Document Management', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('DocumentListPage', () => {
    it('renders the document list page with title', () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<DocumentListPage />, { wrapper: TestWrapper })

      expect(screen.getByRole('heading', { name: /documents/i })).toBeInTheDocument()
    })

    it('displays loading state initially', () => {
      mockApiGet.mockImplementation(
        () => new Promise((resolve) => setTimeout(resolve, 1000))
      )

      render(<DocumentListPage />, { wrapper: TestWrapper })

      expect(screen.getByText(/loading/i)).toBeInTheDocument()
    })

    it('displays list of documents', async () => {
      mockApiGet.mockResolvedValue({ data: mockDocuments, meta: { total: 2 } })

      render(<DocumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('INV-2025-0001')).toBeInTheDocument()
        expect(screen.getByText('QUO-2025-0001')).toBeInTheDocument()
      })
    })

    it('displays empty state when no documents', async () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<DocumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText(/no documents/i)).toBeInTheDocument()
      })
    })

    it('has a button to create new document', () => {
      mockApiGet.mockResolvedValue({ data: [], meta: { total: 0 } })

      render(<DocumentListPage />, { wrapper: TestWrapper })

      expect(screen.getByRole('link', { name: /new document/i })).toBeInTheDocument()
    })

    it('displays document type badges', async () => {
      mockApiGet.mockResolvedValue({ data: mockDocuments, meta: { total: 2 } })

      render(<DocumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('Invoice')).toBeInTheDocument()
        expect(screen.getByText('Quote')).toBeInTheDocument()
      })
    })

    it('displays document status badges', async () => {
      mockApiGet.mockResolvedValue({ data: mockDocuments, meta: { total: 2 } })

      render(<DocumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getAllByText('Draft')).toHaveLength(2)
      })
    })

    it('displays partner name for each document', async () => {
      mockApiGet.mockResolvedValue({ data: mockDocuments, meta: { total: 2 } })

      render(<DocumentListPage />, { wrapper: TestWrapper })

      await waitFor(() => {
        expect(screen.getByText('Acme Corp')).toBeInTheDocument()
        expect(screen.getByText('Client Inc')).toBeInTheDocument()
      })
    })
  })

  describe('DocumentDetailPage', () => {
    it('displays document details', async () => {
      mockApiGet.mockResolvedValue(mockDocumentDetail)

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/documents/1']}>
            <Routes>
              <Route path="/documents/:id" element={<DocumentDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByText('INV-2025-0001')).toBeInTheDocument()
        expect(screen.getByText('Acme Corp')).toBeInTheDocument()
      })
    })

    it('shows loading state while fetching', () => {
      mockApiGet.mockImplementation(
        () => new Promise((resolve) => setTimeout(resolve, 1000))
      )

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/documents/1']}>
            <Routes>
              <Route path="/documents/:id" element={<DocumentDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      expect(screen.getByText(/loading/i)).toBeInTheDocument()
    })

    it('shows error state when document not found', async () => {
      mockApiGet.mockRejectedValue({ response: { status: 404 } })

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/documents/999']}>
            <Routes>
              <Route path="/documents/:id" element={<DocumentDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByText(/not found/i)).toBeInTheDocument()
      })
    })

    it('displays document lines', async () => {
      mockApiGet.mockResolvedValue(mockDocumentDetail)

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/documents/1']}>
            <Routes>
              <Route path="/documents/:id" element={<DocumentDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByText('Oil Filter')).toBeInTheDocument()
        expect(screen.getByText('Labor')).toBeInTheDocument()
      })
    })

    it('has edit and back buttons', async () => {
      mockApiGet.mockResolvedValue(mockDocumentDetail)

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/documents/1']}>
            <Routes>
              <Route path="/documents/:id" element={<DocumentDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByRole('link', { name: /edit/i })).toBeInTheDocument()
        expect(screen.getByRole('link', { name: /back/i })).toBeInTheDocument()
      })
    })

    it('displays totals section', async () => {
      mockApiGet.mockResolvedValue(mockDocumentDetail)

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/documents/1']}>
            <Routes>
              <Route path="/documents/:id" element={<DocumentDetailPage />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByText(/subtotal/i)).toBeInTheDocument()
        // Use getAllByText since "Tax" appears in both totals section and line items table
        expect(screen.getAllByText(/^tax$/i).length).toBeGreaterThan(0)
        expect(screen.getByText('Totals')).toBeInTheDocument()
      })
    })
  })

  describe('DocumentForm', () => {
    it('renders empty form for creating document', () => {
      mockApiGet.mockResolvedValue({ data: mockPartners })

      render(<DocumentForm />, { wrapper: TestWrapper })

      expect(screen.getByLabelText(/type/i)).toBeInTheDocument()
      expect(screen.getByRole('button', { name: /save/i })).toBeInTheDocument()
    })

    it('shows validation errors for required fields', async () => {
      const user = userEvent.setup()
      mockApiGet.mockResolvedValue({ data: mockPartners })

      render(<DocumentForm />, { wrapper: TestWrapper })

      const submitButton = screen.getByRole('button', { name: /save/i })
      await user.click(submitButton)

      await waitFor(() => {
        expect(screen.getByText(/type is required/i)).toBeInTheDocument()
        expect(screen.getByText(/partner is required/i)).toBeInTheDocument()
      })
    })

    it('populates form with document data when editing', async () => {
      // Mock both the document fetch and partners fetch
      mockApiGet.mockImplementation((url: string) => {
        if (url.includes('/documents/')) {
          return Promise.resolve(mockDocumentDetail)
        }
        if (url.includes('/partners')) {
          return Promise.resolve({ data: mockPartners })
        }
        return Promise.resolve({ data: [] })
      })

      render(
        <QueryClientProvider client={createTestQueryClient()}>
          <MemoryRouter initialEntries={['/documents/1/edit']}>
            <Routes>
              <Route path="/documents/:id/edit" element={<DocumentForm />} />
            </Routes>
          </MemoryRouter>
        </QueryClientProvider>
      )

      await waitFor(() => {
        expect(screen.getByDisplayValue('Thank you for your business')).toBeInTheDocument()
      })
    })

    it('has cancel button that navigates back', () => {
      mockApiGet.mockResolvedValue({ data: mockPartners })

      render(<DocumentForm />, { wrapper: TestWrapper })

      expect(screen.getByRole('link', { name: /cancel/i })).toBeInTheDocument()
    })

    it('allows selecting document type', async () => {
      const user = userEvent.setup()
      mockApiGet.mockResolvedValue({ data: mockPartners })

      render(<DocumentForm />, { wrapper: TestWrapper })

      const typeSelect = screen.getByLabelText(/type/i)
      await user.selectOptions(typeSelect, 'invoice')

      expect(typeSelect).toHaveValue('invoice')
    })
  })
})
