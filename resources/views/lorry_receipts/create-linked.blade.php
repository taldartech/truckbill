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

        /* Highlight transporter input when declaration not given */
        #transporter_id.declaration-warning {
            border-color: #ffc107 !important;
            border-width: 2px !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
        }

        /* Highlight Select2 container when declaration not given */
        .select2-container.declaration-warning {
            border-color: #ffc107 !important;
            border-width: 2px !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
        }

        .select2-container.declaration-warning .select2-selection__rendered {
            background-color: #fff3cd !important;
        }
    </style>
    <div class="card card-info card-outline mb-4" style="max-width: 1100px;margin: 0 auto;">
        <!--begin::Header-->
        <div class="card-header">
            <div class="d-flex w-100">
                <div class="card-title h4">Add Linked Good Receipts (Bilty)</div>
                <div class="ms-auto">
                    <a href="{{ route('consignees.create') }}" class="btn btn-brand-primary">
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
                <!-- Declaration Warning (shown at top when truck owner declaration not submitted) -->
                <div id="declarationWarning" class="alert alert-warning mb-3" style="display: none;">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Warning:</strong> Declaration is not submitted for the selected truck owner.
                    <a href="#" id="editTruckOwnerLink" target="_blank" class="alert-link">
                        <i class="bi bi-pencil-square"></i> Edit Truck Owner
                    </a>
                </div>
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
                                @php($selectedTruck = $trucks->firstWhere('id', old('truck_id')))
                                @if($selectedTruck)
                                    <option value="{{ $selectedTruck->id }}"
                                        data-transporter-id="{{ $selectedTruck->transporter_id }}"
                                        data-transporter-name="{{ $selectedTruck->transporter ? $selectedTruck->transporter->name : '' }}"
                                        selected>
                                        {{ $selectedTruck->truck_number }}
                                        @if ($selectedTruck->driver)
                                            - Driver: {{ $selectedTruck->driver->name }}
                                        @endif
                                        ({{ $selectedTruck->transporter ? $selectedTruck->transporter->name : 'No Transporter' }})
                                    </option>
                                @endif
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
                                onchange="window.LREntity.showTdsPercentage(this.value)">
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
                                @php($selectedAgency = $agencies->firstWhere('id', old('reference_by')))
                                @if($selectedAgency)
                                    <option value="{{ $selectedAgency->id }}" selected>
                                        {{ $selectedAgency->name }}
                                    </option>
                                @endif
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
                                    class="btn btn-sm btn-danger ms-auto remove-lr-item">Remove</button>
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
                                        <label class="form-label">Invoice Number</label>
                                        <input type="text" class="form-control invoice-number-input"
                                            placeholder="Unique Invoice Number">
                                        <div class="invalid-feedback">Please enter a unique Invoice number.</div>
                                        <div class="invoice-number-feedback mt-1"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Customer *</label>
                                        <select class="form-select select2 customer-select" required>
                                            <option value="">Select Customer</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a customer.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="gr_number" class="form-label">GR Number <small class="text-muted">(Optional)</small></label>
                                        <input type="text" class="form-control gr-number-input" id="gr_number" name="gr_number"
                                               value="{{ old('gr_number') }}" placeholder="Enter GR Number">
                                        <div class="gr-number-feedback mt-1"></div>
                                        @error('gr_number')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>
                                <div class="row">

                                    <div class="col-md-4">
                                        <label class="form-label">Consignor *</label>
                                        <select class="form-select select2 consignor-select" required>
                                            <option value="">Select Consignor</option>
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
                                    <div class="col-md-4">
                                        <label class="form-label">Consignee *</label>
                                        <select class="form-select select2 consignee-select" required>
                                            <option value="">Select Consignee</option>
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
                                    <div class="col-md-4">
                                        <label for="unloading_station" class="form-label">Unloading Station </label>
                                        <input type="text" class="form-control" id="unloading_station" name="unloading_station"
                                            value="{{ old('unloading_station') }}">
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
                                            <button type="button" class="btn btn-primary"
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
                                        <label class="form-label">Goods Value</label>
                                        <input type="number" step="0.01" class="form-control goods-value" min="0" placeholder="₹">
                                        <small class="text-muted">₹</small>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Weight (Tons) *</label>
                                        <input type="number" step="0.001" class="form-control weight" min="0.1"
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
                                        <label class="form-label d-flex justify-content-between align-items-center">
                                            <span>Amount *</span>
                                            <span class="form-check form-check-inline mb-0">
                                                <input class="form-check-input is-fixed-amount" type="checkbox"
                                                    value="1">
                                                <label class="form-check-label">Use fixed amount</label>
                                            </span>
                                        </label>
                                        <input type="number" step="0.01" class="form-control amount" required>
                                        <small class="text-muted amount-help-text">Auto: Weight × FR Rate (uncheck to auto-calculate)</small>
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
                            <button class="btn btn-brand-primary w-100" type="submit">💾 Create Linked Good Receipt (Bilty)</button>
                        </div>

                        <div class="col-md-6">
                            <a href="{{ route('lorry_receipts.index') }}" class="btn btn-back w-100">📄 Back to
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
                        <button type="submit" class="btn btn-brand-primary">
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

    <!-- Common LR entity confirmation helpers -->
    <script src="/js/lr-entity.js"></script>

    <script>
        // Wait for DOM and jQuery to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for jQuery to be available
            function waitForJQuery() {
                if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined') {
                    checkSelect2();
                } else {
                    setTimeout(waitForJQuery, 100);
                }
            }

            function checkSelect2() {
                if (typeof $.fn.select2 !== 'undefined') {
                    initializeLorryReceiptForm();
                } else {
                    var script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
                    script.onload = function() {
                        initializeLorryReceiptForm();
                    };
                    document.head.appendChild(script);
                }
            }

            waitForJQuery();
        });

        // Global function to add LR item (defined early for accessibility)
        function addLrItem(prefill = {}) {
            const lrItemsContainer = document.getElementById('lr-items-container');
            if (!lrItemsContainer) {
                console.warn('LR items container not found');
                return;
            }
            const tpl = document.getElementById('lr-item-template');
            if (!tpl) {
                console.warn('LR item template not found');
                return;
            }
            const node = tpl.content.cloneNode(true);
            lrItemsContainer.appendChild(node);
            refreshIndices();
            initSelect2In(lrItemsContainer.lastElementChild);
        }

        function refreshIndices() {
            const lrItemsContainer = document.getElementById('lr-items-container');
            if (!lrItemsContainer) return;
            const items = lrItemsContainer.querySelectorAll('.lr-item');
            items.forEach((item, idx) => {
                const indexEl = item.querySelector('.lr-index');
                if (indexEl) {
                    indexEl.textContent = `#${idx + 1}`;
                }
            });
        }

        const ajaxConsigneesConfig = {
            url: '/api/consignees/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1,
                };
            },
            processResults: function (data, params) {
                return {
                    results: data.results || [],
                    pagination: {
                        more: data.pagination?.more || false,
                    },
                };
            },
            cache: true
        };

        const ajaxTrucksConfig = {
            url: '/api/trucks/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1,
                };
            },
            processResults: function (data, params) {
                return {
                    results: data.results || [],
                    pagination: {
                        more: data.pagination?.more || false,
                    },
                };
            },
            cache: true
        };

        const ajaxAgenciesConfig = {
            url: '/api/agencies/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1,
                };
            },
            processResults: function (data, params) {
                return {
                    results: data.results || [],
                    pagination: {
                        more: data.pagination?.more || false,
                    },
                };
            },
            cache: true
        };

        function initSelect2In(scope) {
            if (typeof $ === 'undefined') return;

            const $scope = $(scope);

            $scope.find('.customer-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                minimumInputLength: 1,
                placeholder: 'Search and select customer...',
                ajax: ajaxConsigneesConfig
            });

            $scope.find('.consignor-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                minimumInputLength: 1,
                placeholder: 'Search and select consignor...',
                ajax: ajaxConsigneesConfig
            });

            $scope.find('.consignee-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                minimumInputLength: 1,
                placeholder: 'Search and select consignee...',
                ajax: ajaxConsigneesConfig
            });
        }

        function initializeLorryReceiptForm() {
            $(document).ready(function() {

                // Initialize shared dropdowns with Select2 (AJAX-driven for large datasets)
                $('#truck_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    minimumInputLength: 1,
                    placeholder: 'Search and select truck...',
                    ajax: ajaxTrucksConfig
                });

                $('#transporter_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    minimumInputLength: 1,
                    placeholder: 'Search and select transporter...',
                    ajax: ajaxAgenciesConfig
                });

                $('#reference_by').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    minimumInputLength: 1,
                    placeholder: 'Search and select reference...',
                    ajax: ajaxAgenciesConfig
                });

                // Ensure Select2 search input gets autofocus on open (works for dynamic elements too)
                $(document).off('select2:open.autofocus').on('select2:open.autofocus', function() {
                    // Delay to let the search input render
                    setTimeout(function() {
                        const searchInput = document.querySelector('.select2-container--open .select2-search__field');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    }, 0);
                });

                // Add first LR item by default
                addLrItem();
            });
        }
    </script>

    <script>
        // For consignor

        $(document).on('change', '.consignor-select', function() {
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

            window.LREntity.initLorryReceiptTransporterSelectionFlow({
                transporterTypeSelect,
                truckSelect,
                transporterSelect,
                transporterLabel,
                trucksData: {},
                mode: 'create',
                allowTransporterChanges: true,
            });

            const { showValidationError, clearValidationError } = window.LREntity;

            // Dynamic LR items handling
            const lrItemsContainer = document.getElementById('lr-items-container');
            const addBtn = document.getElementById('add-lr-item');

            // Functions are now defined globally above, just wire up the button
            if (addBtn) {
                addBtn.addEventListener('click', () => addLrItem());
            }

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
                const fixedCheckbox = scope.querySelector('.is-fixed-amount');
                const amountHelp = scope.querySelector('.amount-help-text');

                if (fixedCheckbox && fixedCheckbox.checked) {
                    return; // Do not auto-calculate when using fixed amount
                }

                const weight = parseFloat(weightInput.value) || 0;
                const frRate = parseFloat(frRateInput.value) || 0;
                if (weight > 0 && frRate > 0) {
                    const calculatedAmount = weight * frRate;
                    amountInput.value = calculatedAmount.toFixed(2);
                    amountInput.style.backgroundColor = '#e8f5e8';
                    amountInput.title =
                        `Auto-calculated: ${weight} × ${frRate} = ${calculatedAmount.toFixed(2)}`;
                    if (amountHelp) {
                        amountHelp.textContent = 'Auto: Weight × FR Rate (uncheck \"Use fixed amount\" to auto-calculate)';
                    }
                }
            }

            // Per-row fixed amount toggle
            lrItemsContainer.addEventListener('change', function(e) {
                if (e.target.classList.contains('is-fixed-amount')) {
                    const scope = e.target.closest('.lr-item');
                    if (!scope) return;
                    const amountInput = scope.querySelector('.amount');
                    const amountHelp = scope.querySelector('.amount-help-text');

                    if (e.target.checked) {
                        if (amountInput) {
                            amountInput.readOnly = false;
                            amountInput.style.backgroundColor = '';
                            amountInput.title = 'Fixed amount (manual)';
                        }
                        if (amountHelp) {
                            amountHelp.textContent = 'Fixed amount mode: this value will be used instead of Weight × FR Rate.';
                        }
                    } else {
                        if (amountInput) {
                            amountInput.readOnly = true;
                        }
                        if (amountHelp) {
                            amountHelp.textContent = 'Auto: Weight × FR Rate (uncheck to auto-calculate)';
                        }
                        calculateAmount(scope);
                    }
                }
            });

            // Remove auto-calc styling when manually editing amount
            lrItemsContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('amount')) {
                    e.target.style.backgroundColor = '';
                    const scope = e.target.closest('.lr-item');
                    const fixedCheckbox = scope ? scope.querySelector('.is-fixed-amount') : null;
                    if (fixedCheckbox && fixedCheckbox.checked) {
                        e.target.title = 'Fixed amount (manual)';
                    } else {
                        e.target.title = 'Manually edited amount';
                    }
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

            // Invoice number uniqueness per row
            let invoiceNumberTimeout;

            function checkInvoiceUniq(invoiceNumber, feedbackEl, inputEl) {
                if (!invoiceNumber) {
                    feedbackEl.innerHTML = '';
                    inputEl.classList.remove('is-valid', 'is-invalid');
                    return;
                }
                let allInvoiceNumbers = Array.from(document.querySelectorAll(
                    '.invoice-number-input')).map(el => el.value.trim().toLowerCase());
                let duplicateCount = allInvoiceNumbers.filter(num => num === invoiceNumber.toLowerCase())
                    .length;
                if (duplicateCount > 1) {
                    feedbackEl.innerHTML =
                        '<small class="text-danger">❌ This Invoice number is duplicated in the form.</small>';
                    inputEl.classList.remove('is-valid');
                    inputEl.classList.add('is-invalid');
                    inputEl.setCustomValidity('This Invoice number is duplicated in the form.');
                    return;
                }
                feedbackEl.innerHTML = '<small class="text-info">🔍 Checking availability...</small>';
                inputEl.classList.remove('is-valid', 'is-invalid');
                fetch('/api/check-invoice-number-uniqueness', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            invoice_number: invoiceNumber
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
                        console.error('Invoice uniq check error', err);
                        feedbackEl.innerHTML =
                            '<small class="text-warning">⚠️ Unable to check availability</small>';
                        inputEl.classList.remove('is-valid', 'is-invalid');
                    });
            }

            lrItemsContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('invoice-number-input')) {
                    const inputEl = e.target;
                    const feedbackEl = e.target.closest('.col-md-3').querySelector(
                        '.invoice-number-feedback');
                    const invoiceNumber = inputEl.value.trim();
                    clearTimeout(invoiceNumberTimeout);
                    invoiceNumberTimeout = setTimeout(() => checkInvoiceUniq(invoiceNumber, feedbackEl,
                        inputEl), 500);
                }
            });

            lrItemsContainer.addEventListener('blur', function(e) {
                if (e.target.classList.contains('invoice-number-input')) {
                    const inputEl = e.target;
                    const feedbackEl = e.target.closest('.col-md-3').querySelector(
                        '.invoice-number-feedback');
                    const invoiceNumber = inputEl.value.trim();
                    if (invoiceNumber) {
                        clearTimeout(invoiceNumberTimeout);
                        checkInvoiceUniq(invoiceNumber, feedbackEl, inputEl);
                    }
                }
            }, true);

            // Add GR number timeout variable
