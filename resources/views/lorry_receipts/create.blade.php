@extends('layouts.app')

@section('content')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <style>
        .text-danger {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            word-wrap: break-word;
        }

        .select2-result-truck {
            padding: 4px 0;
        }

        .truck-info {
            font-size: 14px;
            line-height: 1.4;
        }

        .select2-dropdown {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
        }

        .select2-results__option--highlighted {
            background-color: var(--brand-primary, var(--bs-primary));
            color: white;
        }

        #transporter_id.declaration-warning {
            border-color: #ffc107 !important;
            border-width: 2px !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
        }

        .select2-container.declaration-warning .select2-selection {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 0.15rem rgba(245, 158, 11, 0.2) !important;
        }

        .select2-container.declaration-warning .select2-selection__rendered {
            background-color: #fff3cd !important;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/lr-form-redesigned.css') }}">
    <div class="lrf-page app-index-page">
        <div class="lrf-page__toolbar">
            <div>
                <h1 class="lrf-page__toolbar-title">Add New Good Receipt (Bilty)</h1>
                <p class="lrf-page__toolbar-sub">Complete steps 1–3 to save. Optional details can be added anytime.</p>
            </div>
            <div class="lrf-page__toolbar-actions">
                <a href="{{ route('consignees.create') }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                    <i class="bi bi-plus-lg me-1"></i> Create Consignee
                </a>
                <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('lorry_receipts.store') }}" id="lorryReceiptForm" class="needs-validation lrf"
            novalidate="">
            @csrf
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">

            <div id="declarationWarning" class="alert alert-warning lrf-alert mb-3" style="display: none;">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                    <strong>Declaration missing</strong> for the selected truck owner.
                    <a href="#" id="editTruckOwnerLink" target="_blank" class="alert-link">
                        Edit Truck Owner
                    </a>
                </div>
            </div>

            {{-- Section 1 — Receipt Details --}}
            <div class="lrf-section">
                <div class="lrf-section__head">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num">1</div>
                        <div>
                            <h2 class="lrf-section__title">Receipt Details</h2>
                            <p class="lrf-section__desc">Who is shipping, which truck, and who gets paid</p>
                        </div>
                    </div>
                </div>
                <div class="lrf-section__body">
                    @php($selectedTruck = $trucks->firstWhere('id', old('truck_id')))
                    @php($lrTruckSelected = filled(old('truck_id')) || ! empty($selectedTruck))
                    @php($lrPayTypeSelected = in_array(old('transporter_type'), ['owner', 'agency'], true))

                    <div class="lrf-receipt-grid">
                        <div class="lrf-receipt-card">
                            <div class="lrf-receipt-card__title">Document</div>
                            <div class="lrf-receipt-card__row lrf-grid lrf-grid--2">
                                <div class="lrf-pf lrf-pf--feedback">
                                    <label for="lr_number" class="lrf-pf__lbl">LR Number<span class="lrf-pf__req">*</span></label>
                                    <input type="text" class="form-control" id="lr_number" name="lr_number"
                                        value="{{ old('lr_number') }}" required placeholder="Enter unique LR Number" autocomplete="off">
                                    @error('lr_number')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback">Please enter a unique LR number.</div>
                                    <div id="lr-number-feedback" class="lrf-field-feedback" aria-live="polite"></div>
                                </div>
                                <div class="lrf-pf">
                                    <label for="gr_date" class="lrf-pf__lbl">GR Date<span class="lrf-pf__req">*</span></label>
                                    <input type="date" class="form-control" id="gr_date" name="gr_date" required
                                        value="{{ old('gr_date', now()->toDateString()) }}">
                                    @error('gr_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback">Please select a GR date.</div>
                                </div>
                            </div>
                            <div class="lrf-receipt-card__row lrf-pf">
                                <div class="lrf-pf__lbl-row">
                                    <label for="customer_id" class="lrf-pf__lbl">Customer<span class="lrf-pf__req">*</span></label>
                                    <a href="{{ route('consignees.create') }}" class="lrf-quick-add" target="_blank" rel="noopener">
                                        <i class="bi bi-plus-lg"></i> Add
                                    </a>
                                </div>
                                <select class="form-select" id="customer_id" name="customer_id" required
                                    data-placeholder="Search and select customer...">
                                    <option value="">Select Customer</option>
                                    @php($selectedCustomer = $consignees->firstWhere('id', old('customer_id')))
                                    @if($selectedCustomer)
                                        <option value="{{ $selectedCustomer->id }}" selected>
                                            {{ $selectedCustomer->name }}
                                        </option>
                                    @endif
                                </select>
                                @error('customer_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">Please select a customer.</div>
                            </div>
                        </div>

                        <div class="lrf-receipt-card">
                            <div class="lrf-receipt-card__title">Vehicle &amp; Payee</div>
                            <div class="lrf-receipt-card__row lrf-grid lrf-grid--2">
                                <div class="lrf-pf">
                                    <label for="truck_id" class="lrf-pf__lbl">Truck<span class="lrf-pf__req">*</span></label>
                                    <select class="form-select" id="truck_id" name="truck_id" required
                                        data-placeholder="Search and select truck...">
                                        <option value="">Select Trucks</option>
                                        @if($selectedTruck)
                                            <option value="{{ $selectedTruck->id }}"
                                                data-transporter-id="{{ $selectedTruck->transporter_id }}"
                                                data-transporter-name="{{ $selectedTruck->transporter ? $selectedTruck->transporter->name : '' }}"
                                                data-driver-name="{{ $selectedTruck->driver?->name ?? '' }}"
                                                selected>
                                                {{ $selectedTruck->truck_number }}
                                                @if ($selectedTruck->driver)
                                                    - Driver: {{ $selectedTruck->driver->name }}
                                                @endif
                                                ({{ $selectedTruck->transporter ? $selectedTruck->transporter->name : 'No Truck Owner' }})
                                            </option>
                                        @endif
                                    </select>
                                    @error('truck_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback">Please select a truck.</div>
                                </div>

                                <div class="lrf-pf" id="lrPayToField" @if(! $lrTruckSelected) hidden @endif>
                                    <label class="lrf-pf__lbl" id="transporterTypeLabel">Pay to<span class="lrf-pf__req">*</span></label>
                                    <div class="lrf-pay-pills" role="group" aria-label="Pay to">
                                        <button type="button" class="lrf-pay-pill{{ old('transporter_type') === 'owner' ? ' is-active' : '' }}"
                                            data-pay-type="owner" id="lrPayPillOwner">Truck Owner</button>
                                        <button type="button" class="lrf-pay-pill{{ old('transporter_type') === 'agency' ? ' is-active' : '' }}"
                                            data-pay-type="agency" id="lrPayPillAgency">Agency</button>
                                    </div>
                                    <select class="form-select lrf-pay-type-select" id="transporter_type" name="transporter_type"
                                        @if($lrTruckSelected) required @endif
                                        aria-hidden="true" tabindex="-1">
                                        <option value="">Select Type</option>
                                        <option value="owner" {{ old('transporter_type') === 'owner' ? 'selected' : '' }}>Truck Owner</option>
                                        <option value="agency" {{ old('transporter_type') === 'agency' ? 'selected' : '' }}>Agency</option>
                                    </select>
                                    <div class="invalid-feedback">Please select who gets paid.</div>
                                </div>
                            </div>

                            <div id="lrPayeeRow" class="lrf-receipt-card__row lrf-grid lrf-grid--2 lrf-payee-row" @if(! $lrPayTypeSelected) hidden @endif>
                                <div class="lrf-pf">
                                    <div class="lrf-pf__lbl-row">
                                        <label for="transporter_id" id="transporterLabel" class="lrf-pf__lbl">
                                            @if(old('transporter_type') === 'agency')
                                                Agency<span class="lrf-pf__req">*</span>
                                            @elseif(old('transporter_type') === 'owner')
                                                Truck Owner<span class="lrf-pf__req">*</span>
                                            @else
                                                Payee<span class="lrf-pf__req">*</span>
                                            @endif
                                        </label>
                                        <span id="lr-add-transporter-wrap" class="d-none">
                                            <a id="lr-add-transporter-btn"
                                                href="{{ route('truck_owners.create') }}"
                                                data-href-owner="{{ route('truck_owners.create') }}"
                                                class="lrf-quick-add"
                                                target="_blank"
                                                rel="noopener">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </a>
                                        </span>
                                    </div>
                                    <div class="lr-payee-select-wrap">
                                        <select class="form-select" id="transporter_id" name="transporter_id"
                                            @if($lrPayTypeSelected) required @endif
                                            onchange="window.LREntity.showTdsPercentage(this.value)">
                                            <option value="">Select Payee</option>
                                            @if (! empty($payeeTransporter))
                                                <option value="{{ $payeeTransporter->id }}" selected>
                                                    {{ $payeeTransporter->displayNameWithShortName() }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                    @error('transporter_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback">Please select a payee.</div>
                                    <div id="tdsRateDisplay" class="lrf-tds-source" hidden aria-hidden="true">
                                        <span id="tdsPercentageValue">--</span>
                                    </div>
                                </div>

                                <div class="lrf-pf lrf-pay-meta">
                                    <div class="lrf-pf__lbl-row">
                                        <span class="lrf-pf__lbl">Driver / TDS</span>
                                        <span class="lrf-quick-add lrf-quick-add--spacer" aria-hidden="true">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </span>
                                    </div>
                                    <div class="lrf-truck-summary lrf-truck-summary--field" id="lrTruckSummary" role="status">
                                        <span class="lrf-truck-summary__item" id="lrTruckSummaryDriverItem" hidden>
                                            <span class="lrf-truck-summary__k">Driver</span>
                                            <span class="lrf-truck-summary__v" id="lrTruckSummaryDriver">—</span>
                                        </span>
                                        <span class="lrf-truck-summary__sep" id="lrTruckSummaryDriverSep" hidden aria-hidden="true">·</span>
                                        <span class="lrf-truck-summary__item">
                                            <span class="lrf-truck-summary__k">TDS</span>
                                            <span class="lrf-truck-summary__v" id="lrTruckSummaryTds">—</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2 — Parties & Route --}}
            <div class="lrf-section">
                <div class="lrf-section__head lrf-section__head--green">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num lrf-step-num--green">2</div>
                        <div>
                            <h2 class="lrf-section__title">Parties & Route</h2>
                            <p class="lrf-section__desc">Goods travel from consignor to consignee</p>
                        </div>
                    </div>
                </div>
                <div class="lrf-section__body">
                    <div class="lrf-party-flow" id="lrPartyFlow">
                        <div class="lrf-party-card lrf-party-card--from" id="lrConsignorCard">
                            <div class="lrf-party-card__badge">FROM</div>
                            <div class="lrf-party-card__title">
                                <i class="bi bi-geo-alt-fill"></i> Consignor<span class="lrf-pf__req">*</span>
                            </div>
                            <div class="lrf-pf">
                                <select class="form-select" id="consignor_id" name="consignor_id" required
                                    data-placeholder="Search consignor...">
                                    <option value="">Select Consignor</option>
                                    @php($selectedConsignor = $consignees->firstWhere('id', old('consignor_id')))
                                    @if($selectedConsignor)
                                        <option value="{{ $selectedConsignor->id }}" selected>
                                            {{ $selectedConsignor->name }}
                                        </option>
                                    @endif
                                </select>
                                @error('consignor_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">Please select a consignor.</div>
                            </div>
                            <div class="lrf-party-meta">
                                <div class="lrf-party-meta__item">
                                    <span class="lrf-party-meta__label">Contact</span>
                                    <span class="lrf-party-meta__value" id="consignorContactLabel">{{ old('consignor_contact_number') ?: '—' }}</span>
                                </div>
                                <div class="lrf-party-meta__item">
                                    <span class="lrf-party-meta__label">City</span>
                                    <span class="lrf-party-meta__value" id="sourceCityLabel">{{ old('source_city') ?: '—' }}</span>
                                </div>
                                <div class="lrf-party-meta__item">
                                    <span class="lrf-party-meta__label">State</span>
                                    <span class="lrf-party-meta__value" id="sourceStateLabel">{{ old('source_state') ?: '—' }}</span>
                                </div>
                            </div>
                            <input type="hidden" id="consignor_contact_number" name="consignor_contact_number" value="{{ old('consignor_contact_number') }}">
                            <input type="hidden" id="source_city" name="source_city" value="{{ old('source_city') }}" required>
                            <input type="hidden" id="source_state" name="source_state" value="{{ old('source_state') }}" required>
                            <div class="lrf-pf mt-2">
                                <label for="loading_station" class="lrf-pf__lbl">Loading Station</label>
                                <input type="text" class="form-control" id="loading_station" name="loading_station"
                                    value="{{ old('loading_station') }}" placeholder="Optional">
                            </div>
                        </div>

                        <div class="lrf-goods-lane" id="lrGoodsLane" aria-hidden="true">
                            <div class="lrf-goods-lane__track">
                                <span class="lrf-goods-lane__dot lrf-goods-lane__dot--start"></span>
                                <span class="lrf-goods-lane__line"></span>
                                <span class="lrf-goods-lane__dot lrf-goods-lane__dot--end"></span>
                                <span class="lrf-goods-lane__cargo" id="lrGoodsCargo">
                                    <i class="bi bi-box-seam-fill"></i>
                                </span>
                            </div>
                            <div class="lrf-goods-lane__label" id="lrRouteFlowLabel">Select parties to start route</div>
                            <div class="lrf-goods-lane__sub" id="lrRouteFlowSub">Goods move from consignor → consignee</div>
                        </div>

                        <div class="lrf-party-card lrf-party-card--to" id="lrConsigneeCard">
                            <div class="lrf-party-card__badge lrf-party-card__badge--to">TO</div>
                            <div class="lrf-party-card__title">
                                <i class="bi bi-geo-fill"></i> Consignee<span class="lrf-pf__req">*</span>
                            </div>
                            <div class="lrf-pf">
                                <select class="form-select" id="consignee_id" name="consignee_id" required
                                    data-placeholder="Search consignee...">
                                    <option value="">Select Consignee</option>
                                    @php($selectedConsignee = $consignees->firstWhere('id', old('consignee_id')))
                                    @if($selectedConsignee)
                                        <option value="{{ $selectedConsignee->id }}" selected>
                                            {{ $selectedConsignee->name }}
                                        </option>
                                    @endif
                                </select>
                                @error('consignee_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">Please select a consignee.</div>
                            </div>
                            <div class="lrf-party-meta">
                                <div class="lrf-party-meta__item">
                                    <span class="lrf-party-meta__label">Contact</span>
                                    <span class="lrf-party-meta__value" id="consigneeContactLabel">{{ old('consignee_contact_number') ?: '—' }}</span>
                                </div>
                                <div class="lrf-party-meta__item">
                                    <span class="lrf-party-meta__label">City</span>
                                    <span class="lrf-party-meta__value" id="destinationCityLabel">{{ old('destination_city') ?: '—' }}</span>
                                </div>
                                <div class="lrf-party-meta__item">
                                    <span class="lrf-party-meta__label">State</span>
                                    <span class="lrf-party-meta__value" id="destinationStateLabel">{{ old('destination_state') ?: '—' }}</span>
                                </div>
                            </div>
                            <input type="hidden" id="consignee_contact_number" name="consignee_contact_number" value="{{ old('consignee_contact_number') }}">
                            <input type="hidden" id="destination_city" name="destination_city" value="{{ old('destination_city') }}" required>
                            <input type="hidden" id="destination_state" name="destination_state" value="{{ old('destination_state') }}" required>
                            <div class="lrf-pf mt-2">
                                <label for="unloading_station" class="lrf-pf__lbl">Unloading Station</label>
                                <input type="text" class="form-control" id="unloading_station" name="unloading_station"
                                    value="{{ old('unloading_station') }}" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3 — Goods & Charges --}}
            <div class="lrf-section">
                <div class="lrf-section__head lrf-section__head--summary">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num lrf-step-num--summary">3</div>
                        <div>
                            <h2 class="lrf-section__title">Goods & Charges</h2>
                            <p class="lrf-section__desc">Cargo details and auto-calculated freight amount</p>
                        </div>
                    </div>
                </div>
                <div class="lrf-section__body">
                    <input type="hidden" id="lane" name="lane" value="Road">

                    <div class="lrf-grid lrf-grid--5">
                        <div class="lrf-pf">
                            <label for="good_type" class="lrf-pf__lbl">Goods Type<span class="lrf-pf__req">*</span></label>
                            <div class="input-group flex-nowrap">
                                <select class="form-select" id="good_type" name="good_type" required>
                                    <option value="">Select Goods Type</option>
                                    @foreach ($goodsTypes as $goodsType)
                                        <option value="{{ $goodsType->name }}"
                                            {{ old('good_type') == $goodsType->name ? 'selected' : '' }}>
                                            {{ $goodsType->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#addGoodsTypeModal" title="Add New Goods Type">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            @error('good_type')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please select a goods type.</div>
                        </div>
                        <div class="lrf-pf">
                            <label for="no_of_units" class="lrf-pf__lbl">No. of Units</label>
                            <input type="number" class="form-control" id="no_of_units" name="no_of_units"
                                value="{{ old('no_of_units') }}" min="1" max="100000"
                                title="Maximum 100,000 units allowed" placeholder="Optional">
                            @error('no_of_units')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please enter a valid number of units (max 100,000).</div>
                        </div>
                        <div class="lrf-pf">
                            <label for="weight" class="lrf-pf__lbl">Weight (Tons)<span class="lrf-pf__req">*</span></label>
                            <input type="number" step="0.001" class="form-control" id="weight" name="weight"
                                required value="{{ old('weight') }}" min="0.1" max="100"
                                title="Weight must be between 0.1 and 100 tons" placeholder="0.000">
                            @error('weight')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please enter a weight between 0.1 and 100 tons.</div>
                        </div>
                        <div class="lrf-pf">
                            <label for="fr_rate" class="lrf-pf__lbl">Freight Rate (₹/Ton)<span class="lrf-pf__req">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate"
                                required value="{{ old('fr_rate') }}" min="1" max="20000"
                                title="Freight rate must be between 1 and 20,000 per ton" placeholder="0.00">
                            @error('fr_rate')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Between 1 and 20,000.</div>
                        </div>
                        <div class="lrf-pf">
                            <label class="lrf-pf__lbl" for="amount">
                                <span>Amount (₹)<span class="lrf-pf__req">*</span></span>
                                <span class="lrf-amount-toggle">
                                    <input class="form-check-input" type="checkbox" id="is_fixed_amount"
                                        name="is_fixed_amount" value="1" {{ old('is_fixed_amount') ? 'checked' : '' }}>
                                    <label class="form-check-label mb-0" for="is_fixed_amount">Fixed</label>
                                </span>
                            </label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount"
                                required value="{{ old('amount') }}"
                                title="Auto-calculated: Weight × Freight Rate (editable, or fixed when enabled)" placeholder="0.00">
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please enter an amount.</div>
                            <div class="lrf-pf__hint" id="amountHelpText">Auto: Weight × Rate</div>
                        </div>
                    </div>

                    <div class="lrf-summary-metrics">
                        <div class="lrf-metric">
                            <div class="lrf-metric__icon" style="--ic:#0d9488;--bg:#ecfeff"><i class="bi bi-speedometer2"></i></div>
                            <div>
                                <span class="lrf-metric__name">Weight</span>
                                <span class="lrf-metric__val" id="lrSummaryWeight">—</span>
                            </div>
                        </div>
                        <div class="lrf-metric">
                            <div class="lrf-metric__icon" style="--ic:#7c3aed;--bg:#faf5ff"><i class="bi bi-currency-rupee"></i></div>
                            <div>
                                <span class="lrf-metric__name">Rate</span>
                                <span class="lrf-metric__val" id="lrSummaryRate">—</span>
                            </div>
                        </div>
                        <div class="lrf-metric lrf-metric--hero">
                            <div class="lrf-metric__icon" style="--ic:#16a34a;--bg:#dcfce7"><i class="bi bi-cash-stack"></i></div>
                            <div>
                                <span class="lrf-metric__name">Total Amount</span>
                                <span class="lrf-metric__val" id="lrSummaryAmount">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php($lrOptionalOpen = filled(old('reference_by')) || filled(old('invoice_number')) || filled(old('gr_number')) || filled(old('goods_value')) || $errors->hasAny(['reference_by', 'invoice_number', 'gr_number', 'goods_value']))

            {{-- Section 4 — Optional Details --}}
            <div class="lrf-section lrf-section--collapsible{{ $lrOptionalOpen ? ' lrf-section--open' : '' }}" id="lrOptionalSection">
                <button type="button" class="lrf-section__head lrf-section__head--plain lrf-section__head--toggle" id="lrOptionalToggle" aria-expanded="{{ $lrOptionalOpen ? 'true' : 'false' }}" aria-controls="lrOptionalBody">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num lrf-step-num--gray">4</div>
                        <div>
                            <h2 class="lrf-section__title">Optional Details</h2>
                            <p class="lrf-section__desc">GR number, invoice, goods value and reference</p>
                        </div>
                    </div>
                    <div class="lrf-section__head-right">
                        <span class="lrf-optional-chip"><i class="bi bi-plus-circle"></i> Expand if needed</span>
                        <span class="lrf-collapse-arrow{{ $lrOptionalOpen ? ' lrf-collapse-arrow--open' : '' }}" aria-hidden="true">
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </div>
                </button>

                <div class="lrf-section__body" id="lrOptionalBody" @unless($lrOptionalOpen) style="display: none;" @endunless>
                    <div class="lrf-grid lrf-grid--4">
                        <div class="lrf-pf">
                            <label for="gr_number" class="lrf-pf__lbl">GR Number</label>
                            <input type="text" class="form-control" id="gr_number" name="gr_number"
                                value="{{ old('gr_number') }}" placeholder="Enter GR Number">
                            @error('gr_number')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please enter a valid GR number.</div>
                            <div id="gr-number-feedback" class="mt-1"></div>
                        </div>
                        <div class="lrf-pf">
                            <label for="invoice_number" class="lrf-pf__lbl">Invoice Number</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number"
                                value="{{ old('invoice_number') }}" placeholder="Enter Invoice Number">
                            @error('invoice_number')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please enter a valid invoice number.</div>
                            <div id="invoice-number-feedback" class="mt-1"></div>
                        </div>
                        <div class="lrf-pf">
                            <label for="goods_value" class="lrf-pf__lbl">Goods Value</label>
                            <input type="number" step="0.01" class="form-control" id="goods_value" name="goods_value"
                                value="{{ old('goods_value') }}" min="0" placeholder="">
                            @error('goods_value')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                           
                        </div>
                        <div class="lrf-pf">
                            <label for="reference_by" class="lrf-pf__lbl">Reference By</label>
                            <div class="lr-payee-select-wrap">
                                <select class="form-select" id="reference_by" name="reference_by">
                                    <option value="">Select Reference</option>
                                    @php($selectedAgency = $agencies->firstWhere('id', old('reference_by')))
                                    @if($selectedAgency)
                                        <option value="{{ $selectedAgency->id }}" selected>
                                            {{ $selectedAgency->name }}
                                        </option>
                                    @endif
                                </select>
                            </div>
                            @error('reference_by')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Please select a reference.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lrf-actions">
                <a href="{{ route('lorry_receipts.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="lrClearFormBtn">
                    <i class="bi bi-eraser me-1"></i> Clear
                </button>
                <button class="btn btn-success btn-sm" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Save
                </button>
            </div>
        </form>
    </div>

    <!-- Add Goods Type Modal -->
    <div class="modal fade fb-pay-modal" id="addGoodsTypeModal" tabindex="-1" aria-labelledby="addGoodsTypeModalLabel"
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

        function initializeLorryReceiptForm() {
            $(document).ready(function() {
                // Initialize other dropdowns with Select2
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

                const select2AjaxTransport = function (params, success, failure) {
                    const $element = $(params.element);
                    const previousRequest = $element.data('select2ActiveRequest');
                    if (previousRequest && typeof previousRequest.abort === 'function') {
                        previousRequest.abort();
                    }
                    const request = $.ajax(params);
                    $element.data('select2ActiveRequest', request);
                    request.then(success);
                    request.fail(failure);
                    request.always(function () {
                        $element.removeData('select2ActiveRequest');
                    });
                    return request;
                };

                const ajaxTruckOwnersConfig = {
                    url: '/api/truck-owners/search',
                    dataType: 'json',
                    delay: 700,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            limit: 20
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results || [],
                            pagination: { more: false }
                        };
                    },
                    transport: select2AjaxTransport,
                    cache: true
                };

                const lrAddTransporterBtn = document.getElementById('lr-add-transporter-btn');
                const lrAddTransporterWrap = document.getElementById('lr-add-transporter-wrap');

                function syncLrTransporterAddButton(paymentType) {
                    if (lrAddTransporterWrap) {
                        if (paymentType === 'owner') {
                            lrAddTransporterWrap.classList.remove('d-none');
                        } else {
                            lrAddTransporterWrap.classList.add('d-none');
                        }
                    }
                    if (lrAddTransporterBtn) {
                        lrAddTransporterBtn.href = lrAddTransporterBtn.getAttribute('data-href-owner') || lrAddTransporterBtn.href;
                    }
                }

                function bindTransporterSelect2Title($t) {
                    function setTitleFromSelection() {
                        const inst = $t.select2('data');
                        const fromInst = inst && inst[0] && inst[0].text ? String(inst[0].text) : '';
                        const fromOpt = ($t.find('option:selected').text() || '').trim();
                        const text = (fromInst || fromOpt).trim();
                        $t.next('.select2-container').find('.select2-selection__rendered').attr('title', text);
                    }
                    $t.off('select2:select.lrPayeeTitle select2:clear.lrPayeeTitle change.lrPayeeTitle')
                        .on('select2:select.lrPayeeTitle', function (e) {
                            const t = e.params && e.params.data && e.params.data.text ? String(e.params.data.text) : '';
                            $(this).next('.select2-container').find('.select2-selection__rendered').attr('title', t);
                        })
                        .on('select2:clear.lrPayeeTitle', function () {
                            $(this).next('.select2-container').find('.select2-selection__rendered').attr('title', '');
                        })
                        .on('change.lrPayeeTitle', function () {
                            setTimeout(setTitleFromSelection, 0);
                        });
                }

                function rebindLrTransporterSelect2(paymentType) {
                    const $t = $('#transporter_id');
                    if (!$t.length || typeof $t.select2 !== 'function') {
                        return;
                    }
                    if ($t.hasClass('select2-hidden-accessible')) {
                        $t.select2('destroy');
                    }
                    const mode = paymentType === 'owner' ? 'owner' : 'agency';
                    syncLrTransporterAddButton(mode);
                    if (mode === 'owner') {
                        $t.select2({
                            theme: 'bootstrap-5',
                            width: '100%',
                            allowClear: true,
                            minimumInputLength: 1,
                            placeholder: 'Search truck owner (type first letter)...',
                            language: {
                                inputTooShort: function () {
                                    return 'Please type at least 1 character to search';
                                },
                                searching: function () {
                                    return 'Searching...';
                                }
                            },
                            ajax: ajaxTruckOwnersConfig
                        });
                    } else {
                        $t.select2({
                            theme: 'bootstrap-5',
                            width: '100%',
                            allowClear: true,
                            minimumInputLength: 0,
                            placeholder: 'Search agency...',
                            ajax: ajaxAgenciesConfig
                        });
                    }
                    bindTransporterSelect2Title($t);
                    $t.trigger('change.select2');
                }

                window.lrCreateRebindTransporterSelect2 = rebindLrTransporterSelect2;

                // Customer / consignor / consignee
                $('#customer_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    minimumInputLength: 1,
                    placeholder: 'Search and select customer...',
                    ajax: ajaxConsigneesConfig
                });
                $('#consignor_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    minimumInputLength: 1,
                    placeholder: 'Search and select consignor...',
                    ajax: ajaxConsigneesConfig
                });
                $('#consignee_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    minimumInputLength: 1,
                    placeholder: 'Search and select consignee...',
                    ajax: ajaxConsigneesConfig
                });

                // Truck & transporter/reference (truck-owner confirmation flow uses Select2 result data)
                $('#truck_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    minimumInputLength: 1,
                    placeholder: 'Search and select truck...',
                    ajax: ajaxTrucksConfig
                });
                const initialPayeeMode = document.getElementById('transporter_type')?.value === 'owner' ? 'owner' : 'agency';
                rebindLrTransporterSelect2(initialPayeeMode);

                $('#reference_by').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    minimumInputLength: 0,
                    placeholder: 'Search reference agency...',
                    ajax: ajaxAgenciesConfig
                });
                bindTransporterSelect2Title($('#reference_by'));

                // Small/static lists
                $('#good_type').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    minimumResultsForSearch: 0
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

                const transporterTypeSelectEl = document.getElementById('transporter_type');
                const truckSelectEl = document.getElementById('truck_id');
                const transporterSelectEl = document.getElementById('transporter_id');
                const transporterLabelEl = document.getElementById('transporterLabel');

                function syncLrPayPillsFromSelect() {
                    if (!transporterTypeSelectEl) {
                        return;
                    }
                    const selectedType = transporterTypeSelectEl.value;
                    document.querySelectorAll('.lrf-pay-pill').forEach((pill) => {
                        const payType = pill.getAttribute('data-pay-type');
                        pill.classList.toggle('is-active', payType === selectedType);
                        if (payType === 'owner') {
                            const ownerOption = transporterTypeSelectEl.querySelector('option[value="owner"]');
                            pill.disabled = !!(ownerOption && ownerOption.disabled);
                        }
                    });
                }

                document.querySelectorAll('.lrf-pay-pill').forEach((pill) => {
                    pill.addEventListener('click', function () {
                        if (this.disabled || !transporterTypeSelectEl) {
                            return;
                        }
                        const payType = this.getAttribute('data-pay-type');
                        if (!payType || transporterTypeSelectEl.value === payType) {
                            syncLrPayPillsFromSelect();
                            return;
                        }
                        transporterTypeSelectEl.value = payType;
                        transporterTypeSelectEl.dispatchEvent(new Event('change'));
                        syncLrPayPillsFromSelect();
                    });
                });

                if (transporterTypeSelectEl) {
                    transporterTypeSelectEl.addEventListener('change', syncLrPayPillsFromSelect);
                    syncLrPayPillsFromSelect();
                }

                if (transporterTypeSelectEl && truckSelectEl && transporterSelectEl && transporterLabelEl) {
                    window.LREntity.initLorryReceiptTransporterSelectionFlow({
                        transporterTypeSelect: transporterTypeSelectEl,
                        truckSelect: truckSelectEl,
                        transporterSelect: transporterSelectEl,
                        transporterLabel: transporterLabelEl,
                        trucksData: {},
                        mode: 'create',
                        allowTransporterChanges: true,
                        afterTransporterTypeChange: function (selectedType) {
                            const mode = selectedType === 'owner' ? 'owner' : 'agency';
                            if (selectedType === 'owner' || selectedType === 'agency') {
                                if (typeof window.lrCreateRebindTransporterSelect2 === 'function') {
                                    window.lrCreateRebindTransporterSelect2(mode);
                                }
                            }
                            syncLrPayPillsFromSelect();
                            if (typeof window.syncLrPayeeVisibility === 'function') {
                                window.syncLrPayeeVisibility();
                            }
                        },
                    });
                }

                // Trigger Select2 to refresh and show selected values
                setTimeout(function() {
                    $('#customer_id, #truck_id, #transporter_id, #reference_by, #consignor_id, #consignee_id, #good_type')
                        .trigger('change.select2');
                    const payeeId = $('#transporter_id').val();
                    if (payeeId && typeof window.LREntity.showTdsPercentage === 'function') {
                        window.LREntity.showTdsPercentage(payeeId);
                    }
                }, 500);
            });
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const { showValidationError, clearValidationError } = window.LREntity;

            function setPartyMetaLabel(labelId, value) {
                const el = document.getElementById(labelId);
                if (!el) {
                    return;
                }
                const text = (value || '').toString().trim();
                el.textContent = text || '—';
                el.classList.toggle('is-empty', !text);
            }

            function syncConsignorLabels() {
                setPartyMetaLabel('consignorContactLabel', consignorContactInput.value);
                setPartyMetaLabel('sourceCityLabel', sourceCityInput.value);
                setPartyMetaLabel('sourceStateLabel', sourceStateInput.value);
                updateGoodsRouteFlow();
            }

            function syncConsigneeLabels() {
                setPartyMetaLabel('consigneeContactLabel', consigneeContactInput.value);
                setPartyMetaLabel('destinationCityLabel', destinationCityInput.value);
                setPartyMetaLabel('destinationStateLabel', destinationStateInput.value);
                updateGoodsRouteFlow();
            }

            function getSelect2Text(selectEl) {
                if (!selectEl || !selectEl.value) {
                    return '';
                }
                if (window.jQuery && jQuery(selectEl).hasClass('select2-hidden-accessible')) {
                    const data = jQuery(selectEl).select2('data');
                    if (data && data[0] && data[0].text) {
                        return String(data[0].text).trim();
                    }
                }
                const option = selectEl.options[selectEl.selectedIndex];
                return option ? String(option.text).trim() : '';
            }

            function pulsePartyCard(cardEl) {
                if (!cardEl) {
                    return;
                }
                cardEl.classList.remove('is-pulse');
                void cardEl.offsetWidth;
                cardEl.classList.add('is-pulse');
            }

            function updateGoodsRouteFlow() {
                const lane = document.getElementById('lrGoodsLane');
                const label = document.getElementById('lrRouteFlowLabel');
                const sub = document.getElementById('lrRouteFlowSub');
                const fromCard = document.getElementById('lrConsignorCard');
                const toCard = document.getElementById('lrConsigneeCard');
                if (!lane || !label || !sub) {
                    return;
                }

                const hasFrom = Boolean(consignorSelect && consignorSelect.value);
                const hasTo = Boolean(consigneeSelect && consigneeSelect.value);
                const fromCity = (sourceCityInput.value || '').trim();
                const toCity = (destinationCityInput.value || '').trim();
                const fromName = getSelect2Text(consignorSelect);
                const toName = getSelect2Text(consigneeSelect);

                if (fromCard) {
                    fromCard.classList.toggle('is-filled', hasFrom);
                }
                if (toCard) {
                    toCard.classList.toggle('is-filled', hasTo);
                }

                lane.classList.toggle('is-partial', hasFrom !== hasTo || (hasFrom && hasTo && (!fromCity || !toCity)));
                lane.classList.toggle('is-ready', hasFrom && hasTo && Boolean(fromCity) && Boolean(toCity));

                if (hasFrom && hasTo && fromCity && toCity) {
                    label.textContent = `${fromCity} → ${toCity}`;
                    sub.textContent = 'Goods moving to consignee';
                } else if (hasFrom && hasTo) {
                    label.textContent = fromName && toName
                        ? `${fromName.split('(')[0].trim()} → ${toName.split('(')[0].trim()}`
                        : 'Route almost ready';
                    sub.textContent = 'Loading location details…';
                } else if (hasFrom) {
                    label.textContent = fromCity ? `From ${fromCity}` : 'Consignor selected';
                    sub.textContent = 'Select consignee (TO)';
                } else if (hasTo) {
                    label.textContent = toCity ? `To ${toCity}` : 'Consignee selected';
                    sub.textContent = 'Select consignor (FROM)';
                } else {
                    label.textContent = 'Select parties to start route';
                    sub.textContent = 'Goods move from consignor → consignee';
                }
            }

            // Handle consignor contact number and location details
            const consignorSelect = document.getElementById('consignor_id');
            const consignorContactInput = document.getElementById('consignor_contact_number');
            const sourceCityInput = document.getElementById('source_city');
            const sourceStateInput = document.getElementById('source_state');

            // Handle consignor selection with Select2 support
            function handleConsignorSelection(consignorId) {
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
                    syncConsignorLabels();
                    return;
                } else {
                    clearValidationError(consignorSelect);
                }

                if (consignorId) {
                    fetch(`/api/consignees/${consignorId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Auto-populate consignor fields
                            consignorContactInput.value = data.contact_number || '';
                            // Use city and state names from the API response
                            sourceCityInput.value = data.city || '';
                            sourceStateInput.value = data.state || '';
                            syncConsignorLabels();
                            pulsePartyCard(document.getElementById('lrConsignorCard'));
                        })
                        .catch(error => {
                            console.error('❌ Error fetching consignor data:', error);
                        });
                } else {
                    consignorContactInput.value = '';
                    sourceCityInput.value = '';
                    sourceStateInput.value = '';
                    syncConsignorLabels();
                }
            }

            // Handle Select2 consignor selection
            $('#consignor_id').on('select2:select', function(e) {
                const consignorId = e.params.data.id;
                handleConsignorSelection(consignorId);
            });

            // Handle regular consignor selection (fallback)
            consignorSelect.addEventListener('change', function() {
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
                    syncConsigneeLabels();
                    return;
                } else {
                    clearValidationError(consigneeSelect);
                }

                if (consigneeId) {
                    fetch(`/api/consignees/${consigneeId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Auto-populate consignee fields
                            consigneeContactInput.value = data.contact_number || '';
                            // Use city and state names from the API response
                            destinationCityInput.value = data.city || '';
                            destinationStateInput.value = data.state || '';
                            syncConsigneeLabels();
                            pulsePartyCard(document.getElementById('lrConsigneeCard'));
                        })
                        .catch(error => {
                            console.error('❌ Error fetching consignee data:', error);
                        });
                } else {
                    consigneeContactInput.value = '';
                    destinationCityInput.value = '';
                    destinationStateInput.value = '';
                    syncConsigneeLabels();
                }
            }

            // Handle Select2 consignee selection
            $('#consignee_id').on('select2:select', function(e) {
                const consigneeId = e.params.data.id;
                handleConsigneeSelection(consigneeId);
            });

            // Handle regular consignee selection (fallback)
            consigneeSelect.addEventListener('change', function() {
                const consigneeId = this.value;
                handleConsigneeSelection(consigneeId);
            });

            syncConsignorLabels();
            syncConsigneeLabels();
            updateGoodsRouteFlow();

            // Truck / pay-to / payee visibility + owner/driver/TDS summary
            const truckSelect = document.getElementById('truck_id');
            const lrTruckSummary = document.getElementById('lrTruckSummary');
            const lrTruckSummaryTds = document.getElementById('lrTruckSummaryTds');
            const lrTruckSummaryDriver = document.getElementById('lrTruckSummaryDriver');
            const lrTruckSummaryDriverItem = document.getElementById('lrTruckSummaryDriverItem');
            const lrTruckSummaryDriverSep = document.getElementById('lrTruckSummaryDriverSep');
            const tdsPercentageValue = document.getElementById('tdsPercentageValue');
            const lrPayToField = document.getElementById('lrPayToField');
            const lrPayeeRow = document.getElementById('lrPayeeRow');

            function displayOrDash(value) {
                const text = (value || '').toString().trim();
                if (!text || text === 'No Truck Owner' || text === '--' || text === 'Loading...' || text === 'Error') {
                    return '—';
                }
                return text;
            }

            function syncTruckSummaryTds() {
                if (!lrTruckSummaryTds) {
                    return;
                }
                lrTruckSummaryTds.textContent = displayOrDash(tdsPercentageValue ? tdsPercentageValue.textContent : '');
            }

            function syncPayeeFieldLockStyle() {
                const transporterSelect = document.getElementById('transporter_id');
                if (!lrPayeeRow || !transporterSelect) {
                    return;
                }
                lrPayeeRow.classList.toggle('is-payee-locked', !!transporterSelect.disabled);
            }

            function syncPayeeVisibility() {
                const hasTruck = !!(truckSelect && truckSelect.value);
                const transporterTypeSelect = document.getElementById('transporter_type');
                const transporterSelect = document.getElementById('transporter_id');
                const payType = transporterTypeSelect ? transporterTypeSelect.value : '';
                const hasPayType = payType === 'owner' || payType === 'agency';

                if (lrPayToField) {
                    lrPayToField.hidden = !hasTruck;
                }
                if (lrPayeeRow) {
                    lrPayeeRow.hidden = !hasTruck || !hasPayType;
                }
                if (transporterTypeSelect) {
                    transporterTypeSelect.required = hasTruck;
                }
                if (transporterSelect) {
                    transporterSelect.required = hasTruck && hasPayType;
                }
                syncPayeeFieldLockStyle();
            }
            window.syncLrPayeeVisibility = syncPayeeVisibility;
            window.syncLrPayeeFieldLockStyle = syncPayeeFieldLockStyle;

            function updateTruckMetaSummary(ownerName, driverName) {
                const driver = displayOrDash(driverName);
                const hasTruck = !!(truckSelect && truckSelect.value);
                const hasDriver = driver !== '—';
                const transporterTypeSelect = document.getElementById('transporter_type');
                const transporterSelect = document.getElementById('transporter_id');
                const wasTruckSelected = !!(lrPayToField && !lrPayToField.hidden);

                if (lrTruckSummaryDriver) {
                    lrTruckSummaryDriver.textContent = driver;
                }
                if (lrTruckSummaryDriverItem) {
                    lrTruckSummaryDriverItem.hidden = !(hasTruck && hasDriver);
                }
                if (lrTruckSummaryDriverSep) {
                    lrTruckSummaryDriverSep.hidden = !(hasTruck && hasDriver);
                }
                syncTruckSummaryTds();
                syncPayeeVisibility();

                if (!hasTruck && wasTruckSelected) {
                    if (transporterTypeSelect) {
                        transporterTypeSelect.value = '';
                        transporterTypeSelect.dispatchEvent(new Event('change'));
                    }
                    document.querySelectorAll('.lrf-pay-pill').forEach(function (pill) {
                        pill.classList.remove('is-active');
                        if (pill.getAttribute('data-pay-type') === 'owner') {
                            pill.disabled = false;
                        }
                    });
                    if (transporterSelect && window.jQuery && jQuery(transporterSelect).hasClass('select2-hidden-accessible')) {
                        jQuery(transporterSelect).val(null).trigger('change');
                    } else if (transporterSelect) {
                        transporterSelect.value = '';
                    }
                    const tdsDisplay = document.getElementById('tdsRateDisplay');
                    const tdsValue = document.getElementById('tdsPercentageValue');
                    if (tdsDisplay) {
                        tdsDisplay.style.display = 'none';
                    }
                    if (tdsValue) {
                        tdsValue.textContent = '--';
                    }
                    if (typeof window.LREntity !== 'undefined' && typeof window.LREntity.showTdsPercentage === 'function') {
                        window.LREntity.showTdsPercentage(null);
                    }
                    if (lrTruckSummaryDriverItem) {
                        lrTruckSummaryDriverItem.hidden = true;
                    }
                    if (lrTruckSummaryDriverSep) {
                        lrTruckSummaryDriverSep.hidden = true;
                    }
                    syncPayeeVisibility();
                }
            }
            window.syncLrTruckMetaAfterClear = function () {
                updateTruckMetaSummary('', '');
                syncPayeeVisibility();
            };

            function readTruckMetaFromSelection() {
                if (!truckSelect || !truckSelect.value) {
                    updateTruckMetaSummary('', '');
                    return;
                }

                let ownerName = '';
                let driverName = '';

                if (window.jQuery) {
                    const selected = window.jQuery('#truck_id').select2('data');
                    const data = selected && selected[0] ? selected[0] : null;
                    if (data) {
                        ownerName = data.transporter_name || '';
                        driverName = data.driver_name || '';
                        if (!ownerName && data.text) {
                            const match = String(data.text).match(/\(([^)]+)\)$/);
                            ownerName = match ? match[1].trim() : '';
                        }
                        if (!driverName && data.text) {
                            const driverMatch = String(data.text).match(/Driver:\s*([^-—(]+)/i);
                            driverName = driverMatch ? driverMatch[1].trim() : '';
                        }
                    }
                }

                const option = truckSelect.options[truckSelect.selectedIndex];
                if (option) {
                    if (!ownerName) {
                        ownerName = option.getAttribute('data-transporter-name') || '';
                    }
                    if (!driverName) {
                        driverName = option.getAttribute('data-driver-name') || '';
                    }
                }

                updateTruckMetaSummary(ownerName, driverName);

                if (truckSelect.value && (!ownerName || !driverName || ownerName === '—')) {
                    fetch(`/api/trucks/${encodeURIComponent(truckSelect.value)}`)
                        .then((response) => (response.ok ? response.json() : null))
                        .then((data) => {
                            if (!data || String(data.id) !== String(truckSelect.value)) {
                                return;
                            }
                            const fetchedOwner = data.transporter
                                ? (data.transporter.name || data.transporter.short_name || '')
                                : '';
                            const fetchedDriver = data.driver ? (data.driver.name || '') : '';
                            updateTruckMetaSummary(
                                ownerName && ownerName !== '—' ? ownerName : fetchedOwner,
                                driverName && driverName !== '—' ? driverName : fetchedDriver
                            );
                        })
                        .catch(() => {});
                }
            }

            if (truckSelect && window.jQuery) {
                window.jQuery('#truck_id')
                    .on('select2:select.lrTruckMeta select2:clear.lrTruckMeta change.lrTruckMeta', function () {
                        setTimeout(readTruckMetaFromSelection, 0);
                    });
            } else if (truckSelect) {
                truckSelect.addEventListener('change', readTruckMetaFromSelection);
            }

            if (tdsPercentageValue && typeof MutationObserver !== 'undefined') {
                const tdsObserver = new MutationObserver(syncTruckSummaryTds);
                tdsObserver.observe(tdsPercentageValue, {
                    childList: true,
                    characterData: true,
                    subtree: true,
                });
            }

            const transporterSelectForLock = document.getElementById('transporter_id');
            if (transporterSelectForLock && typeof MutationObserver !== 'undefined') {
                const payeeLockObserver = new MutationObserver(syncPayeeFieldLockStyle);
                payeeLockObserver.observe(transporterSelectForLock, {
                    attributes: true,
                    attributeFilter: ['disabled'],
                });
            }
            if (window.jQuery) {
                window.jQuery(document).on(
                    'select2:select.lrPayeeLock select2:clear.lrPayeeLock change.lrPayeeLock',
                    '#transporter_id',
                    function () {
                        setTimeout(syncPayeeFieldLockStyle, 0);
                    }
                );
            }

            readTruckMetaFromSelection();
            syncTruckSummaryTds();
            syncPayeeVisibility();
            syncPayeeFieldLockStyle();

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
                                console.error(
                                    '🚨 Form submission blocked: Consignor and Consignee cannot be the same'
                                );
                            }
                            form.classList.add('was-validated');
                            const firstInvalid = form.querySelector('.is-invalid, :invalid');
                            if (firstInvalid) {
                                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                try {
                                    firstInvalid.focus({ preventScroll: true });
                                } catch (e) {}
                            }
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
            const isFixedAmountCheckbox = document.getElementById('is_fixed_amount');
            const amountHelpText = document.getElementById('amountHelpText');

            function formatLrMoney(value) {
                if (!value || isNaN(value) || value <= 0) {
                    return '—';
                }
                return '₹' + Number(value).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function updateLrSummaryMetrics() {
                const weight = parseFloat(weightInput.value) || 0;
                const frRate = parseFloat(frRateInput.value) || 0;
                const amount = parseFloat(amountInput.value) || 0;
                const weightEl = document.getElementById('lrSummaryWeight');
                const rateEl = document.getElementById('lrSummaryRate');
                const amountEl = document.getElementById('lrSummaryAmount');
                if (weightEl) {
                    weightEl.textContent = weight > 0 ? `${weight} Ton` : '—';
                }
                if (rateEl) {
                    rateEl.textContent = frRate > 0 ? formatLrMoney(frRate) + '/T' : '—';
                }
                if (amountEl) {
                    amountEl.textContent = formatLrMoney(amount);
                }
            }

            function calculateAmount() {
                if (isFixedAmountCheckbox && isFixedAmountCheckbox.checked) {
                    updateLrSummaryMetrics();
                    return; // Do not auto-calculate when using fixed amount
                }
                const weight = parseFloat(weightInput.value) || 0;
                const frRate = parseFloat(frRateInput.value) || 0;

                if (weight > 0 && frRate > 0) {
                    const calculatedAmount = weight * frRate;
                    amountInput.value = calculatedAmount.toFixed(2);

                    amountInput.classList.add('is-auto');
                    amountInput.style.backgroundColor = '';
                    amountInput.title = `Auto-calculated: ${weight} × ${frRate} = ${calculatedAmount.toFixed(2)}`;

                }
                updateLrSummaryMetrics();
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
                }
            }

            // Add blur validation for weight
            weightInput.addEventListener('blur', validateWeight);

            // Allow user to manually edit amount (remove auto-calculated styling when manually edited)
            amountInput.addEventListener('input', function() {
                this.classList.remove('is-auto');
                this.style.backgroundColor = '';
                if (!(isFixedAmountCheckbox && isFixedAmountCheckbox.checked)) {
                    this.title = 'Manually edited amount';
                } else {
                    this.title = 'Fixed amount (manual)';
                }
                updateLrSummaryMetrics();
            });

            if (isFixedAmountCheckbox) {
                // Initial state: when unchecked (auto), amount is read-only
                if (!isFixedAmountCheckbox.checked) {
                    amountInput.readOnly = true;
                } else {
                    amountInput.readOnly = false;
                }

                isFixedAmountCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        amountHelpText.textContent = 'Fixed amount mode';
                        amountInput.classList.remove('is-auto');
                        amountInput.style.backgroundColor = '';
                        amountInput.title = 'Fixed amount (manual)';
                        amountInput.readOnly = false;
                    } else {
                        amountHelpText.textContent = 'Auto: Weight × Rate';
                        amountInput.readOnly = true;
                        calculateAmount();
                    }
                });
            }

            // Calculate amount on page load if values exist
            if (weightInput.value && frRateInput.value) {
                calculateAmount();
            } else {
                updateLrSummaryMetrics();
            }

            const lrClearFormBtn = document.getElementById('lrClearFormBtn');
            if (lrClearFormBtn) {
                lrClearFormBtn.addEventListener('click', function() {
                    const form = document.getElementById('lorryReceiptForm');
                    if (!form) {
                        return;
                    }
                    form.reset();
                    form.classList.remove('was-validated');
                    ['customer_id', 'truck_id', 'transporter_id', 'reference_by', 'consignor_id', 'consignee_id', 'good_type']
                        .forEach(function(id) {
                            const el = document.getElementById(id);
                            if (el && window.jQuery && jQuery(el).hasClass('select2-hidden-accessible')) {
                                jQuery(el).val(null).trigger('change');
                            }
                        });
                    ['source_city', 'source_state', 'destination_city', 'destination_state',
                        'consignor_contact_number', 'consignee_contact_number', 'loading_station'].forEach(function(id) {
                        const el = document.getElementById(id);
                        if (el) {
                            el.value = '';
                        }
                    });
                    if (typeof syncConsignorLabels === 'function') {
                        syncConsignorLabels();
                    }
                    if (typeof syncConsigneeLabels === 'function') {
                        syncConsigneeLabels();
                    }
                    if (typeof updateGoodsRouteFlow === 'function') {
                        updateGoodsRouteFlow();
                    }
                    if (typeof updateTruckMetaSummary === 'function') {
                        updateTruckMetaSummary('', '');
                    }
                    document.querySelectorAll('.lrf-pay-pill').forEach(function(pill) {
                        pill.classList.remove('is-active');
                        if (pill.getAttribute('data-pay-type') === 'owner') {
                            pill.disabled = false;
                        }
                    });
                    const lrFeedback = document.getElementById('lr-number-feedback');
                    if (lrFeedback) {
                        lrFeedback.innerHTML = '';
                    }
                    const tdsDisplay = document.getElementById('tdsRateDisplay');
                    const tdsValue = document.getElementById('tdsPercentageValue');
                    if (tdsDisplay) {
                        tdsDisplay.style.display = 'none';
                    }
                    if (tdsValue) {
                        tdsValue.textContent = '--';
                    }
                    amountInput.style.backgroundColor = '';
                    amountInput.classList.remove('is-auto');
                    amountInput.readOnly = true;
                    if (isFixedAmountCheckbox) {
                        isFixedAmountCheckbox.checked = false;
                    }
                    const warning = document.getElementById('declarationWarning');
                    if (warning) {
                        warning.style.display = 'none';
                    }
                    updateLrSummaryMetrics();
                });
            }

            const lrOptionalToggle = document.getElementById('lrOptionalToggle');
            const lrOptionalBody = document.getElementById('lrOptionalBody');
            const lrOptionalSection = document.getElementById('lrOptionalSection');
            if (lrOptionalToggle && lrOptionalBody && lrOptionalSection) {
                lrOptionalToggle.addEventListener('click', function() {
                    const isOpen = lrOptionalSection.classList.toggle('lrf-section--open');
                    lrOptionalBody.style.display = isOpen ? '' : 'none';
                    lrOptionalToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    const arrow = lrOptionalToggle.querySelector('.lrf-collapse-arrow');
                    if (arrow) {
                        arrow.classList.toggle('lrf-collapse-arrow--open', isOpen);
                    }
                });
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

                lrNumberFeedback.innerHTML = '<span class="lrf-chip lrf-chip--info">Checking…</span>';
                lrNumberInput.classList.remove('is-valid', 'is-invalid');

                fetch('/api/check-lr-number-uniqueness', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            lr_number: lrNumber
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.valid) {
                            lrNumberFeedback.innerHTML =
                                '<span class="lrf-chip lrf-chip--ok">Available</span>';
                            lrNumberInput.classList.remove('is-invalid');
                            lrNumberInput.classList.add('is-valid');
                            lrNumberInput.setCustomValidity('');
                        } else {
                            lrNumberFeedback.innerHTML =
                                '<span class="lrf-chip lrf-chip--bad">Already used</span>';
                            lrNumberInput.classList.remove('is-valid');
                            lrNumberInput.classList.add('is-invalid');
                            lrNumberInput.setCustomValidity(data.message || 'LR number already used');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking LR Number uniqueness:', error);
                        lrNumberFeedback.innerHTML =
                            '<span class="lrf-chip lrf-chip--warn">Unable to check</span>';
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

            // GR Number uniqueness validation
            const grNumberInput = document.getElementById('gr_number');
            const grNumberFeedback = document.getElementById('gr-number-feedback');

        // Add this function for GR number validation
function checkGRNumberUniqueness(grNumber) {
    if (!grNumber) {
        grNumberFeedback.innerHTML = '';
        grNumberInput.classList.remove('is-valid', 'is-invalid');
        return;
    }

    // Show loading state
    grNumberFeedback.innerHTML = '<small class="text-info">🔍 Checking availability...</small>';
    grNumberInput.classList.remove('is-valid', 'is-invalid');

    fetch('/api/check-gr-number-uniqueness', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                gr_number: grNumber
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                grNumberFeedback.innerHTML = '<small class="text-success">✅ ' + data.message + '</small>';
                grNumberInput.classList.remove('is-invalid');
                grNumberInput.classList.add('is-valid');
                grNumberInput.setCustomValidity('');
            } else {
                grNumberFeedback.innerHTML = '<small class="text-danger">❌ ' + data.message + '</small>';
                grNumberInput.classList.remove('is-valid');
                grNumberInput.classList.add('is-invalid');
                grNumberInput.setCustomValidity(data.message);
            }
        })
        .catch(error => {
            console.error('Error checking GR Number uniqueness:', error);
            grNumberFeedback.innerHTML = '<small class="text-warning">⚠️ Unable to check availability</small>';
            grNumberInput.classList.remove('is-valid', 'is-invalid');
        });
}

