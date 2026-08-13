@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/company-settings.css') }}">
@endpush

@section('content')
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form id="companySettingsForm" data-confirm-mode="update" action="{{ route('company_settings.update', $company) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-sliders me-1"></i> {{ $company->name }}
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('company_settings.index') }}" class="btn app-index-header-btn" aria-label="Back to company settings">
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
                        <h5 class="app-form-section__title"><i class="bi bi-printer"></i> Print header</h5>
                        <span class="app-form-section__header-hint">Appears on vouchers, challans &amp; bills</span>
                    </div>
                    <div class="app-form-section__body">
                        @include('company_settings.partials.voucher-header', ['setting' => $setting])
                    </div>
                </div>

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-file-earmark-richtext"></i> Document designs</h5>
                        <span class="app-form-section__header-hint">Pick a design — each has its own Preview PDF</span>
                    </div>
                    <div class="app-form-section__body">
                        @include('company_settings.partials.pdf-studio', ['company' => $company, 'setting' => $setting])
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <span class="text-muted small me-auto d-none d-sm-inline">
                    <i class="bi bi-shield-check me-1"></i>Applies to {{ $company->name }} only
                </span>
                <a href="{{ route('company_settings.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Save settings
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
<script src="{{ asset('js/company-settings-form.js') }}"></script>
@endpush
