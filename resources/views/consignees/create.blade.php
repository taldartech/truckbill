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
        <form action="{{ route('master.countries.store') }}" method="POST">
          @csrf
          <input type="hidden" name="from_modal" value="1">
          <div class="modal-header">
            <h5 class="modal-title" id="addCountryModalLabel">Add Country</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
  
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="name" class="form-label">Country Name *</label>
                <input type="text" class="form-control" id="name" name="name" required>
                @error('name')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
  
              <div class="col-md-6">
                <label for="code" class="form-label">Country Code *</label>
                <input type="text" class="form-control" id="code" name="code" required>
                @error('code')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
  
          <div class="modal-footer">
            <button type="submit" class="btn btn-info">Submit form</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add State Modal -->
<div class="modal fade" id="addStateModal" tabindex="-1" aria-labelledby="addStateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="{{ route('master.states.store') }}" method="POST">
          @csrf
          <input type="hidden" name="from_modal" value="1">
          <div class="modal-header">
            <h5 class="modal-title" id="addStateModalLabel">Add State</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
  
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="name" class="form-label">State Name *</label>
                <input type="text" class="form-control" id="name" name="name" required>
                @error('name')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
  
              <div class="col-md-6">
                <label for="country_id" class="form-label">Country *</label>
                <select class="form-select" id="country_id" name="country_id" required>
                  <option value="">Select Country</option>
                  @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                  @endforeach
                </select>
                @error('country_id')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
                <div class="invalid-feedback">Please select a country.</div>
              </div>
  
              <div class="col-md-6">
                <label for="code" class="form-label">Code *</label>
                <input type="text" class="form-control" id="code" name="code" required>
                @error('code')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
  
          <div class="modal-footer">
            <button type="submit" class="btn btn-info">Submit form</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Add City Modal -->
<div class="modal fade" id="addCityModal" tabindex="-1" aria-labelledby="addCityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="{{ route('master.cities.store') }}" method="POST">
          @csrf
          <input type="hidden" name="from_modal" value="1">
          <div class="modal-header">
            <h5 class="modal-title" id="addCityModalLabel">Add City</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
  
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="city_name" class="form-label">City Name *</label>
                <input type="text" class="form-control" id="city_name" name="name" required>
                @error('name')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="city_country_id" class="form-label">Country *</label>
                <select class="form-select" id="city_country_id" name="country_id" required>
                  <option value="">Select Country</option>
                  @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                  @endforeach
                </select>
                @error('country_id')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
              
              <div class="col-md-6">
                <label for="city_state_id" class="form-label">State *</label>
                <select class="form-select" id="city_state_id" name="state_id" required>
                  <option value="">Select State</option>
                  @if(old('country_id'))
                    @foreach($states as $state)
                      <option value="{{ $state->id }}">{{ $state->name }}</option>
                    @endforeach
                  @endif
                </select>
                @error('state_id')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
  
          <div class="modal-footer">
            <button type="submit" class="btn btn-info">Submit form</button>
          </div>
        </form>
      </div>
    </div>
  </div>
 
  
  
  

@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
      const modalCountry = document.querySelector('#addCityModal #country_id');
      const modalState = document.querySelector('#addCityModal #state_id');
  
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
                      })
                      .catch(error => console.error('Error fetching states for modal:', error));
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
        $('#addCountryModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        // Add new country to the main dropdown
                        $('#country_id').append(`<option value="${response.country.id}">${response.country.name}</option>`);
                        // Select the newly created country
                        $('#country_id').val(response.country.id);
                        // Close modal
                        $('#addCountryModal').modal('hide');
                        // Reset form
                        $('#addCountryModal form')[0].reset();
                        // Show success message
                        toastr.success('Country added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            toastr.error(errors[field][0]);
                        }
                    } else {
                        toastr.error('An error occurred while adding the country.');
                    }
                }
            });
        });

        // Handle State Modal Form Submission
        $('#addStateModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        // Add new state to the main dropdown
                        $('#state_id').append(`<option value="${response.state.id}">${response.state.name}</option>`);
                        // Select the newly created state
                        $('#state_id').val(response.state.id);
                        // Close modal
                        $('#addStateModal').modal('hide');
                        // Reset form
                        $('#addStateModal form')[0].reset();
                        // Show success message
                        toastr.success('State added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            toastr.error(errors[field][0]);
                        }
                    } else {
                        toastr.error('An error occurred while adding the state.');
                    }
                }
            });
        });

        // Handle City Modal Form Submission
        $('#addCityModal form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        // Add new city to the main dropdown
                        $('#city_id').append(`<option value="${response.city.id}">${response.city.name}</option>`);
                        // Select the newly created city
                        $('#city_id').val(response.city.id);
                        // Close modal
                        $('#addCityModal').modal('hide');
                        // Reset form
                        $('#addCityModal form')[0].reset();
                        // Show success message
                        toastr.success('City added successfully!');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            toastr.error(errors[field][0]);
                        }
                    } else {
                        toastr.error('An error occurred while adding the city.');
                    }
                }
            });
        });

        // Handle country change in city modal
        $('#city_country_id').on('change', function() {
            const countryId = $(this).val();
            const stateSelect = $('#city_state_id');
            
            stateSelect.html('<option value="">Select State</option>');
            
            if (countryId) {
                $.get(`/api/states/${countryId}`)
                    .done(function(data) {
                        data.forEach(function(state) {
                            stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                        });
                    })
                    .fail(function() {
                        toastr.error('Error loading states');
                    });
            }
        });
    });
</script>
@endsection
