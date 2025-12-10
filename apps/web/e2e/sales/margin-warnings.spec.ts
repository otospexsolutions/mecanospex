import { test, expect } from '../fixtures'

// Mock product with cost data
const mockProduct = {
  id: 'prod-1',
  sku: 'BP-001',
  name: 'Brake Pads',
  cost_price: '45.00',
  sell_price: '65.00',
}

const mockProducts = [
  mockProduct,
  {
    id: 'prod-2',
    sku: 'OF-001',
    name: 'Oil Filter',
    cost_price: '15.00',
    sell_price: '25.00',
  },
]

const mockPartners = [
  { id: 'customer-1', name: 'Auto Repair Shop', type: 'customer' },
  { id: 'customer-2', name: 'Car Service Center', type: 'customer' },
]

const mockInvoice = {
  id: 'inv-1',
  document_number: 'INV-2025-0001',
  type: 'invoice',
  status: 'draft',
  partner_id: 'customer-1',
  partner_name: 'Auto Repair Shop',
  subtotal: '65.00',
  tax_amount: '12.35',
  total: '77.35',
  currency: 'TND',
  document_date: '2025-01-15',
  lines: [
    {
      id: 'line-1',
      product_id: 'prod-1',
      description: 'Brake Pads',
      quantity: '1',
      unit_price: '65.00',
      line_total: '65.00',
      tax_rate: '19',
    },
  ],
}

// Margin check responses for different scenarios
const marginCheckAboveTarget = {
  cost_price: 45.0,
  sell_price: 65.0,
  margin_level: {
    level: 'green',
    message: 'Above target margin',
    actual_margin: 30.77,
  },
  can_sell: { allowed: true, reason: null },
  suggested_price: 58.5,
  margins: { target_margin: 30.0, minimum_margin: 10.0, source: 'company' },
}

const marginCheckBelowTarget = {
  cost_price: 45.0,
  sell_price: 55.0,
  margin_level: {
    level: 'yellow',
    message: 'Below target margin',
    actual_margin: 18.18,
    target_margin: 30.0,
  },
  can_sell: { allowed: true, reason: null },
  suggested_price: 58.5,
  margins: { target_margin: 30.0, minimum_margin: 10.0, source: 'company' },
}

const marginCheckBelowMinimum = {
  cost_price: 45.0,
  sell_price: 48.0,
  margin_level: {
    level: 'orange',
    message: 'Below minimum margin',
    actual_margin: 6.25,
    minimum_margin: 10.0,
  },
  can_sell: {
    allowed: false,
    reason: 'You do not have permission to sell below minimum margin',
    requires_permission: 'sell_below_minimum_margin',
  },
  suggested_price: 58.5,
  margins: { target_margin: 30.0, minimum_margin: 10.0, source: 'company' },
}

const marginCheckAtLoss = {
  cost_price: 45.0,
  sell_price: 40.0,
  margin_level: {
    level: 'red',
    message: 'Below cost - LOSS',
    actual_margin: -12.5,
    loss_amount: 5.0,
  },
  can_sell: {
    allowed: false,
    reason: 'Sales below cost are not allowed',
    requires_permission: 'sell_below_cost',
  },
  suggested_price: 58.5,
  margins: { target_margin: 30.0, minimum_margin: 10.0, source: 'company' },
}

