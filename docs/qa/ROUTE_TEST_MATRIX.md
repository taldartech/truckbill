# TruckBill Route Test Matrix

## 1. Overview
This matrix maps every application endpoint, HTTP method, controller action, middleware protection layer, and corresponding automated PHPUnit/Pest/Playwright test suite.

---

## 2. Web Routes & Test Suite Coverage

### 2.1 Authentication & Profile Routes

| Method | URI | Controller Action | Middleware | Test Suite |
|--------|-----|-------------------|------------|------------|
| GET | `/login` | `Auth\LoginController@showLoginForm` | `web, guest` | `tests/Feature/Auth/AuthRBACTest.php` |
| POST | `/login` | `Auth\LoginController@login` | `web, guest` | `tests/Feature/Auth/AuthRBACTest.php` |
| POST | `/logout` | `Auth\LoginController@logout` | `web, auth` | `tests/Feature/Auth/AuthRBACTest.php` |
| GET | `/forgot-password` | `Auth\ForgotPasswordController@showLinkRequestForm` | `web, guest` | `tests/Feature/Auth/PasswordResetFlowTest.php` |
| POST | `/forgot-password` | `Auth\ForgotPasswordController@sendResetLinkEmail` | `web, guest` | `tests/Feature/Auth/PasswordResetFlowTest.php` |
| GET | `/reset-password/{token}` | `Auth\ResetPasswordController@showResetForm` | `web, guest` | `tests/Feature/Auth/PasswordResetFlowTest.php` |
| POST | `/reset-password` | `Auth\ResetPasswordController@reset` | `web, guest` | `tests/Feature/Auth/PasswordResetFlowTest.php` |

---

### 2.2 Company & Branch Management

| Method | URI | Controller Action | Middleware | Test Suite |
|--------|-----|-------------------|------------|------------|
| GET | `/companies` | `CompanyController@index` | `web, auth` | `tests/Feature/Company/CompanyManagementTest.php` |
| POST | `/companies` | `CompanyController@store` | `web, auth` | `tests/Feature/Company/CompanyManagementTest.php` |
| GET | `/companies/{company}/edit` | `CompanyController@edit` | `web, auth` | `tests/Feature/Company/CompanyManagementTest.php` |
| PUT | `/companies/{company}` | `CompanyController@update` | `web, auth` | `tests/Feature/Company/CompanyManagementTest.php` |
| GET | `/company/enter/{company}` | `CompanyController@enter` | `web, auth` | `tests/Feature/Company/CompanyContextSwitchTest.php` |
| GET | `/company/exit` | `CompanyController@exit` | `web, auth` | `tests/Feature/Company/CompanyContextSwitchTest.php` |
| RESOURCE | `/branches` | `BranchController` | `web, auth, company.active` | `tests/Feature/Company/BranchManagementTest.php` |

---

### 2.3 Operational Consignments (Lorry Receipts / Bilty)

| Method | URI | Controller Action | Middleware | Test Suite |
|--------|-----|-------------------|------------|------------|
| GET | `/lorry_receipts` | `LorryReceiptController@index` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptTest.php` |
| GET | `/lorry_receipts/create` | `LorryReceiptController@create` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptTest.php` |
| POST | `/lorry_receipts` | `LorryReceiptController@store` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptTest.php` |
| GET | `/lorry_receipts/{lr}` | `LorryReceiptController@show` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptTest.php` |
| GET | `/lorry_receipts/{lr}/edit` | `LorryReceiptController@edit` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptTest.php` |
| PUT | `/lorry_receipts/{lr}` | `LorryReceiptController@update` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptTest.php` |
| DELETE | `/lorry_receipts/{lr}` | `LorryReceiptController@destroy` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptTest.php` |
| GET | `/lorry_receipts/create-linked` | `LorryReceiptController@createLinked` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptLinkedLRTest.php` |
| POST | `/lorry_receipts/bulk-payment` | `LorryReceiptController@bulkPayment` | `web, auth, company.active` | `tests/Feature/LR/LorryReceiptBulkPaymentTest.php` |

---

### 2.4 Lorry Hire & Freight Bookings

| Method | URI | Controller Action | Middleware | Test Suite |
|--------|-----|-------------------|------------|------------|
| GET | `/freight_bookings` | `FreightBookingController@index` | `web, auth, company.active` | `tests/Feature/Freight/FreightBookingTest.php` |
| GET | `/freight_bookings/create` | `FreightBookingController@create` | `web, auth, company.active` | `tests/Feature/Freight/FreightBookingTest.php` |
| POST | `/freight_bookings` | `FreightBookingController@store` | `web, auth, company.active` | `tests/Feature/Freight/FreightBookingTest.php` |
| GET | `/freight_bookings/{fb}` | `FreightBookingController@show` | `web, auth, company.active` | `tests/Feature/Freight/FreightBookingTest.php` |
| GET | `/freight_bookings/{fb}/edit` | `FreightBookingController@edit` | `web, auth, company.active` | `tests/Feature/Freight/FreightBookingTest.php` |
| PUT | `/freight_bookings/{fb}` | `FreightBookingController@update` | `web, auth, company.active` | `tests/Feature/Freight/FreightBookingTest.php` |
| DELETE | `/freight_bookings/{fb}` | `FreightBookingController@destroy` | `web, auth, company.active` | `tests/Feature/Freight/FreightBookingTest.php` |

---

### 2.5 Billing, Invoicing & Voucher Settlements

| Method | URI | Controller Action | Middleware | Test Suite |
|--------|-----|-------------------|------------|------------|
| RESOURCE | `/customer_invoices` | `CustomerInvoiceController` | `web, auth, company.active` | `tests/Feature/Invoice/CustomerInvoiceTest.php` |
| RESOURCE | `/voucher_payments` | `VoucherPaymentController` | `web, auth, company.active` | `tests/Feature/Voucher/VoucherPaymentTest.php` |
| GET | `/voucher_payments/bulk-create` | `VoucherPaymentController@bulkCreate` | `web, auth, company.active` | `tests/Feature/Voucher/VoucherPaymentBulkVoucherTest.php` |

---

### 2.6 Subscriptions & Admin API

| Method | URI | Controller Action | Middleware | Test Suite |
|--------|-----|-------------------|------------|------------|
| GET | `/subscription` | `SubscriptionController@index` | `web, auth` | `tests/Feature/Subscription/SubscriptionManagementTest.php` |
| POST | `/subscription/checkout` | `SubscriptionController@checkout` | `web, auth` | `tests/Feature/Subscription/SubscriptionCheckoutTest.php` |
| POST | `/subscription/callback` | `SubscriptionController@callback` | `web, auth` | `tests/Feature/Subscription/SubscriptionCheckoutTest.php` |
| API | `/api/admin/subscription/*` | `Api\Admin\SubscriptionController` | `api, admin.auth` | `tests/Feature/Api/Admin/ControlPlaneSubscriptionApiTest.php` |
