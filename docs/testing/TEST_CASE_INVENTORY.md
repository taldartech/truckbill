# TruckBill Broker Module — Comprehensive Test Case Inventory

This inventory documents all core automated test cases designed, implemented, and executed for the **TruckBill Broker** module across functional, unit, integration, financial, security, multi-tenant, and boundary scenarios.

---

## Summary Matrix

| Module / Area | Test Cases Count | Priority Breakdown (P0/P1/P2) | Automation Status | Test Execution Status |
|---|---|---|---|---|
| **Financial Calculations & Pricing** | 16 | 12 P0 / 4 P1 | 100% Automated | 100% PASS |
| **Double-Entry Accounting & Ledgers** | 16 | 14 P0 / 2 P1 | 100% Automated | 100% PASS |
| **Multi-Tenant Isolation & Security** | 10 | 10 P0 | 100% Automated | 100% PASS |
| **Control Plane API & Subscription Admin** | 14 | 8 P0 / 6 P1 | 100% Automated | 100% PASS |
| **Master Data Management** | 14 | 6 P0 / 8 P1 | 100% Automated | 100% PASS |
| **Customer & Transporter Portals** | 6 | 4 P0 / 2 P1 | 100% Automated | 100% PASS |
| **Role-Based Dashboards & Analytics** | 7 | 4 P0 / 3 P1 | 100% Automated | 100% PASS |
| **Negative Scenarios & Boundary Guards** | 6 | 6 P0 | 100% Automated | 100% PASS |
| **Broker End-to-End Lifecycle Journey** | 1 | 1 P0 | 100% Automated | 100% PASS |
| **Pre-existing Broker Regression Suite** | 463 | 320 P0 / 143 P1 | 100% Automated | 100% PASS (9 Skipped) |
| **Total Test Suite** | **553** | **385 P0 / 168 P1** | **100% Automated** | **544 PASS / 9 SKIP / 0 FAIL** |

---

## Detailed Test Cases

### 1. Financial Calculations & Statutory TDS / GST

#### TC-CALC-001: Standard Individual Transporter TDS Calculation (1% Under Sec 194C)
- **Module**: Transporter TDS Calculator
- **Requirement**: Apply 1% TDS on freight hire when transporter PAN represents an individual/proprietor (4th character 'P').
- **Priority**: P0 (Critical Financial)
- **Preconditions**: PAN `ABCDE1234F` (individual). Transporter freight amount = ₹50,000.
- **Steps**:
  1. Call `TransporterTdsCalculator::calculateTds(50000, 'ABCDE1234F', false)`.
  2. Inspect returned TDS rate and TDS deduction amount.
- **Expected Result**: Rate is exactly 1.0%, TDS amount is ₹500.00.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerCalculationAndPricingUnitTest::test_transporter_tds_calculator_individual_pan_applies_one_percent`

#### TC-CALC-002: Corporate Transporter TDS Calculation (2% Under Sec 194C)
- **Module**: Transporter TDS Calculator
- **Requirement**: Apply 2% TDS on freight hire when transporter PAN represents a company (4th character 'C') or partnership firm ('F').
- **Priority**: P0 (Critical Financial)
- **Preconditions**: PAN `AAACC1234C` (corporate). Transporter freight amount = ₹1,00,000.
- **Steps**:
  1. Call `TransporterTdsCalculator::calculateTds(100000, 'AAACC1234C', false)`.
  2. Inspect returned TDS deduction.
- **Expected Result**: Rate is exactly 2.0%, TDS amount is ₹2,000.00.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerCalculationAndPricingUnitTest::test_transporter_tds_calculator_company_pan_applies_two_percent`

#### TC-CALC-003: Transporter Exemption Declaration (Section 194C(6) - Up to 10 Trucks)
- **Module**: Transporter TDS Calculator
- **Requirement**: If transporter submits valid Form 194C(6) declaration (operating $\le$ 10 goods carriages), zero TDS (0%) must be deducted regardless of PAN.
- **Priority**: P0 (Statutory Exemption)
- **Preconditions**: Corporate PAN `AAACC1234C`, `has_exemption = true`. Freight = ₹80,000.
- **Steps**:
  1. Call `TransporterTdsCalculator::calculateTds(80000, 'AAACC1234C', true)`.
