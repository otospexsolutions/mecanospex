import { test, expect, mockPayments, mockPartners, mockPaymentMethods } from './fixtures'

test.describe('Payment Management', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // Common mocks for payment tests
    await page.route('**/api/v1/documents**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: [] } }),
      })
    })
  })

  test('should display payment list', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/payments**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPayments, meta: { total: 1 } } }),
      })
    })

    await page.goto('/treasury/payments')

    await expect(page.getByRole('heading', { name: /payments/i })).toBeVisible()
    await expect(page.getByText('PAY-2025-0001')).toBeVisible()
    await expect(page.getByText('Acme Corp')).toBeVisible()
    await expect(page.getByText('Cash')).toBeVisible()
  })

  test('should show empty state when no payments', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/payments**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: [], meta: { total: 0 } } }),
      })
    })

    await page.goto('/treasury/payments')

    await expect(page.getByText(/no payments/i)).toBeVisible()
  })

  test('should navigate to record payment form', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/payments**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: [], meta: { total: 0 } } }),
      })
    })

    await page.route('**/api/v1/payment-methods**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPaymentMethods } }),
      })
    })

    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPartners } }),
      })
    })

    await page.goto('/treasury/payments')
    await page.getByRole('link', { name: /record payment/i }).click()

    await expect(page).toHaveURL(/\/treasury\/payments\/new/)
    await expect(page.getByRole('heading', { name: /record payment/i })).toBeVisible()
  })

  test('should record a new payment', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/payments', (route) => {
      if (route.request().method() === 'POST') {
        route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: '123',
              payment_number: 'PAY-2025-0002',
              amount: 500,
              status: 'completed',
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

    await page.route('**/api/v1/payment-methods**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPaymentMethods } }),
      })
    })

    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPartners } }),
      })
    })

    await page.goto('/treasury/payments/new')

    // Wait for form to load
    await expect(page.getByLabel(/amount/i)).toBeVisible()

    // Fill out the form
    await page.getByLabel(/amount/i).fill('500')
    await page.getByLabel(/payment method/i).selectOption('1')
    await page.getByLabel(/partner/i).selectOption('1')

    // Submit
    await page.getByRole('button', { name: /save/i }).click()

    // Should redirect to payments list
    await expect(page).toHaveURL('/treasury/payments')
  })

  test('should show validation error for missing amount', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/payment-methods**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPaymentMethods } }),
      })
    })

    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { data: mockPartners } }),
      })
    })

    await page.goto('/treasury/payments/new')

    // Wait for form to load
    await expect(page.getByLabel(/amount/i)).toBeVisible()

    // Submit without amount
    await page.getByRole('button', { name: /save/i }).click()

    // Should show validation error
    await expect(page.getByText(/amount is required/i)).toBeVisible()
  })
})
