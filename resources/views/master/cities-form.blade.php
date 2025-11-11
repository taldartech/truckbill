@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">{{ isset($city) ? 'Edit City' : 'Add New City' }}</h3>
    </div>
    <form action="{{ isset($city) ? route('master.cities.update', $city->id) : route('master.cities.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @if(isset($city))
            @method('PUT')
        @endif
        
        <div class="card-body">
            <div class="row g-3">
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
        
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Submit form</button>
            <a href="{{ route('master.cities.index') }}" class="btn btn-info">Back to List</a>
        </div>
    </form>
</div>

<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
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
    // Add dynamic state loading functionality
    document.addEventListener('DOMContentLoaded', function() {
        const countrySelect = document.getElementById('country_id');
        const stateSelect = document.getElementById('state_id');
        
        // Update states when country changes
        countrySelect.addEventListener('change', function() {
            const countryId = this.value;
            if (countryId) {
                fetch(`/api/dropdown/states/${countryId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Clear existing options
                        stateSelect.innerHTML = '<option value="">Select State</option>';
                        
                        // Add new options
                        data.forEach(state => {
                            const option = document.createElement('option');
                            option.value = state.id;
                            option.text = state.name;
                            option.selected = state.id === {{ old('state_id', isset($city) ? $city->state_id : '') }}
                            stateSelect.appendChild(option);
                        });
                        
                        // Validate state field if required
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

        // Initialize states if country is already selected
        if (countrySelect.value) {
            countrySelect.dispatchEvent(new Event('change'));
        }
    });
</script>

@endsection
