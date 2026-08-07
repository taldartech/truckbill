# TruckBill - Data Model Documentation

## Overview
TruckBill is a **freight and logistics management system** built with Laravel. It manages the complete lifecycle of freight operations from booking to invoicing and payment processing, with multi-tenant support through companies.

## Core Domain Concepts

### 1. Multi-Tenancy Architecture
The system is built around **Companies** as the primary tenant:
- Each company has its own data isolation
- Users belong to companies and branches
- All major entities (LRs, invoices, bookings, etc.) are scoped to companies

### 2. Main Business Entities

#### **Company** (Tenant)
- Central entity for multi-tenancy
- Contains company details (GST, PAN, address, location)
- Manages subscription/plan information (truck credits, LR credits, plan dates)
- Has branches, users, and all business entities

#### **Branch**
- Sub-organization within a company
- Users can be assigned to specific branches
- Supports head office designation

#### **User & UserRole**
- Authentication and authorization
- Users belong to companies and optionally branches
- Role-based access control through UserRole

---

## Freight Operations Core Models

### **LorryReceipt (LR)** - Primary Document
The **Lorry Receipt** is the core document representing a freight shipment.

**Key Attributes:**
- `lr_number` - Unique LR identifier
- `delivery_status` - Pending/Delivered/Cancelled
- `gr_date`, `gr_number` - Goods Receipt details
- `invoice_number` - Links to generated invoice
- `memo_id` - Optional memo reference

**Relationships:**
- `truck` - Vehicle used
- `transporter` - Transport service provider
- `consignor` - Shipper (Consignee model)
- `consignee` - Receiver (Consignee model)
- `customer` - Billing customer (Consignee model)
- `company` - Tenant scope
- `freightBookings` - Has many freight bookings

**Special Features:**
- Soft deletes
- Tracks user who created/updated
- Can link to another LR (`linked_lr_id`)
- Syncs delivery status with freight bookings

### **FreightBooking** - Transport Arrangement
Represents the booking/arrangement with a transporter for a specific LR.

**Key Attributes:**
- `tr_fr_rate`, `tr_amount` - Transporter freight rate and amount
- `advance_amount`, `balance_amount` - Payment tracking
- `tds_amount` - Tax deducted at source
- `shortage_amount`, `damage_amount` - Loss tracking
- `detention`, `deviation`, `misc_expense` - Additional charges
- `is_delivered`, `is_cancelled` - Status flags
- `eway_bill_number`, `eway_bill_expiry` - E-way bill compliance
- `voucher_number` - Payment voucher reference
- `pan_verified` - PAN verification status

**Relationships:**
- `lr` - Belongs to LorryReceipt
- `transporter` - Transport service provider
- `truck` - Vehicle assigned
- `company` - Tenant scope
- `voucherPayments` - Payment records
- `linkedFreightBooking` - Can link to another booking

**Business Logic:**
- Tracks all financial aspects of transport arrangement
- Manages TDS calculations
- Handles shortage/damage adjustments

### **Transporter** - Service Provider
Represents transport companies or truck owners.

**Key Attributes:**
- `name`, `short_name` - Identification
- `gst_number`, `pan_number` - Tax identifiers
- `is_owner` - Flag for truck owner vs transport company
- `tds_percentage` - TDS rate for payments
- `pan_active` - PAN verification status
- `advance_amount` - Outstanding advance
- `beneficiary_id` - Payment beneficiary reference
- Location fields (city, state, country, address)

**Relationships:**
- `company` - Tenant scope
- `trucks` - Owned/managed trucks
- `lorryReceipts` - LRs assigned to this transporter
- Location relationships (city, state, country)

**Special Features:**
- Soft deletes
- Can be marked as truck owner (`is_owner`)

### **Truck** - Vehicle
Represents individual vehicles in the fleet.

