@extends('layouts.app')

@section('content')
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
            <div class="card-title h4">📝 Edit Lorry Receipt</div>
            <div class="ms-auto">
                <a href="{{ route('consignees.create') }}" class="btn btn-info">
                    <i class="bi bi-plus"></i> Create Consignee/Consignor/Customer</a>
            </div>
        </div>
    </div>
    <!--end::Header-->
    <!--begin::Form-->
    <form method="POST" action="{{ route('lorry_receipts.update', $lorry_receipt->id) }}" id="lorryReceiptForm" class="needs-validation" novalidate="">
        @csrf
        @method('PUT')
        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                
        <div class="card-body">
            <!-- General Details -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">General Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="lr_number" class="form-label">LR Number * <small class="text-muted">(Must be unique)</small></label>
                        <input type="text" class="form-control" id="lr_number" name="lr_number" value="{{ old('lr_number', $lorry_receipt->lr_number) }}" required placeholder="Enter unique LR Number">
                        @error('lr_number') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a unique LR number.</div>
                        <div id="lr-number-feedback" class="mt-1"></div>
                    </div>
                    <div class="col-md-4">
                        <label for="gr_date" class="form-label">GR Date *</label>
                        <input type="date" class="form-control" id="gr_date" name="gr_date" required value="{{ old('gr_date', $lorry_receipt->gr_date) }}">
                        @error('gr_date') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a GR date.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="customer_id" class="form-label">Customer *</label>
                        <!-- Debug info -->
                        <!-- Customer ID: {{ $lorry_receipt->customer_id }} -->
                        <!-- Customer Name: {{ $lorry_receipt->customer ? $lorry_receipt->customer->name : 'Not found' }} -->
                        
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $lorry_receipt->customer_id == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        <script>
                            // Store the selected customer data for JavaScript access
                            window.selectedCustomerId = {{ $lorry_receipt->customer_id ?? 'null' }};
                            window.selectedCustomerName = "{{ $lorry_receipt->customer ? addslashes($lorry_receipt->customer->name) : '' }}";
                        </script>
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
                                <option value="{{ $truck->id }}" {{ $truck->id == $lorry_receipt->truck_id ? 'selected' : '' }}>
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
                        <select class="form-select" id="transporter_type" name="transporter_type"  required>
                            <option value="">Select Type</option>
                            <option value="owner" {{ $lorry_receipt->transporter_type == 'owner' ? 'selected' : '' }}>Truck Owner</option>
                            <option value="agency" {{ $lorry_receipt->transporter_type == 'agency' ? 'selected' : '' }}>Agency</option>
                        </select>
                        <div class="invalid-feedback">Please select transporter type.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="transporter_id" id="transporterLabel" class="form-label">Transporter *</label>
                        <select class="form-select" id="transporter_id" name="transporter_id" required>
                            <option value="">Select Transporter</option>
                            @foreach($transporters as $transporter)
                                <option value="{{  $transporter->id }}" {{ $transporter->id == $lorry_receipt->transporter_id ? 'selected' : '' }}>
                                    {{ $transporter->name }}
                                </option>
                            @endforeach
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
                            @foreach($consignors as $consignor)
                                <option value="{{ $consignor->id }}" {{ $consignor->id == $lorry_receipt->consignor_id ? 'selected' : '' }}>
                                    {{ $consignor->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('consignor_id') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a consignor.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                        <input type="text" class="form-control" id="consignor_contact_number" name="consignor_contact_number" value="{{ $lorry_receipt->consignor->contact_number ?? '' }}" readonly>
                    </div>
                  
                </div>
                <div class="row mb-3">
                <div class="col-md-4">
                        <label for="consignee_id" class="form-label">Consignee *</label>
                        <select class="form-select" id="consignee_id" name="consignee_id" required data-placeholder="Search and select consignee...">
                            <option value="">Select Consignee</option>
                            @foreach($consignees as $consignee)
                                <option value="{{ $consignee->id }}" {{ $consignee->id == $lorry_receipt->consignee_id ? 'selected' : '' }}>
                                    {{ $consignee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('consignee_id') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a consignee.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                        <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" value="{{ $lorry_receipt->consignee->contact_number ?? '' }}" readonly>
                    </div>
                </div>
            </div>
            <!-- Route Section -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Route Details</h5></div></div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="source_city" class="form-label">Source City *</label>
                        <input type="text" class="form-control" id="source_city" name="source_city" required value="{{ old('source_city', $lorry_receipt->source_city) }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="source_state" class="form-label">Source State *</label>
                        <input type="text" class="form-control" id="source_state" name="source_state" required value="{{ old('source_state', $lorry_receipt->source_state) }}" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="destination_city" class="form-label">Destination City *</label>
                        <input type="text" class="form-control" id="destination_city" name="destination_city" required value="{{ old('destination_city', $lorry_receipt->destination_city) }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="destination_state" class="form-label">Destination State *</label>
                        <input type="text" class="form-control" id="destination_state" name="destination_state" required value="{{ old('destination_state', $lorry_receipt->destination_state) }}" readonly>
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
                                <option value="{{ $goodsType->name }}" {{ $goodsType->name == $lorry_receipt->good_type ? 'selected' : '' }}>
                                    {{ $goodsType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('good_type') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please select a goods type.</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="no_of_units" class="form-label">No. of Units <small class="text-muted">(Optional)</small></label>
                        <input type="number" class="form-control" id="no_of_units" name="no_of_units" value="{{ old('no_of_units', $lorry_receipt->no_of_units) }}" min="1" max="100000" title="Maximum 100,000 units allowed">
                        @error('no_of_units') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a valid number of units (max 100,000).</div>
                        <small class="text-muted">📦 Max: 100,000 units</small>
                    </div>
                    
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="weight" class="form-label">Weight * <small class="text-muted">(in Tons)</small></label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="{{ old('weight', $lorry_receipt->weight) }}" min="0.1" max="100" title="Weight must be between 0.1 and 100 tons">
                        @error('weight') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a weight between 0.1 and 100 tons.</div>
                        <small class="text-muted">📏 Range: 0.1 - 100 tons</small>
                    </div>
                    <div class="col-md-4">
                        <label for="fr_rate" class="form-label">Freight Rate * <small class="text-muted">(₹ per ton)</small></label>
                        <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="{{ old('fr_rate', $lorry_receipt->fr_rate) }}" min="1" max="20000" title="Freight rate must be between ₹1 and ₹20,000 per ton">
                        @error('fr_rate') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter a freight rate between ₹1 and ₹20,000.</div>
                        <small class="text-muted">💰 Range: ₹1 - ₹20,000 per ton</small>
                    </div>
                    <div class="col-md-2">
                        <label for="shortage_amount" class="form-label">Shortage Amount</label>
                        <input type="number" step="0.01" class="form-control" id="shortage_amount" 
                               value="{{ old('shortage_amount', $lorry_receipt->shortage_amount) }}" disabled>
                        <small class="text-muted">🔒 Auto-filled</small>
                    </div>
                
                    <div class="col-md-2">
                        <label for="damage_amount" class="form-label">Damage Amount</label>
                        <input type="number" step="0.01" class="form-control" id="damage_amount" 
                               value="{{ old('damage_amount', $lorry_receipt->damage_amount) }}" disabled>
                        <small class="text-muted">🔒 Auto-filled</small>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount * <small class="text-muted">(Auto-calculated)</small></label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount', $lorry_receipt->amount) }}" title="Auto-calculated: Weight × Freight Rate (editable)">
                        @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                        <div class="invalid-feedback">Please enter an amount.</div>
                        <small class="text-muted">💡 Calculated as: Weight × Freight Rate -(Shortage Amount + Damage Amount)</small>
                    </div>
                </div>
            </div>

            <!-- Submit Actions -->
            <div class="card mb-4 p-3 bg-light">
                <div class="row"><div class="col"><h5 class="mb-3">Submit Actions</h5></div></div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <button class="btn btn-success w-100" type="submit">💾 Update Lorry Receipt</button>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('lorry_receipts.show', $lorry_receipt->id) }}" class="btn btn-info w-100">👁️ View Details</a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-dark w-100">📄 Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.getElementsByClassName('needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach((form) => {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<!-- Load Select2 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Load SweetAlert2 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Lorry Receipt Edit Form initialized');
    
    // Auto-calculate amount based on weight × freight rate
    const weightInput = document.getElementById('weight');
    const frRateInput = document.getElementById('fr_rate');
    const amountInput = document.getElementById('amount');
    let isManuallyEdited = false;
    
    function calculateAmount() {
        if (isManuallyEdited) return;
        
        const weight = parseFloat(weightInput.value) || 0;
        const frRate = parseFloat(frRateInput.value) || 0;
        
        if (weight > 0 && frRate > 0) {
            const calculatedAmount = weight * frRate;
            amountInput.value = calculatedAmount.toFixed(2);
            
            // Visual feedback for auto-calculation
            amountInput.classList.remove('amount-manually-edited');
            amountInput.classList.add('amount-auto-calculated');
            console.log(`💰 Auto-calculated amount: ₹${calculatedAmount.toFixed(2)} (${weight} tons × ₹${frRate}/ton)`);
        }
    }
    
    // Auto-calculate on weight or freight rate change
    weightInput.addEventListener('input', calculateAmount);
    frRateInput.addEventListener('input', calculateAmount);
    
    // Track manual edits to amount field
    amountInput.addEventListener('input', function() {
        isManuallyEdited = true;
        this.classList.remove('amount-auto-calculated');
        this.classList.add('amount-manually-edited');
        console.log('✏️ Amount manually edited by user');
    });
    
    // Initialize Select2 for better dropdowns
    $('#customer_id, #consignor_id, #consignee_id, #transporter_id, #truck_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select an option...',
        allowClear: true
    });
    
    // Bootstrap form validation
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
});
</script>

<script>
// Handle consignor and consignee contact number and location details
document.addEventListener('DOMContentLoaded', function() {
    const consignorSelect = document.getElementById('consignor_id');
    const consignorContact = document.getElementById('consignor_contact_number');
    const consignorLocation = document.getElementById('consignor_location');
            const consigneeSelect = document.getElementById('consignee_id');
            const consigneeContact = document.getElementById('consignee_contact_number');
            const consigneeLocation = document.getElementById('consignee_location');

            function updateConsignorDetails() {
                const selectedConsignor = consignorSelect.value;
                if (!selectedConsignor) {
                    consignorContact.value = '';
                    consignorLocation.value = '';
                    return;
                }

                fetch(`/api/consignors/${selectedConsignor}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(consignor => {
                    consignorContact.value = consignor.contact_number;
                    consignorLocation.value = `${consignor.city}, ${consignor.state}`;
                })
                .catch(error => console.error('Error:', error));
            }

            function updateConsigneeDetails() {
                const selectedConsignee = consigneeSelect.value;
                if (!selectedConsignee) {
                    consigneeContact.value = '';
                    consigneeLocation.value = '';
                    return;
                }

                fetch(`/api/consignees/${selectedConsignee}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(consignee => {
                    consigneeContact.value = consignee.contact_number;
                    consigneeLocation.value = `${consignee.city}, ${consignee.state}`;
                })
                .catch(error => console.error('Error:', error));
            }

    consignorSelect.addEventListener('change', updateConsignorDetails);
    consigneeSelect.addEventListener('change', updateConsigneeDetails);
});
</script>

<script>
// Handle truck selection and transporter details
document.getElementById('truck_id').addEventListener('change', function () {
    let truckId = this.value;

    if (truckId) {
        fetch(`/get-transporter-name/${truckId}`)
            .then(response => response.json())
            .then(data => {
                if (data.transporter_name) {
                    document.getElementById('transporter_name').value = data.transporter_name;
                    if(data.transporter.is_owner == 1){
                        document.getElementById('ownerTransporterSelect').style.display = 'block';
                    }
                } else {
                    document.getElementById('ownerTransporterSelect').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('ownerTransporterSelect').style.display = 'none';
            });
    } else {
        document.getElementById('ownerTransporterSelect').style.display = 'none';
    }
});
</script>

@endsection

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<!-- Ensure proper script loading -->
<script>
// Wait for DOM and jQuery to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be available
    function waitForJQuery() {
        if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined') {
            console.log('✅ jQuery is available');
            checkSelect2();
        } else {
            console.log('⏳ Waiting for jQuery...');
            setTimeout(waitForJQuery, 100);
        }
    }
    
    function checkSelect2() {
        if (typeof $.fn.select2 !== 'undefined') {
            console.log('✅ Select2 is available');
            initializeLorryReceiptForm();
        } else {
            console.log('Loading Select2...');
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
            script.onload = function() {
                console.log('✅ Select2 loaded successfully');
                initializeLorryReceiptForm();
            };
            document.head.appendChild(script);
        }
    }
    
    waitForJQuery();
});

