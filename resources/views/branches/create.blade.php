@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
<div class="card card-info card-outline mb-4">
    <div class="card-header"><div class="card-title">Register New Branch</div></div>
    <!--begin::Form-->
    <form action="{{ route('branches.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="card-body">
            <div class="row g-3">
              
                
                <div class="col-md-6">
                    <label for="company_id" class="form-label">Company *</label>
                    <select class="form-select" id="company_id" name="company_id" required>
                        <option value="">Select Company</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                  <div class="col-md-6">
                    <label for="branch_name" class="form-label">Branch Name *</label>
                    <input type="text" class="form-control" id="branch_name" name="branch_name" required value="{{ old('branch_name') }}">
                    @error('branch_name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>

                <div class="col-md-6">
                    <label for="branch_contact_number" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="branch_contact_number" name="branch_contact_number" value="{{ old('branch_contact_number') }}">
                    @error('branch_contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>

                <div class="col-md-6">
                    <label for="head_office" class="form-label">Head Office</label>
                    <select class="form-select" id="head_office" name="head_office">
                        <option value="0" {{ old('head_office', '0') == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('head_office') == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                    @error('head_office') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>

                <div class="col-12">
                    <label for="branch_address" class="form-label">Address</label>
                    <textarea class="form-control" id="branch_address" name="branch_address" rows="3">{{ old('branch_address') }}</textarea>
                    @error('branch_address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-brand-primary">Save Branch</button>
            <a href="{{ route('branches.index') }}" class="btn btn-brand-primary">Back to Branches</a>
        </div>
    </form>
    <!--end::Form-->
    <!--begin::JavaScript-->
    <script>
        // Form validation
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