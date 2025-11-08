@extends('layouts.app')

@section('content')
<div class="container px-2.3 ">

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9 px-0">

            <div class="card card-info card-outline mb-4">

                <!-- Header -->
                <div class="card-header">
                    <h5 class="card-title mb-0">Register New Branch</h5>
                </div>

                <!-- Form -->
                <form action="{{ route('branches.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf

                    @if(session('success'))
                        <div class="alert alert-success m-3">{{ session('success') }}</div>
                    @endif

                    <div class="card-body">

                        <div class="row g-3">

                            <!-- Company -->
                            <div class="col-md-6 col-12">
                                <label class="form-label">Company *</label>
                                <select class="form-select" name="company_id" required>
                                    <option value="">Select Company</option>
                                    @foreach(\App\Models\Company::all() as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id') 
                                    <div class="text-danger">{{ $message }}</div> 
                                @enderror
                            </div>

                            <!-- Branch Name -->
                            <div class="col-md-6 col-12">
                                <label class="form-label">Branch Name *</label>
                                <input type="text" class="form-control" name="branch_name" value="{{ old('branch_name') }}" required>
                                @error('branch_name') 
                                    <div class="text-danger">{{ $message }}</div> 
                                @enderror
                            </div>

                            <!-- Contact Number -->
                            <div class="col-md-6 col-12">
                                <label class="form-label">Contact Number</label>
                                <input type="text" class="form-control" name="branch_contact_number" maxlength="10" value="{{ old('branch_contact_number') }}">
                                @error('branch_contact_number') 
                                    <div class="text-danger">{{ $message }}</div> 
                                @enderror
                            </div>

                            <!-- Head Office -->
                            <div class="col-md-6 col-12">
                                <label class="form-label">Head Office</label>
                                <select class="form-select" name="head_office">
                                    <option value="0" {{ old('head_office', '0') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('head_office') == '1' ? 'selected' : '' }}>Yes</option>
                                </select>
                                @error('head_office') 
                                    <div class="text-danger">{{ $message }}</div> 
                                @enderror
                            </div>

                            <!-- Address -->
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="branch_address" rows="3">{{ old('branch_address') }}</textarea>
                                @error('branch_address') 
                                    <div class="text-danger">{{ $message }}</div> 
                                @enderror
                            </div>

                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn-info">Save Branch</button>
                        <a href="{{ route('branches.index') }}" class="btn btn-secondary">Back to Branches</a>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<script>
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

@endsection
