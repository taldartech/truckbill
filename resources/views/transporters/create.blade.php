@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="max-width: 950px;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="card-title h4">Add New Transporter</div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
    <form action="{{ route('transporters.store') }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        @php
            $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
            $userCompanyId = auth()->user()->company_id ?? null;
        @endphp
        @error('registered_truck_id')
            <div class="text-danger">{{ $message }}</div>
        @enderror
        <div class="card-body">
            <!-- General Details -->
            <div class="row mb-3">
                <div class="col-md-6">
                    {{-- <label for="company_id" class="form-label">Company *</label>
                    @if($canEditCompany)
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">Select</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
                        <div class="form-control-plaintext">
                            {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
                        </div>
                    @endif
                    @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror --}}
                    <label for="company_id" class="form-label">Company *</label>
                    @if($canEditCompany)
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">Select</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
                        <div class="form-control-plaintext">
                            {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
                        </div>
                    @endif
                    @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="name" class="form-label">Transport Name *</label>
                    <input placeholder="Enter Transport Name" type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
               
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="short_name" class="form-label">Short Name *</label>
                    <input placeholder="Enter Short Name" type="text" class="form-control" id="short_name" name="short_name" value="{{ old('short_name') }}">
                    @error('short_name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="gst_number" class="form-label">GST Number</label>
            <input placeholder="Enter GST Number" type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
            @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                {{-- <div class="col-md-6">
                    <label for="gst_type" class="form-label">GST Type *</label>
                    <input type="text" class="form-control" id="gst_type" name="gst_type" value="Regular" disabled>
                    @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
                </div> --}}
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input placeholder="Enter PAN Number" type="text" class="form-control" id="pan_number" name="pan_number" required value="{{ old('pan_number') }}">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">PAN is Active *</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="pan_active" id="pan_active_yes" value="1" {{ old('pan_active') === '1' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="pan_active_yes">Yes</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="pan_active" id="pan_active_no" value="0" {{ old('pan_active') === '0' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="pan_active_no">No</label>
                    </div>
                    @error('pan_active') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
            <!-- Additional Details Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Additional Details</h5></div></div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Declaration Given? *</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="declaration_given" id="decl_yes" value="1" {{ old('declaration_given') === '1' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="decl_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="declaration_given" id="decl_no" value="0" {{ old('declaration_given') === '0' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="decl_no">No</label>
                        </div>
                        @error('declaration_given') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="total_trucks" class="form-label">Total Trucks *</label>
                        <input type="number" class="form-control" id="total_trucks" name="total_trucks" min="0" required value="{{ old('total_trucks', 0) }}" placeholder="Enter total trucks">
                        @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="advance_amount" class="form-label">Advance Amount</label>
                        <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="{{ old('advance_amount', 0) }}" placeholder="Enter advance amount">
                        @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="card mb-4 p-3 bg-light">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="country_id" class="form-label">Country * </label>
                    <select class="form-select" id="country_id" name="country_id" required>
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a country.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="state_id" class="form-label">State *</label>
                    <select class="form-select" id="state_id" name="state_id" required>
                        <option value="">Select State</option>
                        @if(old('country_id'))
                            @foreach($states as $state)
                                <option value="{{ $state->id }}" {{ old('state_id') == $state->id ? 'selected' : '' }}>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a state.</div>
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-md-4">
                    <label for="city_id" class="form-label">City *</label>
                    <select class="form-select" id="city_id" name="city_id" required>
                        <option value="">Select City</option>
                        @if(old('state_id'))
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ old('city_id') == $city->id ? 'selected' : '' }}>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a city.</div>
                </div>
                <!--end::Col-->
            </div>  
            <div class="row mb-3">          
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input type="text" class="form-control" id="mobile" name="mobile_number" required value="{{ old('mobile_number') }}">
                    @error('mobile_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a valid mobile number.</div>
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an address.</div>
                </div>
                <!--end::Col-->
            </div>
            </div>
            <!-- Quick Actions Section -->
            <div class="card p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <button class="btn btn-success w-100 mb-2" type="submit">💾 Submit Form</button>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('transporters.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
   
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
  </div>
{{-- <div class="container mt-4">
    <h2>Add New Transporter</h2>
    <form action="{{ route('transporters.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @php
    $canEditCompany = auth()->check() && auth()->user()->userRole->name === 'super_admin';
    $userCompanyId = auth()->user()->company_id ?? null;
@endphp
        @error('registered_truck_id')
            <div class="text-danger">{{ $message }}</div>
        @enderror
        <div class="mb-3">
            <label for="company_id" class="form-label">Company *</label>
            @if($canEditCompany)
                <select class="form-select" id="company_id" name="company_id" required>
                    <option value="">Select</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
                <div class="form-control-plaintext">
                    {{ $companies->firstWhere('id', $userCompanyId)->name ?? '' }}
                </div>
            @endif
            @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        @php
            $companyIdError = session('errors') ? session('errors')->first('company_id') : null;
        @endphp
        @if($companyIdError)
            <div class="alert alert-danger">
                {{ $companyIdError }}
            </div>
        @endif
        <div class="mb-3">
            <label for="name" class="form-label">Name *</label>
            <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
        <label for="gst_number" class="form-label">GST Number</label>
            <input type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
            @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="gst_type" class="form-label">GST Type *</label>
            <select class="form-select" id="gst_type" name="gst_type" required>
                <option value="">Select</option>
                <option value="composition" {{ old('gst_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                <option value="regular" {{ old('gst_type') == 'regular' ? 'selected' : '' }}>Regular</option>
            </select>
            @error('gst_type') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="pan_number" class="form-label">PAN Number</label>
            <input type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number') }}">
            @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Type *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_owner" id="owner" value="1" {{ old('is_owner') === '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="owner">Owner</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_owner" id="agency" value="0" {{ old('is_owner') === '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="agency">Agency</label>
            </div>
            @error('is_owner') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Declaration Given? *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="declaration_given" id="decl_yes" value="1" {{ old('declaration_given') === '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="decl_yes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="declaration_given" id="decl_no" value="0" {{ old('declaration_given') === '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="decl_no">No</label>
            </div>
            @error('declaration_given') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="total_trucks" class="form-label">Total Trucks *</label>
            <input type="number" class="form-control" id="total_trucks" name="total_trucks" min="0" required value="{{ old('total_trucks', 0) }}">
            @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        {{-- <div class="mb-3">
            <label for="registered_truck_id" class="form-label">Registered Truck</label>
            <input type="text" class="form-control" id="registered_truck_id" name="registered_truck_id" readonly value="">
            @error('registered_truck_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div> --}}
        {{-- <div class="mb-3">
            <label for="advance_amount" class="form-label">Advance Amount</label>
            <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="{{ old('advance_amount', 0) }}">
            @error('advance_amount') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary">Create Transporter</button>
        <a href="{{ route('transporters.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form> --}}
{{-- </div> --}}

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

    // Country, State, City cascading selects
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');

    const oldCountry = "{{ old('country_id') }}";
    const oldState = "{{ old('state_id') }}";
    const oldCity = "{{ old('city_id') }}";

    countrySelect.addEventListener('change', function() {
    const countryId = this.value;
    stateSelect.length = 0;
    stateSelect.innerHTML = '<option value="">Select State</option>';
    citySelect.length = 0;
    citySelect.innerHTML = '<option value="">Select City</option>';
    if (!oldCountry && !oldState){
    if (countryId) {
        fetch(`/api/states/${countryId}`)
            .then(response => response.json())
            .then(states => {
                states.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.id;
                    option.textContent = state.name;
                    stateSelect.appendChild(option);
                });
                stateSelect.required = true;
            });
    }
    stateSelect.addEventListener('change', function() {
    const stateId = this.value;
    citySelect.length = 0;
    citySelect.innerHTML = '<option value="">Select City</option>';
    if (!oldState && !oldCity){
    if (stateId) {
        fetch(`/api/cities/${stateId}`)
            .then(response => response.json())
            .then(cities => {
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
                citySelect.required = true;
            });
    }
});
}



});



</script>
@endsection
