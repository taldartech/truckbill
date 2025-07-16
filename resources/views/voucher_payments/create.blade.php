@extends('layouts.app')
@section('content')

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
       
    </div>
</div>
<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add Voucher Payment</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('voucher_payments.store') }}" class="needs-validation" novalidate>
        @csrf
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
                           value="{{ $voucher_number }}"
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
                            <option value="{{ $paymentType->name }}" {{ old('payment_type') == $paymentType->name ? 'selected' : '' }}>
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
                            <option value="{{ $paymentMode->name }}" {{ old('payment_mode') == $paymentMode->name ? 'selected' : '' }}>
                                {{ $paymentMode->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_mode') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a payment mode.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                {{-- <div class="col-md-6">
                    <label for="transaction_id" class="form-label">Transaction ID *</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" value="{{ old('transaction_id') }}" required>
                    @error('transaction_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a transaction ID.</div>
                </div> --}}
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="amount" class="form-label">Amount *</label>
                    <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required step="0.01">
                    @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an amount.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <!-- Hidden payment_status for operators -->
                @php
                $isAdmin = auth()->user()->userRole && auth()->user()->userRole->name === 'admin';
            @endphp
               
                <div class="col-md-6"  @if(!$isAdmin) style="display: none;" @endif>
                    <label for="payment_status" class="form-label">Payment Status *</label>
                    <select name="payment_status" id="payment_status" class="form-select" required>
                        <option value="pending" {{ old('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in-progress" {{ old('payment_status') == 'in-progress' ? 'selected' : '' }}>In-Progress</option>
                        <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                    @error('payment_status') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a payment status.</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const paymentModeSelect = document.getElementById('payment_mode');
                const paymentStatusSelect = document.getElementById('payment_status');
                const isAdmin = @json(auth()->user()->userRole->name === 'admin');
            
                paymentModeSelect.addEventListener('change', function() {
                    const selectedValue = this.value.toLowerCase();
            
                    if (isAdmin) {
                        if (selectedValue === 'cash' || selectedValue === 'adjust in advance') {
                            paymentStatusSelect.value = 'in-progress';
                        } else if (['bank', 'online', 'fuel'].includes(selectedValue)) {
                            paymentStatusSelect.value = 'pending';
                            paymentStatusSelect.disabled = false;
                        } else {
                            paymentStatusSelect.disabled = false;
                        }
                    } else {
                        // Operator (non-admin)
                        if (selectedValue === 'cash' || selectedValue === 'adjust in advance') {
                            paymentStatusSelect.value = 'in-progress';
                        } else if (['bank', 'online', 'fuel'].includes(selectedValue)) {
                            paymentStatusSelect.value = 'pending';
                        }
                    }
                });
            
                // Trigger change once at start (in case of edit or default selection)
                paymentModeSelect.dispatchEvent(new Event('change'));
            });
            </script>
{{-- 
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const paymentModeSelect = document.getElementById('payment_mode');
                const paymentStatusSelect = document.getElementById('payment_status');
                const isAdmin = auth()->user()->role === 'admin';

                paymentModeSelect.addEventListener('change', function() {
                    // Get the selected option value (which is the payment mode name)
                    const selectedValue = this.value;
                    console.log('Selected payment mode:', selectedValue);
                    
                    if (isAdmin) {
                        if (selectedValue.toLowerCase() === 'cash' || selectedValue.toLowerCase() === 'adjust in advance') {
                            console.log('Cash selected, setting status to in-progress');
                            paymentStatusSelect.value = 'in-progress';
                            // paymentStatusSelect.disabled = true;
                        } else if (selectedValue.toLowerCase() === 'bank' || 
                                   selectedValue.toLowerCase() === 'online' || 
                                   selectedValue.toLowerCase() === 'fuel') {
                            console.log('Bank/Online/Fuel selected, setting status to pending');
                            paymentStatusSelect.value = 'pending';
                            paymentStatusSelect.disabled = false;
                        } else {
                            console.log('Other payment mode selected, enabling status');
                            paymentStatusSelect.disabled = false;
                        }
                    } else {
                        // For operators, set payment status based on payment mode
                        if (selectedValue.toLowerCase() === 'cash' || selectedValue.toLowerCase() === 'adjust in advance') {
                            paymentStatusSelect.value = 'in-progress';
                        } else if (selectedValue.toLowerCase() === 'bank' || 
                                   selectedValue.toLowerCase() === 'online' || 
                                   selectedValue.toLowerCase() === 'fuel') {
                            paymentStatusSelect.value = 'pending';
                        }
                    }
                });
            });
        </script>
        <!--end::Body--> --}}
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-info" type="submit">Submit form</button>
            <a href="{{ route('voucher_payments.index') }}" class="btn btn-info">Back to List</a>
        </div>
        <!--end::Footer-->
    </form>
    <!--end::Form-->
    <!--begin::JavaScript-->
    <script>
        // Example starter JavaScript for disabling form submissions if there are invalid fields
        (() => {
            'use strict';

            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            const forms = document.querySelectorAll('.needs-validation');

            // Loop over them and prevent submission
            Array.from(forms).forEach((form) => {
                form.addEventListener(
                    'submit',
                    (event) => {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }

                        form.classList.add('was-validated');
                    },
                    false,
                );
            });
        })();
    </script>
    <!--end::JavaScript-->
</div>
@endsection
