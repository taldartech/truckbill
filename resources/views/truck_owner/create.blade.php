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
        <div class="card-title h4">Add New Truck Owner</div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
    <form action="{{ route('truck_owners.store') }}" method="POST" class="needs-validation" novalidate="">
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
            @if($canEditCompany)
                <input type="hidden" name="company_id" value="{{ old('company_id', $userCompanyId) }}">
            @else
                <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
            @endif

            <!-- Basic Information Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Basic Information</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label">Truck Owner Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Enter Truck Owner Name" required>
                        @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="short_name" class="form-label">Short Name</label>
                        <input type="text" class="form-control @error('short_name') is-invalid @enderror" id="short_name" name="short_name" value="{{ old('short_name') }}" placeholder="Enter Short Name">
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
                        <label for="mobile_number" class="form-label">Mobile Number *</label>
                        <input type="tel" class="form-control @error('mobile_number') is-invalid @enderror" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="Enter 10-digit mobile number" maxlength="10" pattern="[0-9]{10}" required>
                        @error('mobile_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 offset-md-4">
                        <input type="hidden" name="is_owner" value="1">
                        <input type="hidden" name="advance_amount" value="0">
                        <label class="form-label">Advance Amount</label>
                        <input type="text" class="form-control" value="₹0.00" readonly>
                        <small class="text-muted d-block">Read-only field</small>
                    </div>
                    
                </div>
            </div>

            <!-- Tax Information Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Tax Information</h5></div></div>
                
                <div class="row mb-3">
                <div class="col-md-4">
                        <label for="gst_number" class="form-label">GST Number</label>
                        <input type="text" class="form-control @error('gst_number') is-invalid @enderror" id="gst_number" name="gst_number" value="{{ old('gst_number') }}" placeholder="Enter GST Number">
                        @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="pan_number" class="form-label">PAN Number *</label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('pan_number') is-invalid @enderror" id="pan_number" name="pan_number" value="{{ old('pan_number') }}" placeholder="Enter PAN Number" required>
                            <a href="https://report.insight.gov.in/reporting-webapp/portal/homePage" target="_blank" class="btn btn-outline-info" title="Check PAN Status">
                                <i class="fas fa-external-link-alt"></i> Check PAN
                            </a>
                        </div>
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
            </div>

            <!-- Business Details Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Business Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label for="total_trucks" class="form-label">Total Trucks</label>
                        <input type="number" class="form-control @error('total_trucks') is-invalid @enderror" id="total_trucks" name="total_trucks" min="0" required value="{{ old('total_trucks', 0) }}" placeholder="Enter total trucks">
                        @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    
                </div>
            </div>

            <!-- Location Details Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Location Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="country_id" class="form-label">Country *</label>
                        <div class="input-group">
                            <select class="form-select" id="country_id" name="country_id" required>
                                <option value="">Select Country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCountryModal" title="Add New Country">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="state_id" class="form-label">State *</label>
                        <div class="input-group">
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
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addStateModal" title="Add New State">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="city_id" class="form-label">City *</label>
                        <div class="input-group">
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
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCityModal" title="Add New City">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                {{-- <div class="row mb-3"> --}}
                    {{-- <div class="col-md-4">
                        <label for="country_id" class="form-label">Country *</label>
                        <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required>
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
                        <select class="form-select @error('state_id') is-invalid @enderror" id="state_id" name="state_id" required>
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
                        <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id" required>
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
                    </div> --}}
                {{-- </div> --}}
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" placeholder="Enter complete address">{{ old('address') }}</textarea>
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
                        <a href="{{ route('truck_owners.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
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
                    <i class="fas fa-check-circle me-2"></i>Confirm Truck Owner Details
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

                            <tr><td class="fw-bold">PAN Number:</td><td id="confirm-pan-number">-</td></tr>
                            <tr><td class="fw-bold">PAN Active:</td><td id="confirm-pan-active">-</td></tr>
                            <tr class="table-warning"><td class="fw-bold">🎯 TDS Percentage:</td><td id="confirm-tds-percentage" class="fw-bold text-danger fs-5">-</td></tr>
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
                        <h6 class="text-primary mb-3"><i class="fas fa-truck me-2"></i>Business Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="fw-bold">Declaration Given:</td><td id="confirm-declaration">-</td></tr>
                            <tr><td class="fw-bold">Total Trucks:</td><td id="confirm-total-trucks">-</td></tr>
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
<div class="modal fade" id="addCountryModal" tabindex="-1" aria-labelledby="addCountryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addCountryForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="addCountryModalLabel">Add Country</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
  
          <div class="modal-body">
            <div id="countryFormErrors" class="alert alert-danger d-none"></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="country_name" class="form-label">Country Name *</label>
                <input type="text" class="form-control" id="country_name" name="name" required>
              </div>
  
              <div class="col-md-6">
                <label for="country_code" class="form-label">Country Code *</label>
                <input type="text" class="form-control" id="country_code" name="code" required>
              </div>
            </div>
          </div>
  
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-info">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit form
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add State Modal -->
<div class="modal fade" id="addStateModal" tabindex="-1" aria-labelledby="addStateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addStateForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="addStateModalLabel">Add State</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
  
          <div class="modal-body">
            <div id="stateFormErrors" class="alert alert-danger d-none"></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="state_name" class="form-label">State Name *</label>
                <input type="text" class="form-control" id="state_name" name="name" required>
              </div>
  
              <div class="col-md-6">
                <label for="state_code" class="form-label">State Code *</label>
                <input type="text" class="form-control" id="state_code" name="code" required maxlength="10">
              </div>
  
              <div class="col-md-12">
                <label for="modal_state_country_id" class="form-label">Country *</label>
                <select class="form-select" id="modal_state_country_id" name="country_id" required>
                  <option value="">Select Country</option>
                  @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
  
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-info">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit form
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add City Modal -->
<div class="modal fade" id="addCityModal" tabindex="-1" aria-labelledby="addCityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addCityForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="addCityModalLabel">Add City</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
  
          <div class="modal-body">
            <div id="cityFormErrors" class="alert alert-danger d-none"></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="modal_city_name" class="form-label">City Name *</label>
                <input type="text" class="form-control" id="modal_city_name" name="name" required>
              </div>

              <div class="col-md-6">
                <label for="modal_city_country_id" class="form-label">Country *</label>
                <select class="form-select" id="modal_city_country_id" name="country_id" required>
                  <option value="">Select Country</option>
                  @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                  @endforeach
                </select>
                
              </div>
              
              <div class="col-md-6">
                <label for="modal_city_state_id" class="form-label">State *</label>
                <select class="form-select" id="modal_city_state_id" name="state_id" required>
                  <option value="">Select State</option>
                </select>
              </div>
            </div>
          </div>
  
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-info">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit form
            </button>
          </div>
        </form>
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

        const panNumber = document.getElementById('pan_number')?.value || '-';
        const panActive = document.querySelector('input[name="pan_active"]:checked')?.value === '1' ? 'Yes' : 'No';
        
        const address = document.getElementById('address')?.value || '-';
        const countrySelect = document.getElementById('country_id');
        const country = countrySelect?.options[countrySelect.selectedIndex]?.text || '-';
        const stateSelect = document.getElementById('state_id');
        const state = stateSelect?.options[stateSelect.selectedIndex]?.text || '-';
        const citySelect = document.getElementById('city_id');
        const city = citySelect?.options[citySelect.selectedIndex]?.text || '-';
        
        const declaration = document.querySelector('input[name="declaration_given"]:checked')?.value === '1' ? 'Yes' : 'No';
        const totalTrucks = document.getElementById('total_trucks')?.value || '0';
        const advance = '0.00'; // Fixed value for truck owners

        // Populate modal with values
        document.getElementById('confirm-name').textContent = name;
        document.getElementById('confirm-short-name').textContent = shortName;
        document.getElementById('confirm-contact-person').textContent = contactPerson;
        document.getElementById('confirm-mobile').textContent = mobile;
        
        document.getElementById('confirm-gst-number').textContent = gstNumber;

        document.getElementById('confirm-pan-number').textContent = panNumber;
        document.getElementById('confirm-pan-active').textContent = panActive;
        
        // Calculate TDS percentage using API
        calculateTdsPercentageFromApi(panNumber, panActive);
        
        document.getElementById('confirm-address').textContent = address;
        document.getElementById('confirm-country').textContent = country;
        document.getElementById('confirm-state').textContent = state;
        document.getElementById('confirm-city').textContent = city;
        
        document.getElementById('confirm-declaration').textContent = declaration;
        document.getElementById('confirm-total-trucks').textContent = totalTrucks;
        document.getElementById('confirm-advance').textContent = '₹' + parseFloat(advance).toFixed(2);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        modal.show();
    }

    // Function to calculate TDS percentage from API
    function calculateTdsPercentageFromApi(panNumber, panActive) {
        const panActiveValue = panActive === 'Yes' ? '1' : '0';
        const declarationGiven = document.querySelector('input[name="declaration_given"]:checked')?.value === '1';
        
        fetch('/api/calculate-truck-owner-tds-percentage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                pan_number: panNumber,
                pan_active: panActiveValue,
                declaration_given: declarationGiven ? '1' : '0'
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('confirm-tds-percentage').textContent = data.tds_percentage + '%';
        })
        .catch(error => {
            console.error('Error calculating TDS percentage:', error);
            document.getElementById('confirm-tds-percentage').textContent = '-';
        });
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
            }
        });
    }



});




    
    // Modal form submissions with AJAX
    $(document).ready(function() {
        // Handle Country Modal Form Submission
        $('#addCountryForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#countryFormErrors');
            const countryName = $('#country_name').val();
            const countryCode = $('#country_code').val();
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');
            
            // Create FormData object
            const formData = new FormData();
            formData.append('name', countryName);
            formData.append('code', countryCode);
            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);
            
            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.countries.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    console.log('Country creation response:', data);
                    if (data && data.success && data.country) {
                        // Add new country to the main dropdown
                        $('#country_id').append(`<option value="${data.country.id}">${data.country.name}</option>`);
                        
                        // Select the newly created country
                        $('#country_id').val(data.country.id).trigger('change');
                        
                        // Close modal and remove backdrop
                        $('#addCountryModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the country.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle State Modal Form Submission
        $('#addStateForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#stateFormErrors');
            const stateName = $('#state_name').val();
            const stateCode = $('#state_code').val();
            const countryId = $('#modal_state_country_id').val();
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');
            
            // Create FormData object
            const formData = new FormData();
            formData.append('name', stateName);
            formData.append('code', stateCode);
            formData.append('country_id', countryId);
            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);
            
            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.states.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    console.log('State creation response:', data);
                    if (data && data.success && data.state) {
                        // Add new state to the main dropdown
                        $('#state_id').append(`<option value="${data.state.id}">${data.state.name}</option>`);
                        
                        // Select the newly created state
                        $('#state_id').val(data.state.id).trigger('change');
                        
                        // Close modal and remove backdrop
                        $('#addStateModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the state.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle City Modal Form Submission
        $('#addCityForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#cityFormErrors');
            const cityName = $('#modal_city_name').val();
            const stateId = $('#modal_city_state_id').val();
          
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            console.log(cityName, stateId, csrfToken);
            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');
            
            // Create FormData object
            const formData = new FormData();
            formData.append('name', cityName);
            formData.append('state_id', stateId);
          

            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);
            
            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.cities.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    console.log('City creation response:', data);
                    if (data && data.success && data.city) {
                        // Add new city to the main dropdown
                        $('#city_id').append(`<option value="${data.city.id}">${data.city.name}</option>`);
                        
                        // Select the newly created city
                        $('#city_id').val(data.city.id).trigger('change');
                        
                        // Close modal and remove backdrop
                        $('#addCityModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the city.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle country change in city modal
        $('#modal_city_country_id').on('change', function() {
            const countryId = $(this).val();
            const stateSelect = $('#modal_city_state_id');
            
            stateSelect.html('<option value="">Select State</option>');
            
            if (countryId) {
                $.get(`/api/states/${countryId}`)
                    .done(function(data) {
                        data.forEach(function(state) {
                            stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                        });
                    })
                    .fail(function() {
                        showErrorMessage('Error loading states');
                    });
            }
        });

        // Function to show success message
        function showSuccessMessage(message) {
            // Remove any existing alerts
            $('.alert-success').remove();
            
            // Create and show success alert
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert after the page title or at the top of the form
            if ($('.card-header').length) {
                $('.card-header').after(alertHtml);
            } else {
                $('form').before(alertHtml);
            }
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('.alert-success').fadeOut();
            }, 5000);
        }

        // Function to show error message
        function showErrorMessage(message) {
            // Remove any existing alerts
            $('.alert-danger').remove();
            
            // Create and show error alert
            const alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert after the page title or at the top of the form
            if ($('.card-header').length) {
                $('.card-header').after(alertHtml);
            } else {
                $('form').before(alertHtml);
            }
        }
    });
</script>
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
            }
        });
    }



});




    
    // Modal form submissions with AJAX
    $(document).ready(function() {
        // Handle Country Modal Form Submission
        $('#addCountryForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#countryFormErrors');
            const countryName = $('#country_name').val();
            const countryCode = $('#country_code').val();
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');
            
            // Create FormData object
            const formData = new FormData();
            formData.append('name', countryName);
            formData.append('code', countryCode);
            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);
            
            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.countries.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    console.log('Country creation response:', data);
                    if (data && data.success && data.country) {
                        // Add new country to the main dropdown
                        $('#country_id').append(`<option value="${data.country.id}">${data.country.name}</option>`);
                        
                        // Select the newly created country
                        $('#country_id').val(data.country.id).trigger('change');
                        
                        // Close modal and remove backdrop
                        $('#addCountryModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the country.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle State Modal Form Submission
        $('#addStateForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#stateFormErrors');
            const stateName = $('#state_name').val();
            const stateCode = $('#state_code').val();
            const countryId = $('#modal_state_country_id').val();
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');
            
            // Create FormData object
            const formData = new FormData();
            formData.append('name', stateName);
            formData.append('code', stateCode);
            formData.append('country_id', countryId);
            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);
            
            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.states.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    console.log('State creation response:', data);
                    if (data && data.success && data.state) {
                        // Add new state to the main dropdown
                        $('#state_id').append(`<option value="${data.state.id}">${data.state.name}</option>`);
                        
                        // Select the newly created state
                        $('#state_id').val(data.state.id).trigger('change');
                        
                        // Close modal and remove backdrop
                        $('#addStateModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the state.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle City Modal Form Submission
        $('#addCityForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            const errorDiv = $('#cityFormErrors');
            const cityName = $('#modal_city_name').val();
            const stateId = $('#modal_city_state_id').val();
          
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            console.log(cityName, stateId, csrfToken);
            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorDiv.addClass('d-none');
            
            // Create FormData object
            const formData = new FormData();
            formData.append('name', cityName);
            formData.append('state_id', stateId);
          

            formData.append('from_modal', '1');
            formData.append('_token', csrfToken);
            
            // Use jQuery AJAX instead of fetch for better FormData handling
            $.ajax({
                url: '{{ route("master.cities.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(data) {
                    console.log('City creation response:', data);
                    if (data && data.success && data.city) {
                        // Add new city to the main dropdown
                        $('#city_id').append(`<option value="${data.city.id}">${data.city.name}</option>`);
                        
                        // Select the newly created city
                        $('#city_id').val(data.city.id).trigger('change');
                        
                        // Close modal and remove backdrop
                        $('#addCityModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        errorDiv.html(errorMessages).removeClass('d-none');
                    } else {
                        errorDiv.html('An error occurred while adding the city.').removeClass('d-none');
                    }
                },
                complete: function() {
                    // Hide loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Handle country change in city modal
        $('#modal_city_country_id').on('change', function() {
            const countryId = $(this).val();
            const stateSelect = $('#modal_city_state_id');
            
            stateSelect.html('<option value="">Select State</option>');
            
            if (countryId) {
                $.get(`/api/states/${countryId}`)
                    .done(function(data) {
                        data.forEach(function(state) {
                            stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                        });
                    })
                    .fail(function() {
                        showErrorMessage('Error loading states');
                    });
            }
        });

        // Function to show success message
        function showSuccessMessage(message) {
            // Remove any existing alerts
            $('.alert-success').remove();
            
            // Create and show success alert
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert after the page title or at the top of the form
            if ($('.card-header').length) {
                $('.card-header').after(alertHtml);
            } else {
                $('form').before(alertHtml);
            }
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('.alert-success').fadeOut();
            }, 5000);
        }

        // Function to show error message
        function showErrorMessage(message) {
            // Remove any existing alerts
            $('.alert-danger').remove();
            
            // Create and show error alert
            const alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert after the page title or at the top of the form
            if ($('.card-header').length) {
                $('.card-header').after(alertHtml);
            } else {
                $('form').before(alertHtml);
            }
        }
    });
</script>
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

    $('#addStateModal, #addCityModal, #addCountryModal').on('shown.bs.modal', function () {
    $('.modal-backdrop').remove();
});
    
    
    
    
    </script>
        

@endsection
