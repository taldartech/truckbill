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
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
<div class="card card-info card-outline mb-4">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Truck</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form id="truckForm" action="{{ route('trucks.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row mb-3">
                <!--begin::Col-->
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
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="truck_number" class="form-label">Truck Number *</label>
                    <input type="text" class="form-control" placeholder="Enter Truck Number" id="truck_number" name="truck_number" required value="{{ old('truck_number') }}">
                    @error('truck_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a truck number.</div>
                </div>
                <!--end::Col-->
            </div>
            <div class="row mb-3">
                <!--begin::Col-->
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
                <!--end::Col-->
                <!--begin::Col-->
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
                        <a href="{{ route('drivers.create') }}" class="btn btn-secondary" target="_blank">Add New Driver</a>
                    </div>
                    @error('driver_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <!--end::Col-->
            </div>
            <div class="row mb-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="registration_date" class="form-label">Registration Date *</label>
                    <input type="date" class="form-control" placeholder="Enter Registration Date" id="registration_date" name="registration_date"  value="{{ old('registration_date') }}">
                    @error('registration_date') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a registration date.</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <div class="card p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <button class="btn btn-brand-primary w-100 mb-2" type="submit">💾 Create Truck</button>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('trucks.index') }}" class="btn btn-back w-100 mb-2">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Body-->
        <!-- Quick Actions Section -->
       
    </form>
    <!--end::Form-->
    <!--begin::JavaScript-->
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
    <!--end::JavaScript-->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
        
        
    
        </div>
    </div>
</div>
@endsection
