<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConsigneeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\LorryReceiptController;
use App\Http\Controllers\FreightBookingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\VoucherPaymentController;
use App\Http\Controllers\TransporterBankAccountController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\TruckOwnerController;
use App\Http\Controllers\TruckOwnerBankAccountController;
use App\Http\Controllers\AdvancePaymentController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VoucherTemplateController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PromoCodeController;

// Protected Routes
Route::middleware(['auth.blocked'])->group(function () {
    Route::group(['middleware' => 'role:super_admin,master_admin'], function () {
        // User Management
        Route::resource('users', UserController::class)->except(['show']);

        // Company Registration
        Route::resource('company', CompanyController::class)->except(['show']);
    });
    // Master Admin only routes
    Route::group(['middleware' => 'role:master_admin'], function () {
        Route::get('/company/{company}/block', [CompanyController::class, 'block'])->name('company.block');
        Route::get('/company/{company}/unblock', [CompanyController::class, 'unblock'])->name('company.unblock');
        Route::resource('promo_codes', PromoCodeController::class)->except(['show']);
    });
    Route::post('promo_codes/validate', [PaymentController::class, 'applyPromoCode'])->name('promo_codes.validate');

    Route::middleware(['role:super_admin'])->group(function () {
        // Branch Management
        Route::resource('branches', BranchController::class)->except(['show']);

        // Voucher Template Routes
        Route::resource('voucher_templates', VoucherTemplateController::class)->except(['create', 'store', 'show', 'destroy']);

        // Truck_Owner Bank Accounts
        Route::get('truck_owner_bank_accounts/index', [TruckOwnerBankAccountController::class, 'index'])->name('truck_owner_bank_accounts.index');
        Route::put('truck_owner_bank_accounts/{transporter_id}/beneficiary-id', [TruckOwnerBankAccountController::class, 'updateBeneficiaryId'])->name('truck_owner_bank_accounts.updateBeneficiaryId');

        // Transporter Bank Accounts
        Route::get('transporter_bank_accounts/index', [TransporterBankAccountController::class, 'index'])->name('transporter_bank_accounts.index');
        Route::put('transporter_bank_accounts/{transporter_id}/beneficiary-id', [TransporterBankAccountController::class, 'updateBeneficiaryId'])->name('transporter_bank_accounts.updateBeneficiaryId');

        // Payment Management Routes
        Route::get('payment', [PaymentController::class, 'index'])->name('payment.index');
        Route::get('payment/create', [PaymentController::class, 'create'])->name('payment.create');
        Route::post('payment', [PaymentController::class, 'store'])->name('payment.store');
        Route::post('payment/callback', [PaymentController::class, 'paymentCallback'])->name('payment.callback');
        Route::get('payment/history', [PaymentController::class, 'history'])->name('payment.history');
        Route::get('payment/{id}/invoice', [PaymentController::class, 'invoice'])->name('payment.invoice');
    });

    Route::redirect('/', '/dashboard');

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
        ->middleware(['auth.blocked', 'verified'])
        ->name('dashboard');

    // Consignee Management
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::resource('consignees', ConsigneeController::class)->only(['index', 'create', 'store']);
    });
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('consignees', ConsigneeController::class)->only(['edit', 'update', 'destroy']);
    });

    // Transporters CRUD
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::resource('transporters', TransporterController::class)->only(['index', 'create', 'store', 'show']);
    });
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('transporters', TransporterController::class)->only(['edit', 'update', 'destroy']);
    });
    Route::get('/transporter/{id}/tds-info', [LorryReceiptController::class, 'getTdsInfo']);


    //Truck Owner Management
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::resource('truck_owners', TruckOwnerController::class)->only(['index', 'create', 'store', 'show']);
    });
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('truck_owners', TruckOwnerController::class)->only(['edit', 'update', 'destroy']);
    });

    // Advance Payment Management
    Route::post('/advance-payments', [AdvancePaymentController::class, 'store'])->name('advance-payments.store')
        ->middleware(['role:admin']);

    // Truck Management
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::get('/trucks', [TruckController::class, 'index'])->name('trucks.index');
        Route::get('/trucks/create/{transporter_id}', [TruckController::class, 'create'])->name('trucks.create');
        Route::post('/trucks', [TruckController::class, 'store'])->name('trucks.store');
        Route::get('/trucks/{truck}', [TruckController::class, 'show'])->name('trucks.show');
    });
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/trucks/{truck}/edit', [TruckController::class, 'edit'])->name('trucks.edit');
        Route::put('/trucks/{truck}', [TruckController::class, 'update'])->name('trucks.update');
        Route::put('/trucks/{truck}/assign-driver', [TruckController::class, 'assignDriver'])->name('trucks.assign-driver');
        Route::delete('/trucks/{truck}', [TruckController::class, 'destroy'])->name('trucks.destroy');
        Route::post('/assign-owner', [TruckController::class, 'assignOwner'])->name('trucks.assign-owner');
        Route::patch('/trucks/{truck}/remove-transporter', [TruckController::class, 'removeTransporter'])
            ->name('trucks.remove-transporter');
    });

    // Lorry Receipt CRUD
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::get('/lorry_receipts/data', [LorryReceiptController::class, 'getLorryReceiptsData'])->name('lorry_receipts.data');
        Route::get('lorry_receipts/create-linked', [LorryReceiptController::class, 'createLinked'])->name('lorry_receipts.createLinked');
        Route::resource('lorry_receipts', LorryReceiptController::class)
            ->except(['edit', 'update', 'destroy']);
        Route::post('lorry_receipts/bulk-payment', [LorryReceiptController::class, 'bulkPayment'])->name('lorry_receipts.bulkPayment');
        Route::post('lorry_receipts/bulk-voucher-payment', [LorryReceiptController::class, 'bulkVoucherPayment'])->name('lorry_receipts.bulkVoucherPayment');
        Route::get('/lorryreceipts/by-linked/{linkedId}', [App\Http\Controllers\LorryReceiptController::class, 'getByLinkedId'])
            ->name('lorryreceipts.by_linked');
    });
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/lorry_receipts/{lorry_receipt}/edit', [LorryReceiptController::class, 'edit'])->name('lorry_receipts.edit');
        Route::put('/lorry_receipts/{lorry_receipt}', [LorryReceiptController::class, 'update'])->name('lorry_receipts.update');
        Route::delete('/lorry_receipts/{lorry_receipt}', [LorryReceiptController::class, 'destroy'])->name('lorry_receipts.destroy');
    });

    // Freight Bookings
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::post('/freight_bookings/store_combined_payment', [FreightBookingController::class, 'storeCombinedPayment'])->name('freight_bookings.store_combined_payment');
        Route::get('/freight_bookings/data', [FreightBookingController::class, 'getFreightBookingsData'])->name('freight_bookings.datatable');
        Route::post('/freight_bookings/confirm', [FreightBookingController::class, 'confirm'])->name('freight_bookings.confirm');
        Route::resource('freight_bookings', FreightBookingController::class);
        Route::post('/freight_bookings/voucher-payment', [FreightBookingController::class, 'voucherPayment'])->name('freight_bookings.voucher_payment');
        Route::post('/freight_bookings/bulk-memo-id', [FreightBookingController::class, 'bulkMemoId'])->name('freight_bookings.bulk_memo_id');
        Route::post('/freight_bookings/payment-history', [FreightBookingController::class, 'getPaymentHistory'])->name('freight_bookings.payment_history');
    });

    // Invoices
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::resource('invoices', InvoiceController::class)
            ->except(['edit', 'update', 'destroy']);
        Route::get('/invoices/lrs/{memoIds}', [InvoiceController::class, 'getLrs'])->name('invoices.lrs');
        Route::get('/invoices/memos/{customerId}', [InvoiceController::class, 'getCustomerMemos']);
        Route::get('/invoices/all-memos/{customerId}', [InvoiceController::class, 'getAllCustomerMemos']);
        Route::get('/invoices/pending-memo/{customerId}', [InvoiceController::class, 'getPendingMemo']);
    });
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
        Route::get('/invoices/{invoice}/payments', [InvoiceController::class, 'getPayments'])->name('invoices.payments.index');
    });

    // Voucher Payments
    // Admin: full CRUD + bulk actions
    Route::middleware(['role:admin'])->group(function () {
        // Create / Store
        Route::get('voucher_payments/create', [VoucherPaymentController::class, 'create'])->name('voucher_payments.create');
        Route::post('voucher_payments', [VoucherPaymentController::class, 'store'])->name('voucher_payments.store');

        // Edit / Update / Delete
        Route::get('voucher_payments/{voucher_payment}/edit', [VoucherPaymentController::class, 'edit'])->name('voucher_payments.edit');
        Route::put('voucher_payments/{voucher_payment}', [VoucherPaymentController::class, 'update'])->name('voucher_payments.update');
        Route::delete('voucher_payments/{voucher_payment}', [VoucherPaymentController::class, 'destroy'])->name('voucher_payments.destroy');

        // Bulk / status updates
        Route::post('voucher_payments/bulk-update', [VoucherPaymentController::class, 'bulkUpdateStatus'])->name('voucher_payments.bulkUpdateStatus');
        Route::post('voucher_payments/bulk-payment', [VoucherPaymentController::class, 'bulkPayment'])->name('voucher_payments.bulkPayment');
        Route::put('voucher_payments/update-status', [VoucherPaymentController::class, 'updateStatus'])->name('voucher_payments.update_status');
    });

    // Admin + Moderator: read-only access (index, show, daily report)
    Route::middleware(['role:admin,moderator'])->group(function () {
        Route::get('voucher_payments', [VoucherPaymentController::class, 'index'])->name('voucher_payments.index');
        Route::get('voucher_payments/{voucherNumber}', [VoucherPaymentController::class, 'show'])->name('voucher_payments.show');
        Route::get('daily-payments', [VoucherPaymentController::class, 'dailyPayments'])->name('voucher_payments.daily');
    });

    // Driver CRUD
    Route::get('/drivers/data', [DriverController::class, 'getDriversData'])->name('drivers.data');
    Route::resource('drivers', DriverController::class)->except(['show']);

    // Master Routes
    Route::prefix('master')->name('master.')->group(function () {
        // Countries
        Route::middleware(['role:admin,moderator'])->group(function () {
            Route::resource('countries', \App\Http\Controllers\CountryController::class)->only(['index', 'create', 'store']);
        });
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('countries', \App\Http\Controllers\CountryController::class)->only(['edit', 'update', 'destroy']);
        });

        // States
        Route::middleware(['role:admin,moderator'])->group(function () {
            Route::resource('states', \App\Http\Controllers\StateController::class)->only(['index', 'create', 'store']);
        });
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('states', \App\Http\Controllers\StateController::class)->only(['edit', 'update', 'destroy']);
        });

        // Cities
        Route::middleware(['role:admin,moderator'])->group(function () {
            Route::resource('cities', \App\Http\Controllers\CityController::class)->only(['index', 'create', 'store']);
        });
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('cities', \App\Http\Controllers\CityController::class)->only(['edit', 'update', 'destroy']);
        });

        // Goods Types
        Route::middleware(['role:admin,moderator'])->group(function () {
            Route::resource('goods-types', \App\Http\Controllers\GoodsTypeController::class)->only(['index', 'create', 'store']);
        });
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('goods-types', \App\Http\Controllers\GoodsTypeController::class)->only(['edit', 'update', 'destroy']);
        });

        // Pumps
        Route::middleware(['role:admin,moderator'])->group(function () {
            Route::resource('pumps', PumpController::class)->only(['index', 'create', 'store'])->names(['index' => 'pump.index', 'create' => 'pump.create', 'store' => 'pump.store']);
        });
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('pumps', PumpController::class)->only(['edit', 'update', 'destroy'])->names(['edit' => 'pump.edit', 'update' => 'pump.update', 'destroy' => 'pump.destroy']);
        });
    });

    Route::group(['prefix' => 'reports', 'middleware' => 'role:admin,accountant'], function () {
        Route::get('/consignor-consignee', [ReportController::class, 'consignor_consignee'])->name('reports.consignor-consignee');
        Route::get('/consignor-consignee-export', [ReportController::class, 'consignor_consignee_export'])->name('reports.consignor-consignee-export');
        Route::get('/tds', [ReportController::class, 'tds'])->name('reports.tds');
        Route::get('/tds-export', [ReportController::class, 'tds_export'])->name('reports.tds-export');
        Route::get('/dispatch', [ReportController::class, 'dispatch'])->name('reports.dispatch');
        Route::get('/dispatch-export', [ReportController::class, 'dispatch_export'])->name('reports.dispatch-export');
        Route::get('/party-ledger', [ReportController::class, 'party_ledger'])->name('reports.party-ledger');
        Route::get('/party-ledger-export', [ReportController::class, 'party_ledger_export'])->name('reports.party-ledger-export');
        Route::get('/lr-copy', [ReportController::class, 'lr_copy'])->name('reports.lr-copy');
        Route::get('/lr-copy-export', [ReportController::class, 'lr_copy_export'])->name('reports.lr-copy-export');
        Route::get('/invoice', [ReportController::class, 'invoice'])->name('reports.invoice');
        Route::get('/invoice-export', [ReportController::class, 'invoice_export'])->name('reports.invoice-export');
        Route::get('/shortage-damage', [ReportController::class, 'shortage_damage'])->name('reports.shortage-damage');
        Route::get('/shortage-damage-export', [ReportController::class, 'shortage_damage_export'])->name('reports.shortage-damage-export');
        Route::get('/eway-bill', [ReportController::class, 'eway_bill'])->name('reports.eway-bill');
        Route::get('/eway-bill-export', [ReportController::class, 'eway_bill_export'])->name('reports.eway-bill-export');

        Route::get('/pump', [ReportController::class, 'pump'])->name('reports.pump');
        Route::get('/pump-export', [ReportController::class, 'pump_export'])->name('reports.pump-export');
        Route::get('/party-bill', [ReportController::class, 'party_bill'])->name('reports.party-bill');
        Route::get('/party-bill-export', [ReportController::class, 'party_bill_export'])->name('reports.party-bill-export');
        Route::get('/ultratech-bill', [ReportController::class, 'ultratech_bill'])->name('reports.ultratech-bill');
        Route::get('/ultratech-bill-export', [ReportController::class, 'ultratech_bill_export'])->name('reports.ultratech-bill-export');
        Route::get('/memo-copy', [ReportController::class, 'memo_copy'])->name('reports.memo-copy');
        Route::get('/memo-copy-export', [ReportController::class, 'memo_copy_export'])->name('reports.memo-copy-export');
        Route::get('/pending-memo', [ReportController::class, 'pending_memo'])->name('reports.pending-memo');
        Route::get('/pending-memo-export', [ReportController::class, 'pendingMemoExport'])->name('reports.pending-memo-export');
        Route::get('/bank', [ReportController::class, 'bank'])->name('reports.bank');
        Route::get('/bank-export', [ReportController::class, 'bank_export'])->name('reports.bank-export');
        Route::get('/transporter-report', [ReportController::class, 'transporter_report'])->name('reports.transporter-report');
        Route::get('/transporter-report-export', [ReportController::class, 'transporter_report_export'])->name('reports.transporter-report-export');
        Route::get('/pending-invoice', [ReportController::class, 'pending_invoice'])->name('reports.pending-invoice');
        Route::get('/pending-invoice-export', [ReportController::class, 'pendingInvoiceExport'])->name('reports.pending-invoice-export');
        Route::get('/transporter-master', [ReportController::class, 'transporter_master'])->name('reports.transporter-master');
        Route::get('/transporter-master-export', [ReportController::class, 'transporter_master_export'])->name('reports.transporter-master-export');
    });
    Route::get('/voucher-receipt-export', [ReportController::class, 'voucher_receipt_export'])->name('reports.voucher-receipt-export');
});

require __DIR__ . '/api-routes.php';
require __DIR__ . '/auth.php';
