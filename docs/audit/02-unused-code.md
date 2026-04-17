# Unused Code Audit

Date: 2026-04-15

## A) Confirmed unused company payload fetches

Status: Implemented.

Removed unused `$companies` payload fetches from:

- `app/Http/Controllers/TruckOwnerController.php` (`index()`, `create()`, `edit()`)
- `app/Http/Controllers/TransporterController.php` (`create()`, `edit()`)
- `app/Http/Controllers/FreightBookingController.php` (`create()`, `edit()`)
- `app/Http/Controllers/VoucherPaymentController.php` (`index()`, `create()`, `edit()`)
- `app/Http/Controllers/InvoiceController.php` (`edit()`)

## B) Likely dead/unreferenced views

- `resources/views/dashboard_global.blade.php`
- `resources/views/coming_soon.blade.php`
- `resources/views/welcome.blade.php`
- `resources/views/voucher_payments/exports/pdf.blade.php`
- `resources/views/emails/lorry_receipt_generated.blade.php`

Note: these are static-scan results; dynamic resolution can hide references.

## C) Stale view artifact (high confidence)

- `resources/views/trucks/report.blade.php`
  - references `route('trucks.report')`, but no such route is defined.
  - no render path found in route/controller scans.

## D) Duplicate JS source (drift risk)

- `resources/js/dynamic-dropdowns.js`
- `public/js/dynamic-dropdowns.js`

Two copies with diverging logic increase stale behavior risk.

## E) Debug leftovers

Heavy `console.log(...)` usage in user-facing flows, including:

- `resources/views/lorry_receipts/create.blade.php`
- `resources/views/lorry_receipts/edit.blade.php`
- `resources/views/lorry_receipts/create-linked.blade.php`
- `resources/views/invoices/create.blade.php`
- `resources/js/dynamic-dropdowns.js`
- `public/js/dynamic-dropdowns.js`

## F) Large commented-out legacy blocks

Examples:

- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/VoucherPaymentController.php`
- `app/Http/Controllers/TruckController.php`
- `resources/views/transporters/create.blade.php`

Recommended: remove commented-out dead logic and rely on git history.
