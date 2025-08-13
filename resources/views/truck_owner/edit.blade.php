

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
        <div class="card-title h4">Edit Truck Owner</div>
    </div>
    <!--end::Header-->

   

    <!--begin::Form-->
    <form action="{{ route('truck_owners.update', $transporter->id) }}" method="POST" class="needs-validation" novalidate="">
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
            {{-- Hidden fields for truck owner identification --}}
            <input type="hidden" name="is_owner" value="1">
            <input type="hidden" name="advance_amount" value="{{ old('advance_amount', $transporter->advance_amount) }}">
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
                    <label for="name" class="form-label">Truck Owner Name *</label>
                    <input placeholder="Enter Truck Owner Name" type="text" class="form-control" id="name" name="name" required value="{{ old('name', $transporter->name) }}">
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
                        <label class="form-label">Declaration Given? *</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="declaration_given" id="decl_yes" value="1" {{ old('declaration_given', $transporter->declaration_given) == 1 ? 'checked' : '' }} required>
                            <label class="form-check-label" for="decl_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="declaration_given" id="decl_no" value="0" {{ old('declaration_given', $transporter->declaration_given) == 0 ? 'checked' : '' }} required>
                            <label class="form-check-label" for="decl_no">No</label>
                        </div>
                        @error('declaration_given') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="total_trucks" class="form-label">Total Trucks *</label>
                        <input type="number" class="form-control" id="total_trucks" name="total_trucks" min="0" required value="{{ old('total_trucks', $transporter->total_trucks) }}" placeholder="Enter total trucks">
                        @error('total_trucks') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                {{-- Advance amount is now hidden as per truck owner requirements --}}
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
            <!-- Registered Trucks Section -->
<div class="card mb-4 p-3 bg-light">
    <div class="row">
        <div class="col">
            <h5 class="mb-3">Registered Trucks</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>S.No</th>
                    <th>Truck Number</th>
                    <th>Truck Type</th>
                    <th>Driver Name</th>
                    <th>Driver Mobile Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trucks as $index => $truck)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $truck->truck_number }}</td>
                        <td>{{ $truck->truck_type }}</td>
                        <td>{{ $truck->driver->name ?? 'N/A' }}</td>
                        <td>{{ $truck->driver->mobile ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No trucks registered for this transporter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="{{ route('truck_owners.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
                <button type="button" class="btn btn-primary" id="submitFormBtn">
                    <i class="fas fa-save me-2"></i>Update Truck Owner
                </button>
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
                    Please review the details below before updating the truck owner.
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
                    <i class="fas fa-check me-2"></i>Confirm & Update
                </button>
            </div>
        </div>
    </div>
</div>

{{-- JS for confirmation modal --}}
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
        const advance = '{{ old("advance_amount", $transporter->advance_amount) }}'; // Current advance amount

        // Populate modal with form data
        document.getElementById('confirm-name').textContent = name;
        document.getElementById('confirm-short-name').textContent = shortName;
        document.getElementById('confirm-contact-person').textContent = contactPerson;
        document.getElementById('confirm-mobile').textContent = mobile;
        
        document.getElementById('confirm-gst-number').textContent = gstNumber;
        document.getElementById('confirm-pan-number').textContent = panNumber;
        document.getElementById('confirm-pan-active').textContent = panActive;
        
        document.getElementById('confirm-address').textContent = address;
        document.getElementById('confirm-country').textContent = country;
        document.getElementById('confirm-state').textContent = state;
        document.getElementById('confirm-city').textContent = city;
        
        document.getElementById('confirm-declaration').textContent = declaration;
        document.getElementById('confirm-total-trucks').textContent = totalTrucks;
        document.getElementById('confirm-advance').textContent = '₹' + parseFloat(advance || 0).toFixed(2);

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        modal.show();
    }

    // Function to submit the form
    function submitForm() {
        document.querySelector('.needs-validation').submit();
    }

    // Attach event listener to submit button
    document.getElementById('submitFormBtn').addEventListener('click', showConfirmationModal);
</script>

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