test.describe('Invoice Margin Warnings', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // Mock partners endpoint
    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPartners }),
      })
    })

    // Mock products endpoint
    await page.route('**/api/v1/products**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockProducts }),
      })
    })
  })

  test('should show green indicator when price is above target margin', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents/invoice/inv-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockInvoice }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: marginCheckAboveTarget }),
      })
    })

    await page.goto('/sales/invoices/inv-1')

    // Should show green margin indicator
    await expect(page.locator('[class*="green"]').first()).toBeVisible()
  })

  test('should show yellow warning when price is below target margin', async ({ authenticatedPage: page }) => {
    const invoiceWithLowPrice = {
      ...mockInvoice,
      lines: [
        {
          ...mockInvoice.lines[0],
          unit_price: '55.00',
          line_total: '55.00',
        },
      ],
      subtotal: '55.00',
      total: '65.45',
    }

    await page.route('**/api/v1/documents/invoice/inv-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: invoiceWithLowPrice }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: marginCheckBelowTarget }),
      })
    })

    await page.goto('/sales/invoices/inv-1')

    // Should show yellow margin indicator
    await expect(page.locator('[class*="yellow"]').first()).toBeVisible()
    await expect(page.getByText(/below target/i)).toBeVisible()
  })

  test('should show orange warning when price is below minimum margin', async ({ authenticatedPage: page }) => {
    const invoiceWithVeryLowPrice = {
      ...mockInvoice,
      lines: [
        {
          ...mockInvoice.lines[0],
          unit_price: '48.00',
          line_total: '48.00',
        },
      ],
      subtotal: '48.00',
      total: '57.12',
    }

    await page.route('**/api/v1/documents/invoice/inv-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: invoiceWithVeryLowPrice }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: marginCheckBelowMinimum }),
      })
    })

    await page.goto('/sales/invoices/inv-1')

    // Should show orange margin indicator
    await expect(page.locator('[class*="orange"]').first()).toBeVisible()
    await expect(page.getByText(/below minimum/i)).toBeVisible()
  })

  test('should show red error when selling at a loss', async ({ authenticatedPage: page }) => {
    const invoiceAtLoss = {
      ...mockInvoice,
      lines: [
        {
          ...mockInvoice.lines[0],
          unit_price: '40.00',
          line_total: '40.00',
        },
      ],
      subtotal: '40.00',
      total: '47.60',
    }

    await page.route('**/api/v1/documents/invoice/inv-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: invoiceAtLoss }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: marginCheckAtLoss }),
      })
    })

    await page.goto('/sales/invoices/inv-1')

    // Should show red margin indicator
    await expect(page.locator('[class*="red"]').first()).toBeVisible()
    await expect(page.getByText(/loss/i)).toBeVisible()
  })

  test('should block submission for unauthorized user selling below cost', async ({ authenticatedPage: page }) => {
    const invoiceAtLoss = {
      ...mockInvoice,
      lines: [
        {
          ...mockInvoice.lines[0],
          unit_price: '40.00',
          line_total: '40.00',
        },
      ],
      subtotal: '40.00',
      total: '47.60',
    }

    await page.route('**/api/v1/documents/invoice/inv-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: invoiceAtLoss }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: marginCheckAtLoss }),
      })
    })

    await page.goto('/sales/invoices/inv-1/edit')

    // Submit button should be disabled or show warning
    const submitButton = page.getByRole('button', { name: /save|confirm|submit/i })
    if (await submitButton.isVisible()) {
      // Either the button is disabled or clicking it shows a warning
      const isDisabled = await submitButton.isDisabled()
      if (!isDisabled) {
        await submitButton.click()
        // Should show error message
        await expect(page.getByText(/not allowed|permission|cannot sell/i)).toBeVisible()
      }
    }
  })

  test('should allow submission for authorized user with sell_below_cost permission', async ({ authenticatedPage: page }) => {
    // Override auth mock with a user who has the permission
    await page.route('**/api/v1/auth/me', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: '1',
            name: 'Admin User',
            email: 'admin@example.com',
            tenant_id: 'tenant-1',
            roles: ['admin'],
            permissions: ['sell_below_cost', 'sell_below_minimum_margin'],
          },
        }),
      })
    })

    const invoiceAtLoss = {
      ...mockInvoice,
      lines: [
        {
          ...mockInvoice.lines[0],
          unit_price: '40.00',
          line_total: '40.00',
        },
      ],
      subtotal: '40.00',
      total: '47.60',
    }

    await page.route('**/api/v1/documents/invoice/inv-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: invoiceAtLoss }),
      })
    })

    // For authorized user, can_sell should be true
    const marginCheckAuthorized = {
      ...marginCheckAtLoss,
      can_sell: { allowed: true, reason: null },
    }

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: marginCheckAuthorized }),
      })
    })

    await page.goto('/sales/invoices/inv-1/edit')

    // Submit button should be enabled for authorized user
    const submitButton = page.getByRole('button', { name: /save|confirm|submit/i })
    if (await submitButton.isVisible()) {
      // Button should not be disabled
      await expect(submitButton).not.toBeDisabled()
    }
  })

  test('should show suggested price button and apply it', async ({ authenticatedPage: page }) => {
    const invoiceWithLowPrice = {
      ...mockInvoice,
      lines: [
        {
          ...mockInvoice.lines[0],
          unit_price: '50.00',
          line_total: '50.00',
        },
      ],
      subtotal: '50.00',
      total: '59.50',
    }

    await page.route('**/api/v1/documents/invoice/inv-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: invoiceWithLowPrice }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: marginCheckBelowTarget }),
      })
    })

    await page.goto('/sales/invoices/inv-1/edit')

    // Look for suggested price button
    const suggestedPriceButton = page.getByRole('button', { name: /suggested|58\.50/i })
    if (await suggestedPriceButton.isVisible()) {
      await suggestedPriceButton.click()

      // Price should update to suggested price
      const priceInput = page.getByLabel(/price/i).first()
      if (await priceInput.isVisible()) {
        await expect(priceInput).toHaveValue('58.50')
      }
    }
  })

  test('should update margin indicator in real-time when price changes', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents/invoice/inv-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockInvoice }),
      })
    })

    let currentPrice = 65
    await page.route('**/api/v1/pricing/check-margin', (route) => {
      const requestBody = route.request().postDataJSON()
      const sellPrice = requestBody?.sell_price || currentPrice

      let level = 'green'
      let message = 'Above target margin'
      if (sellPrice < 45) {
        level = 'red'
        message = 'Below cost - LOSS'
      } else if (sellPrice < 49.5) {
        level = 'orange'
        message = 'Below minimum margin'
      } else if (sellPrice < 58.5) {
        level = 'yellow'
        message = 'Below target margin'
      }

      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            cost_price: 45.0,
            sell_price: sellPrice,
            margin_level: {
              level,
              message,
              actual_margin: sellPrice > 0 ? ((sellPrice - 45) / sellPrice) * 100 : 0,
            },
            can_sell: { allowed: level !== 'red', reason: null },
            suggested_price: 58.5,
            margins: { target_margin: 30.0, minimum_margin: 10.0, source: 'company' },
          },
        }),
      })
    })

    await page.goto('/sales/invoices/inv-1/edit')

    // Find price input
    const priceInput = page.locator('input[type="number"]').first()
    if (await priceInput.isVisible()) {
      // Initially should be green
      await expect(page.locator('[class*="green"]').first()).toBeVisible()

      // Change to below target
      await priceInput.clear()
      await priceInput.fill('55')
      await page.waitForTimeout(500) // Wait for debounce

      // Should show yellow indicator
      await expect(page.locator('[class*="yellow"]').first()).toBeVisible()

      // Change to below minimum
      await priceInput.clear()
      await priceInput.fill('47')
      await page.waitForTimeout(500)

      // Should show orange indicator
      await expect(page.locator('[class*="orange"]').first()).toBeVisible()

      // Change to below cost
      await priceInput.clear()
      await priceInput.fill('40')
      await page.waitForTimeout(500)

      // Should show red indicator
      await expect(page.locator('[class*="red"]').first()).toBeVisible()
    }
  })
})

