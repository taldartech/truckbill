@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">{{ isset($country) ? 'Edit Country' : 'Add New Country' }}</h3>
    </div>
    <form action="{{ isset($country) ? route('master.countries.update', $country->id) : route('master.countries.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @if(isset($country))
            @method('PUT')
        @endif
        
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Country Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="{{ old('name', isset($country) ? $country->name : '') }}">
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                
                <div class="col-md-6">
                    <label for="code" class="form-label">Country Code *</label>
                    <input type="text" class="form-control" id="code" name="code" required 
                           value="{{ old('code', isset($country) ? $country->code : '') }}">
                    @error('code')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Submit form</button>
            <a href="{{ route('master.countries.index') }}" class="btn btn-info">Back to List</a>
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