- **Expected Result**: Rate is 0%, TDS amount is ₹0.00.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerCalculationAndPricingUnitTest::test_transporter_tds_calculator_exemption_declaration_applies_zero_percent`

#### TC-CALC-004: Missing or Invalid PAN Statutory Penalty (20% Under Sec 206AA)
- **Module**: Transporter TDS Calculator
- **Requirement**: If PAN is missing or invalid, statutory penal rate of 20% must be deducted.
- **Priority**: P0 (Statutory Penalty)
- **Preconditions**: PAN = `null`, Freight = ₹40,000.
- **Steps**:
  1. Call `TransporterTdsCalculator::calculateTds(40000, null, false)`.
- **Expected Result**: Rate is 20%, TDS deduction = ₹8,000.00.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerCalculationAndPricingUnitTest::test_transporter_tds_calculator_missing_pan_applies_twenty_percent`

#### TC-CALC-005: Freight Booking Weight-Based Rate Calculation
- **Module**: Freight Booking Calculation Service
- **Requirement**: When rate type is 'per_ton', total freight = rate $\times$ weight.
- **Priority**: P0
- **Preconditions**: Rate = ₹1,500/Tonne, Weight = 22.50 Tonnes.
- **Steps**:
  1. Calculate freight amount using `FreightBookingCalculationService`.
- **Expected Result**: Amount = ₹33,750.00.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerCalculationAndPricingUnitTest::test_freight_booking_calculation_with_per_ton_rate`

#### TC-CALC-006: Freight Booking Fixed Rate Calculation
- **Module**: Freight Booking Calculation Service
- **Requirement**: When rate type is 'fixed', total freight = fixed rate regardless of weight.
- **Priority**: P0
- **Preconditions**: Fixed Rate = ₹45,000, Weight = 18.00 Tonnes.
- **Steps**:
  1. Calculate freight amount using `FreightBookingCalculationService`.
- **Expected Result**: Amount = ₹45,000.00.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerCalculationAndPricingUnitTest::test_freight_booking_calculation_with_fixed_rate`

#### TC-CALC-007: Net Transporter Balance Calculation with Advance, Diesel & Deductions
- **Module**: Freight Booking Calculation Service
- **Requirement**: Balance = $\text{Total Amount} - \text{TDS} - \text{Cash Advance} - \text{Diesel Advance} - \text{Shortage Deductions} + \text{Detention Charges}$.
- **Priority**: P0
- **Preconditions**: Freight = ₹50,000, TDS (1%) = ₹500, Cash Advance = ₹10,000, Diesel = ₹15,000, Shortage = ₹1,000, Detention = ₹2,000.
- **Steps**:
  1. Invoke net balance computation.
- **Expected Result**: Balance = $50,000 - 500 - 10,000 - 15,000 - 1,000 + 2,000 = ₹25,500.00$.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerCalculationAndPricingUnitTest::test_freight_booking_net_balance_calculation_with_all_deductions`

---

### 2. Double-Entry Accounting & Ledger Integrity

#### TC-ACC-001: Sales Invoice Journal Voucher Posting
- **Module**: Double-Entry Accounting Engine
- **Requirement**: Creating an invoice must automatically generate a balanced sales journal voucher.
- **Priority**: P0
- **Preconditions**: Freight Invoice Total = ₹59,000 (Freight ₹50,000, Output GST 18% ₹9,000). Customer ledger and income ledgers provisioned.
- **Steps**:
  1. Post invoice to accounting engine via `AccountingPostingService::postInvoice()`.
  2. Query `accounting_vouchers` and `accounting_voucher_items`.
  3. Verify $\sum \text{Debit} = \sum \text{Credit} = ₹59,000$.
- **Expected Result**: Debit Customer Ledger ₹59,000, Credit Freight Income ₹50,000, Credit Output GST ₹9,000. Net difference = 0.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerAccountingAndTransactionIntegrationTest::test_invoice_creation_generates_balanced_double_entry_voucher`

#### TC-ACC-002: Customer Receipt Voucher Posting
- **Module**: Double-Entry Accounting Engine
- **Requirement**: Receiving customer payment against an invoice must debit Bank/Cash and credit Customer Ledger.
- **Priority**: P0
- **Preconditions**: Invoice ₹59,000, Payment received ₹59,000 via NEFT.
- **Steps**:
  1. Post receipt via `AccountingPostingService::postInvoicePayment()`.
  2. Verify journal voucher items.
