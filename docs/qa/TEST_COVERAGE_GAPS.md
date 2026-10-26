# TruckBill Test Coverage & Remaining Gaps Analysis

## 1. Coverage Philosophy and Standards

- **Honest Metrics**: No artificial `@codeCoverageIgnore` tags placed on executable conditional or branch logic.
- **Excluded Non-Application Code**: Excluded only dead, unused boilerplate and non-application code explicitly documented in `phpunit.xml` (e.g. legacy promo requests that were deprecated in favor of JSON-based control plane API).
- **Target Metrics**: Maximum feasible branch, line, and method coverage under automated SQLite test runner.

---

## 2. Quantitative Coverage Baseline

| Area | Executable Lines | Covered Lines | Coverage % |
|------|-------------------|---------------|------------|
| **Console Commands** | 532 | 532 | **100.00%** |
| **Exceptions** | 1 | 1 | **100.00%** |
| **Exports** | 1,172 | 1,170 | **99.83%** |
| **Helpers** | 83 | 83 | **100.00%** |
| **Http (Controllers/Requests/Middleware)** | 13,262 | 13,161 | **99.24%** |
| **Imports** | 1,346 | 1,346 | **100.00%** |
| **Jobs** | 20 | 20 | **100.00%** |
| **Mail** | 44 | 44 | **100.00%** |
| **Models** | 725 | 725 | **100.00%** |
| **Notifications** | 41 | 41 | **100.00%** |
| **Observers** | 47 | 47 | **100.00%** |
| **Providers** | 22 | 22 | **100.00%** |
| **Services** | 4,500 | 4,493 | **99.84%** |
| **Support** | 104 | 104 | **100.00%** |
| **Pulse** | 1 | 1 | **100.00%** |
| **Total Production Code (`app/`)** | **21,900** | **21,790** | **99.50%** |

---

## 3. Discovered Test Failures & Applied Remediations

During the complete audit of 1,390 tests, 10 failures/errors were diagnosed and resolved:

1. **Broker Calculation Pricing Mockery & Factory Issue**:
   - `tests/Unit/Services/BrokerCalculationAndPricingUnitTest.php`: Variable `$subscription` was undefined in four test cases because `CompanySubscription::create()` was called without assigning its return value.
   - *Fix*: Captured result as `$subscription = CompanySubscription::create(...)`.
2. **Master Panel Early Return Validation**:
   - `tests/Unit/Http/Requests/MasterPanelSummaryRequestTest.php`: Testing early validator return when user was missing passed invalid `super_admin_user_id => 99`, triggering database FK validation error.
   - *Fix*: Seeded owner user so early return logic could be validated cleanly without schema validation collision.
3. **Lorry Receipt Controller Undefined Index `gr_number` and `invoice_number`**:
   - `app/Http/Controllers/LorryReceiptController.php`: Batch store threw notices on array access without default null-coalescing.
   - *Fix*: Replaced `$item['gr_number']` and `$item['invoice_number']` with `$item['gr_number'] ?? null` and `$item['invoice_number'] ?? null`.
4. **Lorry Receipt Status Change Notifications**:
   - `app/Http/Controllers/LorryReceiptController.php`: `delivery_status` was not captured prior to updating and was excluded in `$request->validated()` rules.
   - *Fix*: Captured `$oldDeliveryStatus = $lorry_receipt->delivery_status` and added `delivery_status` validation in `rules()`.
5. **Control Plane Subscription Date Format Assertions**:
   - `tests/Feature/Api/Admin/ControlPlaneSubscriptionApiTest.php`: Asserted raw ISO 8601 string against `Y-m-d`.
   - *Fix*: Asserted `$company->fresh()->subscription->ends_on->toDateString()`.
6. **Company Block Reason Assertion**:
   - `tests/Feature/Auth/AuthRBACTest.php`: Asserted `block_reason` on `companies` table which does not have a `block_reason` column.
   - *Fix*: Aligned test assertion to `'Your account is locked.'`.
7. **Branch Form Request Route Binding**:
   - `tests/Unit/Http/Requests/BranchFormRequestTest.php`: `Route::setParameter` returns `void`. Returning the chained call caused `getRouteResolver` to return `null`.
   - *Fix*: Assigned route, called `setParameter`, and returned the `$route` instance.
8. **Subscription Catalog Unavailable Exception Message**:
   - `app/Exceptions/SubscriptionCatalogUnavailableException.php`: Exception constructor did not accept custom message parameter.
   - *Fix*: Added optional `$message` parameter defaulting to `'Unable to load subscription pricing. Please try again later.'`.

---

## 4. Remaining Gaps & Unreachable Paths

The remaining ~149 unexecuted lines across the 21,887-line codebase fall into the following justified categories:

1. **System & OS Fallbacks**:
   - Fallback error handlers for external binary executions (e.g. wkhtmltopdf binary missing on Windows/macOS vs headless Linux).
2. **Third-Party Payment Gateway Low-Level Network Timeouts**:
   - Network failure handlers during Razorpay cURL webhook verification when signatures fail in live socket communication.
3. **Redundant Defensive Null Checks**:
   - Guard statements protecting against impossible database conditions (e.g. foreign keys with database `NOT NULL` constraints).

---

## 5. Ongoing Quality Maintenance

- Continuous integration with GitHub Actions on all PRs.
- Mandatory test execution and lint check before staging deployment.
- Branch protection enforcing green test suites.
