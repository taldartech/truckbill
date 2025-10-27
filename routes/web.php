<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConsigneeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\LorryReceiptController;
use App\Http\Controllers\FreightBookingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\VoucherPaymentController;
use App\Http\Controllers\TransporterBankAccountController;
use App\Http\Controllers\TransporterLedgerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\TruckOwnerController;
use App\Http\Controllers\TruckOwnerBankAccountController;
use App\Http\Controllers\AdvancePaymentController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BranchController;


// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
// Protected Routes
Route::middleware(['auth'])->group(function () {
  // API Routes
  Route::redirect('/', '/dashboard');
  Route::get('/api/transporters/{transporter}/trucks', [TruckController::class, 'getTrucksForTransporter']);
  Route::get('/api/trucks/{truck}/owner-transporters', [TruckController::class, 'getOwnerTransporters']);
  Route::get('/api/trucks/{truck}/agency-transporters', [TruckController::class, 'getAgencyTransporters']);
  Route::get('/api/trucks/{transporter}/lrs', [TruckController::class, 'getTrucksFromLrs']);
  Route::patch('/trucks/{truck}/remove-transporter', [TruckController::class, 'removeTransporter'])
  ->name('trucks.remove-transporter');
  Route::get('/api/drivers', [DriverController::class, 'getDriversApi']);
  Route::get('/api/lorry_receipts/{id}', [FreightBookingController::class, 'getLorryReceipt']);
  Route::get('/api/transporters/{id}', [FreightBookingController::class, 'getTransporter']);
  Route::get('/api/trucks/{id}', [FreightBookingController::class, 'getTruck']);
  Route::get('/api/truck-owners', [TransporterController::class, 'getTruckOwnersApi']);
  Route::get('/api/agencies', [TransporterController::class, 'getAgenciesApi']);
  Route::get('/api/consignees/{id}', [ConsigneeController::class, 'getConsigneeApi']);
  Route::post('/api/calculate-tds-percentage', [TransporterController::class, 'calculateTdsPercentage']);
  Route::post('/api/calculate-truck-owner-tds-percentage', [TruckOwnerController::class, 'calculateTruckOwnerTdsPercentage']);
  Route::post('/api/check-lr-number-uniqueness', [LorryReceiptController::class, 'checkLrNumberUniqueness']);
  Route::post('/api/check-invoice-number-uniqueness', [LorryReceiptController::class, 'checkInvoiceNumberUniqueness']);
  Route::post('/api/check-gr-number-uniqueness', [LorryReceiptController::class, 'checkGRNumberUniqueness']);
  Route::get('/api/dropdown/states/{countryId}', [\App\Http\Controllers\Api\DropdownController::class, 'states']);
  Route::get('/api/dropdown/cities/{stateId}', [\App\Http\Controllers\Api\DropdownController::class, 'cities']);
  Route::get('/api/lorry-receipts/{customerId}', [LorryReceiptController::class, 'getLorryReceiptsForCustomer']);
  Route::get('/api/invoice-numbers/{customerId}', [InvoiceController::class, 'getInvoiceNumbersForCustomer']);
  Route::get('/api/references/{transporterId}/{truckId}/lrs', [LorryReceiptController::class, 'getReferencesForTransporterTruck']);

  // City and State routes
  Route::get('/cities/{id}', [\App\Http\Controllers\CityController::class, 'show'])->name('cities.show');
  Route::get('/states/{id}', [\App\Http\Controllers\StateController::class, 'show'])->name('states.show');

  Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

  Route::get('/dashboard2', function () {
    return view('dashboard2');
  });

  Route::prefix('branches')->group(function () {
    Route::get('/', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/create', [BranchController::class, 'create'])->name('branches.create');
    Route::post('/store', [BranchController::class, 'store'])->name('branches.store');
    Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
    Route::put('/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::delete('/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
});

  // Company Registration
  Route::get('/company/create', [CompanyController::class, 'create'])->name('company.create');
  Route::post('/company', [CompanyController::class, 'store'])->name('company.store');
  Route::get('/company', [CompanyController::class, 'index'])->name('company.index');
  Route::get('/company/{company}/edit', [CompanyController::class, 'edit'])->name('company.edit');
  Route::put('/company/{company}', [CompanyController::class, 'update'])->name('company.update');
  Route::delete('/company/{company}', [CompanyController::class, 'destroy'])->name('company.destroy');

  // Consignee Management
  Route::get('/consignees/details/{id}', [ConsigneeController::class, 'getDetails'])->name('consignees.details');
  Route::get('/consignees', [ConsigneeController::class, 'index'])->name('consignees.index');
  Route::get('/consignees/create', [ConsigneeController::class, 'create'])->name('consignees.create');
  Route::post('/consignees', [ConsigneeController::class, 'store'])->name('consignees.store');
  Route::get('/consignees/{consignee}/edit', [ConsigneeController::class, 'edit'])->name('consignees.edit');
  Route::put('/consignees/{consignee}', [ConsigneeController::class, 'update'])->name('consignees.update');
  Route::delete('/consignees/{consignee}', [ConsigneeController::class, 'destroy'])->name('consignees.destroy');

  // User Management
  Route::get('/users', [UserController::class, 'index'])->name('users.index');
  Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
  Route::post('/users', [UserController::class, 'store'])->name('users.store');
  Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
  Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
  Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

  // Transporter Management
  Route::get('/transporters', [TransporterController::class, 'index'])->name('transporters.index');
  Route::get('/transporters/create', [TransporterController::class, 'create'])->name('transporters.create');
  Route::post('/transporters', [TransporterController::class, 'store'])->name('transporters.store');
  Route::get('/transporters/{transporter}/edit', [TransporterController::class, 'edit'])->name('transporters.edit');
  Route::put('/transporters/{transporter}', [TransporterController::class, 'update'])->name('transporters.update');
  Route::delete('/transporters/{transporter}', [TransporterController::class, 'destroy'])->name('transporters.destroy');
  Route::get('/transporter/{id}/tds-info', [LorryReceiptController::class, 'getTdsInfo']);


  //Truck Owner Management
  Route::get('/truck_owners', [TruckOwnerController::class, 'index'])->name('truck_owners.index');
  Route::get('/truck_owners/create', [TruckOwnerController::class, 'create'])->name('truck_owners.create');
  Route::post('/truck_owners', [TruckOwnerController::class, 'store'])->name('truck_owners.store');
  Route::get('/truck_owners/{id}', [TruckOwnerController::class, 'show'])->name('truck_owners.show');
  Route::get('/truck_owners/{transporter}/edit', [TruckOwnerController::class, 'edit'])->name('truck_owners.edit');
  Route::put('/truck_owners/{transporter}', [TruckOwnerController::class, 'update'])->name('truck_owners.update');
  Route::delete('/truck_owners/{transporter}', [TruckOwnerController::class, 'destroy'])->name('truck_owners.destroy');

  // Advance Payment Management
  Route::get('/advance-payments', [AdvancePaymentController::class, 'index'])->name('advance-payments.index');
  Route::get('/advance-payments/create', [AdvancePaymentController::class, 'create'])->name('advance-payments.create');
  Route::post('/advance-payments', [AdvancePaymentController::class, 'store'])->name('advance-payments.store');
  Route::get('/advance-payments/{advancePayment}/edit', [AdvancePaymentController::class, 'edit'])->name('advance-payments.edit');
  Route::put('/advance-payments/{advancePayment}', [AdvancePaymentController::class, 'update'])->name('advance-payments.update');
  Route::delete('/advance-payments/{advancePayment}', [AdvancePaymentController::class, 'destroy'])->name('advance-payments.destroy');

  // Truck Management
  Route::get('/trucks', [TruckController::class, 'index'])->name('trucks.index');
  Route::get('/trucks/create/{transporter_id}', [TruckController::class, 'create'])->name('trucks.create');
  Route::post('/trucks', [TruckController::class, 'store'])->name('trucks.store');
  Route::get('/trucks/{truck}', [TruckController::class, 'show'])->name('trucks.show');
  Route::get('/trucks/{truck}/edit', [TruckController::class, 'edit'])->name('trucks.edit');
  Route::put('/trucks/{truck}', [TruckController::class, 'update'])->name('trucks.update');
  Route::put('/trucks/{truck}/assign-driver', [TruckController::class, 'assignDriver'])->name('trucks.assign-driver');
  Route::delete('/trucks/{truck}', [TruckController::class, 'destroy'])->name('trucks.destroy');
  Route::post('/assign-owner', [TruckController::class, 'assignOwner'])->name('trucks.assign-owner');
  // Route::patch('/{truck}/remove-transporter', [TruckController::class, 'removeTransporter'])->name('trucks.remove-transporter');
  // Route::get('/check-truck-number', [TruckController::class, 'checkTruckNumber'])->name('check.truck.number');
  Route::get('/check-truck-number', function (Illuminate\Http\Request $request) {
    $exists = \App\Models\Truck::where('truck_number', $request->truck_number)->exists();
    return response()->json(['exists' => $exists]);
  });

  // Truck Reporting
  Route::get('/trucks/report', [TruckController::class, 'report'])->name('trucks.report');

  // Lorry Receipt CRUD
  Route::get('/lorry_receipts/data', [LorryReceiptController::class, 'getLorryReceiptsData'])->name('lorry_receipts.data');

  // Reports
  Route::get('lorry_receipts/create-linked', [LorryReceiptController::class, 'createLinked'])->name('lorry_receipts.createLinked');
  Route::resource('lorry_receipts', LorryReceiptController::class);
  Route::post('lorry_receipts/bulk-payment', [LorryReceiptController::class, 'bulkPayment'])->name('lorry_receipts.bulkPayment');
  Route::post('lorry_receipts/bulk-voucher-payment', [LorryReceiptController::class, 'bulkVoucherPayment'])->name('lorry_receipts.bulkVoucherPayment');
  Route::get('lorry_receipts/lrs-by-memo/{memo_id}', [LorryReceiptController::class, 'getLRsByMemo'])->name('lorry_receipts.lrsByMemo');
  Route::get('/api/lorry-receipts/{id}/details', [LorryReceiptController::class, 'details'])->name('lorry_receipts.details');
  Route::post('/lorry_receipts/update-status', [LorryReceiptController::class, 'updateDeliveryStatus'])->name('lorry_receipts.update-status');
  Route::get('/api/lorry-receipts/linked/{linkedLrId}', [LorryReceiptController::class, 'getLinkedLRs']);
  Route::get('/lorryreceipts/by-linked/{linkedId}', [App\Http\Controllers\LorryReceiptController::class, 'getByLinkedId'])
    ->name('lorryreceipts.by_linked');




  // Freight Bookings
  Route::post('/freight_bookings/store_combined_payment', [FreightBookingController::class, 'storeCombinedPayment'])->name('freight_bookings.store_combined_payment');
  Route::get('/freight_bookings/data', [FreightBookingController::class, 'getFreightBookingsData'])->name('freight_bookings.datatable');
  Route::post('/freight_bookings/confirm', [FreightBookingController::class, 'confirm'])->name('freight_bookings.confirm');
  Route::resource('freight_bookings', FreightBookingController::class);
  Route::get('/api/freight-bookings/{id}/details', [FreightBookingController::class, 'getFreightBookingDetails'])->name('api.freight_bookings.details');
  Route::post('/freight_bookings/bulk-action', [FreightBookingController::class, 'bulkAction'])->name('freight_bookings.bulkAction');
  Route::post('/freight_bookings/voucher-payment', [FreightBookingController::class, 'voucherPayment'])->name('freight_bookings.voucher_payment');
  Route::post('/freight_bookings/bulk-payment', [FreightBookingController::class, 'bulkPayment'])->name('freight_bookings.bulk_payment');
  Route::post('/freight_bookings/bulk-memo-id', [FreightBookingController::class, 'bulkMemoId'])->name('freight_bookings.bulk_memo_id');
  Route::post('/freight_bookings/payment-history', [FreightBookingController::class, 'getPaymentHistory'])->name('freight_bookings.payment_history');



  // Invoices
  Route::resource('invoices', InvoiceController::class);
  Route::get('/invoices/lrs/{memoIds}', [InvoiceController::class, 'getLrs'])->name('invoices.lrs');
  Route::get('/invoices/memos/{customerId}', [InvoiceController::class, 'getCustomerMemos']);
  Route::get('/invoices/all-memos/{customerId}', [InvoiceController::class, 'getAllCustomerMemos']);
  Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

  // Customers
  Route::get('/customers/details/{id}', [CustomerController::class, 'getDetails'])->name('customers.details');
  Route::resource('customers', CustomerController::class);

  // Vouchers
  Route::resource('vouchers', VoucherController::class);
  Route::get('vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
  Route::get('vouchers/{voucher}/payments-section', [VoucherController::class, 'paymentsSection']);

  // Voucher Payments
  Route::get('/voucher_payments/daily', [VoucherPaymentController::class, 'dailyPayments'])->name('voucher_payments.daily');
  Route::get('/voucher_payments/export/excel', [VoucherPaymentController::class, 'exportToExcel'])->name('voucher_payments.export.excel');
  Route::get('/voucher_payments/export/pdf', [VoucherPaymentController::class, 'exportToPdf'])->name('voucher_payments.export.pdf');
  Route::resource('voucher_payments', VoucherPaymentController::class);
  Route::post('voucher_payments/bulk-update', [VoucherPaymentController::class, 'bulkUpdateStatus'])->name('voucher_payments.bulkUpdateStatus');
  Route::post('voucher_payments/bulk-payment', [VoucherPaymentController::class, 'bulkPayment'])->name('voucher_payments.bulkPayment');
  Route::get('daily-payments', [VoucherPaymentController::class, 'dailyPayments'])->name('voucher_payments.daily');
  Route::put('voucher_payments/update-status', [VoucherPaymentController::class, 'updateStatus'])->name('voucher_payments.update_status');


  // Truck_Owner Bank Accounts
  Route::get('truck_owner_bank_accounts/index', [TruckOwnerBankAccountController::class, 'index'])->name('truck_owner_bank_accounts.index');
  Route::get('truck_owner_bank_accounts/create/{truck_owner_id}', [TruckOwnerBankAccountController::class, 'create'])->name('truck_owner_bank_accounts.create');
  Route::post('truck_owner_bank_accounts', [TruckOwnerBankAccountController::class, 'store'])->name('truck_owner_bank_accounts.store');
  Route::get('truck_owner_bank_accounts/{truck_owner_bank_account}/edit', [TruckOwnerBankAccountController::class, 'edit'])->name('truck_owner_bank_accounts.edit');
  Route::put('truck_owner_bank_accounts/{truck_owner_bank_account}', [TruckOwnerBankAccountController::class, 'update'])->name('truck_owner_bank_accounts.update');
  Route::delete('truck_owner_bank_accounts/{truck_owner_bank_account}', [TruckOwnerBankAccountController::class, 'destroy'])->name('truck_owner_bank_accounts.destroy');
  Route::put('truck_owner_bank_accounts/{transporter_id}/beneficiary-id', [TruckOwnerBankAccountController::class, 'updateBeneficiaryId'])->name('truck_owner_bank_accounts.updateBeneficiaryId');

  // Transporter Bank Accounts
  Route::get('transporter_bank_accounts/index', [TransporterBankAccountController::class, 'index'])->name('transporter_bank_accounts.index');
  Route::put('transporter_bank_accounts/{transporter_id}/beneficiary-id', [TransporterBankAccountController::class, 'updateBeneficiaryId'])->name('transporter_bank_accounts.updateBeneficiaryId');

  // Transporter Ledger
  Route::get('/transporter_ledger', [TransporterLedgerController::class, 'index'])->name('transporter_ledger.index');
  Route::get('transporter_ledger', [TransporterLedgerController::class, 'index'])->name('transporter_ledger.index');
  Route::get('transporter_ledger/create', [TransporterLedgerController::class, 'create'])->name('transporter_ledger.create');
  Route::post('transporter_ledger', [TransporterLedgerController::class, 'store'])->name('transporter_ledger.store');
  Route::get('transporter_ledger/{ledger}/edit', [TransporterLedgerController::class, 'edit'])->name('transporter_ledger.edit');
  Route::put('transporter_ledger/{ledger}', [TransporterLedgerController::class, 'update'])->name('transporter_ledger.update');
  Route::delete('transporter_ledger/{ledger}', [TransporterLedgerController::class, 'destroy'])->name('transporter_ledger.destroy');
  Route::get('transporter_ledger/balance/{transporter}', [TransporterLedgerController::class, 'transporterBalance'])->name('transporter_ledger.balance');

  // Driver CRUD
  Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
  Route::get('/drivers/data', [DriverController::class, 'getDriversData'])->name('drivers.data');
  Route::get('/drivers/create', [DriverController::class, 'create'])->name('drivers.create');
  Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
  Route::get('/drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
  Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
  Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');

  // Master Routes
  Route::prefix('master')->group(function () {
    // Countries
    Route::get('/countries', [\App\Http\Controllers\CountryController::class, 'index'])->name('master.countries.index');
    Route::get('/countries/create', [\App\Http\Controllers\CountryController::class, 'create'])->name('master.countries.create');
    Route::post('/countries', [\App\Http\Controllers\CountryController::class, 'store'])->name('master.countries.store');
    Route::get('/countries/{country}/edit', [\App\Http\Controllers\CountryController::class, 'edit'])->name('master.countries.edit');
    Route::put('/countries/{country}', [\App\Http\Controllers\CountryController::class, 'update'])->name('master.countries.update');
    Route::delete('/countries/{country}', [\App\Http\Controllers\CountryController::class, 'destroy'])->name('master.countries.destroy');

    // States
    Route::get('/states', [\App\Http\Controllers\StateController::class, 'index'])->name('master.states.index');
    Route::get('/states/create', [\App\Http\Controllers\StateController::class, 'create'])->name('master.states.create');
    Route::post('/states', [\App\Http\Controllers\StateController::class, 'store'])->name('master.states.store');
    Route::get('/states/{state}/edit', [\App\Http\Controllers\StateController::class, 'edit'])->name('master.states.edit');
    Route::put('/states/{state}', [\App\Http\Controllers\StateController::class, 'update'])->name('master.states.update');
    Route::delete('/states/{state}', [\App\Http\Controllers\StateController::class, 'destroy'])->name('master.states.destroy');

    // Cities
    Route::get('/cities', [\App\Http\Controllers\CityController::class, 'index'])->name('master.cities.index');
    Route::get('/cities/create', [\App\Http\Controllers\CityController::class, 'create'])->name('master.cities.create');
    Route::post('/cities', [\App\Http\Controllers\CityController::class, 'store'])->name('master.cities.store');
    Route::get('/cities/{city}/edit', [\App\Http\Controllers\CityController::class, 'edit'])->name('master.cities.edit');
    Route::put('/cities/{city}', [\App\Http\Controllers\CityController::class, 'update'])->name('master.cities.update');
    Route::delete('/cities/{city}', [\App\Http\Controllers\CityController::class, 'destroy'])->name('master.cities.destroy');

    // Goods Types
    Route::get('/goods-types', [\App\Http\Controllers\GoodsTypeController::class, 'index'])->name('master.goods-types.index');
    Route::get('/goods-types/create', [\App\Http\Controllers\GoodsTypeController::class, 'create'])->name('master.goods-types.create');
    Route::post('/goods-types', [\App\Http\Controllers\GoodsTypeController::class, 'store'])->name('master.goods-types.store');
    Route::get('/goods-types/{goodsType}/edit', [\App\Http\Controllers\GoodsTypeController::class, 'edit'])->name('master.goods-types.edit');
    Route::put('/goods-types/{goodsType}', [\App\Http\Controllers\GoodsTypeController::class, 'update'])->name('master.goods-types.update');
    Route::delete('/goods-types/{goodsType}', [\App\Http\Controllers\GoodsTypeController::class, 'destroy'])->name('master.goods-types.destroy');

    //Pumps
    // Route::resource('pumps', PumpController::class);
    Route::get('/pumps', [PumpController::class, 'index'])->name('master.pump.index');
    Route::get('/pumps/create', [PumpController::class, 'create'])->name('master.pump.create');
    Route::post('/pumps', [PumpController::class, 'store'])->name('master.pump.store');
    Route::get('/pumps/{pump}/edit', [PumpController::class, 'edit'])->name('master.pump.edit');
    Route::put('/pumps/{pump}', [PumpController::class, 'update'])->name('master.pump.update');
    Route::delete('/pumps/{pump}', [PumpController::class, 'destroy'])->name('master.pump.destroy');
  });




  // Transporters CRUD
  Route::get('/transporters', [TransporterController::class, 'index'])->name('transporters.index');
  Route::get('/transporters/create', [TransporterController::class, 'create'])->name('transporters.create');
  Route::post('/transporters', [TransporterController::class, 'store'])->name('transporters.store');
  Route::get('/transporters/{transporter}', [TransporterController::class, 'show'])->name('transporters.show');
  Route::get('/transporters/{transporter}/edit', [TransporterController::class, 'edit'])->name('transporters.edit');
  Route::put('/transporters/{transporter}', [TransporterController::class, 'update'])->name('transporters.update');
  Route::delete('/transporters/{transporter}', [TransporterController::class, 'destroy'])->name('transporters.destroy');

  //Advance_payments
  // // Route::resource('advance-payments', \App\Http\Controllers\AdvancePaymentController::class);
  // Route::resource('advance-payments', \App\Http\Controllers\AdvancePaymentController::class)->names([
  //     'index' => 'advance-payments.index',
  //     'create' => 'advance-payments.create',
  //     'store' => 'advance-payments.store',
  //     'edit' => 'advance-payments.edit',
  //     'update' => 'advance-payments.update',
  //     'destroy' => 'advance-payments.destroy',
  // ]);
  Route::get('advance-payments', [AdvancePaymentController::class, 'index'])
    ->name('advance-payments.index');
  Route::get('advance-payments/create/{transporter_id}', [AdvancePaymentController::class, 'create'])
    ->name('advance-payments.create');
  Route::post('advance-payments', [AdvancePaymentController::class, 'store'])
    ->name('advance-payments.store');
  Route::get('advance-payments/{advancePayment}/edit', [AdvancePaymentController::class, 'edit'])
    ->name('advance-payments.edit');
  Route::put('advance-payments/{advancePayment}', [AdvancePaymentController::class, 'update'])
    ->name('advance-payments.update');
  Route::delete('advance-payments/{advancePayment}', [AdvancePaymentController::class, 'destroy'])
    ->name('advance-payments.destroy');

  // Route::resource('advance-payments', AdvancePaymentController::class)->except(['create']);

  Route::get('/consignees/{id}', [ConsigneeController::class, 'getConsigneeDetails']);

  Route::get('/branches/by-company/{companyId}', [BranchController::class, 'getByCompany'])->name('branches.by-company');





  // API routes for dynamic dropdowns
  Route::get('/api/states/{countryId}', [\App\Http\Controllers\Api\DropdownController::class, 'states']);
  Route::get('/api/cities/{stateId}', [\App\Http\Controllers\Api\DropdownController::class, 'cities']);

  // Validation routes
  Route::post('/api/validate/gst-number', [\App\Http\Controllers\ValidationController::class, 'checkGstNumber']);
  Route::post('/api/validate/pan-number', [\App\Http\Controllers\ValidationController::class, 'checkPanNumber']);
  Route::get('/get-transporter-name/{truck}', function ($truckId) {
    $truck = \App\Models\Truck::with('transporter')->find($truckId);

    if ($truck && $truck->transporter) {
      return response()->json(['transporter_name' => $truck->transporter->name]);
    }

    return response()->json(['transporter_name' => null]);
  });

  Route::group(['prefix' => 'reports'], function () {
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
    Route::get('/pending-memo-export', [ReportController::class, 'pending_memo_export'])->name('reports.pending-memo-export');
      Route::get('/bank', [ReportController::class, 'bank'])->name('reports.bank');
      Route::get('/bank-export', [ReportController::class, 'bank_export'])->name('reports.bank-export');
      Route::get('/voucher-receipt-export', [ReportController::class, 'voucher_receipt_export'])->name('reports.voucher-receipt-export');
      Route::get('/transporter-report', [ReportController::class, 'transporter_report'])->name('reports.transporter-report');
      Route::get('/transporter-report-export', [ReportController::class, 'transporter_report_export'])->name('reports.transporter-report-export');
  });
});
