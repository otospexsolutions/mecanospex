import { lazy, Suspense } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import { Layout } from '../components/layout/Layout'
import { RequireAuth } from '../features/auth'
import { RequirePermission } from '../components/auth'
import { LoadingSpinner } from '../components/ui/LoadingSpinner'

// Lazy loaded pages
const LoginPage = lazy(() => import('../features/auth/LoginPage').then((m) => ({ default: m.LoginPage })))
const Dashboard = lazy(() => import('../features/dashboard/Dashboard').then((m) => ({ default: m.Dashboard })))

// Sales module
const CustomerListPage = lazy(() => import('../features/partners/PartnerListPage').then((m) => ({ default: m.PartnerListPage })))
const CustomerDetailPage = lazy(() => import('../features/partners/PartnerDetailPage').then((m) => ({ default: m.PartnerDetailPage })))
const CustomerForm = lazy(() => import('../features/partners/PartnerForm').then((m) => ({ default: m.PartnerForm })))

// Documents - reusing for quotes/orders/invoices
const DocumentListPage = lazy(() => import('../features/documents/DocumentListPage').then((m) => ({ default: m.DocumentListPage })))
const DocumentDetailPage = lazy(() => import('../features/documents/DocumentDetailPage').then((m) => ({ default: m.DocumentDetailPage })))
const DocumentForm = lazy(() => import('../features/documents/DocumentForm').then((m) => ({ default: m.DocumentForm })))

// Treasury module
const PaymentListPage = lazy(() => import('../features/treasury/PaymentListPage').then((m) => ({ default: m.PaymentListPage })))
const PaymentDetailPage = lazy(() => import('../features/treasury/PaymentDetailPage').then((m) => ({ default: m.PaymentDetailPage })))
const PaymentForm = lazy(() => import('../features/treasury/PaymentForm').then((m) => ({ default: m.PaymentForm })))
const InstrumentListPage = lazy(() => import('../features/treasury/InstrumentListPage').then((m) => ({ default: m.InstrumentListPage })))
const RepositoryListPage = lazy(() => import('../features/treasury/RepositoryListPage').then((m) => ({ default: m.RepositoryListPage })))

// Reports module
const ReportsPage = lazy(() => import('../features/reports/ReportsPage').then((m) => ({ default: m.ReportsPage })))

// Inventory module
const ProductListPage = lazy(() => import('../features/inventory/ProductListPage').then((m) => ({ default: m.ProductListPage })))
const ProductDetailPage = lazy(() => import('../features/inventory/ProductDetailPage').then((m) => ({ default: m.ProductDetailPage })))
const ProductForm = lazy(() => import('../features/inventory/ProductForm').then((m) => ({ default: m.ProductForm })))
const StockLevelsPage = lazy(() => import('../features/inventory/StockLevelsPage').then((m) => ({ default: m.StockLevelsPage })))
const StockMovementsPage = lazy(() => import('../features/inventory/StockMovementsPage').then((m) => ({ default: m.StockMovementsPage })))

// Vehicles module
const VehicleListPage = lazy(() => import('../features/vehicles/VehicleListPage').then((m) => ({ default: m.VehicleListPage })))
const VehicleDetailPage = lazy(() => import('../features/vehicles/VehicleDetailPage').then((m) => ({ default: m.VehicleDetailPage })))
const VehicleForm = lazy(() => import('../features/vehicles/VehicleForm').then((m) => ({ default: m.VehicleForm })))

// Settings module
const SettingsPage = lazy(() => import('../features/settings/SettingsPage').then((m) => ({ default: m.SettingsPage })))
const UsersPage = lazy(() => import('../features/settings/UsersPage').then((m) => ({ default: m.UsersPage })))
const RolesPage = lazy(() => import('../features/settings/RolesPage').then((m) => ({ default: m.RolesPage })))
const CompanyPage = lazy(() => import('../features/settings/CompanyPage').then((m) => ({ default: m.CompanyPage })))

function SuspenseWrapper({ children }: { children: React.ReactNode }) {
  return (
    <Suspense fallback={<LoadingSpinner fullScreen />}>
      {children}
    </Suspense>
  )
}

