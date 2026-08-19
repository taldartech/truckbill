@extends('layouts.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/lr-form-redesigned.css') }}?v={{ config('app.ASSET_VERSION') }}">
    @include('lorry_receipts.partials.linked-lr-form-styles')
    <div class="lrf-page app-index-page linked-lr-page">
        <div class="lrf-page__toolbar">
            <div>
                <h1 class="lrf-page__toolbar-title">Edit Linked Good Receipts (Bilty)</h1>
                <p class="lrf-page__toolbar-sub">Update shared transport details and all lorry receipts in this linked group together. <span class="lrf-optional-chip">Linked ID: {{ $linkedLrId }}</span></p>
            </div>
            <div class="lrf-page__toolbar-actions">
                <a href="{{ route('consignees.create') }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                    <i class="bi bi-plus-lg me-1"></i> Create Consignee
                </a>
                <a href="{{ route('lorry_receipts.index') }}{{ \App\Support\ListingQueryString::currentSuffix(\App\Support\ListingQueryString::LORRY_RECEIPT_KEYS) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('lorry_receipts.update', $lorry_receipt->id) }}{{ \App\Support\ListingQueryString::currentSuffix(\App\Support\ListingQueryString::LORRY_RECEIPT_KEYS) }}" id="lorryReceiptForm" class="needs-validation lrf linked-lr-form linked-lr-form--edit"
            novalidate="">
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            @foreach (\App\Support\ListingQueryString::params(request(), \App\Support\ListingQueryString::LORRY_RECEIPT_KEYS) as $listingKey => $listingValue)
                <input type="hidden" name="{{ $listingKey }}" value="{{ $listingValue }}">
            @endforeach

            <div id="declarationWarning" class="alert alert-warning lrf-alert mb-3" style="display: none;">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                    <strong>Declaration missing</strong> for the selected truck owner.
                    <a href="#" id="editTruckOwnerLink" target="_blank" class="alert-link">
                        Edit Truck Owner
                    </a>
                </div>
            </div>

            @php($selectedTruck = $trucks->firstWhere('id', old('truck_id', $sharedLorryReceipt->truck_id)))
            @php($lrTruckSelected = filled(old('truck_id', $sharedLorryReceipt->truck_id)) || ! empty($selectedTruck))
            @php($selectedPayType = old('transporter_type', $sharedLorryReceipt->transporter_type))
            @php($lrPayTypeSelected = in_array($selectedPayType, ['owner', 'agency'], true))

            <div class="lrf-section">
                <div class="lrf-section__head">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num">1</div>
                        <div>
                            <h2 class="lrf-section__title">Shared Transport Details</h2>
                            <p class="lrf-section__desc">Applies to every lorry receipt in this linked group</p>
                        </div>
                    </div>
                </div>
                <div class="lrf-section__body">
                    <div class="lrf-receipt-grid">
                        <div class="lrf-receipt-card">
                            <div class="lrf-receipt-card__title">Document</div>
                            <div class="lrf-receipt-card__row lrf-grid lrf-grid--2">
                                <div class="lrf-pf">
                                    <label for="gr_date" class="lrf-pf__lbl">GR Date<span class="lrf-pf__req">*</span></label>
                                    <input type="date" class="form-control" id="gr_date" name="gr_date" required
                                        value="{{ old('gr_date', $sharedLorryReceipt->gr_date) }}">
                                    @error('gr_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback">Please select a GR date.</div>
                                </div>
                                <div class="lrf-pf">
                                    <label for="reference_by" class="lrf-pf__lbl">Reference By</label>
                                    <div class="lr-payee-select-wrap">
                                        <select class="form-select" id="reference_by" name="reference_by">
                                            <option value="">Select Reference</option>
                                            @php($selectedAgency = $agencies->firstWhere('id', old('reference_by', $sharedLorryReceipt->reference_by)))
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
                                        <button type="button" class="lrf-pay-pill{{ $selectedPayType === 'owner' ? ' is-active' : '' }}"
                                            data-pay-type="owner" id="lrPayPillOwner">Truck Owner</button>
                                        <button type="button" class="lrf-pay-pill{{ $selectedPayType === 'agency' ? ' is-active' : '' }}"
                                            data-pay-type="agency" id="lrPayPillAgency">Agency</button>
                                    </div>
                                    <select class="form-select lrf-pay-type-select" id="transporter_type" name="transporter_type"
                                        @if($lrTruckSelected) required @endif
                                        aria-hidden="true" tabindex="-1">
                                        <option value="">Select Type</option>
                                        <option value="owner" {{ $selectedPayType === 'owner' ? 'selected' : '' }}>Truck Owner</option>
                                        <option value="agency" {{ $selectedPayType === 'agency' ? 'selected' : '' }}>Agency</option>
                                    </select>
                                    <div class="invalid-feedback">Please select who gets paid.</div>
                                </div>
                            </div>

                            <div id="lrPayeeRow" class="lrf-receipt-card__row lrf-grid lrf-grid--2 lrf-payee-row" @if(! $lrPayTypeSelected) hidden @endif>
                                <div class="lrf-pf">
                                    <div class="lrf-pf__lbl-row">
                                        <label for="transporter_id" id="transporterLabel" class="lrf-pf__lbl">
                                            @if($selectedPayType === 'agency')
                                                Agency<span class="lrf-pf__req">*</span>
                                            @elseif($selectedPayType === 'owner')
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
                                            onchange="window.LREntity.showTdsPercentage(this.value)" {{ (!isset($canChangeTransporter) || !$canChangeTransporter) ? 'disabled' : '' }}>
                                            <option value="">Select Payee</option>
                                            @if (! empty($payeeTransporter))
                                                <option value="{{ $payeeTransporter->id }}" selected>
                                                    {{ $payeeTransporter->displayNameWithShortName() }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                    @if (!isset($canChangeTransporter) || !$canChangeTransporter)
                                        <input type="hidden" name="transporter_id" value="{{ old('transporter_id', $sharedLorryReceipt->transporter_id) }}">
                                        <div class="lrf-pf__hint text-danger">Transporter cannot be changed because TDS has already been submitted.</div>
                                    @endif
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

            <div class="lrf-section">
                <div class="lrf-section__head lrf-section__head--green">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num lrf-step-num--green">2</div>
                        <div>
                            <h2 class="lrf-section__title">Linked Lorry Receipts</h2>
                            <p class="lrf-section__desc"><span id="linked-lr-toolbar-count">0 items</span> · shared truck and payee apply to every row</p>
                        </div>
                    </div>
                    <div class="lrf-section__head-right">
                        <button type="button" class="btn btn-success btn-sm" id="add-lr-item">
                            <i class="bi bi-plus-lg me-1"></i> Add LR
                        </button>
                    </div>
                </div>
                <div class="lrf-section__body">
                    <div id="lr-items-container"></div>
                    @include('lorry_receipts.partials.linked-lr-item-template')
                </div>
            </div>

            <div class="linked-lr-summary" id="linked-lr-summary">
                <div class="linked-lr-summary__item">
                    <span class="linked-lr-summary__label">LR Count</span>
                    <span class="linked-lr-summary__value" id="linked-lr-count">0</span>
                </div>
                <div class="linked-lr-summary__item">
                    <span class="linked-lr-summary__label">Total Weight</span>
                    <span class="linked-lr-summary__value" id="linked-lr-total-weight">0.000 t</span>
                </div>
                <div class="linked-lr-summary__item">
                    <span class="linked-lr-summary__label">Total Amount</span>
                    <span class="linked-lr-summary__value" id="linked-lr-total-amount">₹0.00</span>
                </div>
            </div>

            <div class="lrf-actions">
                <a href="{{ route('lorry_receipts.index') }}{{ \App\Support\ListingQueryString::currentSuffix(\App\Support\ListingQueryString::LORRY_RECEIPT_KEYS) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button class="btn btn-success btn-sm" type="submit">
                    <i class="bi bi-floppy-fill me-1"></i> Update Linked Good Receipts
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
    <script src="/js/linked-lr-form-ui.js"></script>
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
            const item = lrItemsContainer.lastElementChild;

            if (item) {
                const setValue = (selector, value) => {
                    const el = item.querySelector(selector);
                    if (el && value !== undefined && value !== null) {
                        el.value = value;
                    }
                };

                setValue('.lr-id-input', prefill.id ?? '');
                setValue('.lr-number-input', prefill.lr_number ?? '');
                setValue('.invoice-number-input', prefill.invoice_number ?? '');
                setValue('.gr-number-input', prefill.gr_number ?? '');
                setValue('.consignor-contact', prefill.consignor_contact_number ?? '');
                setValue('.consignee-contact', prefill.consignee_contact_number ?? '');
                setValue('.source-city', prefill.source_city ?? '');
                setValue('.source-state', prefill.source_state ?? '');
                setValue('.destination-city', prefill.destination_city ?? '');
                setValue('.destination-state', prefill.destination_state ?? '');
                setValue('.loading-station-input', prefill.loading_station ?? '');
                setValue('.unloading-station-input', prefill.unloading_station ?? '');
                setValue('.units', prefill.no_of_units ?? '');
                setValue('.goods-value', prefill.goods_value ?? '');
                setValue('.weight', prefill.weight ?? '');
                setValue('.fr-rate', prefill.fr_rate ?? '');
                setValue('.amount', prefill.amount ?? '');

                const fixedCheckbox = item.querySelector('.is-fixed-amount');
                if (fixedCheckbox) {
                    fixedCheckbox.checked = Boolean(Number(prefill.is_fixed_amount ?? 0));
                }

                const appendSelectOption = (selector, id, text) => {
                    if (!id) {
                        return;
                    }
                    const select = item.querySelector(selector);
                    if (!select) {
                        return;
                    }
                    const option = new Option(text || ('#' + id), id, true, true);
                    select.appendChild(option);
                };

                appendSelectOption('.customer-select', prefill.customer_id, prefill.customer_name);
                appendSelectOption('.consignor-select', prefill.consignor_id, prefill.consignor_name);
                appendSelectOption('.consignee-select', prefill.consignee_id, prefill.consignee_name);

                const goodsSelect = item.querySelector('.goods-type');
                if (goodsSelect && prefill.good_type) {
                    const existing = Array.from(goodsSelect.options).some((opt) => opt.value === prefill.good_type);
                    if (!existing) {
                        goodsSelect.add(new Option(prefill.good_type, prefill.good_type, true, true));
                    } else {
                        goodsSelect.value = prefill.good_type;
                    }
                }
                window.LinkedLrFormUI?.updateRoutePreview(item);
            }

            refreshIndices();
            initSelect2In(lrItemsContainer.lastElementChild);
        }

        function refreshIndices() {
            if (window.LinkedLrFormUI) {
                window.LinkedLrFormUI.refreshIndices();
                return;
            }

            const lrItemsContainer = document.getElementById('lr-items-container');
            if (!lrItemsContainer) {
                return;
            }

            lrItemsContainer.querySelectorAll('.lr-item').forEach((item, idx) => {
                const indexEl = item.querySelector('.lr-index');
                const badgeEl = item.querySelector('.lr-index-badge');
                const label = `#${idx + 1}`;

                if (indexEl) {
                    indexEl.textContent = label;
                }

                if (badgeEl) {
                    badgeEl.textContent = String(idx + 1);
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

                const initialPayeeModeLinked = document.getElementById('transporter_type')?.value === 'owner' ? 'owner' : 'agency';
                rebindLrTransporterSelect2(initialPayeeModeLinked);

                $('#reference_by').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    minimumInputLength: 0,
                    placeholder: 'Search reference agency...',
                    ajax: ajaxAgenciesConfig
                });
                bindTransporterSelect2Title($('#reference_by'));

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
                        mode: 'edit',
                        allowTransporterChanges: {{ $canChangeTransporter ? 'true' : 'false' }},
                        afterTransporterTypeChange: function (selectedType) {
                            const mode = selectedType === 'owner' ? 'owner' : 'agency';
                            if (typeof window.lrCreateRebindTransporterSelect2 === 'function') {
                                window.lrCreateRebindTransporterSelect2(mode);
                            }
                            syncLrPayPillsFromSelect();
                            if (typeof window.syncLrPayeeVisibility === 'function') {
                                window.syncLrPayeeVisibility();
                            }
                        },
                    });
                }

                // Populate linked LR items from server data
                const linkedLrsData = @json($linkedLrsPayload);

                if (linkedLrsData.length > 0) {
                    linkedLrsData.forEach((item) => addLrItem(item));
                } else {
                    addLrItem();
                }

                setTimeout(function() {
                    $('#truck_id, #transporter_id, #reference_by').trigger('change.select2');
                    const payeeId = $('#transporter_id').val();
                    if (payeeId && typeof window.LREntity.showTdsPercentage === 'function') {
                        window.LREntity.showTdsPercentage(payeeId);
                    }
                }, 500);
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

            function findFieldFeedback(inputEl, feedbackClass) {
                return inputEl.parentElement?.querySelector('.' + feedbackClass) ?? null;
            }

            // Fetch consignor details
            function handleConsignorSelection(consignorId, scope) {
                if (!scope) {
                    return;
                }

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
                            window.LinkedLrFormUI?.updateRoutePreview(scope);
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
                if (!scope) {
                    return;
                }

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
                            window.LinkedLrFormUI?.updateRoutePreview(scope);
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
                            'Please enter a valid freight rate between 1 and 20,000.');
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
                if (!feedbackEl) {
                    return;
                }

                if (!lrNumber || lrNumber.length < 2) {
                    feedbackEl.innerHTML = '';
                    inputEl.classList.remove('is-valid', 'is-invalid');
                    return;
                }
                const excludeId = inputEl.closest('.lr-item')?.querySelector('.lr-id-input')?.value || null;
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
                            lr_number: lrNumber,
                            exclude_id: excludeId
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
                    const feedbackEl = findFieldFeedback(inputEl, 'lr-number-feedback');
                    const lrNumber = inputEl.value.trim();
                    clearTimeout(lrNumberTimeout);
                    lrNumberTimeout = setTimeout(() => checkLrUniq(lrNumber, feedbackEl, inputEl), 500);
                }
            });

            lrItemsContainer.addEventListener('blur', function(e) {
                if (e.target.classList.contains('lr-number-input')) {
                    const inputEl = e.target;
                    const feedbackEl = findFieldFeedback(inputEl, 'lr-number-feedback');
                    const lrNumber = inputEl.value.trim();
                    if (lrNumber) {
                        clearTimeout(lrNumberTimeout);
                        checkLrUniq(lrNumber, feedbackEl, inputEl);
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

    const excludeId = inputEl.closest('.lr-item')?.querySelector('.lr-id-input')?.value || null;

    fetch('/api/check-gr-number-uniqueness', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                gr_number: grNumber,
                exclude_id: excludeId
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
                if (!form) {
                    return;
                }
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    const lrItemCount = document.querySelectorAll('#lr-items-container .lr-item').length;
                    if (lrItemCount === 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('No LR Items', 'Please add at least one LR row (use Add LR).', 'warning');
                        } else {
                            alert('Please add at least one LR row.');
                        }
                        return;
                    }

                    if (typeof $ !== 'undefined') {
                        $('#lorryReceiptForm select').each(function () {
                            if ($(this).hasClass('select2-hidden-accessible')) {
                                $(this).trigger('change.select2');
                            }
                        });
                    }

                    if (!form.checkValidity()) {
                        form.classList.add('was-validated');
                        const firstInvalid = form.querySelector(':invalid');
                        if (firstInvalid && typeof firstInvalid.focus === 'function') {
                            firstInvalid.focus();
                            firstInvalid.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        }
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
                    const truckMeta = window.LinkedLrFormUI?.getSharedTransportDisplay?.() || {
                        truckNumber: 'Not selected',
                        driverName: '—',
                    };
                    const transporterName = document.querySelector(
                        '#transporter_id option:checked')?.textContent || 'Not selected';
                    const referenceBy = document.querySelector('#reference_by option:checked')?.textContent?.trim() ||
                        '—';

                    const confirmationHTML = `
            <div class="lrf-receipt-confirm text-start">
                <div class="lrf-receipt-confirm__note">
                    <i class="bi bi-info-circle"></i>
                    <span>Please review linked good receipt details before confirming.</span>
                </div>

                <div class="lrf-receipt-confirm__panel">
                    <div class="lrf-receipt-confirm__head">
                        <i class="bi bi-truck"></i> Shared Transport Info
                    </div>
                    <div class="lrf-receipt-confirm__body">
                        <div class="lrf-receipt-confirm__facts lrf-receipt-confirm__facts--3">
                            <div class="lrf-receipt-confirm__fact">
                                <span class="lrf-receipt-confirm__label">GR Date</span>
                                <div class="lrf-receipt-confirm__value">${formatDate(grDate)}</div>
                            </div>
                            <div class="lrf-receipt-confirm__fact">
                                <span class="lrf-receipt-confirm__label">Truck No</span>
                                <div class="lrf-receipt-confirm__value" title="${truckMeta.truckNumber}">${truckMeta.truckNumber}</div>
                            </div>
                            <div class="lrf-receipt-confirm__fact">
                                <span class="lrf-receipt-confirm__label">Driver</span>
                                <div class="lrf-receipt-confirm__value" title="${truckMeta.driverName}">${truckMeta.driverName}</div>
                            </div>
                            <div class="lrf-receipt-confirm__fact">
                                <span class="lrf-receipt-confirm__label">Transporter</span>
                                <div class="lrf-receipt-confirm__value" title="${transporterName}">${transporterName}</div>
                            </div>
                            <div class="lrf-receipt-confirm__fact">
                                <span class="lrf-receipt-confirm__label">Reference By</span>
                                <div class="lrf-receipt-confirm__value" title="${referenceBy}">${referenceBy}</div>
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
                                    <span class="text-success fw-bold small">${it.amount}</span>
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
                                            <span class="text-muted">Rate:</span> <span class="fw-medium text-dark">${it.fr_rate}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>`;

                    if (typeof Swal !== 'undefined') {
                        window.LREntity?.ensureConfirmModalStyles?.();
                        Swal.fire({
                            title: 'Confirm Linked Good Receipts',
                            html: confirmationHTML,
                            showCancelButton: true,
                            confirmButtonText: 'Confirm Update',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                            allowOutsideClick: false,
                            width: 'auto',
                            customClass: {
                                popup: 'lrf-swal-popup lrf-swal-popup--wide',
                                title: 'lrf-swal-title',
                                htmlContainer: 'lrf-swal-html',
                                actions: 'lrf-swal-actions',
                                confirmButton: 'lrf-swal-confirm',
                                cancelButton: 'lrf-swal-cancel'
                            }
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
                        id: item.querySelector('.lr-id-input')?.value ?? '',
                        lr_number: item.querySelector('.lr-number-input').value,
                        invoice_number: item.querySelector('.invoice-number-input').value,
                        customer_id: item.querySelector('.customer-select').value,
                        gr_number: item.querySelector('.gr-number-input')?.value ?? '',
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
                        loading_station: item.querySelector('.loading-station-input')?.value ?? '',
                        unloading_station: item.querySelector('.unloading-station-input')?.value ?? '',
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
            if (!scope) {
                return;
            }

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
                        window.LinkedLrFormUI?.updateRoutePreview(scope);
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
            if (!scope) {
                return;
            }

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
                        window.LinkedLrFormUI?.updateRoutePreview(scope);
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
