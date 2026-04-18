@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
<div class="card card-info card-outline mb-4">
    <div class="card-header"><div class="card-title">Add New Driver</div></div>
    <!--begin::Form-->
    <form action="{{ route('drivers.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a name.</div>
                </div>
                <div class="col-md-6">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input type="text" class="form-control" id="mobile" name="mobile" value="{{ old('mobile') }}">
                    @error('mobile') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a valid mobile number.</div>
                </div>
                <div class="col-md-6">
                    <label for="license_number" class="form-label">License Number</label>
                    <input type="text" class="form-control" id="license_number" name="license_number" value="{{ old('license_number') }}">
                    @error('license_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a license number.</div>
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}">
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an address.</div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-brand-primary" type="submit">Submit form</button>
            <a href="{{ route('drivers.index') }}" class="btn btn-back">Back to List</a>
        </div>
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
@endsection
