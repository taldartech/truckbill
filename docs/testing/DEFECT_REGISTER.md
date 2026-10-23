# TruckBill Broker Module — Defect Register & Resolution Log

This document tracks all defects, inconsistencies, and test infrastructure blockers identified and resolved during the audit, implementation, and execution of the TruckBill Broker automated test suite.

---

## 1. Defect Summary Log

| Defect ID | Severity | Category | Component | Description | Resolution Status | Verified By |
|---|---|---|---|---|---|---|
| **DEF-001** | High | Test Infrastructure | `ListingQueryStringTest` | Pre-existing PHP 8.4 type error: passing empty array to Mockery request builder caused `array_diff_key` failure. | **RESOLVED** | `ListingQueryStringTest.php` |
| **DEF-002** | High | Test Infrastructure | `LorryReceiptTest` | Pre-existing Mockery mock expectation mismatch: `whereDoesntHave` call on relation query returned null instead of query builder. | **RESOLVED** | `LorryReceiptTest.php` |
| **DEF-003** | Medium | Compatibility | `DashboardController` | MySQL-specific raw SQL `DATE_FORMAT(created_at, "%b %Y")` failed in SQLite in-memory test database. | **RESOLVED** (Registered custom SQLite PDO function) | `DashboardRoleViewsTest.php` |
| **DEF-004** | Low | Data Fixture | `CustomerAndTransporterPortalTest` | Model schema constraint violations: `good_types.name`, `lorry_receipts.lane`, `invoice_payments.mode`, `voucher_payments.payment_type`. | **RESOLVED** (Aligned fixture creation with migrations) | `CustomerAndTransporterPortalTest.php` |
| **DEF-005** | Medium | Route Middleware | `CustomerPortalController` | Request attribute injection required by `customer.consignee` middleware before hitting controller actions. | **RESOLVED** (Invoked full HTTP middleware stack with assigned user consignees) | `CustomerAndTransporterPortalTest.php` |

---

## 2. Detailed Defect Investigations & Fix Records

### DEF-001: Pre-existing Mockery Failure in `ListingQueryStringTest`
- **Location**: `tests/Unit/Support/ListingQueryStringTest.php`
- **Root Cause**: PHP 8.4 stricter array type requirements in `ListingQueryString::allExcept()` when processing Mockery request objects.
- **Fix Applied**: Updated request fixture initialization to provide a concrete `Illuminate\Http\Request` instance populated with target query string keys.
- **Verification**: `php -d memory_limit=512M ./vendor/bin/phpunit tests/Unit/Support/ListingQueryStringTest.php` passes 100% green.

### DEF-002: Pre-existing Mockery Failure in `LorryReceiptTest`
- **Location**: `tests/Unit/LorryReceiptTest.php`
- **Root Cause**: Mocked `LorryReceipt` model failed when internal scope `whereDoesntHave('freightBooking')` chained into an unexpected relation builder call.
- **Fix Applied**: Adjusted test mock setup to properly stub chained Eloquent query builder scopes.
- **Verification**: `php -d memory_limit=512M ./vendor/bin/phpunit tests/Unit/LorryReceiptTest.php` passes 100% green.

### DEF-003: SQLite Engine Compatibility with `DATE_FORMAT`
- **Location**: `app/Http/Controllers/DashboardController.php` (lines 117, 674)
- **Root Cause**: Controller executes raw SQL `DATE_FORMAT(created_at, "%b %Y")`. SQLite does not implement `DATE_FORMAT` natively, resulting in `PDOException: SQLSTATE[HY000]: General error: 1 no such function: DATE_FORMAT`.
- **Resolution Strategy**: Per the Safety Rules (preserve production code intact without unauthorized refactoring), registered a user-defined function on the PDO SQLite driver in `DashboardRoleViewsTest::setUp()`:
  ```php
  if (DB::connection()->getDriverName() === 'sqlite') {
      DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
          return $date ? date('M Y', strtotime($date)) : null;
      });
  }
  ```
- **Verification**: `tests/Feature/Dashboard/DashboardRoleViewsTest.php` passes with all 7 tests and 30 assertions green.

### DEF-004: Model Schema Constraint Violations in Test Fixtures
- **Location**: `tests/Feature/Portal/CustomerAndTransporterPortalTest.php`
- **Root Cause**: Direct `create()` calls missed table-level NOT NULL database constraints:
  1. `good_types`: column is `name` (not `good_type_name`).
  2. `lorry_receipts`: requires `source_city`, `source_state`, `destination_city`, `destination_state`, `lane`, `good_type`, `fr_rate`, `no_of_units`.
  3. `invoice_payments`: column is `mode` (not `payment_mode`).
  4. `voucher_payments`: column `payment_type` is non-nullable.
- **Fix Applied**: Implemented centralized `createDummyLr()` fixture builder with complete, valid schema defaults.
- **Verification**: `tests/Feature/Portal/CustomerAndTransporterPortalTest.php` passes with all 6 tests and 22 assertions green.
