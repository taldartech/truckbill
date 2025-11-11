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

/* FIXED: This rule was breaking mobile responsiveness and has been removed. */

/* Prevent card from expanding */
.card {
    overflow: hidden;
}

/* Consistent spacing for form groups */
.mb-3 {
    margin-bottom: 1rem !important;
}
</style>

<div class="container-fluid px-0 ">
    @if(session('success'))
        <div class="alert alert-success mt-2">
            {{ session('success') }}
        </div>
    @endif

    <div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
        <div class="card-header">
            <div class="card-title h4">Add New Consignee/Consignor/Customer</div>
        </div>
        <form method="POST" action="{{ route('consignees.store') }}" class="needs-validation" novalidate="" id="consigneeForm">
            @csrf
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="name" class="form-label">Consignee Name *</label>
                        <input placeholder="Enter Consignee Name" type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                        @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="contact_number" class="form-label">Contact Number *</label>
                        <input placeholder="Enter Contact Number" type="text" maxlength="10" class="form-control" id="contact_number" name="contact_number" required value="{{ old('contact_number') }}">
                        @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input placeholder="Enter Email" type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                        @error('email') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="gst_number" class="form-label">GST Number</label>
                        <input placeholder="Enter GST Number" type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
                        <div class="invalid-feedback" id="gst_number_feedback"></div>
                        @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="pan_number" class="form-label">PAN Number</label>
                        <input placeholder="Enter PAN Number (e.g., ABCDE1234F)" type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number') }}" maxlength="10" style="text-transform: uppercase;">
                        <div class="invalid-feedback" id="pan_number_feedback"></div>
                        @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
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
                    <div class="col-12 col-md-4 mb-3">
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
                    <div class="col-12 col-md-4 mb-3">
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
                <div class="row">
                    <div class="col-12 col-md-8 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input placeholder="Enter Address" type="text" class="form-control" id="address" name="address" value="{{ old('address') }}">
                        @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <button class="btn btn-success" type="submit">
                    <i class="bi bi-check-circle"></i> Submit Form
                </button>
                <a href="{{ route('consignees.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
            </form>
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