**Key Attributes:**
- `truck_number` - Unique vehicle number (unique constraint)
- `truck_type` - Vehicle type classification
- `registration_date` - Vehicle registration

**Relationships:**
- `transporter` - Owner/operator
- `driver` - Assigned driver

**Special Features:**
- Soft deletes
- Unique truck number constraint

### **Driver**
Represents vehicle drivers.

**Key Attributes:**
- `name`, `mobile` - Contact information
- `license_number` - Driver license
- `address` - Driver address

**Relationships:**
- `company` - Tenant scope

**Special Features:**
- Soft deletes

### **Consignee** - Party Entity
Multi-purpose model representing:
- **Consignor** (shipper)
- **Consignee** (receiver)
- **Customer** (billing party)

**Key Attributes:**
- `name`, `address` - Party details
- `gst_number`, `pan_number` - Tax identifiers (PAN unique)
- `contact_number`, `email` - Contact info
- Location fields (city, state, country)

**Relationships:**
- `company` - Tenant scope
- `city`, `state`, `country` - Location
- `lorryReceipts` - As customer

**Special Features:**
- Soft deletes
- Unique PAN number constraint

---

## Financial & Payment Models

### **Invoice** - Customer Billing
Represents invoices generated for customers.

**Key Attributes:**
- `manual_invoice_number` - User-defined invoice number
- `invoice_type` - Reverse/Forward
- `generated_date` - Invoice date
- `total_amount`, `net_amount` - Amounts
- `sgst`, `cgst`, `igst` - Tax components
- `payment_status` - pending/partial/paid
- `memo_ids`, `lr_ids` - Arrays of linked memos/LRs

**Relationships:**
- `company` - Tenant scope
- `consignee` (as customer) - Billing party

**Special Features:**
- Tracks user who created/updated
- Can link multiple memos and LRs

### **VoucherPayment** - Payment Record
Individual payment transactions for freight bookings.

**Key Attributes:**
- `voucher_number` - Payment voucher identifier
- `amount` - Payment amount
- `payment_type`, `payment_mode` - Payment classification
- `payment_type_id`, `payment_mode_id` - Foreign keys to lookup tables
- `payment_status` - pending/in progress/paid
- `date` - Payment date
- `reference_transaction_id` - External transaction reference
- `linked_voucher_id` - Can link to another voucher

**Relationships:**
- `company` - Tenant scope
- `freight_booking` - Associated booking
- `pump` - Optional fuel pump reference

**Special Features:**
- Soft deletes
- Tracks user who created/updated
- Status badge helpers

### **VoucherLinked** - Linked Payment Group
Groups multiple voucher payments together.

**Key Attributes:**
- Similar to VoucherPayment but for grouped payments
- `voucher_ids` - Array of linked voucher IDs
- `freight_booking_id` - Associated booking

**Relationships:**
- `company` - Tenant scope
- `freight_booking` - Associated booking
- `pump` - Optional fuel pump reference

**Special Features:**
- Soft deletes
- Tracks user who created/updated

### **AdvancePayment** - Pre-payment
Tracks advance payments made to transporters.

**Key Attributes:**
- `transporter_id` - Recipient
- `advance_amount` - Payment amount
- `payment_date` - When paid
- `remark` - Notes

**Relationships:**
- `company` - Tenant scope
- `transporter` - Payment recipient

### **PaymentLedger** - Subscription Payments
Tracks subscription/payment transactions for company plans.

**Key Attributes:**
- Razorpay integration fields (order_id, payment_id, signature)
- `truck_count`, `lr_credits` - Subscription quantities
- `tenure` - Subscription period
- `promo_code_id`, `promo_discount` - Discount tracking
- `gst_amount`, `total_amount` - Financial totals
- `status` - Payment status
- `razorpay_response` - Full API response (JSON)

**Relationships:**
- `company` - Tenant scope
- `promoCode` - Applied discount code
- `upcomingPlan` - Associated plan

