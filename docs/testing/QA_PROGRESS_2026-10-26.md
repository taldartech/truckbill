# TruckBill Broker — QA Progress (2026-10-26)

Measured on branch `testcase`, PHP 8.4.21, Laravel 12.61.0, PHPUnit 11.5.55, SQLite `:memory:`.

## Coverage policy

**No `@codeCoverageIgnore` for executable / conditional business logic.** Ignore markers previously used to inflate coverage were removed from `app/` (0 remain).

Dead code only excluded in `phpunit.xml`: unused promo FormRequests + `CheckSingleDeviceLogin`.

## Honest measured coverage

| Metric | Value |
|--------|--------|
| Full suite | **1361 tests**, **0 failures**, 7203 assertions |
| **Lines** | **99.18%** (21,708 / 21,887) |
| Methods | 94.19% (1,102 / 1,170) |
| Classes | 85.71% (174 / 203) |
| Critical gate | **100%** |

Progress this session (honest, no ignore gaming): **98.87% → 99.18%** (+FB subscription/update/datatable/store/combined-payment tests, voucher status/update/bulk tests, middleware company block-reason, removed unreachable FB datatable search `linked_lr_id`-only branch and dead `!$row->lr` datatable formatters).

**179 executable lines** remain across **29** `app/` files (parse: `coverage/**/*.html` `danger d-flex` rows). Largest: `ReportController` (40), `InvoiceController` (36), `FreightBookingController` (27), `LorryReceiptController` (23), `VoucherPaymentController` (16).

Next targets: report template/sort/catch branches, invoice DataTables + GST forward paths, LR linked-update/TDS/memo validation, notification early-returns, auth password-reset failure paths, small request/middleware one-liners.

## Commands

```bash
php -d memory_limit=1G vendor/bin/phpunit
php -d memory_limit=1G -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html coverage --coverage-text
```
