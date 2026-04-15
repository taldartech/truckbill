# Codebase Audit Report

Date: 2026-04-15  
Project: `truckbill`  
Scope: repository-wide read-only audit of tracked files (`git ls-files`)

## Split Documents

This consolidated report has now been split into category-based documents:

- `docs/audit/README.md`
- `docs/audit/01-security-risks.md`
- `docs/audit/02-unused-code.md`
- `docs/audit/03-unused-routes.md`
- `docs/audit/04-tooling-and-architecture.md`

Use the split docs for active cleanup tracking. Keep this file as legacy consolidated context.

## Audit Summary

- Total tracked files reviewed: `828`
- Primary focus: unwanted/unused code and key risk issues
- Method: static analysis (route map, cross-file references, pattern scans, and manual validation of high-impact findings)
- Confidence: medium-high overall (some unused-file findings are inherently probabilistic without runtime tracing)

## Priority Findings

### 1) High - Potential cross-company data exposure in ID-based API endpoints

- **Files**
  - `routes/api-routes.php`
  - `app/Http/Controllers/FreightBookingController.php`
- **Evidence**
  - API endpoints expose `/api/transporters/{id}` and `/api/trucks/{id}`.
  - `getTransporter($id)` and `getTruck($id)` fetch records by ID and return results without visible company/tenant ownership checks.
- **Risk**
  - In a multi-company setup, authenticated users may enumerate IDs and access records outside their company boundary.
- **Recommended action**
  - Enforce tenant/company scoping in these queries (e.g., constrain by current company context before returning records).
- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.
- Question - 
  - Is the above routes are usable in any code?
 - Answer -
   - `GET /api/trucks/{id}` is actively used in Blade JS (for example in `resources/views/lorry_receipts/create.blade.php` and `resources/views/lorry_receipts/edit.blade.php` via `fetch(`/api/trucks/${truckId}`)`).
   - `GET /api/transporters/{id}` does not appear to have a direct static reference in current `resources/` scans, but it is still a live API route and can be called externally or by dynamic code.
   - `GET /api/transporters/{transporter}/trucks` is actively used in `resources/views/freight_bookings/create.blade.php`.
   - So: yes, this route group is usable and partially in active use; security scoping should be fixed regardless of current usage density.

### 2) Medium - Sensitive request payloads are logged

- **Files**
  - `app/Http/Controllers/FreightBookingController.php`
  - `app/Http/Controllers/VoucherPaymentController.php`
- **Evidence**
  - Controller methods log full request payloads via `$request->all()` in normal and exception paths.
- **Risk**
  - Financial/operational and potentially sensitive user data may be retained in logs.
- **Recommended action**
  - Replace full payload logging with a strict allowlist of non-sensitive fields.
- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 3) Medium - Frontend asset pipeline ambiguity (Vite configured but mostly bypassed)

- **Files**
  - `vite.config.js`
  - `resources/views/welcome.blade.php`
  - `resources/views/layouts/app.blade.php`
  - `routes/web.php`
  - `package.json`
- **Evidence**
  - Vite is configured for default Laravel entrypoints.
  - `@vite(...)` appears only in `welcome.blade.php`.
  - App root redirects to dashboard, and main app layout uses static/CDN assets instead of Vite bundles.
  - Lint command dependency appears incomplete (`npm-run-all` missing at runtime).
- **Risk**
  - Build/tooling confusion, onboarding friction, and higher chance of stale frontend assets.
- **Recommended action**
  - Standardize on one asset pipeline (Vite or static/AdminLTE flow), remove obsolete path, and align scripts in `package.json`.
- **Confidence / false-positive risk**
  - Medium-high confidence, medium false-positive risk (could be intentionally hybrid).