### **UpcomingPlan** - Subscription Plan
Represents future or active subscription plans.

**Key Attributes:**
- `plan_start_date`, `plan_end_date` - Plan period
- `truck_count` - Number of trucks in plan
- `tenure` - Subscription duration
- `status` - Plan status

**Relationships:**
- `company` - Tenant scope
- `PaymentLedger` - Associated payment

---

## Supporting Models

### **Location Models**
- **Country** - Countries
- **State** - States/Provinces (belongs to Country)
- **City** - Cities (belongs to State and Country)

### **Lookup Tables**
- **PaymentType** - Payment type classifications
- **PaymentMode** - Payment method types
- **GoodType** - Types of goods being transported
- **UserRole** - User role definitions

### **Other Models**
- **Pump** - Fuel pump references (for voucher payments)
- **PromoCode** - Discount codes for subscriptions
- **Branch** - Company branches

---

## Key Design Patterns

### 1. **Multi-Tenancy**
- All business entities have `company_id`
- Data isolation at the company level
- Users scoped to companies

### 2. **Soft Deletes**
Many models use soft deletes:
- Company, Transporter, Truck, Driver, Consignee
- LorryReceipt, VoucherPayment, VoucherLinked
- Allows data recovery and audit trails

### 3. **User Tracking (TracksUser Trait)**
Models using `TracksUser` trait automatically track:
- `created_by` - User who created
- `updated_by` - User who last updated
- Used by: Invoice, FreightBooking, LorryReceipt, VoucherPayment, VoucherLinked

### 4. **Flexible Relationships**
- **Consignee** model serves multiple roles (consignor, consignee, customer)
- **Transporter** can be owner or company
- **LorryReceipt** can link to other LRs for complex scenarios

### 5. **Status Management**
- Delivery status: Pending → In Progress → Delivered/Cancelled
- Payment status: pending → in progress → paid
- Invoice payment status: pending → partial → paid

### 6. **Array Fields**
- `Invoice.memo_ids` and `lr_ids` stored as JSON arrays
- `VoucherLinked.voucher_ids` stored as JSON array
- Allows flexible many-to-many relationships without junction tables

---

## Business Flow

### Typical Workflow:
1. **Create LR** → LorryReceipt created with consignor, consignee, customer
2. **Assign Transporter** → FreightBooking created linking LR to transporter
3. **Track Delivery** → Update delivery status, handle shortage/damage
4. **Process Payments** → VoucherPayment records for transporter payments
5. **Generate Invoice** → Invoice created linking LRs/memos to customer
6. **Track Payments** → Invoice payment status updated

### Financial Tracking:
- **Advance Payments** → Tracked separately for transporters
- **Voucher Payments** → Individual payment transactions
- **Invoice Payments** → Customer payment tracking
- **TDS** → Calculated and tracked in freight bookings

---

## Database Relationships Summary

```
Company
├── Users
├── Branches
├── Transporters
│   ├── Trucks
│   │   └── Drivers
│   └── AdvancePayments
├── Consignees (as consignor/consignee/customer)
├── LorryReceipts
│   ├── FreightBookings
│   │   └── VoucherPayments
│   └── (linked to Invoices via lr_ids array)
└── Invoices

Location Hierarchy:
Country → State → City
(Used by: Company, Transporter, Consignee)
```

---

## Key Constraints & Validations

- **Truck.truck_number** - Unique
- **Transporter.pan_number** - Unique
- **Consignee.pan_number** - Unique
- **LorryReceipt.gr_number** - Unique
- Foreign key constraints on all relationships
- Soft delete cascades handled in model boot methods

---

## Notes

- The system supports both **truck owners** (`is_owner = true`) and **transport companies**
- **E-way bills** are tracked at the freight booking level
- **TDS** calculations are per-transporter based on their TDS percentage
- **Memo** system exists but model not visible (referenced in invoices)
- **Subscription model** supports truck and LR credit-based plans
