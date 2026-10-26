# TruckBill User Acceptance Testing (UAT) Test Cases

## 1. Scope and Objective
This document outlines real-world operational User Acceptance Testing (UAT) test cases tailored for transport operators, transport brokers, fleet managers, accountants, and company owners.

---

## 2. Test Scenarios

### Scenario UAT-01: User Authentication & Security Isolation
- **Role**: Platform User
- **Preconditions**: User registered with credentials.
- **Steps**:
  1. Access login page at `/login`.
  2. Input valid registered email and password; submit form.
  3. Verify redirection to Dashboard `/dashboard`.
  4. Attempt direct navigation to `/admin` or unauthorized company routes.
- **Expected Outcome**:
  - Valid user reaches active company dashboard.
  - Unauthorized routes return 403 or redirect to login.

---

### Scenario UAT-02: Consignment (LR / Bilty) Lifecycle
- **Role**: Dispatcher / Billing Clerk
- **Preconditions**: Consignor, Consignee, Truck, and Driver records created.
- **Steps**:
  1. Navigate to `/lorry_receipts/create`.
  2. Select Consignor, Consignee, Truck Number, and Driver.
  3. Enter 100 packages, weight 15.00 MT, rate ₹2,500/MT.
  4. Add Loading Charges (₹1,500), Halting (₹1,000), Deductions (₹500).
  5. Verify total calculated freight: ₹39,500.
  6. Submit LR and generate printable PDF copies.
- **Expected Outcome**:
  - LR generated with unique sequential GR Number.
  - Multi-copy PDF correctly formatted with company header and QR code.

---

### Scenario UAT-03: Brokerage & Lorry Hire (Freight Booking) Execution
- **Role**: Transport Broker
- **Preconditions**: LRs created and ready for truck assignment.
- **Steps**:
  1. Navigate to `/freight_bookings/create`.
  2. Select Truck Supplier / Transporter.
  3. Link dispatched LR to booking.
  4. Input Supplier Freight: ₹35,000.
  5. Enter Cash Advance: ₹10,000; Diesel Card Advance: ₹12,000.
  6. Verify TDS withholding deduction (1% for Individual PAN = ₹350).
  7. Verify Net Balance calculation: ₹35,000 - ₹10,000 - ₹12,000 - ₹350 = ₹12,650.
  8. Save Freight Booking and print Lorry Hire Challan.
- **Expected Outcome**:
  - Balance payable recorded accurately as ₹12,650.
  - Advance slips issued and tied to truck owner ledger.

---

### Scenario UAT-04: Batch Invoicing & GST Reconciliation
- **Role**: Billing Accountant
- **Preconditions**: Multiple LRs completed for the same Consignor.
- **Steps**:
  1. Navigate to `/customer_invoices/create`.
  2. Filter unbilled LRs for selected Consignor.
  3. Select multiple LRs to combine into a single commercial tax invoice.
  4. Apply 5% GST (or verify RCM indicator).
  5. Save invoice and trigger PDF generation.
- **Expected Outcome**:
  - Consolidated tax invoice generated.
  - Tax breakdown matches state GST regulations (CGST/SGST or IGST).
  - Selected LRs are marked as "Billed".

---

### Scenario UAT-05: Payment Settlement & Voucher Generation
- **Role**: Accounts Cashier
- **Preconditions**: Unpaid Freight Bookings and pending Customer Invoices.
- **Steps**:
  1. Navigate to `/voucher_payments/create`.
  2. Select Party (Truck Owner or Customer) and Payment Direction (Payment / Receipt).
  3. Allocate payment against specific pending bill or hire balance.
  4. Record payment mode (Bank Transfer / Cheque / Cash) and reference transaction number.
  5. Submit voucher.
- **Expected Outcome**:
  - Voucher generated with sequential voucher number.
  - Party outstanding balance updated immediately.
  - Bank/Cash ledger reflects corresponding debit or credit.

---

### Scenario UAT-06: Subscription & Credit Quota Enforcement
- **Role**: Company Owner
- **Preconditions**: Active subscription plan or trial.
- **Steps**:
  1. Check remaining LR credits on Dashboard.
  2. Attempt to create LR when credit quota is depleted.
  3. Verify system prevents creation and prompts for subscription upgrade.
  4. Complete renewal checkout flow and verify credits replenish immediately.
- **Expected Outcome**:
  - Quota rules strictly enforced without data corruption.
  - Plan upgrades immediately restore operational capabilities.
