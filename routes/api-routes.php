<?php

use App\Http\Controllers\Api\DropdownController;
use App\Http\Controllers\Api\Select2SearchController;
use App\Http\Controllers\ConsigneeController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FreightBookingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LorryReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\TruckOwnerController;
use App\Http\Controllers\ValidationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.blocked')->group(function () {
    Route::middleware('company.context')->group(function () {
        Route::get('/api/transporters/{transporter}/trucks', [TruckController::class, 'getTrucksForTransporter']);
        Route::get('/api/trucks/{truck}/owner-transporters', [TruckController::class, 'getOwnerTransporters']);
        Route::get('/api/drivers', [DriverController::class, 'getDriversApi']);
        Route::get('/api/drivers/search', [Select2SearchController::class, 'drivers']);
        Route::get('/api/transporters/search', [Select2SearchController::class, 'voucherTransporters']);
        Route::get('/api/transporters/{id}', [FreightBookingController::class, 'getTransporter']);
        Route::get('/api/trucks/search', [Select2SearchController::class, 'trucks']);
        Route::get('/api/trucks/{id}', [FreightBookingController::class, 'getTruck']);
        Route::get('/api/truck-owners', [TransporterController::class, 'getTruckOwnersApi']);
        Route::get('/api/truck-owners/search', [Select2SearchController::class, 'truckOwners']);
        Route::get('/api/agencies', [TransporterController::class, 'getAgenciesApi']);
        Route::get('/api/agencies/search', [Select2SearchController::class, 'agencies']);
        Route::get('/api/consignees/search', [Select2SearchController::class, 'consignees'])->name('api.consignees.search');
        Route::get('/api/consignees/{id}', [ConsigneeController::class, 'getConsigneeApi']);
        Route::get('/api/transporter-master-by-pan', [TransporterController::class, 'getTransporterMasterByPan']);
        Route::post('/api/calculate-tds-percentage', [TransporterController::class, 'calculateTdsPercentage']);
        Route::post('/api/calculate-truck-owner-tds-percentage', [TruckOwnerController::class, 'calculateTruckOwnerTdsPercentage']);
        Route::post('/api/check-lr-number-uniqueness', [LorryReceiptController::class, 'checkLrNumberUniqueness']);
        Route::post('/api/check-invoice-number-uniqueness', [LorryReceiptController::class, 'checkInvoiceNumberUniqueness']);
        Route::post('/api/check-gr-number-uniqueness', [LorryReceiptController::class, 'checkGRNumberUniqueness']);
        Route::get('/api/lorry-receipts/customer/{customerId}', [LorryReceiptController::class, 'getLorryReceiptsForCustomer']);
        Route::get('/api/invoice-numbers', [InvoiceController::class, 'getAllInvoiceNumbers'])->name('api.invoice-numbers');
        Route::get('/api/invoice-numbers/{customerId}', [InvoiceController::class, 'getInvoiceNumbersForCustomer'])->name('api.invoice-numbers.customer');
        Route::get('/api/reports/transporter-filter-options', [ReportController::class, 'transporter_report_filter_options']);
        Route::get('/api/lorry-receipts/{id}/details', [LorryReceiptController::class, 'details'])->name('lorry_receipts.details');
    });

    // API routes for dynamic dropdowns
    Route::get('/api/states/{countryId}', [DropdownController::class, 'states']);
    Route::get('/api/cities/search', [Select2SearchController::class, 'citiesSearch']);
    Route::get('/api/cities/{stateId}', [DropdownController::class, 'cities']);

    Route::middleware('company.context')->group(function () {
        // Validation routes
        Route::post('/api/validate/gst-number', [ValidationController::class, 'checkGstNumber']);
        Route::post('/api/validate/pan-number', [ValidationController::class, 'checkPanNumber']);

        Route::post('/api/check-manual-invoice-number-uniqueness', [InvoiceController::class, 'checkManualInvoiceNumberUniqueness']);
        Route::get('/check-truck-number', function (Illuminate\Http\Request $request) {
            $companyId = Auth::user()->userRole->name === 'super_admin'
                ? session('super_admin_company_id')
                : Auth::user()->company_id;

            $exists = \App\Models\Truck::where('truck_number', $request->truck_number)
                ->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereHas('transporter', function ($transporters) use ($companyId) {
                            $transporters->where('company_id', $companyId);
                        });
                })
                ->exists();

            return response()->json(['exists' => $exists]);
        });
        Route::get('/get-transporter-name/{truck}', function ($truckId) {
            $truck = \App\Models\Truck::with('transporter.transporterMaster')->find($truckId);

            if ($truck && $truck->transporter) {
                return response()->json(['transporter_name' => $truck->transporter->name]);
            }

            return response()->json(['transporter_name' => null]);
        });
    });
});
