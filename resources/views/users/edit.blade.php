@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
                <div class="card card-info card-outline mb-4">
                    <!--begin::Header-->
                    <div class="card-header">
                        <div class="card-title">Edit User</div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form action="{{ route('users.update', $user->id) }}" method="POST" class="needs-validation"
                        novalidate="">
                        @csrf
                        @method('PUT')
                        <!--begin::Body-->
                        <div class="card-body">
                            <!--begin::Row-->
                            <div class="row g-3">
                                <!--begin::Col-->
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                        value="{{ old('name', $user->name) }}">
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
                                        value="{{ old('email', $user->email) }}">
                                    @error('email')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <small class="form-text text-muted">Leave blank to keep current password.</small>
                                    @error('password')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="password_confirmation"
                                        name="password_confirmation">
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                                <!--end::Col-->

                                @php
                                    $isMasterAdmin =
                                        auth()->check() && auth()->user()->userRole->name === 'master_admin';
                                    $userIsSuperAdmin = $user->userRole->name === 'super_admin';
                                @endphp

                                <!--begin::Col-->
                                @if ($isMasterAdmin)
                                    <input type="hidden" name="role" value="super_admin">
                                @else
                                    <div class="col-md-6">
                                        <label for="role" class="form-label">Role *</label>
                                        <!-- Super admin can edit roles for company users -->
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="">Select</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role }}"
                                                    {{ old('role', $user->userRole->name ?? '') == $role ? 'selected' : '' }}>
                                                    {{ ucfirst($role) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                        <div class="valid-feedback">Looks good!</div>
                                    </div>
                                @endif
                                <!--end::Col-->

                                <!-- Company and Branch (only for super_admin editing company users) -->
                                @if (!$isMasterAdmin && !$userIsSuperAdmin)
                                    <!--begin::Col-->
                                    <div class="col-md-6">
                                        <label for="company_id" class="form-label">Company *</label>
                                        <select class="form-select" id="company_id" name="company_id" required>
                                            <option value="">Select</option>
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}"
                                                    {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('company_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                        <div class="valid-feedback">Looks good!</div>
                                    </div>
                                    <!--end::Col-->

                                    <!--begin::Col-->
                                    <div class="col-md-6">
                                        <label for="branch_id" class="form-label">Branch *</label>
                                        <select class="form-select" id="branch_id" name="branch_id" required>
                                            <option value="">Select Branch</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}"
                                                    data-company="{{ $branch->company_id }}"
                                                    {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}
                                                    style="display: none;">
                                                    {{ $branch->branch_name }}({{ $branch->head_office == 1 ? 'Head Office' : 'Branch' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('branch_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                        <div class="valid-feedback">Looks good!</div>
                                    </div>
                                    <!--end::Col-->
                                @endif
                            </div>
                            <!--end::Row-->
                        </div>
                        <!--end::Body-->

                        <!--begin::Footer-->
                        <div class="card-footer">
                            <button class="btn btn-brand-primary" type="submit">Update User</button>
                            <a href="{{ route('users.index') }}" class="btn btn-back">Back to List</a>
                        </div>
                        <!--end::Footer-->
                    </form>
                    <!--end::Form-->

                    <!-- JavaScript (same functionality as before, unchanged) -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const companySelect = document.getElementById('company_id');
                            const branchSelect = document.getElementById('branch_id');

                            // Skip branch filtering if elements don't exist (for super_admin editing)
                            if (!companySelect || !branchSelect) {
                                return;
                            }

                            function filterBranches(companyId) {
                                const options = branchSelect.querySelectorAll('option');
                                let hasVisibleOptions = false;

                                options.forEach(option => {
                                    if (option.value === '') return;
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
                                branchSelect.value = '';
                            }

                            companySelect.addEventListener('change', function() {
                                filterBranches(this.value);
                            });

                            if (companySelect && companySelect.value) {
                                filterBranches(companySelect.value);
                                if (branchSelect) {
                                    branchSelect.value = "{{ old('branch_id', $user->branch_id) }}";
                                }
                            } else if ("{{ old('company_id') }}") {
                                filterBranches("{{ old('company_id') }}");
                            }
                        });
                    </script>

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
                </div>
            </div>
        </div>
    </div>
@endsection
