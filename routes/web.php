<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

// Company Registration
use App\Http\Controllers\CompanyController;
Route::get('/company/create', [CompanyController::class, 'create'])->name('company.create');
Route::post('/company', [CompanyController::class, 'store'])->name('company.store');
Route::get('/company', [CompanyController::class, 'index'])->name('company.index');
Route::get('/company/{id}/edit', [CompanyController::class, 'edit'])->name('company.edit');
Route::put('/company/{id}', [CompanyController::class, 'update'])->name('company.update');
Route::delete('/company/{id}', [CompanyController::class, 'destroy'])->name('company.destroy');

// User Management
use App\Http\Controllers\UserController;
// Transporter Management
use App\Http\Controllers\TransporterController;
// Truck Management
use App\Http\Controllers\TruckController;
// Driver Management
use App\Http\Controllers\DriverController;
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

// Transporter CRUD
Route::get('/transporters', [TransporterController::class, 'index'])->name('transporters.index');
Route::get('/transporters/create', [TransporterController::class, 'create'])->name('transporters.create');
Route::post('/transporters', [TransporterController::class, 'store'])->name('transporters.store');
Route::get('/transporters/{transporter}/edit', [TransporterController::class, 'edit'])->name('transporters.edit');
Route::put('/transporters/{transporter}', [TransporterController::class, 'update'])->name('transporters.update');
Route::delete('/transporters/{transporter}', [TransporterController::class, 'destroy'])->name('transporters.destroy');

// Truck CRUD
Route::get('/trucks', [TruckController::class, 'index'])->name('trucks.index');
Route::get('/trucks/create', [TruckController::class, 'create'])->name('trucks.create');
Route::post('/trucks', [TruckController::class, 'store'])->name('trucks.store');
Route::get('/trucks/{truck}/edit', [TruckController::class, 'edit'])->name('trucks.edit');
Route::put('/trucks/{truck}', [TruckController::class, 'update'])->name('trucks.update');
Route::delete('/trucks/{truck}', [TruckController::class, 'destroy'])->name('trucks.destroy');

// Truck Reporting
Route::get('/trucks/report', [TruckController::class, 'report'])->name('trucks.report');

// Lorry Receipt CRUD
use App\Http\Controllers\LorryReceiptController;
Route::resource('lorry_receipts', LorryReceiptController::class);

// Freight Bookings
use App\Http\Controllers\FreightBookingController;
Route::resource('freight_bookings', FreightBookingController::class);

// Invoices
use App\Http\Controllers\InvoiceController;
Route::resource('invoices', InvoiceController::class);

// Customers
use App\Http\Controllers\CustomerController;
Route::resource('customers', CustomerController::class);

// Vouchers
use App\Http\Controllers\VoucherController;
Route::resource('vouchers', VoucherController::class);
// Show voucher details with payments
Route::get('vouchers/{voucher}', [App\Http\Controllers\VoucherController::class, 'show'])->name('vouchers.show');
// AJAX payments section for voucher
Route::get('vouchers/{voucher}/payments-section', [App\Http\Controllers\VoucherController::class, 'paymentsSection']);

// Voucher Payments
use App\Http\Controllers\VoucherPaymentController;
Route::resource('voucher_payments', VoucherPaymentController::class);

// Driver CRUD
Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
Route::get('/drivers/create', [DriverController::class, 'create'])->name('drivers.create');
Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
Route::get('/drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');