- Question - 
  - Explain the above changes to be done?
 - Answer -
   - Pick one frontend asset strategy and remove the other to avoid drift:
     - Option A (`recommended`): use Vite as the single pipeline.
     - Option B: keep static/AdminLTE direct includes and remove unused Vite setup.
   - If choosing Vite (Option A), do these changes:
     - Move common CSS/JS entrypoints to `resources/css/app.css` and `resources/js/app.js`.
     - Replace direct/duplicate `<script>` and `<link>` includes in `resources/views/layouts/app.blade.php` with `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
     - Keep third-party assets either imported through Vite or as explicit CDN includes, but not both.
     - Update `package.json` scripts so build/lint commands run consistently (`dev`, `build`, `lint`), and ensure required dev dependencies exist.
     - Remove unused `welcome.blade.php` Vite-only path if the app always redirects to dashboard.
   - If choosing static/AdminLTE (Option B), do these changes:
     - Remove `vite.config.js` and unused Vite entry files/references.
     - Keep one canonical static asset list in layout and de-duplicate includes.
     - Align `package.json` scripts with the static flow so teammates do not expect Vite.
   - End goal: one source of truth for assets, reproducible build scripts, and no duplicate loading paths.

### 4) Medium - Likely dead/unwanted Blade views

- **Likely unused files**
  - `resources/views/dashboard_global.blade.php`
  - `resources/views/coming_soon.blade.php`
  - `resources/views/welcome.blade.php`
  - `resources/views/voucher_payments/exports/pdf.blade.php`
  - `resources/views/emails/lorry_receipt_generated.blade.php`
- **Evidence**
  - No active static references found from controllers/routes/mail flow in code scans.
- **Risk**
  - Extra maintenance overhead and ambiguity about live behavior.
- **Recommended action**
  - Confirm runtime usage; archive/remove verified dead views.
- **Confidence / false-positive risk**
  - Medium confidence, medium false-positive risk (dynamic resolution can hide usage).

### 5) Low-Medium - `trucks/report` view appears unreachable and references missing route

- **Files**
  - `resources/views/trucks/report.blade.php`
  - `routes/web.php`
- **Evidence**
  - View references `route('trucks.report')`.
  - No matching named route found.
  - No route/controller render path found for this view.
- **Risk**
  - Runtime failure if rendered; likely stale artifact.
- **Recommended action**
  - Either add and wire the missing route or remove the view.
- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 6) Low-Medium - Large commented-out legacy code blocks

- **Files (examples)**
  - `app/Http/Controllers/ReportController.php`
  - `app/Http/Controllers/VoucherPaymentController.php`
  - `app/Http/Controllers/TruckController.php`
  - `resources/views/transporters/create.blade.php`
- **Evidence**
  - Substantial multi-line commented blocks containing legacy logic.
- **Risk**
  - Increased cognitive load and higher regression risk during refactors.
- **Recommended action**
  - Remove dead commented code and rely on git history for retrieval.
- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 7) Low - Debug leftovers (`console.log`) in production-facing JS/Blade

- **Files (examples)**
  - `resources/views/lorry_receipts/edit.blade.php`
  - `resources/views/lorry_receipts/create.blade.php`
  - `resources/views/lorry_receipts/create-linked.blade.php`
  - `resources/views/invoices/create.blade.php`
  - `resources/js/dynamic-dropdowns.js`
  - `public/js/dynamic-dropdowns.js`
- **Evidence**
  - Multiple `console.log(...)` calls in user-facing screens and shared scripts.
- **Risk**
  - Noisy debugging output and potential disclosure of operational context in browser console.
- **Recommended action**
  - Remove or gate logs behind non-production flags.
- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 8) Low - `env()` usage in controllers (config anti-pattern)

- **Files**
  - `app/Http/Controllers/FreightBookingController.php`
  - `app/Http/Controllers/LorryReceiptController.php`
  - `app/Http/Controllers/PaymentController.php`
- **Evidence**
  - Direct `env(...)` access in controller code for app/tds values.
- **Risk**
  - Inconsistent behavior under config caching and weaker config discipline.
- **Recommended action**
  - Move values to config files and consume via `config(...)`.
- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 9) Low - Duplicate/overlapping CSS/JS includes in main layout

- **File**
  - `resources/views/layouts/app.blade.php`
- **Evidence**
  - Multiple repeated library includes (AdminLTE-related assets and others).
- **Risk**
  - Increased payload and higher chance of style/script conflicts.
- **Recommended action**
  - De-duplicate includes and define a single canonical asset list.
- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 10) Low - Duplicate source/public JS for dynamic dropdowns (drift risk)

- **Files**
  - `resources/js/dynamic-dropdowns.js`
  - `public/js/dynamic-dropdowns.js`
  - Blade screens loading `/js/dynamic-dropdowns.js`
- **Evidence**
  - Two copies exist and differ; views load public copy directly.
- **Risk**
  - Behavior drift when one copy is updated and the other is not.
- **Recommended action**
  - Keep a single source of truth and generate/deploy from one pipeline.
- **Confidence / false-positive risk**
  - Medium confidence, medium false-positive risk.

## Tooling/Execution Observations

- `composer test` failed in this environment because Composer root execution is blocked without explicit allowance.
- `npm run -s lint` failed due to missing `npm-run-all` command in runtime.
- `php artisan route:list --except-vendor` succeeds and reports `279` routes.

## Suggested Cleanup Plan

1. Address high-risk authorization scoping first.
2. Remove sensitive payload logging and introduce logging allowlists.
3. Decide and enforce a single frontend asset pipeline.
4. Validate and remove dead views/routes/assets.
5. Clean debug logs and commented-out legacy blocks.
6. Move controller `env()` usage into config and consume via `config()`.

## Additional Confirmed Findings (Company Fetch Pattern)

### 11) Medium - Confirmed unused `$companies` fetches in multiple controllers

- **Observation**
  - Several controller actions fetch all companies and pass `$companies` to views where `$companies` is not used at all.
  - This creates unnecessary queries and increases controller/view noise.

- **Confirmed controller actions with unused `$companies`**
  - `app/Http/Controllers/TruckOwnerController.php`
    - `index()`
    - `create()`
    - `edit()`
  - `app/Http/Controllers/TransporterController.php`
    - `create()`
    - `edit()`
  - `app/Http/Controllers/FreightBookingController.php`
    - `create()`
    - `edit()`
  - `app/Http/Controllers/VoucherPaymentController.php`
    - `index()`
    - `create()`
    - `edit()`
  - `app/Http/Controllers/InvoiceController.php`
    - `edit()`

- **Validated against views**
  - `resources/views/truck_owner/index.blade.php`
  - `resources/views/truck_owner/create.blade.php`
  - `resources/views/truck_owner/edit.blade.php`
  - `resources/views/transporters/create.blade.php`
  - `resources/views/transporters/edit.blade.php`
  - `resources/views/freight_bookings/create.blade.php`
  - `resources/views/freight_bookings/edit.blade.php`
  - `resources/views/voucher_payments/index.blade.php`
  - `resources/views/voucher_payments/create.blade.php`
  - `resources/views/voucher_payments/edit.blade.php`
  - `resources/views/invoices/edit.blade.php`
  - No effective `$companies` rendering/iteration found in these views.

- **Risk**
  - Extra DB reads (`Company::all()` in many requests) and higher maintenance overhead.

- **Recommended action**
  - Remove unused `$companies` queries and remove `companies` from `compact(...)` / view payloads in the affected actions.
  - Keep company fetches only where views actually render company selectors/filters.

- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 12) Medium - Company context inconsistency risk (super admin vs user company)

- **Observation**
  - Multiple forms set hidden `company_id` from `Auth::user()->company_id`.
  - For `super_admin`, active company context is often session-driven (`super_admin_company_id`), not necessarily `Auth::user()->company_id`.

- **Risk**
  - Wrong or null `company_id` can be submitted in super-admin workflows, causing tenant-context inconsistencies.

- **Recommended action**
  - Standardize a single source of truth for active company context in forms/controllers.
  - For super-admin flows, prefer selected company session context; for regular users, use `Auth::user()->company_id`.
  - Add guard validation that submitted `company_id` matches allowed context for current user role.

- **Confidence / false-positive risk**
  - Medium-high confidence, medium false-positive risk (depends on intended super-admin operating model).

## Consolidated Unused Code Inventory (Beyond `Company::all()`)

This section lists all currently **statically confirmed** and **high-confidence likely** unused/unwanted code artifacts identified during the audit.

### 13) Medium - Unused controller payload variables (non-company scope)

- **Confirmed unused payload variable**
  - `app/Http/Controllers/TruckOwnerController.php`
    - `index()` passes `companies` (unused in `truck_owner/index`) while only `truckTypes` is used.
  - Note: same pattern is already covered in Finding 11; repeated here for complete unused-code inventory.

- **Likely redundant local variables in Blade**
  - `resources/views/truck_owner/edit.blade.php` defines `$canEditCompany` but does not use it in conditional rendering.
  - `resources/views/transporters/edit.blade.php` defines `$canEditCompany` but does not use it in conditional rendering.
  - `resources/views/invoices/create.blade.php` defines `$canEditCompany` but no effective usage found.
  - `resources/views/transporters/create.blade.php` defines `$canEditCompany` in multiple places; one declaration appears redundant.

- **Recommended action**
  - Remove unused local Blade variables and duplicate declarations.

- **Confidence / false-positive risk**
  - Medium-high confidence, low-medium false-positive risk (depends on hidden template branches).

### 14) Medium - Likely dead/unreferenced views

- **Files**
  - `resources/views/dashboard_global.blade.php`
  - `resources/views/coming_soon.blade.php`
  - `resources/views/welcome.blade.php`
  - `resources/views/voucher_payments/exports/pdf.blade.php`
  - `resources/views/emails/lorry_receipt_generated.blade.php`

- **Evidence**
  - No active static route/controller/mail references found in repository scans.

- **Recommended action**
  - Confirm runtime usage and remove/archive if unused.

- **Confidence / false-positive risk**
  - Medium confidence, medium false-positive risk (dynamic resolution can hide references).

### 15) High-confidence stale view artifact - `trucks/report`

- **File**
  - `resources/views/trucks/report.blade.php`

- **Evidence**
  - References `route('trucks.report')` but no such named route exists.
  - No route/controller path currently renders this view.

- **Recommended action**
  - Remove the view or wire a real route/controller if feature is required.

- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 16) Low-Medium - Duplicate JS source paths (single-feature duplication)

- **Files**
  - `resources/js/dynamic-dropdowns.js`
  - `public/js/dynamic-dropdowns.js`

- **Evidence**
  - Two copies exist and differ; UI screens load `/js/dynamic-dropdowns.js` directly.

- **Why this is unwanted code**
  - One file usually becomes stale; maintenance splits across two sources for same behavior.

- **Recommended action**
  - Keep one source of truth and remove the duplicate file.

- **Confidence / false-positive risk**
  - Medium confidence, medium false-positive risk.

### 17) Low-Medium - Commented-out legacy logic blocks (effectively dead code)

- **Files (examples)**
  - `app/Http/Controllers/ReportController.php`
  - `app/Http/Controllers/VoucherPaymentController.php`
  - `app/Http/Controllers/TruckController.php`
  - `resources/views/transporters/create.blade.php`

- **Evidence**
  - Large multi-line blocks of disabled old logic.

- **Recommended action**
  - Delete commented legacy blocks and keep history in git.

- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

### 18) Low - Debug leftovers (non-production useful code)

- **Files (examples)**
  - `resources/views/lorry_receipts/edit.blade.php`
  - `resources/views/lorry_receipts/create.blade.php`
  - `resources/views/lorry_receipts/create-linked.blade.php`
  - `resources/views/invoices/create.blade.php`
  - `resources/js/dynamic-dropdowns.js`
  - `public/js/dynamic-dropdowns.js`

- **Evidence**
  - Multiple `console.log(...)` statements and debug traces in user-facing paths.

- **Why this is unwanted code**
  - Not part of business behavior; adds console noise and maintenance clutter.

- **Recommended action**
  - Remove or guard behind explicit non-production debug flags.

- **Confidence / false-positive risk**
  - High confidence, low false-positive risk.

## Final Note on “All Unused Code”

- Static analysis can identify a high-confidence majority of unused code, but cannot prove absolute non-usage for dynamic runtime paths.
- This report now includes all currently identified unused/unwanted code categories from repository scans, with confidence levels to prioritize safe cleanup.

