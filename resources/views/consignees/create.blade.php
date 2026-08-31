@extends('layouts.app')

@section('content')
<div class="container mt-4 pb-5 app-index-page app-create-page">
    @if(session('success'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
            </span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i> {{ session('error') }}
            </span>
        </div>
    @endif

    <form method="POST" action="{{ route('consignees.store') }}" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">

        <div class="card app-index-list-card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-people me-1"></i> Add New Consignee/Consignor/Customer
                </h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('consignees.index') }}" class="btn app-index-header-btn" aria-label="Back to consignees">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-person-vcard"></i> Basic Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="name" class="form-label">Consignee Name *</label>
                                <input placeholder="Enter Consignee Name" type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                                @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="contact_number" class="form-label">Contact Number *</label>
                                <input placeholder="Enter Contact Number" type="text" class="form-control" id="contact_number" name="contact_number" required value="{{ old('contact_number') }}">
                                @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">Email</label>
                                <input placeholder="Enter Email" type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                                @error('email') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label for="gst_number" class="form-label">GST Number</label>
                                <input placeholder="Enter GST Number" type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
                                <div class="invalid-feedback" id="gst_number_feedback"></div>
                                @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="pan_number" class="form-label">PAN Number</label>
                                <input placeholder="Enter PAN Number (e.g., ABCDE1234F)" type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number') }}" maxlength="10" style="text-transform: uppercase;">
                                <div class="invalid-feedback" id="pan_number_feedback"></div>
                                @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-form-section">
                    <div class="app-form-section__header">
                        <h5 class="app-form-section__title"><i class="bi bi-geo-alt"></i> Location Details</h5>
                    </div>
                    <div class="app-form-section__body">
                        <div class="row g-2 mb-2">
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
                                    <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#addCountryModal" title="Add New Country">
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
                                    <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#addStateModal" title="Add New State">
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
                                    <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#addCityModal" title="Add New City">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                                @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label for="address" class="form-label">Address</label>
                                <input placeholder="Enter Address" type="text" class="form-control" id="address" name="address" value="{{ old('address') }}">
                                @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-form-actions card">
            <div class="card-body">
                <a href="{{ route('consignees.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-brand-primary" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Create Consignee
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Add Country Modal -->
<div class="modal fade fb-pay-modal" id="addCountryModal" tabindex="-1" aria-labelledby="addCountryModalLabel" aria-hidden="true">
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
            <button type="submit" class="btn btn-brand-primary">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit form
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add State Modal -->
<div class="modal fade fb-pay-modal" id="addStateModal" tabindex="-1" aria-labelledby="addStateModalLabel" aria-hidden="true">
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
            <button type="submit" class="btn btn-brand-primary">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit form
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add City Modal -->
<div class="modal fade fb-pay-modal" id="addCityModal" tabindex="-1" aria-labelledby="addCityModalLabel" aria-hidden="true">
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
            <button type="submit" class="btn btn-brand-primary">
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
<script src="/js/dynamic-dropdowns.js?v={{ config('app.ASSET_VERSION') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
      const modalCountry = document.querySelector('#addCityModal #modal_city_country_id');
      const modalState = document.querySelector('#addCityModal #modal_city_state_id');

      if (modalCountry && modalState) {
          modalCountry.addEventListener('change', function () {
              const countryId = this.value;

              // Clear current state options
              modalState.innerHTML = '<option value="">Select State</option>';

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

    // Note: Country, State, City cascading selects are handled by dynamic-dropdowns.js
    const oldCountry = "{{ old('country_id') }}";
    const oldState = "{{ old('state_id') }}";
    const oldCity = "{{ old('city_id') }}";

    // These variables are used elsewhere in the code
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');

    // GST and PAN number validation
    const gstNumberInput = document.getElementById('gst_number');
    const panNumberInput = document.getElementById('pan_number');
    const gstFeedback = document.getElementById('gst_number_feedback');
    const panFeedback = document.getElementById('pan_number_feedback');
    let gstTimeout = null;
    let panTimeout = null;
    let gstValid = true;
    let panValid = true;

    // Form validation
    const consigneeForm = document.getElementById('consigneeForm');
    if (consigneeForm) {
        consigneeForm.addEventListener('submit', function(event) {
            // Check if GST or PAN is invalid
            if (!gstValid || !panValid) {
                event.preventDefault();
                alert('Please fix the validation errors before submitting the form.');
                return false;
            }
        });
    }

    // Check GST number availability with debounce
    if (gstNumberInput) {
        gstNumberInput.addEventListener('input', function() {
            const gstNumber = this.value.trim().toUpperCase();
            this.value = gstNumber; // Force uppercase

            clearTimeout(gstTimeout);

            if (gstNumber.length > 0) {
                gstTimeout = setTimeout(() => {
                    validateGstNumber(gstNumber);
                }, 500);
            } else {
                gstNumberInput.classList.remove('is-invalid');
                gstFeedback.textContent = '';
            }
        });
    }

    // Check PAN number availability with debounce
    if (panNumberInput) {
        panNumberInput.addEventListener('input', function() {
            const panNumber = this.value.trim().toUpperCase();
            this.value = panNumber; // Force uppercase

            clearTimeout(panTimeout);

            if (panNumber.length === 10) { // Only validate complete PAN numbers
                panTimeout = setTimeout(() => {
                    validatePanNumber(panNumber);
                }, 500);
            } else {
                panNumberInput.classList.remove('is-invalid');
                panFeedback.textContent = '';
            }
        });
    }

    // Validate PAN number against API
    function validatePanNumber(panNumber) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/api/validate/pan-number', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
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
            panValid = true; // Default to valid on error to not block submission
        });
    }

    // Modal form submissions with AJAX
    $(document).ready(function() {
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
                    if (data && data.success && data.country) {
                        // Add new country to the main dropdown
                        $('#country_id').append(`<option value="${data.country.id}">${data.country.name}</option>`);

                        // Add new country to modal dropdowns
                        $('#modal_state_country_id').append(`<option value="${data.country.id}">${data.country.name}</option>`);
                        $('#modal_city_country_id').append(`<option value="${data.country.id}">${data.country.name}</option>`);

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
                    if (data && data.success && data.state) {
                        // Add new state to the main dropdown
                        $('#state_id').append(`<option value="${data.state.id}">${data.state.name}</option>`);

                        // Add new state to city modal dropdown
                        $('#modal_city_state_id').append(`<option value="${data.state.id}">${data.state.name}</option>`);

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

                    // Show loading
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

                        if (data.success && data.country) {
                            // Update all dropdowns
                            updateCountryDropdowns(data.country);

                            // Select new country
                            document.getElementById('country_id').value = data.country.id;
                            document.getElementById('country_id').dispatchEvent(new Event('change'));

                            // Clean close modal
                            closeModalClean('addCountryModal');

                            // Reset form
                            this.reset();
                        } else {
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

                    // Show loading
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

                        if (data.success && data.state) {
                            // Update dropdowns
                            updateStateDropdowns(data.state);

                            // Select new state
                            document.getElementById('state_id').value = data.state.id;
                            document.getElementById('state_id').dispatchEvent(new Event('change'));

                            // Clean close modal
                            closeModalClean('addStateModal');

                            // Reset form
                            this.reset();
                        } else {
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

                    // Show loading
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

                        if (data.success && data.city) {
                            // Update dropdown
                            updateCityDropdown(data.city);

                            // Select new city
                            document.getElementById('city_id').value = data.city.id;
                            document.getElementById('city_id').dispatchEvent(new Event('change'));

                            // Clean close modal
                            closeModalClean('addCityModal');

                            // Reset form
                            this.reset();
                        } else {
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

            // Note: The country, state, and city cascading selects are handled by dynamic-dropdowns.js
            // No need for duplicate event listeners here

            // Handle city change with validation
            $('#city_id').on('change', function() {
                const cityId = $(this).val();
                const stateId = $('#state_id').val();
                const countryId = $('#country_id').val();

                // Clear validation errors
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();

                // Additional client-side validation
                if (cityId && (!stateId || !countryId)) {
                    $(this).addClass('is-invalid');
                    $(this).after('<div class="invalid-feedback">Please select country and state first.</div>');
                }
            });
        });

        // Helper functions for modal management
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
                // Use Bootstrap's modal instance
                const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                bsModal.hide();

                // Clean up after hide completes
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
