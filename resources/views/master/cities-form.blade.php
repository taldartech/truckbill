@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4">
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
                    <label for="name" class="form-label">City Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="{{ old('name', isset($city) ? $city->name : '') }}">
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                
                <div class="col-md-6">
                    <label for="state_id" class="form-label">State *</label>
                    <select class="form-select" id="state_id" name="state_id" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" 
                                    {{ old('state_id', isset($city) ? $city->state_id : '') == $state->id ? 'selected' : '' }}>
                                {{ $state->name }} ({{ $state->country->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('state_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback">Please select a state.</div>
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
</script>

@endsection