let grNumberTimeout;

// Add GR number check function
function checkGrUniq(grNumber, feedbackEl, inputEl) {
    if (!grNumber) {
        feedbackEl.innerHTML = '';
        inputEl.classList.remove('is-valid', 'is-invalid');
        return;
    }

    fetch('/api/check-gr-number-uniqueness', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                gr_number: grNumber
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                feedbackEl.innerHTML = '<small class="text-success">✓ Available</small>';
                inputEl.classList.remove('is-invalid');
                inputEl.classList.add('is-valid');
            } else {
                feedbackEl.innerHTML = '<small class="text-danger">✗ Already in use</small>';
                inputEl.classList.remove('is-valid');
                inputEl.classList.add('is-invalid');
            }
        })
        .catch(err => {
            console.error('GR number check error', err);
            feedbackEl.innerHTML = '<small class="text-warning">⚠️ Unable to check availability</small>';
            inputEl.classList.remove('is-valid', 'is-invalid');
        });
}

// Add GR number input event listeners
lrItemsContainer.addEventListener('input', function(e) {
    if (e.target.classList.contains('gr-number-input')) {
        const inputEl = e.target;
        const feedbackEl = inputEl.nextElementSibling;
        const grNumber = inputEl.value.trim();
        clearTimeout(grNumberTimeout);
        grNumberTimeout = setTimeout(() => checkGrUniq(grNumber, feedbackEl, inputEl), 500);
    }
});

