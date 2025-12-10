/**
 * Smart Payment E2E Tests - Using Real Seeded Data
 *
 * PREREQUISITES:
 * 1. Run: php artisan db:seed --class=DatabaseSeeder
 * 2. Run: php artisan db:seed --class=SmartPaymentTestDataSeeder
 * 3. Start API server: php artisan serve
 * 4. Start web server: pnpm dev
 *
 * TEST DATA EXPECTED:
 * - User: test@example.com / password
 * - Customer with open invoices (TEST-INV-001, TEST-INV-002, TEST-INV-003)
 * - Invoice for credit note: TEST-INV-CREDIT
 * - Payment methods and repositories (from DatabaseSeeder)
 *
 * NO MOCKING - These tests use real API calls and seeded database data
 */

import { test, expect } from './fixtures'

test.describe('Smart Payment - Payment Allocation (Real Data)', () => {
  test.beforeEach(async ({ page }) => {
    // Ensure we're starting from a clean state
    // Note: The authenticatedPage fixture already handles login
  })

  test('should create payment with FIFO allocation @smoke', async ({ authenticatedPage: page }) => {
    const paymentAmount = '5000.00'

    // Navigate to new payment page
    await page.goto('/treasury/payments/new')
    await expect(page).toHaveURL('/treasury/payments/new')

    // STEP 1: Fill and submit payment form
    await page.getByLabel(/amount/i).fill(paymentAmount)

    // Select first available payment method (from seeded data)
    await page.getByLabel(/payment method/i).click()
    await page.locator('select[name*="payment_method"], select[id*="payment_method"]').selectOption({ index: 1 })

    // Select first available repository (from seeded data)
    await page.getByLabel(/repository/i).click()
    await page.locator('select[name*="repository"], select[id*="repository"]').selectOption({ index: 1 })

    // Select customer with test invoices (from SmartPaymentTestDataSeeder)
    // The first customer in the list should have our test invoices
    await page.getByLabel(/partner/i).click()
    const partnerSelect = page.locator('select[name*="partner"], select[id*="partner"]')
    await partnerSelect.selectOption({ index: 1 })

    // Wait for date field to be visible and set today's date
    const dateField = page.getByLabel(/payment date/i)
    await expect(dateField).toBeVisible()
    await dateField.fill(new Date().toISOString().split('T')[0])

    // Submit payment creation
    await page.getByRole('button', { name: /^save$/i }).click()

    // Wait for API response
    await page.waitForResponse((resp) =>
      resp.url().includes('/api/v1/payments') &&
      resp.request().method() === 'POST' &&
      resp.status() === 201
    , { timeout: 10000 })

    // STEP 2: Allocation section should appear
    // Wait for allocation section to render (with increased timeout for real API)
    await expect(
      page.locator('h2, h3').filter({ hasText: /allocation/i }).first()
    ).toBeVisible({ timeout: 15000 })

    // Verify FIFO method exists (may or may not be pre-selected)
    await expect(
      page.locator('label').filter({ hasText: /first.*in.*first.*out|fifo/i })
    ).toBeVisible()

    // Click FIFO radio if not already selected
    const fifoRadio = page.locator('input[type="radio"]').filter({ has: page.locator('text=/fifo|first.*in/i') })
    await fifoRadio.first().check({ force: true })

    // Verify open invoices are displayed
    await expect(page.getByText(/TEST-INV-001|INV-001/i).first()).toBeVisible({ timeout: 10000 })

    // Click preview button
    await page.getByRole('button', { name: /preview/i }).click()

    // Wait for preview to load
    await page.waitForResponse((resp) =>
      resp.url().includes('/allocate/preview') &&
      resp.status() === 200
    , { timeout: 10000 })

    // Verify preview shows allocation details
    await expect(
      page.locator('text=/total.*to.*invoices|allocated/i').first()
    ).toBeVisible({ timeout: 10000 })

    // Apply allocation
    await page.getByRole('button', { name: /apply.*allocation/i }).click()

    // Wait for allocation to complete
    await page.waitForResponse((resp) =>
      resp.url().includes('/allocate') &&
      !resp.url().includes('/preview') &&
      resp.status() === 200
    , { timeout: 10000 })

    // Should navigate back to payments list or detail
    await expect(page).toHaveURL(/\/treasury\/payments/, { timeout: 10000 })
  })

  test('should create payment with manual allocation @smoke', async ({ authenticatedPage: page }) => {
    const paymentAmount = '3000.00'

    await page.goto('/treasury/payments/new')

    // STEP 1: Create payment (same as FIFO test)
    await page.getByLabel(/amount/i).fill(paymentAmount)
    await page.locator('select[name*="payment_method"]').selectOption({ index: 1 })
    await page.locator('select[name*="repository"]').selectOption({ index: 1 })
    await page.locator('select[name*="partner"]').selectOption({ index: 1 })
    await page.getByLabel(/payment date/i).fill(new Date().toISOString().split('T')[0])

    await page.getByRole('button', { name: /^save$/i }).click()

    await page.waitForResponse((resp) =>
      resp.url().includes('/api/v1/payments') &&
      resp.request().method() === 'POST' &&
      resp.status() === 201
    , { timeout: 10000 })

    // STEP 2: Wait for allocation section
    await expect(
      page.locator('h2, h3').filter({ hasText: /allocation/i }).first()
    ).toBeVisible({ timeout: 15000 })

    // Select manual allocation method
    const manualRadio = page.locator('input[type="radio"]').filter({ has: page.locator('text=/manual/i') })
    await manualRadio.first().check({ force: true })

    // Wait for checkboxes to appear (manual mode)
    await expect(page.locator('input[type="checkbox"]').first()).toBeVisible({ timeout: 5000 })

    // Select invoices and enter amounts
    const firstInvoiceRow = page.locator('tr').filter({ hasText: /TEST-INV-001|INV-001/i })
    await firstInvoiceRow.locator('input[type="checkbox"]').check()

    const firstAmountInput = firstInvoiceRow.locator('input[type="number"]')
    await firstAmountInput.fill('1500.00')

    const secondInvoiceRow = page.locator('tr').filter({ hasText: /TEST-INV-002|INV-002/i })
    await secondInvoiceRow.locator('input[type="checkbox"]').check()

    const secondAmountInput = secondInvoiceRow.locator('input[type="number"]')
    await secondAmountInput.fill('1500.00')

    // Preview allocation
    await page.getByRole('button', { name: /preview/i }).click()

    await page.waitForResponse((resp) =>
      resp.url().includes('/allocate/preview')
    , { timeout: 10000 })

    // Verify total
    await expect(page.locator('text=/3000|total/i')).toBeVisible()

    // Apply allocation
    await page.getByRole('button', { name: /apply/i }).click()

    await page.waitForResponse((resp) =>
      resp.url().includes('/allocate') && !resp.url().includes('/preview')
    , { timeout: 10000 })

    await expect(page).toHaveURL(/\/treasury\/payments/, { timeout: 10000 })
  })

  test('should show validation error when manual allocation exceeds payment', async ({ authenticatedPage: page }) => {
    const paymentAmount = '2000.00'

    await page.goto('/treasury/payments/new')

    // Create payment
    await page.getByLabel(/amount/i).fill(paymentAmount)
    await page.locator('select[name*="payment_method"]').selectOption({ index: 1 })
    await page.locator('select[name*="repository"]').selectOption({ index: 1 })
    await page.locator('select[name*="partner"]').selectOption({ index: 1 })
    await page.getByLabel(/payment date/i).fill(new Date().toISOString().split('T')[0])

    await page.getByRole('button', { name: /^save$/i }).click()

    await page.waitForResponse((resp) =>
      resp.url().includes('/api/v1/payments') &&
      resp.status() === 201
    , { timeout: 10000 })

    // Wait for allocation section
    await expect(
      page.locator('h2, h3').filter({ hasText: /allocation/i }).first()
    ).toBeVisible({ timeout: 15000 })

    // Select manual mode
    const manualRadio = page.locator('input[type="radio"]').filter({ has: page.locator('text=/manual/i') })
    await manualRadio.first().check({ force: true })

    await expect(page.locator('input[type="checkbox"]').first()).toBeVisible()

    // Select both invoices with full amounts (will exceed payment)
    const checkboxes = page.locator('input[type="checkbox"]')
    await checkboxes.nth(0).check()
    await checkboxes.nth(1).check()

    // Total allocations now exceed payment amount
    // Verify error message or disabled apply button
    const applyButton = page.getByRole('button', { name: /apply.*allocation/i })

    // Either button is disabled or error message appears
    await expect(async () => {
      const isDisabled = await applyButton.isDisabled()
      const hasError = await page.locator('text=/exceeds|maximum|error/i').isVisible()
      expect(isDisabled || hasError).toBeTruthy()
    }).toPass({ timeout: 5000 })
  })
})

