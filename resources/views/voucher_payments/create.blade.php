@extends('layouts.app')
@section('content')

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>⚠️ Duplicate Payment Warning:</strong>
        <div style="margin-top: 10px;">{!! session('warning') !!}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
<div class="card card-info card-outline mb-4">
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
            <p><strong> Amount:</strong> {{ $balanceAmount }}</p>
        </div>
       
    </div>
</div>
<div class="card card-info card-outline mb-4">
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
                    <label for="payment_type_id" class="form-label">Payment Type *</label>
                    <select name="payment_type_id" id="payment_type_id" class="form-select" required>
                        <option value="">Select Payment Type</option>
                        @foreach($paymentTypes as $paymentType)
                            <option value="{{ $paymentType->id }}" {{ old('payment_type_id') == $paymentType->id ? 'selected' : '' }}>
                                {{ $paymentType->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="payment_type" id="payment_type" value="">
                    @error('payment_type_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a payment type.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="payment_mode_id" class="form-label">Payment Mode *</label>
                    <select name="payment_mode_id" id="payment_mode_id" class="form-select" required>
                        <option value="">Select Payment Mode</option>
                        @foreach($paymentModes as $paymentMode)
                            <option value="{{ $paymentMode->id }}" {{ old('payment_mode_id') == $paymentMode->id ? 'selected' : '' }}>
                                {{ $paymentMode->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="payment_mode" id="payment_mode" value="">
                    @error('payment_mode_id') <div class="text-danger">{{ $message }}</div> @enderror
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
                <div class="col-md-4">
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
               
                <div class="col-md-4"  @if(!$isAdmin) style="display: none;" @endif>
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
                <div class="col-md-4" id="pumpDropdownContainer" style="display: none;">
                    <label for="pump_id" class="form-label">Select Pump</label>
                    <select name="pump_id" id="pump_id" class="form-select">
                        <option value="">Select Pump</option>
                        @foreach($pumps as $pump)
                            <option value="{{ $pump->id }}" {{ old('pump_id') == $pump->id ? 'selected' : '' }}>
                                {{ $pump->pump_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('pump_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
            <!--end::Row-->
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
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
                    const selectedText = paymentModeIdSelect.options[paymentModeIdSelect.selectedIndex]?.text.toLowerCase() || '';
                    if (selectedText === 'fuel') {
                        pumpDropdownContainer.style.display = 'block';
                    } else {
                        pumpDropdownContainer.style.display = 'none';
                        pumpSelect.value = ''; // Reset pump selection
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
                const isAdmin = @json(auth()->user()->userRole->name === 'admin');
            
                paymentModeIdSelect.addEventListener('change', function() {
                    const selectedText = this.options[this.selectedIndex]?.text.toLowerCase() || '';
            
                    if (isAdmin) {
                        if (selectedText === 'cash' || selectedText === 'adjust in advance') {
                            paymentStatusSelect.value = 'in-progress';
                        } else if (['bank', 'online', 'fuel'].includes(selectedText)) {
                            paymentStatusSelect.value = 'pending';
                            paymentStatusSelect.disabled = false;
                        } else {
                            paymentStatusSelect.disabled = false;
                        }
                    } else {
                        // Operator (non-admin)
                        if (selectedText === 'cash' || selectedText === 'adjust in advance') {
                            paymentStatusSelect.value = 'in-progress';
                        } else if (['bank', 'online', 'fuel'].includes(selectedText)) {
                            paymentStatusSelect.value = 'pending';
                        }
                    }
                });
            
                // Trigger change once at start (in case of edit or default selection)
                paymentModeIdSelect.dispatchEvent(new Event('change'));
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
        @if (session('warning') && session('duplicate_payment_id'))
            <div class="card-body border-top">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="confirm_duplicate" id="confirm_duplicate" value="1" {{ old('confirm_duplicate') ? 'checked' : '' }}>
                    <label class="form-check-label" for="confirm_duplicate">
                        <strong>I confirm that I want to proceed with creating this duplicate payment</strong>
                    </label>
                </div>
            </div>
        @endif
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-brand-primary" type="submit">Submit form</button>
            <a href="{{ route('voucher_payments.index') }}" class="btn btn-back">Back to List</a>
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
        </div>
    </div>
</div>
@endsection
