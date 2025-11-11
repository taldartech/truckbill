@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9 px-0">
            <div class="card card-info card-outline">
        
                <!--begin::Header-->
                <div class="card-header">
                    <div class="card-title">Edit Branch</div>
                </div>
                <!--end::Header-->

                <!--begin::Form-->
                <form action="{{ route('branches.update', $branch->id) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')

                    @if(session('success'))
                        <div class="alert alert-success m-3">{{ session('success') }}</div>
                    @endif

                    <!--begin::Body-->
                    <div class="card-body">
                        <div class="row g-3">

                            <!-- Company -->
                            <div class="col-md-6 col-12">
                                <label for="company_id" class="form-label">Company *</label>
                                <select class="form-select" id="company_id" name="company_id" required>
                                    <option value="">Select Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" 
                                            {{ old('company_id', $branch->company_id) == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            <!-- Branch Name -->
                            <div class="col-md-6 col-12">
                                <label for="branch_name" class="form-label">Branch Name *</label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name" required
                                    value="{{ old('branch_name', $branch->branch_name) }}">
                                @error('branch_name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            <!-- Contact Number -->
                            <div class="col-md-6 col-12">
                                <label for="branch_contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="branch_contact_number" name="branch_contact_number"
                                    value="{{ old('branch_contact_number', $branch->branch_contact_number) }}">
                                @error('branch_contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            <!-- Head Office -->
                            <div class="col-md-6 col-12">
                                <label for="head_office" class="form-label">Head Office</label>
                                <select class="form-select" id="head_office" name="head_office">
                                    <option value="0" {{ old('head_office', $branch->head_office) == 0 ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('head_office', $branch->head_office) == 1 ? 'selected' : '' }}>Yes</option>
                                </select>
                                @error('head_office') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            <!-- Address -->
                            <div class="col-12">
                                <label for="branch_address" class="form-label">Address</label>
                                <textarea class="form-control" id="branch_address" name="branch_address" rows="3">{{ old('branch_address', $branch->branch_address) }}</textarea>
                                @error('branch_address') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                        </div>
                    </div>
                    <!--end::Body-->

                    <!--begin::Footer-->
                    <div class="card-footer text-start">
                        <button type="submit" class="btn btn-success">Update Branch</button>
                        <a href="{{ route('branches.index') }}" class="btn btn-info">Back to list</a>
                    </div>
                    <!--end::Footer-->
                </form>

            </div>
        </div>
    </div>

</div>

<!-- Bootstrap Validation Script -->
<script>
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach((form) => {
            form.addEventListener('submit', (event) => {
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

    