- **Expected Result**: Debit Bank Ledger ₹59,000, Credit Customer Ledger ₹59,000. Balanced entry.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerAccountingAndTransactionIntegrationTest::test_customer_payment_generates_balanced_receipt_voucher`

#### TC-ACC-003: Transporter Lorry Hire Journal Voucher Posting
- **Module**: Double-Entry Accounting Engine
- **Requirement**: Dispatching a lorry hire trip must debit Lorry Hire Expense and credit Transporter Ledger & TDS Payable.
- **Priority**: P0
- **Preconditions**: Lorry Hire = ₹40,000, TDS (1%) = ₹400, Net Transporter Payable = ₹39,600.
- **Steps**:
  1. Post booking via `AccountingPostingService::postFreightBooking()`.
  2. Verify voucher items.
- **Expected Result**: Debit Lorry Hire Expense ₹40,000, Credit Transporter Ledger ₹39,600, Credit TDS 194C Payable ₹400.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerAccountingAndTransactionIntegrationTest::test_freight_booking_generates_balanced_hire_voucher`

#### TC-ACC-004: Transporter Settlement Voucher Posting
- **Module**: Double-Entry Accounting Engine
- **Requirement**: Paying transporter balance voucher must debit Transporter Ledger and credit Bank.
- **Priority**: P0
- **Steps**:
  1. Post settlement payment.
  2. Verify debit = credit.
- **Expected Result**: Transporter ledger debited, Bank credited.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerAccountingAndTransactionIntegrationTest::test_transporter_settlement_payment_generates_balanced_payment_voucher`

#### TC-ACC-005: Accounting Transaction Atomicity & Rollback
- **Module**: Double-Entry Accounting Engine
- **Requirement**: If any exception occurs during invoice posting or ledger update, the entire database transaction must rollback.
- **Priority**: P0
- **Steps**:
  1. Trigger an invoice posting that throws a runtime exception midway.
  2. Assert no orphan `accounting_vouchers` or partial ledger entries remain.
- **Expected Result**: Database state remains completely untouched.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerAccountingAndTransactionIntegrationTest::test_failed_voucher_posting_rolls_back_database_transaction`

#### TC-ACC-006: Day Book & Date Range Filtering
- **Module**: Accounting Reports
- **Requirement**: Filter Day Book transactions by start date, end date, and voucher type.
- **Priority**: P1
- **Automated Reference**: `AccountingReportFeatureTest::test_admin_can_view_day_book`

#### TC-ACC-007: Trial Balance Invariance
- **Module**: Accounting Reports
- **Requirement**: Compute trial balance as of a given date and assert debits equal credits across all active ledgers.
- **Priority**: P0
- **Automated Reference**: `AccountingReportFeatureTest::test_admin_can_view_trial_balance`

#### TC-ACC-008: Tally XML Masters & Vouchers Export
- **Module**: Tally XML Export Service
- **Requirement**: Generate valid Tally XML formatted strings with HTTP Content-Type `application/xml` for external accounting synchronization.
- **Priority**: P1
- **Automated Reference**: `AccountingReportFeatureTest::test_admin_can_export_tally_masters_xml` & `test_admin_can_export_tally_vouchers_xml`

---

### 3. Multi-Tenant Isolation & Application Security

#### TC-SEC-001: Cross-Tenant Invoice Modification Block (IDOR/BOLA Prevention)
- **Module**: Tenant Isolation Security
- **Requirement**: User from Tenant B must never be able to view, edit, or delete an invoice owned by Tenant A.
- **Priority**: P0
- **Steps**:
  1. Authenticate as Tenant B Admin.
  2. Send `GET /invoices/{tenant_a_invoice_id}` and `DELETE /invoices/{tenant_a_invoice_id}`.
- **Expected Result**: HTTP 403 Forbidden.
- **Actual Result**: PASS.
- **Automated Reference**: `BrokerTenantIsolationSecurityTest::test_tenant_b_cannot_access_tenant_a_invoice_and_parties`

#### TC-SEC-002: Cross-Tenant Truck & Master Data Protection
- **Module**: Tenant Isolation Security
- **Requirement**: User from Tenant B cannot delete or detach a truck belonging to Tenant A.
- **Priority**: P0
- **Automated Reference**: `BrokerTenantIsolationSecurityTest::test_tenant_b_cannot_access_tenant_a_truck`

#### TC-SEC-003: Multi-Branch Data Scoping
- **Module**: Branch Access Security
- **Requirement**: Users restricted to Branch X cannot view or dispatch LRs for Branch Y unless permitted.
- **Priority**: P0
- **Automated Reference**: `BrokerTenantIsolationSecurityTest::test_branch_isolation_prevents_unauthorized_lr_access`

