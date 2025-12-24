@extends('layouts.app')

@section('content')
    <style>
        /* Prevent form expansion on validation errors */
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

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <!-- LR and Freight Booking Information Card -->
    <div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
        <div class="card-header">
            <div class="card-title h5">Lorry Receipt & Freight Booking Information</div>
        </div>
        <div class="card-body">
            <!-- LR Information Section -->
            <h6 class="border-bottom pb-2 mb-3">Lorry Receipt Details</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>LR Number:</strong><br>
                        <span class="text-primary">{{ $lorry_receipt->first()->lr_number }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Transporter:</strong><br>
                        <span>{{ $lorry_receipt->first()->transporter->name }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Truck:</strong><br>
                        <span>{{ $lorry_receipt->first()->truck->truck_number }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Customer:</strong><br>
                        <span>{{ $lorry_receipt->first()->customer->name }}</span>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Consignor:</strong><br>
                        <span>{{ $lorry_receipt->first()->consignor->name }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Consignee:</strong><br>
                        <span>{{ $lorry_receipt->first()->consignee->name }}</span>
                    </div>
                </div>
            </div>

            <!-- Freight Booking Information Section -->
            @if ($freightBooking)
                <h6 class="border-bottom pb-2 mb-3 mt-4">Freight Booking Details</h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-2">
                            <strong>FR Number:</strong><br>
                            <span class="text-primary">{{ $freightBooking->id }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <strong>Voucher Number:</strong><br>
                            <span>{{ $freightBooking->voucher_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <strong>TR Amount:</strong><br>
                            <span>₹{{ number_format($freightBooking->tr_amount, 2) }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <strong>Advance Amount:</strong><br>
                            <span>₹{{ number_format($freightBooking->advance_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <div class="mb-2">
                            <strong>Balance Amount:</strong><br>
                            <span class="text-success fw-bold">₹{{ number_format($balanceAmount, 2) }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <strong>Status:</strong><br>
                            <span>
                                @if ($freightBooking->is_delivered)
                                    <span class="badge bg-success">Delivered</span>
                                @else
                                    <span class="badge bg-warning">Not Delivered</span>
                                @endif
                                @if ($freightBooking->is_cancelled)
                                    <span class="badge bg-danger ms-1">Cancelled</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    @if ($freightBooking->transporter)
                        <div class="col-md-3">
                            <div class="mb-2">
                                <strong>Transporter Type:</strong><br>
                                <span>
                                    @if ($freightBooking->transporter->is_owner)
                                        <span class="badge bg-success"><i class="bi bi-truck me-1"></i> Truck Owner</span>
                                    @else
                                        <span class="badge bg-info"><i class="bi bi-building me-1"></i> Agency</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
    <div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
        <!--begin::Header-->
        <div class="card-header">
            <div class="card-title h4">Edit Voucher Payment</div>
        </div>
        <!--end::Header-->
        <!--begin::Form-->
        <form method="POST" action="{{ route('voucher_payments.update', $voucher_payment->id) }}" class="needs-validation"
            novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">

            @if (isset($freight_booking_id))
                <input type="hidden" name="freight_booking_id" value="{{ $freight_booking_id }}">
            @endif
            <!--begin::Body-->
            <div class="card-body">
                <!--begin::Row-->
                <div class="row mb-3">
                    <!--begin::Col-->
                    <div class="col-md-4 mb-3">
                        <label for="voucher_number" class="form-label">Voucher *</label>
                        <input type="text" name="voucher_number" id="voucher_number" class="form-control"
                            value="{{ $voucher_payment->voucher_number }}" readonly required>
                        @error('voucher_number')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter a voucher number.</div>
                    </div>
                    <!--end::Col-->

                    <!--begin::Col-->
                    <div class="col-md-4 mb-3">
                        <label for="payment_type_id" class="form-label">Payment Type *</label>
                        @if ($voucher_payment->linked_voucher_id == null)
                            <select name="payment_type_id" id="payment_type_id" class="form-select" required>
                                <option value="">Select Payment Type</option>
                                @foreach ($paymentTypes as $paymentType)
                                    <option value="{{ $paymentType->id }}"
                                        {{ old('payment_type_id', $voucher_payment->payment_type_id) == $paymentType->id ? 'selected' : '' }}>
                                        {{ $paymentType->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="payment_type" id="payment_type"
                                value="{{ old('payment_type', $voucher_payment->payment_type) }}">
                        @else
                            <input type="text" class="form-control" value="{{ $voucher_payment->payment_type }}"
                                readonly>
                            <input type="hidden" name="payment_type_id" value="{{ $voucher_payment->payment_type_id }}">
                            <input type="hidden" name="payment_type" value="{{ $voucher_payment->payment_type }}">
                        @endif
                        @error('payment_type_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a payment type.</div>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-4 mb-3">
                        <label for="payment_mode_id" class="form-label">Payment Mode *</label>
                        @if ($voucher_payment->linked_voucher_id == null)
                            <select name="payment_mode_id" id="payment_mode_id" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                                @foreach ($paymentModes as $paymentMode)
                                    <option value="{{ $paymentMode->id }}"
                                        {{ old('payment_mode_id', $voucher_payment->payment_mode_id) == $paymentMode->id ? 'selected' : '' }}>
                                        {{ $paymentMode->name }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="hidden" name="payment_mode" id="payment_mode"
                                value="{{ old('payment_mode', $voucher_payment->payment_mode) }}">
                        @else
                            <input type="text" class="form-control" value="{{ $voucher_payment->payment_mode }}"
                                readonly>
                            <input type="hidden" name="payment_mode_id"
                                value="{{ $voucher_payment->payment_mode_id }}">
                            <input type="hidden" name="payment_mode" value="{{ $voucher_payment->payment_mode }}">
                        @endif
                        @error('payment_mode_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a payment mode.</div>
                    </div>
                    <!--end::Col-->
                    <div class="col-md-4 mb-3" id="pumpDropdownContainer"
                        style="display: {{ $voucher_payment->pump_id || (request()->has('show_pump') && request('show_pump') == '1') ? 'block' : 'none' }};">
                        <label for="pump_id" class="form-label">Select Pump</label>
                        @if ($voucher_payment->linked_voucher_id == null)
                            <select name="pump_id" id="pump_id" class="form-select">
                                <option value="">Select Pump</option>
                                @foreach ($pumps as $pump)
                                    <option value="{{ $pump->id }}"
                                        {{ old('pump_id', $voucher_payment->pump_id) == $pump->id ? 'selected' : '' }}>
                                        {{ $pump->pump_name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <select name="pump_id" id="pump_id" class="form-select" disabled>
                                <option value="">Select Pump</option>
                                @foreach ($pumps as $pump)
                                    <option value="{{ $pump->id }}"
                                        {{ old('pump_id', $voucher_payment->pump_id) == $pump->id ? 'selected' : '' }}>
                                        {{ $pump->pump_name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="pump_id" value="{{ $voucher_payment->pump_id }}">
                        @endif
                        @error('pump_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--begin::Col-->
                    {{-- <div class="col-md-6">
                    <label for="transaction_id" class="form-label">Transaction ID *</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" value="{{ old('transaction_id', $voucher_payment->transaction_id) }}" required>
                    @error('transaction_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a transaction ID.</div>
                </div> --}}
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-6 mb-3">
                        <label for="remark" class="form-label">Remark</label>
                        <textarea {{ $voucher_payment->linked_voucher_id != null ? 'readonly' : '' }} name="remark" id="remark"
                            class="form-control" rows="3">{{ old('remark', str_replace('<br>', "\n", $voucher_payment->remark)) }}</textarea>
                        @error('remark')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <!-- Hidden payment_status for operators -->

                    @php
                        $isAdmin = auth()->user()->userRole && auth()->user()->userRole->name === 'admin';
                    @endphp
                    <div class="col-md-4 mb-3" @if (!$isAdmin) style="display: none;" @endif>
                        <label for="payment_status" class="form-label">Payment Status *</label>
                        @if ($voucher_payment->linked_voucher_id == null)
                            <select name="payment_status" id="payment_status" class="form-select" required>
                                <option value="pending"
                                    {{ old('payment_status', $voucher_payment->payment_status) == 'pending' ? 'selected' : '' }}>
                                    Pending</option>
                                <option value="in-progress"
                                    {{ old('payment_status', $voucher_payment->payment_status) == 'in-progress' ? 'selected' : '' }}>
                                    In-Progress</option>
                                <option value="paid" id="paidOption"
                                    {{ old('payment_status', $voucher_payment->payment_status) == 'paid' ? 'selected' : '' }}>
                                    Paid</option>
                            </select>
                        @else
                            <input type="text" class="form-control"
                                value="{{ ucfirst($voucher_payment->payment_status) }}" readonly>
                            <input type="hidden" name="payment_status" value="{{ $voucher_payment->payment_status }}">
                        @endif
                        @error('payment_status')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a payment status.</div>
                    </div>
                    <!--end::Col-->
                    {{-- <!--begin::Col-->
                <div class="col-md-6">
                    <label for="transaction_id" class="form-label">Transaction ID *</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" value="{{ old('transaction_id', $voucher_payment->transaction_id) }}" required>
                    @error('transaction_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a transaction ID.</div>
                </div> --}}
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-4 mb-3">
                        <label for="amount" class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control"
                                value="{{ old('amount', $voucher_payment->amount) }}" required>
                        </div>
                        @error('amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter an amount.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date" class="form-label">Date *</label>
                        <input type="date" name="date" id="date" class="form-control"
                            value="{{ old('date', $voucher_payment->date) }}" required
                            {{ $voucher_payment->linked_voucher_id != null ? 'readonly' : '' }}>
                        @error('date')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a date.</div>
                    </div>

                    <!--end::Col-->
                    <div class="col-md-4 mb-3" id="reference_transaction_box" style="display: none;">
                        <label for="reference_transaction_id" class="form-label">Reference Transaction ID *</label>
                        <input type="text" class="form-control" id="reference_transaction_id"
                            name="reference_transaction_id"
                            value="{{ old('reference_transaction_id', $voucher_payment->reference_transaction_id ?? '') }}">
                        @error('reference_transaction_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <!--end::Row-->
            </div>

            <!--begin::Footer-->
            <div class="card-footer d-flex justify-content-between">
                <div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Update</button>
                    <a href="{{ route('voucher_payments.index') }}" class="btn btn-secondary ms-2"><i
                            class="bi bi-arrow-left me-1"></i> Back to List</a>
                </div>
            </div>
        </form>
    </div>



@endsection
@section('scripts')

    @php
        $currentStatus = $voucher_payment->payment_status ?? '';
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentModeIdSelect = document.getElementById('payment_mode_id');
            const paymentModeInput = document.getElementById('payment_mode');
            const paymentTypeIdSelect = document.getElementById('payment_type_id');
            const paymentTypeInput = document.getElementById('payment_type');
            const pumpDropdownContainer = document.getElementById('pumpDropdownContainer');
            const pumpSelect = document.getElementById('pump_id');

            // Update hidden input with text value when select changes
            paymentModeIdSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                paymentModeInput.value = selectedOption.text;
                togglePumpDropdown();
            });

            paymentTypeIdSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                paymentTypeInput.value = selectedOption.text;
            });

            // Set initial values
            if (paymentModeIdSelect.selectedIndex > 0) {
                paymentModeInput.value = paymentModeIdSelect.options[paymentModeIdSelect.selectedIndex].text;
            }

            if (paymentTypeIdSelect.selectedIndex > 0) {
                paymentTypeInput.value = paymentTypeIdSelect.options[paymentTypeIdSelect.selectedIndex].text;
            }

            function togglePumpDropdown() {
                const selectedText = paymentModeIdSelect.options[paymentModeIdSelect.selectedIndex]?.text
                    .toLowerCase() || '';
                const currentPumpId = pumpSelect ? pumpSelect.value : '';
                const hasExistingPump = @json($voucher_payment->pump_id != null);

                // Show pump dropdown if:
                // 1. Payment mode contains "fuel" or "pump" (e.g., "fuel", "fuel(paid to pump)"), OR
                // 2. There's already a pump selected (for editing existing pump)
                if (selectedText.includes('fuel') || selectedText.includes('pump') || hasExistingPump ||
                    currentPumpId) {
                    pumpDropdownContainer.style.display = 'block';
                } else {
                    // Only hide if no pump is selected and payment mode doesn't require pump
                    if (!hasExistingPump && !currentPumpId) {
                        pumpDropdownContainer.style.display = 'none';
                        if (pumpSelect) pumpSelect.value = ''; // Reset pump selection
                    } else {
                        // Keep visible if there's an existing pump
                        pumpDropdownContainer.style.display = 'block';
                    }
                }
            }

            // Run on page load (for edit forms or validation errors)
            togglePumpDropdown();
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentModeIdSelect = document.getElementById('payment_mode_id');
            const paymentStatusSelect = document.getElementById('payment_status');
            const paidOption = document.getElementById('paidOption');
            const referenceBox = document.getElementById('reference_transaction_box');
            const referenceInput = document.getElementById('reference_transaction_id');

            const isAdmin = @json(auth()->user()->userRole->name === 'admin');
            const dbStatus = @json($currentStatus); // ← value from DB like 'in-progress'

            function updateStatusOptionsAndReferenceBox() {
                const selectedOption = paymentModeIdSelect.options[paymentModeIdSelect.selectedIndex];
                const selectedMode = selectedOption ? selectedOption.text.toLowerCase() : '';
                const selectedStatus = paymentStatusSelect.value.toLowerCase();

                console.log("Selected Payment Mode:", selectedMode);
                console.log("Selected Payment Status:", selectedStatus);
                console.log("DB Status:", dbStatus);

                if (['bank', 'online', 'fuel'].includes(selectedMode)) {
                    // Show Paid only if DB value is in-progress
                    if (dbStatus === 'in-progress') {
                        if (paidOption) paidOption.style.display = 'block';
                    } else {
                        if (paidOption) paidOption.style.display = 'none';

                        // If currently selected is "paid", but it's hidden, reset
                        if (paymentStatusSelect.value === 'paid') {
                            paymentStatusSelect.value = 'pending';
                        }
                    }

                    // Reference box for in-progress only
                    if (isAdmin && selectedStatus === 'in-progress' || selectedStatus === 'paid') {
                        referenceBox.style.display = 'block';
                    } else {
                        referenceBox.style.display = 'none';
                        if (referenceInput) referenceInput.value = '';
                    }

                } else {
                    if (paidOption) paidOption.style.display = 'block';
                    referenceBox.style.display = 'none';
                    if (referenceInput) referenceInput.value = '';
                }
            }

            paymentModeIdSelect.addEventListener('change', updateStatusOptionsAndReferenceBox);
            paymentStatusSelect.addEventListener('change', updateStatusOptionsAndReferenceBox);
            updateStatusOptionsAndReferenceBox(); // run once on page load
        });
    </script>
