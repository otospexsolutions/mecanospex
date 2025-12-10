import { test, expect, mockDocuments, mockPartners, mockPayments } from './fixtures'

test.describe('Invoice Management', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // Common mocks for invoice tests
    await page.route('**/api/v1/payments**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPayments }),
      })
    })
  })

  test('should display invoice list', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/invoices**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockDocuments, meta: { total: 1 } }),
      })
    })

    await page.goto('/sales/invoices')

    await expect(page.getByRole('heading', { name: /invoices/i })).toBeVisible()
    await expect(page.getByText('INV-2025-0001')).toBeVisible()
    await expect(page.getByText('Acme Corp')).toBeVisible()
  })

  test('should show empty state when no invoices', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/invoices**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: [], meta: { total: 0 } }),
      })
    })

    await page.goto('/sales/invoices')

    await expect(page.getByText(/no.*invoices/i)).toBeVisible()
  })

  test('should navigate to new invoice form', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/invoices**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: [], meta: { total: 0 } }),
      })
    })

    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPartners }),
      })
    })

    await page.goto('/sales/invoices')

    // Wait for page to load and click the add button
    await page.waitForLoadState('networkidle')
    const addButton = page.locator('a[href*="/sales/invoices/new"], button:has-text("Add Invoice")').first()
    await addButton.click()

    // Wait for navigation and verify form is visible
    await page.waitForURL(/\/sales\/invoices\/new/, { timeout: 10000 })
    await expect(page.getByRole('heading', { name: /add invoice/i, level: 1 })).toBeVisible({ timeout: 10000 })
  })

  test('should create a new invoice', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/invoices', (route) => {
      if (route.request().method() === 'POST') {
        route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: '123',
              document_number: 'INV-2025-0002',
              type: 'invoice',
              status: 'draft',
            },
          }),
        })
      } else {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: [], meta: { total: 0 } }),
        })
      }
    })

    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPartners }),
      })
    })

    await page.goto('/sales/invoices/new')

    // Fill out the form
    await page.getByLabel(/customer/i).selectOption('1')
    await page.getByLabel(/notes/i).fill('Test invoice notes')

    // Submit
    await page.getByRole('button', { name: /save/i }).click()

    // Should redirect to invoice detail or list
    await expect(page).toHaveURL(/\/sales\/invoices/)
  })

  test('should show validation errors on form submit', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPartners }),
      })
    })

    await page.goto('/sales/invoices/new')

    // Submit without filling required fields
    await page.getByRole('button', { name: /save/i }).click()

    // Should show validation errors via toast
    await expect(page.getByText(/customer.*required|partner.*required/i)).toBeVisible({ timeout: 10000 })
  })
})
