# Tooling and Architecture Audit

Date: 2026-04-15  
Last updated: 2026-04-18 (sections 2–3; tooling scripts and dependency alignment)

## 1) Frontend asset pipeline ambiguity

**Status:** Open (not implemented; recommendation only).

- `vite.config.js` exists with Laravel entrypoints.
- Main app layout mostly uses static/CDN assets instead of Vite bundle loading.
- Mixed pipeline raises drift and onboarding confusion.

### Recommendation

- Standardize on one pipeline:
  - Vite-centric (recommended), or
  - static/AdminLTE-only (remove inactive Vite setup).

## 2) Duplicate/overlapping includes in layout

**Status:** Implemented (see commit suggestion below).

Previously duplicated:

- **CSS:** Bootstrap Icons (two versions), OverlayScrollbars CSS, AdminLTE theme CSS each loaded twice in `<head>`.
- **JS:** `bootstrap.bundle.min.js` plus redundant `@popperjs/core` + `bootstrap.min.js` (bundle already ships Popper + Bootstrap).

### What we did

- Single canonical `<head>` chain: Font Awesome → app CSS → Select2/jQuery/Choices as before → Source Sans → OverlayScrollbars (SRI) → Bootstrap Icons 1.11.3 (SRI) → AdminLTE → ApexCharts / jsvectormap.
- Footer: one Bootstrap 5.3.3 bundle script only; removed duplicate Popper + `bootstrap.min.js`.
- Short Blade comment noting why Popper is not loaded separately.

## 3) Tooling execution gaps

**Status:** Implemented (scripts + declared deps; local env may still need PHP/SQLite/Composer flags).

### What we did

- **`package.json`**
  - Declared **Laravel Vite stack** so `vite.config.js` matches installable packages: `vite`, `laravel-vite-plugin`, `tailwindcss`, `@tailwindcss/vite`.
  - Added scripts: `vite`, `dev:laravel`, `build:laravel` (AdminLTE theme scripts unchanged: `dev`, `compile`, etc.).
  - **`lint`** no longer requires `npm-run-all` for the default path: it runs `js-lint`, `css-lint`, `docs-lint`, and `lockfile-lint` sequentially. Use **`lint:parallel`** for the old `npm-run-all` behavior (faster, needs devDependencies installed).
- **`composer.json`**
  - Added `scripts`: `post-autoload-dump` (discover packages), `test` → `@php artisan test`, `pint` / `pint:test` for Laravel Pint.

### Follow-up on your machine

- Run **`npm install`** after pulling so `package-lock.json` picks up the new npm packages (required for reproducible `npm ci` in CI).
- **`composer test` as root:** Composer may refuse plugins unless `COMPOSER_ALLOW_SUPERUSER=1` is set; prefer a non-root user for local/CI.
- **PHPUnit / `artisan test`:** Needs a working DB driver (e.g. SQLite extension for in-memory tests per `phpunit.xml`) and valid env; failures from missing `pdo_sqlite` or MySQL in `.env` are environment-specific.

## 4) General cleanup sequencing

**Status:** Guidance (ordering reference; not a single implementable ticket).

1. Security fixes first.
2. Route cleanup (from `03-unused-routes.md`) with runtime evidence.
3. Unused code cleanup (from `02-unused-code.md`).
4. Tooling/pipeline normalization.

---

## Suggested Git Commits (Per Point)

- **1) Frontend asset pipeline ambiguity** — **Status:** Open  
  - **Commit message:** `chore: standardize frontend asset pipeline (vite vs static)`  
  - **Description:** Choose one pipeline (Vite bundle entrypoints or static/AdminLTE-only), remove or wire the unused path, and document the decision so new assets are not added through two channels.

- **2) Duplicate/overlapping includes in layout** — **Status:** Implemented  
  - **Commit message:** `fix(layout): dedupe app layout CSS and Bootstrap JS includes`  
  - **Description:** Remove duplicate Bootstrap Icons, OverlayScrollbars, and AdminLTE CSS in `resources/views/layouts/app.blade.php`; load Bootstrap 5 once via `bootstrap.bundle.min.js` and drop redundant Popper + `bootstrap.min.js`; preserve `buttons.css` / `brand.css` after AdminLTE for override order.

- **3) Tooling execution gaps** — **Status:** Implemented  
  - **Commit message:** `chore: align composer and npm tooling scripts with dependencies`  
  - **Description:** Declare Vite/Tailwind/Laravel plugin in `package.json` to match `vite.config.js`; make default `npm run lint` work without `npm-run-all`; add `lint:parallel` for the previous behavior; add Composer `test` / `pint` / `post-autoload-dump` scripts. Regenerate `package-lock.json` with `npm install` after merge.

- **4) General cleanup sequencing** — **Status:** Guidance  
  - **Commit message:** _(no standalone commit; use as PR / runbook checklist)_  
  - **Description:** Track work order: security → unused routes (with evidence) → unused code (`02-unused-code.md`) → tooling normalization (`04` items 1 and 3).
