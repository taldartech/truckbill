# Refactoring and Optimization Opportunities

Date: 2026-04-29  
Project: `truckbill`

This document captures project-wide refactoring and performance optimization opportunities based on static code review. Findings are prioritized by impact and implementation risk.

## Critical Findings

### 1) Query-heavy logic inside export blades

- **Why it matters:** Export runtime and memory usage scale poorly with row count; high timeout risk.
- **Where:**
  - `resources/views/reports/exports/dispatch.blade.php`
  - `resources/views/reports/exports/party-bill.blade.php`
- **Pattern:** DB lookups inside `@foreach` loops (for invoices/LRs).
- **Refactor approach:** Move all data access to export/controller layer, prebuild lookup maps, keep Blade as presentation-only.
- **Status:** Partially resolved on 2026-04-29.
- **Implemented in:** `app/Exports/DispatchExport.php`, `resources/views/reports/exports/dispatch.blade.php`, `app/Exports/PartyBillExport.php`, `resources/views/reports/exports/party-bill.blade.php`.
- **Implementation notes:** Replaced in-blade invoice/LR queries with export-layer prefetch maps (`invoiceByLrId`, `lorryReceiptsById`) and view lookups.

### 2) Controller-to-controller business execution

- **Why it matters:** Hidden side effects and tight request coupling reduce testability and increase regression risk.
- **Where:**
  - `app/Http/Controllers/VoucherPaymentController.php`
  - `app/Http/Controllers/FreightBookingController.php`
- **Pattern:** Calling another controller method for core domain operation.
- **Refactor approach:** Extract shared domain services (for advance adjustment/payment orchestration) and invoke service methods directly.
- **Status:** Resolved on 2026-04-29.
- **Implemented in:** `app/Services/TransporterAdvanceLedgerService.php`, `app/Http/Controllers/AdvancePaymentController.php`, `app/Http/Controllers/VoucherPaymentController.php`, `app/Http/Controllers/FreightBookingController.php`.
- **Implementation notes:** Replaced controller-to-controller calls to `AdvancePaymentController::store()` with a shared service method `TransporterAdvanceLedgerService::recordAdvancePayment(...)` and reused the same balance validation/error behavior.

### 3) Non-atomic combined payment orchestration

- **Why it matters:** Multi-step write flow can partially commit and leave inconsistent state.
- **Where:** `app/Http/Controllers/FreightBookingController.php` (`storeCombinedPayment`)
- **Pattern:** One step commits before linked artifact creation in a separate transaction.
- **Refactor approach:** Use one orchestration service and one transaction boundary for all coupled writes.
- **Status:** Resolved on 2026-04-29.
- **Implemented in:** `app/Http/Controllers/FreightBookingController.php`.
- **Implementation notes:** Combined payment now uses the same internal voucher-processing routine as single voucher payment, but executes voucher creation, linked voucher creation, and linking updates under one transaction boundary.

### 4) Freight booking create flow lacks full transactional boundary

- **Why it matters:** Booking create + LR update + credit changes can diverge during failure.
- **Where:** `app/Http/Controllers/FreightBookingController.php` (`store`)
- **Refactor approach:** Wrap coupled writes in `DB::transaction(...)`; emit notifications/events after commit.
- **Status:** Resolved on 2026-04-29.
- **Implemented in:** `app/Http/Controllers/FreightBookingController.php`.
- **Implementation notes:** Wrapped freight booking create, LR linkage/status update, and company credit decrement in one transaction; moved cancellation and LR status notifications to `DB::afterCommit(...)`.

## High Findings

### 5) Very large controllers with mixed concerns

- **Why it matters:** Slower delivery, higher bug probability, hard onboarding.
- **Where:**
  - `app/Http/Controllers/FreightBookingController.php`
  - `app/Http/Controllers/VoucherPaymentController.php`
  - `app/Http/Controllers/ReportController.php`
- **Pattern:** Querying, validation, business rules, formatting, and side effects in same methods.
- **Refactor approach:** Split into query services, workflow/domain services, FormRequests, and thin controllers.

### 6) Report endpoints rely heavily on in-memory shaping

- **Why it matters:** Large datasets become slow and memory-heavy.
- **Where:** `app/Http/Controllers/ReportController.php` (`pump`, `bank`, `cash`)
- **Pattern:** Assemble/merge/filter in PHP, paginate collections after loading.
- **Refactor approach:** Push filtering/grouping into SQL and paginate at DB level.

### 7) Dashboard metrics repeatedly computed in PHP loops

