# TruckBill Testing Architecture & Engineering Framework

## 1. Architectural Philosophy
TruckBill's automated testing infrastructure provides rapid, reliable, multi-layered quality gates encompassing unit, integration, multi-tenant security, financial calculation, and browser-driven end-to-end (E2E) testing.

---

## 2. Technology Stack & Framework Components

| Component | Technology | Rationale & Configuration |
|-----------|------------|---------------------------|
| **Core Test Runner** | PHPUnit 11.5.55 | Native Laravel 12 integration, strict deprecation/warning flags, parallel capability. |
| **Test Database** | SQLite 3 (`:memory:`) | Zero external database dependencies; ultra-fast schema creation via `RefreshDatabase`. |
| **Mocking Engine** | Mockery 1.6 & PHPUnit Mocks | Service virtualization for external APIs (Razorpay, Control Plane API, SMS/Email). |
| **End-to-End Runner**| Playwright 1.51.0 (TypeScript) | Chromium headless execution for real browser journeys and DOM validation. |
| **Continuous Integration** | GitHub Actions (`broker-tests.yml`) | Automated pipeline running unit, integration, and E2E suites on push and PR. |

---

## 3. Test Directory Organization

```
tests/
├── Feature/
│   ├── Accounting/          # General ledger, journal entries, balance tracking
│   ├── Api/Admin/           # Control plane subscription and catalog synchronization
│   ├── Auth/                # Authentication, RBAC, password reset, rate limiting
│   ├── Broker/              # Broker calculations, commissions, multi-transporter orders
│   ├── Company/             # Multi-tenant context switching, branch management, company settings
│   ├── Freight/             # Lorry hire, freight booking lifecycle, fuel advances, TDS deductions
│   ├── Invoice/             # Customer billing, GST reconciliation (CGST/SGST/IGST), RCM
│   ├── LR/                  # Lorry receipts (Bilty), multi-LR grouping, status transitions, PDF exports
│   ├── MultiTenant/         # Boundary isolation, tenant data leakage prevention
│   ├── Portal/              # Customer portal and Driver/Transporter portal security
│   ├── Reports/             # Profit & loss, freight summary, truck profitability
│   └── Voucher/             # Voucher creation, disbursement, bulk payment allocations
├── Unit/
│   ├── Helpers/             # Math utilities, currency formatters, string sanitizers
│   ├── Http/Requests/       # FormRequest authorization and validation rules
│   ├── Models/              # Eloquent relations, scopes, mutators, soft deletes
│   └── Services/            # Business calculation and checkout domain services
e2e/
├── tests/
│   ├── auth.spec.ts         # User authentication, CSRF, login rejection, password reset
│   ├── lorry-receipt.spec.ts# LR creation, data grid display, status workflow
│   ├── freight-booking.spec.ts # Freight booking creation, hire calculations, advance logging
│   ├── invoice-billing.spec.ts # Invoice generation and voucher payment validation
│   └── multi-tenant.spec.ts # Access isolation and forbidden unauthorized route tests
└── playwright.config.ts    # Browser definitions, timeouts, video/trace configs
```

---

## 4. Test Data Strategy & Database Lifecycle

1. **In-Memory Isolation**:
   Every test class utilizes `Illuminate\Foundation\Testing\RefreshDatabase`. Migrations are executed in-memory on SQLite, ensuring absolute isolation between individual test runs.
2. **Factory-Driven Fixtures**:
   Tests construct valid domain models using Laravel Eloquent Factories (`User::factory()`, `Company::factory()`, `Truck::factory()`).
3. **Explicit Multi-Tenant Scoping**:
   All state-mutating tests explicitly establish an authenticated context (`$this->actingAs($user)`) and an active company context (`session(['active_company_id' => $company->id])`).

---

## 5. Security & Isolation Testing Rules

1. **Tenant Cross-Contamination Gate**:
   Every resource endpoint is validated against unauthorized access by users belonging to competing tenant companies. Tests assert HTTP 403 Forbidden or HTTP 404 Not Found when cross-tenant IDs are accessed.
2. **Strict Financial Precision**:
   Financial calculations use `bcmath` or standard floating precision assertions with delta bounds (`$this->assertEqualsWithDelta(...)` or `$this->assertSame(...)`) to prevent rounding discrepancies in freight, taxes, and TDS.
