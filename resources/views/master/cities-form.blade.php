@extends('layouts.app')

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
<script src="{{ asset('js/city-form.js') }}"></script>
@endpush
