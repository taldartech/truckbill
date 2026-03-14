@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card card-info card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title">{{ $truckType ? 'Edit Truck Type' : 'Create Truck Type' }}</h3>
                </div>
                <form method="POST" action="{{ $truckType ? route('master.truck-types.update', $truckType) : route('master.truck-types.store') }}" class="needs-validation" novalidate>
                    @csrf
                    @if($truckType)
                        @method('PUT')
                    @endif

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $truckType->name ?? '') }}" required maxlength="100">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('master.truck-types.index') }}" class="btn btn-back">Back to List</a>
                        <button type="submit" class="btn btn-brand-primary">Submit form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
@endsection
