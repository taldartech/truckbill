@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .driver-joined-group {
        max-width: 100%;
        min-width: 0;
    }

    .driver-joined-group > .flex-grow-1 {
        min-width: 0;
        overflow: hidden;
    }

    .driver-joined-group .select2-container {
        width: 100% !important;
        max-width: 100%;
        min-width: 0;
    }

    .driver-joined-group .select2-container .select2-selection--single {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        height: 38px;
        display: flex;
        align-items: center;
    }

    .driver-joined-group .select2-selection--single .select2-selection__rendered {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        padding-right: 1.5rem;
    }

    .driver-joined-group .btn {
        flex-shrink: 0;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        white-space: nowrap;
        height: 38px;
        display: inline-flex;
        align-items: center;
    }
</style>
<div class="container mt-4 pb-5 app-index-page app-create-page">
    <form id="truckForm" action="{{ route('trucks.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-truck me-1"></i> Add New Truck
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('trucks.index') }}" class="btn app-index-header-btn" aria-label="Back to trucks">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-truck"></i> Truck Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label for="transporter_id" class="form-label">Truck Owner</label>
                                <select class="form-select" id="transporter_id" name="transporter_id">
                                    <option value="">No Truck Owner</option>
                                    @foreach($transporters as $owner)
                                        <option value="{{ $owner->id }}"
                                            {{ (string) old('transporter_id', $transporter?->id) === (string) $owner->id ? 'selected' : '' }}>
                                            {{ $owner->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="truck_number" class="form-label">Truck Number *</label>
                                <input type="text" class="form-control" placeholder="Enter Truck Number" id="truck_number" name="truck_number" required value="{{ old('truck_number') }}" style="text-transform: uppercase;">
                                @error('truck_number') <div class="text-danger">{{ $message }}</div> @enderror
                                <div class="invalid-feedback">Please enter a truck number.</div>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label for="truck_type" class="form-label">Truck Type *</label>
                                <select class="form-select" id="truck_type" name="truck_type" required>
                                    <option value="">Select</option>
                                    @foreach($truckTypes ?? [] as $type)
                                        <option value="{{ $type->name }}" {{ old('truck_type') == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('truck_type') <div class="text-danger">{{ $message }}</div> @enderror
                                <div class="invalid-feedback">Please select a truck type.</div>
                            </div>
                            <div class="col-md-6 min-w-0">
                                <label for="driver_id" class="form-label">Driver</label>
                                <div class="driver-joined-group d-flex align-items-stretch">
                                    <div class="flex-grow-1">
                                        <select class="form-select" id="driver_id" name="driver_id">
                                            <option value="">No Driver</option>
                                            @foreach($drivers as $driver)
                                                <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->mobile }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <a href="{{ route('drivers.create') }}" class="btn btn-secondary text-nowrap px-2 px-sm-3 d-inline-flex align-items-center" target="_blank" title="Add New Driver">
                                        <i class="bi bi-person-plus-fill me-1"></i><span class="d-none d-sm-inline">Add New Driver</span><span class="d-inline d-sm-none">New Driver</span>
                                    </a>
                                </div>
                                @error('driver_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="registration_date" class="form-label">Registration Date *</label>
                                <input type="date" class="form-control" placeholder="Enter Registration Date" id="registration_date" name="registration_date" value="{{ old('registration_date') }}">
                                @error('registration_date') <div class="text-danger">{{ $message }}</div> @enderror
                                <div class="invalid-feedback">Please select a registration date.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body d-flex flex-row flex-nowrap justify-content-end align-items-center gap-2 p-2 p-sm-3">
                <a href="{{ route('trucks.index') }}" class="btn btn-secondary btn-sm flex-fill flex-sm-grow-0 text-nowrap w-auto">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary btn-sm flex-fill flex-sm-grow-0 text-nowrap w-auto" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Create Truck
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
@parent
    <script>
        // Example starter JavaScript for disabling form submissions if there are invalid fields
        (() => {
            'use strict';

            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            const forms = document.querySelectorAll('.needs-validation');

            // Loop over them and prevent submission
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('truck_number')?.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });

            document.getElementById('truckForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Stop Laravel submit

                let truckNumber = document.getElementById('truck_number').value.trim();
                if (truckNumber === '') {
                    alert('Please enter a Truck Number');
                    return;
                }

                fetch(`/check-truck-number?truck_number=${encodeURIComponent(truckNumber)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            alert('🚫 This Truck Number already exists.');
                            document.getElementById('truck_number').focus();
                        } else {
                            e.target.submit(); // No duplicate → Submit form
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        e.target.submit(); // fallback submit
                    });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.jQuery || typeof window.jQuery.fn.select2 === 'undefined') {
                return;
            }

            const $ = window.jQuery;

            const select2AjaxConfig = (url) => ({
                url: url,
                dataType: 'json',
                delay: 700,
                data: function (params) {
                    return {
                        q: params.term || '',
                        limit: 20
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results || [],
                        pagination: { more: false }
                    };
                },
                transport: function (params, success, failure) {
                    const $element = $(params.element);
                    const previousRequest = $element.data('select2ActiveRequest');
                    if (previousRequest && typeof previousRequest.abort === 'function') {
                        previousRequest.abort();
                    }

                    const request = $.ajax(params);
                    $element.data('select2ActiveRequest', request);
                    request.then(success);
                    request.fail(failure);
                    request.always(function () {
                        $element.removeData('select2ActiveRequest');
                    });

                    return request;
                },
                cache: true
            });

            $('#transporter_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                minimumInputLength: 1,
                placeholder: 'Search owner (type first letter)...',
                language: {
                    inputTooShort: function () {
                        return 'Please type at least 1 character to search';
                    },
                    searching: function () {
                        return 'Searching...';
                    }
                },
                ajax: select2AjaxConfig('/api/truck-owners/search')
            });

            $('#driver_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                minimumInputLength: 1,
                placeholder: 'Search driver (type first letter)...',
                language: {
                    inputTooShort: function () {
                        return 'Please type at least 1 character to search';
                    },
                    searching: function () {
                        return 'Searching...';
                    }
                },
                ajax: select2AjaxConfig('/api/drivers/search')
            });
        });
    </script>
@endsection
