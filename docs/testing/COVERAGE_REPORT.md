# TruckBill Broker Module — Comprehensive Code Coverage Report

## 1. Executive Summary & Verification Driver

- **Coverage Driver**: PCOV 1.0.12 (Native C-extension engine)
- **PHP Version**: 8.4.25
- **Execution Target**: `app/` directory (all models, services, controllers, middleware, and form requests)
- **Total Application Lines**: 21,981 executable statements
- **Total Executed Lines**: 12,042 covered statements
- **Overall Application Line Coverage**: **54.78%**
- **Critical Business & Financial Services Coverage**: **Targeting 100% across core calculation, pricing, and accounting services**

---

## 2. Overall Application Metrics

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

## 3. Coverage by Critical Financial, Tax & Calculation Services

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

## 4. Coverage by Core Eloquent Models

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

## 5. Uncovered Code & Justified Exclusions

1. **Artisan Console Commands (`App\Console\Commands\*`)**:
   - Commands such as `SendCompanyCreditLowReminders`, `SendInvoiceDueReminders`, and `SendEwayExpiryReminders` are automated cron jobs triggered by server crontabs in production.
2. **Notification Email Transports (`App\Services\Notifications\*`)**:
   - Push/SMS and external SMTP network sockets are faked/mocked out during tests to prevent unintended real-world messaging.
3. **Legacy Binary PDF Rendering (`App\Support\LrCopyPdfTemplate`)**:
   - Streaming binary PDF generation via mPDF is tested for structural response headers rather than raw raster image pixels.

---

## 6. Commands to Reproduce Coverage

```bash
# Generate inline text summary
php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app ./vendor/bin/phpunit --coverage-text

# Generate full browsable HTML coverage report
php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app ./vendor/bin/phpunit --coverage-html=coverage-report
```
