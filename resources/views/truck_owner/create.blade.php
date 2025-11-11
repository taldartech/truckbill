@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
@endpush

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
    <div class="card-header">
        <div class="card-title h4">Add New Truck Owner</div>
    </div>
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

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Basic Information</h5></div></div>
                <div class="row mb-3">
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <label for="name" class="form-label">Truck Owner Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Enter Truck Owner Name" required>
                        @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <label for="contact_person_name" class="form-label">Contact Person Name</label>
                        <input type="text" class="form-control @error('contact_person_name') is-invalid @enderror" id="contact_person_name" name="contact_person_name" value="{{ old('contact_person_name') }}" placeholder="Enter Contact Person Name">
                        @error('contact_person_name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4">
                        <label for="mobile_number" class="form-label">Mobile Number *</label>
                        <input type="tel" class="form-control @error('mobile_number') is-invalid @enderror" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="Enter 10-digit mobile number" maxlength="10" pattern="[0-9]{10}" required>
                        @error('mobile_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    
                </div>
            </div>

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Tax Information</h5></div></div>
                
                <div class="row mb-3">
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <label for="gst_number" class="form-label">GST Number</label>
                        <input type="text" class="form-control @error('gst_number') is-invalid @enderror" id="gst_number" name="gst_number" value="{{ old('gst_number') }}" placeholder="Enter GST Number">
                        @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <label for="pan_number" class="form-label">PAN Number *</label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('pan_number') is-invalid @enderror" id="pan_number" name="pan_number" value="{{ old('pan_number') }}" placeholder="Enter PAN Number" required>
                            <a href="https://report.insight.gov.in/reporting-webapp/portal/homePage" target="_blank" class="btn btn-outline-info" title="Check PAN Status">
                                <i class="fas fa-external-link-alt"></i> Check PAN
                            </a>
                        </div>
                        @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4">
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

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Business Details</h5></div></div>
                <div class="row mb-3">
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
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
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4">
                        <label for="total_trucks" class="form-label">Total Trucks</label>
                        <input type="number" class="form-control @error('total_trucks') is-invalid @enderror" id="total_trucks" name="total_trucks" min="0" required value="{{ old('total_trucks', 0) }}" placeholder="Enter total trucks">
                        @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    
                </div>
            </div>

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Location Details</h5></div></div>
                <div class="row mb-3">
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
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
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
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
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4">
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
                
                <div class="row mb-3">
                    {{-- RESPONSIVE FIX: col-12 --}}
                    <div class="col-12">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" placeholder="Enter complete address">{{ old('address') }}</textarea>
                        @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="card p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Quick Actions</h5></div></div>
                <div class="row g-2">
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4">
                        <button class="btn btn-success w-100 mb-2" type="button" onclick="showConfirmationModal()">💾 Submit Form</button>
                    </div>
                    {{-- RESPONSIVE FIX: col-12 col-lg-4 --}}
                    <div class="col-12 col-lg-4">
                        <a href="{{ route('truck_owners.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

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
@endsection

{{-- 
======================================================================
== JAVASCRIPT FIX: 
== All your script tags were combined into one @push('scripts') block.
== Duplicate validation scripts were removed.
== Conflicting modal scripts were resolved.
======================================================================
--}}
@push('scripts')

{{-- Script for Confirmation Modal & Validation --}}
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
        
        // Set loading state
        const tdsEl = document.getElementById('confirm-tds-percentage');
        tdsEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Calculating...';
        
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
            tdsEl.textContent = data.tds_percentage + '%';
        })
        .catch(error => {
            console.error('Error calculating TDS percentage:', error);
            tdsEl.textContent = '-';
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

    // Bootstrap validation IIFE
    (() => {
        'use strict';

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');

        // Loop over them and prevent submission
        Array.from(forms).forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    // This listener is a fallback, the main logic is in showConfirmationModal
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

{{-- Script for Modal AJAX Submissions --}}
<script>
    // Helper functions for modal management
    function updateCountryDropdowns(country) {
        const dropdowns = ['country_id', 'modal_state_country_id', 'modal_city_country_id'];
        dropdowns.forEach(id => {
            const select = document.getElementById(id);
            if (select && !select.querySelector(`option[value="${country.id}"]`)) {
                const option = document.createElement('option');
                option.value = country.id;
                option.textContent = country.name;
                select.appendChild(option);
            }
        });
    }
    
    function updateStateDropdowns(state) {
        const dropdowns = ['state_id', 'modal_city_state_id'];
        dropdowns.forEach(id => {
            const select = document.getElementById(id);
             // Don't just append, check if it belongs to the selected country first
            // The dynamic-dropdowns.js will handle populating it.
            // We just need to make sure the *new* state is an option
            const mainStateSelect = document.getElementById('state_id');
            if(mainStateSelect && !mainStateSelect.querySelector(`option[value="${state.id}"]`)) {
                const option = document.createElement('option');
                option.value = state.id;
                option.textContent = state.name;
                mainStateSelect.appendChild(option);
            }
        });
    }
    
    function updateCityDropdown(city) {
        const select = document.getElementById('city_id');
            if (select && !select.querySelector(`option[value="${city.id}"]`)) {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                select.appendChild(option);
            }
    }
    
    function closeModalClean(modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            if(bsModal) {
                bsModal.hide();
            } else {
                $(modalEl).modal('hide');
            }
            
            // Reset form inside modal
            const form = modalEl.querySelector('form');
            if(form) form.reset();
            
            // Clear errors
            const errorDiv = modalEl.querySelector('.alert-danger');
            if(errorDiv) errorDiv.classList.add('d-none');

            // Must remove backdrop manually on AJAX close
            modalEl.addEventListener('hidden.bs.modal', function cleanup() {
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                modalEl.removeEventListener('hidden.bs.modal', cleanup);
            }, { once: true });
        }
    }

    // Fresh approach using fetch API for modal submissions
    document.addEventListener('DOMContentLoaded', function() {
        
        // Country Modal Handler
        const countryForm = document.getElementById('addCountryForm');
        if (countryForm) {
            countryForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const errorDiv = document.getElementById('countryFormErrors');
                
                submitBtn.disabled = true;
                spinner?.classList.remove('d-none');
                errorDiv?.classList.add('d-none');
                
                try {
                    const formData = new FormData(this);
                    formData.append('from_modal', '1');
                    
                    const response = await fetch('{{ route("master.countries.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success && data.country) {
                        updateCountryDropdowns(data.country);
                        document.getElementById('country_id').value = data.country.id;
                        document.getElementById('country_id').dispatchEvent(new Event('change'));
                        closeModalClean('addCountryModal');
                        this.reset();
                    } else {
                        if (response.status === 422 && data.errors) {
                            let errorMessages = Object.values(data.errors).flat().join('<br>');
                            throw new Error(errorMessages);
                        }
                        throw new Error(data.message || 'Failed to add country');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = error.message || 'An error occurred while adding the country.';
                        errorDiv.classList.remove('d-none');
                    }
                } finally {
                    submitBtn.disabled = false;
                    spinner?.classList.add('d-none');
                }
            });
        }

        // State Modal Handler
        const stateForm = document.getElementById('addStateForm');
        if (stateForm) {
            stateForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const errorDiv = document.getElementById('stateFormErrors');
                
                submitBtn.disabled = true;
                spinner?.classList.remove('d-none');
                errorDiv?.classList.add('d-none');
                
                try {
                    const formData = new FormData(this);
                    formData.append('from_modal', '1');
                    
                    const response = await fetch('{{ route("master.states.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success && data.state) {
                        updateStateDropdowns(data.state);
                        
                        // Set main form's country and state, trigger change
                        document.getElementById('country_id').value = data.state.country_id;
                        document.getElementById('country_id').dispatchEvent(new Event('change'));
                        
                        setTimeout(() => {
                            document.getElementById('state_id').value = data.state.id;
                            document.getElementById('state_id').dispatchEvent(new Event('change'));
                        }, 500); 
                        
                        closeModalClean('addStateModal');
                        this.reset();
                    } else {
                        if (response.status === 422 && data.errors) {
                            let errorMessages = Object.values(data.errors).flat().join('<br>');
                            throw new Error(errorMessages);
                        }
                        throw new Error(data.message || 'Failed to add state');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = error.message || 'An error occurred while adding the state.';
                        errorDiv.classList.remove('d-none');
                    }
                } finally {
                    submitBtn.disabled = false;
                    spinner?.classList.add('d-none');
                }
            });
        }

        // City Modal Handler
        const cityForm = document.getElementById('addCityForm');
        if (cityForm) {
            cityForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const spinner = submitBtn.querySelector('.spinner-border');
                const errorDiv = document.getElementById('cityFormErrors');
                
                submitBtn.disabled = true;
                spinner?.classList.remove('d-none');
                errorDiv?.classList.add('d-none');
                
                try {
                    const formData = new FormData(this);
                    formData.append('from_modal', '1');
                    
                    const response = await fetch('{{ route("master.cities.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success && data.city) {
                        updateCityDropdown(data.city);
                        
                        document.getElementById('country_id').value = data.city.country_id;
                        document.getElementById('country_id').dispatchEvent(new Event('change'));
                        
                        setTimeout(() => {
                            document.getElementById('state_id').value = data.city.state_id;
                            document.getElementById('state_id').dispatchEvent(new Event('change'));
                            
                            setTimeout(() => {
                                document.getElementById('city_id').value = data.city.id;
                                document.getElementById('city_id').dispatchEvent(new Event('change'));
                            }, 500);
                        }, 500);
                        
                        closeModalClean('addCityModal');
                        this.reset();
                    } else {
                        if (response.status === 422 && data.errors) {
                            let errorMessages = Object.values(data.errors).flat().join('<br>');
                            throw new Error(errorMessages);
                        }
                        throw new Error(data.message || 'Failed to add city');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = error.message || 'An error occurred while adding the city.';
                        errorDiv.classList.remove('d-none');
                    }
                } finally {
                    submitBtn.disabled = false;
                    spinner?.classList.add('d-none');
                }
            });
        }

        // Handle country change in city modal (fetch states)
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
                        console.error('Error loading states for city modal');
                    });
            }
        });

        // Uppercase and mobile format listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Function to convert input to uppercase
            function convertToUpperCase(input) {
                input.value = input.value.toUpperCase();
            }

            // PAN Number field
            const panField = document.getElementById('pan_number');
            if (panField) {
                panField.addEventListener('input', function() {
                    convertToUpperCase(this);
                });
            }

            // GST Number field
            const gstField = document.getElementById('gst_number');
            if (gstField) {
                gstField.addEventListener('input', function() {
                    convertToUpperCase(this);
                });
            }
            
            // Mobile number validation (10 digits only)
            const mobileField = document.getElementById('mobile_number');
            if (mobileField) {
                mobileField.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                });
            }
        });
    });
</script>
    
{{-- This script handles the main form's cascading dropdowns --}}
<script src="/js/dynamic-dropdowns.js"></script>

@endpush