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
            <!-- Hidden company field - automatically set based on user's company -->
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Truck Owner Name *</label>
                    <input placeholder="Enter Truck Owner Name" type="text" class="form-control" id="name" name="name" required value="{{ old('name', $transporter->name) }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Advance Amount</label>
                    <div class="form-control-plaintext bg-light border rounded px-3 py-2">
                        <strong>₹ {{ number_format($transporter->advance_amount, 2) }}</strong>
                        <small class="text-muted d-block">Read-only field</small>
                    </div>
                    <input type="hidden" name="advance_amount" value="{{ $transporter->advance_amount }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="gst_number" class="form-label">GST Number</label>
            <input placeholder="Enter GST Number" type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number', $transporter->gst_number) }}">
            @error('gst_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input placeholder="Enter PAN Number" type="text" class="form-control" id="pan_number" name="pan_number" required value="{{ old('pan_number', $transporter->pan_number) }}">
                    @error('pan_number') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
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
                <div class="col-md-6">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input type="text" class="form-control" id="mobile" name="mobile_number" required value="{{ old('mobile_number', $transporter->mobile_number) }}">
                    @error('mobile_number') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter a valid mobile number.</div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $transporter->address) }}</textarea>
                    @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                    <div class="invalid-feedback">Please enter an address.</div>
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
                        <label for="country_id" class="form-label">Country *</label>
                        <div class="input-group">
                            <select class="form-select" id="country_id" name="country_id" required>
                                <option value="">Select Country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ old('country_id', $transporter->country_id) == $country->id ? 'selected' : '' }}>
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
                                        <option value="{{ $state->id }}" {{ old('state_id', $transporter->state_id) == $state->id ? 'selected' : '' }}>
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
                                        <option value="{{ $city->id }}" {{ old('city_id', $transporter->city_id) == $city->id ? 'selected' : '' }}>
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
            {{-- <div class="row mb-3">
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
            </div>   --}}
            </div>
            <!-- Registered Trucks Section -->
{{-- <div class="card mb-4 p-3 bg-light">
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
</div> --}}

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

