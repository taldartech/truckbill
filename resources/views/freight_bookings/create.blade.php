@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="alert alert-success mt-2">
            {{ session('success') }}
        </div>
    @endif

    <style>
        #tr_amount.tr-amount-exceeds-lr {
            background-color: #fff3cd;
        }
    </style>
        .form-control,
        .form-select {
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
            <div class="card-title h5">Good Receipt (Bilty) Information</div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>LR Number:</strong><br>
                        <span
                            class="text-primary">{{ $lorry_receipt->first() ? $lorry_receipt->first()->lr_number : 'N/A' }}</span>
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
                        @if ($lorry_receipt->first() && $lorry_receipt->first()->transporter)
                            @if ($lorry_receipt->first()->transporter->is_owner)
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
                        <span
                            class="badge bg-warning text-dark fs-6">{{ $lorry_receipt->first() && $lorry_receipt->first()->transporter ? number_format($lorry_receipt->first()->transporter->tds_percentage ?? 0, 2) : '0.00' }}%</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>Truck:</strong><br>
                        <span>{{ $lorry_receipt->first() && $lorry_receipt->first()->truck ? $lorry_receipt->first()->truck->truck_number : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>Reference By:</strong><br>
                        <span>{{ $lorry_receipt->first() && $lorry_receipt->first()->reference_by ? $lorry_receipt->first()->reference->name : 'N/A' }}</span>
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
                        <span>{{ $lorry_receipt->first() ? $lorry_receipt->first()->good_type ?? 'N/A' : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>Weight:</strong><br>
                        <span
                            class="text-info fw-bold">{{ $lorry_receipt->first() ? number_format($lorry_receipt->first()->weight ?? 0, 3) : '0.00' }}
                            MT</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>No. of Units:</strong><br>
                        <span>{{ $lorry_receipt->first() ? $lorry_receipt->first()->no_of_units ?? 'N/A' : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>Amount:</strong><br>
                        <span
                            class="badge bg-brand-primary text-white fs-6">{{ $lorry_receipt->first() ? number_format($lorry_receipt->first()->amount ?? 0, 2) : '0.00' }}</span>
                    </div>

                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>Rate:</strong><br>
                        <span
                        @php
                            $rate = $lorry_receipt->first() ? $lorry_receipt->first()->fr_rate : 0;
                        @endphp
                            class="badge bg-brand-primary text-white fs-6">{{ number_format($rate, 2) }}</span>
                    </div>
                    </div>
                @if ($lorry_receipt->first() && $lorry_receipt->first()->freight_booking_id)
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
                        <span>{{ $lorry_receipt->first() ? $lorry_receipt->first()->source_city ?? 'N/A' : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Source State:</strong><br>
                        <span>{{ $lorry_receipt->first() ? $lorry_receipt->first()->source_state ?? 'N/A' : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Destination City:</strong><br>
                        <span>{{ $lorry_receipt->first() ? $lorry_receipt->first()->destination_city ?? 'N/A' : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Destination State:</strong><br>
                        <span>{{ $lorry_receipt->first() ? $lorry_receipt->first()->destination_state ?? 'N/A' : 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
        <div class="card-header">
            <div class="card-title h4 d-flex justify-content-between align-items-center w-100">
                <span>Add New Good Hire (Lorry Hire)</span>
                @php
                    $cancelledBookings = \App\Models\FreightBooking::where('is_cancelled', 1)
                        ->where('lr_id', $lorry_receipt->first() ? $lorry_receipt->first()->id : 0)
                        ->orderBy('id', 'desc')
                        ->first();
                @endphp
                @if ($cancelledBookings)
                    <button class="btn btn-warning" type="button" data-bs-toggle="modal"
                        data-bs-target="#cancelBookingModal">
                        View Cancelled Booking
                    </button>
                @endif
            </div>
        </div>
        <!--begin::Form-->
        @php
            $weight = $weight ?? 0; // default fallback
        @endphp
        <form method="POST" action="{{ route('freight_bookings.confirm') }}" class="needs-validation" novalidate="">
            @csrf
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            <div class="card-body">
                <!-- Hidden inputs for form submission -->
                <input type="hidden" name="lr_id" id="lr_id"
                    value="{{ isset($preFilled) && isset($preFilled['lr_id']) ? $preFilled['lr_id'] : old('lr_id') }}"
                    required>
                <input type="hidden" name="transporter_id"
                    value="{{ isset($preFilled) && isset($preFilled['transporter_id']) ? $preFilled['transporter_id'] : '' }}">
                <input type="hidden" name="truck_id"
                    value="{{ isset($preFilled) && isset($preFilled['truck_id']) ? $preFilled['truck_id'] : '' }}">
                <input type="hidden" name="good_type"
                    value="{{ isset($preFilled) && isset($preFilled['goods_type']) ? $preFilled['goods_type'] : '' }}">
                @error('lr_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
                @error('transporter_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
                @error('truck_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
                @error('good_type')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
                <!-- Financial Information Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                        <input type="number" step="0.01" min="1" max="20000" class="form-control"
                            name="tr_fr_rate" id="tr_fr_rate" value="{{ old('tr_fr_rate') }}"
                            placeholder="Enter freight rate (1 - 20,000 per ton)" required>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Rate must be between 1 and 20,000 per ton
                        </small>
                        @error('tr_fr_rate')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="tr_amount" class="form-label" id="tr_amount_label">TR Amount *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="tr_amount"
                            id="tr_amount" value="{{ old('tr_amount') }}" placeholder="Auto-calculated (editable)"
                            required>
                        <div id="tr_amount_lr_warning" class="form-text text-warning fw-semibold d-none">
                            <i class="fas fa-exclamation-triangle"></i> TR Amount exceeds LR Amount.
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Auto-calculated: TR Freight Rate × Weight
                            ({{ $lorry_receipt->first() ? number_format($lorry_receipt->first()->weight ?? 0, 3) : '0.00' }}
                            MT). Must be greater than 0.
                        </small>
                        @error('tr_amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" value="1" id="is_fixed_amount"
                                name="is_fixed_amount" {{ old('is_fixed_amount') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_fixed_amount">
                                Use fixed amount (do not auto-calculate)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="tds_amount" class="form-label">TDS Amount *</label>
                        <input type="number" step="0.01" class="form-control" name="tds_amount" id="tds_amount"
                            value="{{ old('tds_amount') }}" readonly style="background-color: #f8f9fa;">
                        <small class="form-text text-muted">
                            <i class="fas fa-percentage"></i> Auto-calculated based on transporter TDS percentage
                        </small>
                        @error('tds_amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Hidden advance amount field with default value 0 -->
                    <input type="hidden" name="advance_amount" id="advance_amount" value="0">
                </div>

                <div class="row mb-3">

                </div>

                <!-- Hidden weight input (if needed for JS) -->
                <input type="hidden" id="weight" value="{{ $weight }}">
                <input type="hidden" id="lr_amount" value="{{ $lorry_receipt->first() ? ($lorry_receipt->first()->amount ?? 0) : 0 }}">
                <!-- Hidden transporter TDS percentage for JS calculations -->
                <input type="hidden" id="transporter_tds_percentage" name="transporter_tds_percentage"
                    value="{{ $lorry_receipt->first() && $lorry_receipt->first()->transporter ? $lorry_receipt->first()->transporter->tds_percentage : 0 }}">
                <!-- Additional Information Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="eway_bill_number" class="form-label">E-Way Bill Number</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="eway_bill_number" id="eway_bill_number"
                                value="{{ old('eway_bill_number') }}" placeholder="Enter E-way bill number">
                            <a href="https://ewaybillgst.gov.in/Login.aspx" target="_blank" class="btn btn-info"
                                title="Check E-way Bill Status">
                                <i class="fas fa-external-link-alt"></i> Check Status
                            </a>
                        </div>
                        @error('eway_bill_number')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="eway_bill_expiry" class="form-label">E-Way Bill Expiry Date</label>
                        <input type="date" class="form-control" name="eway_bill_expiry" id="eway_bill_expiry"
                            value="{{ old('eway_bill_expiry') }}">
                        @error('eway_bill_expiry')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="pan_verified" class="form-label">PAN Verified *</label>
                        <input type="hidden" name="pan_verified" id="pan_verified"
                            value="{{ isset($preFilled['pan_verified']) ? (int) $preFilled['pan_verified'] : (old('pan_verified') ? 1 : 0) }}"
                            required>
                        <input type="text" class="form-control" readonly
                            value="{{ isset($preFilled['pan_verified']) && $preFilled['pan_verified'] ? 'Yes' : 'No' }}"
                            style="background-color: #f8f9fa;">
                        <small class="form-text text-muted">
                            <i class="fas fa-lock"></i> Read-only field from transporter data
                        </small>
                        @error('pan_verified')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="balance_amount" class="form-label">Balance Amount *</label>
                        <input type="number" step="0.01" class="form-control" name="balance_amount"
                            id="balance_amount" value="{{ old('balance_amount') }}" required readonly
                            style="background-color: #f8f9fa;">
                        <small class="form-text text-muted">
                            <i class="fas fa-calculator"></i> Auto-calculated: TR Amount - TDS Amount
                        </small>
                        @error('balance_amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Hidden delivered field with default value 0 -->
                    <input type="hidden" name="is_delivered" id="is_delivered" value="0">
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="remark" class="form-label">Remark</label>
                        <textarea name="remark" id="remark" class="form-control" rows="3" placeholder="Enter any remarks">{{ old('remark') }}</textarea>
                        @error('remark')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Hidden cancelled field with default value 0 -->
                    <input type="hidden" name="is_cancelled" id="is_cancelled" value="0">
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('freight_bookings.index') }}" class="btn btn-back">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
                <div>
                    <button class="btn btn-brand-primary me-2" type="submit" name="action" value="save">
                        <i class="bi bi-check-circle"></i> Continue to Review
                    </button>

                </div>
            </div>
            <input type="hidden" name="redirect_to_payment" value="0">
        </form>
    </div>
    @if ($cancelledBookings)
        <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel"
            aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="cancelBookingModalLabel">
                            <i class="fas fa-info-circle me-2"></i> Cancelled Good Hire (Lorry Hire) Details
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>TR Freight Rate:</strong> {{ number_format($cancelledBookings->tr_fr_rate, 2) }}</p>
                        <p><strong>TR Amount:</strong> {{ number_format($cancelledBookings->tr_amount, 2) }}</p>
                        <p><strong>TDS Amount:</strong> {{ number_format($cancelledBookings->tds_amount, 2) }}</p>
                        <p><strong>Balance Amount:</strong> {{ number_format($cancelledBookings->balance_amount, 2) }}</p>
                        <p><strong>Advance Amount:</strong> {{ number_format($cancelledBookings->advance_amount, 2) }}</p>
                        <p><strong>E-Way Bill Number:</strong> {{ $cancelledBookings->eway_bill_number ?? 'N/A' }}</p>
                        <p><strong>E-Way Bill Expiry Date:</strong>
                            {{ $cancelledBookings->eway_bill_expiry ? \Carbon\Carbon::parse($cancelledBookings->eway_bill_expiry)->format('d M Y') : 'N/A' }}
                        </p>
                        <p><strong>Remark:</strong> {{ $cancelledBookings->remark ?? 'N/A' }}</p>
                        <p><strong>Cancelled On:</strong> {{ $cancelledBookings->updated_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weightElement = document.getElementById('weight');
            const weight = weightElement ? parseFloat(weightElement.value || 0) : 0;
            const rateInput = document.getElementById('tr_fr_rate');
            const amountInput = document.getElementById('tr_amount');
            const trAmountInput = document.getElementById('tr_amount');
            const isFixedAmountInput = document.getElementById('is_fixed_amount');
            const advanceAmountInput = document.getElementById('advance_amount');
            const balanceAmountInput = document.getElementById('balance_amount');
            const panVerifiedElement = document.getElementById('pan_verified');
            const panVerified = panVerifiedElement ? parseInt(panVerifiedElement.value || 0) : 0;
            const tdsAmountInput = document.getElementById('tds_amount');

            // Get TDS percentage from transporter (from Lorry Receipt)
            const tdsPercentage = parseFloat(
                "{{ $lorry_receipt->first() && $lorry_receipt->first()->transporter ? $lorry_receipt->first()->transporter->tds_percentage : 0 }}"
            ); // already in %

            function roundUpTdsAmount(amount) {
                return Math.ceil(amount);
            }

            function updateTrAmountHighlight() {
                const lrAmountElement = document.getElementById('lr_amount');
                const trAmountField = document.getElementById('tr_amount');
                const trAmountLabel = document.getElementById('tr_amount_label');
                const warning = document.getElementById('tr_amount_lr_warning');

                if (!lrAmountElement || !trAmountField) {
                    return;
                }

                const lrAmount = parseFloat(lrAmountElement.value) || 0;
                const trAmount = parseFloat(trAmountField.value) || 0;
                const exceeds = lrAmount > 0 && trAmount > lrAmount;

                trAmountField.classList.toggle('tr-amount-exceeds-lr', exceeds);
                trAmountLabel?.classList.toggle('tr-amount-exceeds-lr', exceeds);
                warning?.classList.toggle('d-none', !exceeds);
            }

            // Function to calculate TDS and Balance amounts
            function calculateTdsAndBalance() {
                const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;

                // Calculate TDS Amount based on TR Amount (always round up)
                const tdsAmount = roundUpTdsAmount(trAmount * (tdsPercentage / 100)).toFixed(0);
                if (tdsAmountInput) {
                    tdsAmountInput.value = tdsAmount;
                }

                // Calculate Balance Amount (TR Amount - TDS Amount)
                const balance = trAmount - parseFloat(tdsAmount);
                if (balanceAmountInput) {
                    balanceAmountInput.value = balance.toFixed(2);
                }

                updateTrAmountHighlight();
            }

            // Add null checks for event listeners
            if (rateInput) {
                rateInput.addEventListener('input', function() {
                    const rate = parseFloat(rateInput.value || 0);
                    if (!isFixedAmountInput || !isFixedAmountInput.checked) {
                        const amount = (weight * rate).toFixed(2);
                        if (amountInput) {
                            amountInput.value = amount;
                        }
                    }

                    // Recalculate TDS and Balance when TR Amount changes
                    calculateTdsAndBalance();
                });
            }

            if (isFixedAmountInput) {
                isFixedAmountInput.addEventListener('change', function() {
                    // When switching from fixed to auto, immediately recalculate amount from weight × rate
                    if (!isFixedAmountInput.checked && rateInput && amountInput) {
                        const rate = parseFloat(rateInput.value || 0);
                        const amount = (weight * rate).toFixed(2);
                        amountInput.value = amount;
                        calculateTdsAndBalance();
                    }
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
        });
    </script>

    <script>
        const IS_OWNER = {{ (int) ($preFilled['is_owner'] ?? 0) }};

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

            function roundUpTdsAmount(amount) {
                return Math.ceil(amount);
            }

            function updateTrAmountHighlight() {
                const lrAmountElement = document.getElementById('lr_amount');
                const trAmountField = document.getElementById('tr_amount');
                const trAmountLabel = document.getElementById('tr_amount_label');
                const warning = document.getElementById('tr_amount_lr_warning');

                if (!lrAmountElement || !trAmountField) {
                    return;
                }

                const lrAmount = parseFloat(lrAmountElement.value) || 0;
                const trAmount = parseFloat(trAmountField.value) || 0;
                const exceeds = lrAmount > 0 && trAmount > lrAmount;

                trAmountField.classList.toggle('tr-amount-exceeds-lr', exceeds);
                trAmountLabel?.classList.toggle('tr-amount-exceeds-lr', exceeds);
                warning?.classList.toggle('d-none', !exceeds);
            }

            // Function to calculate TDS and Balance amounts
            function calculateTdsAndBalance() {
                const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;

                // Calculate TDS Amount based on TR Amount (always round up)
                const tdsAmount = roundUpTdsAmount(trAmount * (tdsPercentage / 100)).toFixed(0);
                if (tdsAmountInput) {
                    tdsAmountInput.value = tdsAmount;
                }

                // Calculate Balance Amount (TR Amount - TDS Amount)
                const balance = trAmount - parseFloat(tdsAmount);
                if (balanceAmountInput) {
                    balanceAmountInput.value = balance.toFixed(2);
                }

                updateTrAmountHighlight();
            }

            // Add null checks for event listeners
            if (rateInput) {
                rateInput.addEventListener('input', function() {
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
            // Update trucks when transporter changes
            if (transporterSelect) {
                transporterSelect.addEventListener('change', updateTrucks);
            }

        });
    </script>
@endsection