@section('scripts')
<script src="/js/dynamic-dropdowns.js"></script>
<script>
// FIXED: Merged all scripts into one DOMContentLoaded listener for clarity
document.addEventListener('DOMContentLoaded', function () {
    
    // --- City Modal Dropdown Logic ---
    const modalCountry = document.querySelector('#addCityModal #modal_city_country_id');
    const modalState = document.querySelector('#addCityModal #modal_city_state_id');

    if (modalCountry && modalState) {
        modalCountry.addEventListener('change', function () {
            const countryId = this.value;
            modalState.innerHTML = '<option value="">Select State</option>'; // Clear current state options

            if (countryId) {
                fetch(`/api/states/${countryId}`)
                    .then(response => response.json())
                    .then(states => {
                        states.forEach(state => {
                            const option = document.createElement('option');
                            option.value = state.id;
                            option.textContent = state.name;
                            modalState.appendChild(option);
                        });
                        modalState.required = true;
                    })
                    .catch(error => console.error('Error fetching states for modal:', error));
            } else {
                modalState.required = false;
            }
        });
    }

    // --- Bootstrap Form Validation ---
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

    // --- Variables for dropdowns and validation ---
    const oldCountry = "{{ old('country_id') }}";
    const oldState = "{{ old('state_id') }}";
    const oldCity = "{{ old('city_id') }}";
    
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    
    const gstNumberInput = document.getElementById('gst_number');
    const panNumberInput = document.getElementById('pan_number');
    const gstFeedback = document.getElementById('gst_number_feedback');
    const panFeedback = document.getElementById('pan_number_feedback');
    let gstTimeout = null;
    let panTimeout = null;
    let gstValid = true;
    let panValid = true;
    
    // --- Main Form Validation (GST/PAN) ---
    const consigneeForm = document.getElementById('consigneeForm');
    if (consigneeForm) {
        consigneeForm.addEventListener('submit', function(event) {
            if (!gstValid || !panValid) {
                event.preventDefault();
                alert('Please fix the validation errors before submitting the form.');
                return false;
            }
        });
    }
    
    if (gstNumberInput) {
        gstNumberInput.addEventListener('input', function() {
            const gstNumber = this.value.trim().toUpperCase();
            this.value = gstNumber;
            clearTimeout(gstTimeout);
            
            if (gstNumber.length > 0) {
                gstTimeout = setTimeout(() => validateGstNumber(gstNumber), 500);
            } else {
                gstNumberInput.classList.remove('is-invalid');
                gstFeedback.textContent = '';
                gstValid = true; // Is valid if empty
            }
        });
    }
    
    if (panNumberInput) {
        panNumberInput.addEventListener('input', function() {
            const panNumber = this.value.trim().toUpperCase();
            this.value = panNumber;
            clearTimeout(panTimeout);
            
            if (panNumber.length === 10) {
                panTimeout = setTimeout(() => validatePanNumber(panNumber), 500);
            } else if (panNumber.length === 0) {
                 panNumberInput.classList.remove('is-invalid');
                 panFeedback.textContent = '';
                 panValid = true; // Is valid if empty
            } else {
                 // Incomplete PAN
                 panNumberInput.classList.add('is-invalid');
                 panFeedback.textContent = 'PAN must be 10 characters.';
                 panValid = false;
            }
        });
    }
    
    function validateGstNumber(gstNumber) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch('/api/validate/gst-number', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ gst_number: gstNumber })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.available) {
                gstNumberInput.classList.add('is-invalid');
                gstFeedback.textContent = 'This GST Number is already in use';
                gstValid = false;
            } else {
                gstNumberInput.classList.remove('is-invalid');
                gstFeedback.textContent = '';
                gstValid = true;
            }
        })
        .catch(error => {
            console.error('Error checking GST number:', error);
            gstValid = true;
        });
    }
    
    function validatePanNumber(panNumber) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch('/api/validate/pan-number', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ pan_number: panNumber })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.available) {
                panNumberInput.classList.add('is-invalid');
                panFeedback.textContent = 'This PAN Number is already in use';
                panValid = false;
            } else {
                panNumberInput.classList.remove('is-invalid');
                panFeedback.textContent = '';
                panValid = true;
            }
        })
        .catch(error => {
            console.error('Error checking PAN number:', error);
            panValid = true;
        });
    }

    // --- Modal Form Submission Handlers (Fetch-based) ---
        
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
                    document.getElementById('state_id').value = data.state.id;
                    document.getElementById('state_id').dispatchEvent(new Event('change'));
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
                    document.getElementById('city_id').value = data.city.id;
                    document.getElementById('city_id').dispatchEvent(new Event('change'));
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
    
    // City validation on main form
    $('#city_id').on('change', function() {
        const cityId = $(this).val();
        const stateId = $('#state_id').val();
        const countryId = $('#country_id').val();
        
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
        
        if (cityId && (!stateId || !countryId)) {
            $(this).addClass('is-invalid');
            $(this).after('<div class="invalid-feedback">Please select country and state first.</div>');
        }
    });

    // --- Helper Functions ---
    function updateCountryDropdowns(country) {
        const dropdowns = ['country_id', 'modal_state_country_id', 'modal_city_country_id'];
        dropdowns.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
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
            if (select) {
                const option = document.createElement('option');
                option.value = state.id;
                option.textContent = state.name;
                select.appendChild(option);
            }
        });
    }
    
    function updateCityDropdown(city) {
        const select = document.getElementById('city_id');
        if (select) {
            const option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            select.appendChild(option);
        }
    }
    
    function closeModalClean(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
            bsModal.hide();
            
            modal.addEventListener('hidden.bs.modal', function cleanup() {
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                modal.removeEventListener('hidden.bs.modal', cleanup);
            }, { once: true });
        }
    }
});
</script>
@endsection