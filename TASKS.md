# AutoERP - Task Tracker

> **Claude Code: Read this file to know what to work on.**
> Complete tasks in order. Run verification after each task. Never skip verification.

---

## Current Status

**Phase:** 6 - Inventory System
**Task:** 6.1 - Stock Management
**Last Updated:** 2025-11-30

---

## Verification Protocol

**Test-Driven Development is MANDATORY. Not optional.**

After completing ANY task:

1. ✅ Write the test FIRST (before implementation)
2. ✅ Run test — confirm it FAILS (red)
3. ✅ Write implementation
4. ✅ Run test — confirm it PASSES (green)
5. ✅ Run `./scripts/preflight.sh` (full verification)
6. ✅ Mark task as `[x]` in this file
7. ✅ Commit with conventional commit message
8. ✅ Move to next task

**If verification fails:** Fix → Re-verify → Only proceed when ALL green

**Verification Commands:**
```bash
# Backend (run from apps/api/)
./vendor/bin/phpstan analyse --level=8
./vendor/bin/pint --test
php artisan test --parallel

# Frontend (run from apps/web/)
pnpm typecheck
pnpm lint
pnpm test

# Full preflight (run from root)
./scripts/preflight.sh
```

---

## Phase 1: Infrastructure & Foundation

### 1.1 Repository Setup
- [x] Initialize pnpm monorepo
- [x] Create workspace structure:
  ```
  autoerp/
  ├── apps/
  │   ├── api/          # Laravel 12
  │   └── web/          # React + Vite
  ├── packages/
  │   └── shared/       # Shared TypeScript types
  ├── docs/             # Documentation
  ├── CLAUDE.md
  ├── TASKS.md
  └── pnpm-workspace.yaml
  ```
- [x] Create root `package.json` with workspaces
- [x] Initialize git with `.gitignore`

**Verification:**
```bash
test -f pnpm-workspace.yaml && echo "✓ Workspace config"
test -d apps/api && echo "✓ API directory"
test -d apps/web && echo "✓ Web directory"
pnpm install && echo "✓ Dependencies install"
```

### 1.2 Docker Environment
- [x] Create `docker-compose.yml` with:
  - PostgreSQL 16 with TimescaleDB extension
  - Redis 7
  - Meilisearch
  - MinIO (S3-compatible storage)
- [x] Create `docker-compose.override.yml` for local dev
- [x] Add health checks for all services
- [x] Create `scripts/setup.sh` for first-time setup

**Verification:**
```bash
docker compose up -d
docker compose ps | grep -c "healthy" | grep -q "4" && echo "✓ All services healthy"
docker compose exec postgres pg_isready && echo "✓ PostgreSQL ready"
docker compose exec redis redis-cli ping | grep -q "PONG" && echo "✓ Redis ready"
```

### 1.3 Laravel Foundation
- [x] Install Laravel 12 in `apps/api`
- [x] Configure PostgreSQL connection
- [x] Configure Redis for cache, sessions, queue
- [x] Install and configure packages:
  - `stancl/tenancy` (multi-tenancy)
  - `spatie/laravel-event-sourcing`
  - `spatie/laravel-permission`
  - `laravel/sanctum`
  - `laravel/horizon`
  - `dedoc/scramble` (API docs)
- [x] Configure strict PHP settings (declare strict_types)
- [x] Set up module structure in `app/Modules`

**Verification:**
```bash
cd apps/api
php artisan --version | grep -q "12" && echo "✓ Laravel 12"
php artisan config:cache && echo "✓ Config valid"
php artisan migrate:status && echo "✓ Database connected"
php artisan tinker --execute="Redis::ping()" && echo "✓ Redis connected"
```

### 1.4 React Foundation
- [x] Create React app with Vite in `apps/web`
- [x] Configure TypeScript strict mode
- [x] Install dependencies:
  - `@tanstack/react-query`
  - `zustand`
  - `react-router-dom`
  - `tailwindcss`
  - `lucide-react`
- [x] Set up folder structure
- [x] Create base API client with auth handling

**Verification:**
```bash
cd apps/web
pnpm build && echo "✓ Build succeeds"
pnpm typecheck && echo "✓ TypeScript valid"
pnpm lint && echo "✓ Lint passes"
```

### 1.5 CI/CD Pipeline
- [x] Create `.github/workflows/ci.yml`
- [x] Configure jobs:
  - Backend tests (PHPUnit)
  - Backend static analysis (PHPStan level 8)
  - Frontend tests (Vitest)
  - Frontend type check
  - Frontend lint
- [x] Set up CodeRabbit config (`.coderabbit.yaml`)
- [x] Configure SonarCloud

**Verification:**
```bash
act -j test && echo "✓ CI workflow runs locally"
```

