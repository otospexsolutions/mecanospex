# AutoERP Project Overview

This is the documentation for AutoERP, a compliance-ready ERP system for automotive service businesses.

## Key Technologies

*   **Backend:** Laravel 12
*   **Database:** PostgreSQL + TimescaleDB
*   **Frontend:** React + TypeScript

## Building and Running

### Backend

*   **Run tests:** `composer test`
*   **Static analysis:** `./vendor/bin/phpstan`
*   **Code style:** `./vendor/bin/pint`

### Frontend

*   **Run tests:** `pnpm test`
*   **Lint:** `pnpm lint`
*   **Typecheck:** `pnpm typecheck`

### E2E Tests

*   `pnpm playwright test`

## Development Conventions

*   Follow the development phases in `TASKS.md`.
*   Run verification after each task.
*   Use conventional commits.
*   Never edit posted fiscal documents — only reversals.
*   Always use transactions for financial operations.
*   Lock before read when checking stock or balances.
*   Event first, state second — persist event before updating state.
