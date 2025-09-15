@extends('layouts.app')

@section('content')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <style>
        /* Prevent form expansion on validation errors */
        .form-control,
        .form-select {
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
            background: var(--bs-body-bg);
            border: var(--bs-border-width) solid var(--bs-border-color);
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single:focus-visible {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
            outline: 0;
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 12px;
        }

        .select2-dropdown {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }

        .select2-results__option--highlighted {
            background-color: var(--bs-primary);
            color: white;
        }
    </style>
    <div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
        <!--begin::Header-->
        <div class="card-header">
            <div class="d-flex w-100">
                <div class="card-title h4">Add Linked Lorry Receipts</div>
                <div class="ms-auto">
                    <a href="{{ route('consignees.create') }}" class="btn btn-info">
                        <i class="bi bi-plus"></i> Create Consignee/Consignor/Customer</a>
                </div>
            </div>
        </div>
        <!--end::Header-->
        <!--begin::Form-->
        <form method="POST" action="{{ route('lorry_receipts.store') }}" id="lorryReceiptForm" class="needs-validation"
            novalidate="">
            @csrf
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            <div class="card-body">
                <!-- Shared Details for all LRs -->
                <div class="card mb-4 p-3 bg-light">
                    <div class="row">
                        <div class="col">
                            <h5 class="mb-3">Shared Details</h5>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="gr_date" class="form-label">GR Date *</label>
                            <input type="date" class="form-control" id="gr_date" name="gr_date" required
                                value="{{ old('gr_date') }}">
                            @error('gr_date')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please select a GR date.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="truck_id" class="form-label">Truck *</label>
                            <select class="form-select" id="truck_id" name="truck_id" required>
                                <option value="">Select Trucks</option>
                                @foreach ($trucks as $truck)
                                    <option value="{{ $truck->id }}"
                                        {{ old('truck_id') == $truck->id ? 'selected' : '' }}>
                                        {{ $truck->truck_number }}
                                        @if ($truck->driver)
                                            - Driver: {{ $truck->driver->name }}
                                        @endif
                                        ({{ $truck->transporter ? $truck->transporter->name : 'No Transporter' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('truck_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please select a truck.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="transporter_type" class="form-label" id="transporterTypeLabel">Select Payment Type
                                *</label>
                            <select class="form-select" id="transporter_type" name="transporter_type" required>
                                <option value="">Select Type</option>
                                <option value="owner">Truck Owner</option>
                                <option value="agency">Agency</option>
                            </select>
                            <div class="invalid-feedback">Please select transporter type.</div>
                        </div>
                    </div>
                    <!-- Memo ID field removed from creation form as it cannot be present at creation time -->
                    <div class="row mb-3">

                        <div class="col-md-4">
                            <label for="transporter_id" id="transporterLabel" class="form-label">Transporter *</label>
                            <select class="form-select" id="transporter_id" name="transporter_id" required
                                onchange="showTdsPercentage(this.value)">
                                <option value="">Select Transporter</option>
                                {{-- This will be filled via JavaScript --}}
                            </select>
                            @error('transporter_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please select a transporter.</div>
                            <div id="tdsRateDisplay" class="mt-2" style="display: none;">
                                <span class="badge bg-warning text-dark fs-6">
                                    🎯 TDS Rate: <span id="tdsPercentageValue">--</span>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="reference_by" class="form-label">Reference By</label>
                            <select class="form-select" id="reference_by" name="reference_by">
                                <option value="">Select Reference</option>
                                @foreach ($agencies as $agency)
                                    <option value="{{ $agency->id }}"
                                        {{ old('reference_by') == $agency->id ? 'selected' : '' }}>
                                        {{ $agency->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('reference_by')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please select a reference.</div>
                        </div>
                    </div>
                </div>
                <!-- LR Items Repeater -->
                <div class="card mb-4 p-3 bg-light">
                    <div class="d-flex align-items-center mb-3">
                        <h5 class="mb-0">LR Items</h5>
                    </div>
                    <div id="lr-items-container"></div>
                    <template id="lr-item-template">
                        <div class="lr-item card mb-3 p-3">
                            <div class="d-flex align-items-center mb-2">
                                <h6 class="mb-0">LR <span class="lr-index">#1</span></h6>
                                <button type="button"
                                    class="btn btn-sm btn-outline-danger ms-auto remove-lr-item">Remove</button>
                            </div>
                            <input type="hidden" class="lane-input" value="Road">
                            <div class="card mb-4 p-3 bg-light">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">LR Number *</label>
                                        <input type="text" class="form-control lr-number-input" required
                                            placeholder="Unique LR Number">
                                        <div class="invalid-feedback">Please enter a unique LR number.</div>
                                        <div class="lr-number-feedback mt-1"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Customer *</label>
                                        <select class="form-select select2 customer-select" required>
                                            <option value="">Select Customer</option>
                                            @foreach ($consignees as $consignee)
                                                <option value="{{ $consignee->id }}">{{ $consignee->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a customer.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Consignor *</label>
                                        <select class="form-select select2 consignor-select" required>
                                            <option value="">Select Consignor</option>
                                            @foreach ($consignees as $consignee)
                                                <option value="{{ $consignee->id }}">{{ $consignee->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted d-block consignor-contact-text"></small>
                                        <small class="form-text text-muted d-block consignor-city-text"></small>
                                        <small class="form-text text-muted d-block consignor-state-text"></small>
                                        <div class="invalid-feedback">Please select a consignor.</div>
                                    </div>
                                    <div class="col-md-3" style="display: none;">
                                        <label class="form-label">Consignor Contact</label>
                                        <input type="text" class="form-control consignor-contact" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Consignee *</label>
                                        <select class="form-select select2 consignee-select" required>
                                            <option value="">Select Consignee</option>
                                            @foreach ($consignees as $consignee)
                                                <option value="{{ $consignee->id }}">{{ $consignee->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted d-block consignee-contact-text"></small>
                                        <small class="form-text text-muted d-block consignee-city-text"></small>
                                        <small class="form-text text-muted d-block consignee-state-text"></small>
                                        <div class="invalid-feedback">Please select a consignee.</div>
                                    </div>
                                    <div class="col-md-3" style="display: none;">
                                        <label class="form-label">Consignee Contact</label>
                                        <input type="text" class="form-control consignee-contact" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">

                                    <input type="hidden" class="form-control source-city" readonly>
                                    <input type="hidden" class="form-control source-state" readonly>

                                </div>
                                <div class="row mb-3">
                                    <input type="hidden" class="form-control destination-city" readonly>
                                    <input type="hidden" class="form-control destination-state" readonly>

                                </div>
                            </div>
                            <div class="card mb-4 p-3 bg-light">
                                <div class="row mb-3">
                                    <div class="col-md-2 ">
                                        <label class="form-label">Goods Type *</label>
                                        <div class="input-group flex-nowrap">
                                            <select class="form-select select2 goods-type" required>
                                                <option value="">Select Goods Type</option>
                                                @foreach ($goodsTypes as $goodsType)
                                                    <option value="{{ $goodsType->name }}">{{ $goodsType->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-outline-primary"
                                                onclick="displayGoodAdd(this)" title="Add New Goods Type">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 ms-4">
                                        <label class="form-label">No. of Units</label>
                                        <input type="number" class="form-control units" min="1" max="100000">
                                        <small class="text-muted">📦 Max: 100,000 units</small>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Weight (Tons) *</label>
                                        <input type="number" step="0.01" class="form-control weight" min="0.1"
                                            max="100" required>
                                        <small class="text-muted">📏 Range: 0.1 - 100 tons</small>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Fr Rate (₹/ton) *</label>
                                        <input type="number" step="0.01" class="form-control fr-rate" min="1"
                                            max="20000" required>
                                        <small class="text-muted">💰 Range: ₹1 - ₹20,000 per ton</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Amount *</label>
                                        <input type="number" step="0.01" class="form-control amount" required>
                                        <small class="text-muted">Auto: Weight × FR Rate</small>
                                    </div>
                                </div>

                                <div class="row mb-3">


                                    <input type="hidden" class="form-control lane" value="Road" readonly>
                                </div>
                            </div>
                        </div>
                    </template>
                    <button type="button" class="btn btn-sm btn-primary ms-auto" id="add-lr-item"><i
                            class="bi bi-plus"></i> Add LR</button>
                </div>
                <!-- Submit Actions -->
                <div class="card mb-4 p-3 bg-light">
                    <div class="row">
                        <div class="col">
                            <h5 class="mb-3">Submit Actions</h5>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4 offset-md-4">
                            <button class="btn btn-success w-100" type="submit">💾 Submit Form</button>
                        </div>

                        <div class="col-md-4">
                            <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-dark w-100">📄 Back to
                                List</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <!-- Add Goods Type Modal -->
    <div class="modal fade" id="addGoodsTypeModal" tabindex="-1" aria-labelledby="addGoodsTypeModalLabel"
        aria-hidden="true">
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
                    <input type="hidden" name="good_type_field" id="good_type_field" value="">

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">
                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
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
                console.log('🚀 Linked Lorry Receipts Form initialized');

                // Initialize shared dropdowns with Select2
                $('#truck_id, #transporter_id, #reference_by').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });

                // Add first LR item by default
                addLrItem();
            });
        }
    </script>

    <script>
        console.log('🚀 Linked  Receipts Form initialized');
        // For consignor

        $(document).on('change', '.consignor-select', function() {
            console.log('🔥 Change event fired', $(this).val());
        });
        $(document).on('select2:select', '.consignor-select', function(e) {
            const scope = this.closest('.lr-item');
            handleConsignorSelection(e.params.data.id, scope);
        });

        // For consignee
        $(document).on('select2:select', '.consignee-select', function(e) {
            const scope = this.closest('.lr-item');
            handleConsigneeSelection(e.params.data.id, scope);
        });
        document.addEventListener('DOMContentLoaded', function() {

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
            $('#truck_id').on('select2:select', function(e) {
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
                        console.log('ℹ️ Transporter type is not "owner", current value:',
                            transporterTypeSelect.value);
                    }
                } else {
                    console.warn('⚠️ Could not extract transporter name from truck text:', truckText);
                }
            });

            // Also handle regular change event as fallback
            $('#truck_id').on('change', function() {
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
            transporterTypeSelect.addEventListener('change', function() {
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
                            if (owner.name.toLowerCase().trim() === transporterName.toLowerCase()
                                .trim()) {
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

                            console.log(
                                `✅ Auto-selected truck owner: ${matchedOwner.name} (ID: ${matchedOwner.id})`
                            );

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

            // Handle transporter selection to show TDS percentage
            transporterSelect.addEventListener('change', function() {
                const transporterId = this.value;
                showTdsPercentage(transporterId);
            });

            // Dynamic LR items handling
            const lrItemsContainer = document.getElementById('lr-items-container');
            const addBtn = document.getElementById('add-lr-item');

            function addLrItem(prefill = {}) {
                const tpl = document.getElementById('lr-item-template');
                const node = tpl.content.cloneNode(true);
                lrItemsContainer.appendChild(node);
                refreshIndices();
                initSelect2In(lrItemsContainer.lastElementChild);
            }

            function refreshIndices() {
                const items = lrItemsContainer.querySelectorAll('.lr-item');
                items.forEach((item, idx) => {
                    item.querySelector('.lr-index').textContent = `#${idx + 1}`;
                });
            }

            function initSelect2In(scope) {
                $(scope).find('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }

            addBtn.addEventListener('click', () => addLrItem());

            // Remove LR item
            lrItemsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-lr-item')) {
                    const card = e.target.closest('.lr-item');
                    card.remove();
                    refreshIndices();
                }
            });

            // Fetch consignor details
            function handleConsignorSelection(consignorId, scope) {
                console.log('👤 Consignor selected:', consignorId);

                const consigneeId = scope.querySelector('.consignee-select')?.value;
                if (consignorId && consigneeId && String(consignorId) === String(consigneeId)) {
                    const msg = 'Consignor and Consignee cannot be the same.';
                    Swal ? Swal.fire('Invalid Selection', msg, 'warning') : alert(msg);

                    $(scope).find('.consignor-select').val(null).trigger('change.select2');
                    scope.querySelector('.consignor-contact').value = '';
                    scope.querySelector('.source-city').value = '';
                    scope.querySelector('.source-state').value = '';
                    scope.querySelector('.consignor-contact-text').textContent = '';
                    scope.querySelector('.consignor-city-text').textContent = '';
                    scope.querySelector('.consignor-state-text').textContent = '';
                    return;
                }

                if (consignorId) {
                    fetch(`/api/consignees/${consignorId}`)
                        .then(res => {
                            if (!res.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return res.json();
                        })
                        .then(data => {
                            console.log('📊 Consignor data received:', data);

                            // Update contact number
                            scope.querySelector('.consignor-contact').value = data.contact_number || '';
                            scope.querySelector('.consignor-contact-text').textContent = "Contact Number: " +
                                data.contact_number || '';

                            // Handle both nested and flat city/state structures
                            const cityValue = data.city?.name || data.city || '';
                            const stateValue = data.state?.name || data.state || '';
                            scope.querySelector('.consignor-city-text').textContent = "Source City: " +
                                cityValue || '';
                            scope.querySelector('.consignor-state-text').textContent = "Source State: " +
                                stateValue || '';

                            scope.querySelector('.source-city').value = cityValue;
                            scope.querySelector('.source-state').value = stateValue;

                            console.log('Updated consignor fields:', {
                                contact: data.contact_number,
                                city: cityValue,
                                state: stateValue
                            });
                        })
                        .catch(err => {
                            console.error('❌ Consignor fetch error:', err);
                            // Clear fields on error
                            scope.querySelector('.consignor-contact').value = '';
                            scope.querySelector('.source-city').value = '';
                            scope.querySelector('.source-state').value = '';
                            scope.querySelector('.consignor-contact-text').textContent = '';
                            scope.querySelector('.consignor-city-text').textContent = '';
                            scope.querySelector('.consignor-state-text').textContent = '';
                        });
                } else {
                    // Clear fields if no consignor selected
                    scope.querySelector('.consignor-contact').value = '';
                    scope.querySelector('.source-city').value = '';
                    scope.querySelector('.source-state').value = '';
                    scope.querySelector('.consignor-contact-text').textContent = '';
                    scope.querySelector('.consignor-city-text').textContent = '';
                    scope.querySelector('.consignor-state-text').textContent = '';
                }
            }

            // Fetch consignee details
            function handleConsigneeSelection(consigneeId, scope) {
                console.log('🏢 Consignee selected:', consigneeId);

                const consignorId = scope.querySelector('.consignor-select')?.value;
                if (consigneeId && consignorId && String(consigneeId) === String(consignorId)) {
                    const msg = 'Consignee and Consignor cannot be the same.';
                    Swal ? Swal.fire('Invalid Selection', msg, 'warning') : alert(msg);

                    $(scope).find('.consignee-select').val(null).trigger('change.select2');
                    scope.querySelector('.consignee-contact').value = '';
                    scope.querySelector('.destination-city').value = '';
                    scope.querySelector('.destination-state').value = '';
                    scope.querySelector('.consignee-contact-text').textContent = '';
                    scope.querySelector('.consignee-city-text').textContent = '';
                    scope.querySelector('.consignee-state-text').textContent = '';
                    return;
                }

                if (consigneeId) {
                    fetch(`/api/consignees/${consigneeId}`)
                        .then(res => {
                            if (!res.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return res.json();
                        })
                        .then(data => {
                            console.log('📊 Consignee data received:', data);

                            // Update contact number
                            scope.querySelector('.consignee-contact').value = data.contact_number || '';
                            scope.querySelector('.consignee-contact-text').textContent = "Contact Number: " +
                                data.contact_number || '';

                            // Handle both nested and flat city/state structures
                            const cityValue = data.city?.name || data.city || '';
                            const stateValue = data.state?.name || data.state || '';
                            scope.querySelector('.consignee-city-text').textContent = "Destination City: " +
                                cityValue || '';
                            scope.querySelector('.consignee-state-text').textContent = "Destination State: " +
                                stateValue || '';

                            scope.querySelector('.destination-city').value = cityValue;
                            scope.querySelector('.destination-state').value = stateValue;

                            console.log('Updated consignee fields:', {
                                contact: data.contact_number,
                                city: cityValue,
                                state: stateValue
                            });
                        })
                        .catch(err => {
                            console.error('❌ Consignee fetch error:', err);
                            // Clear fields on error
                            scope.querySelector('.consignee-contact').value = '';
                            scope.querySelector('.destination-city').value = '';
                            scope.querySelector('.destination-state').value = '';
                            scope.querySelector('.consignee-contact-text').textContent = '';
                            scope.querySelector('.consignee-city-text').textContent = '';
                            scope.querySelector('.consignee-state-text').textContent = '';
                        });
                } else {
                    // Clear fields if no consignee selected
                    scope.querySelector('.consignee-contact').value = '';
                    scope.querySelector('.destination-city').value = '';
                    scope.querySelector('.destination-state').value = '';
                    scope.querySelector('.consignee-contact-text').textContent = '';
                    scope.querySelector('.consignee-city-text').textContent = '';
                    scope.querySelector('.consignee-state-text').textContent = '';
                }
            }

            // Update event listeners to handle both change and select2:select events
            $(document).on('change', '.consignor-select', function() {
                const scope = this.closest('.lr-item');
                handleConsignorSelection(this.value, scope);
            });

            $(document).on('select2:select', '.consignor-select', function(e) {
                const scope = this.closest('.lr-item');
                handleConsignorSelection(e.params.data.id, scope);
            });

            $(document).on('change', '.consignee-select', function() {
                const scope = this.closest('.lr-item');
                handleConsigneeSelection(this.value, scope);
            });

            $(document).on('select2:select', '.consignee-select', function(e) {
                const scope = this.closest('.lr-item');
                handleConsigneeSelection(e.params.data.id, scope);
            });

            // Delegate change/input/blur events inside LR items for selects and calculations
            lrItemsContainer.addEventListener('change', function(e) {
                const scope = e.target.closest('.lr-item');
                if (!scope) return;
                if (e.target.classList.contains('consignor-select')) {
                    handleConsignorSelection(e.target.value, scope);
                } else if (e.target.classList.contains('consignee-select')) {
                    handleConsigneeSelection(e.target.value, scope);
                }
            });
            lrItemsContainer.addEventListener('input', function(e) {
                const scope = e.target.closest('.lr-item');
                if (!scope) return;
                if (e.target.classList.contains('weight') || e.target.classList.contains(
                        'fr-rate')) {
                    calculateAmount(scope);
                }
            });

            // Per-item amount calc
            function calculateAmount(scope) {
                const weightInput = scope.querySelector('.weight');
                const frRateInput = scope.querySelector('.fr-rate');
                const amountInput = scope.querySelector('.amount');
                const weight = parseFloat(weightInput.value) || 0;
                const frRate = parseFloat(frRateInput.value) || 0;
                if (weight > 0 && frRate > 0) {
                    const calculatedAmount = weight * frRate;
                    amountInput.value = calculatedAmount.toFixed(2);
                    amountInput.style.backgroundColor = '#e8f5e8';
                    amountInput.title =
                        `Auto-calculated: ${weight} × ${frRate} = ${calculatedAmount.toFixed(2)}`;
                }
            }

            // Remove auto-calc styling when manually editing amount
            lrItemsContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('amount')) {
                    e.target.style.backgroundColor = '';
                    e.target.title = 'Manually edited amount';
                }
            });

            // Weight validation on blur
            lrItemsContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('weight')) {
                    const weightInput = e.target;
                    const weight = parseFloat(weightInput.value);
                    if (isNaN(weight)) return;
                    if (weight < 0.1) {
                        weightInput.setCustomValidity('Weight must be at least 0.1 tons');
                        weightInput.classList.add('is-invalid');
                    } else if (weight > 100) {
                        weightInput.setCustomValidity('Weight cannot exceed 100 tons');
                        weightInput.classList.add('is-invalid');
                    } else {
                        weightInput.setCustomValidity('');
                        weightInput.classList.remove('is-invalid');
                    }
                }
            }, true);

            // Units and FR rate validation
            lrItemsContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('units')) {
                    const el = e.target;
                    const value = parseFloat(el.value);
                    if (el.value && (isNaN(value) || value < 1 || value > 100000)) {
                        el.setCustomValidity(
                            'Please enter a valid number of units between 1 and 100,000.');
                        el.classList.add('is-invalid');
                        el.classList.remove('is-valid');
                    } else {
                        el.setCustomValidity('');
                        el.classList.remove('is-invalid');
                        if (el.value) {
                            el.classList.add('is-valid');
                        } else {
                            el.classList.remove('is-valid');
                        }
                    }
                } else if (e.target.classList.contains('fr-rate')) {
                    const fr = e.target;
                    const value = parseFloat(fr.value);
                    if (fr.value && (isNaN(value) || value < 1 || value > 20000)) {
                        fr.setCustomValidity(
                            'Please enter a valid freight rate between ₹1 and ₹20,000.');
                        fr.classList.add('is-invalid');
                        fr.classList.remove('is-valid');
                    } else if (fr.value) {
                        fr.setCustomValidity('');
                        fr.classList.remove('is-invalid');
                        fr.classList.add('is-valid');
                    } else {
                        fr.setCustomValidity('');
                        fr.classList.remove('is-invalid', 'is-valid');
                    }
                }
            });

            // LR number uniqueness per row
            let lrNumberTimeout;

            function checkLrUniq(lrNumber, feedbackEl, inputEl) {
                if (!lrNumber || lrNumber.length < 2) {
                    feedbackEl.innerHTML = '';
                    inputEl.classList.remove('is-valid', 'is-invalid');
                    return;
                }
                let allLrNumbers = Array.from(document.querySelectorAll(
                    '.lr-number-input')).map(el => el.value.trim().toLowerCase());
                let duplicateCount = allLrNumbers.filter(num => num === lrNumber.toLowerCase())
                    .length;
                if (duplicateCount > 1) {
                    feedbackEl.innerHTML =
                        '<small class="text-danger">❌ This LR number is duplicated in the form.</small>';
                    inputEl.classList.remove('is-valid');
                    inputEl.classList.add('is-invalid');
                    inputEl.setCustomValidity('This LR number is duplicated in the form.');
                    return;
                }
                feedbackEl.innerHTML = '<small class="text-info">🔍 Checking availability...</small>';
                inputEl.classList.remove('is-valid', 'is-invalid');
                fetch('/api/check-lr-number-uniqueness', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            lr_number: lrNumber
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.valid) {
                            feedbackEl.innerHTML = '<small class="text-success">✅ ' + data.message +
                                '</small>';
                            inputEl.classList.remove('is-invalid');
                            inputEl.classList.add('is-valid');
                            inputEl.setCustomValidity('');
                        } else {
                            feedbackEl.innerHTML = '<small class="text-danger">❌ ' + data.message +
                                '</small>';
                            inputEl.classList.remove('is-valid');
                            inputEl.classList.add('is-invalid');
                            inputEl.setCustomValidity(data.message);
                        }
                    })
                    .catch(err => {
                        console.error('LR uniq check error', err);
                        feedbackEl.innerHTML =
                            '<small class="text-warning">⚠️ Unable to check availability</small>';
                        inputEl.classList.remove('is-valid', 'is-invalid');
                    });
            }

            lrItemsContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('lr-number-input')) {
                    const inputEl = e.target;
                    const feedbackEl = e.target.closest('.col-md-3').querySelector(
                        '.lr-number-feedback');
                    const lrNumber = inputEl.value.trim();
                    clearTimeout(lrNumberTimeout);
                    lrNumberTimeout = setTimeout(() => checkLrUniq(lrNumber, feedbackEl, inputEl), 500);
                }
            });

            lrItemsContainer.addEventListener('blur', function(e) {
                if (e.target.classList.contains('lr-number-input')) {
                    const inputEl = e.target;
                    const feedbackEl = e.target.closest('.col-md-3').querySelector(
                        '.lr-number-feedback');
                    const lrNumber = inputEl.value.trim();
                    if (lrNumber) {
                        clearTimeout(lrNumberTimeout);
                        checkLrUniq(lrNumber, feedbackEl, inputEl);
                    }
                }
            }, true);

            // Bootstrap validation and SweetAlert confirmation similar to single create
            (function() {
                const form = document.getElementById('lorryReceiptForm');
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (document.querySelectorAll('#lr-items-container .lr-item').length ===
                        0) {
                        Swal.fire('No LR Items', 'Please add at least one LR item.', 'warning');
                        return;
                    }

                    if (!form.checkValidity()) {
                        form.classList.add('was-validated');
                        return;
                    }

                    // Build summary
                    const items = Array.from(document.querySelectorAll(
                        '#lr-items-container .lr-item')).map((item, i) => ({
                        lr_number: item.querySelector('.lr-number-input').value,
                        customer: $(item).find('.customer-select option:selected')
                            .text(),
                        consignor: $(item).find('.consignor-select option:selected')
                            .text(),
                        consignee: $(item).find('.consignee-select option:selected')
                            .text(),
                        source: `${item.querySelector('.source-city').value}, ${item.querySelector('.source-state').value}`,
                        destination: `${item.querySelector('.destination-city').value}, ${item.querySelector('.destination-state').value}`,
                        good_type: $(item).find('.goods-type option:selected')
                            .text(),
                        no_of_units: item.querySelector('.units').value,
                        weight: item.querySelector('.weight').value,
                        fr_rate: item.querySelector('.fr-rate').value,
                        amount: item.querySelector('.amount').value,

                    }));

                    const grDate = document.getElementById('gr_date').value;
                    const truckNumber = document.querySelector('#truck_id option:checked')
                        ?.textContent || 'Not selected';
                    const transporterName = document.querySelector(
                        '#transporter_id option:checked')?.textContent || 'Not selected';
                    const referenceBy = document.querySelector('#reference_by option:checked')
                        ?.textContent || 'Not selected';

                    const confirmationHTML = `
                                  <div class="confirmation-details">
                                      <h5 class="text-primary mb-3">📋 Verify details</h5>
                                      <div class="row">
                                        <div class="col-md-3"></div>
                                          <div class="col-md-6">
                                            <div class="card mb-3"><div class="card-header bg-light"><strong>Shared</strong></div>
                                            <div class="card-body">
                                              <p><strong>GR Date:</strong> ${formatDate(grDate)}</p>
                                              <p><strong>Truck:</strong> ${truckNumber}</p>
                                              <p><strong>Transporter:</strong> ${transporterName}</p>
                                              <p><strong>Reference By:</strong> ${referenceBy}</p>
                                            </div></div>
                                          </div>
                                          <div class="col-md-3"></div>

                                              ${items.map((it, i) => `
                                                    <div class="col-md-6">
                                                <div class="card mb-3"><div class="card-header bg-light"><strong> ${i+1}. LR:</strong> ${it.lr_number}</strong></div>
                                                <div class="card-body">
                                                                                    <div class='mb-2'>
                                                                                      <strong>Customer:</strong> ${it.customer}<br/>
                                                                                      <strong>Consignor:</strong> ${it.consignor} | <strong>Consignee:</strong> ${it.consignee}<br/>
                                                                                      <strong>Route:</strong> ${it.source} → ${it.destination}<br/>
                                                                                      <strong>Goods:</strong> ${it.good_type} | <strong>No. of Units:</strong> ${it.no_of_units} <br/>
                                                                                      <strong>Weight:</strong> ${it.weight} | <strong>FR:</strong> ₹${it.fr_rate} | <strong>Amount:</strong> ₹${it.amount}
                                                                                    </div>
                                                                                     </div></div>
                                                                                     </div>
                                                                                  `).join('')}


                                      </div>
                                  </div>`;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Confirm Linked LRs',
                            html: confirmationHTML,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#28a745',
                            confirmButtonText: '✅ Submit',
                            cancelButtonText: '❌ Cancel',
                            width: '90%'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                compileAndSubmit(form);
                            }
                        });
                    } else {
                        compileAndSubmit(form);
                    }
                });
            })();

            // Helper: format DD-MM-YYYY like in single create
            function formatDate(dateString) {
                if (!dateString) return 'Not selected';
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return dateString;
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}-${month}-${year}`;
            }

            // Compile payload into lrs[...] hidden inputs and submit
            function compileAndSubmit(form) {
                // Remove any previous hidden inputs for lrs
                form.querySelectorAll('input[name^="lrs["]').forEach(el => el.remove());

                const items = document.querySelectorAll('#lr-items-container .lr-item');
                items.forEach((item, idx) => {
                    const pairs = {
                        lr_number: item.querySelector('.lr-number-input').value,
                        customer_id: item.querySelector('.customer-select').value,
                        consignor_id: item.querySelector('.consignor-select').value,
                        consignor_contact_number: item.querySelector('.consignor-contact')
                            .value,
                        consignee_id: item.querySelector('.consignee-select').value,
                        consignee_contact_number: item.querySelector('.consignee-contact')
                            .value,
                        source_city: item.querySelector('.source-city').value,
                        source_state: item.querySelector('.source-state').value,
                        destination_city: item.querySelector('.destination-city').value,
                        destination_state: item.querySelector('.destination-state').value,
                        lane: item.querySelector('.lane').value || 'Road',
                        good_type: item.querySelector('.goods-type').value,
                        no_of_units: item.querySelector('.units').value,
                        weight: item.querySelector('.weight').value,
                        fr_rate: item.querySelector('.fr-rate').value,
                        amount: item.querySelector('.amount').value,
                    };
                    Object.entries(pairs).forEach(([key, val]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `lrs[${idx}][${key}]`;
                        input.value = val ?? '';
                        form.appendChild(input);
                    });
                });

                form.submit();
            }

            function saveAndRedirect() {
                // Get form
                let form = $('#lorryReceiptForm');

                // Append hidden input to tell backend to redirect to FR
                if ($('#redirect_to_fr').length === 0) {
                    form.append(
                        '<input type="hidden" name="redirect_to_fr" id="redirect_to_fr" value="1">');

                }

                form.submit();
            }
        });

        function displayGoodAdd(btn) {
            // Using Bootstrap's Modal API to show the modal
            const modalElement = document.getElementById('addGoodsTypeModal');
            const goodTypeField = document.getElementById('good_type_field');
            goodTypeField.value = btn.closest('.input-group').querySelector('.goods-type').dataset.select2Id;
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }

        // Handle Add Goods Type Form Submission
        document.getElementById('addGoodsTypeForm').addEventListener('submit', function(e) {
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
                        console.log(data);
                        // Add new option to Goods Type dropdown
                        let selects = document.querySelectorAll('.goods-type');
                        let newOption = new Option(data.goodsType.name, data.goodsType.name);

                        selects.forEach(select => {
                            select.add(new Option(data.goodsType.name, data.goodsType.name));
                        });

                        // Add in template as well for future items
                        const templateSelect = document.getElementById('lr-item-template').content
                            .querySelector('.goods-type');
                        if (templateSelect) {
                            templateSelect.add(new Option(data.goodsType.name, data.goodsType.name));
                        }

                        // Select the newly added option in the relevant dropdown
                        const goodTypeField = document.getElementById('good_type_field');
                        if (goodTypeField && goodTypeField.value) {
                            document.querySelector(`[data-select2-id="${goodTypeField.value}"]`).value = data
                                .goodsType.name;
                        }

                        // Close modal properly using Bootstrap's API
                        const modalElement = document.getElementById('addGoodsTypeModal');
                        const modal = bootstrap.Modal.getInstance(modalElement);

                        if (modal) {
                            modal.hide();

                            // Properly clean up modal after it's hidden
                            modalElement.addEventListener('hidden.bs.modal', function(event) {
                                // Remove all modal backdrops
                                document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                                    backdrop.remove();
                                });

                                // Fix body classes and styles
                                document.body.classList.remove('modal-open');
                                document.body.style.overflow = '';
                                document.body.style.paddingRight = '';
                            }, {
                                once: true
                            }); // Only listen once
                        } else {
                            // Fallback for older Bootstrap versions
                            $('#addGoodsTypeModal').modal('hide');

                            setTimeout(() => {
                                // Clean up modal backdrop and body classes
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open').css({
                                    'overflow': '',
                                    'padding-right': ''
                                });
                            }, 500);
                        }

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

    <script>
        // Fetch consignor details
        function handleConsignorSelection(consignorId, scope) {
            console.log('👤 Consignor selected:', consignorId);

            const consigneeId = scope.querySelector('.consignee-select')?.value;
            if (consignorId && consigneeId && String(consignorId) === String(consigneeId)) {
                const msg = 'Consignor and Consignee cannot be the same.';
                Swal ? Swal.fire('Invalid Selection', msg, 'warning') : alert(msg);

                $(scope).find('.consignor-select').val(null).trigger('change.select2');
                scope.querySelector('.consignor-contact').value = '';
                scope.querySelector('.source-city').value = '';
                scope.querySelector('.source-state').value = '';
                return;
            }

            if (consignorId) {
                fetch(`/api/consignees/${consignorId}`)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('📊 Consignor data received:', data);

                        // Update contact number
                        scope.querySelector('.consignor-contact').value = data.contact_number || '';

                        // Handle both nested and flat city/state structures
                        const cityValue = data.city?.name || data.city || '';
                        const stateValue = data.state?.name || data.state || '';

                        scope.querySelector('.source-city').value = cityValue;
                        scope.querySelector('.source-state').value = stateValue;

                        console.log('Updated consignor fields:', {
                            contact: data.contact_number,
                            city: cityValue,
                            state: stateValue
                        });
                    })
                    .catch(err => {
                        console.error('❌ Consignor fetch error:', err);
                        // Clear fields on error
                        scope.querySelector('.consignor-contact').value = '';
                        scope.querySelector('.source-city').value = '';
                        scope.querySelector('.source-state').value = '';
                    });
            } else {
                // Clear fields if no consignor selected
                scope.querySelector('.consignor-contact').value = '';
                scope.querySelector('.source-city').value = '';
                scope.querySelector('.source-state').value = '';
            }
        }

        // Fetch consignee details
        function handleConsigneeSelection(consigneeId, scope) {
            console.log('🏢 Consignee selected:', consigneeId);

            const consignorId = scope.querySelector('.consignor-select')?.value;
            if (consigneeId && consignorId && String(consigneeId) === String(consignorId)) {
                const msg = 'Consignee and Consignor cannot be the same.';
                Swal ? Swal.fire('Invalid Selection', msg, 'warning') : alert(msg);

                $(scope).find('.consignee-select').val(null).trigger('change.select2');
                scope.querySelector('.consignee-contact').value = '';
                scope.querySelector('.destination-city').value = '';
                scope.querySelector('.destination-state').value = '';
                return;
            }

            if (consigneeId) {
                fetch(`/api/consignees/${consigneeId}`)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('📊 Consignee data received:', data);

                        // Update contact number
                        scope.querySelector('.consignee-contact').value = data.contact_number || '';

                        // Handle both nested and flat city/state structures
                        const cityValue = data.city?.name || data.city || '';
                        const stateValue = data.state?.name || data.state || '';

                        scope.querySelector('.destination-city').value = cityValue;
                        scope.querySelector('.destination-state').value = stateValue;

                        console.log('Updated consignee fields:', {
                            contact: data.contact_number,
                            city: cityValue,
                            state: stateValue
                        });
                    })
                    .catch(err => {
                        console.error('❌ Consignee fetch error:', err);
                        // Clear fields on error
                        scope.querySelector('.consignee-contact').value = '';
                        scope.querySelector('.destination-city').value = '';
                        scope.querySelector('.destination-state').value = '';
                    });
            } else {
                // Clear fields if no consignee selected
                scope.querySelector('.consignee-contact').value = '';
                scope.querySelector('.destination-city').value = '';
                scope.querySelector('.destination-state').value = '';
            }
        }

        // Update event listeners to handle both change and select2:select events
        $(document).on('change', '.consignor-select', function() {
            const scope = this.closest('.lr-item');
            handleConsignorSelection(this.value, scope);
        });

        $(document).on('select2:select', '.consignor-select', function(e) {
            const scope = this.closest('.lr-item');
            handleConsignorSelection(e.params.data.id, scope);
        });

        $(document).on('change', '.consignee-select', function() {
            const scope = this.closest('.lr-item');
            handleConsigneeSelection(this.value, scope);
        });

        $(document).on('select2:select', '.consignee-select', function(e) {
            const scope = this.closest('.lr-item');
            handleConsigneeSelection(e.params.data.id, scope);
        });

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
    </script>
@endsection
