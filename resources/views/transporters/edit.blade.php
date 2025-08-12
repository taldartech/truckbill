

@extends('layouts.app')

@section('content')
<div class="card card-info card-outline mb-4" style="max-width: 950px;align-items: center;margin: auto;">
    <div class="card-header">
        <h3 class="card-title">Edit Transporter</h3>
    </div>

   

    <form action="{{ route('transporters.update', $transporter->id) }}" method="POST" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
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
                  
                    <label for="company_id" class="form-label">Company *</label>
                    @if($canEditCompany)
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">Select</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id', $transporter->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
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
                    <input placeholder="Enter Transport Name" type="text" class="form-control" id="name" name="name" required value="{{ old('name', $transporter->name) }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
               
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="short_name" class="form-label">Short Name *</label>
                    <input placeholder="Enter Short Name" type="text" class="form-control" id="short_name" name="short_name" value="{{ old('short_name', $transporter->short_name) }}">
                    @error('short_name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="gst_number" class="form-label">GST Number</label>
            <input placeholder="Enter GST Number" type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number', $transporter->gst_number) }}">
            @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
               
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input placeholder="Enter PAN Number" type="text" class="form-control" id="pan_number" name="pan_number" required value="{{ old('pan_number', $transporter->pan_number) }}">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">PAN is Active *</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="pan_active" id="pan_active_yes" value="1" {{ old('pan_active', $transporter->pan_active) == 1 ? 'checked' : '' }} required>
                        <label class="form-check-label" for="pan_active_yes">Yes</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="pan_active" id="pan_active_no" value="0" {{ old('pan_active', $transporter->pan_active) == 0 ? 'checked' : '' }} required>
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
                        <label for="advance_amount" class="form-label">Advance Amount</label>
                        <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="{{ old('advance_amount', $transporter->advance_amount) }}" placeholder="Enter advance amount">
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
                            <option value="{{ $country->id }}" {{ old('country_id', $transporter->country_id) == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a country.</div>
                </div>
                <!--end::Col-->
                
                <div class="col-md-4">
                    <label for="state_id" class="form-label">State *</label>
                    <select class="form-select" id="state_id" name="state_id" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}"
                                {{ (old('state_id', $transporter->state_id) == $state->id) ? 'selected' : '' }}>
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please select a state.</div>
                </div>
                
                <div class="col-md-4">
                    <label for="city_id" class="form-label">City *</label>
                    <select class="form-select" id="city_id" name="city_id" required>
                        <option value="">Select City</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}"
                                {{ (old('city_id', $transporter->city_id) == $city->id) ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
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
                    <input type="text" class="form-control" id="mobile" name="mobile_number" required value="{{ old('mobile_number', $transporter->mobile_number) }}">
                    @error('mobile_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a valid mobile number.</div>
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $transporter->address) }}</textarea>
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
                        <button class="btn btn-success w-100 mb-2" type="submit">💾Update Form</button>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('transporters.index') }}" class="btn btn-outline-dark w-100 mb-2">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- JS for cascading dropdowns --}}
<script>
    $(document).ready(function () {
        let countryId = "{{ old('country_id', $transporter->country_id) }}";
        let stateId = "{{ old('state_id', $transporter->state_id) }}";
        let cityId = "{{ old('city_id', $transporter->city_id) }}";
    
        // Pre-fill states if editing
        if (countryId) {
            loadStates(countryId, stateId, function () {
                // Load cities after states are loaded
                if (stateId) {
                    loadCities(stateId, cityId);
                }
            });
        }
    
        // Country change
        $('#country_id').on('change', function () {
            let cid = $(this).val();
            $('#state_id').html('<option value="">Select State</option>');
            $('#city_id').html('<option value="">Select City</option>');
            if (cid) {
                loadStates(cid);
            }
        });
    
        // State change
        $('#state_id').on('change', function () {
            let sid = $(this).val();
            $('#city_id').html('<option value="">Select City</option>');
            if (sid) {
                loadCities(sid);
            }
        });
    
        function loadStates(countryId, selectedStateId = null, callback = null) {
            $.get('/api/dropdown/states/' + countryId, function (data) {
                $('#state_id').empty().append('<option value="">Select State</option>');
                $.each(data, function (key, state) {
                    $('#state_id').append('<option value="' + state.id + '" ' +
                        (state.id == selectedStateId ? 'selected' : '') + '>' + state.name + '</option>');
                });
                if (callback) callback();
            });
        }
    
        function loadCities(stateId, selectedCityId = null) {
            $.get('/api/dropdown/cities/' + stateId, function (data) {
                $('#city_id').empty().append('<option value="">Select City</option>');
                $.each(data, function (key, city) {
                    $('#city_id').append('<option value="' + city.id + '" ' +
                        (city.id == selectedCityId ? 'selected' : '') + '>' + city.name + '</option>');
                });
            });
        }
    });
    </script>
    

    
@endsection

