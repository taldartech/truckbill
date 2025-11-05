@extends('layouts.app')

@section('content')
    <div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
        <!--begin::Header-->
        <div class="card-header">
            <div class="card-title">Add New User</div>
        </div>
        <!--end::Header-->
        <!--begin::Form-->
        <form action="{{ route('users.store') }}" method="POST" class="needs-validation" novalidate="">
            @csrf
            <!--begin::Body-->
            <div class="card-body">
                <!--begin::Row-->
                <div class="row g-3">
                    <!--begin::Col-->
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required
                            value="{{ old('name') }}">
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                            value="{{ old('email') }}">
                        @error('email')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        @error('password')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                            required>
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <!--end::Col-->
                    @php
                        $canEdit =
                            auth()->check() &&
                            (auth()->user()->userRole->name === 'super_admin' ||
                                auth()->user()->userRole->name === 'admin');
                    @endphp
                    <!--begin::Col-->
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role *</label>
                        @if ($canEdit)
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>
                                        {{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="role" value="{{ auth()->user()->userRole->name ?? '' }}">
                            <div class="form-control-plaintext">{{ ucfirst(auth()->user()->userRole->name ?? '') }}</div>
                        @endif
                        @error('role')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-6">
                        <label for="company_id" class="form-label">Company *</label>
                        @if ($canEdit)
                            <select class="form-select" id="company_id" name="company_id">
                                <option value="">Select</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                            <div class="form-control-plaintext">{{ auth()->user()->company->name ?? '' }}</div>
                        @endif
                        @error('company_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                <!--begin::Row-->
                <div class="row g-3">
                    <!--begin::Col-->
                    <div class="col-md-6">
                        <label for="branch_id" class="form-label">Branch *</label>
                        @if ($canEdit)
                            {{-- <select class="form-select" id="branch_id" name="branch_id" required>
                            <option value="">Select</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->branch_name }}({{ $branch->head_office == 1 ? 'Head Office' : 'Branch' }})</option>
                            @endforeach
                        </select> --}}
                            <select class="form-select" id="branch_id" name="branch_id" required>
                                <option value="">Select Branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" data-company="{{ $branch->company_id }}"
                                        {{ old('branch_id') == $branch->id ? 'selected' : '' }} style="display: none;">
                                        {{ $branch->branch_name }}({{ $branch->head_office == 1 ? 'Head Office' : 'Branch' }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                            <div class="form-control-plaintext">{{ auth()->user()->branch->branch_name ?? '' }}</div>
                        @endif
                        @error('branch_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
            </div>
            <!--end::Body-->
            <!--begin::Footer-->
            <div class="card-footer">
                <button class="btn btn-info" type="submit">Submit form</button>
                <a href="{{ route('users.index') }}" class="btn btn-info">Back to List</a>
            </div>
            <!--end::Footer-->
        </form>
        <!--end::Form-->
        <!--begin::JavaScript-->
        <script>
            // Load branches when the page loads if a company is already selected
            document.addEventListener('DOMContentLoaded', function() {
                const companySelect = document.getElementById('company_id');
                const branchSelect = document.getElementById('branch_id');

                function filterBranches(companyId) {
                    const options = branchSelect.querySelectorAll('option');
                    let hasVisibleOptions = false;

                    options.forEach(option => {
                        if (option.value === '') return; // Skip the "Select Branch" option

                        const branchCompanyId = option.getAttribute('data-company');
                        if (companyId && branchCompanyId === companyId) {
                            option.style.display = 'block';
                            hasVisibleOptions = true;
                        } else {
                            option.style.display = 'none';
                            if (option.selected) {
                                option.selected = false;
                            }
                        }
                    });

                    // Show a message if no branches are available for the selected company
                    const noBranchOption = document.getElementById('no-branch-option');
                    if (!hasVisibleOptions) {
                        if (!noBranchOption) {
                            const newOption = document.createElement('option');
                            newOption.id = 'no-branch-option';
                            newOption.value = '';
                            newOption.textContent = 'No branches available';
                            branchSelect.appendChild(newOption);
                        }
                    } else if (noBranchOption) {
                        noBranchOption.remove();
                    }

                    // Reset to "Select Branch" when company changes
                    branchSelect.value = '';
                }

                // Add event listener for company change
                companySelect.addEventListener('change', function() {
                    filterBranches(this.value);
                });

                // Initialize with the selected company if any
                if (companySelect.value) {
                    filterBranches(companySelect.value);
                } else if ("{{ old('company_id') }}") {
                    filterBranches("{{ old('company_id') }}");
                }
            });
        </script>

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
