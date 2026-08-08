<template id="lr-item-template">
    <div class="lr-item linked-lr-card mb-3">
        <div class="linked-lr-card__header">
            <div class="linked-lr-card__title">
                <span class="linked-lr-index-badge lr-index-badge">1</span>
                <div class="min-w-0">
                    <h6 class="mb-0">Lorry Receipt <span class="lr-index">#1</span></h6>
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
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">LR Number *</label>
                        <input type="text" class="form-control lr-number-input" required placeholder="Unique LR Number">
                        <div class="invalid-feedback">Please enter a unique LR number.</div>
                        <div class="lr-number-feedback mt-1"></div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Invoice Number</label>
                        <input type="text" class="form-control invoice-number-input" placeholder="Enter Invoice Number">
                        <div class="invalid-feedback">Please enter a unique Invoice number.</div>
                        <div class="invoice-number-feedback mt-1"></div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Customer *</label>
                        <select class="form-select select2 customer-select" required>
                            <option value="">Select Customer</option>
                        </select>
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">GR Number <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control gr-number-input" placeholder="Enter GR Number" autocomplete="off">
                        <div class="gr-number-feedback mt-1"></div>
                    </div>
                </div>
            </div>

            <div class="linked-lr-subsection">
                <div class="linked-lr-subsection__label">Parties & Destination</div>
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Consignor *</label>
                        <select class="form-select select2 consignor-select" required>
                            <option value="">Select Consignor</option>
                        </select>
                        <small class="linked-lr-party-hint consignor-contact-text"></small>
                        <small class="linked-lr-party-hint consignor-city-text"></small>
                        <small class="linked-lr-party-hint consignor-state-text"></small>
                        <div class="invalid-feedback">Please select a consignor.</div>
                        <div class="mt-2">
                            <label class="form-label">Loading Station</label>
                            <input type="text" class="form-control loading-station-input" autocomplete="off" placeholder="Source loading point">
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Consignee *</label>
                        <select class="form-select select2 consignee-select" required>
                            <option value="">Select Consignee</option>
                        </select>
                        <small class="linked-lr-party-hint consignee-contact-text"></small>
                        <small class="linked-lr-party-hint consignee-city-text"></small>
                        <small class="linked-lr-party-hint consignee-state-text"></small>
                        <div class="invalid-feedback">Please select a consignee.</div>
                        <div class="mt-2">
                            <label class="form-label">Unloading Station</label>
                            <input type="text" class="form-control unloading-station-input" autocomplete="off" placeholder="Destination unloading point">
                        </div>
                    </div>
                </div>
                <div class="linked-lr-route-banner lr-route-banner mt-3 d-none">
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
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="form-label">Goods Type *</label>
                        <div class="input-group flex-nowrap">
                            <select class="form-select select2 goods-type" required>
                                <option value="">Select Goods Type</option>
                                @foreach ($goodsTypes as $goodsType)
                                    <option value="{{ $goodsType->name }}">{{ $goodsType->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-primary" onclick="displayGoodAdd(this)" title="Add New Goods Type">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label">No. of Units</label>
                        <input type="number" class="form-control units" min="1" max="100000" placeholder="0">
                        <small class="text-muted">Max 100,000</small>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label">Goods Value</label>
                        <input type="number" step="0.01" class="form-control goods-value" min="0" placeholder="0.00">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label">Weight (Tons) *</label>
                        <input type="number" step="0.001" class="form-control weight" min="0.1" max="100" required>
                        <small class="text-muted">0.1 – 100 tons</small>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label">Fr Rate (/ton) *</label>
                        <input type="number" step="0.01" class="form-control fr-rate" min="1" max="20000" required>
                        <small class="text-muted">₹ 1 – 20,000</small>
                    </div>
                    <div class="col-12 col-xl-4">
                        <label class="form-label d-flex justify-content-between align-items-center gap-2">
                            <span>Amount *</span>
                            <span class="form-check form-check-inline mb-0">
                                <input class="form-check-input is-fixed-amount" type="checkbox" value="1">
                                <label class="form-check-label">Fixed amount</label>
                            </span>
                        </label>
                        <input type="number" step="0.01" class="form-control amount" required>
                        <small class="text-muted amount-help-text">Auto: Weight × FR Rate</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
