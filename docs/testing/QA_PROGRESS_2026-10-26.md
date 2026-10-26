# TruckBill Broker — QA Progress (2026-10-26)

Measured on branch `testcase`, PHP 8.4.21, Laravel 12.61.0, PHPUnit 11.5.55, SQLite `:memory:`.

## Why `coverage/index.html` showed ~85%

That HTML report was **full-app** line coverage of `app/` (not the critical gate).

| Area | Role in the 85% figure |
|------|------------------------|
| Models / Console / Imports / Jobs / Mail / Observers | Already ~100% |
| Services | ~99% |
| **Http/Controllers** | Dominant gap — fat web controllers (reports, freight, vouchers, LRs, invoices) |
| Dead / unreferenced files | Promo FormRequests + unused `CheckSingleDeviceLogin` (now excluded in `phpunit.xml`) |

Critical gate (`phpunit.critical.xml`) was already **100%** (11 classes / 304 lines). The 85% number was overall `app/`, mostly unexercised controller branches.

## Current measured coverage (this session)

| Metric | Value |
|--------|--------|
| Full suite | **1138 tests**, 0 failures, 7 skipped, 6542 assertions |
| **Lines** | **95.71%** (20,967 / 21,906) |
| Methods | 87.18% (1,020 / 1,170) |
| Classes | 80.30% (163 / 203) |
| Critical gate | **100%** (unchanged) |

Progress vs earlier HTML: **85.69% → 95.71%** (~+2.1k lines covered). Still **~939 lines** short of full-app 100%.

## Changes toward coverage

1. Excluded dead code from coverage in `phpunit.xml` (~94 lines).
2. Large Feature expansions: reports (HTML views), freight, vouchers, LRs, invoices, subscription checkout, Select2, onboarding/dropdown APIs, users, middleware.
3. Fixed **EwayBill** report/export to use `eway_bills` table (columns were removed from `freight_bookings`).
4. Small production fixes needed for tests: voucher route order, `bulkPayment` null `pump_id`, freight `voucherPayment` rolls back on `ValidationException`.

## Still not 100% full-app (~939 lines)

Remaining gaps are concentrated in deep controller branches (catch/log helpers, linked-LR edges, payment status transitions, report PDF/export edge cases, notification private methods). Closing them needs more targeted Feature tests / fault injection — **not claimed as done**.

## Commands

```bash
# Full suite
php -d memory_limit=1G vendor/bin/phpunit

# Full coverage HTML (writes coverage/)
php -d memory_limit=1G -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html coverage --coverage-text

# Critical gate
php -d memory_limit=1G -d xdebug.mode=coverage vendor/bin/phpunit -c phpunit.critical.xml --coverage-text
```
