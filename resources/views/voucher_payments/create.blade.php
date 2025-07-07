@extends('layouts.app')
@section('content')
<div class="card card-info card-outline mb-4">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add Voucher Payment</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('voucher_payments.store') }}" class="needs-validation" novalidate>
        @csrf
        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="voucher_id" class="form-label">Voucher *</label>
                    <select name="voucher_id" id="voucher_id" class="form-select" required>
                        <option value="">Select</option>
                        @foreach($vouchers as $voucher)
                            <option value="{{ $voucher->id }}" {{ old('voucher_id') == $voucher->id ? 'selected' : '' }}>{{ $voucher->id }}</option>
                        @endforeach
                    </select>
                    @error('voucher_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a voucher.</div>
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
                <div class="col-md-6">
                    <label for="transaction_id" class="form-label">Transaction ID *</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" value="{{ old('transaction_id') }}" required>
                    @error('transaction_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a transaction ID.</div>
                </div>
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
                <div class="col-md-6">
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
        <!--end::Body-->
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
