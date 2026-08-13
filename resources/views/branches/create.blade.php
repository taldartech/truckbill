@extends('layouts.app')

@section('content')
@php
    $selectedCompanyId = old('company_id', $companies->count() === 1 ? $companies->first()->id : '');
@endphp
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form id="branchForm" data-confirm-mode="create" action="{{ route('branches.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-diagram-3 me-1"></i> Register Branch
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('branches.index') }}" class="btn app-index-header-btn" aria-label="Back to branches">
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
                        <h5 class="app-form-section__title"><i class="bi bi-diagram-3"></i> Branch Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label for="company_id" class="form-label">Company *</label>
                                <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                                    <option value="">Select company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ (string) $selectedCompanyId === (string) $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="branch_name" class="form-label">Branch Name *</label>
                                <input type="text" class="form-control @error('branch_name') is-invalid @enderror" id="branch_name" name="branch_name" required value="{{ old('branch_name') }}">
                                @error('branch_name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label for="branch_contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control @error('branch_contact_number') is-invalid @enderror" id="branch_contact_number" name="branch_contact_number" maxlength="10" inputmode="numeric" autocomplete="tel" value="{{ old('branch_contact_number') }}" placeholder="10-digit mobile">
                                @error('branch_contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="head_office" class="form-label">Head Office</label>
                                <select class="form-select @error('head_office') is-invalid @enderror" id="head_office" name="head_office" required>
                                    <option value="0" {{ old('head_office', '0') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('head_office') == '1' ? 'selected' : '' }}>Yes</option>
                                </select>
                                @error('head_office') <div class="text-danger">{{ $message }}</div> @enderror
                                <div class="app-form-section__hint mt-1 mb-0">Only one head office is allowed per company.</div>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="branch_address" class="form-label">Address</label>
                                <textarea class="form-control @error('branch_address') is-invalid @enderror" id="branch_address" name="branch_address" rows="3" placeholder="Branch address">{{ old('branch_address') }}</textarea>
                                @error('branch_address') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <a href="{{ route('branches.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Register Branch
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
<script src="{{ asset('js/branch-form.js') }}"></script>
@endpush
