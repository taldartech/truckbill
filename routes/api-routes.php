<?php

use App\Http\Controllers\ConsigneeController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FreightBookingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LorryReceiptController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\TruckOwnerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.blocked')->group(function () {
    Route::get('/api/transporters/{transporter}/trucks', [TruckController::class, 'getTrucksForTransporter']);
    Route::get('/api/trucks/{truck}/owner-transporters', [TruckController::class, 'getOwnerTransporters']);
    Route::get('/api/trucks/{truck}/agency-transporters', [TruckController::class, 'getAgencyTransporters']);
    Route::get('/api/trucks/{transporter}/lrs', [TruckController::class, 'getTrucksFromLrs']);
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
    Route::get('/api/invoice-numbers', [InvoiceController::class, 'getAllInvoiceNumbers']);
    Route::get('/api/invoice-numbers/{customerId}', [InvoiceController::class, 'getInvoiceNumbersForCustomer']);
    Route::get('/api/references/{transporterId}/{truckId}/lrs', [LorryReceiptController::class, 'getReferencesForTransporterTruck']);
    Route::get('/api/lorry-receipts/{id}/details', [LorryReceiptController::class, 'details'])->name('lorry_receipts.details');
    Route::get('/api/lorry-receipts/linked/{linkedLrId}', [LorryReceiptController::class, 'getLinkedLRs']);
    Route::get('/api/freight-bookings/{id}/details', [FreightBookingController::class, 'getFreightBookingDetails'])->name('api.freight_bookings.details');

    // API routes for dynamic dropdowns
    Route::get('/api/states/{countryId}', [\App\Http\Controllers\Api\DropdownController::class, 'states']);
    Route::get('/api/cities/{stateId}', [\App\Http\Controllers\Api\DropdownController::class, 'cities']);

    // Validation routes
    Route::post('/api/validate/gst-number', [\App\Http\Controllers\ValidationController::class, 'checkGstNumber']);
    Route::post('/api/validate/pan-number', [\App\Http\Controllers\ValidationController::class, 'checkPanNumber']);

    Route::post('/api/check-manual-invoice-number-uniqueness', [InvoiceController::class, 'checkManualInvoiceNumberUniqueness']);
    Route::get('/check-truck-number', function (Illuminate\Http\Request $request) {
        $exists = \App\Models\Truck::where('truck_number', $request->truck_number)->exists();
        return response()->json(['exists' => $exists]);
    });
    Route::get('/get-transporter-name/{truck}', function ($truckId) {
        $truck = \App\Models\Truck::with('transporter')->find($truckId);

        if ($truck && $truck->transporter) {
            return response()->json(['transporter_name' => $truck->transporter->name]);
        }

        return response()->json(['transporter_name' => null]);
    });
});
