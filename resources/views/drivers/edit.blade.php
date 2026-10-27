@extends('layouts.app')

@section('content')
<style>
/* Prevent form expansion on validation errors */
.form-control, .form-select {
    min-height: 38px;
    box-sizing: border-box;
}

.text-danger {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    word-wrap: break-word;
}

/* Ensure consistent column widths on desktop */
@media (min-width: 768px) {
    .row .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

/* Mobile view adjustments */
@media (max-width: 767.98px) {
    .card-header .card-title,
    .card-header .h4 {
        font-size: 0.88rem !important;
        font-weight: 600 !important;
    }

    h5, .h5 {
        font-size: 0.76rem !important;
        font-weight: 700 !important;
        margin-bottom: 0.3rem !important;
    }

    label,
    .form-label,
    [class*="col-"] > label {
        font-size: 0.68rem !important;
        font-weight: 600 !important;
        margin-bottom: 0.12rem !important;
        line-height: 1.25 !important;
        color: #334155 !important;
        display: inline-block !important;
    }

    .form-control, .form-select {
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
    }

    .card-body {
        padding: 0.5rem 0.55rem !important;
    }

    .card-footer {
        padding: 0.45rem 0.55rem !important;
        display: flex !important;
        gap: 0.45rem !important;
        align-items: center !important;
    }

    .mb-3 {
        margin-bottom: 0.35rem !important;
    }

    .btn,
    .btn-success,
    .btn-back,
    .btn-brand-primary,
    .btn-secondary {
        font-size: 0.72rem !important;
        font-weight: 600 !important;
        padding: 0.28rem 0.55rem !important;
        min-height: 29px !important;
        height: auto !important;
        line-height: 1.25 !important;
        border-radius: 0.375rem !important;
        white-space: nowrap !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.25rem !important;
    }

    .btn i,
    .btn-success i,
    .btn-back i,
    .btn-brand-primary i {
        font-size: 0.70rem !important;
        margin: 0 !important;
    }

    .small, small, .text-muted {
        font-size: 0.64rem !important;
    }

    .text-danger {
        font-size: 0.64rem !important;
        margin-top: 0.1rem !important;
    }
}
</style>

<div class="card card-info card-outline mb-4 entity-edit-card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title h4">Edit Driver</div>
    </div>
    <form action="{{ route('drivers.update', $driver->id) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')
        <div class="card-body p-2 p-md-4">
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="name" class="form-label">Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $driver->name) }}" placeholder="Enter Driver Name">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input type="text" class="form-control" id="mobile" name="mobile" value="{{ old('mobile', $driver->mobile) }}" placeholder="Enter 10-digit Mobile Number" maxlength="10">
                    @error('mobile') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="license_number" class="form-label">License Number</label>
                    <input type="text" class="form-control" id="license_number" name="license_number" value="{{ old('license_number', $driver->license_number) }}" placeholder="Enter License Number">
                    @error('license_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $driver->address) }}" placeholder="Enter Address">
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-brand-primary">
                <i class="fas fa-save me-1"></i>Update Driver
            </button>
            <a href="{{ route('drivers.index') }}" class="btn btn-back">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </form>
</div>
@endsection
