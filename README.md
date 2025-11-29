# AutoERP Documentation Package

> Comprehensive documentation for building AutoERP, a compliance-ready ERP system
> for automotive service businesses.

---

## Quick Start

1. Copy this entire package to your project root
2. Read `CLAUDE.md` first (architecture overview)
3. Read `TASKS.md` to understand the development phases
4. Start with Phase 1: Infrastructure

---

## Documentation Structure

```
autoerp-docs/
├── CLAUDE.md              # Master architecture document (read first!)
├── TASKS.md               # Development phases and progress tracking
├── README.md              # This file
└── docs/
    ├── DATABASE.md        # Complete database schema
    ├── TREASURY.md        # Payment methods, instruments, repositories
    ├── IMPORTS.md         # Data import patterns and migration
    ├── FRONTEND.md        # React patterns and state management
    └── DESIGN-SYSTEM.md   # Visual design tokens (may be updated)
```

---

## Key Architectural Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Backend | Laravel 12 | Mature, excellent DX, PHP ecosystem |
| Database | PostgreSQL + TimescaleDB | Schema-based multi-tenancy, time-series for events |
| Frontend | React + TypeScript | Type safety, TanStack Query for server state |
| Documents | Unified table + subtypes | Single source, separate GL for accounting truth |
| Events | Two-tier (fiscal + audit) | Compliance chain + full fraud detection |
| Payments | Universal switches | One system for all payment types globally |

---

## Module Priority

### Phase 1-4: Foundation
- Infrastructure (Docker, CI/CD)
- Multi-tenancy
- Identity & RBAC
- Core domain models (Partners, Products, Vehicles)

### Phase 5-7: Critical Business Logic (Use Opus 4)
- Accounting (Chart of accounts, Journal entries, GL)
- Inventory (Stock management, movements)
- Treasury (Payments, instruments, reconciliation)

### Phase 8-10: Integration & UI
- Event sourcing & compliance
- Import system
- Frontend application

---

## Compliance Readiness

The system is designed to be certification-ready:

| Certification | Region | Status |
|---------------|--------|--------|
| NF525 | France | Architecture ready, needs POS module |
| ZATCA | Saudi Arabia | Hash chain ready |
| E-invoicing | EU (Factur-X) | Schema ready |

---

## Development Guidelines

### For Claude Code

1. **Always read CLAUDE.md** before starting any task
2. **Follow TASKS.md** in order
3. **Run verification** after each task
4. **Use conventional commits**

### Critical Rules

- ⚠️ **Never edit posted fiscal documents** — only reversals
- ⚠️ **Always use transactions** for financial operations
- ⚠️ **Lock before read** when checking stock or balances
- ⚠️ **Event first, state second** — persist event before updating state

---

## Quality Gates

### Backend
```bash
composer test          # PHPUnit
./vendor/bin/phpstan   # Level 8
./vendor/bin/pint      # Code style
```

### Frontend
```bash
pnpm test              # Vitest
pnpm lint              # ESLint
pnpm typecheck         # TypeScript
```

### E2E
```bash
pnpm playwright test
```

---

## Resources

- [Laravel 12 Docs](https://laravel.com/docs)
- [TanStack Query](https://tanstack.com/query)
- [Tailwind CSS](https://tailwindcss.com)
- [Lucide Icons](https://lucide.dev)

---

## Questions?

If something is unclear or missing from this documentation:
1. Check if it's covered in another doc file
2. Search past conversations in this Claude project
3. Ask for clarification before implementing

---

*Generated: November 2025*
*AutoERP v0.1.0*