export function AppRoutes() {
  return (
    <Routes>
      {/* Public routes */}
      <Route
        path="/login"
        element={
          <SuspenseWrapper>
            <LoginPage />
          </SuspenseWrapper>
        }
      />

      {/* Protected routes */}
      <Route
        path="/"
        element={
          <RequireAuth>
            <Layout />
          </RequireAuth>
        }
      >
        {/* Redirect root to dashboard */}
        <Route index element={<Navigate to="/dashboard" replace />} />

        {/* Dashboard */}
        <Route
          path="dashboard"
          element={
            <SuspenseWrapper>
              <Dashboard />
            </SuspenseWrapper>
          }
        />

        {/* Sales Module */}
        <Route path="sales">
          <Route index element={<Navigate to="/sales/customers" replace />} />

          {/* Customers (filtered partners) */}
          <Route
            path="customers"
            element={
              <RequirePermission moduleKey="sales">
                <SuspenseWrapper>
                  <CustomerListPage partnerType="customer" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="customers/new"
            element={
              <RequirePermission permission="sales.create">
                <SuspenseWrapper>
                  <CustomerForm partnerType="customer" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="customers/:id"
            element={
              <RequirePermission moduleKey="sales">
                <SuspenseWrapper>
                  <CustomerDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="customers/:id/edit"
            element={
              <RequirePermission permission="sales.edit">
                <SuspenseWrapper>
                  <CustomerForm partnerType="customer" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          {/* Quotes */}
          <Route
            path="quotes"
            element={
              <RequirePermission moduleKey="sales">
                <SuspenseWrapper>
                  <DocumentListPage documentType="quote" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="quotes/new"
            element={
              <RequirePermission permission="sales.create">
                <SuspenseWrapper>
                  <DocumentForm documentType="quote" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="quotes/:id"
            element={
              <RequirePermission moduleKey="sales">
                <SuspenseWrapper>
                  <DocumentDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="quotes/:id/edit"
            element={
              <RequirePermission permission="sales.edit">
                <SuspenseWrapper>
                  <DocumentForm documentType="quote" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          {/* Sales Orders */}
          <Route
            path="orders"
            element={
              <RequirePermission moduleKey="sales">
                <SuspenseWrapper>
                  <DocumentListPage documentType="sales_order" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="orders/new"
            element={
              <RequirePermission permission="sales.create">
                <SuspenseWrapper>
                  <DocumentForm documentType="sales_order" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="orders/:id"
            element={
              <RequirePermission moduleKey="sales">
                <SuspenseWrapper>
                  <DocumentDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="orders/:id/edit"
            element={
              <RequirePermission permission="sales.edit">
                <SuspenseWrapper>
                  <DocumentForm documentType="sales_order" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          {/* Invoices */}
          <Route
            path="invoices"
            element={
              <RequirePermission moduleKey="sales">
                <SuspenseWrapper>
                  <DocumentListPage documentType="invoice" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="invoices/new"
            element={
              <RequirePermission permission="sales.create">
                <SuspenseWrapper>
                  <DocumentForm documentType="invoice" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="invoices/:id"
            element={
              <RequirePermission moduleKey="sales">
                <SuspenseWrapper>
                  <DocumentDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="invoices/:id/edit"
            element={
              <RequirePermission permission="sales.edit">
                <SuspenseWrapper>
                  <DocumentForm documentType="invoice" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
        </Route>

        {/* Purchases Module */}
        <Route path="purchases">
          <Route index element={<Navigate to="/purchases/suppliers" replace />} />

          {/* Suppliers (filtered partners) */}
          <Route
            path="suppliers"
            element={
              <RequirePermission moduleKey="purchases">
                <SuspenseWrapper>
                  <CustomerListPage partnerType="supplier" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="suppliers/new"
            element={
              <RequirePermission permission="purchases.create">
                <SuspenseWrapper>
                  <CustomerForm partnerType="supplier" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="suppliers/:id"
            element={
              <RequirePermission moduleKey="purchases">
                <SuspenseWrapper>
                  <CustomerDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="suppliers/:id/edit"
            element={
              <RequirePermission permission="purchases.edit">
                <SuspenseWrapper>
                  <CustomerForm partnerType="supplier" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          {/* Purchase Orders */}
          <Route
            path="orders"
            element={
              <RequirePermission moduleKey="purchases">
                <SuspenseWrapper>
                  <DocumentListPage documentType="purchase_order" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="orders/new"
            element={
              <RequirePermission permission="purchases.create">
                <SuspenseWrapper>
                  <DocumentForm documentType="purchase_order" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="orders/:id"
            element={
              <RequirePermission moduleKey="purchases">
                <SuspenseWrapper>
                  <DocumentDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="orders/:id/edit"
            element={
              <RequirePermission permission="purchases.edit">
                <SuspenseWrapper>
                  <DocumentForm documentType="purchase_order" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
        </Route>

        {/* Inventory Module */}
        <Route path="inventory">
          <Route index element={<Navigate to="/inventory/products" replace />} />

          <Route
            path="products"
            element={
              <RequirePermission moduleKey="inventory">
                <SuspenseWrapper>
                  <ProductListPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="products/new"
            element={
              <RequirePermission permission="inventory.create">
                <SuspenseWrapper>
                  <ProductForm />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="products/:id"
            element={
              <RequirePermission moduleKey="inventory">
                <SuspenseWrapper>
                  <ProductDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="products/:id/edit"
            element={
              <RequirePermission permission="inventory.edit">
                <SuspenseWrapper>
                  <ProductForm />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          <Route
            path="stock"
            element={
              <RequirePermission moduleKey="inventory">
                <SuspenseWrapper>
                  <StockLevelsPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          <Route
            path="movements"
            element={
              <RequirePermission moduleKey="inventory">
                <SuspenseWrapper>
                  <StockMovementsPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          {/* Delivery Notes */}
          <Route
            path="delivery-notes"
            element={
              <RequirePermission moduleKey="inventory">
                <SuspenseWrapper>
                  <DocumentListPage documentType="delivery_note" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="delivery-notes/new"
            element={
              <RequirePermission permission="inventory.create">
                <SuspenseWrapper>
                  <DocumentForm documentType="delivery_note" />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="delivery-notes/:id"
            element={
              <RequirePermission moduleKey="inventory">
                <SuspenseWrapper>
                  <DocumentDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
        </Route>

        {/* Vehicles */}
        <Route
          path="vehicles"
          element={
            <RequirePermission moduleKey="vehicles">
              <SuspenseWrapper>
                <VehicleListPage />
              </SuspenseWrapper>
            </RequirePermission>
          }
        />
        <Route
          path="vehicles/new"
          element={
            <RequirePermission permission="vehicles.create">
              <SuspenseWrapper>
                <VehicleForm />
              </SuspenseWrapper>
            </RequirePermission>
          }
        />
        <Route
          path="vehicles/:id"
          element={
            <RequirePermission moduleKey="vehicles">
              <SuspenseWrapper>
                <VehicleDetailPage />
              </SuspenseWrapper>
            </RequirePermission>
          }
        />
        <Route
          path="vehicles/:id/edit"
          element={
            <RequirePermission permission="vehicles.edit">
              <SuspenseWrapper>
                <VehicleForm />
              </SuspenseWrapper>
            </RequirePermission>
          }
        />

        {/* Treasury Module */}
        <Route path="treasury">
          <Route index element={<Navigate to="/treasury/payments" replace />} />

          <Route
            path="payments"
            element={
              <RequirePermission moduleKey="treasury">
                <SuspenseWrapper>
                  <PaymentListPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="payments/new"
            element={
              <RequirePermission permission="treasury.create">
                <SuspenseWrapper>
                  <PaymentForm />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="payments/:id"
            element={
              <RequirePermission moduleKey="treasury">
                <SuspenseWrapper>
                  <PaymentDetailPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          <Route
            path="instruments"
            element={
              <RequirePermission moduleKey="treasury">
                <SuspenseWrapper>
                  <InstrumentListPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />

          <Route
            path="repositories"
            element={
              <RequirePermission moduleKey="treasury">
                <SuspenseWrapper>
                  <RepositoryListPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
        </Route>

        {/* Reports */}
        <Route
          path="reports"
          element={
            <RequirePermission moduleKey="reports">
              <SuspenseWrapper>
                <ReportsPage />
              </SuspenseWrapper>
            </RequirePermission>
          }
        />

        {/* Settings */}
        <Route path="settings">
          <Route
            index
            element={
              <RequirePermission moduleKey="settings">
                <SuspenseWrapper>
                  <SettingsPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="users"
            element={
              <RequirePermission moduleKey="settings">
                <SuspenseWrapper>
                  <UsersPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="roles"
            element={
              <RequirePermission moduleKey="settings">
                <SuspenseWrapper>
                  <RolesPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
          <Route
            path="company"
            element={
              <RequirePermission moduleKey="settings">
                <SuspenseWrapper>
                  <CompanyPage />
                </SuspenseWrapper>
              </RequirePermission>
            }
          />
        </Route>

        {/* Legacy redirects for backward compatibility */}
        <Route path="partners" element={<Navigate to="/sales/customers" replace />} />
        <Route path="partners/*" element={<Navigate to="/sales/customers" replace />} />
        <Route path="documents" element={<Navigate to="/sales/invoices" replace />} />
        <Route path="documents/*" element={<Navigate to="/sales/invoices" replace />} />
        <Route path="products" element={<Navigate to="/inventory/products" replace />} />

        {/* Catch-all redirect */}
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Route>
    </Routes>
  )
}
