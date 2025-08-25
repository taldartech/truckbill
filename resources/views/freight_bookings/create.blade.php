@extends('layouts.app')

@section('content')
<style>
/* Prevent form expansion on validation errors */
.form-control, .form-select {
    min-height: 38px;
    box-sizing: border-box;
}

.text-danger {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    word-wrap: break-word;
}

/* Ensure consistent column widths */
.row .col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

/* Prevent card from expanding */
.card {
    overflow: hidden;
}

/* Consistent spacing for form groups */
.mb-3 {
    margin-bottom: 1rem !important;
}
</style>

<!-- LR Information Card -->
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title h5">Lorry Receipt Information</div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>LR Number:</strong><br>
                    <span class="text-primary">{{ $lorry_receipt->first() ? $lorry_receipt->first()->lr_number : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>Transporter:</strong><br>
                    <span>{{ $lorry_receipt->first() && $lorry_receipt->first()->transporter ? $lorry_receipt->first()->transporter->name : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>Transporter Type:</strong><br>
                    @if($lorry_receipt->first() && $lorry_receipt->first()->transporter)
                        @if($lorry_receipt->first()->transporter->is_owner)
                            <span class="badge bg-primary text-white fs-6">Truck Owner</span>
                        @else
                            <span class="badge bg-info text-white fs-6">Agency</span>
                        @endif
                    @else
                        <span>N/A</span>
                    @endif
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>TDS Percentage:</strong><br>
                    <span class="badge bg-warning text-dark fs-6">{{ $lorry_receipt->first() && $lorry_receipt->first()->transporter ? number_format($lorry_receipt->first()->transporter->tds_percentage ?? 0, 2) : '0.00' }}%</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>Truck:</strong><br>
                    <span>{{ $lorry_receipt->first() && $lorry_receipt->first()->truck ? $lorry_receipt->first()->truck->truck_number : 'N/A' }}</span>
                </div>
            </div>
            
        </div>
        <div class="row">
        <div class="col-md-4">
                <div class="mb-2">
                    <strong>Customer:</strong><br>
                    <span>{{ $lorry_receipt->first() && $lorry_receipt->first()->customer ? $lorry_receipt->first()->customer->name : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-2">
                    <strong>Consignor:</strong><br>
                    <span>{{ $lorry_receipt->first() && $lorry_receipt->first()->consignor ? $lorry_receipt->first()->consignor->name : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-2">
                    <strong>Consignee:</strong><br>
                    <span>{{ $lorry_receipt->first() && $lorry_receipt->first()->consignee ? $lorry_receipt->first()->consignee->name : 'N/A' }}</span>
                </div>
            </div>
            
        </div>
        
        <!-- Goods Type Details Section -->
        <div class="row mt-3">
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>Goods Type:</strong><br>
                    <span>{{ $lorry_receipt->first() ? ($lorry_receipt->first()->good_type ?? 'N/A') : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>Weight:</strong><br>
                    <span class="text-info fw-bold">{{ $lorry_receipt->first() ? number_format($lorry_receipt->first()->weight ?? 0, 2) : '0.00' }} kg</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-2">
                    <strong>No. of Units:</strong><br>
                    <span>{{ $lorry_receipt->first() ? ($lorry_receipt->first()->no_of_units ?? 'N/A') : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-2">
                    <strong>Amount:</strong><br>
                    <span class="badge bg-success text-white fs-6">₹{{ $lorry_receipt->first() ? number_format($lorry_receipt->first()->amount ?? 0, 2) : '0.00' }}</span>
                </div>
            </div>
            @if($lorry_receipt->first() && $lorry_receipt->first()->freight_booking_id)
            <div class="col-md-3">
                <div class="alert alert-danger mb-0">
                    <strong>Old Freight Number:</strong> {{ $lorry_receipt->first()->freight_booking_id }}
                </div>
            </div>
            @endif
        </div>
        
        <!-- Route Details Section -->
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="mb-2">
                    <strong>Source City:</strong><br>
                    <span>{{ $lorry_receipt->first() ? ($lorry_receipt->first()->source_city ?? 'N/A') : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-2">
                    <strong>Source State:</strong><br>
                    <span>{{ $lorry_receipt->first() ? ($lorry_receipt->first()->source_state ?? 'N/A') : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-2">
                    <strong>Destination City:</strong><br>
                    <span>{{ $lorry_receipt->first() ? ($lorry_receipt->first()->destination_city ?? 'N/A') : 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-2">
                    <strong>Destination State:</strong><br>
                    <span>{{ $lorry_receipt->first() ? ($lorry_receipt->first()->destination_state ?? 'N/A') : 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $lrs = \App\Models\LorryReceipt::all();
    $transporters = \App\Models\Transporter::where('company_id', auth()->user()->company_id)->get();
@endphp


<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="card-title h4">Add New Freight Booking</div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
    @php
    $weight = $weight ?? 0; // default fallback
@endphp
    <form method="POST" action="{{ route('freight_bookings.confirm') }}" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        <!--begin::Body-->
        <div class="card-body">
            <!-- Hidden inputs for form submission -->
            <input type="hidden" name="lr_id" id="lr_id" value="{{ isset($preFilled) && isset($preFilled['lr_id']) ? $preFilled['lr_id'] : old('lr_id') }}" required>
            <input type="hidden" name="transporter_id" value="{{ isset($preFilled) && isset($preFilled['transporter_id']) ? $preFilled['transporter_id'] : '' }}">
            <input type="hidden" name="truck_id" value="{{ isset($preFilled) && isset($preFilled['truck_id']) ? $preFilled['truck_id'] : '' }}">
            <input type="hidden" name="good_type" value="{{ isset($preFilled) && isset($preFilled['goods_type']) ? $preFilled['goods_type'] : '' }}">
            @error('lr_id') <div class="text-danger">{{ $message }}</div> @enderror
            @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
            @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
            @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
            <!-- Financial Information Section -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                    <input type="number" step="0.01" min="1" max="20000" class="form-control" name="tr_fr_rate" id="tr_fr_rate" value="{{ old('tr_fr_rate') }}" placeholder="Enter freight rate (₹1 - ₹20,000 per ton)" required>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Rate must be between ₹1 and ₹20,000 per ton
                    </small>
                    @error('tr_fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="tr_amount" class="form-label">TR Amount *</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="tr_amount" id="tr_amount" value="{{ old('tr_amount') }}" placeholder="Auto-calculated (editable)" required>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Auto-calculated: TR Freight Rate × Weight ({{ $lorry_receipt->first() ? number_format($lorry_receipt->first()->weight ?? 0, 2) : '0.00' }} kg). Must be greater than ₹0.
                    </small>
                    @error('tr_amount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="tds_amount" class="form-label">TDS Amount *</label>
                    <input type="number" step="0.01" class="form-control" name="tds_amount" id="tds_amount" value="{{ old('tds_amount') }}" readonly style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">
                        <i class="fas fa-percentage"></i> Auto-calculated based on transporter TDS percentage
                    </small>
                    @error('tds_amount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!-- Hidden advance amount field with default value 0 -->
                <input type="hidden" name="advance_amount" id="advance_amount" value="0">
            </div>

            <div class="row mb-3">
              
               
            </div>

            <!-- Hidden weight input (if needed for JS) -->
            <input type="hidden" id="weight" value="{{ $weight }}">
            <!-- Hidden transporter TDS percentage for JS calculations -->
            <input type="hidden" id="transporter_tds_percentage" name="transporter_tds_percentage" value="{{ $lorry_receipt->first() && $lorry_receipt->first()->transporter ? $lorry_receipt->first()->transporter->tds_percentage : 0 }}">
            <!-- Additional Information Section -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="eway_bill_number" class="form-label">E-Way Bill Number</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="eway_bill_number" id="eway_bill_number" value="{{ old('eway_bill_number') }}" placeholder="Enter E-way bill number">
                        <a href="https://ewaybillgst.gov.in/Login.aspx" target="_blank" class="btn btn-outline-info" title="Check E-way Bill Status">
                            <i class="fas fa-external-link-alt"></i> Check Status
                        </a>
                    </div>
                    @error('eway_bill_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="pan_verified" class="form-label">PAN Verified *</label>
                    <input type="hidden" name="pan_verified" id="pan_verified" value="{{ isset($preFilled['pan_verified']) ? (int)$preFilled['pan_verified'] : (old('pan_verified') ? 1 : 0) }}" required>
                    <input type="text" class="form-control" readonly value="{{ isset($preFilled['pan_verified']) && $preFilled['pan_verified'] ? 'Yes' : 'No' }}" style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">
                        <i class="fas fa-lock"></i> Read-only field from transporter data
                    </small>
                    @error('pan_verified') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="balance_amount" class="form-label">Balance Amount *</label>
                    <input type="number" step="0.01" class="form-control" name="balance_amount" id="balance_amount" value="{{ old('balance_amount') }}" required readonly style="background-color: #f8f9fa;">
                    <small class="form-text text-muted">
                        <i class="fas fa-calculator"></i> Auto-calculated: TR Amount - TDS Amount
                    </small>
                    @error('balance_amount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!-- Hidden delivered field with default value 0 -->
                <input type="hidden" name="is_delivered" id="is_delivered" value="0">
            </div>
    
    
        <div class="row">
            {{-- <div class="col-md-4 mb-3">
                <label for="deviation" class="form-label">Deviation</label>
                <input type="number" step="0.01" class="form-control" name="deviation" id="deviation" value="{{ old('deviation') }}">
                @error('deviation') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="misc_expense" class="form-label">Misc. Expense</label>
                <input type="number" step="0.01" class="form-control" name="misc_expense" id="misc_expense" value="{{ old('misc_expense') }}">
                @error('misc_expense') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="damage_qty" class="form-label">Damage Quantity</label>
                <input type="number" step="0.01" class="form-control" name="damage_qty" id="damage_qty" value="{{ old('damage_qty') }}">
                @error('damage_qty') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="damage_rate" class="form-label">Damage Rate</label>
                <input type="number" step="0.01" class="form-control" name="damage_rate" id="damage_rate" value="{{ old('damage_rate') }}">
                @error('damage_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="damage_amount" class="form-label">Damage Amount</label>
                <input type="number" step="0.01" class="form-control" name="damage_amount" id="damage_amount" value="{{ old('damage_amount') }}">
                @error('damage_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_qty" class="form-label">Shortage Quantity</label>
                <input type="number" step="0.01" class="form-control" name="shortage_qty" id="shortage_qty" value="{{ old('shortage_qty') }}">
                @error('shortage_qty') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_rate" class="form-label">Shortage Rate</label>
                <input type="number" step="0.01" class="form-control" name="shortage_rate" id="shortage_rate" value="{{ old('shortage_rate') }}">
                @error('shortage_rate') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_amount" class="form-label">Shortage Amount</label>
                <input type="number" step="0.01" class="form-control" name="shortage_amount" id="shortage_amount" value="{{ old('shortage_amount') }}">
                @error('shortage_amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div> --}}
            
        </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="remark" class="form-label">Remark</label>
                    <textarea name="remark" id="remark" class="form-control" rows="3" placeholder="Enter any remarks">{{ old('remark') }}</textarea>
                    @error('remark') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!-- Hidden cancelled field with default value 0 -->
                <input type="hidden" name="is_cancelled" id="is_cancelled" value="0">
            </div>
        {{-- <div class="row"> --}}
            {{-- <div class="col-md-6 mb-3">
                <label for="linked_freight_booking_id" class="form-label">Linked Freight Booking</label>
                <select name="linked_freight_booking_id" id="linked_freight_booking_id" class="form-select">
                    <option value="">None</option>
                    @foreach($linkedBookings as $fb)
                        <option value="{{ $fb->id }}" {{ old('linked_freight_booking_id') == $fb->id ? 'selected' : '' }}>
                            #{{ $fb->id }} (LR: {{ $fb->lr ? $fb->lr->gr_number : '-' }})
                        </option>
                    @endforeach
                </select>
                @error('linked_freight_booking_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div> --}}
        {{-- </div> --}}
        <!--end::Row-->
        </div>
        <!--end::Body-->
        <!--begin::Footer-->
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('freight_bookings.index') }}" class="btn btn-outline-dark">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <div>
                <button class="btn btn-success me-2" type="submit" name="action" value="save">
                    <i class="bi bi-check-circle"></i> Continue to Review
                </button>

            </div>
        </div>
        <!--end::Footer-->
        <input type="hidden" name="redirect_to_payment" value="0">
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const weightElement = document.getElementById('weight');
        const weight = weightElement ? parseFloat(weightElement.value || 0) : 0;
        const rateInput = document.getElementById('tr_fr_rate');
        const amountInput = document.getElementById('tr_amount');
        const trAmountInput = document.getElementById('tr_amount');
        const advanceAmountInput = document.getElementById('advance_amount');
        const balanceAmountInput = document.getElementById('balance_amount');
        const panVerifiedElement = document.getElementById('pan_verified');
        const panVerified = panVerifiedElement ? parseInt(panVerifiedElement.value || 0) : 0;
        const tdsAmountInput = document.getElementById('tds_amount');

          // Get TDS percentage from transporter (from Lorry Receipt)
    const tdsPercentage = parseFloat("{{ $lorry_receipt->first() && $lorry_receipt->first()->transporter ? $lorry_receipt->first()->transporter->tds_percentage : 0 }}"); // already in %

        // Function to calculate TDS and Balance amounts
        function calculateTdsAndBalance() {
            const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;
            
            // Calculate TDS Amount based on TR Amount
            const tdsAmount = (trAmount * (tdsPercentage / 100)).toFixed(2);
            if (tdsAmountInput) {
                tdsAmountInput.value = tdsAmount;
            }
            
            // Calculate Balance Amount (TR Amount - TDS Amount)
            const balance = trAmount - parseFloat(tdsAmount);
            if (balanceAmountInput) {
                balanceAmountInput.value = balance.toFixed(2);
            }
        }

        // Add null checks for event listeners
        if (rateInput) {
            rateInput.addEventListener('input', function () {
                const rate = parseFloat(rateInput.value || 0);
                const amount = (weight * rate).toFixed(2);
                if (amountInput) {
                    amountInput.value = amount;
                }
                
                // Recalculate TDS and Balance when TR Amount changes
                calculateTdsAndBalance();
            });
        }

        // Update TDS and balance when TR Amount changes (manual input)
        if (trAmountInput) {
            trAmountInput.addEventListener('input', calculateTdsAndBalance);
        }
        
        // Update balance when Advance Amount changes
        if (advanceAmountInput) {
            advanceAmountInput.addEventListener('input', calculateTdsAndBalance);
        }

        // Calculate initial TDS and balance if values exist
        calculateTdsAndBalance();

        // Existing code for LR selection
        const lrSelect = document.getElementById('lr_id');
        const transporterSelect = document.getElementById('transporter_id');
        const truckSelect = document.getElementById('truck_id');

        // Function to update trucks based on selected transporter
        function updateTrucks() {
            const selectedTransporter = transporterSelect.value;
            if (!selectedTransporter) {
                truckSelect.innerHTML = '<option value="">Select Truck</option>';
                return;
            }

            // Make AJAX request to get trucks for selected transporter
            fetch(`/api/transporters/${selectedTransporter}/trucks`)
                .then(response => response.json())
                .then(trucks => {
                    let options = '<option value="">Select Truck</option>';
                    trucks.forEach(truck => {
                        options += `<option value="${truck.id}">${truck.truck_number}</option>`;
                    });
                    truckSelect.innerHTML = options;
                })
                .catch(error => console.error('Error:', error));
        }

        // Function to load LR details
        function loadLRDetails() {
            const selectedLR = lrSelect.value;
            if (!selectedLR) {
                document.getElementById('transporter_id').value = '';
                document.getElementById('truck_id').value = '';
                return;
            }

            // Make AJAX request to get LR details
            fetch(`/api/lorry_receipts/${selectedLR}`)
                .then(response => response.json())
                .then(lr => {
                    // Set transporter
                    const transporterSelect = document.getElementById('transporter_id');
                    const truckSelect = document.getElementById('truck_id');
                    
                    // Get transporter name
                    fetch(`/api/transporters/${lr.transporter_id}`)
                        .then(response => response.json())
                        .then(transporter => {
                            transporterSelect.value = `${transporter.name}`;
                        });
                    
                    // Get truck number
                    fetch(`/api/trucks/${lr.truck_id}`)
                        .then(response => response.json())
                        .then(truck => {
                            truckSelect.value = `${truck.truck_number}`;
                        });
                })
                .catch(error => console.error('Error:', error));
        }
        });
    });
</script>

<script>
    const IS_OWNER = {{ $preFilled['is_owner'] ?? 0 }};
    
    document.addEventListener('DOMContentLoaded', function() {
        // Get all required elements with null checks
        const weightElement = document.getElementById('weight');
        const weight = weightElement ? parseFloat(weightElement.value || 0) : 0;
        const rateInput = document.getElementById('tr_fr_rate');
        const amountInput = document.getElementById('tr_amount');
        const trAmountInput = document.getElementById('tr_amount');
        const advanceAmountInput = document.getElementById('advance_amount');
        const balanceAmountInput = document.getElementById('balance_amount');
        const panVerifiedElement = document.getElementById('pan_verified');
        const panVerified = panVerifiedElement ? parseInt(panVerifiedElement.value || 0) : 0;
        const tdsAmountInput = document.getElementById('tds_amount');

        // Get TDS percentage from freight booking's transporter_tds_percentage field
        const tdsPercentageElement = document.getElementById('transporter_tds_percentage');
        const tdsPercentage = tdsPercentageElement ? parseFloat(tdsPercentageElement.value || 0) : 0;

        // Function to calculate TDS and Balance amounts
        function calculateTdsAndBalance() {
            const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;
            
            // Calculate TDS Amount based on TR Amount
            const tdsAmount = (trAmount * (tdsPercentage / 100)).toFixed(2);
            if (tdsAmountInput) {
                tdsAmountInput.value = tdsAmount;
            }
            
            // Calculate Balance Amount (TR Amount - TDS Amount)
            const balance = trAmount - parseFloat(tdsAmount);
            if (balanceAmountInput) {
                balanceAmountInput.value = balance.toFixed(2);
            }
        }

        // Add null checks for event listeners
        if (rateInput) {
            rateInput.addEventListener('input', function () {
                const rate = parseFloat(rateInput.value || 0);
                const amount = (weight * rate).toFixed(2);
                if (amountInput) {
                    amountInput.value = amount;
                }
                
                // Recalculate TDS and Balance when TR Amount changes
                calculateTdsAndBalance();
            });
        }

        // Update TDS and balance when TR Amount changes (manual input)
        if (trAmountInput) {
            trAmountInput.addEventListener('input', calculateTdsAndBalance);
        }
        
        // Update balance when Advance Amount changes
        if (advanceAmountInput) {
            advanceAmountInput.addEventListener('input', calculateTdsAndBalance);
        }

        // Calculate initial TDS and balance if values exist
        calculateTdsAndBalance();

        // Existing code for LR selection
        const lrSelect = document.getElementById('lr_id');
        const transporterSelect = document.getElementById('transporter_id');
        const truckSelect = document.getElementById('truck_id');

        // Function to update trucks based on selected transporter
        function updateTrucks() {
            const selectedTransporter = transporterSelect ? transporterSelect.value : '';
            if (!selectedTransporter || !truckSelect) {
                if (truckSelect) {
                    truckSelect.innerHTML = '<option value="">Select Truck</option>';
                }
                return;
            }

            // Make AJAX request to get trucks for selected transporter
            fetch(`/api/transporters/${selectedTransporter}/trucks`)
                .then(response => response.json())
                .then(trucks => {
                    let options = '<option value="">Select Truck</option>';
                    trucks.forEach(truck => {
                        options += `<option value="${truck.id}">${truck.truck_number}</option>`;
                    });
                    truckSelect.innerHTML = options;
                })
                .catch(error => console.error('Error:', error));
        }

        // Function to load LR details
        function loadLRDetails() {
            const selectedLR = lrSelect ? lrSelect.value : '';
            if (!selectedLR) {
                const transporterInput = document.getElementById('transporter_id');
                const truckInput = document.getElementById('truck_id');
                if (transporterInput) transporterInput.value = '';
                if (truckInput) truckInput.value = '';
                return;
            }

            // Make AJAX request to get LR details
            fetch(`/api/lorry_receipts/${selectedLR}`)
                .then(response => response.json())
                .then(lr => {
                    // Set transporter
                    const transporterSelect = document.getElementById('transporter_id');
                    const truckSelect = document.getElementById('truck_id');
                    
                    // Get transporter name
                    if (lr.transporter_id) {
                        fetch(`/api/transporters/${lr.transporter_id}`)
                            .then(response => response.json())
                            .then(transporter => {
                                if (transporterSelect) {
                                    transporterSelect.value = `${transporter.name}`;
                                }
                            })
                            .catch(error => console.error('Error fetching transporter:', error));
                    }
                    
                    // Get truck number
                    if (lr.truck_id) {
                        fetch(`/api/trucks/${lr.truck_id}`)
                            .then(response => response.json())
                            .then(truck => {
                                if (truckSelect) {
                                    truckSelect.value = `${truck.truck_number}`;
                                }
                            })
                            .catch(error => console.error('Error fetching truck:', error));
                    }
                })
                .catch(error => console.error('Error fetching LR details:', error));
        }

        // Update trucks when transporter changes
        if (transporterSelect) {
            transporterSelect.addEventListener('change', updateTrucks);
        }
        
        // Load LR details when LR changes
        if (lrSelect) {
            lrSelect.addEventListener('change', loadLRDetails);
        }
    });
</script>
@endsection
