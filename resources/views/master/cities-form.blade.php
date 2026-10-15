@extends('layouts.app')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .card-header .card-title,
        .card-header .h3,
        .card-header .h4 {
            font-size: 0.88rem !important;
            font-weight: 600 !important;
        }

        .card-header .btn,
        .app-index-header-btn {
            font-size: 0.72rem !important;
            padding: 0.25rem 0.5rem !important;
            min-height: 28px !important;
            height: 28px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }

        .app-form-section__title,
        h5, .h5 {
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.3rem !important;
        }

        label,
        .form-label {
            font-size: 0.68rem !important;
            font-weight: 600 !important;
            margin-bottom: 0.12rem !important;
            line-height: 1.25 !important;
            color: #334155 !important;
            display: inline-block !important;
        }

        /* Fill-in-the-blank input heights made compact */
        .form-control,
        .form-select,
        input.form-control {
            font-size: 0.72rem !important;
            padding: 0.2rem 0.45rem !important;
            min-height: 29px !important;
            height: 29px !important;
            border-radius: 0.35rem !important;
            line-height: 1.2 !important;
        }

        textarea.form-control {
            height: auto !important;
            min-height: 48px !important;
            font-size: 0.72rem !important;
            padding: 0.25rem 0.45rem !important;
            line-height: 1.3 !important;
        }

        .app-form-section__body {
            padding: 0.5rem 0.65rem !important;
        }

        .card-body {
            padding: 0.5rem 0.65rem !important;
        }

        /* Save & Cancel buttons on the same line */
        .app-form-actions {
            margin-top: 0.65rem !important;
            border-radius: 10px !important;
            padding: 0 !important;
        }

        .app-form-actions .card-body {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            justify-content: stretch !important;
            align-items: center !important;
            gap: 0.45rem !important;
            padding: 0.45rem 0.55rem !important;
        }

        .app-form-actions .btn,
        .app-form-actions .card-body .btn {
            width: 50% !important;
            flex: 1 1 50% !important;
            min-width: 0 !important;
            white-space: nowrap !important;
            font-size: 0.74rem !important;
            font-weight: 600 !important;
            min-height: 29px !important;
            height: 29px !important;
            padding: 0.25rem 0.45rem !important;
            border-radius: 0.375rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.25rem !important;
        }

        .app-form-actions .btn i {
            font-size: 0.70rem !important;
            margin: 0 !important;
        }

        .text-danger, .invalid-feedback {
            font-size: 0.64rem !important;
            margin-top: 0.1rem !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $isEdit = isset($city);
    $selectedCountryId = $selectedCountryId ?? old('country_id', $isEdit ? ($city->state->country_id ?? $city->country_id) : '');
    $selectedStateId = $selectedStateId ?? old('state_id', $isEdit ? $city->state_id : '');
@endphp
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form id="cityForm" @unless($isEdit) data-confirm-mode="create" @endunless data-selected-state-id="{{ $selectedStateId }}" action="{{ $isEdit ? route('master.cities.update', $city->id) : route('master.cities.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-geo-alt me-1"></i> {{ $isEdit ? 'Edit City' : 'Add New City' }}
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('master.cities.index') }}" class="btn app-index-header-btn" aria-label="Back to cities">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
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
                        <h5 class="app-form-section__title"><i class="bi bi-geo-alt"></i> City Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="country_id" class="form-label">Country *</label>
                                <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required>
                                    <option value="">Select country</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ (string) $selectedCountryId === (string) $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="state_id" class="form-label">State *</label>
                                <select class="form-select @error('state_id') is-invalid @enderror" id="state_id" name="state_id" required>
                                    <option value="">Select state</option>
                                </select>
                                @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">City Name *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" required value="{{ old('name', $isEdit ? $city->name : '') }}">
                                @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <a href="{{ route('master.cities.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Save
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
<script src="{{ asset('js/city-form.js') }}?v={{ config('app.ASSET_VERSION') }}"></script>
@endpush