function initializeLorryReceiptForm() {
    $(document).ready(function() {
        console.log('🚀 Lorry Receipt Edit Form initialized');
        
        // Initialize other dropdowns with Select2
        $('#truck_id, #transporter_id, #consignor_id, #consignee_id, #good_type').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });
        
        // Access the customer data from the window object
        console.log('Selected customer data:', { 
            id: window.selectedCustomerId, 
            name: window.selectedCustomerName 
        });
        
        // Initialize customer dropdown with Select2
        $('#customer_id').select2({
            theme: 'bootstrap-5',
            width: '100%'
        }).on('select2:open', function() {
            // Log when dropdown is opened
            console.log('Customer dropdown opened');
        });
        
        // Create a function to ensure the customer is selected
        function ensureCustomerSelected() {
            if (window.selectedCustomerId) {
                // Make sure the option exists
                const optionExists = $('#customer_id option[value="' + window.selectedCustomerId + '"]').length > 0;
                
                if (!optionExists) {
                    // Create the option if it doesn't exist
                    const newOption = new Option(window.selectedCustomerName, window.selectedCustomerId, true, true);
                    $('#customer_id').append(newOption);
                    console.log('Created missing option for customer:', window.selectedCustomerName);
                }
                
                // Set the value and trigger change
                $('#customer_id').val(window.selectedCustomerId).trigger('change');
                
                // Verify the selection
                const currentVal = $('#customer_id').val();
                console.log('Customer dropdown value after setting:', currentVal);
                
                if (currentVal == window.selectedCustomerId) {
                    console.log('✅ Customer dropdown set successfully');
                } else {
                    console.log('⚠️ Failed to set customer dropdown value');
                }
            }
        }
        
        // Call immediately
        ensureCustomerSelected();
        
        // And also after a short delay to ensure Select2 is fully initialized
        setTimeout(ensureCustomerSelected, 300);
        
        console.log('✅ All dropdowns initialized successfully');
        console.log('Truck ID:', $('#truck_id').val());
        console.log('Transporter ID:', $('#transporter_id').val());
        console.log('Transporter Type:', $('#transporter_type').val());
        console.log('Lorry Receipt Data:', {
            customer_id: {{ $lorry_receipt->customer_id ?? 'null' }},
            truck_id: {{ $lorry_receipt->truck_id ?? 'null' }},
            transporter_id: {{ $lorry_receipt->transporter_id ?? 'null' }},
            source: '{{ $lorry_receipt->source ?? '' }}',
            destination: '{{ $lorry_receipt->destination ?? '' }}'
        });
        
        // Ensure customer dropdown is properly initialized with the correct value
        const customerSelect = $('#customer_id');
        if (customerSelect.length) {
            const customerId = {{ $lorry_receipt->customer_id ?? 'null' }};
            if (customerId) {
                customerSelect.val(customerId).trigger('change');
                console.log('✅ Customer dropdown initialized with ID:', customerId);
            }
        }
        console.log('Route field values:', {
            source_city: $('#source_city').val(),
            source_state: $('#source_state').val(),
            destination_city: $('#destination_city').val(),
            destination_state: $('#destination_state').val()
        });

        // Store consignor and consignee data for contact number population
        const consignors = @json($consignors->keyBy('id'));
        const consignees = @json($consignees->keyBy('id'));
        const trucks = @json($trucks->keyBy('id'));
        const transporters = @json($transporters->keyBy('id'));

        // Populate contact numbers and details on page load
        updateConsignorContact();
        updateConsigneeContact();
        updateTruckDetails();
        updateTransporterDetails();
        
        // Set transporter type based on current truck selection
        const dbTransporterType = "{{ $lorry_receipt->transporter_type ?? '' }}";

if (dbTransporterType) {
    $('#transporter_type').val(dbTransporterType);
    console.log('Restored DB transporter_type:', dbTransporterType);
} else{
        const currentTruckId = $('#truck_id').val();
        if (currentTruckId && trucks[currentTruckId]) {
            const transporterType = trucks[currentTruckId].transporter_type;
            $('#transporter_type').val(transporterType);
            console.log('Set transporter type to:', transporterType);
        }
    }
        
        // Trigger Select2 to refresh and show selected values
        setTimeout(function() {
            $('#customer_id, #truck_id, #transporter_id, #consignor_id, #consignee_id, #good_type').trigger('change.select2');
            console.log('🔄 Select2 dropdowns refreshed');
        }, 500);

        // Handle consignor selection change
        $('#consignor_id').on('change', function() {
            updateConsignorContact();
        });

        // Handle consignee selection change
        $('#consignee_id').on('change', function() {
            updateConsigneeContact();
        });

        // Handle truck selection change
        $('#truck_id').on('change', function() {
            updateTruckDetails();
            populateTransporterDropdown();
        });

        // Handle transporter selection change
        $('#transporter_id').on('change', function() {
            updateTransporterDetails();
        });

        function updateConsignorContact() {
            const consignorId = $('#consignor_id').val();
            if (consignorId && consignors[consignorId]) {
                $('#consignor_contact_number').val(consignors[consignorId].contact_number || '');
            } else {
                $('#consignor_contact_number').val('');
            }
        }

        function updateConsigneeContact() {
            const consigneeId = $('#consignee_id').val();
            if (consigneeId && consignees[consigneeId]) {
                $('#consignee_contact_number').val(consignees[consigneeId].contact_number || '');
            } else {
                $('#consignee_contact_number').val('');
            }
        }

        function updateTruckDetails() {
            const dbSourceCity = "{{ $lorry_receipt->source_city ?? '' }}";
    const dbSourceState = "{{ $lorry_receipt->source_state ?? '' }}";
    const dbDestinationCity = "{{ $lorry_receipt->destination_city ?? '' }}";
    const dbDestinationState = "{{ $lorry_receipt->destination_state ?? '' }}";

    // Check if fields are already filled (from DB in edit form)
    if ($('#source_city').val() || $('#destination_city').val()) {
        // Do nothing, keep DB values
        console.log('Keeping DB values for truck details.');
        return;
    }

            const truckId = $('#truck_id').val();
            if (truckId && trucks[truckId]) {
                const truck = trucks[truckId];
                $('#source_city').val(truck.source_city || '');
                $('#source_state').val(truck.source_state || '');
                $('#destination_city').val(truck.destination_city || '');
                $('#destination_state').val(truck.destination_state || '');
            } else {
                $('#source_city, #source_state, #destination_city, #destination_state').val('');
            }
        }

        function updateTransporterDetails() {
            const transporterId = $('#transporter_id').val();
            if (transporterId && transporters[transporterId]) {
                const transporter = transporters[transporterId];
                const tdsPercentage = transporter.tds_percentage || 0;
                
                // Update TDS badge
                if (tdsPercentage > 0) {
                    $('#tds-badge').text(`TDS: ${tdsPercentage}%`).removeClass('d-none');
                } else {
                    $('#tds-badge').addClass('d-none');
                }
            } else {
                $('#tds-badge').addClass('d-none');
            }
        }

        function populateTransporterDropdown() {
            const truckId = $('#truck_id').val();
            const currentTransporterId = $('#transporter_id').val();
            
            if (truckId && trucks[truckId]) {
                const truck = trucks[truckId];
                const transporterType = truck.transporter_type;
                
                // Clear and repopulate transporter dropdown
                $('#transporter_id').empty().append('<option value="">Select Transporter</option>');
                
                Object.values(transporters).forEach(transporter => {
                    if (transporter.transporter_type === transporterType) {
                        const selected = transporter.id == currentTransporterId ? 'selected' : '';
                        $('#transporter_id').append(`<option value="${transporter.id}" ${selected}>${transporter.name}</option>`);
                    }
                });
                
                $('#transporter_id').trigger('change');
            } else {
                $('#transporter_id').empty().append('<option value="">Select Transporter</option>');
            }
        }

        // Auto-calculate amount when weight or freight rate changes
        $('#weight, #fr_rate').on('input', function() {
            calculateAmount();
        });

        function calculateAmount() {
            const weight = parseFloat($('#weight').val()) || 0;
            const frRate = parseFloat($('#fr_rate').val()) || 0;
            const amount = weight * frRate;
            
            if (weight > 0 && frRate > 0) {
                $('#amount').val(amount.toFixed(2));
            }
        }

        // Validate no_of_units field
        $('#no_of_units').on('input blur', function() {
            const value = parseInt($(this).val());
            const isValid = !value || (value >= 1 && value <= 100000);
            
            $(this).toggleClass('is-valid', isValid && value)
                   .toggleClass('is-invalid', !isValid && value);
            
            if (!isValid && value) {
                this.setCustomValidity('Number of units must be between 1 and 100,000');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form validation
        $('form').on('submit', function(e) {
            const noOfUnits = parseInt($('#no_of_units').val());
            if (noOfUnits && (noOfUnits < 1 || noOfUnits > 100000)) {
                e.preventDefault();
                $('#no_of_units').addClass('is-invalid');
                alert('Number of units must be between 1 and 100,000');
                return false;
            }
        });
    });
}
</script>

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
</script>

