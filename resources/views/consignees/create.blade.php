@extends('layouts.app')

@section('content')
@if(session('success'))
    <div class="alert alert-success mt-2">
        {{ session('success') }}
    </div>
@endif
<div class="card card-info card-outline mb-4" style="width: 70%;align-items: center;margin: auto;">
    <!--begin::Header-->
    <div class="card-header"><div class="card-title">Add New Consignee</div></div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('consignees.store') }}" id="consigneeForm" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        <input type="hidden" name="country_id" id="country_id_hidden" value="{{ old('country_id') }}">
        <input type="hidden" name="state_id" id="state_id_hidden" value="{{ old('state_id') }}">
        <input type="hidden" name="city_id" id="city_id_hidden" value="{{ old('city_id') }}">

        <!--begin::Body-->
        <div class="card-body">
            <!--begin::Row-->
            <div class="row mb-3">
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="name" class="form-label">Name *</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a name.</div>
                </div>
                <!--end::Col-->
               
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number *</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required>
                    @error('contact_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a contact number.</div>
                </div>
                <!--begin::Col-->
                <div class="col-md-6">
                    <label for="gst_number" class="form-label">GST Number</label>
                    <input type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number') }}">
                    @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a GST number.</div>
                </div>
                
                <!--end::Col-->
                <!--begin::Col-->
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="country_id" class="form-label">Country *  <i class="bi bi-plus-circle btn btn-sm text-primary" data-bs-toggle="modal" data-bs-target="#addCountryModal" style="cursor: pointer;"></i></label>
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
                    <label for="state_id" class="form-label">State *  <i class="bi bi-plus-circle btn btn-sm text-primary" data-bs-toggle="modal" data-bs-target="#addStateModal" style="cursor: pointer;"></i></label>
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
                    <label for="city_id" class="form-label">City * <i class="bi bi-plus-circle btn btn-sm text-primary" data-bs-toggle="modal" data-bs-target="#addCityModal" style="cursor: pointer;"></i></label>
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
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an address.</div>
                </div>
                <!--end::Col-->
             
             
             
            </div>
            <!--end::Row-->
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
        <!--end::Body-->
        {{-- <!--begin::Footer-->
        <div class="card-footer">
            <button class="btn btn-info" type="submit">Submit form</button>
            <a href="{{ route('consignees.index') }}" class="btn btn-info">Back to List</a>
        </div>
        <!--end::Footer--> --}}
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
                <label for="state_id" class="form-label">State *</label>
                <select class="form-select" id="state_id" name="state_id" required>
                  <option value="">Select State</option>
                  @foreach($states as $state)
                    <option value="{{ $state->id }}">
                      {{ $state->name }} ({{ $state->country->name }})
                    </option>
                  @endforeach
                </select>
                @error('state_id')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
                <div class="invalid-feedback">Please select a state.</div>
              </div>

              <div class="col-md-6">
                <label for="name" class="form-label">City Name *</label>
                <input type="text" class="form-control" id="name" name="name" required>
                @error('name')
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
</script>
@endsection