### 1.6 Agent Safety Infrastructure
- [x] Create `scripts/preflight.sh`:
  ```bash
  #!/bin/bash
  set -e
  echo "🔍 Running preflight checks..."

  # Backend
  cd apps/api
  ./vendor/bin/pint --test
  ./vendor/bin/phpstan analyse --level=8
  php artisan test --parallel

  # Type Generation
  php artisan typescript:transform

  # Frontend
  cd ../web
  pnpm typecheck
  pnpm lint
  pnpm test --run

  echo "✅ All preflight checks passed!"
  ```
- [x] Install `spatie/typescript-transformer` in Laravel
- [x] Configure TypeScript transformer for DTOs
- [x] Create `tests/Fixtures/HashChainReference.php` with hardcoded test vectors
- [x] Configure Deptrac for module boundary enforcement
- [x] Create `.deptrac.yaml` with layer definitions

**Verification:**
```bash
chmod +x scripts/preflight.sh
./scripts/preflight.sh
./vendor/bin/deptrac analyse --config-file=.deptrac.yaml
```

### 2.1 Tenant Management
- [x] Create `tenants` table migration
- [x] Configure schema-based tenancy
- [x] Create tenant seeder for development
- [x] Implement tenant creation command
- [x] Add tenant middleware

**Verification:**
```bash
php artisan tenant:create test-tenant
php artisan tinker --execute="Tenant::count()" | grep -q "1"
```

### 2.2 User & Authentication
- [x] Create users table with tenant association
- [x] Implement Sanctum authentication
- [x] Create login/logout endpoints
- [x] Implement device management
- [x] Add biometric auth support structure

**Verification:**
```bash
php artisan test --filter=AuthenticationTest
```

### 2.3 RBAC
- [x] Configure spatie/permission for multi-tenant
- [x] Create default roles: admin, manager, cashier, viewer, technician, accountant
- [x] Create base permissions per module (71 permissions)
- [x] Implement permission middleware
- [x] Create role assignment endpoints

**Verification:**
```bash
php artisan test --filter=RBACTest
```

---

## Phase 3: Core Domain Models

### 3.1 Partners Module
**Step 1 - Write Tests First:**
- [x] Create `tests/Feature/Partner/CreatePartnerTest.php`:
  - Test: name is required
  - Test: email format validation
  - Test: VAT number format by country
  - Test: duplicate detection on VAT number
  - Test: successful creation returns 201
- [x] Create `tests/Feature/Partner/UpdatePartnerTest.php`
- [x] Create `tests/Feature/Partner/ListPartnersTest.php`
- [x] Create `tests/Unit/Partner/PartnerEntityTest.php`

**Step 2 - Run Tests (must fail):**
```bash
php artisan test --filter=Partner
# Expected: All tests should FAIL (classes don't exist yet)
```

