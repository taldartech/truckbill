@extends('layouts.app')

@section('content')
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form action="{{ isset($city) ? route('master.cities.update', $city->id) : route('master.cities.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @if(isset($city))
            @method('PUT')
        @endif

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-geo-alt me-1"></i> {{ isset($city) ? 'Edit City' : 'Add New City' }}
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('master.cities.index') }}" class="btn app-index-header-btn" aria-label="Back to cities">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-geo-alt"></i> City Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="country_id" class="form-label">Country *</label>
                                <select class="form-select" id="country_id" name="country_id" required>
                                    <option value="">Select Country</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}"
                                                {{ old('country_id', isset($city) ? $city->state->country_id : '') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">Please select a country.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="state_id" class="form-label">State *</label>
                                <select class="form-select" id="state_id" name="state_id" required>
                                    <option value="">Select State</option>
                                </select>
                                @error('state_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">Please select a state.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="name" class="form-label">City Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="{{ old('name', isset($city) ? $city->name : '') }}">
                                @error('name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
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

<script>
    (() => {
        'use strict';

        const forms = document.querySelectorAll('.needs-validation');
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

    document.addEventListener('DOMContentLoaded', function() {
        const countrySelect = document.getElementById('country_id');
        const stateSelect = document.getElementById('state_id');

        countrySelect.addEventListener('change', function() {
            const countryId = this.value;
            if (countryId) {
                fetch(`/api/states/${countryId}`)
                    .then(response => response.json())
                    .then(data => {
                        stateSelect.innerHTML = '<option value="">Select State</option>';

                        data.forEach(state => {
                            const option = document.createElement('option');
                            option.value = state.id;
                            option.text = state.name;
                            option.selected = state.id === {{ old('state_id', isset($city) ? $city->state_id : '') }}
                            stateSelect.appendChild(option);
                        });

                        const form = stateSelect.closest('form');
                        if (form.classList.contains('needs-validation')) {
                            stateSelect.classList.remove('is-invalid');
                            stateSelect.classList.remove('is-valid');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading states:', error);
                        stateSelect.innerHTML = '<option value="">Error loading states</option>';
                    });
            } else {
                stateSelect.innerHTML = '<option value="">Select State</option>';
            }
        });

        if (countrySelect.value) {
            countrySelect.dispatchEvent(new Event('change'));
        }
    });
</script>

@endsection
