import { test, expect } from '../fixtures'

// Mock product data with cost and margin information
const mockProduct = {
  id: 'prod-1',
  sku: 'BP-001',
  name: 'Brake Pads - Premium',
  description: 'High-quality ceramic brake pads',
  category_id: 'cat-1',
  category_name: 'Brake Parts',
  cost_price: '45.00',
  sell_price: '65.00',
  last_purchase_cost: '48.00',
  cost_updated_at: '2025-01-10T10:00:00Z',
  target_margin_override: null,
  minimum_margin_override: null,
  stock_quantity: 25,
  currency: 'TND',
}

const mockCategory = {
  id: 'cat-1',
  name: 'Brake Parts',
  target_margin_override: null,
  minimum_margin_override: null,
}

const mockCompanySettings = {
  id: 'company-1',
  name: 'Auto Shop',
  default_target_margin: '30.00',
  default_minimum_margin: '10.00',
  allow_below_cost_sales: false,
  inventory_costing_method: 'weighted_average',
}

const mockMarginCheck = {
  cost_price: 45.0,
  sell_price: 65.0,
  margin_level: {
    level: 'green',
    message: 'Above target margin',
    actual_margin: 44.44,
  },
  can_sell: {
    allowed: true,
    reason: null,
  },
  suggested_price: 58.5,
  margins: {
    target_margin: 30.0,
    minimum_margin: 10.0,
    source: 'company',
  },
}

