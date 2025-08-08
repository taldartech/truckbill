@extends('layouts.app')

@section('content')
@if(session('success'))
    <div class="alert alert-success mt-2">
        {{ session('success') }}
    </div>
@endif
<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Advance Payment</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('advance-payments.store') }}" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">

        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row mb-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="transporter_id" class="form-label">Transporter *</label>
                    <div class="input-group">
                        <input type="hidden" name="transporter_id" value="{{ isset($preFilled) ? $preFilled['transporter_id'] : old('transporter_id') }}">
                        <input type="text" class="form-control" readonly value="{{ isset($preFilled) ? $preFilled['transporter_name'] : old('transporter_name') }}">
                    </div>
                    @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="advance_amount" class="form-label">Advance Amount *</label>
                    <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="{{ old('advance_amount') }}" required>
                    @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an advance amount.</div>
                </div>
                <!--end::Col-->
            </div>
            <!--begin::Row-->
            <div class="row mb-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="payment_date" class="form-label">Payment Date *</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ old('payment_date') }}" required>
                    @error('payment_date') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a payment date.</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <div class="card p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <button class="btn btn-success w-100 mb-2" type="submit">💾 Submit Form</button>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('advance-payments.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Body-->
    </form>
    <!--end::Form-->
</div>
@endsection

@section('scripts')
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
@endsection