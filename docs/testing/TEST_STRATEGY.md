# TruckBill Broker Module — Comprehensive Test Strategy

## 1. Executive Summary & Purpose

TruckBill Broker is the core operational ERP and financial billing system for road freight brokers, commission agents, and logistics intermediaries in India. The application manages the end-to-end transportation lifecycle: from customer consignment booking, market vehicle/fleet sourcing, Lorry Receipt (LR / Bilty) issuance, trip dispatch, diesel and cash advances, through customer freight billing, transporter voucher settlement, GST & TDS statutory compliance, and double-entry accounting ledger postings.

This document defines the quality assurance strategy, architecture, methodology, and verification protocols implemented to guarantee zero financial inaccuracies, rock-solid multi-tenant data isolation, strict role-based access control (RBAC), and 100% executable coverage across critical business logic.

---

## 2. Scope & Target Application

- **Target System**: TruckBill Broker (`/Users/vijay/Development/truckbill`).
- **Out of Scope**: TruckBill Admin (Control Plane), Own Fleet Management, Partload Logistics, or generic monorepo infrastructure.
- **Architectural Boundary**: Laravel 11.x, PHP 8.4, SQLite test runner, MySQL production engine, Blade UI, Livewire, and internal REST API controllers.

---

## 3. Testing Objectives & Quality Goals

1. **Financial Exactness**:
   - Zero tolerance for calculation errors in freight rates (Per Tonne vs Fixed), diesel advances, cash advances, shortage/detention deductions, and net balance settlements.
   - 100% compliance with Section 194C TDS rules (1% individual/proprietor, 2% company/partnership, 0% valid exemption declaration) and integer paisa rounding.
   - Accurate GST table mapping (Table 4A Forward Charge vs Table 4B Reverse Charge Mechanism / RCM under Notification 13/2017-CT).
2. **Double-Entry Accounting Invariance**:
   - Every invoice, receipt, voucher, and payment must generate balanced journal postings ($\sum \text{Debits} = \sum \text{Credits}$).
   - General Ledger, Day Book, Trial Balance, Profit & Loss, and Balance Sheet reconciliation must remain mathematically locked under all transaction sequences.
3. **Multi-Tenant Isolation & Zero Data Leakage**:
   - Strict tenant boundary enforcement across companies and branches.
   - Comprehensive protection against Insecure Direct Object References (IDOR/BOLA) across URLs, resource IDs, and API payloads.
4. **Resilience & State Integrity**:
   - Database atomicity via database transactions: if an invoice generation fails halfway, accounting vouchers, credit consumption, and counter states must cleanly roll back without leaving orphan rows.
   - Graceful handling of subscription expiry, exhausted LR credits, blocked accounts, and invalid operational inputs.

---

## 4. Test Pyramid & Methodology

```
                   ▲
                  / \
                 /   \
                / E2E \       End-to-End User Journeys (Booking → Invoice → Settlement)
               /-------\
              / Integr. \     Accounting Postings, Event Rollbacks, Multi-Tenant Checks
             /-----------\
            / Feature/API \   HTTP Controllers, Form Requests, RBAC, DataTables JSON
           /---------------\
          /   Unit Tests    \ Calculation Services, TDS, Pricing, Subscription Guards
         /-------------------\
```

### A. Unit Tests (`tests/Unit/`)
- Isolated test cases without database overhead where possible.
- **Focus**: `TransporterTdsCalculator`, `FreightBookingCalculationService`, `InvoiceNumberService`, `SubscriptionGuard`, `SubscriptionUsageService`, `LorryReceiptCalculationService`, `FreightBookingAmountVerifier`.

### B. Feature & HTTP Tests (`tests/Feature/`)
- Tests simulating user interactions through Laravel's HTTP layer with in-memory database assertions.
- **Focus**:
  - `Master/`: Goods types, pumps, trucks (driver/owner assignment), transporter masters.
  - `Accounting/`: Day Book, Ledger Statement, Trial Balance, P&L, Balance Sheet, GSTR-1, Debtors Aging, and Tally XML exports.
  - `Portal/`: Customer portal and Transporter portal access control and query scopes.
  - `Dashboard/`: Role-specific dashboards (Admin, Moderator, Accountant, Customer, Transporter, Super Admin).
  - `Api/Admin/`: Control plane HMAC-SHA256 signature verification, quotes, offline payments, and plan renewals.

### C. Financial & Double-Entry Integration Tests (`tests/Feature/Integration/`)
- Verifies `AccountingPostingService` and `ChartOfAccountsProvisioner`.
- Confirms balanced voucher items (`type: 'debit'`, `type: 'credit'`) for Sales Invoices, Customer Receipts, Transporter Hire Vouchers, Cash Advances, and Diesel Slips.
- Confirms database transaction rollback under simulated exceptions.

### D. Security & Tenant Isolation Tests (`tests/Feature/MultiTenant/`)
- Two independent tenant companies (`Tenant A` and `Tenant B`) created in parallel.
- Asserts that Tenant B cannot read, edit, delete, or disassociate Tenant A's invoices, LRs, consignees, trucks, or drivers.
- Asserts that blocked companies (`is_blocked = 1`) or blocked users are halted by `auth.blocked` middleware immediately.

### E. Negative & Boundary Condition Tests (`tests/Feature/Broker/`)
- Expired subscription: block LR creation.
- Grace period: read-only access (GET allowed, POST/PUT/DELETE blocked with 403).
- Zero LR credits: block dispatch.
- Invalid truck number formats (regex validation).
- Delivered LR protection: prevent deletion of completed consignments.

---

## 5. Test Environment & Tooling Architecture

| Component | Specification | Purpose |
|---|---|---|
| **PHP Runtime** | PHP 8.4.25 (cli) | Application runtime execution |
| **Testing Harness** | PHPUnit 11.5.46 | Test runner and assertion framework |
| **Coverage Engine** | PCOV 1.0.12 | Ultra-fast C-extension line coverage driver |
| **Database** | SQLite `:memory:` | High-velocity, isolated, ephemeral test database |
| **HTTP Client** | Laravel `MakesHttpRequests` | Full middleware, session, and routing execution |
| **Mocking** | Mockery & PHPUnit Mocks | External webhooks and clock overrides |

---

## 6. Execution Protocols

### Fast Development Run
```bash
php -d memory_limit=512M ./vendor/bin/phpunit --testdox
```

### Single Test Execution
```bash
php -d memory_limit=512M ./vendor/bin/phpunit tests/Feature/Broker/BrokerEndToEndWorkflowTest.php
```

### Full Coverage Generation
```bash
php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app ./vendor/bin/phpunit --coverage-text --coverage-html=coverage-report
```

---

## 7. Defect Management & Resolution Workflow

1. **Identification**: Any test failure is classified into one of four categories:
   - *Application Defect*: Production code diverges from documented business rules.
   - *Security Vulnerability*: Unauthorized data access or bypass of multi-tenant boundaries.
   - *Assertion Gap*: Outdated test expectations against updated features.
   - *Infrastructure Blocker*: Database engine nuances (e.g., SQLite vs MySQL functions).
2. **Safety Rule**: Never weaken assertions or eliminate edge cases to force a green result.
3. **Tracking**: Every defect and correction is cataloged in `docs/testing/DEFECT_REGISTER.md`.