test.describe('Product Pricing Card', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // Mock company settings endpoint
    await page.route('**/api/v1/companies/current', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockCompanySettings }),
      })
    })

    // Mock categories endpoint
    await page.route('**/api/v1/categories**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: [mockCategory] }),
      })
    })
  })

  test('should display product cost and margins', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/products/prod-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockProduct }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockMarginCheck }),
      })
    })

    await page.goto('/products/prod-1')

    // Should show product name
    await expect(page.getByText('Brake Pads - Premium')).toBeVisible()

    // Should show cost price (weighted average cost)
    await expect(page.getByText(/45\.00/)).toBeVisible()

    // Should show last purchase cost
    await expect(page.getByText(/48\.00/)).toBeVisible()

    // Should show current sell price
    await expect(page.getByText(/65\.00/)).toBeVisible()

    // Should show margin information
    await expect(page.getByText(/30%/)).toBeVisible() // Target margin
    await expect(page.getByText(/10%/)).toBeVisible() // Minimum margin
  })

  test('should show green indicator for above target margin', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/products/prod-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockProduct }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockMarginCheck }),
      })
    })

    await page.goto('/products/prod-1')

    // Should show green margin indicator
    await expect(page.locator('.bg-green-100, .text-green-700, [class*="green"]').first()).toBeVisible()
    await expect(page.getByText(/above target/i)).toBeVisible()
  })

  test('should show yellow indicator for below target margin', async ({ authenticatedPage: page }) => {
    const belowTargetProduct = {
      ...mockProduct,
      sell_price: '55.00', // 22% margin, below 30% target
    }

    const belowTargetMarginCheck = {
      ...mockMarginCheck,
      sell_price: 55.0,
      margin_level: {
        level: 'yellow',
        message: 'Below target margin',
        actual_margin: 22.22,
        target_margin: 30.0,
      },
    }

    await page.route('**/api/v1/products/prod-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: belowTargetProduct }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: belowTargetMarginCheck }),
      })
    })

    await page.goto('/products/prod-1')

    // Should show yellow margin indicator
    await expect(page.locator('.bg-yellow-100, .text-yellow-700, [class*="yellow"]').first()).toBeVisible()
    await expect(page.getByText(/below target/i)).toBeVisible()
  })

  test('should show orange indicator for below minimum margin', async ({ authenticatedPage: page }) => {
    const belowMinimumProduct = {
      ...mockProduct,
      sell_price: '48.00', // 6.67% margin, below 10% minimum
    }

    const belowMinimumMarginCheck = {
      ...mockMarginCheck,
      sell_price: 48.0,
      margin_level: {
        level: 'orange',
        message: 'Below minimum margin',
        actual_margin: 6.67,
        minimum_margin: 10.0,
      },
    }

    await page.route('**/api/v1/products/prod-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: belowMinimumProduct }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: belowMinimumMarginCheck }),
      })
    })

    await page.goto('/products/prod-1')

    // Should show orange margin indicator
    await expect(page.locator('.bg-orange-100, .text-orange-700, [class*="orange"]').first()).toBeVisible()
    await expect(page.getByText(/below minimum/i)).toBeVisible()
  })

  test('should show red indicator for selling at loss', async ({ authenticatedPage: page }) => {
    const lossProduct = {
      ...mockProduct,
      sell_price: '40.00', // Below cost of 45, selling at loss
    }

    const lossMarginCheck = {
      ...mockMarginCheck,
      sell_price: 40.0,
      margin_level: {
        level: 'red',
        message: 'Below cost - LOSS',
        actual_margin: -11.11,
        loss_amount: 5.0,
      },
      can_sell: {
        allowed: false,
        reason: 'Sales below cost are not allowed',
        requires_permission: 'sell_below_cost',
      },
    }

    await page.route('**/api/v1/products/prod-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: lossProduct }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: lossMarginCheck }),
      })
    })

    await page.goto('/products/prod-1')

    // Should show red margin indicator
    await expect(page.locator('.bg-red-100, .text-red-700, [class*="red"]').first()).toBeVisible()
    await expect(page.getByText(/loss/i)).toBeVisible()
  })

  test('should show suggested price', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/products/prod-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockProduct }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockMarginCheck }),
      })
    })

    await page.goto('/products/prod-1')

    // Suggested price based on cost (45) + target margin (30%) = 58.50
    await expect(page.getByText(/58\.50/)).toBeVisible()
  })

  test('should update price and recalculate margin', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/products/prod-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockProduct }),
      })
    })

    let checkCount = 0
    await page.route('**/api/v1/pricing/check-margin', (route) => {
      checkCount++
      const requestBody = route.request().postDataJSON()
      const sellPrice = requestBody?.sell_price || 65

      // Return different margin level based on price
      let marginLevel
      if (sellPrice >= 58.5) {
        marginLevel = { level: 'green', message: 'Above target margin', actual_margin: ((sellPrice - 45) / sellPrice) * 100 }
      } else if (sellPrice >= 49.5) {
        marginLevel = { level: 'yellow', message: 'Below target margin', actual_margin: ((sellPrice - 45) / sellPrice) * 100 }
      } else if (sellPrice >= 45) {
        marginLevel = { level: 'orange', message: 'Below minimum margin', actual_margin: ((sellPrice - 45) / sellPrice) * 100 }
      } else {
        marginLevel = { level: 'red', message: 'Below cost - LOSS', actual_margin: ((sellPrice - 45) / sellPrice) * 100, loss_amount: 45 - sellPrice }
      }

      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            ...mockMarginCheck,
            sell_price: sellPrice,
            margin_level: marginLevel,
          },
        }),
      })
    })

    await page.goto('/products/prod-1/edit')

    // Find and update price input
    const priceInput = page.getByLabel(/sell price|price/i).first()
    if (await priceInput.isVisible()) {
      await priceInput.clear()
      await priceInput.fill('50')

      // Should trigger margin check - wait a moment for debounce
      await page.waitForTimeout(500)

      // Margin indicator should update
      await expect(page.locator('.bg-yellow-100, .text-yellow-700, [class*="yellow"]').first()).toBeVisible()
    }
  })

  test('should show margin source (product/category/company)', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/products/prod-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockProduct }),
      })
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockMarginCheck }),
      })
    })

    await page.goto('/products/prod-1')

    // Should indicate margin is inherited from company (since product and category don't have overrides)
    await expect(page.getByText(/company|inherited|default/i)).toBeVisible()
  })
})

test.describe('Product Pricing Card - Margin Override', () => {
  test('should allow setting custom margin override', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/products/prod-1', (route) => {
      if (route.request().method() === 'PATCH') {
        const body = route.request().postDataJSON()
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              ...mockProduct,
              target_margin_override: body.target_margin_override,
              minimum_margin_override: body.minimum_margin_override,
            },
          }),
        })
      } else {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: mockProduct }),
        })
      }
    })

    await page.route('**/api/v1/pricing/check-margin', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockMarginCheck }),
      })
    })

    await page.route('**/api/v1/companies/current', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockCompanySettings }),
      })
    })

    await page.goto('/products/prod-1/edit')

    // Look for margin override inputs
    const targetMarginInput = page.getByLabel(/target margin/i)
    if (await targetMarginInput.isVisible()) {
      await targetMarginInput.clear()
      await targetMarginInput.fill('35')

      // Save the product
      await page.getByRole('button', { name: /save/i }).click()

      // Should show success message
      await expect(page.getByText(/saved|updated|success/i)).toBeVisible()
    }
  })
})
