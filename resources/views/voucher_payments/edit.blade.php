@extends('layouts.app')
@section('content')
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    
    <div class="row g-3">
         <div class="col-md-3 ps-4">
            <p><strong>LR Number:</strong> {{ $lorry_receipt->first()->lr_number }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Transporter:</strong> {{ $lorry_receipt->first()->transporter->name }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Truck:</strong> {{ $lorry_receipt->first()->truck->truck_number }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Customer:</strong> {{ $lorry_receipt->first()->customer->name }}</p>
        </div>
        <div class="col-md-3 ps-4">
            <p><strong>Consignor:</strong> {{ $lorry_receipt->first()->consignor->name }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Consignee:</strong> {{ $lorry_receipt->first()->consignee->name }}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Amount:</strong> {{ $balanceAmount }}</p>
        </div>
    </div>
</div>
<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Edit Voucher Payment</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('voucher_payments.update', $voucher_payment->id) }}" class="needs-validation" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        
        @if(isset($freight_booking_id))
            <input type="hidden" name="freight_booking_id" value="{{ $freight_booking_id }}">
        @endif
        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="voucher_number" class="form-label">Voucher *</label>
                    <input type="text" 
                           name="voucher_number" 
                           id="voucher_number" 
                           class="form-control" 
                           value="{{ $voucher_payment->voucher_number }}"
                           readonly 
                           required>
                    @error('voucher_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a voucher number.</div>
                </div>
                <!--end::Col-->
               
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="payment_type" class="form-label">Payment Type *</label>
                    <select name="payment_type" id="payment_type" class="form-select" required>
                        <option value="">Select Payment Type</option>
                        @foreach($paymentTypes as $paymentType)
                            <option value="{{ $paymentType->name }}" {{ old('payment_type', $voucher_payment->payment_type) == $paymentType->name ? 'selected' : '' }}>
                                {{ $paymentType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_type') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a payment type.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="payment_mode" class="form-label">Payment Mode *</label>
                    <select name="payment_mode" id="payment_mode" class="form-select" required>
                        <option value="">Select Payment Mode</option>
                        @foreach($paymentModes as $paymentMode)
                            <option value="{{ $paymentMode->name }}" {{ old('payment_mode', $voucher_payment->payment_mode) == $paymentMode->name ? 'selected' : '' }}>
                                {{ $paymentMode->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_mode') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a payment mode.</div>
                </div>
                <!--end::Col-->
                <div class="col-md-4 mt-3" id="pumpDropdownContainer" style="display: none;">
                    <label for="pump_id" class="form-label">Select Pump</label>
                    <select name="pump_id" id="pump_id" class="form-select">
                        <option value="">Select Pump</option>
                        @foreach($pumps as $pump)
                            <option value="{{ $pump->id }}" {{ old('pump_id', $voucher_payment->pump_id) == $pump->id ? 'selected' : '' }}>
                                {{ $pump->pump_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('pump_id') <div class="text-danger">{{ $message }}</div> @enderror
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
                <div class="col-md-6">
                    <label for="remark" class="form-label">Remark</label>
                    <textarea name="remark" id="remark" class="form-control">{{ old('remark', $voucher_payment->remark) }}</textarea>
                    @error('remark') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <!-- Hidden payment_status for operators -->
               
                @php
    $isAdmin = auth()->user()->userRole && auth()->user()->userRole->name === 'admin';
@endphp
                <div class="col-md-4" @if(!$isAdmin) style="display: none;" @endif>
                    <label for="payment_status" class="form-label">Payment Status *</label>
                    <select name="payment_status" id="payment_status" class="form-select" required>
                        <option value="pending" {{ old('payment_status', $voucher_payment->payment_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in-progress" {{ old('payment_status', $voucher_payment->payment_status) == 'in-progress' ? 'selected' : '' }}>In-Progress</option>
                        <option  value="paid"   id="paidOption" {{ old('payment_status', $voucher_payment->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                    @error('payment_status') <div class="text-danger">{{ $message }}</div> @enderror
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
                <div class="col-md-4">
                    <label for="amount" class="form-label">Amount *</label>
                    <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount', $voucher_payment->amount) }}" required>
                    @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an amount.</div>
                </div>
                <!--end::Col-->
                <div class="col-md-4" id="reference_transaction_box" style="display: none;">
                    <label for="reference_transaction_id" class="form-label">Reference Transaction ID *</label>
                    <input type="text" class="form-control"
                           id="reference_transaction_id"
                           name="reference_transaction_id"
                           value="{{ old('reference_transaction_id', $voucher_payment->reference_transaction_id ?? '') }}">
                    @error('reference_transaction_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
            <!--end::Row-->
        </div>
      
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-info" type="submit">Update</button>
            <a href="{{ route('voucher_payments.index') }}" class="btn btn-info">Back to List</a>
        </div>
    </form>
</div>



@endsection
@section('scripts')

@php
    $currentStatus = $voucher_payment->payment_status ?? '';
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const paymentModeSelect = document.getElementById('payment_mode');
        const pumpDropdownContainer = document.getElementById('pumpDropdownContainer');
        const pumpSelect = document.getElementById('pump_id');
    
        function togglePumpDropdown() {
            const selectedValue = paymentModeSelect.value.toLowerCase();
            if (selectedValue === 'fuel') {
                pumpDropdownContainer.style.display = 'block';
            } else {
                pumpDropdownContainer.style.display = 'none';
                pumpSelect.value = ''; // Reset pump selection
            }
        }
    
        paymentModeSelect.addEventListener('change', togglePumpDropdown);
    
        // Run on page load (for edit forms or validation errors)
        togglePumpDropdown();
    });
    </script>
    

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const paymentModeSelect = document.getElementById('payment_mode');
        const paymentStatusSelect = document.getElementById('payment_status');
        const paidOption = document.getElementById('paidOption');
        const referenceBox = document.getElementById('reference_transaction_box');
        const referenceInput = document.getElementById('reference_transaction_id');

        const isAdmin = @json(auth()->user()->userRole->name === 'admin');
        const dbStatus = @json($currentStatus); // ← value from DB like 'in-progress'

        function updateStatusOptionsAndReferenceBox() {
            const selectedMode = paymentModeSelect.value.toLowerCase();
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

        paymentModeSelect.addEventListener('change', updateStatusOptionsAndReferenceBox);
        paymentStatusSelect.addEventListener('change', updateStatusOptionsAndReferenceBox);
        updateStatusOptionsAndReferenceBox(); // run once on page load
    });
</script>


