@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title">{{ isset($state) ? 'Edit State' : 'Add New State' }}</h3>
    </div>
    <form action="{{ isset($state) ? route('master.states.update', $state->id) : route('master.states.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @if(isset($state))
            @method('PUT')
        @endif
        
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">State Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="{{ old('name', isset($state) ? $state->name : '') }}">
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                
                <div class="col-md-6">
                    <label for="country_id" class="form-label">Country *</label>
                    <select class="form-select" id="country_id" name="country_id" required>
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" 
                                    {{ old('country_id', isset($state) ? $state->country_id : '') == $country->id ? 'selected' : '' }}>
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
                    <label for="code" class="form-label">Code *</label>
                    <input type="text" class="form-control" id="code" name="code" 
                           value="{{ old('code', isset($state) ? $state->code : '') }}" required>
                    @error('code')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Submit form</button>
            <a href="{{ route('master.states.index') }}" class="btn btn-info">Back to List</a>
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
</script>

@endsection
