import { test, expect, mockDocuments, mockPartners, mockPayments } from './fixtures'

test.describe('Document Management', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // Common mocks for document tests
    await page.route('**/api/v1/payments**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPayments } }),
      })
    })
  })

  test('should display document list', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockDocuments, meta: { total: 1 } } }),
      })
    })

    await page.goto('/documents')

    await expect(page.getByRole('heading', { name: /documents/i })).toBeVisible()
    await expect(page.getByText('INV-2025-0001')).toBeVisible()
    await expect(page.getByText('Acme Corp')).toBeVisible()
  })

  test('should show empty state when no documents', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: [], meta: { total: 0 } } }),
      })
    })

    await page.goto('/documents')

    await expect(page.getByText(/no documents/i)).toBeVisible()
  })

  test('should navigate to new document form', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: [], meta: { total: 0 } } }),
      })
    })

    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPartners } }),
      })
    })

    await page.goto('/documents')
    await page.getByRole('link', { name: /new document/i }).click()

    await expect(page).toHaveURL(/\/documents\/new/)
    await expect(page.getByRole('heading', { name: /new document/i })).toBeVisible()
  })

  test('should create a new invoice', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents', (route) => {
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
          body: JSON.stringify({ data: { data: [], meta: { total: 0 } } }),
        })
      }
    })

    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPartners } }),
      })
    })

    await page.goto('/documents/new')

    // Fill out the form
    await page.getByLabel(/type/i).selectOption('invoice')
    await page.getByLabel(/partner/i).selectOption('1')
    await page.getByLabel(/notes/i).fill('Test invoice notes')

    // Submit
    await page.getByRole('button', { name: /save/i }).click()

    // Should redirect to documents list
    await expect(page).toHaveURL('/documents')
  })

  test('should show validation errors on form submit', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPartners } }),
      })
    })

    await page.goto('/documents/new')

    // Submit without filling required fields
    await page.getByRole('button', { name: /save/i }).click()

    // Should show validation errors
    await expect(page.getByText(/type is required/i)).toBeVisible()
    await expect(page.getByText(/partner is required/i)).toBeVisible()
  })
})