**Step 3 - Implement:**
- [x] Create `App\Modules\Partner\Domain\Partner` entity
- [x] Create `App\Modules\Partner\Domain\Enums\PartnerType`
- [x] Create migrations (partners table)
- [x] Create `App\Modules\Partner\Application\DTOs\PartnerData`
- [x] Create CRUD endpoints in `App\Modules\Partner\Presentation\Controllers\`
- [x] Create permission-based route protection

**Step 4 - Verification (must pass):**
```bash
php artisan test --filter=Partner
./vendor/bin/phpstan analyse app/Modules/Partner --level=8
php artisan typescript:transform
./scripts/preflight.sh
```

### 3.2 Products Module
- [x] Create Product entity with ProductType enum
- [x] Create migrations (products table with JSONB for OEM/cross-refs)
- [x] Create ProductData DTO for API responses
- [x] Create CRUD endpoints with permission protection
- [x] Implement automotive-specific fields (OEM numbers, cross-refs)
- [ ] Add product search with Meilisearch (deferred to Phase 10)

**Verification:**
```bash
php artisan test --filter=Product
```

### 3.3 Vehicles Module
- [x] Create Vehicle entity
- [x] Create migrations
- [x] Implement VIN validation (17-char format, excludes I/O/Q)
- [x] Create vehicle CRUD endpoints
- [ ] Link vehicles to work orders (deferred to Phase 4)

**Verification:**
```bash
php artisan test --filter=Vehicle
# ✓ 48 tests passing (151 assertions)
```

---

## Phase 4: Document System

### 4.1 Unified Document Structure
- [x] Create Document aggregate root
- [x] Create migrations (documents, document_lines, document_sequences tables)
- [x] Implement document numbering service
- [x] Create document DTOs and controller
- [x] Implement document status state machine
- [x] Create type-specific routes (quotes, orders, invoices, credit-notes, delivery-notes)

**Verification:**
```bash
php artisan test --filter=Document
# ✓ 63 tests passing (197 assertions)
```

### 4.2 Document Types
- [x] Implement Quote type with metadata
- [x] Implement SalesOrder type
- [x] Implement Invoice type with fiscal fields
- [x] Implement CreditNote type
- [x] Implement DeliveryNote type with DDT

**Verification:**
```bash
php artisan test --filter=Document
# ✓ 31 document type tests passing
```

### 4.3 Document Lifecycle
- [x] Implement quote → order conversion
- [x] Implement order → invoice conversion
- [x] Implement invoice posting (status transition)
- [x] Implement cancellation
- [x] Implement credit note creation from invoice
- [ ] Add document PDF generation (deferred)
- [ ] GL integration (Phase 5)

**Verification:**
```bash
php artisan test --filter=Document
# ✓ All 94 document tests passing (258 assertions)
```

---

## Phase 5: Accounting System (Use Opus 4)

### 5.1 Chart of Accounts
- [x] Create Account entity with AccountType enum
- [x] Create migrations (accounts table with hierarchy support)
- [x] Implement account hierarchy (parent/children relationships)
- [x] Add account CRUD endpoints with tenant isolation
- [x] Add system account protection
- [ ] Create default chart of accounts seeder (deferred)

**Verification:**
```bash
php artisan test --filter=Account
# ✓ 33 tests passing (77 assertions)
```

### 5.2 Journal Entries
- [x] Create JournalEntry aggregate
- [x] Create migrations
- [x] Implement double-entry validation
- [x] Create journal entry service
- [x] Implement hash chain for posted entries

**Verification:**
```bash
php artisan test --filter=JournalEntry
# ✓ 25 tests passing (42 assertions)
```

### 5.3 Document → GL Integration
- [x] Create invoice posting handler
- [x] Generate GL entries from posted invoices
- [x] Handle credit notes with reversals
- [x] Implement payment GL entries
- [ ] Create reconciliation service (deferred)

**Verification:**
```bash
php artisan test --filter=GLIntegration
# ✓ 8 tests passing (25 assertions)
```

---

## Phase 6: Inventory System (Use Opus 4)

### 6.1 Stock Management
- [ ] Create StockLevel entity
- [ ] Create migrations (stock_levels, locations)
- [ ] Implement stock adjustment service with locking
- [ ] Create stock movement tracking
- [ ] Add low stock alerts

**Verification:**
```bash
php artisan test --filter=StockTest
```

### 6.2 Stock Movements
- [ ] Implement purchase receipt flow
- [ ] Implement sales delivery flow
- [ ] Implement stock transfers
- [ ] Implement stock adjustments
- [ ] Create movement history reporting

**Verification:**
```bash
php artisan test --filter=StockMovementTest
```

### 6.3 Delivery Notes (DDT)
- [ ] Implement DDT generation
- [ ] Link stock movements to delivery notes
- [ ] Create DDT PDF template
- [ ] Add signature capture

**Verification:**
```bash
php artisan test --filter=DeliveryNoteTest
```

---

## Phase 7: Treasury System (Use Opus 4)

### 7.1 Payment Methods
- [ ] Create PaymentMethod entity with switches
- [ ] Create migrations
- [ ] Implement country presets
- [ ] Create payment method CRUD
- [ ] Add fee calculation service

**Verification:**
```bash
php artisan test --filter=PaymentMethodTest
```

### 7.2 Payment Repositories
- [ ] Create PaymentRepository entity
- [ ] Create migrations
- [ ] Implement repository balance tracking
- [ ] Add repository CRUD endpoints

**Verification:**
```bash
php artisan test --filter=PaymentRepositoryTest
```

### 7.3 Payment Instruments
- [ ] Create PaymentInstrument aggregate
- [ ] Create migrations
- [ ] Implement instrument lifecycle (received → cleared)
- [ ] Create custody transfer service
- [ ] Add maturity tracking

**Verification:**
```bash
php artisan test --filter=PaymentInstrumentTest
```

### 7.4 Payments
- [ ] Create Payment entity
- [ ] Implement payment recording
- [ ] Create payment allocation service
- [ ] Generate GL entries from payments
- [ ] Update invoice paid status

**Verification:**
```bash
php artisan test --filter=PaymentTest
```

---

## Phase 8: Event Sourcing & Compliance

### 8.1 Event Store
- [ ] Configure spatie/laravel-event-sourcing
- [ ] Create base event classes
- [ ] Implement domain events for all aggregates
- [ ] Set up event handlers

**Verification:**
```bash
php artisan test --filter=EventStoreTest
```

### 8.2 Hash Chains
**CRITICAL: This is compliance-critical code. AI agents cannot "see" hash outputs.**

**Step 1 - Create Reference Fixture (Manual Verification Required):**
- [ ] Create `tests/Fixtures/HashChainReference.php` with hardcoded test vectors:
  ```php
  class HashChainReference
  {
      // GROUND TRUTH - Manually calculated and verified
      public const VECTORS = [
          [
              'input' => 'INV-2025-0001|2025-01-15|1500.00|EUR',
              'previous_hash' => null, // Genesis
              'expected_hash' => 'a3f2b8c9d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1',
          ],
          [
              'input' => 'INV-2025-0002|2025-01-16|2300.50|EUR',
              'previous_hash' => 'a3f2b8c9d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1',
              'expected_hash' => 'b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a7b6c5d4e3f2a1b0c9d8e7f6a5b4c3',
          ],
      ];
  }
  ```

**Step 2 - Write Test Against Reference:**
- [ ] Create `tests/Unit/Compliance/FiscalHashServiceTest.php`
- [ ] Test MUST use `HashChainReference::VECTORS` as ground truth
- [ ] Implementation MUST produce exact same hashes

**Step 3 - Implement:**
- [ ] Create `App\Modules\Compliance\Services\FiscalHashService`
- [ ] Add hash/previous_hash columns to fiscal documents
- [ ] Add hash chains to journal entries
- [ ] Create `php artisan fiscal:verify-chains` command

**Verification:**
```bash
php artisan test --filter=FiscalHashServiceTest
php artisan test --filter=HashChainTest
php artisan fiscal:verify-chains
./scripts/preflight.sh
```

### 8.3 Audit Trail
- [ ] Configure TimescaleDB hypertable
- [ ] Create audit event projector
- [ ] Implement anomaly detection queries
- [ ] Add audit reporting endpoints

**Verification:**
```bash
php artisan test --filter=AuditTrailTest
```

---

## Phase 9: Import System

### 9.1 Import Infrastructure
- [ ] Create import job tracking
- [ ] Create staging table
- [ ] Implement validation engine
- [ ] Create error reporting

**Verification:**
```bash
php artisan test --filter=ImportInfrastructureTest
```

### 9.2 Import Types
- [ ] Implement customer/supplier import
- [ ] Implement product import
- [ ] Implement stock level import
- [ ] Implement opening balance import

**Verification:**
```bash
php artisan test --filter=ImportTypesTest
```

### 9.3 Migration Wizard
- [ ] Create wizard API endpoints
- [ ] Implement dependency checking
- [ ] Add smart column mapping
- [ ] Create template generator

**Verification:**
```bash
php artisan test --filter=MigrationWizardTest
```

---

## Phase 10: Frontend Application

### 10.1 Core Layout
- [ ] Implement sidebar navigation
- [ ] Create top bar with search
- [ ] Add authentication flow
- [ ] Implement responsive layout

**Verification:**
```bash
pnpm test --filter=LayoutTest
```

### 10.2 Partner Management
- [ ] Create partner list view
- [ ] Create partner detail view
- [ ] Add partner form (create/edit)
- [ ] Implement partner search

**Verification:**
```bash
pnpm test --filter=PartnerViewsTest
```

### 10.3 Document Management
- [ ] Create document list view
- [ ] Create document detail view
- [ ] Implement document form (quote/order/invoice)
- [ ] Add document line editing
- [ ] Implement document actions (post, cancel, convert)

**Verification:**
```bash
pnpm test --filter=DocumentViewsTest
```

### 10.4 Treasury
- [ ] Create payment recording form
- [ ] Implement check register view
- [ ] Add instrument lifecycle UI
- [ ] Create payment allocation modal

**Verification:**
```bash
pnpm test --filter=TreasuryViewsTest
```

### 10.5 Dashboard
- [ ] Create main dashboard
- [ ] Add KPI widgets
- [ ] Implement recent activity
- [ ] Add quick actions

**Verification:**
```bash
pnpm test --filter=DashboardTest
```

---

## E2E Tests (Playwright)

### Critical Journeys
- [ ] User login/logout
- [ ] Create and post invoice
- [ ] Record payment and allocate
- [ ] Check lifecycle (receive → deposit → clear)
- [ ] Import products from CSV
- [ ] Generate report

**Verification:**
```bash
pnpm playwright test
```

---

## Notes & Decisions

Track any blockers, architectural decisions, or deviations here:

- [ ] Decision: [description]
- [ ] Blocker: [description]
- [ ] Change: [description]

---

## Commit Convention

Use conventional commits:
- `feat(module): description` - New feature
- `fix(module): description` - Bug fix
- `refactor(module): description` - Code change that neither fixes nor adds
- `test(module): description` - Adding tests
- `docs(module): description` - Documentation
- `chore: description` - Maintenance

---

*Last Updated: [timestamp]*
