/**
 * AllocationPreview Component Tests
 * TDD: Tests written FIRST before implementation
 */

import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { AllocationPreview } from './AllocationPreview'
import { AllocationMethod, type PaymentAllocationPreview } from '@/types/treasury'

// Mock the translation hook
vi.mock('react-i18next', () => ({
  useTranslation: () => ({
    t: (key: string, params?: Record<string, unknown>) => {
      // Handle both namespaced (common:status.current) and non-namespaced (smartPayment.preview.title) keys
      // Non-namespaced keys default to 'treasury' namespace when using useTranslation(['treasury', 'common'])
      const translations: Record<string, string> = {
        // Treasury namespace (can be called without prefix)
        'smartPayment.preview.title': 'Allocation Preview',
        'smartPayment.preview.invoiceNumber': 'Invoice',
        'smartPayment.preview.originalBalance': 'Balance',
        'smartPayment.preview.allocated': 'Allocated',
        'smartPayment.preview.daysOverdue': '{{days}} days overdue',
        'smartPayment.preview.totalToInvoices': 'Total to Invoices',
        'smartPayment.preview.toleranceWriteoff': 'Tolerance Write-off',
        'smartPayment.preview.excessAmount': 'Excess Amount',
        'smartPayment.preview.excessHandling.creditBalance': 'Will be kept as credit balance',
        'smartPayment.preview.excessHandling.toleranceWriteoff': 'Will be written off (within tolerance)',
        'smartPayment.preview.noAllocations': 'No invoices allocated',
        'treasury:smartPayment.preview.daysOverdue': '{{days}} days overdue',
        // Common namespace (must be called with prefix)
        'common:status.current': 'Current',
        'common:status.overdue': 'Overdue',
        'common.loading': 'Loading preview...',
      }
      let result = translations[key] || key
      if (params) {
        Object.entries(params).forEach(([k, v]) => {
          result = result.replace(`{{${k}}}`, String(v))
        })
      }
      return result
    },
  }),
}))