lrItemsContainer.addEventListener('blur', function(e) {
    if (e.target.classList.contains('gr-number-input')) {
        const inputEl = e.target;
        const feedbackEl = inputEl.nextElementSibling;
        const grNumber = inputEl.value.trim();
        if (grNumber) {
            clearTimeout(grNumberTimeout);
            checkGrUniq(grNumber, feedbackEl, inputEl);
        }
    }
});
            // Bootstrap validation and SweetAlert confirmation similar to single create
            (function() {
                const form = document.getElementById('lorryReceiptForm');
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (document.querySelectorAll('#lr-items-container .lr-item').length ===
                        0 || document.querySelectorAll('#lr-items-container .lr-item').length ===
                        1) {
                        Swal.fire('No LR Items', 'Please add at least two LR items.', 'warning');
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
                        invoice_number: item.querySelector('.invoice-number-input').value,
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
            <div class="confirmation-details text-start">
                <div class="alert alert-soft-primary bg-primary bg-opacity-10 border-0 mb-3 py-2 px-3 d-flex align-items-center rounded-3">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    <h6 class="mb-0 text-primary fw-bold" style="font-size:0.9rem;">Review Linked Lorry Receipts</h6>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-3 border-top border-3 border-primary">
                            <div class="card-header bg-transparent border-bottom-0 pt-2 pb-0">
                                <h6 class="fw-bold text-dark mb-0" style="font-size:0.9rem;"><i class="fas fa-truck text-primary me-1"></i> Shared Transport Info</h6>
                            </div>
                            <div class="card-body p-2">
                                <div class="row g-2">
                                    <div class="col-md-3 col-sm-6">
                                        <span class="text-muted small d-block">GR Date</span>
                                        <span class="fw-bold text-dark small">${formatDate(grDate)}</span>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <span class="text-muted small d-block">Truck No</span>
                                        <span class="badge bg-dark bg-opacity-10 text-dark fw-bold px-2 py-0 rounded">${truckNumber}</span>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <span class="text-muted small d-block">Transporter</span>
                                        <span class="fw-medium text-dark text-truncate d-block small" title="${transporterName}">${transporterName}</span>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <span class="text-muted small d-block">Reference</span>
                                        <span class="fw-medium text-dark small">${referenceBy}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2" style="font-size:0.9rem;"><i class="fas fa-list-ul text-info me-1"></i> Lorry Receipt Items (${items.length})</h6>
                <div class="row g-2">
                    ${items.map((it, i) => `
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm rounded-3 border-top border-3 border-success mb-2">
                                <div class="card-header bg-light border-bottom border-light pt-2 pb-1 rounded-top-3 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small"><span class="badge bg-primary px-1 py-0 me-1">${i+1}</span> LR: ${it.lr_number}</span>
                                    <span class="text-success fw-bold small">₹${it.amount}</span>
                                </div>
                                <div class="card-body p-2" style="font-size:0.85rem;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Invoice</span>
                                        <span class="fw-medium text-dark">${it.invoice_number}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Customer</span>
                                        <span class="fw-medium text-dark text-truncate ms-2 text-end" style="max-width:130px;" title="${it.customer}">${it.customer}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Consignor</span>
                                        <span class="fw-medium text-dark text-truncate ms-2 text-end" style="max-width:130px;" title="${it.consignor}">${it.consignor}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Consignee</span>
                                        <span class="fw-medium text-dark text-truncate ms-2 text-end" style="max-width:130px;" title="${it.consignee}">${it.consignee}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1 mb-1 text-center bg-light rounded p-1">
                                        <div class="w-100 text-truncate px-1 fw-bold text-dark">${it.source}</div>
                                        <i class="fas fa-arrow-right text-muted" style="font-size:0.7rem; align-self:center;"></i>
                                        <div class="w-100 text-truncate px-1 fw-bold text-dark">${it.destination}</div>
                                    </div>
                                    <div class="row g-1 mt-1 pt-1 border-top border-light">
                                        <div class="col-6">
                                            <span class="text-muted">Goods:</span> <span class="fw-medium text-dark line-clamp-1">${it.good_type}</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span class="text-muted">Units:</span> <span class="fw-medium text-dark">${it.no_of_units}</span>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted">Wt:</span> <span class="fw-medium text-dark">${it.weight} t</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span class="text-muted">Rate:</span> <span class="fw-medium text-dark">₹${it.fr_rate}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                        invoice_number: item.querySelector('.invoice-number-input').value,
                        customer_id: item.querySelector('.customer-select').value,
                        gr_number: item.querySelector('#gr_number').value,
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
                        unloading_station: item.querySelector('#unloading_station').value,
                        lane: item.querySelector('.lane').value || 'Road',
                        good_type: item.querySelector('.goods-type').value,
                        no_of_units: item.querySelector('.units').value,
                        goods_value: item.querySelector('.goods-value')?.value ?? '',
                        weight: item.querySelector('.weight').value,
                        fr_rate: item.querySelector('.fr-rate').value,
                        is_fixed_amount: item.querySelector('.is-fixed-amount')?.checked ? 1 : 0,
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

                        // Update contact number
                        scope.querySelector('.consignor-contact').value = data.contact_number || '';

                        // Handle both nested and flat city/state structures
                        const cityValue = data.city?.name || data.city || '';
                        const stateValue = data.state?.name || data.state || '';

                        scope.querySelector('.source-city').value = cityValue;
                        scope.querySelector('.source-state').value = stateValue;
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

                        // Update contact number
                        scope.querySelector('.consignee-contact').value = data.contact_number || '';

                        // Handle both nested and flat city/state structures
                        const cityValue = data.city?.name || data.city || '';
                        const stateValue = data.state?.name || data.state || '';

                        scope.querySelector('.destination-city').value = cityValue;
                        scope.querySelector('.destination-state').value = stateValue;
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

    </script>
@endsection
