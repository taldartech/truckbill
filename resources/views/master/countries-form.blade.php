@extends('layouts.app')

@section('content')
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form action="{{ isset($country) ? route('master.countries.update', $country->id) : route('master.countries.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @if(isset($country))
            @method('PUT')
        @endif

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-globe me-1"></i> {{ isset($country) ? 'Edit Country' : 'Add New Country' }}
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('master.countries.index') }}" class="btn app-index-header-btn" aria-label="Back to countries">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-globe"></i> Country Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2">
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
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <a href="{{ route('master.countries.index') }}" class="btn btn-secondary">
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
</script>

@endsection
