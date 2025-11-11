@extends('layouts.app')

@section('content')

<div class="container px-0">

    <div class="card card-info card-outline mx-auto" style="max-width: 900px;">

        <!-- Header -->
        <div class="card-header text-center">
            <h5 class="card-title mb-0">Register Company</h5>
        </div>

        <form action="{{ route('company.update', $company->id) }}"
              method="POST"
              class="needs-validation"
              novalidate>

            @csrf
            @method('PUT')

            @if(session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            <div class="card-body">
                <div class="row g-3">

                    <!-- Company Name -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Company Name *</label>
                        <input type="text" class="form-control" name="name" required
                               value="{{ old('name', $company->name) }}">
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <!-- GST -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">GST Number</label>
                        <input type="text" class="form-control" name="gst_number"
                               value="{{ old('gst_number', $company->gst_number) }}">
                    </div>

                    <!-- PAN -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">PAN Number</label>
                        <input type="text" class="form-control" name="pan_number"
                               value="{{ old('pan_number', $company->pan_number) }}">
                    </div>

                    <!-- GST Scheme -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">GST Scheme</label>
                        <select class="form-select" name="gst_scheme">
                            <option value="">Select</option>
                            <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                            <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                        </select>
                    </div>

                    <!-- GST Type -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">GST Type *</label>
                        <input type="text" class="form-control" value="Regular" disabled>
                    </div>

                    <!-- Address -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address">{{ old('address', $company->address) }}</textarea>
                    </div>

                    <!-- Country -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Country</label>
                        <select class="form-select" name="country_id" onchange="fetchStates(this.value)">
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}"
                                    {{ old('country_id', $company->country_id) == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- State -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">State</label>
                        <select class="form-select" name="state_id" onchange="fetchCities(this.value)">
                            <option value="">Select State</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}"
                                    {{ old('state_id', $company->state_id) == $state->id ? 'selected' : '' }}>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- City -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">City</label>
                        <select class="form-select" name="city_id">
                            <option value="">Select City</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}"
                                    {{ old('city_id', $company->city_id) == $city->id ? 'selected' : '' }}>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pincode -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Pin Code</label>
                        <input type="text" class="form-control" name="pin_code"
                               value="{{ old('pin_code', $company->pin_code) }}">
                    </div>

                    <!-- Contact Person -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text" class="form-control" name="contact_person"
                               value="{{ old('contact_person', $company->contact_person) }}">
                    </div>

                    <!-- Contact Number -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="text" class="form-control" name="contact_number"
                               value="{{ old('contact_number', $company->contact_number) }}">
                    </div>

                </div>
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="company_type" class="form-label">Company Type</label>
                    <select class="form-select" id="company_type" name="company_type" required>
                        <option value="">Select Company Type</option>
                        <option value="Broker" {{ old('company_type', $company->company_type) == 'Broker' ? 'selected' : '' }}>Broker</option>
                        <option value="Agency" {{ old('company_type', $company->company_type) == 'Agency' ? 'selected' : '' }}>Agency</option>
                        <option value="Both" {{ old('company_type', $company->company_type) == 'Both' ? 'selected' : '' }}>Both</option>
                    </select>
                    @error('company_type') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>

                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="company_logo" class="form-label">Company Logo</label>
                    <input type="file" class="form-control" id="company_logo" name="company_logo">
                    <img src="{{ asset('storage/' . $company->company_logo) }}" alt="Company Logo" style="width: 100px; height: 100px;">
                    @error('company_logo') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="company_slab" class="form-label">Company Slab</label>
                    <select class="form-select" id="company_slab" name="company_slab" required>
                        <option value="{{ old('company_slab', $company->company_slab) }}">Select Company Slab</option>
                        @foreach($companySlabs as $slab)
                            <option value="{{ trim($slab) }}" {{ old('company_slab', $company->company_slab) == trim($slab) ? 'selected' : '' }}>
                                {{ trim($slab) }}%
                            </option>
                        @endforeach
                    </select>

                    @error('company_slab')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Body-->
        <!--begin::Footer-->
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-success" type="submit">Update Company</button>
            <a href="{{ route('company.index') }}" class="btn btn-info">Back to list</a>
        </div>
        <!--end::Footer-->
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
        function fetchStates(countryId) {
            fetch(`/api/states/${countryId}`)
                .then(response => response.json())
                .then(data => {
                    const stateSelect = document.getElementById('state_id');
                    stateSelect.innerHTML = '<option value="">Select State</option>';
                    data.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.id;
                        option.textContent = state.name;
                        stateSelect.appendChild(option);
                    });
                    // Clear city dropdown
                    const citySelect = document.getElementById('city_id');
                    citySelect.innerHTML = '<option value="">Select City</option>';
                })
                .catch(error => console.error('Error fetching states:', error));
        }
        function fetchCities(stateId) {
            fetch(`/api/cities/${stateId}`)
                .then(response => response.json())
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
                .catch(error => console.error('Error fetching cities:', error));
        }
    </script>
    <!--end::JavaScript-->
</div>

@endsection
