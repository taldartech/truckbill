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

@if(session('success'))
    <div class="alert alert-success mt-2">
        {{ session('success') }}
    </div>
@endif

<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="card-title h4">Add New Consignee/Consignor/Customer</div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('consignees.store') }}" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        
        <div class="card-body">
            <!-- Basic Details -->
            <div class="row mb-3">
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
                
            </div>
            
            <div class="row mb-3">
            <div class="col-md-4">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input placeholder="Enter GST Number" type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
                    @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input placeholder="Enter PAN Number (e.g., ABCDE1234F)" type="text" class="form-control" id="pan_number" name="pan_number" value="{{ old('pan_number') }}" maxlength="10" style="text-transform: uppercase;">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                
            </div>
            <!-- Location Details -->
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
            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="address" class="form-label">Address</label>
                    <input placeholder="Enter Address" type="text" class="form-control" id="address" name="address" value="{{ old('address') }}">
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        
        <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-success" type="submit">
                <i class="bi bi-check-circle"></i> Submit Form
            </button>
            <a href="{{ route('consignees.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
        <!--end::Footer-->
    </form>
    <!--end::Form-->
</div>

<!-- Add Country Modal -->
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
 
  
  
  

@endsection

@section('scripts')
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




    // countrySelect.addEventListener('change', function() {
    //     const countryId = this.value;
    //     if (countryId) {
    //         fetch(`/api/states/${countryId}`)
    //             .then(response => response.json())
    //             .then(states => {
    //                 stateSelect.innerHTML = '<option value="">Select State</option>';
    //                 states.forEach(state => {
    //                     const option = document.createElement('option');
    //                     option.value = state.id;
    //                     option.textContent = state.name;
    //                     stateSelect.appendChild(option);
    //                 });
    //                 stateSelect.required = true;
    //             });
    //     } else {
    //         stateSelect.innerHTML = '<option value="">Select State</option>';
    //         stateSelect.required = false;
    //     }
    // });

    // stateSelect.addEventListener('change', function() {
    //     const stateId = this.value;
    //     if (stateId) {
    //         fetch(`/api/cities/${stateId}`)
    //             .then(response => response.json())
    //             .then(cities => {
    //                 citySelect.innerHTML = '<option value="">Select City</option>';
    //                 cities.forEach(city => {
    //                     const option = document.createElement('option');
    //                     option.value = city.id;
    //                     option.textContent = city.name;
    //                     citySelect.appendChild(option);
    //                 });
    //                 citySelect.required = true;
    //             });
    //     } else {
    //         citySelect.innerHTML = '<option value="">Select City</option>';
    //         citySelect.required = false;
    //     }
    // });

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
            const cityName = $('#city_name').val();
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
@endsection
