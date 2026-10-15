<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\AccountingVoucherController;
use App\Http\Controllers\AdvancePaymentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\ConsigneeController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CustomInvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\FreightBookingController;
use App\Http\Controllers\GoodsTypeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\LorryReceiptController;
use App\Http\Controllers\MasterPanelSummaryController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\Report\PartyLedgerReportController;
use App\Http\Controllers\Report\PartyPendingInvoiceReportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransporterBankAccountController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TransporterMasterController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\TruckOwnerBankAccountController;
use App\Http\Controllers\TruckOwnerController;
use App\Http\Controllers\TruckTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', function () {
    $content = config('app.block_search_indexing')
        ? "User-agent: *\nDisallow: /\n"
        : "User-agent: *\nDisallow:\n";

    return response($content, 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
});

// Protected Routes
Route::middleware(['auth.blocked'])->group(function () {
    Route::group(['middleware' => 'role:super_admin,master_admin'], function () {
        // User Management
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('master/summary-dashboard', [MasterPanelSummaryController::class, 'index'])
            ->middleware('role:master_admin')
            ->name('master.summary-dashboard');

        // Company Registration
        Route::get('/company/enter/{company}', [CompanyController::class, 'enter'])->name('company.enter');
        Route::get('/company/exit', [CompanyController::class, 'exit'])->name('company.exit');
        Route::get('company/{company}/invoice-template-preview', [CompanyController::class, 'invoiceTemplatePreview'])->name('company.invoice-template-preview');
        Route::get('company/{company}/lr-copy-template-preview', [CompanyController::class, 'lrCopyTemplatePreview'])->name('company.lr-copy-template-preview');
        Route::get('company/{company}/freight-challan-template-preview', [CompanyController::class, 'freightChallanTemplatePreview'])->name('company.freight-challan-template-preview');
        Route::get('invoice-template-demo', [CompanyController::class, 'invoiceTemplateDemo'])->name('invoice.template.demo');
        Route::get('lr-copy-template-demo', [CompanyController::class, 'lrCopyTemplateDemo'])->name('lr-copy.template.demo');
        Route::get('freight-challan-template-demo', [CompanyController::class, 'freightChallanTemplateDemo'])->name('freight-challan.template.demo');
        Route::resource('company', CompanyController::class);
    });
    // Master Admin only routes
    Route::group(['middleware' => 'role:master_admin'], function () {
        Route::patch('/company/{company}/subscription', [CompanyController::class, 'updateSubscription'])->name('company.subscription.update');
        Route::post('/company/{company}/block', [CompanyController::class, 'block'])->name('company.block');
        Route::post('/company/{company}/unblock', [CompanyController::class, 'unblock'])->name('company.unblock');
        Route::post('/users/{user}/block', [UserController::class, 'block'])->name('users.block');
        Route::post('/users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
        Route::resource('email_templates', EmailTemplateController::class)->except(['show', 'destroy']);
        Route::get('email_templates/{email_template}/preview', [EmailTemplateController::class, 'preview'])->name('email_templates.preview');
        Route::post('email_templates/{email_template}/send-test', [EmailTemplateController::class, 'sendTest'])->name('email_templates.send-test');
    });
    Route::middleware(['role:super_admin'])->group(function () {
        // Branch Management
        Route::resource('branches', BranchController::class)->except(['show']);

        // Company settings (voucher header, PDF templates)
        Route::get('company_settings', [CompanySettingsController::class, 'index'])->name('company_settings.index');
        Route::get('company_settings/{company}/edit', [CompanySettingsController::class, 'edit'])->name('company_settings.edit');
        Route::put('company_settings/{company}', [CompanySettingsController::class, 'update'])->name('company_settings.update');

        // Truck_Owner Bank Accounts
        Route::get('truck_owner_bank_accounts/index', [TruckOwnerBankAccountController::class, 'index'])->name('truck_owner_bank_accounts.index');
        Route::put('truck_owner_bank_accounts/{transporter_id}/beneficiary-id', [TruckOwnerBankAccountController::class, 'updateBeneficiaryId'])->name('truck_owner_bank_accounts.updateBeneficiaryId');

        // Transporter Bank Accounts
        Route::get('transporter_bank_accounts/index', [TransporterBankAccountController::class, 'index'])->name('transporter_bank_accounts.index');
        Route::put('transporter_bank_accounts/{transporter_id}/beneficiary-id', [TransporterBankAccountController::class, 'updateBeneficiaryId'])->name('transporter_bank_accounts.updateBeneficiaryId');

        Route::prefix('subscription')->name('subscription.')->group(function () {
            Route::get('/', [SubscriptionController::class, 'index'])->name('index');
            Route::get('/manage', [SubscriptionController::class, 'show'])->name('show');
            Route::get('/checkout', [SubscriptionController::class, 'checkoutForm'])->name('checkout.form');
            Route::get('/history', [SubscriptionController::class, 'history'])->name('history');
            Route::post('/quote', [SubscriptionController::class, 'quote'])->name('quote');
            Route::post('/promo/validate', [SubscriptionController::class, 'validatePromo'])->name('promo.validate');
            Route::post('/checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
            Route::post('/callback', [SubscriptionController::class, 'callback'])->name('callback');
        });
    });

    Route::redirect('/', '/dashboard');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth.blocked', 'verified'])
        ->name('dashboard');

    // Consignee Management
    Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::resource('consignees', ConsigneeController::class)->only(['index', 'create', 'store']);
        Route::post('/consignees/bulk-upload', [ConsigneeController::class, 'bulkUpload'])
        ->name('consignees.bulkUpload');

    Route::get('/consignees/bulk-upload-template', [ConsigneeController::class, 'bulkUploadTemplate'])
        ->name('consignees.bulkUploadTemplate');
    });
    Route::middleware(['role:admin,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::resource('consignees', ConsigneeController::class)->only(['edit', 'update', 'destroy']);
    });

    //// Transporters CRUD

Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {

    Route::get('/transporters/bulk-upload-template', [TransporterController::class, 'bulkUploadTemplate'])
        ->name('transporters.bulk-upload-template');

    Route::resource('transporters', TransporterController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('/transporters/bulk-upload', [TransporterController::class, 'bulkUpload'])
        ->name('transporters.bulk-upload');
});

Route::middleware(['role:admin,super_admin', 'company.context', 'subscription.active'])->group(function () {

    Route::resource('transporters', TransporterController::class)
        ->only(['edit', 'update', 'destroy']);

    Route::post('/transporters/{transporter}/refresh-tds', [TransporterController::class, 'refreshTds'])
        ->name('transporters.refresh-tds');
});

    // Truck Owner Management
   
    Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {

       Route::get('/truck_owners/bulk-upload-template', [TruckOwnerController::class, 'bulkUploadTemplate'])
        ->name('truck_owners.bulk-upload-template');
        Route::resource('truck_owners', TruckOwnerController::class)->only(['index', 'create', 'store', 'show']);
         Route::post('/truck_owners/bulk-upload', [TruckOwnerController::class, 'bulkUpload'])
        ->name('truck_owners.bulk-upload');
    });
    Route::middleware(['role:admin,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::resource('truck_owners', TruckOwnerController::class)->only(['edit', 'update', 'destroy']);
        Route::post('/truck_owners/{truck_owner}/refresh-tds', [TruckOwnerController::class, 'refreshTds'])->name('truck_owners.refresh-tds');
    });

    // Advance Payment Management
    Route::post('/advance-payments', [AdvancePaymentController::class, 'store'])->name('advance-payments.store')
        ->middleware(['role:admin,super_admin', 'company.context', 'subscription.active']);

    // Truck Management
    Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::get('/trucks', [TruckController::class, 'index'])->name('trucks.index');
        Route::get('/trucks/create/{transporter_id?}', [TruckController::class, 'create'])->name('trucks.create');
        Route::post('/trucks', [TruckController::class, 'store'])->name('trucks.store');
        Route::post('/trucks/bulk-upload', [TruckController::class, 'bulkUpload'])
        ->name('trucks.bulkUpload');

    Route::get('/trucks/bulk-upload/template', [TruckController::class, 'bulkUploadTemplate'])
        ->name('trucks.bulkUploadTemplate');
        Route::get('/trucks/{truck}', [TruckController::class, 'show'])->name('trucks.show');
    });
    Route::middleware(['role:admin,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::get('/trucks/{truck}/edit', [TruckController::class, 'edit'])->name('trucks.edit');
        Route::put('/trucks/{truck}', [TruckController::class, 'update'])->name('trucks.update');
        Route::put('/trucks/{truck}/assign-driver', [TruckController::class, 'assignDriver'])->name('trucks.assign-driver');
        Route::delete('/trucks/{truck}', [TruckController::class, 'destroy'])->name('trucks.destroy');
        Route::post('/assign-owner', [TruckController::class, 'assignOwner'])->name('trucks.assign-owner');
        Route::patch('/trucks/{truck}/remove-transporter', [TruckController::class, 'removeTransporter'])
            ->name('trucks.remove-transporter');
    });

    // Lorry Receipt CRUD
    Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::get('lorry_receipts/create-linked', [LorryReceiptController::class, 'createLinked'])->name('lorry_receipts.createLinked');
        Route::resource('lorry_receipts', LorryReceiptController::class)
            ->except(['edit', 'update', 'destroy', 'show']);
        Route::post('lorry_receipts/bulk-payment', [LorryReceiptController::class, 'bulkPayment'])->name('lorry_receipts.bulkPayment');
        Route::get('/lorryreceipts/by-linked/{linkedId}', [LorryReceiptController::class, 'getByLinkedId'])
            ->name('lorryreceipts.by_linked');
    });
    Route::middleware(['role:admin,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::get('/lorry_receipts/{lorry_receipt}/edit', [LorryReceiptController::class, 'edit'])->name('lorry_receipts.edit');
        Route::put('/lorry_receipts/{lorry_receipt}', [LorryReceiptController::class, 'update'])->name('lorry_receipts.update');
        Route::delete('/lorry_receipts/{lorry_receipt}', [LorryReceiptController::class, 'destroy'])->name('lorry_receipts.destroy');
    });

    // Freight Bookings
    Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::post('/freight_bookings/store_combined_payment', [FreightBookingController::class, 'storeCombinedPayment'])->name('freight_bookings.store_combined_payment');
        Route::get('/freight_bookings/data', [FreightBookingController::class, 'getFreightBookingsData'])->name('freight_bookings.datatable');
        Route::post('/freight_bookings/confirm', [FreightBookingController::class, 'confirm'])->name('freight_bookings.confirm');
        Route::resource('freight_bookings', FreightBookingController::class);
        Route::post('/freight_bookings/voucher-payment', [FreightBookingController::class, 'voucherPayment'])->name('freight_bookings.voucher_payment');
        Route::post('/freight_bookings/bulk-memo-id', [FreightBookingController::class, 'bulkMemoId'])->name('freight_bookings.bulk_memo_id');
        Route::post('/freight_bookings/payment-history', [FreightBookingController::class, 'getPaymentHistory'])->name('freight_bookings.payment_history');
    });

    // Invoices
    Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {
        // Register AJAX helpers before the resource so `/invoices/lrs` is not captured as `{invoice}`.
        Route::get('/invoices/lrs', [InvoiceController::class, 'getLrs'])->name('invoices.lrs');
        Route::get('/invoices/memos/{customerId}', [InvoiceController::class, 'getCustomerMemos']);
        Route::get('/invoices/all-memos/{customerId}', [InvoiceController::class, 'getAllCustomerMemos']);
        Route::get('/invoices/pending-memo/{customerId}', [InvoiceController::class, 'getPendingMemo']);
        Route::resource('invoices', InvoiceController::class)
            ->except(['edit', 'update', 'destroy']);
        Route::get('/custom-invoices/create', [CustomInvoiceController::class, 'create'])->name('custom-invoices.create');
        Route::post('/custom-invoices', [CustomInvoiceController::class, 'store'])->name('custom-invoices.store');
        Route::get('/custom-invoices/lr-by-number', [CustomInvoiceController::class, 'lrByNumber'])->name('custom-invoices.lr-by-number');
    });
    Route::middleware(['role:admin,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });
    Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
        Route::get('/invoices/{invoice}/payments', [InvoiceController::class, 'getPayments'])->name('invoices.payments.index');
    });

    // Voucher Payments
    // Admin: full CRUD + bulk actions
    Route::middleware(['role:admin,super_admin', 'company.context', 'subscription.active'])->group(function () {
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
    Route::middleware(['role:admin,moderator,super_admin', 'company.context', 'subscription.active'])->group(function () {
        Route::get('voucher_payments', [VoucherPaymentController::class, 'index'])->name('voucher_payments.index');
        Route::get('voucher_payments/{voucherNumber}', [VoucherPaymentController::class, 'show'])->name('voucher_payments.show');
        Route::get('daily-payments', [VoucherPaymentController::class, 'dailyPayments'])->name('voucher_payments.daily');
    });

    // Driver CRUD
    Route::get('/drivers/data', [DriverController::class, 'getDriversData'])->name('drivers.data');
    Route::post('/drivers/bulk-upload', [DriverController::class, 'bulkUpload'])
    ->name('drivers.bulkUpload');

Route::get('/drivers/bulk-upload-template', [DriverController::class, 'bulkUploadTemplate'])
    ->name('drivers.bulkUploadTemplate');
    Route::resource('drivers', DriverController::class)->except(['show']);

    // Master Routes
    Route::prefix('master')->name('master.')->group(function () {
        // Countries
        Route::middleware(['role:admin,moderator,super_admin,master_admin'])->group(function () {
            Route::resource('countries', CountryController::class)->only(['index', 'create', 'store']);
        });
        Route::middleware(['role:master_admin'])->group(function () {
            Route::resource('countries', CountryController::class)->only(['edit', 'update', 'destroy']);
        });

        // States
        Route::middleware(['role:admin,moderator,super_admin,master_admin'])->group(function () {
            Route::resource('states', StateController::class)->only(['index', 'create', 'store']);
        });
        Route::middleware(['role:master_admin'])->group(function () {
            Route::resource('states', StateController::class)->only(['edit', 'update', 'destroy']);
        });

        // Cities
        Route::middleware(['role:admin,moderator,super_admin,master_admin'])->group(function () {
            Route::resource('cities', CityController::class)->only(['index', 'create', 'store']);
        });
        Route::middleware(['role:master_admin'])->group(function () {
            Route::resource('cities', CityController::class)->only(['edit', 'update', 'destroy']);
        });

        // Goods Types
        Route::middleware(['role:admin,moderator,super_admin'])->group(function () {
            Route::resource('goods-types', GoodsTypeController::class)->only(['index', 'create', 'store']);

             Route::get('/goods-types/bulk-upload/template', [GoodsTypeController::class, 'bulkUploadTemplate'])
        ->name('goods-types.bulk-upload.template');
        Route::post('/goods-types/bulk-upload', [GoodsTypeController::class, 'bulkUpload'])
      ->name('goods-types.bulk-upload');
        });
       
        Route::middleware(['role:admin,super_admin'])->group(function () {
            Route::resource('goods-types', GoodsTypeController::class)->only(['edit', 'update', 'destroy']);
        });

        // Truck Types
        Route::middleware(['role:admin,moderator,super_admin'])->group(function () {
            Route::resource('truck-types', TruckTypeController::class)->only(['index', 'create', 'store']);
        });
        Route::middleware(['role:admin,super_admin'])->group(function () {
            Route::resource('truck-types', TruckTypeController::class)->only(['edit', 'update', 'destroy']);
        });

        // Pumps
        Route::middleware(['role:admin,moderator,super_admin'])->group(function () {
            Route::resource('pumps', PumpController::class)->only(['index', 'create', 'store'])->names(['index' => 'pump.index', 'create' => 'pump.create', 'store' => 'pump.store']);
              Route::get('/pumps/bulk-upload/template', [PumpController::class, 'bulkUploadTemplate'])
        ->name('pump.bulk-upload.template');

    Route::post('/pumps/bulk-upload', [PumpController::class, 'bulkUpload'])
        ->name('pump.bulk-upload');

        });
        Route::middleware(['role:admin,super_admin'])->group(function () {
            Route::resource('pumps', PumpController::class)->only(['edit', 'update', 'destroy'])->names(['edit' => 'pump.edit', 'update' => 'pump.update', 'destroy' => 'pump.destroy']);
        });

        Route::middleware(['role:master_admin'])->group(function () {
            Route::resource('transporter-masters', TransporterMasterController::class)->except(['show']);
        });
    });

    Route::group(['prefix' => 'reports', 'middleware' => ['role:admin,accountant,moderator,super_admin', 'company.context', 'subscription.active']], function () {
        Route::get('/consignor-consignee', [ReportController::class, 'consignor_consignee'])->name('reports.consignor-consignee');
        Route::get('/consignor-consignee-export', [ReportController::class, 'consignor_consignee_export'])->name('reports.consignor-consignee-export');
        Route::get('/tds', [ReportController::class, 'tds'])->name('reports.tds');
        Route::get('/tds-export', [ReportController::class, 'tds_export'])->name('reports.tds-export');
        Route::get('/dispatch', [ReportController::class, 'dispatch'])->name('reports.dispatch');
        Route::get('/dispatch-export', [ReportController::class, 'dispatch_export'])->name('reports.dispatch-export');
        Route::get('/party-ledger', [PartyLedgerReportController::class, 'index'])->name('reports.party-ledger');
        Route::get('/party-ledger-export', [PartyLedgerReportController::class, 'export'])->name('reports.party-ledger-export');
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
        Route::get('/customer-memos/{customerId}', [ReportController::class, 'customer_memos_json'])->name('reports.customer-memos');
        Route::get('/memo-copy', [ReportController::class, 'memo_copy'])->name('reports.memo-copy');
        Route::get('/memo-copy-export', [ReportController::class, 'memo_copy_export'])->name('reports.memo-copy-export');
        Route::get('/pending-memo', [ReportController::class, 'pending_memo'])->name('reports.pending-memo');
        Route::get('/pending-memo-export', [ReportController::class, 'pendingMemoExport'])->name('reports.pending-memo-export');
        Route::get('/bank', [ReportController::class, 'bank'])->name('reports.bank');
        Route::get('/bank-export', [ReportController::class, 'bank_export'])->name('reports.bank-export');
        Route::get('/cash', [ReportController::class, 'cash'])->name('reports.cash');
        Route::get('/cash-export', [ReportController::class, 'cash_export'])->name('reports.cash-export');
        Route::get('/transporter-report', [ReportController::class, 'transporter_report'])->name('reports.transporter-report');
        Route::get('/transporter-report-export', [ReportController::class, 'transporter_report_export'])->name('reports.transporter-report-export');
        Route::get('/profitability', [ReportController::class, 'profitability'])
           ->name('reports.profitability');
        Route::get('/profitability-export', [ReportController::class, 'profitability_export'])
         ->name('reports.profitability-export');
        Route::get('/pending-invoice', [ReportController::class, 'pending_invoice'])->name('reports.pending-invoice');
        Route::get('/pending-invoice-export', [ReportController::class, 'pendingInvoiceExport'])->name('reports.pending-invoice-export');
        Route::get('/party-pending-invoice', [PartyPendingInvoiceReportController::class, 'index'])->name('reports.party-pending-invoice');
        Route::get('/party-pending-invoice-export', [PartyPendingInvoiceReportController::class, 'export'])->name('reports.party-pending-invoice-export');
        Route::get('/transporter-master', [ReportController::class, 'transporter_master'])->name('reports.transporter-master');
        Route::get('/transporter-master-export', [ReportController::class, 'transporter_master_export'])->name('reports.transporter-master-export');
        Route::get('/voucher-receipt-export', [ReportController::class, 'voucher_receipt_export'])->name('reports.voucher-receipt-export');
        Route::post('/voucher-receipt-mark-printed', [ReportController::class, 'markVoucherReceiptPrinted'])->name('reports.voucher-receipt-mark-printed');
    });

    // Accounting & Tally Functionality
    Route::group(['prefix' => 'accounting', 'as' => 'accounting.', 'middleware' => ['role:admin,accountant,super_admin', 'company.context', 'subscription.active']], function () {
        Route::get('/day-book', [AccountingReportController::class, 'dayBook'])->name('day-book');
        Route::get('/ledger-statement/{ledgerId?}', [AccountingReportController::class, 'ledgerStatement'])->name('ledger-statement');
        Route::get('/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/profit-and-loss', [AccountingReportController::class, 'profitAndLoss'])->name('profit-and-loss');
        Route::get('/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/export-tally-masters', [AccountingReportController::class, 'exportTallyMasters'])->name('export-tally-masters');
        Route::get('/export-tally-vouchers', [AccountingReportController::class, 'exportTallyVouchers'])->name('export-tally-vouchers');
        Route::get('/gstr1', [AccountingReportController::class, 'gstr1'])->name('gstr1');
        Route::get('/debtors-aging', [AccountingReportController::class, 'debtorsAging'])->name('debtors-aging');
        Route::resource('vouchers', AccountingVoucherController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('/ledgers/{ledger}/pending-invoices', [AccountingVoucherController::class, 'getPendingInvoices'])->name('ledgers.pending-invoices');
        Route::resource('ledgers', LedgerController::class)->only(['index', 'store']);
    });

    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

require __DIR__.'/api-routes.php';
require __DIR__.'/auth.php';
