
@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
<div class="card card-info card-outline mb-4">
    <div class="card-header"><div class="card-title">Edit company</div></div>
    <!--begin::Form-->
    <form action="{{ route('company.update', $company->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Company Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $company->name) }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="short_name" class="form-label">Short Name</label>
                    <input
                        type="text"
                        class="form-control"
                        id="short_name"
                        name="short_name"
                        value="{{ $company->short_name }}"
                        disabled>
                    @error('short_name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input type="text" class="form-control" id="gst_number" name="gst_number"  value="{{ old('gst_number', $company->gst_number) }}">
                    @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number" required value="{{ old('pan_number', $company->pan_number) }}">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="gst_scheme" class="form-label">GST Scheme</label>
                    @if($hasInvoicesInFy ?? false)
                        <input type="hidden" name="gst_scheme" value="{{ old('gst_scheme', $company->gst_scheme) }}">
                        <select class="form-select" id="gst_scheme" disabled>
                            <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                            <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                        </select>
                        <small class="form-text text-warning">
                            <i class="fas fa-lock"></i> Cannot change GST scheme. Invoices have been created for the current financial year (April {{ $fyStart->year }} - March {{ $fyEnd->year }}).
                        </small>
                    @else
                        <select class="form-select" id="gst_scheme" name="gst_scheme" required value="{{ old('gst_scheme', $company->gst_scheme) }}">
                            <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                            <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                        </select>
                    @endif
                    @error('gst_scheme') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                 <div class="col-md-6">
                    <label for="gst_type" class="form-label">GST Type *</label>
                    <input type="text" class="form-control" id="gst_type" name="gst_type" required value="Regular" disabled>
                    @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" required>{{ old('address', $company->address) }}</textarea>
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="country_id" class="form-label">Country</label>
                    <select class="form-select" id="country_id" name="country_id" required onchange="fetchStates(this.value)">
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option {{ old('country_id', $company->country_id) == $country->id ? 'selected' : '' }} value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="state_id" class="form-label">State</label>
                    <select class="form-select" id="state_id" name="state_id" required onchange="fetchCities(this.value)">
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option {{ (string) old('state_id', $company->state_id) === (string) $state->id ? 'selected' : '' }} value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="city_id" class="form-label">City</label>
                    <select class="form-select" id="city_id" name="city_id" required>
                        <option value="">Select City</option>
                        @foreach($cities as $city)
                            <option {{ (string) old('city_id', $company->city_id) === (string) $city->id ? 'selected' : '' }} value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="pin_code" class="form-label">Pin Code</label>
                    <input type="text" class="form-control" id="pin_code" name="pin_code" required value="{{ old('pin_code', $company->pin_code) }}">
                    @error('pin_code') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" required value="{{ old('contact_person', $company->contact_person) }}">
                    @error('contact_person') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" required value="{{ old('contact_number', $company->contact_number) }}">
                    @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
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
            </div>
            <div class="row g-2 mt-1">
                <div class="col-12">
                    <div class="border rounded px-3 py-2 bg-light">
                        <div class="small fw-semibold text-muted text-uppercase mb-2">
                            <i class="fas fa-university me-1"></i>Bank details
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label for="bank" class="form-label">Bank</label>
                                <input type="text" class="form-control" id="bank" name="bank"
                                    value="{{ old('bank', $company->bank) }}" placeholder="Bank name">
                                @error('bank') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="account_number" class="form-label">Account number</label>
                                <input type="text" class="form-control" id="account_number" name="account_number"
                                    value="{{ old('account_number', $company->account_number) }}" placeholder="Account number">
                                @error('account_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="ifsc" class="form-label">IFSC</label>
                                <input type="text" class="form-control" id="ifsc" name="ifsc"
                                    value="{{ old('ifsc', $company->ifsc) }}" placeholder="e.g. SBIN0001234" maxlength="11">
                                @error('ifsc') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="company_logo" class="form-label">Company Logo</label>
                    <input type="file" class="form-control" id="company_logo" name="company_logo">
                    <img src="{{ asset('storage/' . $company->company_logo) }}" alt="Company Logo" style="width: 100px; height: 100px;">
                    @error('company_logo') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="gst_slab" class="form-label">GST Slab</label>
                    <select class="form-select" id="gst_slab" name="gst_slab" required>
                        <option value="{{ old('gst_slab', $company->gst_slab) }}">Select GST Slab</option>
                        @foreach($companySlabs as $slab)
                            <option value="{{ trim($slab) }}" {{ old('gst_slab', $company->gst_slab) == trim($slab) ? 'selected' : '' }}>
                                {{ trim($slab) }}%
                            </option>
                        @endforeach
                    </select>

                    @error('gst_slab')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">Looks good!</div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-success" type="submit">Update Company</button>
            <a href="{{ route('company.index') }}" class="btn btn-brand-primary">Back to list</a>
        </div>
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
    </div>
</div>
@endsection