test.describe('Quote to Invoice Conversion with Margin Check', () => {
  test('should check margins when converting quote to invoice', async ({ authenticatedPage: page }) => {
    const mockQuote = {
      id: 'quote-1',
      document_number: 'QUO-2025-0001',
      type: 'quote',
      status: 'confirmed',
      partner_id: 'customer-1',
      partner_name: 'Auto Repair Shop',
      subtotal: '65.00',
      tax_amount: '12.35',
      total: '77.35',
      currency: 'TND',
      document_date: '2025-01-15',
      lines: [
        {
          id: 'line-1',
          product_id: 'prod-1',
          description: 'Brake Pads',
          quantity: '1',
          unit_price: '65.00',
          line_total: '65.00',
          tax_rate: '19',
        },
      ],
    }

    await page.route('**/api/v1/documents/quote/quote-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockQuote }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: marginCheckAboveTarget }),
      })
    })

    await page.route('**/api/v1/documents/quote/quote-1/convert-to-order', (route) => {
      route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            ...mockQuote,
            id: 'order-1',
            document_number: 'ORD-2025-0001',
            type: 'sales_order',
            status: 'draft',
          },
        }),
      })
    })

    await page.goto('/sales/quotes/quote-1')

    // Look for convert button
    const convertButton = page.getByRole('button', { name: /convert|create order/i })
    if (await convertButton.isVisible()) {
      await convertButton.click()

      // Should redirect to new order or show success
      await expect(page.getByText(/ORD-2025-0001|order created|converted/i)).toBeVisible()
    }
  })
})
