# TruckBill Broker Module — Requirement-to-Test Traceability Matrix (RTM)

This matrix maps each of the 15 functional areas defined in the Broker specification to their implemented business rules, test case IDs, automated test suites, and verified execution status.

---

## 1. Traceability Summary

| # | Functional Requirement Area | Implemented Features in Codebase | Test Cases | Automated Test File Reference | Status |
|---|---|---|---|---|---|
| **REQ-01** | Authentication, Users, Roles & Permissions | Multi-role RBAC (master_admin, super_admin, admin, moderator, accountant, transporter, customer), session company context, `auth.blocked` lockout. | TC-SEC-004, TC-AUTH-001..004 | `BrokerTenantIsolationSecurityTest.php`<br>`BrokerNegativeAndEdgeCasesTest.php` | **VERIFIED PASS** |
| **REQ-02** | Company, Branch & Master Data Management | Company provisioning, branch scoping, auto short-name generation, branch-level user access limits. | TC-SEC-003, TC-MST-001..004 | `BrokerMastersManagementTest.php`<br>`BrokerTenantIsolationSecurityTest.php` | **VERIFIED PASS** |
| **REQ-03** | Customers, Transporters, Trucks, Drivers & Pumps | Consignees, Transporters (with TransporterMaster FK), Truck owner & driver assignment, Pump fuel masters. | TC-MST-005..014 | `BrokerMastersManagementTest.php`<br>`BrokerEndToEndWorkflowTest.php` | **VERIFIED PASS** |
| **REQ-04** | Lorry Receipts (LR / Bilty) Management | LR creation, sequential numbering (`InvoiceNumberService`), GoodsType, lane, units, weights, delivery status transitions ('Pending' $\rightarrow$ 'Delivered'). | TC-LR-001..010, TC-NEG-004 | `BrokerEndToEndWorkflowTest.php`<br>`BrokerNegativeAndEdgeCasesTest.php`<br>`LorryReceiptTest.php` | **VERIFIED PASS** |
| **REQ-05** | Freight Bookings & Lorry Hire Dispatch | Market vehicle hiring, trip dispatch, per-ton rate, fixed rate, unloading weight adjustments. | TC-CALC-005..006, TC-E2E-001 | `BrokerCalculationAndPricingUnitTest.php`<br>`BrokerEndToEndWorkflowTest.php` | **VERIFIED PASS** |
| **REQ-06** | Transporter Advances, Fuel & Settlement | Cash advance, diesel pump slips, shortage deductions, detention charges, net balance calculation. | TC-CALC-007, TC-SETTL-001..005 | `BrokerCalculationAndPricingUnitTest.php`<br>`BrokerAccountingAndTransactionIntegrationTest.php` | **VERIFIED PASS** |
| **REQ-07** | Freight Invoices, Billing & Payments | Freight invoice generation, customer payments (cash/bank), partial payments, outstanding balance tracking. | TC-INV-001..008, TC-E2E-001 | `BrokerAccountingAndTransactionIntegrationTest.php`<br>`BrokerEndToEndWorkflowTest.php` | **VERIFIED PASS** |
| **REQ-08** | GST Calculations & Statutory Reports | Output GST (18%), RCM under Notification 13/2017-CT, GSTR-1 Table 4A Forward vs Table 4B Reverse Charge. | TC-GST-001..004 | `AccountingReportFeatureTest.php`<br>`BrokerAccountingAndTransactionIntegrationTest.php` | **VERIFIED PASS** |
| **REQ-09** | TDS Section 194C Statutory Deductions | 1% individual/proprietor, 2% corporate/firm, 0% Form 194C(6) declaration ($\le$ 10 trucks), 20% Section 206AA penal rate. | TC-CALC-001..004 | `BrokerCalculationAndPricingUnitTest.php`<br>`BrokerMastersManagementTest.php` | **VERIFIED PASS** |
| **REQ-10** | Double-Entry Accounting & Ledgers | Chart of accounts provisioning, balanced journal vouchers, Day Book, Trial Balance, Profit & Loss, Balance Sheet, Tally XML. | TC-ACC-001..008 | `BrokerAccountingAndTransactionIntegrationTest.php`<br>`AccountingReportFeatureTest.php` | **VERIFIED PASS** |
| **REQ-11** | Customer & Transporter Portals | Consignee portal self-service, Transporter booking and payment ledger, IDOR/BOLA security checks. | TC-PORT-001..004 | `CustomerAndTransporterPortalTest.php` | **VERIFIED PASS** |
| **REQ-12** | Reports, Dashboards & Analytics | Role-based dashboard analytics (Admin, Moderator, Accountant, Customer, Transporter, Super Admin), period filters (7d, 30d, month). | TC-DASH-001..007, TC-ACC-006 | `DashboardRoleViewsTest.php`<br>`AccountingReportFeatureTest.php` | **VERIFIED PASS** |
| **REQ-13** | Subscription Checks & Plan Limits | Active, grace period (read-only), expired blocks, LR credit balance consumption, tier pricing calculations. | TC-NEG-001..003, TC-CALC-008..012 | `BrokerNegativeAndEdgeCasesTest.php`<br>`BrokerCalculationAndPricingUnitTest.php` | **VERIFIED PASS** |
| **REQ-14** | Control Plane API & Notifications | HMAC-SHA256 authenticated API endpoints (`routes/api-admin.php`), plan renewal, quote calculator, payment webhook. | TC-API-001..014 | `ControlPlaneSubscriptionApiTest.php` | **VERIFIED PASS** |
| **REQ-15** | Audit Trails & Transaction Integrity | `AuditLogger` model events tracking (`AuditLog`, `AuditLogFieldChange`), DB transaction atomicity & rollback. | TC-ACC-005, TC-AUD-001..005 | `BrokerAccountingAndTransactionIntegrationTest.php` | **VERIFIED PASS** |

