# Security Risks Audit

Date: 2026-04-15

## 1) High - Cross-company data exposure in ID-based API endpoints

- **Routes**
  - `GET /api/transporters/{id}`
  - `GET /api/trucks/{id}`
- **Controller**
  - `app/Http/Controllers/FreightBookingController.php`
- **Issue**
  - ID-based fetches could return records outside the active company context.
- **Status**
  - Mitigated and verified.
- **Implementation note**
  - `getTransporter()` now resolves active company with `getActiveCompanyId()` and enforces `where('company_id', $companyId)` for `GET /api/transporters/{id}`.
  - `getTruck()` now resolves active company with `getActiveCompanyId()` and enforces company ownership via truck `company_id` or related transporter `company_id` for `GET /api/trucks/{id}`.

## 2) Medium - Sensitive request payload logging

- **Controllers**
  - `app/Http/Controllers/FreightBookingController.php`
  - `app/Http/Controllers/VoucherPaymentController.php`
- **Issue**
  - Multiple paths log `$request->all()` (normal and exception paths), which can expose sensitive/financial data.
- **Status**
  - Mitigated and verified.
- **Implementation note**
  - Replaced `$request->all()` logging in `FreightBookingController` voucher/update/combined-payment paths with strict allowlist metadata (`request_context`) using dedicated helper methods.
  - Replaced `$request->all()` logging in `VoucherPaymentController::bulkPayment()` request/exception paths with strict allowlist metadata (`request_context`).
  - Removed `info`/`warning` success logs so request metadata is only logged on error paths.

## 3) Medium - Company context mismatch risk for super admin

- **Issue**
  - Several forms submit hidden `company_id` from `Auth::user()->company_id`; super-admin workflows are usually session-company driven.
- **Risk**
  - Wrong/null company context in submission and cross-tenant inconsistencies.
- **Status**
  - Mitigated and partially verified.
- **Implementation note**
  - Added route-level `company.context` middleware (`app/Http/Middleware/EnsureCompanyContextMiddleware.php`) to block `super_admin` until `session('super_admin_company_id')` is set.
  - Standardized the “active company” for super-admin by enforcing `auth()->user()->company_id` (now backed by `User::getCompanyIdAttribute`) for tenant write flows.
  - Added request-level guards that reject mismatched submitted `company_id` for:
    - `FreightBookingController@confirm` and `FreightBookingController@store`
    - `VoucherPaymentController@update` and `VoucherPaymentController@bulkPayment`

## 4) Low - `env()` usage in controllers

- **Controllers**
  - `app/Http/Controllers/FreightBookingController.php`
  - `app/Http/Controllers/LorryReceiptController.php`
  - `app/Http/Controllers/PaymentController.php`
- **Issue**
  - Direct `env(...)` in application code can become inconsistent under config cache.
- **Recommended**
  - Move to config files and read through `config(...)`.
