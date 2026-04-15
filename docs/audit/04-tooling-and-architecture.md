# Tooling and Architecture Audit

Date: 2026-04-15

## 1) Frontend asset pipeline ambiguity

- `vite.config.js` exists with Laravel entrypoints.
- Main app layout mostly uses static/CDN assets instead of Vite bundle loading.
- Mixed pipeline raises drift and onboarding confusion.

### Recommendation

- Standardize on one pipeline:
  - Vite-centric (recommended), or
  - static/AdminLTE-only (remove inactive Vite setup).

## 2) Duplicate/overlapping includes in layout

- `resources/views/layouts/app.blade.php` has repeated includes for some libraries.

### Recommendation

- Keep one canonical include list and remove duplicates.

## 3) Tooling execution gaps

- `composer test` may fail under root restrictions in this environment.
- `npm run -s lint` failed because `npm-run-all` was missing at runtime.

### Recommendation

- Align `package.json` scripts and actual dependencies.
- Ensure local/CI commands are reproducible from clean checkout.

## 4) General cleanup sequencing

1. Security fixes first.
2. Route cleanup (from `03-unused-routes.md`) with runtime evidence.
3. Unused code cleanup (from `02-unused-code.md`).
4. Tooling/pipeline normalization.
