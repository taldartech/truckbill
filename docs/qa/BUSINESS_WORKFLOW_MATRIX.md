# TruckBill Business Workflow Matrix

## 1. Executive Overview
This document inventories and details the end-to-end transportation and brokerage operational workflows supported by the TruckBill SaaS platform.

---

## 2. Core Operational Lifecycles

### 2.1 Workflow 1: Master Setup (Entity Onboarding)
- **Actor**: Super Admin / Operations Manager
- **Steps**:
  1. Onboard Company with legal GSTIN, State, Bank Details, and PAN.
  2. Setup Branches (Head Office vs Regional Hubs).
  3. Register Consignors (Senders), Consignees (Receivers), Truck Owners (Suppliers), and Drivers.
  4. Validate PAN number format and identify dummy PANs requiring mandatory TDS adjustments.
- **Automated Validation**: Handled by `CompanyManagementTest`, `BranchManagementTest`, and `MasterDataTest`.

---

### 2.2 Workflow 2: Lorry Receipt (LR / Bilty) Generation
- **Actor**: Dispatcher / Billing Operator
- **Steps**:
  1. Input Consignor, Consignee, Delivery Destination, Truck No, Driver.
  2. Enter consignment items (packages, weight, rate per MT/fixed, charge type: Paid / To Pay / To Be Billed).
  3. Additional charges: Loading/Unloading, Halting, Insurance, Hamali, Toll.
  4. System calculates Total Freight, Deductions, and Net Receivable.
  5. Generate multi-copy PDF: Consignor Copy, Consignee Copy, Driver Copy, File Copy.
- **Automated Validation**: `LorryReceiptTest`, `LorryReceiptLinkedLRTest`, `LorryReceiptPDFTest`.

---

### 2.3 Workflow 3: Lorry Hire & Freight Booking (Brokerage Operational Flow)
- **Actor**: Transport Broker
- **Steps**:
  1. Negotiate freight with 3rd-party truck supplier/transporter.
  2. Create Freight Booking linked to one or multiple LRs (`lorry_receipts.freight_booking_id`).
  3. Enter Supplier Agreed Freight.
  4. Record immediate Advances (Cash Advance, Diesel Card / Fuel Slip Advance).
  5. Broker Commission / TDS calculation:
     - Deduct 1% TDS if Truck Owner PAN is Individual.
     - Deduct 2% TDS if Truck Owner PAN is Corporate/Partnership.
     - Deduct 20% TDS if Truck Owner PAN is dummy or unverified.
  6. Compute Balance Payable on Proof of Delivery (POD) submission.
- **Automated Validation**: `FreightBookingTest`, `BrokerCalculationAndPricingUnitTest`.

---

### 2.4 Workflow 4: Consignment Invoicing & Billing
- **Actor**: Accountant / Billing Manager
- **Steps**:
  1. Filter unbilled LRs for a Customer/Consignor.
  2. Batch-generate Customer Invoice (`customer_invoices`).
  3. Apply GST rules:
     - Inter-state consignment: IGST (e.g. 5% or 12%).
     - Intra-state consignment: CGST + SGST split.
     - Reverse Charge Mechanism (RCM) flag if consignor pays GST directly.
  4. Track invoice delivery status, payment milestones, and outstanding aging.
- **Automated Validation**: `CustomerInvoiceTest`, `InvoiceCalculationTest`.

---

### 2.5 Workflow 5: Payment Settlements & Voucher Management
- **Actor**: Cashier / Accounts Officer
- **Steps**:
  1. Issue Payment Vouchers for Truck Hire Balances, Driver Halting, Loading Expenses.
  2. Issue Receipt Vouchers for Customer Invoices and Direct Freight Receipts.
  3. Maintain multi-tenant general ledger and bank account balances.
- **Automated Validation**: `VoucherPaymentTest`, `VoucherPaymentBulkVoucherTest`.

---

### 2.6 Workflow 6: SaaS Subscription & Plan Governance
- **Actor**: Platform Admin & Tenant Owner
- **Steps**:
  1. Free Trial initiation (15 days, 100 LR credits, 5 branches, 5 users).
  2. Catalog fetch from control plane API.
  3. Plan upgrade / renewal via Razorpay gateway integration.
  4. Credit quota enforcement on LR creation.
  5. Lockout / Read-only enforcement upon subscription lapse.
- **Automated Validation**: `SubscriptionManagementTest`, `SubscriptionCheckoutTest`, `ControlPlaneSubscriptionApiTest`.
