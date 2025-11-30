# Phase 0 Progress Tracker

> **Last Updated:** 2025-11-30
> **Status:** In Progress

---

## Pre-Flight Checklist

- [x] Database backup created (`backup_before_phase0.sql` - 104K)
- [x] Read PHASE-0-REFACTOR.md
- [x] Read REFACTOR-ARCHITECTURE.md
- [x] Read REFACTOR-COMPLIANCE.md
- [x] Read CLAUDE.md
- [ ] Baseline tests passing

---

## Section 0.1: Database Schema Changes

### 0.1.1 Update Tenants Table (Personal Info Only)
- [x] Write failing test for tenant personal info columns
- [x] Create migration to add `first_name`, `last_name`, `preferred_locale`
- [x] Run migration
- [x] Test passes
- [x] Commit: `Phase 0.1.1: Add personal info columns to tenants table` (952d54b)

### 0.1.2 Create Companies Table
- [ ] Write failing test for companies table
- [ ] Create migration with all company fields
- [ ] Run migration
- [ ] Test passes
- [ ] Commit: `Phase 0.1.2: Create companies table`

### 0.1.3 Create Locations Table
- [ ] Write failing test for locations table
- [ ] Create migration with all location fields
- [ ] Run migration
- [ ] Test passes
- [ ] Commit: `Phase 0.1.3: Create locations table`

### 0.1.4 Create User-Company Memberships Table
- [ ] Write failing test for memberships table
- [ ] Create migration with membership fields
- [ ] Run migration
- [ ] Test passes
- [ ] Commit: `Phase 0.1.4: Create user_company_memberships table`

### 0.1.5 Create Company Documents Table
- [ ] Write failing test for company documents table
- [ ] Create migration
- [ ] Run migration
- [ ] Test passes
- [ ] Commit: `Phase 0.1.5: Create company_documents table`

### 0.1.6 Add company_id to Existing Tables
- [ ] Write failing test for company_id columns
- [ ] Create migration to add company_id to all business tables
- [ ] Run migration
- [ ] Test passes
- [ ] Commit: `Phase 0.1.6: Add company_id to existing tables`

### 0.1.7 Add location_id to Stock Tables
- [ ] Write failing test for location_id columns
- [ ] Create migration to add location_id to stock tables
- [ ] Run migration
- [ ] Test passes
- [ ] Commit: `Phase 0.1.7: Add location_id to stock tables`

### 0.1.8 Compliance Tables Updates
- [ ] Write failing test for compliance tables
- [ ] Create migrations for:
  - [ ] company_hash_chains
  - [ ] company_sequences
  - [ ] fiscal_years
  - [ ] fiscal_periods
  - [ ] Add chain_sequence to documents
- [ ] Run migrations
- [ ] Test passes
- [ ] Commit: `Phase 0.1.8: Create compliance tables`

**Section 0.1 Checkpoint:**
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/phpstan` passes

---

## Section 0.2: Data Migration

- [ ] 0.2.1: Create Company for each existing Tenant
- [ ] 0.2.2: Create default Location per Company
- [ ] 0.2.3: Populate company_id in all tables
- [ ] 0.2.4: Create UserCompanyMembership for tenant owner
- [ ] 0.2.5: Initialize hash chains per company
- [ ] 0.2.6: Initialize sequences per company

**Section 0.2 Checkpoint:**
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/phpstan` passes

---

## Section 0.3: Model Updates

- [ ] 0.3.1: Create Company model with relationships
- [ ] 0.3.2: Create Location model with relationships
- [ ] 0.3.3: Create UserCompanyMembership model
- [ ] 0.3.4: Create BelongsToCompany trait with global scope
- [ ] 0.3.5: Update all business models to use BelongsToCompany
- [ ] 0.3.6: Add Company/Location relationships to User model
- [ ] 0.3.7: Update Tenant model (remove company-specific fields)

**Section 0.3 Checkpoint:**
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/phpstan` passes

---

## Section 0.4: Compliance Core Refactor

- [ ] 0.4.1: Create CompanyHashChain model
- [ ] 0.4.2: Refactor FiscalHashService to scope by company
- [ ] 0.4.3: Create CompanySequence model
- [ ] 0.4.4: Refactor SequenceService to scope by company
- [ ] 0.4.5: Update event store to include company_id
- [ ] 0.4.6: Update audit logging to include company_id

**Section 0.4 Checkpoint:**
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/phpstan` passes

---

## Section 0.5: API & Middleware Updates

- [ ] 0.5.1: Create CompanyContext middleware
- [ ] 0.5.2: Update API routes to use CompanyContext
- [ ] 0.5.3: Update controllers to use company from context
- [ ] 0.5.4: Add company/location switching endpoints
- [ ] 0.5.5: Update validation rules for company scope
- [ ] 0.5.6: Update resource transformers

**Section 0.5 Checkpoint:**
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/phpstan` passes

---

## Section 0.6: Frontend Context & Switcher

- [ ] 0.6.1: Create CompanyProvider context
- [ ] 0.6.2: Create LocationProvider context
- [ ] 0.6.3: Create CompanySwitcher component
- [ ] 0.6.4: Update API client to include company header
- [ ] 0.6.5: Update AppLayout with company/location switcher
- [ ] 0.6.6: Update all queries to invalidate on company change

**Section 0.6 Checkpoint:**
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/phpstan` passes
- [ ] Playwright visual test: Company switcher works

---

## Section 0.7: Signup Flow Refactor

- [ ] 0.7.1: Update SignupController to create Company + Location
- [ ] 0.7.2: Update Signup form to collect company info
- [ ] 0.7.3: Create CompanySetupWizard component
- [ ] 0.7.4: Update onboarding flow

**Section 0.7 Checkpoint:**
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/phpstan` passes
- [ ] Playwright visual test: Signup creates company correctly

---

## Section 0.8: Testing & Verification

- [ ] 0.8.1: Verify all existing tests pass
- [ ] 0.8.2: Add integration tests for company scope
- [ ] 0.8.3: Add integration tests for location scope
- [ ] 0.8.4: Run full E2E test suite
- [ ] 0.8.5: Verify hash chain integrity
- [ ] 0.8.6: Final visual QA with Playwright

**Final Checkpoint:**
- [ ] `php artisan test` passes
- [ ] `./vendor/bin/phpstan` passes
- [ ] All Playwright tests pass
- [ ] Manual QA complete

---

## Notes & Blockers

*Document any issues encountered here*

---

## Commits Log

| Commit | Description | Status |
|--------|-------------|--------|
| Phase 0.1.1 | Add personal info columns to tenants table | Pending |
| Phase 0.1.2 | Create companies table | Pending |
| Phase 0.1.3 | Create locations table | Pending |
| Phase 0.1.4 | Create user_company_memberships table | Pending |
| Phase 0.1.5 | Create company_documents table | Pending |
| Phase 0.1.6 | Add company_id to existing tables | Pending |
| Phase 0.1.7 | Add location_id to stock tables | Pending |
| Phase 0.1.8 | Create compliance tables | Pending |