- **Why it matters:** Repeated full-collection scans and duplicated calculations per role.
- **Where:** `app/Http/Controllers/DashboardController.php`
- **Refactor approach:** Create `DashboardMetricsService` with aggregate SQL/subqueries and shared metric methods.

### 8) `all()->where(...)` and post-fetch filtering patterns

- **Why it matters:** Pulls unnecessary rows into memory.
- **Where:**
  - `app/Http/Controllers/TruckOwnerBankAccountController.php`
  - `app/Http/Controllers/TransporterBankAccountController.php`
  - `app/Http/Controllers/InvoiceController.php`
- **Refactor approach:** Replace with constrained queries (`where(...)->get()`) and DB-side ownership checks.
- **Status:** Resolved on 2026-04-30.
- **Implemented in:** `app/Http/Controllers/TruckOwnerBankAccountController.php`, `app/Http/Controllers/TransporterBankAccountController.php`, `app/Http/Controllers/InvoiceController.php`.
- **Implementation notes:** Replaced `all()->where(...)` and unscoped `all()` usage with query-constrained `where(...)->get()` plus company-aware filtering and deterministic ordering.

### 9) Export-layer N+1 in transporter export

- **Why it matters:** Export latency increases rapidly with row count.
- **Where:** `app/Exports/TransporterReportExport.php`
- **Pattern:** Per-row payment lookup.
- **Refactor approach:** Preload/aggregate voucher totals keyed by freight booking id.

## Medium Findings

### 10) Validation strategy is inconsistent

- **Why it matters:** Rules drift across endpoints and error behavior becomes inconsistent.
- **Where:** Several inline validation blocks in major controllers.
- **Refactor approach:** Migrate high-change flows to dedicated `FormRequest` classes first.

### 11) Transaction usage style is inconsistent

- **Why it matters:** Early-return and rollback edge cases become harder to reason about.
- **Where:** Payment and freight workflows in major controllers.
- **Refactor approach:** Standardize on `DB::transaction(...)` with exceptions and post-commit side effects.
- **Status:** Partially resolved on 2026-04-30.
- **Implemented in:** `app/Http/Controllers/VoucherPaymentController.php`.
- **Implementation notes:** Standardized `store`, `bulkUpdateStatus`, and `destroy` flows to `DB::transaction(...)` with exception-based rollback control; moved status notifications to execute after successful commit path.

### 12) Business logic duplication in TDS/settlement calculations

- **Why it matters:** Formula changes require multi-file edits and can drift.
- **Where:**
  - `app/Http/Controllers/FreightBookingController.php`
  - `app/Http/Controllers/VoucherPaymentController.php`
  - `app/Http/Controllers/TruckOwnerController.php`
- **Refactor approach:** Centralize in dedicated domain calculators/services.

### 13) Blade templates still contain non-trivial logic

- **Why it matters:** Harder to test and easy to reintroduce query inefficiencies.
- **Where:** report/export blades and long freight booking templates.
- **Refactor approach:** Move logic to controller/view-model layer, keep templates render-only.

## Low Findings

### 14) Minor cleanup and consistency debt

- **Why it matters:** Adds noise and cognitive overhead over time.
- **Where:** scattered unused imports and formatting inconsistencies.
- **Refactor approach:** incremental cleanup during touched-file changes.

### 15) Repeated plan/role guard code

- **Why it matters:** Duplicate checks can drift in behavior.
- **Where:** multiple controllers handling plan expiry and role gates.
- **Refactor approach:** push to middleware/policies/shared guards.

## Refactoring Roadmap

### Phase 1: Quick Wins (1-3 days)

- Remove DB lookups from export blades and preload maps in export/controller classes.
- Replace `all()->where` and heavy in-memory filters with constrained DB queries.
- Convert highest-traffic write endpoints to FormRequests.
- Add query-count baselines for top report/export endpoints.

### Phase 2: Structural Improvements (1-3 weeks)

- Extract payment/settlement domain services and remove controller-to-controller calls.
- Introduce report query services to isolate filter/aggregation logic.
- Centralize dashboard metrics in one read service.
- Standardize transaction boundaries and post-commit notifications.

### Phase 3: Hardening and Scale (3+ weeks)

- Add integration tests for multi-step financial workflows and rollback scenarios.
- Add regression checks for query counts in critical reports/exports.
- Introduce read-model or caching strategy for large report ranges.
- Define architecture guardrails (no model queries in Blade, controller-size limits, service entry points for write flows).

## Suggested Execution Order

1. Export blade query removals and report/export N+1 fixes  
2. Service extraction for payment/settlement flows  
3. Dashboard metrics centralization  
4. Validation and transaction standardization  
5. Architecture guardrails + test hardening