describe('AllocationPreview', () => {
  it('renders allocation table with invoice details', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.FIFO,
      allocations: [
        {
          document_id: '1',
          document_number: 'INV-00001',
          amount: '100.0000',
          original_balance: '150.0000',
          days_overdue: 0,
        },
        {
          document_id: '2',
          document_number: 'INV-00002',
          amount: '50.0000',
          original_balance: '75.0000',
          days_overdue: 15,
        },
      ],
      total_to_invoices: '150.0000',
      excess_amount: '0.0000',
      excess_handling: 'credit_balance',
      tolerance_settings: {
        enabled: true,
        percentage: '0.0050',
        max_amount: '0.5000',
        source: 'system',
      },
    }

    render(<AllocationPreview preview={preview} />)

    expect(screen.getByText('Allocation Preview')).toBeInTheDocument()
    expect(screen.getByText('INV-00001')).toBeInTheDocument()
    expect(screen.getByText('INV-00002')).toBeInTheDocument()
    expect(screen.getByText('100.00')).toBeInTheDocument() // Allocated amount for INV-00001
    expect(screen.getAllByText('150.00')).toHaveLength(2) // Original balance for INV-00001 AND total
    expect(screen.getByText('50.00')).toBeInTheDocument() // Allocated amount for INV-00002
    expect(screen.getByText('75.00')).toBeInTheDocument() // Original balance for INV-00002
    expect(screen.getByText('15 days overdue')).toBeInTheDocument()
  })

  it('displays tolerance write-off when excess is within tolerance', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.FIFO,
      allocations: [
        {
          document_id: '1',
          document_number: 'INV-00001',
          amount: '100.0000',
          original_balance: '100.5000',
          days_overdue: 0,
        },
      ],
      total_to_invoices: '100.0000',
      excess_amount: '0.5000',
      excess_handling: 'tolerance_writeoff',
      tolerance_settings: {
        enabled: true,
        percentage: '0.0050',
        max_amount: '0.5000',
        source: 'system',
      },
    }

    render(<AllocationPreview preview={preview} />)

    expect(screen.getByText('Tolerance Write-off')).toBeInTheDocument()
    expect(screen.getByText('0.50')).toBeInTheDocument()
    expect(screen.getByText('Will be written off (within tolerance)')).toBeInTheDocument()
  })

  it('displays credit balance when excess exceeds tolerance', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.MANUAL,
      allocations: [
        {
          document_id: '1',
          document_number: 'INV-00001',
          amount: '100.0000',
          original_balance: '100.0000',
          days_overdue: 5,
        },
      ],
      total_to_invoices: '100.0000',
      excess_amount: '50.0000',
      excess_handling: 'credit_balance',
      tolerance_settings: {
        enabled: true,
        percentage: '0.0050',
        max_amount: '0.5000',
        source: 'system',
      },
    }

    render(<AllocationPreview preview={preview} />)

    expect(screen.getByText('Excess Amount')).toBeInTheDocument()
    expect(screen.getByText('50.00')).toBeInTheDocument()
    expect(screen.getByText('Will be kept as credit balance')).toBeInTheDocument()
  })

  it('shows current status for invoices not overdue', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.FIFO,
      allocations: [
        {
          document_id: '1',
          document_number: 'INV-00001',
          amount: '100.0000',
          original_balance: '100.0000',
          days_overdue: 0,
        },
      ],
      total_to_invoices: '100.0000',
      excess_amount: '0.0000',
      excess_handling: 'credit_balance',
      tolerance_settings: {
        enabled: false,
        percentage: '0.0000',
        max_amount: '0.0000',
        source: 'company',
      },
    }

    render(<AllocationPreview preview={preview} />)

    expect(screen.getByText('Current')).toBeInTheDocument()
  })

  it('renders empty state when no allocations', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.MANUAL,
      allocations: [],
      total_to_invoices: '0.0000',
      excess_amount: '0.0000',
      excess_handling: 'credit_balance',
      tolerance_settings: {
        enabled: false,
        percentage: '0.0000',
        max_amount: '0.0000',
        source: 'system',
      },
    }

    render(<AllocationPreview preview={preview} />)

    expect(screen.getByText('No invoices allocated')).toBeInTheDocument()
  })

  it('displays loading state when isLoading is true', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.FIFO,
      allocations: [],
      total_to_invoices: '0.0000',
      excess_amount: '0.0000',
      excess_handling: 'credit_balance',
      tolerance_settings: {
        enabled: false,
        percentage: '0.0000',
        max_amount: '0.0000',
        source: 'system',
      },
    }

    render(<AllocationPreview preview={preview} isLoading={true} />)

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('calculates and displays total to invoices correctly', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.FIFO,
      allocations: [
        {
          document_id: '1',
          document_number: 'INV-00001',
          amount: '100.0000',
          original_balance: '100.0000',
          days_overdue: 0,
        },
        {
          document_id: '2',
          document_number: 'INV-00002',
          amount: '50.0000',
          original_balance: '50.0000',
          days_overdue: 0,
        },
        {
          document_id: '3',
          document_number: 'INV-00003',
          amount: '25.5000',
          original_balance: '25.5000',
          days_overdue: 0,
        },
      ],
      total_to_invoices: '175.5000',
      excess_amount: '0.0000',
      excess_handling: 'credit_balance',
      tolerance_settings: {
        enabled: false,
        percentage: '0.0000',
        max_amount: '0.0000',
        source: 'system',
      },
    }

    render(<AllocationPreview preview={preview} />)

    expect(screen.getByText('Total to Invoices')).toBeInTheDocument()
    expect(screen.getByText('175.50')).toBeInTheDocument()
  })

  it('formats amounts correctly with 2 decimals', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.FIFO,
      allocations: [
        {
          document_id: '1',
          document_number: 'INV-00001',
          amount: '100.5050',
          original_balance: '150.2500',
          days_overdue: 0,
        },
      ],
      total_to_invoices: '100.5050',
      excess_amount: '0.0000',
      excess_handling: 'credit_balance',
      tolerance_settings: {
        enabled: false,
        percentage: '0.0000',
        max_amount: '0.0000',
        source: 'system',
      },
    }

    render(<AllocationPreview preview={preview} />)

    // Amounts should be formatted to 2 decimals
    // 100.5050 rounds to 100.50 (not 100.51 - it just removes trailing zeros)
    expect(screen.getAllByText('100.50')).toHaveLength(2) // Allocated amount AND total (both same value)
    expect(screen.getByText('150.25')).toBeInTheDocument() // Original balance
  })

  it('displays overdue badge for overdue invoices', () => {
    const preview: PaymentAllocationPreview = {
      allocation_method: AllocationMethod.DUE_DATE,
      allocations: [
        {
          document_id: '1',
          document_number: 'INV-00001',
          amount: '100.0000',
          original_balance: '100.0000',
          days_overdue: 30,
        },
      ],
      total_to_invoices: '100.0000',
      excess_amount: '0.0000',
      excess_handling: 'credit_balance',
      tolerance_settings: {
        enabled: false,
        percentage: '0.0000',
        max_amount: '0.0000',
        source: 'system',
      },
    }

    render(<AllocationPreview preview={preview} />)

    expect(screen.getByText('30 days overdue')).toBeInTheDocument()
    expect(screen.getByText('Overdue')).toBeInTheDocument()
  })
})
