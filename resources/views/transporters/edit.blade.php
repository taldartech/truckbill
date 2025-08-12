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
        <div class="card-title h4">Edit Transporter</div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
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
            {{-- Company field hidden as requested --}}
            @if($canEditCompany)
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="company_id" class="form-label">Company *</label>
                        <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id', $transporter->company_id) == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ $userCompanyId }}">
            @endif

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Basic Information</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $transporter->name) }}" required>
                        @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="short_name" class="form-label">Short Name</label>
                        <input type="text" class="form-control @error('short_name') is-invalid @enderror" id="short_name" name="short_name" value="{{ old('short_name', $transporter->short_name) }}">
                        @error('short_name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="contact_person_name" class="form-label">Contact Person Name *</label>
                        <input type="text" class="form-control @error('contact_person_name') is-invalid @enderror" id="contact_person_name" name="contact_person_name" value="{{ old('contact_person_name', $transporter->contact_person_name) }}" required>
                        @error('contact_person_name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="mobile_number" class="form-label">Mobile Number *</label>
                        <input type="text" class="form-control @error('mobile_number') is-invalid @enderror" id="mobile_number" name="mobile_number" value="{{ old('mobile_number', $transporter->mobile_number) }}" required pattern="[0-9]{10}" maxlength="10">
                        @error('mobile_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Advance Amount</label>
                        <div class="form-control-plaintext bg-light border rounded px-3 py-2">
                            <strong>₹ {{ number_format($transporter->advance_amount, 2) }}</strong>
                            <small class="text-muted d-block">Read-only field</small>
                        </div>
                        <input type="hidden" name="advance_amount" value="{{ $transporter->advance_amount }}">
                    </div>
                </div>
            </div>

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">GST & PAN Information</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="gst_number" class="form-label">GST Number</label>
                        <input type="text" class="form-control @error('gst_number') is-invalid @enderror" id="gst_number" name="gst_number" value="{{ old('gst_number', $transporter->gst_number) }}" maxlength="15">
                        @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <!-- Empty column for consistent 3-column layout -->
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="pan_number" class="form-label">PAN Number *</label>
                        <input type="text" class="form-control @error('pan_number') is-invalid @enderror" id="pan_number" name="pan_number" value="{{ old('pan_number', $transporter->pan_number) }}" required maxlength="10" style="text-transform: uppercase;">
                        @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="pan_active" class="form-label">PAN Active *</label>
                        <select class="form-select @error('pan_active') is-invalid @enderror" id="pan_active" name="pan_active" required>
                            <option value="">Select Status</option>
                            <option value="1" {{ old('pan_active', $transporter->pan_active) == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('pan_active', $transporter->pan_active) == '0' ? 'selected' : '' }}>No</option>
                        </select>
                        @error('pan_active') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Location Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="country_id" class="form-label">Country *</label>
                        <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required>
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id', $transporter->country_id) == $country->id ? 'selected' : '' }}>
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
                            @foreach($states as $state)
                                <option value="{{ $state->id }}" {{ old('state_id', $transporter->state_id) == $state->id ? 'selected' : '' }}>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('state_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="city_id" class="form-label">City *</label>
                        <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id" required>
                            <option value="">Select City</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ old('city_id', $transporter->city_id) == $city->id ? 'selected' : '' }}>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('city_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $transporter->address) }}</textarea>
                        @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Update Transporter</button>
            <a href="{{ route('transporters.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Example starter JavaScript for disabling form submissions if there are invalid fields
(() => {
    'use strict';
    window.addEventListener('load', () => {
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms).forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// PAN number uppercase conversion
document.getElementById('pan_number').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Mobile number validation (10 digits only)
document.getElementById('mobile_number').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
});

$(document).ready(function() {
    // Load states when country changes
    $('#country_id').change(function() {
        var countryId = $(this).val();
        if (countryId) {
            loadStates(countryId);
        } else {
            $('#state_id').empty().append('<option value="">Select State</option>');
            $('#city_id').empty().append('<option value="">Select City</option>');
        }
    });

    // Load cities when state changes
    $('#state_id').change(function() {
        var stateId = $(this).val();
        if (stateId) {
            loadCities(stateId);
        } else {
            $('#city_id').empty().append('<option value="">Select City</option>');
        }
    });

    // Load states and cities on page load if country and state are already selected
    var selectedCountryId = $('#country_id').val();
    var selectedStateId = '{{ old("state_id", $transporter->state_id) }}';
    var selectedCityId = '{{ old("city_id", $transporter->city_id) }}';

    if (selectedCountryId && selectedStateId) {
        loadStates(selectedCountryId, function() {
            $('#state_id').val(selectedStateId);
            if (selectedStateId && selectedCityId) {
                loadCities(selectedStateId, function() {
                    $('#city_id').val(selectedCityId);
                });
            }
        });
    }

    function loadStates(countryId, callback = null) {
        $.get('/api/dropdown/states/' + countryId, function(data) {
            $('#state_id').empty().append('<option value="">Select State</option>');
            $.each(data, function(key, state) {
                $('#state_id').append('<option value="' + state.id + '">' + state.name + '</option>');
            });
            $('#city_id').empty().append('<option value="">Select City</option>');
            if (callback) callback();
        });
    }

    function loadCities(stateId, callback = null) {
        $.get('/api/dropdown/cities/' + stateId, function(data) {
            $('#city_id').empty().append('<option value="">Select City</option>');
            $.each(data, function(key, city) {
                $('#city_id').append('<option value="' + city.id + '">' + city.name + '</option>');
            });
            if (callback) callback();
        });
    }
});
</script>
    

    
@endsection

