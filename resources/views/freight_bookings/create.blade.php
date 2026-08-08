@extends('layouts.app')

@section('content')
    @php
        $lr = $lorry_receipt->first();
        $weight = $weight ?? ($lr->weight ?? 0);
        $lrAmount = $lr->amount ?? 0;
        $tdsPct = $lr && $lr->transporter ? ($lr->transporter->tds_percentage ?? 0) : 0;
        $cancelledBookings = \App\Models\FreightBooking::where('is_cancelled', 1)
            ->where('lr_id', $lr->id ?? 0)
            ->orderBy('id', 'desc')
            ->first();
        $fbOptionalOpen = filled(old('eway_bill_number'))
            || filled(old('eway_bill_expiry'))
            || filled(old('remark'))
            || $errors->hasAny(['eway_bill_number', 'eway_bill_expiry', 'remark']);
    @endphp

    @if (session('success'))
        <div class="alert alert-success lrf-alert mb-3">
            {{ session('success') }}
        </div>
    @endif

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

    <link rel="stylesheet" href="{{ asset('css/lr-form-redesigned.css') }}">

    <div class="lrf-page app-index-page">
        <div class="lrf-page__toolbar">
            <div>
                <h1 class="lrf-page__toolbar-title">Add New Good Hire (Lorry Hire)</h1>
                <p class="lrf-page__toolbar-sub">Linked bilty is shown for reference. Complete hire charges to continue.</p>
            </div>
            <div class="lrf-page__toolbar-actions">
                @if ($cancelledBookings)
                    <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="modal"
                        data-bs-target="#cancelBookingModal">
                        <i class="bi bi-info-circle me-1"></i> Cancelled Booking
                    </button>
                @endif
                <a href="{{ route('freight_bookings.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        {{-- Linked Bilty (read-only context from LR) --}}
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
                        <span class="lrf-linked__kpi-value">{{ $lr?->good_type ?? '—' }}</span>
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
                                    {{ $lr && $lr->transporter ? $lr->transporter->name : 'N/A' }}
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

                @if ($lr && $lr->freight_booking_id)
                    <div class="alert alert-danger lrf-alert mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div><strong>Old Freight Number:</strong> {{ $lr->freight_booking_id }}</div>
                    </div>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('freight_bookings.confirm') }}" id="freightBookingForm"
            class="needs-validation lrf" novalidate="">
            @csrf
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            <input type="hidden" name="lr_id" id="lr_id"
                value="{{ isset($preFilled) && isset($preFilled['lr_id']) ? $preFilled['lr_id'] : old('lr_id') }}" required>
            <input type="hidden" name="transporter_id"
                value="{{ isset($preFilled) && isset($preFilled['transporter_id']) ? $preFilled['transporter_id'] : '' }}">
            <input type="hidden" name="truck_id"
                value="{{ isset($preFilled) && isset($preFilled['truck_id']) ? $preFilled['truck_id'] : '' }}">
            <input type="hidden" name="good_type"
                value="{{ isset($preFilled) && isset($preFilled['goods_type']) ? $preFilled['goods_type'] : '' }}">
            <input type="hidden" name="advance_amount" id="advance_amount" value="0">
            <input type="hidden" name="is_delivered" id="is_delivered" value="0">
            <input type="hidden" name="is_cancelled" id="is_cancelled" value="0">
            <input type="hidden" name="redirect_to_payment" value="0">
            <input type="hidden" id="weight" value="{{ $weight }}">
            <input type="hidden" id="lr_amount" value="{{ $lrAmount }}">
            <input type="hidden" id="transporter_tds_percentage" name="transporter_tds_percentage" value="{{ $tdsPct }}">

            @error('lr_id')
                <div class="text-danger mb-2">{{ $message }}</div>
            @enderror
            @error('transporter_id')
                <div class="text-danger mb-2">{{ $message }}</div>
            @enderror
            @error('truck_id')
                <div class="text-danger mb-2">{{ $message }}</div>
            @enderror
            @error('good_type')
                <div class="text-danger mb-2">{{ $message }}</div>
            @enderror

            {{-- Section 1 — Hire Charges --}}
            <div class="lrf-section">
                <div class="lrf-section__head lrf-section__head--summary">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num lrf-step-num--summary">1</div>
                        <div>
                            <h2 class="lrf-section__title">Hire Charges</h2>
                            <p class="lrf-section__desc">TR rate, amount, TDS and balance</p>
                        </div>
                    </div>
                </div>
                <div class="lrf-section__body">
                    <div class="lrf-grid lrf-grid--4">
                        <div class="lrf-pf">
                            <label for="tr_fr_rate" class="lrf-pf__lbl">TR Freight Rate<span class="lrf-pf__req">*</span></label>
                            <input type="number" step="0.01" min="1" max="20000" class="form-control"
                                name="tr_fr_rate" id="tr_fr_rate" value="{{ old('tr_fr_rate') }}"
                                placeholder="1 – 20,000" required>
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
                                        name="is_fixed_amount" {{ old('is_fixed_amount') ? 'checked' : '' }}>
                                    <label class="form-check-label mb-0" for="is_fixed_amount">Fixed</label>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="tr_amount"
                                id="tr_amount" value="{{ old('tr_amount') }}" placeholder="Auto-calculated" required>
                            <div id="tr_amount_lr_warning" class="form-text text-warning fw-semibold d-none">
                                <i class="bi bi-exclamation-triangle"></i> TR Amount exceeds LR Amount.
                            </div>
                            <div class="lrf-pf__hint">Auto: Rate × {{ number_format($weight, 3) }} MT</div>
                            @error('tr_amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="lrf-pf">
                            <label for="tds_amount" class="lrf-pf__lbl">TDS Amount<span class="lrf-pf__req">*</span></label>
                            <input type="number" step="0.01" class="form-control is-auto" name="tds_amount" id="tds_amount"
                                value="{{ old('tds_amount') }}" readonly>
                            <div class="lrf-pf__hint">{{ number_format($tdsPct, 2) }}% of TR Amount</div>
                            @error('tds_amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="lrf-pf">
                            <label for="balance_amount" class="lrf-pf__lbl">Balance Amount<span class="lrf-pf__req">*</span></label>
                            <input type="number" step="0.01" class="form-control is-auto" name="balance_amount"
                                id="balance_amount" value="{{ old('balance_amount') }}" required readonly>
                            <div class="lrf-pf__hint">TR Amount − TDS</div>
                            @error('balance_amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="lrf-summary-metrics">
                        <div class="lrf-metric">
                            <div class="lrf-metric__icon" style="--ic:#7c3aed;--bg:#faf5ff"><i class="bi bi-currency-rupee"></i></div>
                            <div>
                                <span class="lrf-metric__name">TR Rate</span>
                                <span class="lrf-metric__val" id="fbSummaryRate">—</span>
                            </div>
                        </div>
                        <div class="lrf-metric">
                            <div class="lrf-metric__icon" style="--ic:#b45309;--bg:#fffbeb"><i class="bi bi-percent"></i></div>
                            <div>
                                <span class="lrf-metric__name">TDS</span>
                                <span class="lrf-metric__val" id="fbSummaryTds">—</span>
                            </div>
                        </div>
                        <div class="lrf-metric lrf-metric--hero">
                            <div class="lrf-metric__icon" style="--ic:#16a34a;--bg:#dcfce7"><i class="bi bi-wallet2"></i></div>
                            <div>
                                <span class="lrf-metric__name">Balance</span>
                                <span class="lrf-metric__val" id="fbSummaryBalance">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2 — Optional Details --}}
            <div class="lrf-section lrf-section--collapsible{{ $fbOptionalOpen ? ' lrf-section--open' : '' }}" id="fbOptionalSection">
                <button type="button" class="lrf-section__head lrf-section__head--plain lrf-section__head--toggle"
                    id="fbOptionalToggle" aria-expanded="{{ $fbOptionalOpen ? 'true' : 'false' }}"
                    aria-controls="fbOptionalBody">
                    <div class="lrf-section__head-left">
                        <div class="lrf-step-num lrf-step-num--gray">2</div>
                        <div>
                            <h2 class="lrf-section__title">Optional Details</h2>
                            <p class="lrf-section__desc">E-way bill, PAN status and remarks</p>
                        </div>
                    </div>
                    <i class="bi bi-chevron-down lrf-collapse-arrow{{ $fbOptionalOpen ? ' lrf-collapse-arrow--open' : '' }}"></i>
                </button>
                <div class="lrf-section__body" id="fbOptionalBody" @if (! $fbOptionalOpen) style="display: none;" @endif>
                    <div class="lrf-grid lrf-grid--4">
                        <div class="lrf-pf">
                            <label for="eway_bill_number" class="lrf-pf__lbl">E-Way Bill Number</label>
                            <div class="input-group flex-nowrap">
                                <input type="text" class="form-control" name="eway_bill_number" id="eway_bill_number"
                                    value="{{ old('eway_bill_number') }}" placeholder="Optional">
                                <a href="https://ewaybillgst.gov.in/Login.aspx" target="_blank" rel="noopener"
                                    class="btn btn-outline-secondary" title="Check E-way Bill Status">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                            @error('eway_bill_number')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="lrf-pf">
                            <label for="eway_bill_expiry" class="lrf-pf__lbl">E-Way Bill Expiry</label>
                            <input type="date" class="form-control" name="eway_bill_expiry" id="eway_bill_expiry"
                                value="{{ old('eway_bill_expiry') }}">
                            @error('eway_bill_expiry')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="lrf-pf">
                            <label for="pan_verified_display" class="lrf-pf__lbl">PAN Verified<span class="lrf-pf__req">*</span></label>
                            <input type="hidden" name="pan_verified" id="pan_verified"
                                value="{{ isset($preFilled['pan_verified']) ? (int) $preFilled['pan_verified'] : (old('pan_verified') ? 1 : 0) }}"
                                required>
                            <input type="text" class="form-control is-auto" id="pan_verified_display" readonly
                                value="{{ isset($preFilled['pan_verified']) && $preFilled['pan_verified'] ? 'Yes' : 'No' }}">
                            <div class="lrf-pf__hint">From transporter data</div>
                            @error('pan_verified')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="lrf-pf">
                            <label for="remark" class="lrf-pf__lbl">Remark</label>
                            <input type="text" name="remark" id="remark" class="form-control"
                                value="{{ old('remark') }}" placeholder="Optional">
                            @error('remark')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="lrf-actions">
                <a href="{{ route('freight_bookings.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="fbClearFormBtn">
                    <i class="bi bi-eraser me-1"></i> Clear
                </button>
                <button class="btn btn-success btn-sm" type="submit" name="action" value="save">
                    <i class="bi bi-check-lg me-1"></i> Continue to Review
                </button>
            </div>
        </form>
    </div>

    @if ($cancelledBookings)
        <div class="modal fade fb-pay-modal" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="cancelBookingModalLabel">
                            <i class="bi bi-info-circle me-2"></i> Cancelled Good Hire Details
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>TR Freight Rate:</strong> {{ number_format($cancelledBookings->tr_fr_rate, 2) }}</p>
                        <p><strong>TR Amount:</strong> {{ number_format($cancelledBookings->tr_amount, 2) }}</p>
                        <p><strong>TDS Amount:</strong> {{ number_format($cancelledBookings->tds_amount, 2) }}</p>
                        <p><strong>Balance Amount:</strong> {{ number_format($cancelledBookings->balance_amount, 2) }}</p>
                        <p><strong>Advance Amount:</strong> {{ number_format($cancelledBookings->advance_amount, 2) }}</p>
                        <p><strong>E-Way Bill Number:</strong> {{ $cancelledBookings->eway_bill_number ?? 'N/A' }}</p>
                        <p><strong>E-Way Bill Expiry Date:</strong>
                            {{ $cancelledBookings->eway_bill_expiry ? \Carbon\Carbon::parse($cancelledBookings->eway_bill_expiry)->format('d M Y') : 'N/A' }}
                        </p>
                        <p><strong>Remark:</strong> {{ $cancelledBookings->remark ?? 'N/A' }}</p>
                        <p><strong>Cancelled On:</strong> {{ $cancelledBookings->updated_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weight = parseFloat(document.getElementById('weight')?.value || 0) || 0;
            const rateInput = document.getElementById('tr_fr_rate');
            const amountInput = document.getElementById('tr_amount');
            const isFixedAmountInput = document.getElementById('is_fixed_amount');
            const balanceAmountInput = document.getElementById('balance_amount');
            const tdsAmountInput = document.getElementById('tds_amount');
            const tdsPercentage = parseFloat(document.getElementById('transporter_tds_percentage')?.value || 0) || 0;
            const fbSummaryRate = document.getElementById('fbSummaryRate');
            const fbSummaryTds = document.getElementById('fbSummaryTds');
            const fbSummaryBalance = document.getElementById('fbSummaryBalance');

            function formatMoney(value) {
                if (!value || isNaN(value)) {
                    return '—';
                }
                return '₹' + Number(value).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

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

            function updateSummaryMetrics() {
                const rate = parseFloat(rateInput?.value || 0) || 0;
                const tds = parseFloat(tdsAmountInput?.value || 0) || 0;
                const balance = parseFloat(balanceAmountInput?.value || 0) || 0;
                if (fbSummaryRate) {
                    fbSummaryRate.textContent = rate > 0 ? formatMoney(rate) : '—';
                }
                if (fbSummaryTds) {
                    fbSummaryTds.textContent = tds > 0 ? formatMoney(tds) : '—';
                }
                if (fbSummaryBalance) {
                    fbSummaryBalance.textContent = balance !== 0 ? formatMoney(balance) : '—';
                }
            }

            function calculateTdsAndBalance() {
                const trAmount = parseFloat(amountInput?.value || 0) || 0;
                const tdsAmount = roundUpTdsAmount(trAmount * (tdsPercentage / 100)).toFixed(0);
                if (tdsAmountInput) {
                    tdsAmountInput.value = tdsAmount;
                }
                if (balanceAmountInput) {
                    balanceAmountInput.value = (trAmount - parseFloat(tdsAmount)).toFixed(2);
                }
                updateTrAmountHighlight();
                updateSummaryMetrics();
            }

            function recalculateAmountFromRate() {
                if (!amountInput) {
                    return;
                }
                if (!isFixedAmountInput || !isFixedAmountInput.checked) {
                    const rate = parseFloat(rateInput?.value || 0) || 0;
                    amountInput.value = (weight * rate).toFixed(2);
                    amountInput.classList.add('is-auto');
                }
                calculateTdsAndBalance();
            }

            if (rateInput) {
                rateInput.addEventListener('input', recalculateAmountFromRate);
            }

            if (isFixedAmountInput) {
                isFixedAmountInput.addEventListener('change', function() {
                    if (amountInput) {
                        amountInput.readOnly = !isFixedAmountInput.checked ? false : false;
                        if (!isFixedAmountInput.checked) {
                            recalculateAmountFromRate();
                        } else {
                            amountInput.classList.remove('is-auto');
                        }
                    }
                });
            }

            if (amountInput) {
                amountInput.addEventListener('input', function() {
                    this.classList.remove('is-auto');
                    calculateTdsAndBalance();
                });
            }

            const fbOptionalToggle = document.getElementById('fbOptionalToggle');
            const fbOptionalBody = document.getElementById('fbOptionalBody');
            const fbOptionalSection = document.getElementById('fbOptionalSection');
            if (fbOptionalToggle && fbOptionalBody && fbOptionalSection) {
                fbOptionalToggle.addEventListener('click', function() {
                    const isOpen = fbOptionalSection.classList.toggle('lrf-section--open');
                    fbOptionalBody.style.display = isOpen ? '' : 'none';
                    fbOptionalToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    const arrow = fbOptionalToggle.querySelector('.lrf-collapse-arrow');
                    if (arrow) {
                        arrow.classList.toggle('lrf-collapse-arrow--open', isOpen);
                    }
                });
            }

            const fbClearFormBtn = document.getElementById('fbClearFormBtn');
            if (fbClearFormBtn) {
                fbClearFormBtn.addEventListener('click', function() {
                    if (rateInput) {
                        rateInput.value = '';
                    }
                    if (amountInput) {
                        amountInput.value = '';
                        amountInput.classList.remove('is-auto', 'tr-amount-exceeds-lr');
                    }
                    if (isFixedAmountInput) {
                        isFixedAmountInput.checked = false;
                    }
                    if (tdsAmountInput) {
                        tdsAmountInput.value = '';
                    }
                    if (balanceAmountInput) {
                        balanceAmountInput.value = '';
                    }
                    const eway = document.getElementById('eway_bill_number');
                    const ewayExpiry = document.getElementById('eway_bill_expiry');
                    const remark = document.getElementById('remark');
                    if (eway) {
                        eway.value = '';
                    }
                    if (ewayExpiry) {
                        ewayExpiry.value = '';
                    }
                    if (remark) {
                        remark.value = '';
                    }
                    document.getElementById('tr_amount_label')?.classList.remove('tr-amount-exceeds-lr');
                    document.getElementById('tr_amount_lr_warning')?.classList.add('d-none');
                    updateSummaryMetrics();
                });
            }

            calculateTdsAndBalance();
        });
    </script>
@endsection
