# Repository Guidelines

## Project Structure & Module Organization
- Monorepo managed with `pnpm-workspace.yaml`; run `pnpm install` once at the root.
- `apps/api/` hosts the Laravel 12 service layer (`app`, `routes`, `database`, `tests`) plus PHP tooling configs.
- `apps/web/` contains the Vite + React client (`src` for features, `e2e` for Playwright specs, `public` assets).
- `packages/shared/` keeps cross-application TypeScript types and helpers consumed by both apps.
- `docs/`, `TASKS.md`, and `CLAUDE.md` capture architecture and phase plans—consult them before coding.

## Build, Test, and Development Commands
- `pnpm dev` — starts every package’s dev task (Vite dev server, Laravel queues) in parallel.
- `pnpm --filter @autoerp/web dev` — focus on the web client only; pairs well with `vite preview` for QA.
- `pnpm build | test | lint | typecheck` — run workspace-wide builds, Vitest suites, ESLint, and TS checks.
- `pnpm --filter @autoerp/web test:e2e` — executes Playwright specs in `apps/web/e2e`.
- `composer test` (inside `apps/api`) — clears config cache then runs the Laravel test suite; `php artisan serve` or `sail up` bootstraps local API services.

## Coding Style & Naming Conventions
- TypeScript/React follows ESLint strict configs (`apps/web/eslint.config.js`); avoid `any`, keep hooks/functions in `camelCase`, components in `PascalCase`, and prefer 2-space indentation that matches current sources.
- PHP abides by PSR-12; format with `./vendor/bin/pint` and keep domain services under `App\Domain\*`.
- Reuse shared DTOs from `packages/shared/types` instead of redefining shapes; backend DTOs should map via Spatie Data objects.
- Keep translation keys and Tailwind utility classes alphabetized to ease diff reviews.

## Testing Guidelines
- Co-locate Vitest unit files beside the component (`FeatureCard.test.tsx`) and mirror folder names from `src/`.
- Use Testing Library queries (no `.container.querySelector`) to stay within accessibility rules.
- Laravel tests live in `apps/api/tests`; prefer Pest-style descriptive names even in PHPUnit classes, seed tenants via dedicated factories, and assert emitted events when modifying fiscal artifacts.
- Run `pnpm --filter @autoerp/web test:e2e` before merging UI-heavy work; include failing screenshots in the PR if a spec is intentionally skipped.

## Commit & Pull Request Guidelines
- Follow the existing log pattern: `Phase <major.minor.patch>: <imperative summary>` so automation can map progress (e.g., `Phase 0.1.8: Create compliance tables`).
- Each PR should link the relevant phase/task doc, describe schema or API changes, and attach screenshots for UI updates or `artisan route:list` diffs for backend endpoints.
- Ensure CI commands (`pnpm lint`, `pnpm test`, `composer test`, `./vendor/bin/phpstan`) are green and note any temporary skips with justification.

## Environment & Security Notes
- Copy `.env.example` files per app, run `php artisan key:generate`, and keep secrets in local `.env`; never commit tenant credentials or fiscal seeds.
- Prefer `docker compose up -d` for parity (PostgreSQL, Redis, Horizon). When touching database migrations, verify both single-tenant and tenant-specific paths via `php artisan migrate --database=tenant`.