<!-- Registered Trucks Section -->
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title h5">
            <i class="bi bi-truck"></i> Registered Trucks
        </div>
        <div class="card-tools">
            <button type="button" class="btn btn-success btn-sm" id="addNewTruckBtn">
                <i class="bi bi-plus-circle"></i> Add New Truck
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="trucksContainer">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="trucksTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Truck Number</th>
                            <th>Truck Type</th>
                            <th>Driver</th>
                            <th>Driver Mobile</th>
                            <th>Registration Date</th>
                            <th width="120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transporter->trucks as $truck)
                        <tr id="truck-row-{{ $truck->id }}">
                            <td class="fw-bold">{{ $truck->truck_number }}</td>
                            <td>{{ $truck->truck_type }}</td>
                            <td>{{ $truck->driver->name ?? 'Not Assigned' }}</td>
                            <td>{{ $truck->driver->mobile ?? '-' }}</td>
                            <td>{{ $truck->registration_date ? \Carbon\Carbon::parse($truck->registration_date)->format('d-m-Y') : '-' }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-truck-btn" 
                                            title="Edit Truck" 
                                            data-truck-id="{{ $truck->id }}"
                                            data-truck-number="{{ $truck->truck_number }}"
                                            data-truck-type="{{ $truck->truck_type }}"
                                            data-driver-id="{{ $truck->driver_id }}"
                                            data-registration-date="{{ $truck->registration_date }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-truck-btn" 
                                            title="Delete Truck" 
                                            data-truck-id="{{ $truck->id }}"
                                            data-truck-number="{{ $truck->truck_number }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    {{-- add remove icon here --}}
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-truck-btn" 
                                            title="Remove Truck" 
                                            data-truck-id="{{ $truck->id }}"
                                            data-truck-number="{{ $truck->truck_number }}">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($transporter->trucks->isEmpty())
                <div class="text-center py-4">
                    <i class="bi bi-truck text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">No trucks registered yet.</p>
                    <button type="button" class="btn btn-primary" id="addFirstTruckBtn">
                        <i class="bi bi-plus-circle"></i> Add First Truck
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Truck Modal -->
<div class="modal fade" id="truckModal" tabindex="-1" aria-labelledby="truckModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="truckModalLabel">
                    <i class="bi bi-truck"></i> <span id="truckModalTitle">Add New Truck</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="truckForm" method="POST">
                @csrf
                <input type="hidden" id="truck_id" name="truck_id">
                <input type="hidden" name="transporter_id" value="{{ $transporter->id }}">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="truck_owner_display" class="form-label">Truck Owner *</label>
                            <input type="text" class="form-control" id="truck_owner_display" value="{{ $transporter->name }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="truck_number" class="form-label">Truck Number *</label>
                            <input type="text" class="form-control" placeholder="e.g., MH12AB1234 or GJ01CD5678" id="truck_number" name="truck_number" required>
                            <div class="invalid-feedback">Please enter a valid truck number (6-15 characters).</div>
                            <small class="form-text text-muted">Format: State code + digits + letters + digits (e.g., MH12AB1234)</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="truck_type" class="form-label">Truck Type *</label>
                            <select class="form-select" id="truck_type" name="truck_type" required>
                                <option value="">Select</option>
                                <option value="Open">Open</option>
                                <option value="Closed">Closed</option>
                                <option value="Container">Container</option>
                                <option value="Trailer">Trailer</option>
                                <option value="Tanker">Tanker</option>
                                <option value="Refrigerated">Refrigerated</option>
                                <option value="Flatbed">Flatbed</option>
                                <option value="Tipper">Tipper</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a truck type.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="driver_id" class="form-label">Driver</label>
                            <div class="d-flex gap-2">
                                <select class="form-select" id="driver_id" name="driver_id">
                                    <option value="">Select Driver</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="showAddDriverFormTruck">
                                    <i class="bi bi-plus-circle"></i> Add New
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="registration_date" class="form-label">Registration Date</label>
                            <input type="date" class="form-control" id="registration_date" name="registration_date">
                            <small class="form-text text-muted">Optional field</small>
                        </div>
                    </div>
                    
                    <!-- Add New Driver Form (Initially Hidden) -->
                    <div id="addDriverFormTruck" style="display: none;">
                        <hr class="my-4">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="bi bi-person-plus"></i> Add New Driver</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="new_driver_name_truck" class="form-label">Name *</label>
                                        <input type="text" class="form-control" id="new_driver_name_truck" name="new_driver_name">
                                        <div class="invalid-feedback">Please enter a name.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_driver_mobile_truck" class="form-label">Mobile *</label>
                                        <input type="text" class="form-control" id="new_driver_mobile_truck" name="new_driver_mobile" pattern="[0-9]{10}" maxlength="10">
                                        <div class="invalid-feedback">Please enter a valid 10-digit mobile number.</div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="new_driver_license_truck" class="form-label">License Number</label>
                                        <input type="text" class="form-control" id="new_driver_license_truck" name="new_driver_license">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_driver_address_truck" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="new_driver_address_truck" name="new_driver_address">
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" id="addDriverBtnTruck">
                                        <i class="bi bi-check-circle"></i> Add Driver
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="cancelAddDriverTruck">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> The new driver will be automatically selected after creation.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="saveTruckBtn">
                        <i class="bi bi-save"></i> <span id="saveTruckText">Add Truck</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
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
                    <i class="fas fa-check me-2"></i>Confirm & Update
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
        document.getElementById('confirm-contact-person').textContent = contactPerson;
        document.getElementById('confirm-mobile').textContent = mobile;
        
        document.getElementById('confirm-gst-number').textContent = gstNumber;
        document.getElementById('confirm-pan-number').textContent = panNumber;
        document.getElementById('confirm-pan-active').textContent = panActive;
        
        // Use TDS percentage from database
        const tdsPercentage = '{{ number_format($transporter->tds_percentage ?? 0, 2) }}%';
        document.getElementById('confirm-tds-percentage').textContent = tdsPercentage;
        
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

    // Truck Management JavaScript
    let isEditMode = false;
    let currentTruckId = null;

    // Load drivers for truck modal
    function loadDriversForTruck() {
        $.ajax({
            url: '/api/drivers',
            type: 'GET',
            success: function(drivers) {
                const driverSelect = $('#driver_id');
                driverSelect.empty();
                driverSelect.append('<option value="">Select</option>');
                
                drivers.forEach(function(driver) {
                    driverSelect.append(`<option value="${driver.id}">${driver.name} (${driver.mobile})</option>`);
                });
            },
            error: function() {
                console.log('Failed to load drivers');
            }
        });
    }

    // Show truck modal for adding new truck
    $('#addNewTruckBtn, #addFirstTruckBtn').on('click', function() {
        isEditMode = false;
        currentTruckId = null;
        $('#truckModalTitle').text('Add New Truck');
        $('#saveTruckText').text('Add Truck');
        $('#truckForm')[0].reset();
        $('#truck_id').val('');
        loadDriversForTruck();
        $('#truckModal').modal('show');
    });

    // Show truck modal for editing existing truck
    $(document).on('click', '.edit-truck-btn', function() {
        isEditMode = true;
        currentTruckId = $(this).data('truck-id');
        
        $('#truckModalTitle').text('Edit Truck');
        $('#saveTruckText').text('Update Truck');
        
        // Populate form with truck data
        $('#truck_id').val(currentTruckId);
        $('#truck_number').val($(this).data('truck-number'));
        $('#truck_type').val($(this).data('truck-type'));
        $('#registration_date').val($(this).data('registration-date'));
        
        // Load drivers and select current driver
        loadDriversForTruck();
        setTimeout(() => {
            $('#driver_id').val($(this).data('driver-id'));
        }, 500);
        
        $('#truckModal').modal('show');
    });

    // Handle truck form submission
    $('#truckForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const url = isEditMode ? `/trucks/${currentTruckId}` : '{{ route("trucks.store") }}';
        const method = isEditMode ? 'PUT' : 'POST';
        
        // Add method field for PUT requests
        let data = formData;
        if (isEditMode) {
            data += '&_method=PUT';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function(response) {
                // Show success message
                $('body').prepend(`
                    <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="bi bi-check-circle"></i>
                        <strong>Success!</strong> Truck has been ${isEditMode ? 'updated' : 'added'} successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                setTimeout(function() {
                    $('.alert-success').fadeOut();
                }, 5000);
                
                $('#truckModal').modal('hide');
                location.reload(); // Reload page to show updated trucks
            },
            error: function(xhr) {
                console.log('AJAX Error:', xhr.status, xhr.responseText);
                
                if (xhr.status === 422) {
                    // Handle validation errors
                    const errors = xhr.responseJSON.errors;
                    console.log('Validation errors:', errors);
                    
                    // Clear previous validation states
                    $('#truckForm .form-control, #truckForm .form-select').removeClass('is-invalid');
                    $('#truckForm .invalid-feedback').hide();
                    
                    Object.keys(errors).forEach(function(field) {
                        const input = $(`#${field}`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(errors[field][0]).show();
                    });
                    
                    // Show general validation error message
                    $('body').prepend(`
                        <div class="alert alert-warning alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Validation Error!</strong> Please check the form fields and try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    setTimeout(function() {
                        $('.alert-warning').fadeOut();
                    }, 5000);
                } else {
                    // Show detailed error information
                    let errorMessage = 'Unable to ' + (isEditMode ? 'update' : 'add') + ' truck. ';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage += xhr.responseJSON.message;
                    } else {
                        errorMessage += `Server returned ${xhr.status} error.`;
                    }
                    
                    $('body').prepend(`
                        <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Error!</strong> ${errorMessage}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    setTimeout(function() {
                        $('.alert-danger').fadeOut();
                    }, 5000);
                }
            }
        });
    });

    // Handle truck deletion
    $(document).on('click', '.delete-truck-btn', function() {
        const truckId = $(this).data('truck-id');
        const truckNumber = $(this).data('truck-number');
        
        if (confirm(`Are you sure you want to delete truck "${truckNumber}"? This action cannot be undone.`)) {
            $.ajax({
                url: `/trucks/${truckId}`,
                type: 'POST',
                data: {
                    '_token': $('meta[name="csrf-token"]').attr('content'),
                    '_method': 'DELETE'
                },
                success: function(response) {
                    $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-check-circle"></i>
                            <strong>Success!</strong> Truck "${truckNumber}" has been deleted successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    setTimeout(function() {
                        $('.alert-success').fadeOut();
                    }, 5000);
                    
                    // Remove truck row from table
                    $(`#truck-row-${truckId}`).fadeOut(500, function() {
                        $(this).remove();
                        
                        // Show empty state if no trucks left
                        if ($('#trucksTable tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                },
                error: function(xhr) {
                    $('body').prepend(`
                        <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Error!</strong> Unable to delete truck "${truckNumber}". Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    setTimeout(function() {
                        $('.alert-danger').fadeOut();
                    }, 5000);
                }
            });
        }
    });

    // Handle truck removal (unlink from transporter)
    
    $(document).on('click', '.remove-truck-btn', function() {
    const truckId = $(this).data('truck-id');
    const truckNumber = $(this).data('truck-number');
    const $row = $(`#truck-row-${truckId}`);
    const $button = $(this);
    
    console.log('Remove button clicked for truck ID:', truckId); // Debug log
    
    if (confirm(`Are you sure you want to remove truck "${truckNumber}" from this transporter?`)) {
        // Show loading state
        $button.prop('disabled', true).html('<i class="bi bi-arrow-repeat fa-spin"></i> Removing...');
        
        console.log('Sending AJAX request...'); // Debug log
        
        $.ajax({
            url: `/trucks/${truckId}/remove-transporter`,
            type: 'POST',
            data: {
                '_token': $('meta[name="csrf-token"]').attr('content'),
                '_method': 'PATCH'
            },
            success: function(response) {
    console.log('Success response:', response); // Debug log
    // Re-enable the button in both success and error cases
    $button.prop('disabled', false).html('<i class="bi bi-x-circle"></i>');
    
    if (response.success) {
        // Remove the row from the table
        $row.fadeOut(400, function() {
            $(this).remove();
        });
        
        // Show success message
        showAlert('success', `<i class="bi bi-check-circle"></i> ${response.success}`);
    } else if (response.error) {
        // Show error message from response
        showAlert('danger', `<i class="bi bi-exclamation-triangle"></i> ${response.error}`);
    } else {
        // Fallback error message
        showAlert('danger', '<i class="bi bi-exclamation-triangle"></i> An unknown error occurred');
    }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error); // Debug log
                console.error('Response:', xhr.responseJSON); // Debug log
                
                // Show error message
                const errorMsg = xhr.responseJSON && xhr.responseJSON.error 
                    ? xhr.responseJSON.error 
                    : 'An error occurred while removing the truck. Please try again.';
                
                showAlert('danger', `<i class="bi bi-exclamation-triangle"></i> ${errorMsg}`);
                
                // Re-enable the button
                $button.prop('disabled', false).html('<i class="bi bi-x-circle"></i>');
            }
        });
    }
    
});
function showAlert(type, message) {
    const alertId = 'alert-' + Math.random().toString(36).substr(2, 9);
    const $alert = $(`
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 1060; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `);
    
    // Remove any existing alerts
    $('.alert-dismissible.position-fixed').remove();
    
    // Add new alert
    $('body').prepend($alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        $alert.alert('close');
    }, 5000);
}


    // Reset truck modal when hidden
    $('#truckModal').on('hidden.bs.modal', function() {
        $('#truckForm')[0].reset();
        $('#truckForm .form-control, #truckForm .form-select').removeClass('is-invalid is-valid');
        $('#truckForm .invalid-feedback').hide();
        $('#addDriverFormTruck').hide();
        $('#showAddDriverFormTruck').show();
        isEditMode = false;
        currentTruckId = null;
    });

    // Show/Hide Add Driver Form in Truck Modal
    $('#showAddDriverFormTruck').on('click', function() {
        $('#addDriverFormTruck').slideDown();
        $(this).hide();
    });
    
    $('#cancelAddDriverTruck').on('click', function() {
        $('#addDriverFormTruck').slideUp();
        $('#showAddDriverFormTruck').show();
        // Reset driver form fields
        $('#new_driver_name_truck, #new_driver_mobile_truck, #new_driver_license_truck, #new_driver_address_truck').val('');
        $('#addDriverFormTruck .form-control').removeClass('is-invalid is-valid');
    });
    
    // Handle new driver addition in truck modal
    $('#addDriverBtnTruck').on('click', function(e) {
        e.preventDefault();
        
        // Get form data
        const driverData = {
            name: $('#new_driver_name_truck').val().trim(),
            mobile: $('#new_driver_mobile_truck').val().trim(),
            license_number: $('#new_driver_license_truck').val().trim(),
            address: $('#new_driver_address_truck').val().trim(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        // Basic validation
        let isValid = true;
        if (!driverData.name) {
            $('#new_driver_name_truck').addClass('is-invalid');
            isValid = false;
        } else {
            $('#new_driver_name_truck').removeClass('is-invalid').addClass('is-valid');
        }
        
        if (!driverData.mobile || !/^[0-9]{10}$/.test(driverData.mobile)) {
            $('#new_driver_mobile_truck').addClass('is-invalid');
            isValid = false;
        } else {
            $('#new_driver_mobile_truck').removeClass('is-invalid').addClass('is-valid');
        }
        
        if (!isValid) {
            return;
        }
        
        // Show loading state
        const originalText = $(this).html();
        $(this).html('<i class="bi bi-hourglass-split"></i> Adding...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("drivers.store") }}',
            type: 'POST',
            data: driverData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <i class="bi bi-check-circle"></i>
                            <strong>Success!</strong> Driver added successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    // Reset and hide form
                    $('#new_driver_name_truck, #new_driver_mobile_truck, #new_driver_license_truck, #new_driver_address_truck').val('');
                    $('#addDriverFormTruck .form-control').removeClass('is-invalid is-valid');
                    $('#addDriverFormTruck').slideUp();
                    $('#showAddDriverFormTruck').show();
                    
                    // Reload drivers and auto-select the new one
                    loadDriversForTruck();
                    setTimeout(() => {
                        $('#driver_id').val(response.driver.id);
                    }, 500);
                    
                    // Auto-hide success message
                    setTimeout(function() {
                        $('.alert-success').fadeOut();
                    }, 5000);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Unable to add driver. ';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage += Object.values(errors).flat().join(' ');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage += xhr.responseJSON.message;
                } else {
                    errorMessage += 'Please try again.';
                }
                
                $('body').prepend(`
                    <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Error!</strong> ${errorMessage}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                setTimeout(function() {
                    $('.alert-danger').fadeOut();
                }, 5000);
            },
            complete: function() {
                // Restore button state
                $('#addDriverBtnTruck').html(originalText).prop('disabled', false);
            }
        });
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
                    
                    if (response.ok && data.success && data.country) {
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
                        // Handle validation errors or server errors
                        let errorMessage = data.message || 'Failed to add country';
                        
                        if (data.errors) {
                            // Handle validation errors
                            let errorHtml = '<ul class="mb-0">';
                            Object.values(data.errors).forEach(errors => {
                                errors.forEach(error => {
                                    errorHtml += `<li>${error}</li>`;
                                });
                            });
                            errorHtml += '</ul>';
                            errorDiv.innerHTML = errorHtml;
                        } else {
                            errorDiv.innerHTML = errorMessage;
                        }
                        errorDiv.classList.remove('d-none');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = 'An error occurred while adding the country. Please try again.';
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
                    
                    if (response.ok && data.success && data.state) {
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
                        // Handle validation errors or server errors
                        let errorMessage = data.message || 'Failed to add state';
                        
                        if (data.errors) {
                            // Handle validation errors
                            let errorHtml = '<ul class="mb-0">';
                            Object.values(data.errors).forEach(errors => {
                                errors.forEach(error => {
                                    errorHtml += `<li>${error}</li>`;
                                });
                            });
                            errorHtml += '</ul>';
                            errorDiv.innerHTML = errorHtml;
                        } else {
                            errorDiv.innerHTML = errorMessage;
                        }
                        errorDiv.classList.remove('d-none');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = 'An error occurred while adding the state. Please try again.';
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
                    
                    if (response.ok && data.success && data.city) {
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
                        // Handle validation errors or server errors
                        let errorMessage = data.message || 'Failed to add city';
                        
                        if (data.errors) {
                            // Handle validation errors
                            let errorHtml = '<ul class="mb-0">';
                            Object.values(data.errors).forEach(errors => {
                                errors.forEach(error => {
                                    errorHtml += `<li>${error}</li>`;
                                });
                            });
                            errorHtml += '</ul>';
                            errorDiv.innerHTML = errorHtml;
                        } else {
                            errorDiv.innerHTML = errorMessage;
                        }
                        errorDiv.classList.remove('d-none');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = 'An error occurred while adding the city. Please try again.';
                        errorDiv.classList.remove('d-none');
                    }
                } finally {
                    submitBtn.disabled = false;
                    spinner?.classList.add('d-none');
                }
            });
        }
        
        // Handle country change with validation and reset
        $('#country_id').on('change', function() {
            const countryId = $(this).val();
            const stateSelect = $('#state_id');
            const citySelect = $('#city_id');
            
            // Reset state and city dropdowns when country changes
            stateSelect.html('<option value="">Select State</option>').val('');
            citySelect.html('<option value="">Select City</option>').val('');
            
            // Clear validation errors
            stateSelect.removeClass('is-invalid');
            citySelect.removeClass('is-invalid');
            stateSelect.next('.invalid-feedback').remove();
            citySelect.next('.invalid-feedback').remove();
            
            if (countryId) {
                $.get(`/api/states/${countryId}`)
                    .done(function(data) {
                        data.forEach(function(state) {
                            stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                        });
                    })
                    .fail(function() {
                        console.error('Error loading states for selected country');
                    });
            }
        });

        // Handle state change with validation and reset
        $('#state_id').on('change', function() {
            const stateId = $(this).val();
            const countryId = $('#country_id').val();
            const citySelect = $('#city_id');
            
            // Reset city dropdown when state changes
            citySelect.html('<option value="">Select City</option>').val('');
            
            // Clear validation errors
            citySelect.removeClass('is-invalid');
            citySelect.next('.invalid-feedback').remove();
            
            if (stateId) {
                $.get(`/api/cities/${stateId}`)
                    .done(function(data) {
                        data.forEach(function(city) {
                            citySelect.append(`<option value="${city.id}">${city.name}</option>`);
                        });
                    })
                    .fail(function() {
                        console.error('Error loading cities for selected state');
                    });
            }
        });
        
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
    </script>

@push('scripts')
<script>
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
    });
</script>
@endpush

@endsection