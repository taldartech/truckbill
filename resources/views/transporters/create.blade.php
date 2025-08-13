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

/* Ensure consistent column widths */
.row .col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

/* Prevent card from expanding */
.card {
    overflow: hidden;
}

/* Consistent spacing for form groups */
.mb-3 {
    margin-bottom: 1rem !important;
}
</style>
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
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
            {{-- Company field hidden as requested --}}
            <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
            
            <!-- General Details -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="name" class="form-label">Transport Name *</label>
                    <input placeholder="Enter Transport Name" type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="short_name" class="form-label">Short Name *</label>
                    <input placeholder="Enter Short Name" type="text" class="form-control" id="short_name" name="short_name" value="{{ old('short_name') }}">
                    @error('short_name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="contact_person_name" class="form-label">Contact Person Name</label>
                    <input type="text" class="form-control @error('contact_person_name') is-invalid @enderror" id="contact_person_name" name="contact_person_name" value="{{ old('contact_person_name') }}" placeholder="Enter Contact Person Name">
                    @error('contact_person_name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input placeholder="Enter GST Number" type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
                    @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="pan_number" class="form-label">PAN Number *</label>
                    <input placeholder="Enter PAN Number" type="text" class="form-control" id="pan_number" name="pan_number" required value="{{ old('pan_number') }}">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
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
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="mobile_number" class="form-label">Mobile Number *</label>
                    <input type="text" class="form-control" id="mobile_number" name="mobile_number" required value="{{ old('mobile_number') }}" placeholder="Enter Mobile Number">
                    @error('mobile_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <!-- Empty column for consistent 3-column layout -->
                </div>
                <div class="col-md-4">
                    <label class="form-label">Advance Amount</label>
                    <div class="form-control-plaintext bg-light border rounded px-3 py-2">
                        <strong>₹ 0.00</strong>
                        <small class="text-muted d-block">Read-only field</small>
                    </div>
                    <input type="hidden" name="advance_amount" value="0">
                </div>
            </div>
            

            <!-- Location Details Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Location Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="country_id" class="form-label">Country *</label>
                        <select class="form-select" id="country_id" name="country_id" required>
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
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
                    </div>
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
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter complete address">{{ old('address') }}</textarea>
                        @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <!-- Quick Actions Section -->
            <div class="card p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <button class="btn btn-success w-100 mb-2" type="button" onclick="showConfirmationModal()">💾 Submit Form</button>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('transporters.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="confirmationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Confirm Transporter Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please review the details below before submitting the form.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Basic Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">Name:</td><td id="confirm-name">-</td></tr>
                            <tr><td class="fw-bold">Short Name:</td><td id="confirm-short-name">-</td></tr>
                            <tr><td class="fw-bold">Contact Person:</td><td id="confirm-contact-person">-</td></tr>
                            <tr><td class="fw-bold">Mobile Number:</td><td id="confirm-mobile">-</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-file-alt me-2"></i>Tax Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">GST Number:</td><td id="confirm-gst-number">-</td></tr>
                            <tr><td class="fw-bold">GST Type:</td><td id="confirm-gst-type">-</td></tr>
                            <tr><td class="fw-bold">PAN Number:</td><td id="confirm-pan-number">-</td></tr>
                            <tr><td class="fw-bold">PAN Active:</td><td id="confirm-pan-active">-</td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-map-marker-alt me-2"></i>Location Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">Address:</td><td id="confirm-address">-</td></tr>
                            <tr><td class="fw-bold">Country:</td><td id="confirm-country">-</td></tr>
                            <tr><td class="fw-bold">State:</td><td id="confirm-state">-</td></tr>
                            <tr><td class="fw-bold">City:</td><td id="confirm-city">-</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-cog me-2"></i>Other Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">Is Owner:</td><td id="confirm-is-owner">-</td></tr>
                            <tr><td class="fw-bold">Declaration Given:</td><td id="confirm-declaration">-</td></tr>
                            <tr><td class="fw-bold">Advance Amount:</td><td id="confirm-advance">-</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="submitForm()">
                    <i class="fas fa-check me-2"></i>Confirm & Submit
                </button>
            </div>
        </div>
    </div>
</div>
   
    <script>
      // Function to show confirmation modal with form data
      function showConfirmationModal() {
          // First validate the form
          const form = document.querySelector('.needs-validation');
          if (!form.checkValidity()) {
              form.classList.add('was-validated');
              return;
          }

          // Get form values with safe access
          const name = document.getElementById('name')?.value || '-';
          const shortName = document.getElementById('short_name')?.value || '-';
          const contactPerson = document.getElementById('contact_person_name')?.value || '-';
          const mobile = document.getElementById('mobile_number')?.value || '-';
          
          const gstNumber = document.getElementById('gst_number')?.value || '-';
          const gstType = document.getElementById('gst_type')?.value || '-';
          const panNumber = document.getElementById('pan_number')?.value || '-';
          const panActive = document.querySelector('input[name="pan_active"]:checked')?.value === '1' ? 'Yes' : 'No';
          
          const address = document.getElementById('address')?.value || '-';
          const countrySelect = document.getElementById('country_id');
          const country = countrySelect?.options[countrySelect.selectedIndex]?.text || '-';
          const stateSelect = document.getElementById('state_id');
          const state = stateSelect?.options[stateSelect.selectedIndex]?.text || '-';
          const citySelect = document.getElementById('city_id');
          const city = citySelect?.options[citySelect.selectedIndex]?.text || '-';
          
          const isOwner = document.querySelector('input[name="is_owner"]:checked')?.value === '1' ? 'Yes' : 'No';
          const declaration = document.querySelector('input[name="declaration_given"]:checked')?.value === '1' ? 'Yes' : 'No';
          const advance = document.querySelector('input[name="advance_amount"]')?.value || '0';

          // Populate modal with values
          document.getElementById('confirm-name').textContent = name;
          document.getElementById('confirm-short-name').textContent = shortName;
          document.getElementById('confirm-contact-person').textContent = contactPerson;
          document.getElementById('confirm-mobile').textContent = mobile;
          
          document.getElementById('confirm-gst-number').textContent = gstNumber;
          document.getElementById('confirm-gst-type').textContent = gstType.charAt(0).toUpperCase() + gstType.slice(1);
          document.getElementById('confirm-pan-number').textContent = panNumber;
          document.getElementById('confirm-pan-active').textContent = panActive;
          
          document.getElementById('confirm-address').textContent = address;
          document.getElementById('confirm-country').textContent = country;
          document.getElementById('confirm-state').textContent = state;
          document.getElementById('confirm-city').textContent = city;
          
          document.getElementById('confirm-is-owner').textContent = isOwner;
          document.getElementById('confirm-declaration').textContent = declaration;
          document.getElementById('confirm-advance').textContent = '₹' + parseFloat(advance).toFixed(2);

          // Show modal
          const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
          modal.show();
      }

      // Function to submit the form after confirmation
      function submitForm() {
          const form = document.querySelector('.needs-validation');
          
          // Hide modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
          modal.hide();
          
          // Submit form
          form.submit();
      }

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
              // Allow form submission since we handle validation in showConfirmationModal
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
