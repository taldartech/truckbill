@extends('layouts.app')

@section('content')
    @php
        $lr = $freightBooking->lr;
        $listingSuffix = \App\Support\ListingQueryString::currentSuffix(\App\Support\ListingQueryString::FREIGHT_BOOKING_KEYS);
        $weight = $lr->weight ?? 0;
        $lrAmount = $lr->amount ?? 0;
        $tdsPct = (float) ($freightBooking->transporter_tds_percentage ?? ($lr?->transporter?->tds_percentage ?? 0));
        $is_delivered = $freightBooking->is_delivered;
        $is_invoice_generated = $lr && $lr->invoice_generated;
        $fbOptionalOpen = filled(old('eway_bill_number', $freightBooking->eway_bill_number))
            || filled(old('eway_bill_expiry', $freightBooking->eway_bill_expiry))
            || filled(old('remark', $freightBooking->remark))
            || $errors->hasAny(['eway_bill_number', 'eway_bill_expiry', 'remark']);
        $customAmountRows = old('custom_amounts');
        if ($customAmountRows === null) {
            $customAmountRows = $freightBooking->customAmounts->map(fn ($row) => [
                'id' => $row->id,
                'description' => $row->description,
                'amount' => $row->amount,
            ])->values()->all();
        }
        $rateReadonly = ! $is_admin || $freightBooking->is_cancelled;
        $amountReadonly = ! $is_admin || $freightBooking->is_cancelled;
        $disabledAttr = $freightBooking->is_cancelled ? 'disabled' : '';
    @endphp

    <style>
        #tr_amount.tr-amount-exceeds-lr {
            background-color: #fff3cd !important;
            border-color: #f59e0b !important;
        }

        .lrf-pf__lbl.tr-amount-exceeds-lr {
            color: #b45309;
        }

        .text-danger {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            word-wrap: break-word;
        }
    </style>

    <link rel="stylesheet" href="{{ asset('css/lr-form-redesigned.css') }}?v={{ config('app.ASSET_VERSION') }}">
    <link rel="stylesheet" href="{{ asset('css/fb-show-redesigned.css') }}?v={{ config('app.ASSET_VERSION') }}">

    <div class="lrf-page app-index-page">
        <div class="lrf-page__toolbar">
            <div>
                <h1 class="lrf-page__toolbar-title">
                    Edit Good Hire (Lorry Hire)
                    @if ($freightBooking->is_cancelled)
                        <span class="badge bg-danger ms-1">Cancelled</span>
                    @endif
                </h1>
                <p class="lrf-page__toolbar-sub">Linked bilty is read-only. Update hire charges and optional details below.</p>
            </div>
            <div class="lrf-page__toolbar-actions">
                @if ($freightBooking->linked_freight_booking_id)
                    <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="modal"
                        data-bs-target="#viewCancelledModal">
                        <i class="bi bi-info-circle me-1"></i> View Cancelled
                    </button>
                @endif
                <a href="{{ route('freight_bookings.index') }}{{ $listingSuffix }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        @if ($freightBooking->is_cancelled)
            <div class="alert alert-danger lrf-alert mb-3" role="alert">
                <i class="bi bi-x-circle-fill mt-1"></i>
                <div>
                    <strong>Cancelled good hire</strong>
                    <div class="small mb-0">This booking cannot be modified. All form fields are disabled.</div>
                </div>
            </div>
        @endif

        {{-- Linked Bilty --}}
        <div class="lrf-section lrf-linked">
            <div class="lrf-linked__head">
                <div class="lrf-linked__head-main">
                    <div class="lrf-linked__eyebrow">Linked Bilty</div>
                    <div class="lrf-linked__title-row">
                        <span class="lrf-linked__lr" title="Good Receipt number">{{ $lr?->lr_number ?? 'N/A' }}</span>
                        @if ($lr && $lr->transporter)
                            <span class="lrf-chip {{ $lr->transporter->is_owner ? 'lrf-chip--info' : 'lrf-chip--ok' }}">
                                {{ $lr->transporter->is_owner ? 'Owner payee' : 'Agency payee' }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="lrf-linked__route-pill" title="Route">
                    <span>{{ $lr?->source_city ?? '—' }}</span>
                    <i class="bi bi-arrow-right"></i>
                    <span>{{ $lr?->destination_city ?? '—' }}</span>
                </div>
            </div>

            <div class="lrf-section__body">
                <div class="lrf-linked__kpis">
                    <div class="lrf-linked__kpi">
                        <span class="lrf-linked__kpi-label">Goods</span>
                        <span class="lrf-linked__kpi-value">{{ $lr?->good_type ?? ($freightBooking->good_type ?? '—') }}</span>
                    </div>
                    <div class="lrf-linked__kpi">
                        <span class="lrf-linked__kpi-label">Weight</span>
                        <span class="lrf-linked__kpi-value">{{ number_format($weight, 3) }} <small>MT</small></span>
                    </div>
                    <div class="lrf-linked__kpi">
                        <span class="lrf-linked__kpi-label">LR Rate</span>
                        <span class="lrf-linked__kpi-value">₹{{ number_format($lr?->fr_rate ?? 0, 2) }}</span>
                    </div>
                    <div class="lrf-linked__kpi lrf-linked__kpi--accent">
                        <span class="lrf-linked__kpi-label">LR Amount</span>
                        <span class="lrf-linked__kpi-value">₹{{ number_format($lrAmount, 2) }}</span>
                    </div>
                </div>

                <div class="lrf-linked__panels">
                    <div class="lrf-linked__panel">
                        <div class="lrf-linked__panel-title">
                            <i class="bi bi-people"></i> Parties
                        </div>
                        <div class="lrf-linked__facts">
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">Customer</span>
                                <span class="lrf-linked__fact-value">{{ $lr && $lr->customer ? $lr->customer->name : 'N/A' }}</span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">Payee</span>
                                <span class="lrf-linked__fact-value">
                                    {{ $freightBooking->transporter_name ?? ($lr && $lr->transporter ? $lr->transporter->name : 'N/A') }}
                                    @if ($lr && $lr->transporter)
                                        <span class="lrf-chip {{ $lr->transporter->is_owner ? 'lrf-chip--info' : 'lrf-chip--ok' }}">
                                            {{ $lr->transporter->is_owner ? 'Owner' : 'Agency' }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">Consignor</span>
                                <span class="lrf-linked__fact-value">{{ $lr && $lr->consignor ? $lr->consignor->name : 'N/A' }}</span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">Consignee</span>
                                <span class="lrf-linked__fact-value">{{ $lr && $lr->consignee ? $lr->consignee->name : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="lrf-linked__panel">
                        <div class="lrf-linked__panel-title">
                            <i class="bi bi-truck"></i> Vehicle &amp; Route
                        </div>
                        <div class="lrf-linked__facts">
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">Truck</span>
                                <span class="lrf-linked__fact-value">{{ $lr && $lr->truck ? $lr->truck->truck_number : 'N/A' }}</span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">TDS %</span>
                                <span class="lrf-linked__fact-value">{{ number_format($tdsPct, 2) }}%</span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">From</span>
                                <span class="lrf-linked__fact-value">
                                    {{ $lr?->source_city ?? 'N/A' }}{{ $lr && ! empty($lr->source_state) ? ', '.$lr->source_state : '' }}
                                </span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">To</span>
                                <span class="lrf-linked__fact-value">
                                    {{ $lr?->destination_city ?? 'N/A' }}{{ $lr && ! empty($lr->destination_state) ? ', '.$lr->destination_state : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form
            action="{{ route('freight_bookings.update', $freightBooking->id) }}{{ $listingSuffix }}"
            method="POST"
            class="needs-validation lrf"
            {{ $freightBooking->is_cancelled ? 'onsubmit="return false;"' : '' }}>
            @csrf
            @method('PUT')
            @foreach (\App\Support\ListingQueryString::params(request(), \App\Support\ListingQueryString::FREIGHT_BOOKING_KEYS) as $listingKey => $listingValue)
                <input type="hidden" name="{{ $listingKey }}" value="{{ $listingValue }}">
            @endforeach

            <input type="hidden" name="advance_amount" id="advance_amount" value="0">
            <input type="hidden" name="memo_id" id="memo_id"
                value="{{ $lr && $lr->memo_id ? $lr->memo_id : '' }}">
            <input type="hidden" id="weight" value="{{ $weight }}">
            <input type="hidden" id="lr_amount" value="{{ $lrAmount }}">
            <input type="hidden" id="transporter_tds_percentage" name="transporter_tds_percentage"
                value="{{ old('transporter_tds_percentage', $freightBooking->transporter_tds_percentage) }}">
            <input type="hidden" id="pan_verified" name="pan_verified"
                value="{{ old('pan_verified', $freightBooking->pan_verified) }}">
            <input type="hidden" id="good_type" name="good_type"
                value="{{ old('good_type', $freightBooking->good_type) }}">
            <input type="hidden" name="lr_id" value="{{ $freightBooking->lr_id }}">

            {{-- Step 1 Hire Charges --}}
            <div class="lrf-section">
                <div class="lrf-section__head lrf-section__head--summary">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num lrf-step-num--summary">1</div>
                        <div>
                            <h2 class="lrf-section__title">Hire Charges</h2>
                            <p class="lrf-section__desc">TR rate, amount, advance and adjustments</p>
                        </div>
                    </div>
                </div>
                <div class="lrf-section__body">
                    <div class="lrf-grid lrf-grid--4">
                        <div class="lrf-pf">
                            <label for="tr_fr_rate" class="lrf-pf__lbl">TR Freight Rate<span class="lrf-pf__req">*</span></label>
                            <input type="number" step="0.01" min="1" max="20000"
                                class="form-control @error('tr_fr_rate') is-invalid @enderror" name="tr_fr_rate"
                                id="tr_fr_rate" value="{{ old('tr_fr_rate', $freightBooking->tr_fr_rate) }}"
                                placeholder="1 – 20,000" required
                                {{ $rateReadonly ? 'readonly style=background-color:#e9ecef;' : '' }}
                                {{ $disabledAttr }}>
                            <div class="lrf-pf__hint">Between 1 and 20,000 / ton</div>
                            @error('tr_fr_rate')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="lrf-pf">
                            <label class="lrf-pf__lbl" for="tr_amount" id="tr_amount_label">
                                <span>TR Amount<span class="lrf-pf__req">*</span></span>
                                <span class="lrf-amount-toggle">
                                    <input class="form-check-input" type="checkbox" value="1" id="is_fixed_amount"
                                        name="is_fixed_amount"
                                        {{ old('is_fixed_amount', $freightBooking->is_fixed_amount ?? false) ? 'checked' : '' }}
                                        {{ ! $is_admin || $freightBooking->is_cancelled ? 'disabled' : '' }}>
                                    <label class="form-check-label mb-0" for="is_fixed_amount">Fixed</label>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0.01"
                                class="form-control @error('tr_amount') is-invalid @enderror" name="tr_amount"
                                id="tr_amount" value="{{ old('tr_amount', $freightBooking->tr_amount) }}"
                                placeholder="Auto-calculated" required
                                {{ $amountReadonly ? 'readonly style=background-color:#e9ecef;' : '' }}
                                {{ $disabledAttr }}>
                            <div id="tr_amount_lr_warning" class="form-text text-warning fw-semibold d-none">
                                <i class="bi bi-exclamation-triangle"></i> TR Amount exceeds LR Amount.
                            </div>
                            <div class="lrf-pf__hint">Auto: Rate × min(load, unload) MT</div>
                            @error('tr_amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="lrf-pf">
                            <label for="advance_amount_main" class="lrf-pf__lbl">Advance Amount</label>
                            <input type="number" step="0.01" class="form-control is-auto" id="advance_amount_main"
                                name="advance_amount"
                                value="{{ old('advance_amount', $freightBooking->advance_amount) }}" readonly>
                            <div class="lrf-pf__hint">Read-only</div>
                        </div>
                        <div class="lrf-pf">
                            <label for="negative_adjusted_amount_display" class="lrf-pf__lbl">Negative Adjusted Amount</label>
                            <input type="number" step="0.01"
                                class="form-control is-auto {{ ($freightBooking->negative_adjusted_amount ?? 0) > 0 ? '' : 'text-muted' }}"
                                id="negative_adjusted_amount_display"
                                value="{{ number_format((float) ($freightBooking->negative_adjusted_amount ?? 0), 2, '.', '') }}"
                                readonly>
                            <input type="hidden" id="negative_adjusted_amount"
                                value="{{ number_format((float) ($freightBooking->negative_adjusted_amount ?? 0), 2, '.', '') }}">
                            <div class="lrf-pf__hint">System-managed</div>
                        </div>
                    </div>

                    @if (! ($canChangeTds ?? true))
                        <div class="alert alert-warning py-2 mb-3 small">
                            <i class="bi bi-lock me-1"></i> TDS already submitted; amount cannot be changed.
                        </div>
                    @else
                        <div class="form-text small text-muted mb-2">
                            Auto-calculated based on transporter TDS percentage
                        </div>
                    @endif

                    <input type="hidden" name="tds_amount" id="tds_amount"
                        value="{{ old('tds_amount', $freightBooking->tds_amount) }}">
                    <input type="hidden" name="balance_amount" id="balance_amount"
                        value="{{ old('balance_amount', $freightBooking->balance_amount) }}">
                    @error('tds_amount')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    @error('balance_amount')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror

                    <div class="lrf-summary-metrics">
                        <div class="lrf-metric">
                            <div class="lrf-metric__icon" style="--ic:#7c3aed;--bg:#faf5ff"><i class="bi bi-currency-rupee"></i></div>
                            <div>
                                <span class="lrf-metric__name">TR Rate</span>
                                <span class="lrf-metric__val" id="fbSummaryRate">₹{{ number_format(old('tr_fr_rate', $freightBooking->tr_fr_rate), 2) }}</span>
                            </div>
                        </div>
                        <div class="lrf-metric">
                            <div class="lrf-metric__icon" style="--ic:#b45309;--bg:#fffbeb"><i class="bi bi-percent"></i></div>
                            <div>
                                <span class="lrf-metric__name">TDS</span>
                                <span class="lrf-metric__val" id="fbSummaryTds">₹{{ number_format(old('tds_amount', $freightBooking->tds_amount), 2) }}</span>
                            </div>
                        </div>
                        <div class="lrf-metric lrf-metric--hero">
                            <div class="lrf-metric__icon" style="--ic:#16a34a;--bg:#dcfce7"><i class="bi bi-wallet2"></i></div>
                            <div>
                                <span class="lrf-metric__name">Balance</span>
                                <span class="lrf-metric__val" id="fbSummaryBalance">₹{{ number_format(old('balance_amount', $freightBooking->balance_amount), 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (! $freightBooking->is_cancelled)
                <div class="lrf-section" id="custom-amounts-section">
                    <div class="lrf-section__head lrf-section__head--plain">
                        <div class="lrf-section__head-left">
                            <div>
                                <h2 class="lrf-section__title">Custom Amounts</h2>
                                <p class="lrf-section__desc">Positive or negative adjustments that affect balance</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-brand-primary btn-sm" id="addCustomAmountRow">
                            <i class="bi bi-plus-lg me-1"></i> Add amount
                        </button>
                    </div>
                    <div class="lrf-section__body lrf-section__body--flush">
                        <div class="lrf-custom-table-wrap">
                            <table class="lrf-custom-table">
                                <thead>
                                    <tr>
                                        <th class="lrf-custom-table__desc">Description</th>
                                        <th class="lrf-custom-table__amt">Amount (+/−)</th>
                                        <th class="lrf-custom-table__act">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="custom-amounts-body">
                                    @forelse ($customAmountRows as $index => $customAmountRow)
                                        <tr class="custom-amount-row">
                                            <td>
                                                @if (! empty($customAmountRow['id']))
                                                    <input type="hidden" name="custom_amounts[{{ $index }}][id]"
                                                        value="{{ $customAmountRow['id'] }}">
                                                @endif
                                                <input type="text" class="form-control custom-amount-description"
                                                    name="custom_amounts[{{ $index }}][description]"
                                                    value="{{ $customAmountRow['description'] ?? '' }}"
                                                    maxlength="255" required placeholder="e.g. Loading charge">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01"
                                                    class="form-control custom-amount-value"
                                                    name="custom_amounts[{{ $index }}][amount]"
                                                    value="{{ $customAmountRow['amount'] ?? '' }}"
                                                    required placeholder="0.00">
                                            </td>
                                            <td class="lrf-custom-table__act-cell">
                                                <button type="button"
                                                    class="btn btn-sm lrf-custom-remove remove-custom-amount-row"
                                                    title="Remove amount">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="lrf-custom-empty{{ count($customAmountRows) ? ' d-none' : '' }}"
                                id="custom-amounts-empty">
                                <i class="bi bi-plus-slash-minus"></i>
                                <span>No custom amounts yet. Click <strong>Add amount</strong> to create one.</span>
                            </div>
                        </div>
                        <div class="lrf-custom-total">
                            <span>Custom amounts total</span>
                            <strong id="custom-amounts-total">₹0.00</strong>
                        </div>
                    </div>
                </div>

                <template id="custom-amount-row-template">
                    <tr class="custom-amount-row">
                        <td>
                            <input type="text" class="form-control custom-amount-description"
                                maxlength="255" required placeholder="e.g. Loading charge">
                        </td>
                        <td>
                            <input type="number" step="0.01"
                                class="form-control custom-amount-value"
                                required placeholder="0.00">
                        </td>
                        <td class="lrf-custom-table__act-cell">
                            <button type="button"
                                class="btn btn-sm lrf-custom-remove remove-custom-amount-row"
                                title="Remove amount">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            @endif

            {{-- Step 2 Optional --}}
           

{{-- Step 2 Optional --}}
<div class="lrf-section" id="fbOptionalSection">
    <div class="lrf-section__head lrf-section__head--plain">
        <div class="lrf-section__head-left">
            <div class="lrf-step-num lrf-step-num--gray">2</div>
            <div>
                <h2 class="lrf-section__title">Optional Details</h2>
                <p class="lrf-section__desc">E-way bill and remarks</p>
            </div>
        </div>
    </div>


                    <div class="lrf-section__body" id="fbOptionalBody">
                    
                    <div class="lrf-grid lrf-grid--4 fb-optional-grid">
                        <div class="lrf-pf fb-eway-col" style="grid-column: span 2;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="lrf-pf__lbl mb-0">E-Way Bills</label>
                                @if(!$is_delivered)
                                <button
                                    type="button"
                                    id="addEwayBill"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <i class="bi bi-plus-lg me-1"></i> Add E-Way Bill
                                </button>
                                @endif
                            </div>
                            <div id="ewayBillsContainer">
        @forelse($freightBooking->ewayBills as $index => $ewayBill)
            <div class="eway-bill-row d-flex gap-2 mb-2">
                <input
                    type="hidden"
                    name="eway_bills[{{ $index }}][id]"
                    value="{{ $ewayBill->id }}"
                >
                <div class="input-group">
                    <input
                        type="text"
                        name="eway_bills[{{ $index }}][eway_bill_number]"
                        class="form-control"
                        value="{{ old('eway_bills.' . $index . '.eway_bill_number', $ewayBill->eway_bill_number) }}"
                        placeholder="E-Way Bill Number"
                        {{ $is_delivered ? 'readonly style=background-color:#e9ecef;' : '' }}
                        {{ $disabledAttr }}
                    >
                    <a 
                        href="https://ewaybillgst.gov.in/Login.aspx"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-outline-success eway-portal-btn"
                        title="Create E-Way Bill"
                    >
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
                <input
                    type="date"
                    name="eway_bills[{{ $index }}][eway_bill_expiry]"
                    class="form-control eway-expiry-input"
                    value="{{ old('eway_bills.' . $index . '.eway_bill_expiry', $ewayBill->eway_bill_expiry?->format('Y-m-d')) }}"
                    {{ $is_delivered ? 'readonly style=background-color:#e9ecef;' : '' }}
                    {{ $disabledAttr }}
                >
                @if(!$is_delivered)
                    <button
                        type="button"
                        class="btn btn-outline-danger remove-eway-bill"
                    >
                        <i class="bi bi-trash"></i> <span>Remove</span>
                    </button>
                @endif
            </div>
        @empty
            <div class="eway-bill-row d-flex gap-2 mb-2">
                <div class="input-group">
                    <input
                        type="text"
                        name="eway_bills[0][eway_bill_number]"
                        class="form-control"
                        placeholder="E-Way Bill Number"
                        {{ $is_delivered ? 'readonly style=background-color:#e9ecef;' : '' }}
                        {{ $disabledAttr }}
                    >
                    <a 
                        href="https://ewaybillgst.gov.in/Login.aspx"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-outline-success eway-portal-btn"
                        title="Create E-Way Bill"
                    >
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
                <input
                    type="date"
                    name="eway_bills[0][eway_bill_expiry]"
                    class="form-control eway-expiry-input"
                    {{ $is_delivered ? 'readonly style=background-color:#e9ecef;' : '' }}
                    {{ $disabledAttr }}
                >
                @if(!$is_delivered)
                    <button
                        type="button"
                        class="btn btn-outline-danger remove-eway-bill"
                    >
                        <i class="bi bi-trash"></i> <span>Remove</span>
                    </button>
                @endif
            </div>
        @endforelse
      </div>
    </div>
                        <div class="lrf-pf" style="grid-column: span 2;">
                            <label for="remark" class="lrf-pf__lbl">Remark</label>
                            <input type="text" name="remark" id="remark"
                                class="form-control @error('remark') is-invalid @enderror"
                                value="{{ old('remark', $freightBooking->remark) }}" placeholder="Optional"
                                {{ $disabledAttr }}>
                            @error('remark')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>


            {{-- Step 3 Status & additional --}}
            <div class="lrf-section">
                <div class="lrf-section__head lrf-section__head--plain">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num lrf-step-num--gray">3</div>
                        <div>
                            <h2 class="lrf-section__title">Status &amp; Additional Charges</h2>
                            <p class="lrf-section__desc">Delivery / cancel flags and post-delivery charges</p>
                        </div>
                    </div>
                </div>
                <div class="lrf-section__body">
                    <div class="d-flex flex-wrap gap-4 mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_delivered" value="{{ $freightBooking->is_delivered }}">
                            <input class="form-check-input" type="checkbox" id="is_delivered" name="is_delivered"
                                value="1"
                                {{ old('is_delivered', $freightBooking->is_delivered) ? 'checked' : '' }}
                                {{ ($freightBooking->is_delivered && $lr) || ($lr && $lr->memo_id) || $freightBooking->is_cancelled ? 'disabled' : '' }}>
                            <label class="form-check-label" for="is_delivered">
                                Delivered
                                @if (($freightBooking->is_delivered && $lr) || ($lr && $lr->memo_id))
                                    <small class="text-muted ms-1">Locked (Memo: {{ $lr->memo_id ?? 'N/A' }})
                                         @if ($lr->memo_created_at)
                                    <br>
                           <span style="display: inline-block; margin-left: 82px;">{{ date('d-m-Y h:i A', strtotime($lr->memo_created_at)) }}</span>
                             @endif
                                    </small>
                                @endif
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_cancelled" value="{{ $freightBooking->is_cancelled }}">
                            <input class="form-check-input" type="checkbox" id="is_cancelled" name="is_cancelled"
                                value="1"
                                {{ old('is_cancelled', $freightBooking->is_cancelled) ? 'checked' : '' }}
                                {{ $freightBooking->is_delivered || $freightBooking->is_cancelled ? 'disabled' : '' }}>
                            <label class="form-check-label" for="is_cancelled">
                                Cancelled
                                @if ($freightBooking->is_delivered)
                                    <small class="text-muted ms-1">Cannot cancel after delivery</small>
                                @else
                                    <small class="text-muted ms-1">Moves LR to Pending</small>
                                @endif
                            </label>
                        </div>
                    </div>

                    <div id="additional-charges-section"
                        style="display: {{ $freightBooking->is_delivered ? 'block' : 'none' }};">
                        <div class="lrf-grid lrf-grid--6">
                            <div class="lrf-pf">
                                <label for="detention" class="lrf-pf__lbl">Detention</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('detention') is-invalid @enderror" name="detention"
                                    id="detention" value="{{ old('detention', $freightBooking->detention) }}"
                                    {{ $disabledAttr }}>
                                @error('detention')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="lrf-pf">
                                <label for="deviation" class="lrf-pf__lbl">Deviation</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('deviation') is-invalid @enderror" name="deviation"
                                    id="deviation" value="{{ old('deviation', $freightBooking->deviation) }}"
                                    {{ $disabledAttr }}>
                                @error('deviation')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="lrf-pf">
                                <label for="misc_expense" class="lrf-pf__lbl">Misc. Expense</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('misc_expense') is-invalid @enderror"
                                    name="misc_expense" id="misc_expense"
                                    value="{{ old('misc_expense', $freightBooking->misc_expense) }}"
                                    {{ $disabledAttr }}>
                                @error('misc_expense')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="lrf-pf">
                                <label for="unloading_weight" class="lrf-pf__lbl">Unloading Weight<span class="lrf-pf__req">*</span></label>
                                <input type="number" step="0.001" min="0"
                                    class="form-control @error('unloading_weight') is-invalid @enderror"
                                    name="unloading_weight" id="unloading_weight"
                                    value="{{ old('unloading_weight', $lr ? ($lr->unloading_weight ?? $lr->weight ?? 0) : 0) }}"
                                    required
                                    {{ $is_invoice_generated ? 'readonly style=background-color:#e9ecef;' : '' }}
                                    {{ $disabledAttr }}>
                                @error('unloading_weight')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="lrf-pf">
                                <label for="damage_amount" class="lrf-pf__lbl">Damage Amount</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('damage_amount') is-invalid @enderror"
                                    name="damage_amount" id="damage_amount"
                                    value="{{ old('damage_amount', $freightBooking->damage_amount) }}"
                                    {{ $disabledAttr }}>
                                @error('damage_amount')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="lrf-pf">
                                <label for="shortage_amount" class="lrf-pf__lbl">Shortage Amount</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('shortage_amount') is-invalid @enderror"
                                    name="shortage_amount" id="shortage_amount"
                                    value="{{ old('shortage_amount', $freightBooking->shortage_amount) }}"
                                    {{ $disabledAttr }}>
                                @error('shortage_amount')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lrf-actions">
                <a href="{{ route('freight_bookings.index') }}{{ $listingSuffix }}"
                    class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
                @if ($freightBooking->is_cancelled)
                    <button class="btn btn-secondary btn-sm" type="button" disabled>
                        <i class="bi bi-x-circle me-1"></i> Cannot Update — Cancelled
                    </button>
                @else
                    <button class="btn btn-success btn-sm" type="button" id="showConfirmationBtn">
                        <i class="bi bi-check-lg me-1"></i> Update Good Hire
                    </button>
                @endif
            </div>
        </form>
    </div>





    <!-- Confirmation Modal for Good Hire (Lorry Hire) Update -->
    <div class="modal fade fb-pay-modal" id="updateConfirmationModal" tabindex="-1"
        aria-labelledby="updateConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateConfirmationModalLabel">
                        <i class="bi bi-check2-circle"></i>
                        Confirm Good Hire Update
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fb-confirm-note">
                        <i class="bi bi-info-circle"></i>
                        Please review the booking details below before confirming your update.
                    </div>

                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-receipt"></i> Good Receipt (Bilty)
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-facts fb-pay-facts--3">
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">LR Number</span>
                                    <div class="fb-pay-fact__value" id="confirm-lr-number"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Transporter</span>
                                    <div class="fb-pay-fact__value" id="confirm-transporter"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Truck</span>
                                    <div class="fb-pay-fact__value" id="confirm-truck"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel fb-pay-panel--alloc">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-currency-rupee"></i> Financial Details
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-facts fb-pay-facts--4">
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">TR Freight Rate</span>
                                    <div class="fb-pay-fact__value" id="confirm-tr-rate"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">TR Amount</span>
                                    <div class="fb-pay-fact__value fb-pay-fact__value--accent" id="confirm-tr-amount"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">TDS Amount</span>
                                    <div class="fb-pay-fact__value" id="confirm-tds-amount"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Balance Amount</span>
                                    <div class="fb-pay-fact__value fb-pay-fact__value--accent" id="confirm-balance-amount"></div>
                                </div>
                                <div class="fb-pay-fact" id="confirm-negative-adjusted-wrap" style="display: none;">
                                    <span class="fb-pay-fact__label">Negative Adjusted</span>
                                    <div class="fb-pay-fact__value fb-pay-fact__value--warn" id="confirm-negative-adjusted"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel" id="confirm-custom-amounts-row" style="display: none;">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-list-ul"></i> Custom Amounts
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-facts fb-pay-facts--3" id="confirm-custom-amounts-list"></div>
                        </div>
                        <div class="fb-pay-total">
                            <span>Custom amounts total</span>
                            <strong id="confirm-custom-amounts-total"></strong>
                        </div>
                    </div>

                    <div class="fb-pay-panel" id="confirm-delivery-status-row">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-clipboard-check"></i> Status
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-facts fb-pay-facts--2">
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Delivery Status</span>
                                    <div class="fb-pay-fact__value" id="confirm-delivery-status"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Cancellation Status</span>
                                    <div class="fb-pay-fact__value" id="confirm-cancellation-status"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel" id="confirm-additional-charges-row" style="display: none;">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-plus-circle"></i> Additional Charges
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-facts fb-pay-facts--5">
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Detention</span>
                                    <div class="fb-pay-fact__value" id="confirm-detention"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Deviation</span>
                                    <div class="fb-pay-fact__value" id="confirm-deviation"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Misc Expense</span>
                                    <div class="fb-pay-fact__value" id="confirm-misc-expense"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Damage Amount</span>
                                    <div class="fb-pay-fact__value" id="confirm-damage-amount"></div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Shortage Amount</span>
                                    <div class="fb-pay-fact__value" id="confirm-shortage-amount"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel" id="confirm-remark-row" style="display: none;">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-chat-left-text"></i> Remarks
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-fact__value" id="confirm-remark"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-brand-primary" id="confirmUpdateBtn">
                        <i class="bi bi-check-lg me-1"></i> Confirm Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Confirmation Modal -->
    <div class="modal fade fb-pay-modal" id="askMemoModal" tabindex="-1" aria-labelledby="askMemoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="askMemoModalLabel">
                        <i class="bi bi-journal-text"></i>
                        Memo ID / POD Confirmation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fb-confirm-note mb-0">
                        <i class="bi bi-info-circle"></i>
                        Do you want to enter a Memo ID / POD for this delivery?
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-danger" id="noMemoBtn">No, Continue Without Memo / POD</button>
                    <button type="button" class="btn btn-brand-primary" id="yesMemoBtn">Yes, Enter Memo ID / POD</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Memo ID Entry -->
    <div class="modal fade fb-pay-modal" id="memoIdModal" tabindex="-1" aria-labelledby="memoIdModalLabel"
        aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memoIdModalLabel">
                        <i class="bi bi-pencil-square"></i>
                        Enter Memo ID / POD for Delivery
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fb-confirm-note">
                        <i class="bi bi-info-circle"></i>
                        A Memo ID / POD is required to mark this good hire as delivered.
                    </div>
                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-hash"></i> Memo / POD Details
                        </div>
                        <div class="fb-pay-panel__body">
                            <form id="memoIdForm">
                                <div class="fb-pay-fields" style="grid-template-columns: 1fr;">
                                    <div>
                                        <label for="deliveryMemoId" class="form-label">Memo ID / POD *</label>
                                        <input type="text" class="form-control" id="deliveryMemoId" name="memo_id"
                                            required placeholder="Enter memo ID / POD" pattern="[A-Za-z0-9\-\/]+">
                                        <div class="lrf-pf__hint mt-1">Saved to the good receipt (bilty) record.</div>
                                    </div>
                                    <div>
                                        <label for="deliveryMemoCreatedAt" class="form-label">Memo Date &amp; Time</label>
                                        <input type="datetime-local" class="form-control" id="deliveryMemoCreatedAt"
                                            name="memo_created_at" max="{{ now()->format('Y-m-d\TH:i') }}">
                                        <div class="lrf-pf__hint mt-1">Optional. Leave blank to use the current date &amp; time.</div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div id="memoModalError" style="display: none;">
                        <div class="alert alert-danger mb-0" id="memoErrorMessage"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-brand-primary" onclick="saveMemoId()" id="saveMemoBtn">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                            style="display: none;"></span>
                        Save Memo ID / POD
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Custom Amount Confirmation -->
    <div class="modal fade fb-pay-modal" id="deleteCustomAmountModal" tabindex="-1"
        aria-labelledby="deleteCustomAmountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCustomAmountModalLabel">
                        <i class="bi bi-trash"></i>
                        Remove Custom Amount
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fb-confirm-note">
                        <i class="bi bi-exclamation-triangle"></i>
                        This adjustment will be removed from the balance calculation. You can still cancel without saving.
                    </div>
                    <div class="fb-pay-panel fb-pay-panel--alloc">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-plus-slash-minus"></i> Amount to remove
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-facts fb-pay-facts--2">
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Description</span>
                                    <div class="fb-pay-fact__value" id="deleteCustomAmountDesc">—</div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Amount</span>
                                    <div class="fb-pay-fact__value fb-pay-fact__value--warn" id="deleteCustomAmountValue">₹0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Keep it
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="confirmDeleteCustomAmountBtn">
                        <i class="bi bi-trash me-1"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade fb-pay-modal" id="viewCancelledModal" tabindex="-1" aria-labelledby="viewCancelledModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="viewCancelledModalLabel">
                        <i class="fas fa-info-circle me-2"></i> Cancelled Good Hire (Lorry Hire) Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cancelledDetailsContent">
                        @php
                            $cancelledfr = App\Models\FreightBooking::where(
                                'id',
                                $freightBooking->linked_freight_booking_id,
                            )->first();
                        @endphp
                        @if ($cancelledfr)
                            <p><strong>TR Freight Rate:</strong> {{ number_format($cancelledfr->tr_fr_rate, 2) }}</p>
                            <p><strong>TR Amount:</strong> {{ number_format($cancelledfr->tr_amount, 2) }}</p>
                            <p><strong>TDS Amount:</strong> {{ number_format($cancelledfr->tds_amount, 2) }}</p>
                            <p><strong>Balance Amount:</strong> {{ number_format($cancelledfr->balance_amount, 2) }}</p>
                            <p><strong>Advance Amount:</strong> {{ number_format($cancelledfr->advance_amount, 2) }}</p>
                            <p><strong>E-Way Bill Number:</strong> {{ $cancelledfr->eway_bill_number ?? 'N/A' }}</p>
                            <p><strong>E-Way Bill Expiry Date:</strong>
                              {{ $cancelledfr->eway_bill_expiry ? \Carbon\Carbon::parse($cancelledfr->eway_bill_expiry)->format('d M Y') : 'N/A' }}
                            </p>
                            <p><strong>Remark:</strong> {{ $cancelledfr->remark ?? 'N/A' }}</p>
                            <p><strong>Cancelled On:</strong> {{ $cancelledfr->updated_at->format('d M Y, h:i A') }}</p>
                        @else
                            <p>No cancelled good hire (lorry hire) details found.</p>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Good Hire Confirmation Modal -->
    <div class="modal fade fb-pay-modal" id="cancelGoodHireModal" tabindex="-1"
        aria-labelledby="cancelGoodHireModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelGoodHireModalLabel">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                        Confirm Cancellation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Are you sure you want to cancel this good hire (lorry hire)?</p>

                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <div class="fw-bold mb-1">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            WARNING: This action will:
                        </div>
                        <ul class="mb-0 ps-3">
                            <li>Mark the good hire (lorry hire) as cancelled</li>
                            <li>Move the related Good Receipt (Bilty) status back to "Pending"</li>
                            <li>This change cannot be undone easily</li>
                        </ul>
                    </div>

                    <div class="mt-3">
                        <label for="cancelGoodHireRemark" class="form-label">
                            <strong>Remark <span class="text-danger">*</span></strong>
                        </label>
                        <textarea
                            class="form-control"
                            id="cancelGoodHireRemark"
                            rows="3"
                            placeholder="Enter reason for cancellation"></textarea>
                        <div id="cancelGoodHireRemarkError" class="text-danger small mt-1 d-none">
                            Cancellation remark is required.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="cancelGoodHireDismissBtn">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmCancelGoodHireBtn">
                        <i class="bi bi-x-circle me-1"></i> Confirm Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let deliveredSwitch = document.getElementById("is_delivered");

            deliveredSwitch.addEventListener("change", function() {
                if (this.checked) {
                    // Reset it temporarily
                    this.checked = false;

                    // Show confirmation modal instead
                    new bootstrap.Modal(document.getElementById('askMemoModal')).show();
                }
            });

            // If YES → Show Memo modal
            document.getElementById("yesMemoBtn").addEventListener("click", function() {
                var askModal = bootstrap.Modal.getInstance(document.getElementById('askMemoModal'));
                askModal.hide();
                new bootstrap.Modal(document.getElementById('memoIdModal')).show();
            });

            // If NO → Ask extra confirmation
            document.getElementById("noMemoBtn").addEventListener("click", function() {
                var askModal = bootstrap.Modal.getInstance(document.getElementById('askMemoModal'));
                askModal.hide();

                // Show extra confirmation alert
                if (confirm("⚠️ This delivery status cannot be changed again. Do you want to continue?")) {
                    deliveredSwitch.checked = true; // now lock delivered
                    document.getElementById("additional-charges-section").style.display = "block";

                } else {
                    deliveredSwitch.checked = false; // keep it unchecked
                    document.getElementById("additional-charges-section").style.display = "none";
                }
            });
        });

        // Function to save memo ID for delivery
        window.saveMemoId = function() {
            const memoId = document.getElementById('deliveryMemoId').value.trim();
            const memoCreatedAt = document.getElementById('deliveryMemoCreatedAt').value;
            const saveBtn = document.getElementById('saveMemoBtn');
            const spinner = saveBtn.querySelector('.spinner-border');
            const errorDiv = document.getElementById('memoModalError');
            const errorMessage = document.getElementById('memoErrorMessage');

            if (!memoId) {
                errorMessage.textContent = 'Please enter a Memo ID';
                errorDiv.style.display = 'block';
                return;
            }

            // Show loading state
            saveBtn.disabled = true;
            spinner.style.display = 'inline-block';
            errorDiv.style.display = 'none';

            // Create form data
            const formData = new FormData();
            formData.append('memo_id', memoId);
            if (memoCreatedAt) {
                formData.append('memo_created_at', memoCreatedAt);
            }
            formData.append('lr_ids[]', {{ $freightBooking->lr_id }});

            // Send AJAX request to update memo ID
            fetch('/lorry_receipts/bulk-payment', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal and enable delivered toggle
                        const modal = bootstrap.Modal.getInstance(document.getElementById('memoIdModal'));
                        modal.hide();

                        // Show additional charges sections
                        document.getElementById('additional-charges-section').style.display = 'block';

                        // Show success message
                        alert('Memo ID / POD saved successfully. Freight booking marked as delivered.');

                        // Optionally reload to reflect changes
                        location.reload();
                    } else {
                        errorMessage.textContent = data.message || 'Failed to save memo ID';
                        errorDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorMessage.textContent = 'An error occurred while saving memo ID';
                    errorDiv.style.display = 'block';
                })
                .finally(() => {
                    // Reset loading state
                    saveBtn.disabled = false;
                    spinner.style.display = 'none';
                });
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Handle the confirmation modal for form submission
            const showConfirmationBtn = document.getElementById('showConfirmationBtn');
            const confirmUpdateBtn = document.getElementById('confirmUpdateBtn');
            const editForm = document.querySelector('form[action*="freight_bookings"]');

            if (showConfirmationBtn && confirmUpdateBtn && editForm) {
                showConfirmationBtn.addEventListener('click', function() {
                    // Populate confirmation modal with current form values
                    document.getElementById('confirm-lr-number').textContent =
                        '{{ $freightBooking->lr ? $freightBooking->lr->lr_number : 'N/A' }}';
                    document.getElementById('confirm-transporter').textContent =
                        '{{ $freightBooking->transporter_name ?? 'N/A' }}';
                    document.getElementById('confirm-truck').textContent =
                        '{{ $freightBooking->lr && $freightBooking->lr->truck ? $freightBooking->lr->truck->truck_number : 'N/A' }}';

                    // Financial details
                    const trRate = document.getElementById('tr_fr_rate').value;
                    const trAmount = document.getElementById('tr_amount').value;
                    const tdsAmount = document.getElementById('tds_amount').value;
                    const balanceAmount = document.getElementById('balance_amount').value;

                    document.getElementById('confirm-tr-rate').textContent = '₹' + parseFloat(trRate)
                        .toFixed(2) + ' / ton';
                    document.getElementById('confirm-tr-amount').textContent = '₹' + parseFloat(trAmount)
                        .toFixed(2);
                    document.getElementById('confirm-tds-amount').textContent = '₹' + parseFloat(tdsAmount)
                        .toFixed(2);
                    document.getElementById('confirm-balance-amount').textContent = '₹' + parseFloat(
                        balanceAmount).toFixed(2);

                    const negativeAdjustedAmount = parseFloat(document.getElementById('negative_adjusted_amount')?.value || 0) || 0;
                    const negativeAdjustedWrap = document.getElementById('confirm-negative-adjusted-wrap');
                    if (negativeAdjustedWrap) {
                        negativeAdjustedWrap.style.display = negativeAdjustedAmount > 0 ? '' : 'none';
                        document.getElementById('confirm-negative-adjusted').textContent = '₹' + negativeAdjustedAmount.toFixed(2);
                    }

                    const customAmountRows = document.querySelectorAll('#custom-amounts-body .custom-amount-row');
                    const customAmountsRow = document.getElementById('confirm-custom-amounts-row');
                    const customAmountsList = document.getElementById('confirm-custom-amounts-list');
                    if (customAmountsRow && customAmountsList) {
                        if (customAmountRows.length > 0) {
                            customAmountsRow.style.display = 'block';
                            customAmountsList.innerHTML = '';
                            let customTotal = 0;

                            customAmountRows.forEach(function(row) {
                                const description = row.querySelector('[name*="[description]"], .custom-amount-description')?.value || '';
                                const amount = parseFloat(row.querySelector('.custom-amount-value')?.value || 0) || 0;
                                customTotal += amount;

                                const item = document.createElement('div');
                                item.className = 'fb-pay-fact';
                                item.innerHTML = '<span class="fb-pay-fact__label"></span><div class="fb-pay-fact__value"></div>';
                                item.querySelector('.fb-pay-fact__label').textContent = description || 'Custom amount';
                                item.querySelector('.fb-pay-fact__value').textContent = '₹' + amount.toFixed(2);
                                customAmountsList.appendChild(item);
                            });

                            document.getElementById('confirm-custom-amounts-total').textContent = '₹' + customTotal.toFixed(2);
                        } else {
                            customAmountsRow.style.display = 'none';
                            customAmountsList.innerHTML = '';
                        }
                    }

                    // Status
                    const isDelivered = document.getElementById('is_delivered').checked;
                    const isCancelled = document.getElementById('is_cancelled').checked;

                    document.getElementById('confirm-delivery-status').innerHTML = isDelivered ?
                        '<span class="badge bg-success">Delivered</span>' :
                        '<span class="badge bg-warning text-dark">Not Delivered</span>';

                    document.getElementById('confirm-cancellation-status').innerHTML = isCancelled ?
                        '<span class="badge bg-danger">Cancelled</span>' :
                        '<span class="badge bg-secondary">Not Cancelled</span>';

                    // Additional charges (only show if delivered)
                    const additionalChargesRow = document.getElementById('confirm-additional-charges-row');
                    if (isDelivered) {
                        additionalChargesRow.style.display = 'block';

                        const detention = document.getElementById('detention').value || '0.00';
                        const deviation = document.getElementById('deviation').value || '0.00';
                        const miscExpense = document.getElementById('misc_expense').value || '0.00';
                        const damageAmount = document.getElementById('damage_amount').value || '0.00';
                        const shortageAmount = document.getElementById('shortage_amount').value || '0.00';

                        document.getElementById('confirm-detention').textContent = '₹' + parseFloat(
                            detention).toFixed(2);
                        document.getElementById('confirm-deviation').textContent = '₹' + parseFloat(
                            deviation).toFixed(2);
                        document.getElementById('confirm-misc-expense').textContent = '₹' + parseFloat(
                            miscExpense).toFixed(2);
                        document.getElementById('confirm-damage-amount').textContent = '₹' + parseFloat(
                            damageAmount).toFixed(2);
                        document.getElementById('confirm-shortage-amount').textContent = '₹' + parseFloat(
                            shortageAmount).toFixed(2);
                    } else {
                        additionalChargesRow.style.display = 'none';
                    }

                    // Remarks
                    const remark = document.getElementById('remark').value;
                    const remarkRow = document.getElementById('confirm-remark-row');
                    const remarkElement = document.getElementById('confirm-remark');

                    if (remark && remark.trim() !== '') {
                        remarkRow.style.display = 'block';
                        remarkElement.textContent = remark;
                    } else {
                        remarkRow.style.display = 'none';
                        remarkElement.textContent = 'No remarks provided.';
                    }

                    // Show the confirmation modal
                    const modal = new bootstrap.Modal(document.getElementById('updateConfirmationModal'));
                    modal.show();
                });

                // Handle form submission after confirmation
                confirmUpdateBtn.addEventListener('click', function() {
                    // Submit the form
                    editForm.submit();
                });
            }

            // Get all required elements with null checks
            const weightElement = document.getElementById('weight');
            const weight = weightElement ? parseFloat(weightElement.value || 0) : 0;
            const unloadingWeightInput = document.getElementById('unloading_weight');
            const unloadingWeight = unloadingWeightInput ? parseFloat(unloadingWeightInput.value || 0) : weight;
            const rateInput = document.getElementById('tr_fr_rate');
            const amountInput = document.getElementById('tr_amount');
            const trAmountInput = document.getElementById('tr_amount');
            const isFixedAmountInput = document.getElementById('is_fixed_amount');
            const balanceAmountInput = document.getElementById('balance_amount');
            const tdsAmountInput = document.getElementById('tds_amount');

            // Get TDS percentage from freight booking's transporter_tds_percentage field
            const tdsPercentageElement = document.getElementById('transporter_tds_percentage');
            const tdsPercentage = tdsPercentageElement ? parseFloat(tdsPercentageElement.value || 0) : 0;

            // Track previous values for TDS calculation
            let previousLoadingWeight = parseFloat(weightElement ? weightElement.value : 0) || 0;
            let previousRate = parseFloat(rateInput ? rateInput.value : 0) || 0;
            let previousTdsAmount = parseFloat(tdsAmountInput ? tdsAmountInput.value : 0) || 0;

            // Check if freight booking is delivered
            const isDelivered = @json((bool) ($is_delivered ?? false));
            // TDS already submitted to government cannot be changed
            const canChangeTds = @json($canChangeTds ?? true);

            function roundUpTdsAmount(amount) {
                return Math.ceil(amount);
            }

            function updateTrAmountHighlight() {
                const lrAmountElement = document.getElementById('lr_amount');
                const trAmountField = document.getElementById('tr_amount');
                const trAmountLabel = document.getElementById('tr_amount_label');
                const warning = document.getElementById('tr_amount_lr_warning');

                if (!lrAmountElement || !trAmountField) {
                    return;
                }

                const lrAmount = parseFloat(lrAmountElement.value) || 0;
                const trAmount = parseFloat(trAmountField.value) || 0;
                const exceeds = lrAmount > 0 && trAmount > lrAmount;

                trAmountField.classList.toggle('tr-amount-exceeds-lr', exceeds);
                trAmountLabel?.classList.toggle('tr-amount-exceeds-lr', exceeds);
                warning?.classList.toggle('d-none', !exceeds);
            }

            // Function to sum custom amount row values
            function getCustomAmountsTotal() {
                let total = 0;
                document.querySelectorAll('#custom-amounts-body .custom-amount-value').forEach(function(input) {
                    total += parseFloat(input.value) || 0;
                });

                const totalDisplay = document.getElementById('custom-amounts-total');
                if (totalDisplay) {
                    totalDisplay.textContent = '₹' + total.toFixed(2);
                }

                return total;
            }

            function syncCustomAmountsEmptyState() {
                const body = document.getElementById('custom-amounts-body');
                const empty = document.getElementById('custom-amounts-empty');
                if (!body || !empty) {
                    return;
                }

                const hasRows = body.querySelectorAll('.custom-amount-row').length > 0;
                empty.classList.toggle('d-none', hasRows);
            }

            function reindexCustomAmountRows() {
                document.querySelectorAll('#custom-amounts-body .custom-amount-row').forEach(function(row, index) {
                    const idInput = row.querySelector('input[name*="[id]"]');
                    const descInput = row.querySelector('[name*="[description]"], .custom-amount-description');
                    const amountInput = row.querySelector('.custom-amount-value');

                    if (descInput) {
                        descInput.name = 'custom_amounts[' + index + '][description]';
                    }
                    if (amountInput) {
                        amountInput.name = 'custom_amounts[' + index + '][amount]';
                    }
                    if (idInput && idInput.name.includes('[id]')) {
                        idInput.name = 'custom_amounts[' + index + '][id]';
                    }
                });
            }

            function initCustomAmountsRepeater() {
                const addBtn = document.getElementById('addCustomAmountRow');
                const body = document.getElementById('custom-amounts-body');
                const template = document.getElementById('custom-amount-row-template');
                const deleteModalEl = document.getElementById('deleteCustomAmountModal');
                const confirmDeleteBtn = document.getElementById('confirmDeleteCustomAmountBtn');
                let pendingDeleteRow = null;

                if (!addBtn || !body || !template) {
                    return;
                }

                const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

                addBtn.addEventListener('click', function() {
                    const clone = template.content.cloneNode(true);
                    body.appendChild(clone);
                    reindexCustomAmountRows();
                    syncCustomAmountsEmptyState();
                    getCustomAmountsTotal();
                    calculateTdsAndBalance();
                });

                body.addEventListener('click', function(event) {
                    const removeBtn = event.target.closest('.remove-custom-amount-row');
                    if (!removeBtn) {
                        return;
                    }

                    const row = removeBtn.closest('.custom-amount-row');
                    if (!row) {
                        return;
                    }

                    if (!deleteModal) {
                        row.remove();
                        reindexCustomAmountRows();
                        syncCustomAmountsEmptyState();
                        getCustomAmountsTotal();
                        calculateTdsAndBalance();
                        return;
                    }

                    pendingDeleteRow = row;
                    const description = row.querySelector('[name*="[description]"], .custom-amount-description')?.value?.trim() || 'Untitled';
                    const amount = parseFloat(row.querySelector('.custom-amount-value')?.value || 0) || 0;
                    document.getElementById('deleteCustomAmountDesc').textContent = description;
                    document.getElementById('deleteCustomAmountValue').textContent = '₹' + amount.toFixed(2);
                    deleteModal.show();
                });

                if (confirmDeleteBtn) {
                    confirmDeleteBtn.addEventListener('click', function() {
                        if (pendingDeleteRow) {
                            pendingDeleteRow.remove();
                            pendingDeleteRow = null;
                            reindexCustomAmountRows();
                            syncCustomAmountsEmptyState();
                            getCustomAmountsTotal();
                            calculateTdsAndBalance();
                        }
                        deleteModal?.hide();
                    });
                }

                if (deleteModalEl) {
                    deleteModalEl.addEventListener('hidden.bs.modal', function() {
                        pendingDeleteRow = null;
                    });
                }

                body.addEventListener('input', function(event) {
                    if (event.target.classList.contains('custom-amount-value')) {
                        getCustomAmountsTotal();
                        calculateTdsAndBalance();
                    }
                });

                syncCustomAmountsEmptyState();
                getCustomAmountsTotal();
            }
            const ewayBillsContainer = document.getElementById('ewayBillsContainer');
const addEwayBillBtn = document.getElementById('addEwayBill');

let ewayBillIndex = ewayBillsContainer
    ? ewayBillsContainer.querySelectorAll('.eway-bill-row').length
    : 0;

if (addEwayBillBtn && ewayBillsContainer) {
    addEwayBillBtn.addEventListener('click', function () {
        const row = document.createElement('div');

        row.className = 'eway-bill-row d-flex gap-2 mb-2';

        row.innerHTML = `
            <div class="input-group">
                <input
                    type="text"
                    name="eway_bills[${ewayBillIndex}][eway_bill_number]"
                    class="form-control"
                    placeholder="E-Way Bill Number"
                >
                <a 
                    href="https://ewaybillgst.gov.in/Login.aspx"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-outline-success eway-portal-btn"
                    title="Create E-Way Bill"
                >
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>

            <input
                type="date"
                name="eway_bills[${ewayBillIndex}][eway_bill_expiry]"
                class="form-control eway-expiry-input"
            >

            <button
                type="button"
                class="btn btn-outline-danger remove-eway-bill"
            >
                <i class="bi bi-trash"></i> <span>Remove</span>
            </button>
        `;

        ewayBillsContainer.appendChild(row);
        ewayBillIndex++;
    });

    ewayBillsContainer.addEventListener('click', function (event) {
        if (event.target.closest('.remove-eway-bill')) {
            const row = event.target.closest('.eway-bill-row');

            if (!row) {
                return;
            }

            const ewayBillNumber = row.querySelector(
                'input[name*="[eway_bill_number]"]'
            )?.value?.trim();

            const ewayBillExpiry = row.querySelector(
                'input[name*="[eway_bill_expiry]"]'
            )?.value?.trim();

            if (ewayBillNumber || ewayBillExpiry) {
                const confirmed = confirm(
                    'This E-Way Bill has data. Are you sure you want to remove it?'
                );

                if (!confirmed) {
                    return;
                }
            }

            row.remove();
        }
    });
}
            // Function to calculate TDS and Balance amounts
            function calculateTdsAndBalance() {
                const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;
                const advanceAmount = parseFloat(document.getElementById('advance_amount_main') ? document
                    .getElementById('advance_amount_main').value : 0) || 0;
                const negativeAdjustedAmount = parseFloat(document.getElementById('negative_adjusted_amount') ?
                    document.getElementById('negative_adjusted_amount').value : 0) || 0;
                const customAmountsTotal = getCustomAmountsTotal();
                const detention = parseFloat(document.getElementById('detention') ? document.getElementById(
                    'detention').value : 0) || 0;
                const deviation = parseFloat(document.getElementById('deviation') ? document.getElementById(
                    'deviation').value : 0) || 0;
                const miscExpense = parseFloat(document.getElementById('misc_expense') ? document.getElementById(
                    'misc_expense').value : 0) || 0;
                const damageAmount = parseFloat(document.getElementById('damage_amount') ? document.getElementById(
                    'damage_amount').value : 0) || 0;
                const shortageAmount = parseFloat(document.getElementById('shortage_amount') ? document
                    .getElementById('shortage_amount').value : 0) || 0;

                const summaryTdsEl = document.getElementById('fbSummaryTds');
                const summaryBalanceEl = document.getElementById('fbSummaryBalance');

                // TDS calculation logic:
                // If TDS already submitted, never change TDS amount.
                // Before delivery: Calculate TDS from freight amount (TR amount)
                // After delivery: Only recalculate if loading_weight or rate changed, else keep previous TDS
                const loadingWeight = parseFloat(weightElement ? weightElement.value : 0) || 0;
                const trFrRate = parseFloat(rateInput ? rateInput.value : 0) || 0;

                let tdsAmount = previousTdsAmount;
                let shouldRecalculateTds = false;
                let tdsWasRecalculated = false;

                if (canChangeTds) {
                    if (!isDelivered) {
                        shouldRecalculateTds = true;
                    } else {
                        const loadingWeightChanged = Math.abs(previousLoadingWeight - loadingWeight) > 0.01;
                        const rateChanged = Math.abs(previousRate - trFrRate) > 0.01;

                        if (loadingWeightChanged || rateChanged) {
                            shouldRecalculateTds = true;
                        }
                    }

                    if (shouldRecalculateTds) {
                        if (!isDelivered) {
                            tdsAmount = roundUpTdsAmount(trAmount * (tdsPercentage / 100));
                        } else {
                            const tdsBaseAmount = loadingWeight * trFrRate;
                            tdsAmount = roundUpTdsAmount(tdsBaseAmount * (tdsPercentage / 100));
                        }
                        previousTdsAmount = tdsAmount;
                        tdsWasRecalculated = true;
                    }
                }

                if (tdsAmountInput) {
                    tdsAmountInput.value = tdsWasRecalculated
                        ? tdsAmount.toFixed(0)
                        : tdsAmount.toFixed(2);
                }

                if (summaryTdsEl) {
                    summaryTdsEl.textContent = '₹' + Number(tdsAmount).toFixed(2);
                }

                // Calculate Balance Amount
                const balance = trAmount - advanceAmount - tdsAmount + detention + deviation -
                    miscExpense - damageAmount - shortageAmount + customAmountsTotal + negativeAdjustedAmount;
                if (balanceAmountInput) {
                    balanceAmountInput.value = balance.toFixed(2);
                }

                if (summaryBalanceEl) {
                    summaryBalanceEl.textContent = '₹' + Number(balance).toFixed(2);
                }

                updateTrAmountHighlight();
            }

            // Function to calculate TR Amount using loose weight (min of loading and unloading weight)
            // When weight or rate changes → ALWAYS recalculate (mandatory, override any manual edit),
            // unless fixed amount is enabled.
            function calculateTrAmount() {
                const isFixed = isFixedAmountInput && isFixedAmountInput.checked;
                const rate = parseFloat(rateInput ? rateInput.value : 0) || 0;
                const loadingWeight = parseFloat(weightElement ? weightElement.value : 0) || 0;
                const unloadingWeight = parseFloat(unloadingWeightInput ? unloadingWeightInput.value : 0) || loadingWeight;

                if (!isFixed) {
                    if (Math.abs(loadingWeight - unloadingWeight) > 0.01) {
                        const effectiveWeight = Math.min(loadingWeight, unloadingWeight);
                        const amount = (effectiveWeight * rate).toFixed(2);
                        if (amountInput) {
                            amountInput.value = amount;
                        }
                    } else {
                        const amount = (loadingWeight * rate).toFixed(2);
                        if (amountInput) {
                            amountInput.value = amount;
                        }
                    }
                }

                previousLoadingWeight = loadingWeight;
                previousRate = rate;

                calculateTdsAndBalance();
            }

            // Add null checks for event listeners
            if (rateInput) {
                rateInput.addEventListener('input', calculateTrAmount);
            }
            if (unloadingWeightInput) {
                unloadingWeightInput.addEventListener('input', function() {
                    const loadingWeight = parseFloat(weightElement ? weightElement.value : 0) || 0;
                    const trFrRate = parseFloat(rateInput ? rateInput.value : 0) || 0;

                    const loadingWeightChanged = Math.abs(previousLoadingWeight - loadingWeight) > 0.01;
                    const rateChanged = Math.abs(previousRate - trFrRate) > 0.01;

                    calculateTrAmount();

                    // For TDS: after delivery, only recalculate if loading_weight or rate changed
                    if (isDelivered && !loadingWeightChanged && !rateChanged) {
                        if (tdsAmountInput) {
                            tdsAmountInput.value = previousTdsAmount.toFixed(2);
                        }
                        const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;
                        const advanceAmount = parseFloat(document.getElementById('advance_amount_main') ? document.getElementById('advance_amount_main').value : 0) || 0;
                        const detention = parseFloat(document.getElementById('detention') ? document.getElementById('detention').value : 0) || 0;
                        const deviation = parseFloat(document.getElementById('deviation') ? document.getElementById('deviation').value : 0) || 0;
                        const miscExpense = parseFloat(document.getElementById('misc_expense') ? document.getElementById('misc_expense').value : 0) || 0;
                        const damageAmount = parseFloat(document.getElementById('damage_amount') ? document.getElementById('damage_amount').value : 0) || 0;
                        const shortageAmount = parseFloat(document.getElementById('shortage_amount') ? document.getElementById('shortage_amount').value : 0) || 0;
                        const customAmountsTotal = getCustomAmountsTotal();
                        const negativeAdjustedAmount = parseFloat(document.getElementById('negative_adjusted_amount') ? document.getElementById('negative_adjusted_amount').value : 0) || 0;
                        const balance = trAmount - advanceAmount - previousTdsAmount + detention + deviation - miscExpense - damageAmount - shortageAmount + customAmountsTotal + negativeAdjustedAmount;
                        const balanceAmountInput = document.getElementById('balance_amount');
                        if (balanceAmountInput) {
                            balanceAmountInput.value = balance.toFixed(2);
                        }
                    }
                });
            }
            if (weightElement) {
                weightElement.addEventListener('input', calculateTrAmount);
            }

            if (isFixedAmountInput) {
                isFixedAmountInput.addEventListener('change', function() {
                    // When switching from fixed to auto, immediately recalculate amount from weight × rate
                    if (!isFixedAmountInput.checked) {
                        calculateTrAmount();
                    }
                    // TDS and balance depend on TR amount, so recalc them as well
                    calculateTdsAndBalance();
                });
            }

            // Update TDS and balance when TR Amount changes (manual input)
            if (trAmountInput) {
                trAmountInput.addEventListener('input', function() {
                    calculateTdsAndBalance();
                });
            }

            // Initialize previous values
            previousLoadingWeight = parseFloat(weightElement ? weightElement.value : 0) || 0;
            previousRate = parseFloat(rateInput ? rateInput.value : 0) || 0;
            previousTdsAmount = parseFloat(tdsAmountInput ? tdsAmountInput.value : 0) || 0;

            // Calculate initial TDS and balance if values exist
            initCustomAmountsRepeater();
            calculateTdsAndBalance();

            // Validation for additional charges fields
            function validateAdditionalCharges() {
                const trAmount = parseFloat(trAmountInput ? trAmountInput.value : 0) || 0;
                const fieldsToValidate = ['detention', 'deviation', 'misc_expense'];

                fieldsToValidate.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field && !field.hasValidationListener) {
                        field.hasValidationListener = true;
                        field.addEventListener('input', function() {
                            const value = parseFloat(field.value || 0);
                            const isValid = value >= 0 && value <= trAmount;

                            if (!isValid && value > 0) {
                                field.classList.add('is-invalid');
                                field.classList.remove('is-valid');

                                // Show custom validation message
                                let errorDiv = field.parentNode.querySelector('.validation-error');
                                if (!errorDiv) {
                                    errorDiv = document.createElement('div');
                                    errorDiv.className = 'validation-error text-danger small mt-1';
                                    field.parentNode.appendChild(errorDiv);
                                }
                                errorDiv.textContent =
                                    `Value must be >= 0 and <= TR Amount (Rs.${trAmount.toFixed(2)})`;
                            } else {
                                field.classList.remove('is-invalid');
                                field.classList.add('is-valid');

                                // Remove error message
                                const errorDiv = field.parentNode.querySelector(
                                    '.validation-error');
                                if (errorDiv) {
                                    errorDiv.remove();
                                }
                            }
                        });
                    }
                });
            }

            // Initialize validation
            validateAdditionalCharges();

            // Re-validate when TR Amount changes
            if (trAmountInput) {
                trAmountInput.addEventListener('input', function() {
                    validateAdditionalCharges();
                });
            }

            // Add event listeners for additional charges to recalculate balance
            const additionalChargeFields = ['detention', 'deviation', 'misc_expense', 'damage_amount',
                'shortage_amount'
            ];
            additionalChargeFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        calculateTdsAndBalance();
                        validateAdditionalCharges();
                    });
                }
            });

            // Handle is_delivered toggle
            const isDeliveredToggle = document.getElementById('is_delivered');
            if (isDeliveredToggle) {
                // Check if toggle should be frozen (memo_id exists)
                const currentMemoId =
                    '{{ $freightBooking->lr && $freightBooking->lr->memo_id ? $freightBooking->lr->memo_id : '' }}';
                const isCurrentlyDelivered = {{ $freightBooking->is_delivered ? 'true' : 'false' }};

                // If delivered and memo_id exists, freeze the toggle
                if (isCurrentlyDelivered && currentMemoId) {
                    isDeliveredToggle.disabled = true;
                }

                // Show additional charges if currently delivered
                if (isCurrentlyDelivered) {
                    document.getElementById('additional-charges-section').style.display = 'block';
                }

                isDeliveredToggle.addEventListener('change', function() {
                    const isCancelledToggle = document.getElementById('is_cancelled');

                    // Prevent unchecking if memo_id exists
                    if (!this.checked && currentMemoId) {
                        this.checked = true;
                        alert(
                            'Cannot unmark as delivered - Memo ID has been set. Contact administrator if changes are needed.'
                        );
                        return;
                    }

                    if (this.checked) {
                        // Disable cancelled toggle when delivered is checked
                        if (isCancelledToggle) {
                            isCancelledToggle.disabled = true;
                            isCancelledToggle.checked = false; // Uncheck cancelled if it was checked

                            // Update label to show it's disabled
                            const cancelledLabel = isCancelledToggle.parentNode.querySelector('label');
                            if (cancelledLabel && !cancelledLabel.querySelector('.text-muted')) {
                                cancelledLabel.innerHTML +=
                                    '<small class="text-muted ms-2"><i class="fas fa-lock"></i> Cannot cancel after delivery</small>';
                            }
                        }

                        // Show additional charges immediately when delivered is checked
                        document.getElementById('additional-charges-section').style.display = 'block';

                        // Check if memo_id already exists
                        if (!currentMemoId) {
                            // Show memo ID modal
                            const modal = new bootstrap.Modal(document.getElementById('memoIdModal'));
                            modal.show();

                            // Reset toggle if user cancels
                            document.getElementById('memoIdModal').addEventListener('hidden.bs.modal',
                                function() {
                                    if (!document.getElementById('deliveryMemoId').value) {
                                        isDeliveredToggle.checked = false;
                                        document.getElementById('additional-charges-section').style
                                            .display = 'none';

                                        // Re-enable cancelled toggle
                                        if (isCancelledToggle) {
                                            isCancelledToggle.disabled = false;

                                            // Remove disabled message
                                            const cancelledLabel = isCancelledToggle.parentNode
                                                .querySelector('label');
                                            const disabledMsg = cancelledLabel.querySelector(
                                                '.text-muted');
                                            if (disabledMsg) {
                                                disabledMsg.remove();
                                            }
                                        }
                                    }
                                });
                        }
                    } else {
                        // Re-enable cancelled toggle when delivered is unchecked
                        if (isCancelledToggle) {
                            isCancelledToggle.disabled = false;

                            // Remove disabled message
                            const cancelledLabel = isCancelledToggle.parentNode.querySelector('label');
                            const disabledMsg = cancelledLabel.querySelector('.text-muted');
                            if (disabledMsg) {
                                disabledMsg.remove();
                            }
                        }

                        // Hide additional charges when not delivered
                        document.getElementById('additional-charges-section').style.display = 'none';
                    }
                });
            }

            // Handle is_cancelled toggle with modal confirmation
            const isCancelledToggle = document.getElementById('is_cancelled');
            const cancelModalEl = document.getElementById('cancelGoodHireModal');
            const cancelModal = cancelModalEl ? new bootstrap.Modal(cancelModalEl) : null;
            const confirmCancelBtn = document.getElementById('confirmCancelGoodHireBtn');
            const cancelRemarkInput = document.getElementById('cancelGoodHireRemark');
            const cancelRemarkError = document.getElementById('cancelGoodHireRemarkError');
            let isCancellationConfirmed = false;

            if (isCancelledToggle && cancelModal) {
                isCancelledToggle.addEventListener('change', function() {
                    if (this.checked) {
                        isCancellationConfirmed = false;
                        if (cancelRemarkInput) {
                            const formRemark = document.getElementById('remark');
                            cancelRemarkInput.value = (formRemark && formRemark.value) ? formRemark.value : '';
                        }
                        if (cancelRemarkError) {
                            cancelRemarkError.classList.add('d-none');
                        }
                        cancelModal.show();
                    }
                });

                if (confirmCancelBtn) {
                    confirmCancelBtn.addEventListener('click', function() {
                        const remarkVal = cancelRemarkInput ? cancelRemarkInput.value.trim() : '';
                        if (!remarkVal) {
                            if (cancelRemarkError) {
                                cancelRemarkError.classList.remove('d-none');
                                cancelRemarkError.textContent = 'Cancellation remark is required.';
                            }
                            if (cancelRemarkInput) cancelRemarkInput.focus();
                            return;
                        }

                        if (cancelRemarkError) {
                            cancelRemarkError.classList.add('d-none');
                        }

                        // Set the remark value in the form
                        const formRemark = document.getElementById('remark');
                        if (formRemark) {
                            formRemark.value = remarkVal;
                        }

                        isCancellationConfirmed = true;
                        isCancelledToggle.checked = true;
                        cancelModal.hide();
                    });
                }

                cancelModalEl.addEventListener('hidden.bs.modal', function() {
                    if (!isCancellationConfirmed) {
                        isCancelledToggle.checked = false;
                    }
                    isCancellationConfirmed = false;
                });
            }
        });
    </script>
@endsection
