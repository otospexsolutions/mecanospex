# Repository Guidelines

## Project Structure & Module Organization
This pnpm monorepo runs every workspace task from `/` via `pnpm-workspace.yaml`. `apps/api/` holds the Laravel 12 backend (domain logic under `app/Domain`, HTTP surfaces in `routes`, persistence in `database`, tests in `tests`). `apps/web/` provides the Vite + React client with feature code in `src`, Playwright coverage in `e2e`, and static assets in `public`. Shared DTOs, utilities, and TS configs live in `packages/shared/`. Architecture references live in `docs/`, `TASKS.md`, and `CLAUDE.md`; review them before starting work.

## Build, Test, and Development Commands
Run `pnpm install` once, then `pnpm dev` to boot the stack (Vite dev server, queues, Horizon). Use `pnpm --filter @autoerp/web dev` for client-only work, and `composer test` within `apps/api` for backend suites—pair with `php artisan serve` or Sail for a local API. CI parity means running `pnpm build`, `pnpm lint`, `pnpm test`, `pnpm typecheck`, and `./vendor/bin/phpstan` before pushing.

## Coding Style & Naming Conventions
TypeScript follows the strict config in `apps/web/eslint.config.js`: two-space indentation, no `any`, hooks and helpers in `camelCase`, components in `PascalCase`. Favor functional components, Testing Library queries, and alphabetized Tailwind classes. PHP code must satisfy PSR-12, be formatted via `./vendor/bin/pint`, and encapsulate business rules in `App/Domain/*` services; hydrate transport types through Spatie Data objects drawn from `packages/shared/types`.

## Testing Guidelines
Mirror feature folders and append `.test.tsx` files beside the components they verify. Rely on Vitest plus Testing Library; avoid DOM selectors outside the accessibility API. Backend tests live in `apps/api/tests`, prefer Pest-style, leverage tenant factories for multi-tenant cases, and assert emitted domain events when fiscal records change. Before merging UI work, run `pnpm --filter @autoerp/web test:e2e` and document any intentionally skipped specs with screenshots.

## Commit & Pull Request Guidelines
Commits must follow `Phase <major.minor.patch>: <imperative summary>` (example: `Phase 0.1.8: Create compliance tables`). PRs reference the relevant phase document, explain schema or API changes, and attach screenshots or `artisan route:list` diffs when endpoints move. Confirm all CI commands and `composer test` succeed, note temporary skips, and request design or product review links for UX-visible changes.

## Security & Configuration Tips
Copy each `.env.example`, run `php artisan key:generate`, and store secrets locally only. Start dependencies with `docker compose up -d` to match CI (PostgreSQL, Redis, Horizon). After editing migrations, run `php artisan migrate` plus `php artisan migrate --database=tenant` to cover single-tenant and tenant paths. Never commit tenant credentials, fiscal seeds, or real documents.
