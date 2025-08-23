@extends('layouts.app')

@section('content')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
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
                    <i class="bi bi-plus"></i> Create Consignee/Consignor/Customer</a>
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
                        <label for="lr_number" class="form-label">LR Number * <small class="text-muted">(Must be unique)</small></label>
                        <input type="text" class="form-control" id="lr_number" name="lr_number" value="{{ old('lr_number') }}" required placeholder="Enter unique LR Number">
                        @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a unique LR number.</div>
                        <div id="lr-number-feedback" class="mt-1"></div>
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
                <!-- Memo ID field removed from creation form as it cannot be present at creation time -->
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
                        <label for="transporter_type" class="form-label" id="transporterTypeLabel">Select Type *</label>
                        <select class="form-select" id="transporter_type" name="transporter_type" required>
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
                        <div id="tdsRateDisplay" class="mt-2" style="display: none;">
                            <span class="badge bg-warning text-dark fs-6">
                                🎯 TDS Rate: <span id="tdsPercentageValue">--</span>
                            </span>
                        </div>
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
                        <div class="input-group">
                        <select class="form-select" id="good_type" name="good_type" required>
                            <option value="">Select Goods Type</option>
                            @foreach($goodsTypes as $goodsType)
                                <option value="{{ $goodsType->name }}" {{ old('good_type') == $goodsType->name ? 'selected' : '' }}>
                                    {{ $goodsType->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addGoodsTypeModal" title="Add New Goods Type">
                            <i class="bi bi-plus"></i>
                        </button>
                        </div>
                        @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a goods type.</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="no_of_units" class="form-label">No. of Units <small class="text-muted">(Optional)</small></label>
                        <input type="number" class="form-control" id="no_of_units" name="no_of_units" value="{{ old('no_of_units') }}" min="1" max="100000" title="Maximum 100,000 units allowed">
                        @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a valid number of units (max 100,000).</div>
                        <small class="text-muted">📦 Max: 100,000 units</small>
                    </div>
                    
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="weight" class="form-label">Weight * <small class="text-muted">(in Tons)</small></label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight') }}" min="0.1" max="100" title="Weight must be between 0.1 and 100 tons">
                        @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a weight between 0.1 and 100 tons.</div>
                        <small class="text-muted">📏 Range: 0.1 - 100 tons</small>
                    </div>
                    <div class="col-md-4">
                        <label for="fr_rate" class="form-label">Freight Rate * <small class="text-muted">(₹ per ton)</small></label>
                        <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate') }}" min="1" max="20000" title="Freight rate must be between ₹1 and ₹20,000 per ton">
                        @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a freight rate between ₹1 and ₹20,000.</div>
                        <small class="text-muted">💰 Range: ₹1 - ₹20,000 per ton</small>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount * <small class="text-muted">(Auto-calculated)</small></label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount') }}" title="Auto-calculated: Weight × Freight Rate (editable)">
                        @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter an amount.</div>
                        <small class="text-muted">💡 Calculated as: Weight × Freight Rate</small>
                    </div>
                </div>
            </div>
            <!-- Submit Actions -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Submit Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-4 offset-md-4">
                        <button class="btn btn-success w-100" type="submit">💾 Submit Form</button>
                    </div>
                    
                    <div class="col-md-4">
                        <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-dark w-100">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- Add Goods Type Modal -->
<div class="modal fade" id="addGoodsTypeModal" tabindex="-1" aria-labelledby="addGoodsTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <form id="addGoodsTypeForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="addGoodsTypeModalLabel">Add Goods Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
  
          <div class="modal-body">
            <div id="goodsTypeFormErrors" class="alert alert-danger d-none"></div>
  
            <div class="mb-3">
              <label for="goods_type_name" class="form-label">Goods Type Name *</label>
              <input type="text" class="form-control" id="goods_type_name" name="name" required>
            </div>
          </div>
  
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-info">
              <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              Submit
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  

<!-- Load Select2 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Load SweetAlert2 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Function to check if Select2 is loaded
function waitForSelect2() {
    return new Promise((resolve) => {
        if (typeof $.fn.select2 !== 'undefined') {
            resolve();
            return;
        }
        
        // Wait for Select2 to load
        const checkSelect2 = setInterval(() => {
            if (typeof $.fn.select2 !== 'undefined') {
                clearInterval(checkSelect2);
                console.log('✅ Select2 loaded successfully');
                resolve();
            }
        }, 100);
    });
}

// Initialize Select2 after ensuring it's loaded
$(document).ready(function() {
    waitForSelect2().then(() => {
        // Wait a bit more to ensure Select2 is fully initialized
        setTimeout(() => {
            // Initialize truck dropdown
            if ($('#truck_id').length > 0) {
                $('#truck_id').select2({
                    placeholder: 'Search and select truck...',
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 0,
                    matcher: matchCustom
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
                    width: '100%',
                    minimumInputLength: 0,
                    matcher: matchCustom
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
                    width: '100%',
                    minimumInputLength: 0,
                    matcher: matchCustom
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
                    width: '100%',
                    minimumInputLength: 0,
                    matcher: matchCustom
                });
                console.log('✅ Select2 initialized successfully for consignee dropdown');
            } else {
                console.error('❌ Consignee element not found');
            }
            
            // Initialize transporter_type dropdown
            if ($('#transporter_type').length > 0) {
                $('#transporter_type').select2({
                    placeholder: 'Select Type...',
                    allowClear: true,
                    width: '100%'
                });
                console.log('✅ Select2 initialized successfully for transporter type dropdown');
            } else {
                console.error('❌ Transporter type element not found');
            }
            
            // Custom matcher function for better searching
            function matchCustom(params, data) {
                // If there are no search terms, return all of the data
                if ($.trim(params.term) === '') {
                    return data;
                }
                
                // Do not display the item if there is no 'text' property
                if (typeof data.text === 'undefined') {
                    return null;
                }
                
                // `params.term` should be the term that is used for searching
                // `data.text` is the text that is displayed for the data object
                if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    return data;
                }
                
                // Return `null` if the term should not be displayed
                return null;
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

    // Validation helper functions
    function showValidationError(element, message) {
        // Remove existing error message
        clearValidationError(element);
        
        // Add error styling
        element.classList.add('is-invalid');
        
        // Create and add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block';
        errorDiv.textContent = message;
        errorDiv.id = element.id + '_validation_error';
        
        // Insert after the element
        element.parentNode.insertBefore(errorDiv, element.nextSibling);
        
        console.log('🚨 Validation error shown:', message);
    }

    function clearValidationError(element) {
        // Remove error styling
        element.classList.remove('is-invalid');
        
        // Remove error message
        const existingError = document.getElementById(element.id + '_validation_error');
        if (existingError) {
            existingError.remove();
        }
    }

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
                    // transporterSelect.disabled = true;
                    // transporterSelect.style.backgroundColor = '#e9ecef';
                    // transporterSelect.title = 'Automatically selected based on truck owner';
                    
                    // console.log(`✅ Auto-selected truck owner: ${matchedOwner.name} (ID: ${matchedOwner.id})`);
                    transporterSelect.value = matchedOwner.id;
    transporterSelect.disabled = true;
    transporterSelect.style.backgroundColor = '#e9ecef';
    transporterSelect.title = 'Automatically selected based on truck owner';

    // Hidden input to ensure value is submitted
    let hiddenInput = document.createElement("input");
    hiddenInput.type = "hidden";
    hiddenInput.name = "transporter_id";
    hiddenInput.value = matchedOwner.id;
    transporterSelect.form.appendChild(hiddenInput);

    console.log(`✅ Auto-selected truck owner: ${matchedOwner.name} (ID: ${matchedOwner.id})`);
                    
                    // Show TDS percentage for auto-selected truck owner
                    showTdsPercentage(matchedOwner.id);
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

    // Function to show TDS percentage for a transporter
    function showTdsPercentage(transporterId) {
        const tdsDisplay = document.getElementById('tdsRateDisplay');
        const tdsValue = document.getElementById('tdsPercentageValue');
        
        if (transporterId) {
            // Show loading state
            tdsDisplay.style.display = 'block';
            tdsValue.textContent = 'Loading...';
            
            // Fetch transporter details to get TDS percentage
            fetch(`/api/transporters/${transporterId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.tds_percentage !== null && data.tds_percentage !== undefined) {
                        tdsValue.textContent = `${parseFloat(data.tds_percentage).toFixed(2)}%`;
                    } else {
                        tdsValue.textContent = 'Not Set';
                    }
                })
                .catch(error => {
                    console.error('Error fetching transporter TDS percentage:', error);
                    tdsValue.textContent = 'Error';
                });
        } else {
            // Hide TDS display when no transporter selected
            tdsDisplay.style.display = 'none';
        }
    }

    // Handle transporter selection to show TDS percentage
    transporterSelect.addEventListener('change', function() {
        const transporterId = this.value;
        showTdsPercentage(transporterId);
    });

    // Handle consignor contact number and location details
    const consignorSelect = document.getElementById('consignor_id');
    const consignorContactInput = document.getElementById('consignor_contact_number');
    const sourceCityInput = document.getElementById('source_city');
    const sourceStateInput = document.getElementById('source_state');

    // Handle consignor selection with Select2 support
    function handleConsignorSelection(consignorId) {
        console.log('👤 Consignor selected:', consignorId);
        
        // Check if consignor and consignee are the same
        const consigneeId = consigneeSelect.value;
        if (consignorId && consigneeId && consignorId === consigneeId) {
            console.warn('⚠️ Consignor and Consignee cannot be the same!');
            showValidationError(consignorSelect, 'Consignor and Consignee cannot be the same');
            
            // Clear the consignor selection
            $('#consignor_id').val(null).trigger('change');
            consignorContactInput.value = '';
            sourceCityInput.value = '';
            sourceStateInput.value = '';
            return;
        } else {
            clearValidationError(consignorSelect);
        }
        
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
                    console.log('📊 Consignor data keys:', Object.keys(data));
                    
                    // Auto-populate consignor fields
                    consignorContactInput.value = data.contact_number || '';
                    // Use city and state names from the API response
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
        
        // Check if consignor and consignee are the same
        const consignorId = consignorSelect.value;
        if (consigneeId && consignorId && consigneeId === consignorId) {
            console.warn('⚠️ Consignee and Consignor cannot be the same!');
            showValidationError(consigneeSelect, 'Consignee and Consignor cannot be the same');
            
            // Clear the consignee selection
            $('#consignee_id').val(null).trigger('change');
            consigneeContactInput.value = '';
            destinationCityInput.value = '';
            destinationStateInput.value = '';
            return;
        } else {
            clearValidationError(consigneeSelect);
        }
        
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
                    console.log('📊 Consignee data keys:', Object.keys(data));
                    
                    // Auto-populate consignee fields
                    consigneeContactInput.value = data.contact_number || '';
                    // Use city and state names from the API response
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

    // Custom form validation function
    function validateConsignorConsignee() {
        const consignorId = consignorSelect.value;
        const consigneeId = consigneeSelect.value;
        
        if (consignorId && consigneeId && consignorId === consigneeId) {
            showValidationError(consignorSelect, 'Consignor and Consignee cannot be the same');
            showValidationError(consigneeSelect, 'Consignor and Consignee cannot be the same');
            return false;
        }
        
        clearValidationError(consignorSelect);
        clearValidationError(consigneeSelect);
        return true;
    }

    // Bootstrap form validation with custom validation and confirmation dialog
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                // Always prevent default submission first
                event.preventDefault();
                event.stopPropagation();
                
                // Run custom validation first
                const customValidationPassed = validateConsignorConsignee();
                
                if (!form.checkValidity() || !customValidationPassed) {
                    if (!customValidationPassed) {
                        console.error('🚨 Form submission blocked: Consignor and Consignee cannot be the same');
                    }
                    form.classList.add('was-validated');
                    return;
                }
                
                // If validation passes, show confirmation dialog
                showConfirmationDialog(form);
                form.classList.add('was-validated');
            }, false);
        });
    })();

    // Auto-calculate amount based on weight × freight rate
    const weightInput = document.getElementById('weight');
    const frRateInput = document.getElementById('fr_rate');
    const amountInput = document.getElementById('amount');
    
    function calculateAmount() {
        const weight = parseFloat(weightInput.value) || 0;
        const frRate = parseFloat(frRateInput.value) || 0;
        
        if (weight > 0 && frRate > 0) {
            const calculatedAmount = weight * frRate;
            amountInput.value = calculatedAmount.toFixed(2);
            
            // Add visual feedback to show it's auto-calculated
            amountInput.style.backgroundColor = '#e8f5e8';
            amountInput.title = `Auto-calculated: ${weight} × ${frRate} = ${calculatedAmount.toFixed(2)}`;
            
            console.log(`💰 Amount auto-calculated: ${weight} × ${frRate} = ${calculatedAmount.toFixed(2)}`);
        }
    }
    
    // Add event listeners for weight and freight rate changes
    weightInput.addEventListener('input', function() {
        validateWeight();
        calculateAmount();
    });
    frRateInput.addEventListener('input', calculateAmount);
    
    // Weight validation function
    function validateWeight() {
        const weight = parseFloat(weightInput.value);
        
        if (isNaN(weight)) {
            return; // Let HTML5 validation handle empty/invalid input
        }
        
        if (weight < 0.1) {
            weightInput.setCustomValidity('Weight must be at least 0.1 tons');
            weightInput.classList.add('is-invalid');
            console.warn('⚠️ Weight too low:', weight);
        } else if (weight > 100) {
            weightInput.setCustomValidity('Weight cannot exceed 100 tons');
            weightInput.classList.add('is-invalid');
            console.warn('⚠️ Weight too high:', weight);
        } else {
            weightInput.setCustomValidity('');
            weightInput.classList.remove('is-invalid');
            console.log('✅ Valid weight:', weight, 'tons');
        }
    }
    
    // Add blur validation for weight
    weightInput.addEventListener('blur', validateWeight);
    
    // Allow user to manually edit amount (remove auto-calculated styling when manually edited)
    amountInput.addEventListener('input', function() {
        // Remove auto-calculated styling when user manually edits
        this.style.backgroundColor = '';
        this.title = 'Manually edited amount';
        console.log('✏️ Amount manually edited by user');
    });
    
    // Calculate amount on page load if values exist
    if (weightInput.value && frRateInput.value) {
        calculateAmount();
    }



    // LR Number uniqueness validation
    const lrNumberInput = document.getElementById('lr_number');
    const lrNumberFeedback = document.getElementById('lr-number-feedback');
    let lrNumberTimeout;
    
    function checkLrNumberUniqueness(lrNumber) {
        if (!lrNumber || lrNumber.length < 2) {
            lrNumberFeedback.innerHTML = '';
            lrNumberInput.classList.remove('is-valid', 'is-invalid');
            return;
        }
        
        // Show loading state
        lrNumberFeedback.innerHTML = '<small class="text-info">🔍 Checking availability...</small>';
        lrNumberInput.classList.remove('is-valid', 'is-invalid');
        
        fetch('/api/check-lr-number-uniqueness', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                lr_number: lrNumber
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                lrNumberFeedback.innerHTML = '<small class="text-success">✅ ' + data.message + '</small>';
                lrNumberInput.classList.remove('is-invalid');
                lrNumberInput.classList.add('is-valid');
                lrNumberInput.setCustomValidity('');
            } else {
                lrNumberFeedback.innerHTML = '<small class="text-danger">❌ ' + data.message + '</small>';
                lrNumberInput.classList.remove('is-valid');
                lrNumberInput.classList.add('is-invalid');
                lrNumberInput.setCustomValidity(data.message);
            }
        })
        .catch(error => {
            console.error('Error checking LR Number uniqueness:', error);
            lrNumberFeedback.innerHTML = '<small class="text-warning">⚠️ Unable to check availability</small>';
            lrNumberInput.classList.remove('is-valid', 'is-invalid');
        });
    }
    
    // Add debounced input listener for LR Number
    lrNumberInput.addEventListener('input', function() {
        const lrNumber = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(lrNumberTimeout);
        
        // Set new timeout for debounced checking
        lrNumberTimeout = setTimeout(() => {
            checkLrNumberUniqueness(lrNumber);
        }, 500); // 500ms delay
    });
    
    // Check on blur as well
    lrNumberInput.addEventListener('blur', function() {
        const lrNumber = this.value.trim();
        if (lrNumber) {
            clearTimeout(lrNumberTimeout);
            checkLrNumberUniqueness(lrNumber);
        }
    });

    // No. of Units validation
    const noOfUnitsInput = document.getElementById('no_of_units');
    
    function validateNoOfUnits() {
        const value = parseFloat(noOfUnitsInput.value);
        
        if (noOfUnitsInput.value && (isNaN(value) || value < 1 || value > 100000)) {
            noOfUnitsInput.setCustomValidity('Please enter a valid number of units between 1 and 100,000.');
            noOfUnitsInput.classList.add('is-invalid');
            noOfUnitsInput.classList.remove('is-valid');
        } else {
            noOfUnitsInput.setCustomValidity('');
            noOfUnitsInput.classList.remove('is-invalid');
            if (noOfUnitsInput.value) {
                noOfUnitsInput.classList.add('is-valid');
            } else {
                noOfUnitsInput.classList.remove('is-valid');
            }
        }
    }
    
    // Add event listeners for No. of Units validation
    noOfUnitsInput.addEventListener('input', validateNoOfUnits);
    noOfUnitsInput.addEventListener('blur', validateNoOfUnits);

    // Freight Rate validation (using existing frRateInput variable)
    function validateFreightRate() {
        const value = parseFloat(frRateInput.value);
        
        if (frRateInput.value && (isNaN(value) || value < 1 || value > 20000)) {
            frRateInput.setCustomValidity('Please enter a valid freight rate between ₹1 and ₹20,000.');
            frRateInput.classList.add('is-invalid');
            frRateInput.classList.remove('is-valid');
        } else if (frRateInput.value) {
            frRateInput.setCustomValidity('');
            frRateInput.classList.remove('is-invalid');
            frRateInput.classList.add('is-valid');
        } else {
            frRateInput.setCustomValidity('');
            frRateInput.classList.remove('is-invalid', 'is-valid');
        }
    }
    
    // Add event listeners for Freight Rate validation
    frRateInput.addEventListener('input', validateFreightRate);
    frRateInput.addEventListener('blur', validateFreightRate);

    // Function to show confirmation dialog with form details
    function showConfirmationDialog(form) {
        const formData = new FormData(form);
        
        // Get display values for dropdowns
        const customerName = document.querySelector('#customer_id option:checked')?.textContent || 'Not selected';
        const consignorName = document.querySelector('#consignor_id option:checked')?.textContent || 'Not selected';
        const consigneeName = document.querySelector('#consignee_id option:checked')?.textContent || 'Not selected';
        const truckNumber = document.querySelector('#truck_id option:checked')?.textContent || 'Not selected';
        const transporterName = document.querySelector('#transporter_id option:checked')?.textContent || 'Not selected';
        
        // Build confirmation message
        const confirmationHTML = `
            <div class="confirmation-details">
                <h5 class="text-primary mb-3">📋 Please verify the following details:</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light"><strong>📄 Basic Information</strong></div>
                            <div class="card-body">
                                <p><strong>LR Number:</strong> ${formData.get('lr_number') || 'Not entered'}</p>
                                <p><strong>GR Date:</strong> ${formData.get('gr_date') || 'Not selected'}</p>
                                <p><strong>Customer:</strong> ${customerName}</p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light"><strong>🚛 Transport Details</strong></div>
                            <div class="card-body">
                                <p><strong>Truck:</strong> ${truckNumber}</p>
                                <p><strong>Transporter:</strong> ${transporterName}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light"><strong>👥 Consignor & Consignee</strong></div>
                            <div class="card-body">
                                <p><strong>Consignor:</strong> ${consignorName}</p>
                                <p><strong>Consignee:</strong> ${consigneeName}</p>
                                <p><strong>Source:</strong> ${formData.get('source_city') || 'Not entered'}, ${formData.get('source_state') || 'Not entered'}</p>
                                <p><strong>Destination:</strong> ${formData.get('destination_city') || 'Not entered'}, ${formData.get('destination_state') || 'Not entered'}</p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light"><strong>📦 Goods & Charges</strong></div>
                            <div class="card-body">
                                <p><strong>Goods Type:</strong> ${formData.get('good_type') || 'Not selected'}</p>
                                <p><strong>Weight:</strong> ${formData.get('weight') || 'Not entered'} tons</p>
                                <p><strong>No. of Units:</strong> ${formData.get('no_of_units') || 'Not entered'}</p>
                                <p><strong>Freight Rate:</strong> ₹${formData.get('fr_rate') || 'Not entered'}</p>
                                <p><strong>Amount:</strong> ₹${formData.get('amount') || 'Not entered'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Please review all details carefully before confirming submission.
                </div>
            </div>
        `;
        
        // Show confirmation dialog using SweetAlert2 or Bootstrap modal
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '📋 Confirm Lorry Receipt Details',
                html: confirmationHTML,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '✅ Yes, Submit',
                cancelButtonText: '❌ Cancel',
                width: '90%',
                customClass: {
                    popup: 'swal-wide'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Actually submit the form
                    form.submit();
                }
            });
        } else {
            // Fallback to native confirm dialog
            const confirmText = `Please confirm the following details:\n\n` +
                `LR Number: ${formData.get('lr_number')}\n` +
                `Customer: ${customerName}\n` +
                `Consignor: ${consignorName}\n` +
                `Consignee: ${consigneeName}\n` +
                `Weight: ${formData.get('weight')} tons\n` +
                `Amount: ₹${formData.get('amount')}\n\n` +
                `Do you want to submit this lorry receipt?`;
                
            if (confirm(confirmText)) {
                form.submit();
            }
        }
    }
});
// Handle Add Goods Type Form Submission
document.getElementById('addGoodsTypeForm').addEventListener('submit', function (e) {
    e.preventDefault();

    let form = this;
    let submitBtn = form.querySelector('button[type="submit"]');
    let spinner = submitBtn.querySelector('.spinner-border');
    let errorBox = document.getElementById('goodsTypeFormErrors');

    // Reset error
    errorBox.classList.add('d-none');
    errorBox.innerHTML = '';

    // Show spinner
    spinner.classList.remove('d-none');
    submitBtn.disabled = true;

    fetch("{{ route('master.goods-types.store') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            "Accept": "application/json",
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            name: document.getElementById('goods_type_name').value,
            from_modal: true
        })
    })
    .then(response => response.json())
    .then(data => {
        spinner.classList.add('d-none');
        submitBtn.disabled = false;

        if (data.success) {
            // Add new option to Goods Type dropdown
            $('#goodsTypeModal').modal('hide');

            // ✅ Remove backdrop if still visible
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            let select = document.getElementById('good_type');

            let newOption = new Option(data.goodsType.name, data.goodsType.name, true, true);
            select.add(newOption);
            select.value = data.goodsType.name;

            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('addGoodsTypeModal')).hide();

            // Reset form
            form.reset();
        } else {
            errorBox.classList.remove('d-none');
            errorBox.innerHTML = 'Failed to add goods type';
        }
    })
    .catch(error => {
        spinner.classList.add('d-none');
        submitBtn.disabled = false;
        errorBox.classList.remove('d-none');
        errorBox.innerHTML = '⚠️ Error: ' + error.message;
    });
});


</script>


@endsection

