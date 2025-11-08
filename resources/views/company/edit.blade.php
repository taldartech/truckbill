@extends('layouts.app')

@section('content')

<div class="container px-0">

    <div class="card card-info card-outline mx-auto" style="max-width: 900px;">

        <!-- Header -->
        <div class="card-header text-center">
            <h5 class="card-title mb-0">Register Company</h5>
        </div>

        <form action="{{ route('company.update', $company->id) }}" 
              method="POST" 
              class="needs-validation" 
              novalidate>
              
            @csrf
            @method('PUT')

            @if(session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            <div class="card-body">
                <div class="row g-3">

                    <!-- Company Name -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Company Name *</label>
                        <input type="text" class="form-control" name="name" required
                               value="{{ old('name', $company->name) }}">
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <!-- GST -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">GST Number</label>
                        <input type="text" class="form-control" name="gst_number"
                               value="{{ old('gst_number', $company->gst_number) }}">
                    </div>

                    <!-- PAN -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">PAN Number</label>
                        <input type="text" class="form-control" name="pan_number"
                               value="{{ old('pan_number', $company->pan_number) }}">
                    </div>

                    <!-- GST Scheme -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">GST Scheme</label>
                        <select class="form-select" name="gst_scheme">
                            <option value="">Select</option>
                            <option value="reverse charge" {{ old('gst_scheme', $company->gst_scheme) == 'reverse charge' ? 'selected' : '' }}>Reverse Charge</option>
                            <option value="forward charge" {{ old('gst_scheme', $company->gst_scheme) == 'forward charge' ? 'selected' : '' }}>Forward Charge</option>
                        </select>
                    </div>

                    <!-- GST Type -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">GST Type *</label>
                        <input type="text" class="form-control" value="Regular" disabled>
                    </div>

                    <!-- Address -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address">{{ old('address', $company->address) }}</textarea>
                    </div>

                    <!-- Country -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Country</label>
                        <select class="form-select" name="country_id" onchange="fetchStates(this.value)">
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}"
                                    {{ old('country_id', $company->country_id) == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- State -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">State</label>
                        <select class="form-select" name="state_id" onchange="fetchCities(this.value)">
                            <option value="">Select State</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}"
                                    {{ old('state_id', $company->state_id) == $state->id ? 'selected' : '' }}>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- City -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">City</label>
                        <select class="form-select" name="city_id">
                            <option value="">Select City</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}"
                                    {{ old('city_id', $company->city_id) == $city->id ? 'selected' : '' }}>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pincode -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Pin Code</label>
                        <input type="text" class="form-control" name="pin_code"
                               value="{{ old('pin_code', $company->pin_code) }}">
                    </div>

                    <!-- Contact Person -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text" class="form-control" name="contact_person"
                               value="{{ old('contact_person', $company->contact_person) }}">
                    </div>

                    <!-- Contact Number -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="text" class="form-control" name="contact_number"
                               value="{{ old('contact_number', $company->contact_number) }}">
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-success" type="submit">Update Company</button>
                <a href="{{ route('company.index') }}" class="btn btn-info">Back to list</a>
            </div>

        </form>

    </div>
</div>

@endsection
