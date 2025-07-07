@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Bank Account</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('transporter_bank_accounts.store') }}" id="bankAccountForm" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">

        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="transporter_id" class="form-label">Transporter *</label>
                    <select class="form-select" id="transporter_id" name="transporter_id" required>
                        <option value="">Select Transporter</option>
                        @foreach($transporters as $transporter)
                            <option value="{{ $transporter->id }}" {{ old('transporter_id') == $transporter->id ? 'selected' : '' }}>
                                {{ $transporter->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a transporter.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="bank_name" class="form-label">Bank Name *</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" required>
                    @error('bank_name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a bank name.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="account_number" class="form-label">Account Number *</label>
                    <input type="text" class="form-control" id="account_number" name="account_number" value="{{ old('account_number') }}" required>
                    @error('account_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an account number.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="ifsc_code" class="form-label">IFSC Code *</label>
                    <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" value="{{ old('ifsc_code') }}" required>
                    @error('ifsc_code') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an IFSC code.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="beneficiary_name" class="form-label">Beneficiary Name *</label>
                    <input type="text" class="form-control" id="beneficiary_name" name="beneficiary_name" value="{{ old('beneficiary_name') }}" required>
                    @error('beneficiary_name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a beneficiary name.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="bulk_payment_id" class="form-label">Bulk Payment ID</label>
                    <input type="text" class="form-control" id="bulk_payment_id" name="bulk_payment_id" value="{{ old('bulk_payment_id') }}">
                    @error('bulk_payment_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a bulk payment ID.</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Body-->
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-info" type="submit">Submit form</button>
            <a href="{{ route('transporter_bank_accounts.index') }}" class="btn btn-info">Back to List</a>
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
