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

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // API Routes
    Route::get('/api/transporters/{transporter}/trucks', [TruckController::class, 'getTrucksForTransporter']);

    // City and State routes
    Route::get('/cities/{id}', [\App\Http\Controllers\CityController::class, 'show'])->name('cities.show');
    Route::get('/states/{id}', [\App\Http\Controllers\StateController::class, 'show'])->name('states.show');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::get('/dashboard2', function () {
        return view('dashboard2');
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

    // Truck Management
    Route::get('/trucks', [TruckController::class, 'index'])->name('trucks.index');
    Route::get('/trucks/create', [TruckController::class, 'create'])->name('trucks.create');
    Route::post('/trucks', [TruckController::class, 'store'])->name('trucks.store');
    Route::get('/trucks/{truck}/edit', [TruckController::class, 'edit'])->name('trucks.edit');
    Route::put('/trucks/{truck}', [TruckController::class, 'update'])->name('trucks.update');
    Route::delete('/trucks/{truck}', [TruckController::class, 'destroy'])->name('trucks.destroy');

    // Truck Reporting
    Route::get('/trucks/report', [TruckController::class, 'report'])->name('trucks.report');

    // Lorry Receipt CRUD
    Route::resource('lorry_receipts', LorryReceiptController::class);
    Route::post('/lorry_receipts/update-status', [LorryReceiptController::class, 'updateDeliveryStatus'])->name('lorry_receipts.update-status');

    // Freight Bookings
    Route::resource('freight_bookings', FreightBookingController::class);

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/memos/{customerId}', [InvoiceController::class, 'getCustomerMemos']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Customers
    Route::get('/customers/details/{id}', [CustomerController::class, 'getDetails'])->name('customers.details');
    Route::resource('customers', CustomerController::class);

    // Vouchers
    Route::resource('vouchers', VoucherController::class);
    Route::get('vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('vouchers/{voucher}/payments-section', [VoucherController::class, 'paymentsSection']);

    // Voucher Payments
    Route::resource('voucher_payments', VoucherPaymentController::class);

    // Transporter Bank Accounts
    Route::resource('transporter_bank_accounts', TransporterBankAccountController::class);
    Route::get('transporter_bank_accounts/create', [TransporterBankAccountController::class, 'create'])->name('transporter_bank_accounts.create');
    Route::get('transporter_bank_accounts/{transporter_bank_account}/edit', [TransporterBankAccountController::class, 'edit'])->name('transporter_bank_accounts.edit');

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
    });

    // Transporters CRUD
    Route::get('/transporters', [TransporterController::class, 'index'])->name('transporters.index');
    Route::get('/transporters/create', [TransporterController::class, 'create'])->name('transporters.create');
    Route::post('/transporters', [TransporterController::class, 'store'])->name('transporters.store');
    Route::get('/transporters/{transporter}/edit', [TransporterController::class, 'edit'])->name('transporters.edit');
    Route::put('/transporters/{transporter}', [TransporterController::class, 'update'])->name('transporters.update');
    Route::delete('/transporters/{transporter}', [TransporterController::class, 'destroy'])->name('transporters.destroy');

    // API routes for dynamic dropdowns
    Route::get('/api/states/{countryId}', [\App\Http\Controllers\Api\DropdownController::class, 'states']);
    Route::get('/api/cities/{stateId}', [\App\Http\Controllers\Api\DropdownController::class, 'cities']);
});