test.describe('Smart Payment - Credit Notes (Real Data)', () => {
  test('should create credit note from posted invoice @smoke', async ({ authenticatedPage: page }) => {
    // Navigate to invoice detail page (TEST-INV-CREDIT from seeder)
    // We need to get the invoice ID first, so navigate to invoices list
    await page.goto('/sales/invoices')

    // Find and click on TEST-INV-CREDIT
    const invoiceLink = page.locator('a').filter({ hasText: /TEST-INV-CREDIT/i })
    await expect(invoiceLink).toBeVisible({ timeout: 10000 })
    await invoiceLink.click()

    // Wait for invoice detail page
    await expect(page.locator('h1').filter({ hasText: /TEST-INV-CREDIT/i })).toBeVisible({ timeout: 10000 })

    // Click "Create Credit Note" button
    await page.getByRole('button', { name: /create.*credit.*note/i }).click()

    // Wait for modal to appear (check for form elements instead of heading)
    await expect(page.getByLabel(/amount/i)).toBeVisible({ timeout: 5000 })

    // Fill credit note form
    await page.getByLabel(/amount/i).fill('500.00')

    // Select reason from dropdown
    const reasonSelect = page.locator('select').filter({ hasText: /return|adjustment|error/i })
    if (await reasonSelect.isVisible()) {
      await reasonSelect.selectOption({ index: 1 }) // Select first reason
    }

    await page.getByLabel(/notes/i).fill('Product returned by customer - E2E test')

    // Submit form
    await page.getByRole('button', { name: /^save$/i }).click()

    // Wait for credit note creation
    await page.waitForResponse((resp) =>
      resp.url().includes('/api/v1/credit-notes') &&
      resp.request().method() === 'POST' &&
      resp.status() === 201
    , { timeout: 10000 })

    // Modal should close (or we navigate to credit note detail)
    // Verify success by checking for toast/message or navigation
    await expect(async () => {
      const hasSuccessMessage = await page.locator('text=/success|created/i').isVisible()
      const urlChanged = page.url().includes('/credit-notes/')
      expect(hasSuccessMessage || urlChanged).toBeTruthy()
    }).toPass({ timeout: 5000 })
  })

  test('should use "Full Refund" button to fill amount', async ({ authenticatedPage: page }) => {
    await page.goto('/sales/invoices')

    const invoiceLink = page.locator('a').filter({ hasText: /TEST-INV-CREDIT/i })
    await expect(invoiceLink).toBeVisible({ timeout: 10000 })
    await invoiceLink.click()

    await expect(page.locator('h1').filter({ hasText: /TEST-INV-CREDIT/i })).toBeVisible()

    await page.getByRole('button', { name: /create.*credit.*note/i }).click()

    await expect(page.getByLabel(/amount/i)).toBeVisible({ timeout: 5000 })

    // Click "Full Refund" button
    const fullRefundButton = page.getByRole('button', { name: /full.*refund/i })
    await expect(fullRefundButton).toBeVisible()
    await fullRefundButton.click()

    // Amount should be filled with invoice total (1190.00 from seeder)
    await expect(page.getByLabel(/amount/i)).toHaveValue('1190.00')
  })
})

/**
 * SETUP INSTRUCTIONS FOR MANUAL TESTING:
 *
 * 1. Seed database:
 *    cd apps/api
 *    php artisan migrate:fresh --seed
 *    php artisan db:seed --class=SmartPaymentTestDataSeeder
 *
 * 2. Start servers:
 *    Terminal 1: cd apps/api && php artisan serve
 *    Terminal 2: cd apps/web && pnpm dev
 *
 * 3. Login credentials:
 *    Email: test@example.com
 *    Password: password
 *
 * 4. Test data available:
 *    - Customer with 4 test invoices (TEST-INV-001 through TEST-INV-CREDIT)
 *    - Payment methods and repositories from DatabaseSeeder
 *
 * 5. Run tests:
 *    pnpm exec playwright test e2e/smart-payment.spec.ts
 *
 * 6. Run tests in UI mode (for debugging):
 *    pnpm exec playwright test e2e/smart-payment.spec.ts --ui
 */
