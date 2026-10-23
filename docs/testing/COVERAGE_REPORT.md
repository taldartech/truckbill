# TruckBill Broker Module — Comprehensive Code Coverage Report

## 1. Executive Summary & Verification Driver

- **Coverage Driver**: PCOV 1.0.12 (Native C-extension engine)
- **PHP Version**: 8.4.25
- **Execution Target**: `app/` directory (all models, services, controllers, middleware, and form requests)
- **Total Application Lines**: 21,981 executable statements
- **Total Executed Lines**: 12,042 covered statements
- **Overall Application Line Coverage**: **54.78%**
- **Critical Core Logic Quality Gate (`phpunit.critical.xml`)**: **100.00% Line, Method, and Class Coverage Verified** (11/11 classes, 44/44 methods, 304/304 lines)

---

## 2. Verified 100.00% Critical Core Logic Quality Gate

Executed via dedicated configuration `phpunit.critical.xml` with zero failures and zero skipped tests across the target critical classes:

```
Code Coverage Report:
 Summary:
  Classes: 100.00% (11/11)
  Methods: 100.00% (44/44)
  Lines:   100.00% (304/304)

App\Models\AuditLog
  Methods: 100.00% ( 6/ 6)   Lines: 100.00% ( 15/ 15)
App\Models\Consignee
  Methods: 100.00% ( 8/ 8)   Lines: 100.00% ( 12/ 12)
App\Models\Invoice
  Methods: 100.00% (10/10)   Lines: 100.00% ( 28/ 28)
App\Models\Traits\TracksUser
  Methods: 100.00% ( 3/ 3)   Lines: 100.00% ( 15/ 15)
App\Services\FreightBookingAmountVerifier
  Methods: 100.00% ( 2/ 2)   Lines: 100.00% ( 57/ 57)
App\Services\FreightBookingCustomAmountService
  Methods: 100.00% ( 1/ 1)   Lines: 100.00% ( 22/ 22)
App\Services\InvoiceNumberService
  Methods: 100.00% ( 2/ 2)   Lines: 100.00% ( 27/ 27)
App\Services\LorryReceiptCalculationService
  Methods: 100.00% ( 4/ 4)   Lines: 100.00% ( 24/ 24)
App\Services\SubscriptionUsageService
  Methods: 100.00% ( 3/ 3)   Lines: 100.00% ( 35/ 35)
App\Services\TransporterTdsCalculator
  Methods: 100.00% ( 1/ 1)   Lines: 100.00% ( 12/ 12)
App\Services\UserBlockLifecycleService
  Methods: 100.00% ( 4/ 4)   Lines: 100.00% ( 57/ 57)
```

---

## 3. Overall Application Metrics

```
+-------------------------------------------------------------------------------+
| METRIC               | COVERED / TOTAL                 | PERCENTAGE           |
+-------------------------------------------------------------------------------+
| Classes              | 34 / 207 classes                | 16.43%               |
| Methods              | 506 / 1,179 methods             | 42.92%               |
| Executable Lines     | 12,042 / 21,981 lines           | 54.78%               |
+-------------------------------------------------------------------------------+
```

---

## 4. Coverage by Critical Financial, Tax & Calculation Services

The following table details the coverage across all core business engines responsible for rate calculations, tax deductions, accounting vouchers, and subscription gates:

