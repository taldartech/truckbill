<template id="lr-item-template">
    <div class="lr-item linked-lr-card">
        <div class="linked-lr-card__header">
            <div class="linked-lr-card__title">
                <span class="linked-lr-index-badge lr-index-badge">1</span>
                <div class="min-w-0">
                    <h6>Lorry Receipt <span class="lr-index">#1</span></h6>
                    <small class="linked-lr-route-preview lr-route-preview">Select consignor and consignee to show route</small>
                </div>
            </div>
            <div class="linked-lr-card__meta">
                <span class="badge bg-success-subtle text-success border border-success-subtle linked-lr-amount-badge lr-amount-badge">₹ 0.00</span>
                <button type="button" class="btn btn-sm btn-outline-danger remove-lr-item" title="Remove this LR">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        <input type="hidden" class="lr-id-input" value="">
        <input type="hidden" class="lane-input" value="Road">

        <div class="linked-lr-card__body">
            <div class="linked-lr-subsection">
                <div class="linked-lr-subsection__label">Documents & Customer</div>
                <div class="lrf-grid lrf-grid--4">
                    <div class="lrf-pf lrf-pf--feedback">
                        <label class="lrf-pf__lbl">LR Number<span class="lrf-pf__req">*</span></label>
                        <input type="text" class="form-control lr-number-input" required placeholder="Unique LR Number">
                        <div class="invalid-feedback">Please enter an LR number.</div>
                        <div class="lr-number-feedback lrf-field-feedback"></div>
                    </div>
                    <div class="lrf-pf">
                        <label class="lrf-pf__lbl">Invoice Number</label>
                        <input type="text" class="form-control invoice-number-input" placeholder="Invoice Number">
                        <div class="invalid-feedback">Please enter a unique Invoice number.</div>
                        <div class="invoice-number-feedback"></div>
                    </div>
                    <div class="lrf-pf">
                        <div class="lrf-pf__lbl-row">
                            <label class="lrf-pf__lbl">Customer<span class="lrf-pf__req">*</span></label>
                            <a href="#" class="lrf-quick-add"
                                data-bs-toggle="modal"
                                data-bs-target="#quickAddCustomerModal">
                                <i class="bi bi-plus-lg"></i> Add
                            </a>
                        </div>
                        <select class="form-select select2 customer-select" required>
                            <option value="">Select Customer</option>
                        </select>
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>
                    <div class="lrf-pf">
                        <label class="lrf-pf__lbl">GR Number</label>
                        <input type="text" class="form-control gr-number-input" placeholder="Optional" autocomplete="off">
                        <div class="gr-number-feedback"></div>
                    </div>
                </div>
            </div>

            <div class="linked-lr-subsection">
                <div class="linked-lr-subsection__label">Parties & Destination</div>
                <div class="linked-lr-party-grid">
                    <div class="linked-lr-party-group">
                        <div class="lrf-grid lrf-grid--2">
                            <div class="lrf-pf">
                                <label class="lrf-pf__lbl">Consignor<span class="lrf-pf__req">*</span></label>
                                <select class="form-select select2 consignor-select" required>
                                    <option value="">Select Consignor</option>
                                </select>
                                <div class="invalid-feedback">Please select a consignor.</div>
                            </div>
                            <div class="lrf-pf">
                                <label class="lrf-pf__lbl">Loading Station</label>
                                <input type="text" class="form-control loading-station-input" autocomplete="off" placeholder="Optional">
                            </div>
                        </div>
                        <div class="linked-lr-party-hints">
                            <small class="linked-lr-party-hint consignor-contact-text"></small>
                            <small class="linked-lr-party-hint consignor-city-text"></small>
                            <small class="linked-lr-party-hint consignor-state-text"></small>
                        </div>
                    </div>
                    <div class="linked-lr-party-group">
                        <div class="lrf-grid lrf-grid--2">
                            <div class="lrf-pf">
                                <label class="lrf-pf__lbl">Consignee<span class="lrf-pf__req">*</span></label>
                                <select class="form-select select2 consignee-select" required>
                                    <option value="">Select Consignee</option>
                                </select>
                                <div class="invalid-feedback">Please select a consignee.</div>
                            </div>
                            <div class="lrf-pf">
                                <label class="lrf-pf__lbl">Unloading Station</label>
                                <input type="text" class="form-control unloading-station-input" autocomplete="off" placeholder="Optional">
                            </div>
                        </div>
                        <div class="linked-lr-party-hints">
                            <small class="linked-lr-party-hint consignee-contact-text"></small>
                            <small class="linked-lr-party-hint consignee-city-text"></small>
                            <small class="linked-lr-party-hint consignee-state-text"></small>
                        </div>
                    </div>
                </div>
                <div class="linked-lr-route-banner lr-route-banner d-none">
                    <span class="route-pill route-source"><i class="bi bi-geo-alt"></i> <span class="route-source-text">—</span></span>
                    <i class="bi bi-arrow-right text-muted"></i>
                    <span class="route-pill route-destination"><i class="bi bi-geo-alt-fill"></i> <span class="route-destination-text">—</span></span>
                </div>
                <input type="hidden" class="form-control consignor-contact">
                <input type="hidden" class="form-control consignee-contact">
                <input type="hidden" class="form-control source-city">
                <input type="hidden" class="form-control source-state">
                <input type="hidden" class="form-control destination-city">
                <input type="hidden" class="form-control destination-state">
                <input type="hidden" class="form-control lane" value="Road">
            </div>

            <div class="linked-lr-subsection">
                <div class="linked-lr-subsection__label">Freight Details</div>
                <div class="lrf-grid lrf-grid--7">
                    <div class="lrf-pf">
                        <div class="lrf-pf__lbl-row">
                            <label class="lrf-pf__lbl">Goods Type<span class="lrf-pf__req">*</span></label>
                            <button type="button" class="lrf-quick-add border-0 bg-transparent p-0" onclick="displayGoodAdd(this)" title="Add New Goods Type">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>
                        <select class="form-select select2 goods-type" required data-placeholder="Search and select goods type...">
                            <option value=""></option>
                            @foreach ($goodsTypes as $goodsType)
                                <option value="{{ $goodsType->name }}">{{ $goodsType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lrf-pf">
                        <label class="lrf-pf__lbl">Units</label>
                        <input type="number" class="form-control units" min="1" max="100000" placeholder="0" title="Maximum 100,000">
                    </div>
                    <div class="lrf-pf">
                        <label class="lrf-pf__lbl">
                            Wt / Unit (t)
                        </label>
                        <input type="number" step="0.001" class="form-control weight-per-unit"
                            min="0.001" max="100" placeholder="0.000"
                            title="Helper only — used to calculate total weight. Not saved."
                            autocomplete="off">
                        <div class="lrf-pf__hint">Units × Wt/Unit → Weight</div>
                    </div>
                    <div class="lrf-pf">
                        <label class="lrf-pf__lbl">Goods Value</label>
                        <input type="number" step="0.01" class="form-control goods-value" min="0" placeholder="0.00">
                    </div>
                    <div class="lrf-pf">
                        <label class="lrf-pf__lbl">Weight (t)<span class="lrf-pf__req">*</span></label>
                        <input type="number" step="0.001" class="form-control weight" min="0.1" max="100" required placeholder="0.000" title="0.1 – 100 tons">
                    </div>
                    <div class="lrf-pf">
                        <label class="lrf-pf__lbl">Rate (₹/t)<span class="lrf-pf__req">*</span></label>
                        <input type="number" step="0.01" class="form-control fr-rate" min="1" max="20000" required placeholder="0.00" title="₹ 1 – 20,000">
                    </div>
                    <div class="lrf-pf">
                        <label class="lrf-pf__lbl">
                            <span>Amount<span class="lrf-pf__req">*</span></span>
                            <span class="lrf-amount-toggle">
                                <input class="form-check-input is-fixed-amount" type="checkbox" value="1">
                                <label class="form-check-label mb-0">Fixed</label>
                            </span>
                        </label>
                        <input type="number" step="0.01" class="form-control amount" required placeholder="0.00">
                        <div class="lrf-pf__hint amount-help-text"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
