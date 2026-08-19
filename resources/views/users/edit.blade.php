@extends('layouts.app')

@section('content')
@php
    $userIsSuperAdmin = ($user->userRole->name ?? '') === 'super_admin';
    $selectedCompanyId = old('company_id', $user->company_id);
    $selectedBranchId = old('branch_id', $user->branch_id);
@endphp
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form id="userForm" data-confirm-mode="update" data-old-branch-id="{{ $selectedBranchId }}" action="{{ route('users.update', $user->id) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-pencil-square me-1"></i> Edit User
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('users.index') }}" class="btn app-index-header-btn" aria-label="Back to users">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="mb-3">
                        <span class="badge text-bg-dark border app-index-flash">
                            <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
                        </span>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-person-vcard"></i> User Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" required value="{{ old('name', $user->name) }}">
                                @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" required value="{{ old('email', $user->email) }}">
                                @error('email') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-2">
                            @if ($isMasterAdmin)
                                <div class="col-md-6">
                                    <label for="role_display" class="form-label">Role *</label>
                                    <input type="text" class="form-control" id="role_display" value="Super Admin" readonly>
                                    <input type="hidden" id="role" name="role" value="super_admin">
                                </div>
                            @else
                                <div class="col-md-{{ ! $userIsSuperAdmin ? '4' : '6' }}">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                        <option value="">Select role</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}" {{ old('role', $user->userRole->name ?? '') == $role ? 'selected' : '' }}>
                                                {{ \Illuminate\Support\Str::headline($role) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                                @if (! $userIsSuperAdmin)
                                    <div class="col-md-4 company-branch-field">
                                        <label for="company_id" class="form-label">Company *</label>
                                        <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                                            <option value="">Select company</option>
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}" {{ (string) $selectedCompanyId === (string) $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4 company-branch-field">
                                        <label for="branch_id" class="form-label">Branch *</label>
                                        <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                            <option value="">Select branch</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}" data-company="{{ $branch->company_id }}" hidden>
                                                    {{ $branch->branch_name }} ({{ $branch->head_office == 1 ? 'Head Office' : 'Branch' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('branch_id') <div class="text-danger">{{ $message }}</div> @enderror
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-shield-lock"></i> Login Credentials</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" minlength="6" autocomplete="new-password">
                                @error('password') <div class="text-danger">{{ $message }}</div> @enderror
                                <div class="app-form-section__hint mt-1 mb-0">Leave blank to keep the current password. Minimum 6 characters if changing.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" minlength="6" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Update User
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
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
<script src="{{ asset('js/user-form.js') }}?v={{ config('app.ASSET_VERSION') }}"></script>
@endpush
