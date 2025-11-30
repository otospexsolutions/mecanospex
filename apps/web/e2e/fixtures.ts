import { test as base, Page } from '@playwright/test'

// Extend the base test with authenticated session helper
export const test = base.extend<{ authenticatedPage: Page }>({
  authenticatedPage: async ({ page }, use) => {
    // Mock authenticated user - app uses /auth/me endpoint
    await page.route('**/api/v1/auth/me', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: '1',
            name: 'Test User',
            email: 'test@example.com',
            tenant_id: 'tenant-1',
            roles: ['admin'],
          },
        }),
      })
    })

    // Mock dashboard data
    await page.route('**/api/v1/dashboard/**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            revenue: { current: 15000, previous: 12000, change: 25 },
            invoices: { total: 45, pending: 12, overdue: 3 },
            partners: { total: 28, newThisMonth: 5 },
            payments: { received: 35000, pending: 8000 },
          },
        }),
      })
    })

    await use(page)
  },
})

export { expect } from '@playwright/test'

// Common mock data
export const mockDocuments = [
  {
    id: '1',
    document_number: 'INV-2025-0001',
    type: 'invoice',
    status: 'posted',
    partner_id: '1',
    partner_name: 'Acme Corp',
    total_amount: 1500,
    tax_amount: 300,
    net_amount: 1200,
    issue_date: '2025-01-15',
    due_date: '2025-02-15',
    created_at: '2025-01-15T10:00:00Z',
  },
]

export const mockPayments = [
  {
    id: '1',
    payment_number: 'PAY-2025-0001',
    amount: 1500,
    payment_date: '2025-01-15',
    payment_method_id: '1',
    payment_method_name: 'Cash',
    partner_id: '1',
    partner_name: 'Acme Corp',
    status: 'completed',
    created_at: '2025-01-15T10:00:00Z',
  },
]

export const mockPartners = [
  { id: '1', name: 'Acme Corp' },
  { id: '2', name: 'Client Inc' },
]

export const mockPaymentMethods = [
  { id: '1', name: 'Cash', is_physical: false },
  { id: '2', name: 'Check', is_physical: true },
]