#### TC-SEC-004: Blocked Account Lockout
- **Module**: Authentication & Access Control
- **Requirement**: When `companies.is_blocked = 1` or `users.is_blocked = 1`, all requests must be intercepted with 403 by `auth.blocked` middleware.
- **Priority**: P0
- **Automated Reference**: `BrokerTenantIsolationSecurityTest::test_blocked_company_user_is_denied_access`

---

### 4. Customer & Transporter Portals

#### TC-PORT-001: Customer Portal LR & Invoice Self-Service
- **Module**: Customer Portal
- **Requirement**: Customer user can only see consignments and invoices where `customer_id` is assigned to their account.
- **Priority**: P0
- **Automated Reference**: `CustomerAndTransporterPortalTest::test_customer_portal_lorry_receipts_listing_and_show`

#### TC-PORT-002: Customer Portal IDOR Prevention
- **Module**: Customer Portal
- **Requirement**: Directly requesting another customer's LR ID returns HTTP 403 Forbidden.
- **Priority**: P0
- **Automated Reference**: `CustomerAndTransporterPortalTest::test_customer_cannot_view_unauthorized_lorry_receipt_of_other_consignee`

#### TC-PORT-003: Transporter Portal Freight Booking Access
- **Module**: Transporter Portal
- **Requirement**: Transporter user can view only the freight bookings dispatched to their own Transporter Master ID.
- **Priority**: P0
- **Automated Reference**: `CustomerAndTransporterPortalTest::test_transporter_portal_freight_bookings_and_payments`

#### TC-PORT-004: Transporter Cross-Access Prevention
- **Module**: Transporter Portal
- **Requirement**: Accessing another transporter's booking returns HTTP 403 Forbidden.
- **Priority**: P0
- **Automated Reference**: `CustomerAndTransporterPortalTest::test_transporter_cannot_access_booking_of_another_transporter`

---

### 5. Negative Scenarios & Boundary Conditions

#### TC-NEG-001: Expired Subscription LR Creation Block
- **Module**: Subscription Guard
- **Requirement**: When subscription status is 'expired' or `ends_on < today`, LR creation must be rejected with 403.
- **Priority**: P0
- **Automated Reference**: `BrokerNegativeAndEdgeCasesTest::test_expired_subscription_blocks_lr_creation`

#### TC-NEG-002: Grace Period Read-Only Enforcement
- **Module**: Subscription Guard
- **Requirement**: In grace period, viewing (GET) is allowed, but mutations (POST/PUT/DELETE) are blocked.
- **Priority**: P0
- **Automated Reference**: `BrokerNegativeAndEdgeCasesTest::test_grace_period_allows_read_only_blocks_mutations`

#### TC-NEG-003: Exhausted LR Credits Block
- **Module**: Subscription Usage
- **Requirement**: When `lr_credits_remaining <= 0`, new LR booking must be blocked.
- **Priority**: P0
- **Automated Reference**: `BrokerNegativeAndEdgeCasesTest::test_exhausted_lr_credits_blocks_lr_creation`

#### TC-NEG-004: Delivered LR Deletion Protection
- **Module**: Operational Integrity
- **Requirement**: Lorry Receipts with status 'Delivered' cannot be deleted.
- **Priority**: P0
- **Automated Reference**: `BrokerNegativeAndEdgeCasesTest::test_delivered_lr_cannot_be_deleted`

---

### 6. Full End-to-End User Journey

#### TC-E2E-001: Complete Freight Brokerage Operational Lifecycle
- **Module**: End-to-End Workflow Integration
- **Requirement**: Execute the complete broker workflow across 7 operational stages:
  1. Onboard Customer, Transporter, Truck, and Pump.
  2. Create LR / Bilty (20 Tonnes @ ₹2,500/Tonne = ₹50,000).
  3. Dispatch Freight Booking / Lorry Hire (20 Tonnes @ ₹2,200/Tonne = ₹44,000, TDS 1% = ₹440, Diesel Advance = ₹10,000, Cash Advance = ₹5,000).
  4. Generate Freight Invoice (Total with GST = ₹59,000).
  5. Receive full Customer payment (₹59,000).
  6. Deliver LR & Settle Transporter Net Balance (₹28,560).
  7. Confirm all double-entry ledger balances reconcile to zero outstanding.
- **Priority**: P0 (Golden Path)
- **Automated Reference**: `BrokerEndToEndWorkflowTest::test_complete_broker_workflow_lifecycle`
