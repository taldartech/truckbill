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

        .text-danger {
            font-size: 0.64rem !important;
            margin-top: 0.1rem !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form action="{{ isset($pump) ? route('master.pump.update', $pump->id) : route('master.pump.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @if(isset($pump))
            @method('PUT')
        @endif

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-fuel-pump me-1"></i> {{ isset($pump) ? 'Edit Pump' : 'Add New Pump' }}
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('master.pump.index') }}" class="btn app-index-header-btn" aria-label="Back to pumps">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-fuel-pump"></i> Pump Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="pump_name" class="form-label">Pump Name *</label>
                                <input type="text" class="form-control" id="pump_name" name="pump_name" required
                                       value="{{ old('pump_name', isset($pump) ? $pump->pump_name : '') }}">
                                @error('pump_name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>

                            <div class="col-md-6">
                                <label for="contact_name" class="form-label">Contact Person *</label>
                                <input type="text" class="form-control" id="contact_name" name="contact_name" required
                                       value="{{ old('contact_name', isset($pump) ? $pump->contact_name : '') }}">
                                @error('contact_name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>

                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number *</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number" required
                                       value="{{ old('contact_number', isset($pump) ? $pump->contact_number : '') }}">
                                @error('contact_number')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>

                            <div class="col-md-6">
                                <label for="remark" class="form-label">Remark</label>
                                <textarea class="form-control" id="remark" name="remark">{{ old('remark', isset($pump) ? $pump->remark : '') }}</textarea>
                                @error('remark')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <a href="{{ route('master.pump.index') }}" class="btn btn-secondary">
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