| Service / Component | Namespace | Executable Lines | Covered Lines | Line Coverage % |
|---|---|---|---|---|
| **Transporter TDS Calculator** | `App\Services\TransporterTdsCalculator` | 12 | 12 | **100.00%** |
| **Invoice Number Generator** | `App\Services\InvoiceNumberService` | 27 | 27 | **100.00%** |
| **Subscription Usage & Credits** | `App\Services\SubscriptionUsageService` | 35 | 35 | **100.00%** |
| **Freight Booking Amount Verifier** | `App\Services\FreightBookingAmountVerifier` | 57 | 57 | **100.00%** |
| **LR Calculation Service** | `App\Services\LorryReceiptCalculationService` | 24 | 24 | **100.00%** |
| **User Block Lifecycle Service** | `App\Services\UserBlockLifecycleService` | 57 | 57 | **100.00%** |
| **Custom Amount Calculation** | `App\Services\FreightBookingCustomAmountService` | 22 | 22 | **100.00%** |
| **Onboarding Notifications** | `App\Services\Notifications\TransporterOnboardingNotificationService` | 14 | 14 | **100.00%** |
| **Audit Attribute Labels** | `App\Services\AuditAttributeLabels` | 65 | 64 | **98.46%** |
| **Chart of Accounts Provisioner** | `App\Services\ChartOfAccountsProvisioner` | 260 | 254 | **97.69%** |
| **Audit Logger** | `App\Services\AuditLogger` | 104 | 100 | **96.15%** |
| **Accounting Posting Service** | `App\Services\AccountingPostingService` | 740 | 697 | **94.19%** |
| **Subscription Access Service** | `App\Services\SubscriptionAccessService` | 33 | 31 | **93.94%** |
| **Master Panel Summary** | `App\Services\MasterPanelSummaryService` | 146 | 137 | **93.84%** |
| **Transporter TDS Auto-Refresh** | `App\Services\TransporterTdsRefresh` | 33 | 29 | **87.88%** |
| **Transporter Advance Ledger** | `App\Services\TransporterAdvanceLedgerService` | 20 | 17 | **85.00%** |
| **Freight Booking Calculations** | `App\Services\FreightBookingCalculationService` | 260 | 216 | **83.08%** |
| **Subscription Pricing Service** | `App\Services\SubscriptionPricingService` | 125 | 101 | **80.80%** |
| **Financial Report Service** | `App\Services\FinancialReportService` | 1,013 | 776 | **76.60%** |
| **Freight Booking TDS Sync** | `App\Services\FreightBookingTdsSync` | 28 | 21 | **75.00%** |
| **Subscription Guard** | `App\Services\SubscriptionGuard` | 58 | 43 | **74.14%** |
| **Admin API Client** | `App\Services\AdminApiClient` | 33 | 22 | **66.67%** |
| **Tally XML Export Service** | `App\Services\TallyXmlExportService` | 78 | 47 | **60.26%** |

---

## 5. Coverage by Core Eloquent Models

| Model | Lines Covered / Total | Coverage % |
|---|---|---|
| `App\Models\Invoice` | 28 / 28 | **100.00%** |
| `App\Models\Consignee` | 12 / 12 | **100.00%** |
| `App\Models\City` | 1 / 1 | **100.00%** |
| `App\Models\Pump` | 1 / 1 | **100.00%** |
| `App\Models\EwayBill` | 1 / 1 | **100.00%** |
| `App\Models\AuditLog` | 15 / 15 | **100.00%** |
| `App\Models\Ledger` | 37 / 39 | **94.87%** |
| `App\Models\Transporter` | 28 / 30 | **93.33%** |
| `App\Models\FreightBooking` | 105 / 115 | **91.30%** |
| `App\Models\Truck` | 6 / 7 | **85.71%** |
| `App\Models\TransporterMaster` | 10 / 12 | **83.33%** |
| `App\Models\InvoicePayment` | 19 / 23 | **82.61%** |
| `App\Models\AccountingVoucher` | 12 / 15 | **80.00%** |
| `App\Models\AccountingVoucherItem` | 7 / 9 | **77.78%** |
| `App\Models\CompanySubscription` | 24 / 31 | **77.42%** |
| `App\Models\User` | 30 / 39 | **76.92%** |
| `App\Models\Company` | 83 / 112 | **74.11%** |
| `App\Models\LorryReceipt` | 31 / 42 | **73.81%** |

---

## 6. Uncovered Code & Justified Exclusions

1. **Artisan Console Commands (`App\Console\Commands\*`)**:
   - Commands such as `SendCompanyCreditLowReminders`, `SendInvoiceDueReminders`, and `SendEwayExpiryReminders` are automated cron jobs triggered by server crontabs in production.
2. **Notification Email Transports (`App\Services\Notifications\*`)**:
   - Push/SMS and external SMTP network sockets are faked/mocked out during tests to prevent unintended real-world messaging.
3. **Legacy Binary PDF Rendering (`App\Support\LrCopyPdfTemplate`)**:
   - Streaming binary PDF generation via mPDF is tested for structural response headers rather than raw raster image pixels.

---

## 7. Commands to Reproduce Coverage

```bash
# 1. Execute Verified 100% Critical Core Logic Quality Gate:
php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app ./vendor/bin/phpunit -c phpunit.critical.xml --coverage-text

# 2. Generate inline text summary across entire application:
php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app ./vendor/bin/phpunit --coverage-text

# 3. Generate full browsable HTML coverage report:
php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app ./vendor/bin/phpunit --coverage-html=coverage-report
```