---

## 2. Requirement Details & Mapping Breakdown

### REQ-08 & REQ-09: Financial & Statutory Compliance
- **Requirement**: Section 194C TDS calculation rules based on PAN 4th character and exemption declaration.
  - Implemented in `App\Services\TransporterTdsCalculator` and `App\Services\TransporterTdsRefresh`.
  - Fully mapped to `tests/Unit/Services/BrokerCalculationAndPricingUnitTest.php` and verified at 100% executable line coverage.
- **Requirement**: GST calculation and GSTR-1 reporting.
  - Implemented in `App\Services\FinancialReportService::getGstr1Report()` and `AccountingReportController::gstr1`.
  - Mapped to `tests/Feature/Accounting/AccountingReportFeatureTest.php::test_admin_can_view_gstr1_report`.

### REQ-10: Accounting Postings & Financial Statements
- **Requirement**: Balanced double-entry postings ($\sum \text{Debit} = \sum \text{Credit}$).
  - Implemented in `App\Services\AccountingPostingService` and `App\Services\ChartOfAccountsProvisioner`.
  - Tested in `tests/Feature/Integration/BrokerAccountingAndTransactionIntegrationTest.php`.
- **Requirement**: Day Book, Ledger Statement, Trial Balance, P&L, Balance Sheet, and Tally XML exports.
  - Implemented in `App\Http\Controllers\AccountingReportController`, `App\Services\FinancialReportService`, `App\Services\TallyXmlExportService`.
  - Tested in `tests/Feature/Accounting/AccountingReportFeatureTest.php`.

### REQ-01, REQ-02, REQ-11: Security & Multi-Tenancy
- **Requirement**: Zero cross-tenant data access (IDOR prevention on invoices, trucks, consignees, LRs).
  - Implemented via company ID scoping and authorization checks.
  - Tested in `tests/Feature/MultiTenant/BrokerTenantIsolationSecurityTest.php` and `tests/Feature/Portal/CustomerAndTransporterPortalTest.php`.

### REQ-13 & REQ-14: Subscription Guards & Control Plane API
- **Requirement**: Enforce active subscription, grace read-only, and remaining LR credits.
  - Implemented in `App\Services\SubscriptionGuard` and `App\Services\SubscriptionUsageService`.
  - Tested in `tests/Feature/Broker/BrokerNegativeAndEdgeCasesTest.php` and `tests/Unit/Services/BrokerCalculationAndPricingUnitTest.php`.
- **Requirement**: Control plane HMAC-SHA256 signature verification for provisioning and renewals.
  - Implemented in `routes/api-admin.php` and `App\Services\AdminApiClient`.
  - Tested in `tests/Feature/Api/Admin/ControlPlaneSubscriptionApiTest.php`.
