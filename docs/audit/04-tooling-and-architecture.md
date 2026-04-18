# Tooling and Architecture Audit

Date: 2026-04-15  
Last updated: 2026-04-18 (section 2 layout deduplication; statuses + suggested commits for all points)

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

**Status:** Open (not implemented; environment / dependency alignment pending).

- `composer test` may fail under root restrictions in this environment.
- `npm run -s lint` failed because `npm-run-all` was missing at runtime.

### Recommendation

- Align `package.json` scripts and actual dependencies.
- Ensure local/CI commands are reproducible from clean checkout.

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

- **3) Tooling execution gaps** — **Status:** Open  
  - **Commit message:** `chore: align npm scripts with declared dependencies`  
  - **Description:** Add missing dev dependencies (e.g. `npm-run-all` if scripts require it), fix `npm run lint` / test scripts, and document any composer test constraints for CI or local runs.

- **4) General cleanup sequencing** — **Status:** Guidance  
  - **Commit message:** _(no standalone commit; use as PR / runbook checklist)_  
  - **Description:** Track work order: security → unused routes (with evidence) → unused code (`02-unused-code.md`) → tooling normalization (`04` items 1 and 3).
