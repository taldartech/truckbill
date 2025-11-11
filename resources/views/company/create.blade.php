@extends('layouts.app')

@section('content')

<div class="container px-2.3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9 px-0">
            <div class="card card-info card-outline mb-4">
                <!--begin::Header-->
                <div class="card-header">
                    <h5 class="card-title mb-0">Register Company</h5>
                </div>
                <!--end::Header-->

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!--begin::Form-->
                <form action="{{ route('company.store') }}" method="POST" class="needs-validation" novalidate="">
                    @csrf
                    <!--begin::Body-->
                    <div class="card-body">
                        <!--begin::Row-->
                        <div class="row g-3">
                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label fw-medium">Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" required value="{{ old('name') }}" placeholder="Enter company name">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="gst_number" class="form-label fw-medium">GST Number</label>
                                <input type="text" class="form-control @error('gst_number') is-invalid @enderror" id="gst_number" name="gst_number" value="{{ old('gst_number') }}" placeholder="Enter GST number" maxlength="15">
                                @error('gst_number')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="pan_number" class="form-label fw-medium">PAN Number</label>
                                <input type="text" class="form-control @error('pan_number') is-invalid @enderror" id="pan_number" name="pan_number" value="{{ old('pan_number') }}" placeholder="Enter PAN number" maxlength="10">
                                @error('pan_number')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="gst_scheme" class="form-label fw-medium">GST Scheme</label>
                                <select class="form-select @error('gst_scheme') is-invalid @enderror" id="gst_scheme" name="gst_scheme">
                                    <option value="">Select GST Scheme</option>
                                    <option value="reverse charge" {{ old('gst_scheme') == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                                    <option value="forward charge" {{ old('gst_scheme') == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                                </select>
                                @error('gst_scheme')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="gst_type_display" class="form-label fw-medium">GST Type <span class="text-danger">*</span></label>

                                <!-- VISIBLE readonly input for user -->
                                <input type="text" id="gst_type_display" class="form-control bg-light" value="{{ old('gst_type_display', 'Regular') }}" readonly>

                                <!-- HIDDEN input that actually submits the value expected by validator -->
                                <!-- Change 'regular' below if your validator expects a different token (e.g., 'Regular') -->
                                <input type="hidden" name="gst_type" value="{{ old('gst_type', 'regular') }}">

                                @error('gst_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="address" class="form-label fw-medium">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" placeholder="Enter company address">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="country_id" class="form-label fw-medium">Country</label>
                                <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id" onchange="fetchStates(this.value)">
                                    <option value="">Select Country</option>
                                    @foreach(\App\Models\Country::all() as $country)
                                        <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="state_id" class="form-label fw-medium">State</label>
                                <select class="form-select @error('state_id') is-invalid @enderror" id="state_id" name="state_id" onchange="fetchCities(this.value)">
                                    <option value="">Select State</option>
                                </select>
                                @error('state_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="city_id" class="form-label fw-medium">City</label>
                                <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id">
                                    <option value="">Select City</option>
                                </select>
                                @error('city_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="pin_code" class="form-label fw-medium">Pin Code</label>
                                <input type="text" class="form-control @error('pin_code') is-invalid @enderror" id="pin_code" name="pin_code" value="{{ old('pin_code') }}" placeholder="Enter pin code" maxlength="6">
                                @error('pin_code')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="contact_person" class="form-label fw-medium">Contact Person</label>
                                <input type="text" class="form-control @error('contact_person') is-invalid @enderror" id="contact_person" name="contact_person" value="{{ old('contact_person') }}" placeholder="Enter contact person name">
                                @error('contact_person')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col-->
                            <div class="col-12 col-md-6">
                                <label for="contact_number" class="form-label fw-medium">Contact Number</label>
                                <input type="tel" class="form-control @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="Enter contact number" maxlength="10">
                                @error('contact_number')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                    </div>
                    <!--end::Body-->

                    <!--begin::Footer-->
                    <div class="card-footer">
                        <div class="d-grid d-sm-flex gap-2">
                            <button class="btn btn-info" type="submit">
                                <i class="bi bi-check-circle"></i> Register Company
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                    <!--end::Footer-->
                </form>
                <!--end::Form-->
            </div>
        </div>
    </div>

<script>
    // Form validation
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

    // Fetch states based on country
    function fetchStates(countryId) {
        if (!countryId) {
            document.getElementById('state_id').innerHTML = '<option value="">Select State</option>';
            document.getElementById('city_id').innerHTML = '<option value="">Select City</option>';
            return;
        }

        fetch(`/api/states/${countryId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                const stateSelect = document.getElementById('state_id');
                stateSelect.innerHTML = '<option value="">Select State</option>';

                data.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.id;
                    option.textContent = state.name;
                    stateSelect.appendChild(option);
                });

                const citySelect = document.getElementById('city_id');
                citySelect.innerHTML = '<option value="">Select City</option>';
            })
            .catch(error => {
                console.error('Error fetching states:', error);
                alert('Failed to load states. Please try again.');
            });
    }

    // Fetch cities based on state
    function fetchCities(stateId) {
        if (!stateId) {
            document.getElementById('city_id').innerHTML = '<option value="">Select City</option>';
            return;
        }

        fetch(`/api/cities/${stateId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                const citySelect = document.getElementById('city_id');
                citySelect.innerHTML = '<option value="">Select City</option>';

                data.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching cities:', error);
                alert('Failed to load cities. Please try again.');
            });
    }

    // Input formatting
    document.getElementById('pan_number')?.addEventListener('input', function(e) {
        this.value = this.value.toUpperCase();
    });

    document.getElementById('gst_number')?.addEventListener('input', function(e) {
        this.value = this.value.toUpperCase();
    });

    document.getElementById('contact_number')?.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    document.getElementById('pin_code')?.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>
@endsection
