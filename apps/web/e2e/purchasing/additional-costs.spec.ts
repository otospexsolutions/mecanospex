import { test, expect } from '../fixtures'

// Mock data for purchase order tests
const mockPurchaseOrder = {
  id: 'po-1',
  document_number: 'PO-2025-0001',
  type: 'purchase_order',
  status: 'draft',
  partner_id: 'supplier-1',
  partner_name: 'Auto Parts Supplier',
  subtotal: '1000.00',
  tax_amount: '190.00',
  total: '1190.00',
  currency: 'TND',
  document_date: '2025-01-15',
  lines: [
    {
      id: 'line-1',
      product_id: 'prod-1',
      description: 'Brake Pads',
      quantity: '10',
      unit_price: '50.00',
      line_total: '500.00',
      tax_rate: '19',
      allocated_costs: '0.00',
      landed_unit_cost: null,
    },
    {
      id: 'line-2',
      product_id: 'prod-2',
      description: 'Oil Filter',
      quantity: '20',
      unit_price: '25.00',
      line_total: '500.00',
      tax_rate: '19',
      allocated_costs: '0.00',
      landed_unit_cost: null,
    },
  ],
}

const mockAdditionalCosts = [
  {
    id: 'cost-1',
    document_id: 'po-1',
    cost_type: 'shipping',
    description: 'Shipping from supplier',
    amount: '100.00',
  },
  {
    id: 'cost-2',
    document_id: 'po-1',
    cost_type: 'customs',
    description: 'Customs duty',
    amount: '50.00',
  },
]

const mockSuppliers = [
  { id: 'supplier-1', name: 'Auto Parts Supplier', type: 'supplier' },
  { id: 'supplier-2', name: 'Parts Wholesale', type: 'supplier' },
]

test.describe('Purchase Order Additional Costs', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // Mock partners/suppliers endpoint
    await page.route('**/api/v1/partners**', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockSuppliers }),
      })
    })
  })

  test('should display additional costs section on purchase order', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents/purchase_order/po-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPurchaseOrder }),
      })
    })

    await page.route('**/api/v1/documents/po-1/additional-costs', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockAdditionalCosts }),
      })
    })

    await page.goto('/purchasing/orders/po-1')

    // Should show additional costs section
    await expect(page.getByText(/additional costs/i)).toBeVisible()
    await expect(page.getByText('Shipping from supplier')).toBeVisible()
    await expect(page.getByText('Customs duty')).toBeVisible()
  })

  test('should add a new additional cost', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents/purchase_order/po-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPurchaseOrder }),
      })
    })

    await page.route('**/api/v1/documents/po-1/additional-costs', (route) => {
      if (route.request().method() === 'POST') {
        route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: 'cost-3',
              document_id: 'po-1',
              cost_type: 'insurance',
              description: 'Transport insurance',
              amount: '25.00',
            },
          }),
        })
      } else {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: mockAdditionalCosts }),
        })
      }
    })

    await page.goto('/purchasing/orders/po-1')

    // Select cost type
    await page.getByLabel(/type/i).first().selectOption('insurance')

    // Enter description
    await page.getByPlaceholder(/description/i).first().fill('Transport insurance')

    // Enter amount
    await page.getByPlaceholder(/0.00/i).first().fill('25')

    // Click add button
    await page.getByRole('button', { name: /add/i }).click()

    // Should show success or the new cost in the list
    await expect(page.getByText('Transport insurance')).toBeVisible()
  })

  test('should remove an additional cost', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents/purchase_order/po-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPurchaseOrder }),
      })
    })

    let costs = [...mockAdditionalCosts]

    await page.route('**/api/v1/documents/po-1/additional-costs**', (route) => {
      if (route.request().method() === 'DELETE') {
        costs = costs.filter((c) => !route.request().url().includes(c.id))
        route.fulfill({
          status: 204,
          contentType: 'application/json',
          body: '',
        })
      } else {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: costs }),
        })
      }
    })

    await page.goto('/purchasing/orders/po-1')

    // Should show shipping cost
    await expect(page.getByText('Shipping from supplier')).toBeVisible()

    // Click remove button on first cost
    await page.getByRole('button', { name: /remove/i }).first().click()

    // Confirm if there's a confirmation dialog
    const confirmButton = page.getByRole('button', { name: /confirm|yes|delete/i })
    if (await confirmButton.isVisible()) {
      await confirmButton.click()
    }
  })

  test('should display total additional costs', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents/purchase_order/po-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPurchaseOrder }),
      })
    })

    await page.route('**/api/v1/documents/po-1/additional-costs', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockAdditionalCosts }),
      })
    })

    await page.goto('/purchasing/orders/po-1')

    // Total should be 100 + 50 = 150 TND
    await expect(page.getByText(/150/)).toBeVisible()
  })
})

test.describe('Landed Cost Breakdown', () => {
  test('should display landed cost breakdown after confirmation', async ({ authenticatedPage: page }) => {
    const confirmedPO = {
      ...mockPurchaseOrder,
      status: 'confirmed',
      lines: mockPurchaseOrder.lines.map((line, index) => ({
        ...line,
        // Proportional allocation: 150 total costs, 50% each line
        allocated_costs: '75.00',
        landed_unit_cost: index === 0 ? '57.50' : '28.75', // (500+75)/10 = 57.50, (500+75)/20 = 28.75
      })),
    }

    await page.route('**/api/v1/documents/purchase_order/po-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: confirmedPO }),
      })
    })

    await page.route('**/api/v1/documents/po-1/landed-cost-breakdown', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: confirmedPO.lines.map((line) => ({
            line_id: line.id,
            product_name: line.description,
            quantity: line.quantity,
            unit_price: line.unit_price,
            line_total: line.line_total,
            allocated_costs: line.allocated_costs,
            landed_unit_cost: line.landed_unit_cost,
          })),
        }),
      })
    })

    await page.route('**/api/v1/documents/po-1/additional-costs', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockAdditionalCosts }),
      })
    })

    await page.goto('/purchasing/orders/po-1')

    // Should show landed cost breakdown
    await expect(page.getByText(/landed cost/i)).toBeVisible()

    // Should show allocated costs per line
    await expect(page.getByText('75.00')).toBeVisible()

    // Should show landed unit cost
    await expect(page.getByText('57.50')).toBeVisible()
    await expect(page.getByText('28.75')).toBeVisible()
  })

  test('should update landed costs when confirming purchase order', async ({ authenticatedPage: page }) => {
    await page.route('**/api/v1/documents/purchase_order/po-1', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockPurchaseOrder }),
      })
    })

    await page.route('**/api/v1/documents/po-1/additional-costs', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockAdditionalCosts }),
      })
    })

    // Mock confirm endpoint
    await page.route('**/api/v1/documents/purchase_order/po-1/confirm', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            ...mockPurchaseOrder,
            status: 'confirmed',
            lines: mockPurchaseOrder.lines.map((line, index) => ({
              ...line,
              allocated_costs: '75.00',
              landed_unit_cost: index === 0 ? '57.50' : '28.75',
            })),
          },
        }),
      })
    })

    await page.goto('/purchasing/orders/po-1')

    // Click confirm button
    const confirmButton = page.getByRole('button', { name: /confirm/i })
    if (await confirmButton.isVisible()) {
      await confirmButton.click()

      // After confirmation, landed costs should be calculated
      await expect(page.getByText(/confirmed/i)).toBeVisible()
    }
  })
})
