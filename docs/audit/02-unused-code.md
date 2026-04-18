# Unused Code Audit

Date: 2026-04-15  
Last updated: 2026-04-18 (sections E–F and follow-up cleanup)

## A) Confirmed unused company payload fetches

Status: Implemented.

Removed unused `$companies` payload fetches from:

- `app/Http/Controllers/TruckOwnerController.php` (`index()`, `create()`, `edit()`)
- `app/Http/Controllers/TransporterController.php` (`create()`, `edit()`)
- `app/Http/Controllers/FreightBookingController.php` (`create()`, `edit()`)
- `app/Http/Controllers/VoucherPaymentController.php` (`index()`, `create()`, `edit()`)
- `app/Http/Controllers/InvoiceController.php` (`edit()`)

## B) Likely dead/unreferenced views

Status: Implemented (removed after full reference scan).

- `resources/views/dashboard_global.blade.php`
- `resources/views/coming_soon.blade.php`
- `resources/views/welcome.blade.php`
- `resources/views/voucher_payments/exports/pdf.blade.php`
- `resources/views/emails/lorry_receipt_generated.blade.php`

## C) Stale view artifact (high confidence)

Status: Implemented (removed).

- `resources/views/trucks/report.blade.php`
  - references `route('trucks.report')`, but no such route is defined.
  - no render path found in route/controller scans.

## D) Duplicate JS source (drift risk)

Status: Implemented (consolidated to one source).

- Removed duplicate: `resources/js/dynamic-dropdowns.js`
- Kept active source: `public/js/dynamic-dropdowns.js` (the file currently loaded by Blade screens via `/js/dynamic-dropdowns.js`)

## E) Debug leftovers

Status: Implemented (removed non-error logs; retained error logging only).

Removed debug/informational logs (`console.log(...)`, `Log::info(...)`, `Log::warning(...)`) across modules and kept error-path logging only (`console.error(...)` / `Log::error(...)`), including:

- `resources/views/lorry_receipts/create.blade.php`
- `resources/views/lorry_receipts/edit.blade.php`
- `resources/views/lorry_receipts/create-linked.blade.php`
- `resources/views/invoices/create.blade.php`
- `public/js/dynamic-dropdowns.js`

LR and other heavy views were cleaned so debug calls are removed rather than suppressed at runtime.

## F) Large commented-out legacy blocks

Status: Implemented.

Removed commented-out dead logic from controllers and views (large legacy `//` blocks, duplicate old methods, stale jQuery/AJAX blocks, Blade-commented UI chunks). Restored short **explanatory** comments where they clarify behavior (e.g. date fallback, GR derivation, company scope, invoice filter UX); those are not dead code.

Follow-up cleanup in the same effort:

- Removed orphan AdminLTE-style structural markers (`<!--begin::Row-->`, `<!--end::Col-->`, etc.) across `resources/views` where they no longer wrapped real markup.
- Removed commented global AJAX loader CSS from `resources/views/layouts/app.blade.php` after loader markup/script removal.
- Normalized excessive blank lines in Blade views (format-only).

Representative files touched earlier in this track:

- `app/Http/Controllers/ReportController.php`, `VoucherPaymentController.php`, `TruckController.php`, `FreightBookingController.php`, `InvoiceController.php`, `Api/DropdownController.php`
- `resources/views/transporters/create.blade.php`, `freight_bookings/create.blade.php`, `voucher_payments/edit.blade.php`, `invoices/index.blade.php`, `trucks/create.blade.php`, `layouts/app.blade.php`

## Suggested Git Commits (Per Point)

- **A) Confirmed unused company payload fetches**
  - **Commit message:** `refactor: remove unused companies payloads from controllers`
  - **Description:** Remove unused `$companies` fetches and view payload entries where Blade templates no longer consume them.

- **B) Likely dead/unreferenced views**
  - **Commit message:** `chore: delete unreferenced blade views`
  - **Description:** Remove views with no active route/controller/mail render path after full reference verification.

- **C) Stale view artifact**
  - **Commit message:** `cleanup: remove stale trucks report blade artifact`
  - **Description:** Delete `resources/views/trucks/report.blade.php` because `trucks.report` route/render path does not exist.

- **D) Duplicate JS source**
  - **Commit message:** `refactor: consolidate dynamic-dropdowns script source`
  - **Description:** Keep a single source of truth for `dynamic-dropdowns` logic and remove duplicate file drift.

- **E) Debug leftovers**
  - **Commit message:** `chore: strip production console logs from user flows`
  - **Description:** Remove debug/info/warning logs across frontend/backend modules and keep only error-path logging.

- **F) Large commented-out legacy blocks**
  - **Commit message:** `cleanup: remove commented legacy code blocks`
  - **Description:** Remove dead commented sections in controllers and Blade/JS; strip orphan structural markers and unused loader CSS in layouts; keep brief explanatory comments only; normalize view whitespace.
