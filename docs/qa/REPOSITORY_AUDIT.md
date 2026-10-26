# TruckBill Repository Technical & Business Audit

## 1. Executive Summary

- **Application Name**: TruckBill (Broker & Transport Management SaaS)
- **Framework**: Laravel 12.61.0 on PHP 8.4.21
- **Primary Database Engine**: MySQL 8.0+ (Production) / SQLite 3 in-memory (Test/CI)
- **Frontend Layer**: Blade Views, Bootstrap 5.3, AdminLTE 4, Vite 6, TailwindCSS 4, DataTables, Select2
- **Testing Engine**: PHPUnit 11.5.55 with SQLite in-memory, Mockery 1.6, Playwright 1.51 (TypeScript E2E)
- **Multi-Tenant Architecture**: Multi-company tenancy segregated by `company_id` and company ownership scopes (`users.user_role_id = 2` for Super Admin company owners, `user_role_id = 1` for Master Platform Admins). Active company switching managed via `session('active_company_id')`.

---

## 2. Codebase Inventory & Structure

### 2.1 Core Architectural Domains

| Layer | Path | Description & Role |
|-------|------|--------------------|
| **Controllers (Web)** | `app/Http/Controllers/` | 27 controllers managing LRs, Freight Bookings, Invoices, Vouchers, Trucks, Drivers, Subscriptions, Reports, and Settings. |
| **Controllers (API & Admin)** | `app/Http/Controllers/Api/Admin/` | Control plane subscription management, catalog sync, promo validations, webhook handling. |
| **Controllers (Portal)** | `app/Http/Controllers/` | Customer Portal (`CustomerPortalController`) and Transporter/Driver Portal (`TransporterDashboardController`). |
| **Models** | `app/Models/` | 38 Eloquent models encapsulating schema definitions, soft deletes, boot events, relations, and business scopes. |
| **Services** | `app/Services/` | Domain logic extracted from controllers: `BrokerCalculationAndPricingService`, `SubscriptionCheckoutService`, `AdminApiClient`, `VoucherPaymentService`, `RazorpayService`. |
| **Form Requests** | `app/Http/Requests/` | 42 form requests enforcing strict input validation, authorization, and route-binding ownership checks. |
| **Middleware** | `app/Http/Middleware/` | `EnsureActiveCompanyContext`, `CheckBlockMiddleware`, `EnsureAdminAuthenticated`, `CheckSingleDeviceLogin`, `RoleMiddleware`. |
| **Policies & Auth** | `app/Policies/` | Granular authorization for LR, Invoice, Booking, and Company operations. |
| **Console Commands** | `app/Console/Commands/` | Subscription expiry checks, reminder dispatchers, credit replenishment jobs. |

---

## 3. Database Schema & Multi-Tenancy Segregation

### 3.1 Primary Entities and Tenancy Mapping

1. **Companies (`companies`)**:
   - Master entity for multi-tenancy.
   - Tied to owner `user_id`.
   - Contains GSTIN, PAN (with dummy fallback generator), bank details, digital signature, and active status (`is_blocked`).
2. **Branches (`branches`)**:
   - Sub-units within a company (`company_id`).
   - Head office designation (`head_office` boolean).
3. **Lorry Receipts (`lorry_receipts`)**:
   - Primary operational consignment document ("Bilty" / GR).
   - Segregated by `company_id` and optionally `branch_id`.
   - Links to Consignor, Consignee, Truck, Driver, Freight Booking (`freight_booking_id`), and Parent LR (`parent_id` for linked multi-party LRs).
4. **Freight Bookings (`freight_bookings`)**:
   - Lorry hire agreement between broker and truck owner/transporter.
   - Tracks total hire freight, loading/unloading charges, broker commission, advance cash/diesel, TDS deductions, and net balance payable.
5. **Customer Invoices (`customer_invoices`) & Items (`customer_invoice_items`)**:
   - Commercial billing generated against completed or dispatched LRs.
   - Calculates taxable amount, CGST, SGST, IGST, and payment status (Pending, Partial, Paid).
6. **Voucher Payments (`voucher_payments`)**:
   - Receipts and payment disbursements for freight balances, driver advances, broker charges, and customer settlements.

### 3.2 Security and Tenant Boundary Enforcement

- **Database-Level Protection**: Global scopes and query filters mandate `$query->where('company_id', session('active_company_id'))`.
- **Route-Binding Authorization**: All FormRequests verify that the model being updated or deleted belongs to a company owned by `Auth::user()`.
- **Context Injection**: `EnsureActiveCompanyContext` middleware validates the active tenant context on every state-mutating request.

---

## 4. Financial & Calculation Integrity

### 4.1 Brokerage and Freight Math

1. **Customer LR Freight Calculation**:
   $$\text{Subtotal} = \text{Freight Amount} + \text{Loading Charges} + \text{Unloading Charges} + \text{Halting} + \text{Other Charges} - \text{Deductions}$$
2. **Lorry Hire (Truck Owner) Payable**:
   $$\text{Net Balance} = \text{Agreed Freight} - \text{Advance Cash} - \text{Advance Diesel/HSD} - \text{Brokerage/Commission} - \text{TDS}$$
3. **TDS Withholding Rules**:
   - Under Section 194C of the Indian Income Tax Act:
     - 1% for Individual / Proprietary Truck Owners with valid PAN.
     - 2% for Companies / Partnerships with valid PAN.
     - 20% penalty TDS if PAN is missing or dummy.
4. **GST Reconciliation**:
   - Forward charge vs Reverse Charge Mechanism (RCM): Transport Agency (GTA) standard RCM @ 5% vs forward charge @ 12%.

---

## 5. Security & RBAC Audit

- **Role Definitions**:
  - `master_admin` (Role ID 1): Platform super-administrator. Access to global billing, tenant monitoring, and system metrics.
  - `super_admin` (Role ID 2): Company owner / SaaS subscriber. Full administrative authority over own tenant companies.
  - `admin` (Role ID 3): Operational company manager. Cannot delete companies or alter subscription plans.
  - `operator` / `staff` (Role ID 4+): Limited day-to-day data entry (LR creation, status update).
- **Authentication**: Stateful session auth with CSRF protection, rate limiting on login/reset routes, and password hash using Bcrypt (cost 12 in production, cost 4 in tests).
- **Data Protection**: Soft deletes applied to sensitive entities (`companies`, `lorry_receipts`, `freight_bookings`, `customer_invoices`, `customers`) with tracking of `deleted_by` and `deleted_remark`.
