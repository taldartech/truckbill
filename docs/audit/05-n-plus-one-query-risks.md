# N+1 Query Risk Audit

Date: 2026-04-22  
Project: `truckbill`

This document lists likely N+1 query risks discovered via static code review. These are high-confidence candidates and should be validated with query logging (`DB::listen`, Telescope, Laravel Debugbar, or Clockwork) in target flows.

## Prioritized Findings

### 1) Query inside loop in daily voucher payments view (High)

- **Path:** `resources/views/voucher_payments/daily.blade.php`
- **Risk pattern:** A `LorryReceipt::where(...)->get()` runs inside a loop over payments.
- **Why it is N+1-prone:** Every rendered row can trigger a new SQL query.
- **Recommended fix:** Move the query to controller scope, prefetch by collected `linked_lr_id` values, group in memory, and render from prepared map data.
- **Status:** Resolved on 2026-04-22.
- **Implemented in:** `app/Http/Controllers/VoucherPaymentController.php` (`dailyPayments`) and `resources/views/voucher_payments/daily.blade.php`.
- **Implementation notes:** Added a single batched `LorryReceipt` preload (`whereIn` on all linked IDs, eager loading `consignor.city` and `consignee.city`) and replaced in-loop query with map lookup (`$linkedLorryReceiptsByLinkedId[$linked_lr_id]`).

### 2) Voucher lookup per LR row in transporter report view (High)

- **Path:** `resources/views/reports/transporter-report.blade.php`
- **Risk pattern:** `VoucherPayment::where('freight_booking_id', ...)->get()` inside `@foreach ($lrs as $lr)`.
- **Why it is N+1-prone:** One voucher query per LR row.
- **Recommended fix:** Eager load nested relations (`freightBookings.voucherPayments`) or pre-aggregate vouchers once by `freight_booking_id`.
- **Status:** Resolved on 2026-04-22.
- **Implemented in:** `app/Http/Controllers/ReportController.php` (`transporter_report`) and `resources/views/reports/transporter-report.blade.php`.
- **Implementation notes:** Added eager loading for `freightBookings.voucherPayments` in the report query and replaced row-level `VoucherPayment::where(...)` calls with in-memory `$fr->voucherPayments` iteration.

### 3) Invoice lookup per LR row in party ledger view (High)

- **Path:** `resources/views/reports/party-ledger.blade.php`
- **Risk pattern:** `Invoice::whereJsonContains('lr_ids', $lr->id)->first()` inside LR loop.
- **Why it is N+1-prone:** Per-row query during rendering.
- **Recommended fix:** Build an invoice map once in controller for all LR IDs and pass lookup data to the view.

### 4) Repeated `find()` and lazy relation access in freight booking show view (High)

- **Path:** `resources/views/freight_bookings/show.blade.php`
- **Risk pattern:** Repeated `FreightBooking::find(...)` in a loop, then relation access like `$fr->lr`.
- **Why it is N+1-prone:** Repeated primary-key fetches plus potential lazy-load relation queries.
- **Recommended fix:** Fetch all records in one query (`whereIn`) with eager load (`with('lr')`) before rendering.

### 5) Truck relation query per transporter in controller (Medium-High)

- **Path:** `app/Http/Controllers/TruckOwnerController.php`
- **Risk pattern:** Iterating owners and calling `$truckOwner->trucks()->pluck(...)` per owner.
- **Why it is N+1-prone:** One trucks query per truck owner.
- **Recommended fix:** Eager load `trucks` once (`with('trucks')`) and use collection access (`$truckOwner->trucks->pluck(...)`).

### 6) Freight booking loop with lazy LR relation in TDS flow (Medium-High)

- **Path:** `app/Http/Controllers/TruckOwnerController.php`
- **Risk pattern:** Loop freight bookings and access `$freightBooking->lr` without eager load.
- **Why it is N+1-prone:** Can issue one query per booking for LR.
- **Recommended fix:** Query freight bookings with `with('lr')` and only required columns.

### 7) Repeated `find()` in bulk voucher linked status update (Medium)

- **Path:** `app/Http/Controllers/VoucherPaymentController.php`
- **Risk pattern:** Loop IDs and call `VoucherLinked::find($id)` each time.
- **Why it is N+1-prone:** One query per ID.
- **Recommended fix:** Replace with batched fetch (`whereIn(...)->pluck(...)`) and decode values in memory.

### 8) Nested dashboard relations likely under-eager loaded (Medium)

- **Paths:** `app/Http/Controllers/DashboardController.php`, `resources/views/dashboard/roles/admin.blade.php`, `resources/views/dashboard/roles/moderator.blade.php`
- **Risk pattern:** Controller loads `with('lr')`, while view accesses `lr->truck->driver`.
- **Why it is N+1-prone:** Nested relations can lazy-load per row if not preloaded.
- **Recommended fix:** Eager load nested graph (`with('lr.truck.driver')`) for this dataset.

## Remediation Strategy

- **Move DB access out of Blade:** All per-row lookups should be computed in controllers/services.
- **Eager load deliberately:** Use `with()` for frequently traversed relations and nested relation paths when templates access deep graphs.
- **Batch by IDs:** Replace repeated `find()` and per-row `where(...)` calls with `whereIn(...)`.
- **Aggregate in SQL when possible:** Use `withCount`, grouped `sum`, and pre-aggregation instead of repeated relation traversal.
- **Validate improvements:** Compare query counts and endpoint/view render times before and after changes.

## Validation Checklist

- Enable query inspection in non-production environment.
- Load each affected page/report with realistic data volume.
- Verify query count does not scale linearly with row count for listed flows.
- Confirm unchanged business output after optimization.
