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
                        <select class="form-select" id="transporter_type" required>
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
                                <option value="{{ $goodsType->name }}" {{ $goodsType->name == $lorry_receipt->goods ? 'selected' : '' }}>
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
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount * <small class="text-muted">(Auto-calculated)</small></label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="{{ old('amount', $lorry_receipt->amount) }}" title="Auto-calculated: Weight × Freight Rate (editable)">
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
        const currentTruckId = $('#truck_id').val();
        if (currentTruckId && trucks[currentTruckId]) {
            const transporterType = trucks[currentTruckId].transporter_type;
            $('#transporter_type').val(transporterType);
            console.log('Set transporter type to:', transporterType);
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