// Add debounced input listener for GR Number
let grNumberTimeout;
grNumberInput.addEventListener('input', function() {
    const grNumber = this.value.trim();
    clearTimeout(grNumberTimeout);
    grNumberTimeout = setTimeout(() => {
        checkGRNumberUniqueness(grNumber);
    }, 500);
});

// Check on blur as well
grNumberInput.addEventListener('blur', function() {
    const grNumber = this.value.trim();
    if (grNumber) {
        clearTimeout(grNumberTimeout);
        checkGRNumberUniqueness(grNumber);
    }
});

            // No. of Units validation
            const noOfUnitsInput = document.getElementById('no_of_units');

            function validateNoOfUnits() {
                const value = parseFloat(noOfUnitsInput.value);

                if (noOfUnitsInput.value && (isNaN(value))) {
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
                    frRateInput.setCustomValidity('Between 1 and 20,000.');
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

            // Function to format date to DD-MM-YYYY
            function formatDate(dateString) {
                if (!dateString) return 'Not selected';
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return dateString; // Return original if invalid date

                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}-${month}-${year}`;
            }

            // Function to get current date and time in DD-MM-YYYY HH:MM format
            function getCurrentDateTime() {
                const now = new Date();
                const day = String(now.getDate()).padStart(2, '0');
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const year = now.getFullYear();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                return `${day}-${month}-${year} ${hours}:${minutes}`;
            }

            // Function to show confirmation dialog with form details
            function showConfirmationDialog(form) {
                const formData = new FormData(form);

                const customerName = document.querySelector('#customer_id option:checked')?.textContent || '';
                const consignorName = document.querySelector('#consignor_id option:checked')?.textContent || '';
                const consigneeName = document.querySelector('#consignee_id option:checked')?.textContent || '';
                const truckNumber = document.querySelector('#truck_id option:checked')?.textContent || '';
                const transporterName = document.querySelector('#transporter_id option:checked')?.textContent || '';
                const referenceBy = document.querySelector('#reference_by option:checked')?.textContent || '';

                const formattedGrDate = formatDate(formData.get('gr_date'));
                const currentDateTime = getCurrentDateTime();

                window.LREntity.showLorryReceiptConfirm({
                    title: 'Confirm Good Receipt',
                    note: 'Please review the good receipt details before submitting.',
                    details: {
                        lrNumber: formData.get('lr_number'),
                        grDate: formattedGrDate,
                        createdAt: currentDateTime,
                        customer: customerName,
                        invoice: formData.get('invoice_number'),
                        referenceBy: referenceBy,
                        consignor: consignorName,
                        consignee: consigneeName,
                        sourceCity: formData.get('source_city'),
                        destinationCity: formData.get('destination_city'),
                        truck: truckNumber,
                        transporter: transporterName,
                        goodsType: formData.get('good_type'),
                        weight: formData.get('weight'),
                        units: formData.get('no_of_units'),
                        rate: formData.get('fr_rate'),
                        amount: formData.get('amount'),
                    },
                    confirmText: 'Confirm & Submit',
                    cancelText: 'Cancel',
                    denyText: 'Save & Create FR',
                    onConfirm: function () {
                        form.submit();
                    },
                    onDeny: function () {
                        saveAndRedirect();
                    },
                });
            }

            function saveAndRedirect() {
                // Get form
                let form = $('#lorryReceiptForm');

                // Append hidden input to tell backend to redirect to FR
                if ($('#redirect_to_fr').length === 0) {
                    form.append('<input type="hidden" name="redirect_to_fr" id="redirect_to_fr" value="1">');

                }

                form.submit();
            }
        });
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
                        let select = document.getElementById('good_type');
                        let newOption = new Option(data.goodsType.name, data.goodsType.name, true, true);
                        select.add(newOption);
                        select.value = data.goodsType.name;

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
@endsection
