@extends('layouts.app')

@section('content')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

/* Select2 custom styling for truck dropdown */
.select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
}

.select2-result-truck {
    padding: 4px 0;
}

.truck-info {
    font-size: 14px;
    line-height: 1.4;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    padding-left: 12px;
    padding-right: 20px;
    color: #212529;
    line-height: 36px;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: 12px;
}

.select2-dropdown {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
</style>
<div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
    <!--begin::Header-->
    <div class="card-header">
        <div class="d-flex w-100">
            <div class="card-title h4">Add New Lorry Receipt</div>
            <div class="ms-auto">
                <a href="{{ route('consignees.create') }}" class="btn btn-info">
                    <i class="bi bi-plus"></i> Create Consignee
                </a>
            </div>
        </div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('lorry_receipts.store') }}" id="lorryReceiptForm" class="needs-validation" novalidate="">
        @csrf
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
        <div class="card-body">
            <!-- General Details -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">General Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="lr_number" class="form-label">LR Number *</label>
                        <input type="text" class="form-control" id="lr_number" name="lr_number" value="{{ old('lr_number') }}" required placeholder="Enter LR Number">
                        @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter an LR number.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="gr_date" class="form-label">GR Date *</label>
                        <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ old('gr_date') }}">
                        @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a GR date.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="customer_id" class="form-label">Customer *</label>
                        <select class="form-select" id="customer_id" name="customer_id" required data-placeholder="Search and select customer...">
                            <option value="">Select Customer</option>
                            @foreach($consignees as $consignee)
                                <option value="{{ $consignee->id }}" {{ old('customer_id') == $consignee->id ? 'selected' : '' }}>
                                    {{ $consignee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="truck_id" class="form-label">Truck *</label>
                        <select class="form-select" id="truck_id" name="truck_id" required data-placeholder="Search and select truck...">
                            <option value="">Select Trucks</option>
                            @foreach($trucks as $truck)
                                <option value="{{ $truck->id }}" {{ old('truck_id') == $truck->id ? 'selected' : '' }}>
                                    {{ $truck->truck_number }} 
                                    @if($truck->driver)
                                        - Driver: {{ $truck->driver->name }}
                                    @endif
                                    ({{ $truck->transporter ? $truck->transporter->name : 'No Transporter' }})
                                </option>
                            @endforeach
                        </select>
                        @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a truck.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="transporter_type" class="form-label">Select Type *</label>
                        <select class="form-select" id="transporter_type" required>
                            <option value="">Select Type</option>
                            <option value="owner">Truck Owner</option>
                            <option value="agency">Agency</option>
                        </select>
                        <div class="invalid-feedback">Please select transporter type.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="transporter_id" id="transporterLabel" class="form-label">Transporter *</label>
                        <select class="form-select" id="transporter_id" name="transporter_id" required>
                            <option value="">Select Transporter</option>
                            {{-- This will be filled via JavaScript --}} 
                        </select>
                        @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a transporter.</div>
                        <p id="tdsRateDisplay" class="form-control-plaintext text-primary fw-bold">--</p>
                    </div>
                </div>
            </div>

            <!-- Consignor & Consignee Details -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Consignor & Consignee Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="consignor_id" class="form-label">Consignor *</label>
                        <select class="form-select" id="consignor_id" name="consignor_id" required data-placeholder="Search and select consignor...">
                            <option value="">Select Consignor</option>
                            @foreach($consignees as $consignee) 
                                <option value="{{ $consignee->id }}" {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                    {{ $consignee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('consignor_id') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a consignor.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                        <input type="text" class="form-control" id="consignor_contact_number" name="consignor_contact_number" readonly>
                    </div>
                  
                </div>
                <div class="row mb-3">
                <div class="col-md-4">
                        <label for="consignee_id" class="form-label">Consignee *</label>
                        <select class="form-select" id="consignee_id" name="consignee_id" required data-placeholder="Search and select consignee...">
                            <option value="">Select Consignee</option>
                            @foreach($consignees as $consignee)
                                <option value="{{ $consignee->id }}" {{ old('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                    {{ $consignee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('consignee_id') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a consignee.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                        <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" readonly>
                    </div>
                </div>
            </div>
            <!-- Route Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Route Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="source_city" class="form-label">Source City *</label>
                        <input type="text" class="form-control" id="source_city" name="source_city" required value="{{ old('source_city') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="source_state" class="form-label">Source State *</label>
                        <input type="text" class="form-control" id="source_state" name="source_state" required value="{{ old('source_state') }}" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="destination_city" class="form-label">Destination City *</label>
                        <input type="text" class="form-control" id="destination_city" name="destination_city" required value="{{ old('destination_city') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="destination_state" class="form-label">Destination State *</label>
                        <input type="text" class="form-control" id="destination_state" name="destination_state" required value="{{ old('destination_state') }}" readonly>
                    </div>
                </div>
            </div>
            <!-- Goods & Charges Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Goods & Charges</h5></div></div>
                <div class="row mb-3">
                    <input type="hidden" id="lane" name="lane" value="Road">
                    <div class="col-md-4">
                        <label for="good_type" class="form-label">Goods Type *</label>
                        <select class="form-select" id="good_type" name="good_type" required>
                            <option value="">Select Goods Type</option>
                            @foreach($goodsTypes as $goodsType)
                                <option value="{{ $goodsType->name }}" {{ old('good_type') == $goodsType->name ? 'selected' : '' }}>
                                    {{ $goodsType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a goods type.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="weight" class="form-label">Weight *</label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight') }}">
                        @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a weight.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="no_of_units" class="form-label">No. of Units *</label>
                        <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="{{ old('no_of_units') }}">
                        @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter number of units.</div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="fr_rate" class="form-label">Freight Rate *</label>
                        <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate') }}">
                        @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a freight rate.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount *</label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount') }}">
                        @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter an amount.</div>
                    </div>
                </div>
            </div>
            <!-- Submit Actions -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Submit Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <button class="btn btn-success w-100" type="submit">💾 Submit Form</button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-secondary w-100" type="button" title="Copies most data, clears variable fields">🔄 Save & Create Similar</button>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-dark w-100">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Load Select2 dynamically to ensure it loads properly -->
<script>
// Function to load Select2 dynamically
function loadSelect2() {
    return new Promise((resolve, reject) => {
        if (typeof $.fn.select2 !== 'undefined') {
            resolve();
            return;
        }
        
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        script.onload = () => {
            console.log('✅ Select2 loaded successfully');
            resolve();
        };
        script.onerror = () => {
            console.error('❌ Failed to load Select2');
            reject();
        };
        document.head.appendChild(script);
    });
}

// Initialize Select2 after ensuring it's loaded
$(document).ready(function() {
    loadSelect2().then(() => {
        // Wait a bit more to ensure Select2 is fully initialized
        setTimeout(() => {
            // Initialize truck dropdown
            if ($('#truck_id').length > 0) {
                $('#truck_id').select2({
                    placeholder: 'Search and select truck...',
                    allowClear: true,
                    width: '100%'
                });
                console.log('✅ Select2 initialized successfully for truck dropdown');
            } else {
                console.error('❌ Truck element not found');
            }
            
            // Initialize customer dropdown
            if ($('#customer_id').length > 0) {
                $('#customer_id').select2({
                    placeholder: 'Search and select customer...',
                    allowClear: true,
                    width: '100%'
                });
                console.log('✅ Select2 initialized successfully for customer dropdown');
            } else {
                console.error('❌ Customer element not found');
            }
            
            // Initialize consignor dropdown
            if ($('#consignor_id').length > 0) {
                $('#consignor_id').select2({
                    placeholder: 'Search and select consignor...',
                    allowClear: true,
                    width: '100%'
                });
                console.log('✅ Select2 initialized successfully for consignor dropdown');
            } else {
                console.error('❌ Consignor element not found');
            }
            
            // Initialize consignee dropdown
            if ($('#consignee_id').length > 0) {
                $('#consignee_id').select2({
                    placeholder: 'Search and select consignee...',
                    allowClear: true,
                    width: '100%'
                });
                console.log('✅ Select2 initialized successfully for consignee dropdown');
            } else {
                console.error('❌ Consignee element not found');
            }
        }, 100);
    }).catch(() => {
        console.error('❌ Failed to initialize Select2 - library could not be loaded');
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const transporterTypeSelect = document.getElementById('transporter_type');
    const truckSelect = document.getElementById('truck_id');
    const transporterSelect = document.getElementById('transporter_id');
    const transporterLabel = document.getElementById('transporterLabel');
    
    // Store truck data for easy access
    let trucksData = {};

    // Handle truck selection change
    $('#truck_id').on('select2:select', function (e) {
        const selectedTruckId = e.params.data.id;
        
        console.log('🚛 Truck selected:', selectedTruckId);
        
        // Get truck data from the selected option
        const selectedOption = $(this).find('option:selected');
        const truckText = selectedOption.text().trim();
        
        console.log('📝 Truck text:', truckText);
        
        // Extract transporter info from truck text (format: "TruckNumber - Driver: DriverName (TransporterName)")
        const transporterMatch = truckText.match(/\(([^)]+)\)$/);
        if (transporterMatch) {
            const transporterName = transporterMatch[1].trim();
            
            console.log('🏢 Extracted transporter name:', transporterName);
            
            // Store truck-transporter relationship
            trucksData[selectedTruckId] = {
                transporterName: transporterName,
                truckText: truckText
            };
            
            // If "Truck Owner" is selected, auto-select the truck owner
            if (transporterTypeSelect.value === 'owner') {
                console.log('🎯 Auto-selecting truck owner for:', transporterName);
                autoSelectTruckOwner(selectedTruckId, transporterName);
            } else {
                console.log('ℹ️ Transporter type is not "owner", current value:', transporterTypeSelect.value);
            }
        } else {
            console.warn('⚠️ Could not extract transporter name from truck text:', truckText);
        }
    });

    // Also handle regular change event as fallback
    $('#truck_id').on('change', function () {
        const selectedTruckId = $(this).val();
        if (selectedTruckId && !trucksData[selectedTruckId]) {
            // Trigger the same logic as select2:select
            const selectedOption = $(this).find('option:selected');
            const truckText = selectedOption.text().trim();
            
            const transporterMatch = truckText.match(/\(([^)]+)\)$/);
            if (transporterMatch) {
                const transporterName = transporterMatch[1].trim();
                
                trucksData[selectedTruckId] = {
                    transporterName: transporterName,
                    truckText: truckText
                };
                
                if (transporterTypeSelect.value === 'owner') {
                    autoSelectTruckOwner(selectedTruckId, transporterName);
                }
            }
        }
    });

    // Handle transporter type change
    transporterTypeSelect.addEventListener('change', function () {
        const selectedType = this.value;
        const selectedTruckId = truckSelect.value;
        
        transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
        transporterSelect.disabled = false; // Reset disabled state
        
        if (selectedType === 'owner') {
            transporterLabel.textContent = 'Truck Owner *';
            
            // If a truck is already selected, auto-select its owner
            if (selectedTruckId && trucksData[selectedTruckId]) {
                autoSelectTruckOwner(selectedTruckId, trucksData[selectedTruckId].transporterName);
            } else {
                // Fetch all truck owners
                fetch('/api/truck-owners')
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(owner => {
                            const option = document.createElement('option');
                            option.value = owner.id;
                            option.textContent = owner.name;
                            transporterSelect.appendChild(option);
                        });
                    });
            }
        } else if (selectedType === 'agency') {
            transporterLabel.textContent = 'Agency *';
            // Fetch agencies
            fetch('/api/agencies')
                .then(response => response.json())
                .then(data => {
                    data.forEach(agency => {
                        const option = document.createElement('option');
                        option.value = agency.id;
                        option.textContent = agency.name;
                        transporterSelect.appendChild(option);
                    });
                });
        }
    });
    
    // Function to auto-select truck owner
    function autoSelectTruckOwner(truckId, transporterName) {
        console.log('🔄 Fetching truck owners from API...');
        
        // Fetch truck owners and find the matching one
        fetch('/api/truck-owners')
            .then(response => {
                console.log('📡 API response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('📊 Truck owners data received:', data);
                
                transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
                
                let ownerFound = false;
                let matchedOwner = null;
                
                data.forEach(owner => {
                    const option = document.createElement('option');
                    option.value = owner.id;
                    option.textContent = owner.name;
                    
                    console.log('🔍 Comparing:', owner.name, 'with', transporterName);
                    
                    // Auto-select if this is the truck's owner (case-insensitive comparison)
                    if (owner.name.toLowerCase().trim() === transporterName.toLowerCase().trim()) {
                        option.selected = true;
                        ownerFound = true;
                        matchedOwner = owner;
                        console.log('✅ Found matching owner:', owner.name);
                    }
                    
                    transporterSelect.appendChild(option);
                });
                
                if (ownerFound) {
                    // Disable the dropdown since it's auto-selected
                    transporterSelect.disabled = true;
                    transporterSelect.style.backgroundColor = '#e9ecef';
                    transporterSelect.title = 'Automatically selected based on truck owner';
                    
                    console.log(`✅ Auto-selected truck owner: ${matchedOwner.name} (ID: ${matchedOwner.id})`);
                } else {
                    console.warn(`⚠️ No matching truck owner found for: "${transporterName}"`);
                    console.log('Available owners:', data.map(o => o.name));
                    
                    // Re-enable the dropdown if no match found
                    transporterSelect.disabled = false;
                    transporterSelect.style.backgroundColor = '';
                    transporterSelect.title = '';
                }
            })
            .catch(error => {
                console.error('❌ Error fetching truck owners:', error);
                
                // Re-enable the dropdown on error
                transporterSelect.disabled = false;
                transporterSelect.style.backgroundColor = '';
                transporterSelect.title = '';
            });
    }

    // Handle consignor contact number and location details
    const consignorSelect = document.getElementById('consignor_id');
    const consignorContactInput = document.getElementById('consignor_contact_number');
    const sourceCityInput = document.getElementById('source_city');
    const sourceStateInput = document.getElementById('source_state');

    // Handle consignor selection with Select2 support
    function handleConsignorSelection(consignorId) {
        console.log('👤 Consignor selected:', consignorId);
        
        if (consignorId) {
            console.log('🔄 Fetching consignor data from API...');
            fetch(`/api/consignees/${consignorId}`)
                .then(response => {
                    console.log('📡 Consignor API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📊 Consignor data received:', data);
                    
                    // Auto-populate consignor fields
                    consignorContactInput.value = data.contact_number || '';
                    sourceCityInput.value = data.city || '';
                    sourceStateInput.value = data.state || '';
                    
                    console.log('✅ Auto-populated consignor fields:');
                    console.log('  📞 Contact:', data.contact_number || 'N/A');
                    console.log('  🏙️ City:', data.city || 'N/A');
                    console.log('  🗺️ State:', data.state || 'N/A');
                })
                .catch(error => {
                    console.error('❌ Error fetching consignor data:', error);
                });
        } else {
            console.log('🧹 Clearing consignor fields...');
            consignorContactInput.value = '';
            sourceCityInput.value = '';
            sourceStateInput.value = '';
        }
    }

    // Handle Select2 consignor selection
    $('#consignor_id').on('select2:select', function (e) {
        const consignorId = e.params.data.id;
        handleConsignorSelection(consignorId);
    });

    // Handle regular consignor selection (fallback)
    consignorSelect.addEventListener('change', function () {
        const consignorId = this.value;
        handleConsignorSelection(consignorId);
    });

    // Handle consignee contact number and location details
    const consigneeSelect = document.getElementById('consignee_id');
    const consigneeContactInput = document.getElementById('consignee_contact_number');
    const destinationCityInput = document.getElementById('destination_city');
    const destinationStateInput = document.getElementById('destination_state');

    // Handle consignee selection with Select2 support
    function handleConsigneeSelection(consigneeId) {
        console.log('🏢 Consignee selected:', consigneeId);
        
        if (consigneeId) {
            console.log('🔄 Fetching consignee data from API...');
            fetch(`/api/consignees/${consigneeId}`)
                .then(response => {
                    console.log('📡 Consignee API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📊 Consignee data received:', data);
                    
                    // Auto-populate consignee fields
                    consigneeContactInput.value = data.contact_number || '';
                    destinationCityInput.value = data.city || '';
                    destinationStateInput.value = data.state || '';
                    
                    console.log('✅ Auto-populated consignee fields:');
                    console.log('  📞 Contact:', data.contact_number || 'N/A');
                    console.log('  🏙️ Destination City:', data.city || 'N/A');
                    console.log('  🗺️ Destination State:', data.state || 'N/A');
                })
                .catch(error => {
                    console.error('❌ Error fetching consignee data:', error);
                });
        } else {
            console.log('🧹 Clearing consignee fields...');
            consigneeContactInput.value = '';
            destinationCityInput.value = '';
            destinationStateInput.value = '';
        }
    }

    // Handle Select2 consignee selection
    $('#consignee_id').on('select2:select', function (e) {
        const consigneeId = e.params.data.id;
        handleConsigneeSelection(consigneeId);
    });

    // Handle regular consignee selection (fallback)
    consigneeSelect.addEventListener('change', function () {
        const consigneeId = this.value;
        handleConsigneeSelection(consigneeId);
    });

    // Handle truck selection for transporter info
    truckSelect.addEventListener('change', function () {
        const truckId = this.value;
        if (truckId) {
            fetch(`/api/trucks/${truckId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.transporter) {
                        document.getElementById('tdsRateDisplay').textContent = `TDS Rate: ${data.transporter.tds_rate || 'N/A'}%`;
                    }
                });
        } else {
            document.getElementById('tdsRateDisplay').textContent = '--';
        }
    });

    // Bootstrap form validation
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
});
</script>

@endsection
