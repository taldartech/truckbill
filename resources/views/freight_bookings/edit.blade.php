@extends('layouts.app')

@section('content')
    <style>
        #tr_amount.tr-amount-exceeds-lr {
            background-color: #fff3cd;
        }

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
    </style>

    <!-- LR Information Card -->
    <div class="card card-info card-outline mb-4" style="max-width: 1000px; margin: 0 auto;">
        <div class="card-header">
            <div class="card-title h5">Good Receipt (Bilty) Information</div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>LR Number:</strong><br>
                        <span class="text-primary">{{ $freightBooking->lr ? $freightBooking->lr->lr_number : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>Transporter:</strong><br>
                        <span>{{ $freightBooking->transporter_name ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>Transporter Type:</strong><br>
                        @if ($freightBooking->lr && $freightBooking->lr->transporter)
                            @if ($freightBooking->lr->transporter->is_owner)
                                <span class="badge bg-primary text-white fs-6">Truck Owner</span>
                            @else
                                <span class="badge bg-info text-white fs-6">Agency</span>
                            @endif
                        @else
                            <span>N/A</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>TDS Percentage:</strong><br>
                        <span
                            class="badge bg-warning text-dark fs-6">{{ number_format($freightBooking->transporter_tds_percentage ?? 0, 2) }}%</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <strong>Truck:</strong><br>
                        <span>{{ $freightBooking->lr && $freightBooking->lr->truck ? $freightBooking->lr->truck->truck_number : 'N/A' }}</span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-2">
                        <strong>Customer:</strong><br>
                        <span>{{ $freightBooking->lr && $freightBooking->lr->customer ? $freightBooking->lr->customer->name : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-2">
                        <strong>Consignor:</strong><br>
                        <span>{{ $freightBooking->lr && $freightBooking->lr->consignor ? $freightBooking->lr->consignor->name : 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-2">
                        <strong>Consignee:</strong><br>
                        <span>{{ $freightBooking->lr && $freightBooking->lr->consignee ? $freightBooking->lr->consignee->name : 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Goods Type Details Section -->
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Goods Type:</strong><br>
                        <span
                            class="badge bg-secondary text-white fs-6">{{ $freightBooking->lr ? $freightBooking->lr->good_type : 'N/A' }}</span>
                    </div>
                </div>
                    <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Loading Weight:</strong><br>
                        <span
                            class="badge bg-info text-white fs-6">{{ $freightBooking->lr ? number_format($freightBooking->lr->weight ?? 0, 3) : '0.00' }}
                            MT</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>LR Amount:</strong><br>
                        <span
                            class="badge bg-brand-primary text-white fs-6">{{ $freightBooking->lr ? number_format($freightBooking->lr->amount ?? 0, 2) : '0.00' }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <strong>Rate:</strong><br>
                        <span
                            class="badge bg-brand-primary text-white fs-6">{{ $freightBooking->lr ? number_format($freightBooking->lr->fr_rate ?? 0, 2) : '0.00' }}</span>
                    </div>
                </div>
            </div>

            <!-- Route Details Section -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                        <strong>Route:</strong><br>
                        <span class="text-muted">
                            {{ $freightBooking->lr ? $freightBooking->lr->source_city : 'N/A' }},
                            {{ $freightBooking->lr ? $freightBooking->lr->source_state : 'N/A' }}
                            <i class="fas fa-arrow-right mx-2"></i>
                            {{ $freightBooking->lr ? $freightBooking->lr->destination_city : 'N/A' }},
                            {{ $freightBooking->lr ? $freightBooking->lr->destination_state : 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($freightBooking->is_cancelled)
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert"
            style="max-width: 1000px; margin: 0 auto;">
            <div class="d-flex align-items-center">
                <i class="fas fa-times-circle fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">
                        <i class="fas fa-exclamation-triangle"></i> CANCELLED FREIGHT BOOKING
                    </h5>
                    <p class="mb-0">This good hire (lorry hire) has been cancelled and cannot be modified. All form fields are
                        disabled.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="card {{ $freightBooking->is_cancelled ? 'border-danger' : 'card-info' }} card-outline mb-4"
        style="max-width: 1000px; margin: 0 auto; {{ $freightBooking->is_cancelled ? 'opacity: 0.8;' : '' }}">
        <div class="card-header {{ $freightBooking->is_cancelled ? 'bg-danger-subtle' : '' }}">
            <div class="card-title h4 d-flex justify-content-between align-items-center w-100">
                <div>
                    Edit Good Hire (Lorry Hire)
                    @if ($freightBooking->is_cancelled)
                        <span class="badge bg-danger ms-2">
                            <i class="fas fa-times-circle"></i> CANCELLED
                        </span>
                    @endif
                </div>
                @if ($freightBooking->linked_freight_booking_id)
                    <div class="card-toolbar">
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#viewCancelledModal">
                            View Cancelled
                        </button>
                    </div>
                @endif
            </div>
        </div>
        <!--begin::Form-->
        <form
            action="{{ route('freight_bookings.update', $freightBooking->id) }}{{ \App\Support\ListingQueryString::currentSuffix(\App\Support\ListingQueryString::FREIGHT_BOOKING_KEYS) }}"
            method="POST"
            {{ $freightBooking->is_cancelled ? 'onsubmit="return false;"' : '' }}>
            @csrf
            @php
                $is_delivered = $freightBooking->is_delivered;
                $is_invoice_generated = $freightBooking->lr && $freightBooking->lr->invoice_generated;
            @endphp
            @method('PUT')
            @foreach (\App\Support\ListingQueryString::params(request(), \App\Support\ListingQueryString::FREIGHT_BOOKING_KEYS) as $listingKey => $listingValue)
                <input type="hidden" name="{{ $listingKey }}" value="{{ $listingValue }}">
            @endforeach
            <div class="card-body">
                <!-- Financial Information Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                        <input type="number" step="0.01" min="1" max="20000"
                            class="form-control @error('tr_fr_rate') is-invalid @enderror" name="tr_fr_rate" id="tr_fr_rate"
                            value="{{ old('tr_fr_rate', $freightBooking->tr_fr_rate) }}"
                            placeholder="Enter freight rate (1 - 20,000 per ton)" required
                            {{ ! $is_admin ? 'readonly style=background-color:#e9ecef;' : '' }}
                            {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Rate must be between 1 and 20,000 per ton
                        </small>
                        @error('tr_fr_rate')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="tr_amount" class="form-label" id="tr_amount_label">TR Amount *</label>
                        <input type="number" step="0.01" min="0.01"
                            class="form-control @error('tr_amount') is-invalid @enderror" name="tr_amount" id="tr_amount"
                            value="{{ old('tr_amount', $freightBooking->tr_amount) }}"
                            placeholder="Auto-calculated (editable)" required
                            {{ ! $is_admin ? 'readonly style=background-color:#e9ecef;' : '' }}
                            {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                        <div id="tr_amount_lr_warning" class="form-text text-warning fw-semibold d-none">
                            <i class="fas fa-exclamation-triangle"></i> TR Amount exceeds LR Amount.
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Auto-calculated: TR Freight Rate × Min(Loading Weight, Unloading Weight). Must be greater
                            than 0.
                        </small>
                        @error('tr_amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" value="1" id="is_fixed_amount"
                                name="is_fixed_amount"
                                {{ old('is_fixed_amount', $freightBooking->is_fixed_amount ?? false) ? 'checked' : '' }}
                                {{ ! $is_admin ? 'disabled' : '' }}
                                {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                            <label class="form-check-label" for="is_fixed_amount">
                                Use fixed amount (do not auto-calculate)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="tds_amount" class="form-label">TDS Amount *</label>
                        <input type="number" step="0.01"
                            class="form-control @error('tds_amount') is-invalid @enderror" name="tds_amount"
                            id="tds_amount" value="{{ old('tds_amount', $freightBooking->tds_amount) }}" readonly
                            style="background-color: #f8f9fa;" {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                        @if(isset($canChangeTds) && !$canChangeTds)
                        <small class="form-text text-muted text-warning">
                            <i class="fas fa-lock"></i> TDS already submitted; amount cannot be changed.
                        </small>
                        @else
                        <small class="form-text text-muted">
                            <i class="fas fa-percentage"></i> Auto-calculated based on transporter TDS percentage
                        </small>
                        @endif
                        @error('tds_amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Hidden advance amount field with default value 0 -->
                    <input type="hidden" name="advance_amount" id="advance_amount" value="0">
                    <!-- Hidden memo_id field -->
                    <input type="hidden" name="memo_id" id="memo_id"
                        value="{{ $freightBooking->lr && $freightBooking->lr->memo_id ? $freightBooking->lr->memo_id : '' }}">
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="eway_bill_number" class="form-label">E-Way Bill Number</label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('eway_bill_number') is-invalid @enderror"
                                name="eway_bill_number" id="eway_bill_number"
                                value="{{ old('eway_bill_number', $freightBooking->eway_bill_number) }}"
                                placeholder="Enter E-way bill number"
                                {{ $is_delivered ? 'readonly style=background-color:#e9ecef;' : '' }}
                                {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                            <a href="https://ewaybillgst.gov.in/Login.aspx" target="_blank" class="btn btn-info"
                                title="Check E-way Bill Status">
                                <i class="fas fa-external-link-alt"></i> Check Status
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="eway_bill_expiry" class="form-label">E-Way Bill Expiry Date</label>
                        <input type="date" class="form-control @error('eway_bill_expiry') is-invalid @enderror"
                            name="eway_bill_expiry" id="eway_bill_expiry"
                            value="{{ old('eway_bill_expiry', $freightBooking->eway_bill_expiry ? $freightBooking->eway_bill_expiry : '') }}"
                            {{ $is_delivered ? 'readonly style=background-color:#e9ecef;' : '' }}
                            {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                        @error('eway_bill_expiry')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="advance_amount_main" class="form-label">Advance Amount</label>
                            <input type="number" step="0.01" class="form-control" id="advance_amount_main"
                                name="advance_amount"
                                value="{{ old('advance_amount', $freightBooking->advance_amount) }}" readonly
                                style="background-color: #f8f9fa;">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Read-only field
                            </small>
                        </div>
                        <div class="col-md-4">
                            <label for="negative_adjusted_amount_display" class="form-label">Negative Adjusted Amount</label>
                            <input type="number" step="0.01" class="form-control {{ ($freightBooking->negative_adjusted_amount ?? 0) > 0 ? '' : 'text-muted' }}"
                                id="negative_adjusted_amount_display"
                                value="{{ number_format((float) ($freightBooking->negative_adjusted_amount ?? 0), 2, '.', '') }}"
                                readonly style="background-color: #f8f9fa;">
                            <input type="hidden" id="negative_adjusted_amount"
                                value="{{ number_format((float) ($freightBooking->negative_adjusted_amount ?? 0), 2, '.', '') }}">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Overpayment moved to transporter advance (system-managed)
                            </small>
                        </div>
                        <div class="col-md-4">
                            <label for="balance_amount" class="form-label">Balance Amount *</label>
                            <input type="number" step="0.01"
                                class="form-control @error('balance_amount') is-invalid @enderror" name="balance_amount"
                                id="balance_amount" value="{{ old('balance_amount', $freightBooking->balance_amount) }}"
                                required readonly style="background-color: #f8f9fa;"
                                {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                            <small class="form-text text-muted">
                                <i class="fas fa-calculator"></i> Auto-calculated
                            </small>
                            @error('balance_amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if (! $freightBooking->is_cancelled)
                        @php
                            $customAmountRows = old('custom_amounts');
                            if ($customAmountRows === null) {
                                $customAmountRows = $freightBooking->customAmounts->map(fn ($row) => [
                                    'id' => $row->id,
                                    'description' => $row->description,
                                    'amount' => $row->amount,
                                ])->values()->all();
                            }
                        @endphp
                        <div class="row mb-3" id="custom-amounts-section">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                    <h5 class="text-primary mb-0">
                                        <i class="fas fa-list-ul"></i> Custom Amounts
                                    </h5>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addCustomAmountRow">
                                        <i class="fas fa-plus"></i> Add amount
                                    </button>
                                </div>
                                <p class="small text-muted mb-2">Add positive or negative adjustments with a description. These affect the booking balance.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-2">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 55%;">Description</th>
                                                <th style="width: 30%;">Amount (+/−)</th>
                                                <th style="width: 15%;" class="text-center">Action</th>
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
                                                        <input type="text" class="form-control form-control-sm"
                                                            name="custom_amounts[{{ $index }}][description]"
                                                            value="{{ $customAmountRow['description'] ?? '' }}"
                                                            maxlength="255" required placeholder="e.g. Loading charge">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01"
                                                            class="form-control form-control-sm custom-amount-value"
                                                            name="custom_amounts[{{ $index }}][amount]"
                                                            value="{{ $customAmountRow['amount'] ?? '' }}"
                                                            required placeholder="0.00">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-custom-amount-row">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end small">
                                    <span class="text-muted me-2">Custom amounts total:</span>
                                    <strong id="custom-amounts-total">0.00</strong>
                                </div>
                            </div>
                        </div>

                        <template id="custom-amount-row-template">
                            <tr class="custom-amount-row">
                                <td>
                                    <input type="text" class="form-control form-control-sm custom-amount-description"
                                        maxlength="255" required placeholder="e.g. Loading charge">
                                </td>
                                <td>
                                    <input type="number" step="0.01"
                                        class="form-control form-control-sm custom-amount-value"
                                        required placeholder="0.00">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-custom-amount-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    @endif

                    <!-- Additional Charges & Status Section -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-plus-circle"></i> Additional Charges & Status
                            </h5>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Delivery Status</label>
                            <div class="d-flex gap-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_delivered"
                                        value="{{ $freightBooking->is_delivered }}">
                                    <input class="form-check-input" type="checkbox" id="is_delivered"
                                        name="is_delivered" value="1"
                                        {{ old('is_delivered', $freightBooking->is_delivered) ? 'checked' : '' }}
                                        {{ ($freightBooking->is_delivered && $freightBooking->lr) || $freightBooking->lr->memo_id || $freightBooking->is_cancelled ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="is_delivered">
                                        <i class="fas fa-truck"></i> Delivered
                                        @if (($freightBooking->is_delivered && $freightBooking->lr) || $freightBooking->lr->memo_id)
                                            <small class="text-muted ms-2">
                                                <i class="fas fa-lock"></i> Locked (Memo ID:
                                                {{ $freightBooking->lr->memo_id ?? 'N/A' }})
                                            </small>
                                        @endif
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_cancelled"
                                        value="{{ $freightBooking->is_cancelled }}">
                                    <input class="form-check-input" type="checkbox" id="is_cancelled"
                                        name="is_cancelled" value="1"
                                        {{ old('is_cancelled', $freightBooking->is_cancelled) ? 'checked' : '' }}
                                        {{ $freightBooking->is_delivered || $freightBooking->is_cancelled ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="is_cancelled">
                                        <i class="fas fa-times-circle"></i> Cancelled
                                        @if ($freightBooking->is_delivered)
                                            <small class="text-muted ms-2">
                                                <i class="fas fa-lock"></i> Cannot cancel after delivery
                                            </small>
                                        @else
                                            <small class="text-muted ms-2">
                                                <i class="fas fa-exclamation-triangle"></i> Will move LR to Pending status
                                            </small>
                                        @endif
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="additional-charges-section"
                        style="display: {{ $freightBooking->is_delivered ? 'block' : 'none' }};">
                        <div class="row mb-2">
                            <div class="col-md-2">
                                <label for="detention" class="form-label">Detention</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control form-control-sm @error('detention') is-invalid @enderror"
                                    name="detention" id="detention"
                                    value="{{ old('detention', $freightBooking->detention) }}"
                                    placeholder="Enter detention amount"
                                    {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> ≥ 0 and < TR Amount </small>
                                        @error('detention')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                            </div>
                            <div class="col-md-2">
                                <label for="deviation" class="form-label">Deviation</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control form-control-sm @error('deviation') is-invalid @enderror"
                                    name="deviation" id="deviation"
                                    value="{{ old('deviation', $freightBooking->deviation) }}"
                                    placeholder="Enter deviation amount"
                                    {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> ≥ 0 and < TR Amount </small>
                                        @error('deviation')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                            </div>
                            <div class="col-md-2">
                                <label for="misc_expense" class="form-label">Misc. Expense</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control form-control-sm @error('misc_expense') is-invalid @enderror"
                                    name="misc_expense" id="misc_expense"
                                    value="{{ old('misc_expense', $freightBooking->misc_expense) }}"
                                    placeholder="Enter misc expense"
                                    {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> ≥ 0 and < TR Amount </small>
                                        @error('misc_expense')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                            </div>
                            <div class="col-md-2">
                                <label for="unloading_weight" class="form-label">Unloading Weight *</label>
                                <input type="number" step="0.001" min="0"
                                    class="form-control form-control-sm @error('unloading_weight') is-invalid @enderror"
                                    name="unloading_weight" id="unloading_weight"
                                    value="{{ old('unloading_weight', $freightBooking->lr ? ($freightBooking->lr->unloading_weight ?? $freightBooking->lr->weight ?? 0) : 0) }}"
                                    placeholder="Enter unloading weight" required
                                    {{ $is_invoice_generated ? 'readonly style=background-color:#e9ecef;' : '' }}
                                    {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>
                                @error('unloading_weight')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <label for="damage_amount" class="form-label">Damage Amount</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control form-control-sm @error('damage_amount') is-invalid @enderror"
                                    name="damage_amount" id="damage_amount"
                                    value="{{ old('damage_amount', $freightBooking->damage_amount) }}"
                                    placeholder="Enter damage amount"
                                    {{ $freightBooking->is_cancelled ? 'disabled' : '' }}
                                    {{ $is_invoice_generated ? 'readonly style=background-color:#e9ecef;' : '' }}>
                                {{-- <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Must be ≥ 0 and < TR Amount
                        </small> --}}
                                @error('damage_amount')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <label for="shortage_amount" class="form-label">Shortage Amount</label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control form-control-sm @error('shortage_amount') is-invalid @enderror"
                                    name="shortage_amount" id="shortage_amount"
                                    value="{{ old('shortage_amount', $freightBooking->shortage_amount) }}"
                                    placeholder="Enter shortage amount"
                                    {{ $freightBooking->is_cancelled ? 'disabled' : '' }}
                                    {{ $is_invoice_generated ? 'readonly style=background-color:#e9ecef;' : '' }}>
                                {{-- <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Must be ≥ 0 and < TR Amount
                        </small> --}}
                                @error('shortage_amount')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Hidden weight input and transporter TDS percentage -->
                    <input type="hidden" id="weight"
                        value="{{ $freightBooking->lr ? $freightBooking->lr->weight : 0 }}">
                    <input type="hidden" id="lr_amount"
                        value="{{ $freightBooking->lr ? ($freightBooking->lr->amount ?? 0) : 0 }}">
                    <input type="hidden" id="transporter_tds_percentage" name="transporter_tds_percentage"
                        value="{{ old('transporter_tds_percentage', $freightBooking->transporter_tds_percentage) }}">
                    <input type="hidden" id="pan_verified" name="pan_verified"
                        value="{{ old('pan_verified', $freightBooking->pan_verified) }}">
                    <input type="hidden" id="good_type" name="good_type"
                        value="{{ old('good_type', $freightBooking->good_type) }}">
                    <input type="hidden" name="lr_id" value="{{ $freightBooking->lr_id }}">

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea name="remark" id="remark" class="form-control @error('remark') is-invalid @enderror" rows="3"
                                placeholder="Enter any additional remarks or notes" {{ $freightBooking->is_cancelled ? 'disabled' : '' }}>{{ old('remark', $freightBooking->remark) }}</textarea>
                            @error('remark')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="card-footer">
                    <div>
                        @if ($freightBooking->is_cancelled)
                            <button class="btn btn-secondary me-2" type="button" disabled>
                                <i class="bi bi-x-circle"></i> Cannot Update - Cancelled
                            </button>
                        @else
                            <button class="btn btn-brand-primary me-2" type="button" id="showConfirmationBtn">
                                <i class="bi bi-check-circle"></i> Update Good Hire (Lorry Hire)
                            </button>
                        @endif
                        <a href="{{ route('freight_bookings.index') }}{{ \App\Support\ListingQueryString::currentSuffix(\App\Support\ListingQueryString::FREIGHT_BOOKING_KEYS) }}" class="btn btn-back">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Confirmation Modal for Good Hire (Lorry Hire) Update -->
    <div class="modal fade" id="updateConfirmationModal" tabindex="-1" aria-labelledby="updateConfirmationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="updateConfirmationModalLabel">
                        <i class="fas fa-info-circle me-2"></i> Confirm Good Hire (Lorry Hire) Update
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Please review the booking details below before confirming
                        your update.
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2"><i class="fas fa-truck me-2"></i> Good Receipt (Bilty) Information
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>LR Number:</strong> <span id="confirm-lr-number"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Transporter:</strong> <span id="confirm-transporter"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Truck:</strong> <span id="confirm-truck"></span></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2"><i class="fas fa-indian-rupee-sign me-2"></i> Financial Details
                            </h6>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong>TR Freight Rate:</strong> <span id="confirm-tr-rate"></span></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong>TR Amount:</strong> <span id="confirm-tr-amount"></span></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong>TDS Amount:</strong> <span id="confirm-tds-amount"></span></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong>Balance Amount:</strong> <span id="confirm-balance-amount"></span>
                            </p>
                        </div>
                        <div class="col-md-3" id="confirm-negative-adjusted-wrap">
                            <p class="mb-1"><strong>Negative Adjusted:</strong> <span id="confirm-negative-adjusted"></span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3" id="confirm-custom-amounts-row" style="display: none;">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2"><i class="fas fa-list-ul me-2"></i> Custom Amounts</h6>
                            <div id="confirm-custom-amounts-list"></div>
                            <p class="mb-0 mt-2"><strong>Custom amounts total:</strong> <span id="confirm-custom-amounts-total"></span></p>
                        </div>
                    </div>

                    <div class="row mb-3" id="confirm-delivery-status-row">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2"><i class="fas fa-clipboard-check me-2"></i> Status</h6>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Delivery Status:</strong> <span id="confirm-delivery-status"></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Cancellation Status:</strong> <span
                                    id="confirm-cancellation-status"></span></p>
                        </div>
                    </div>

                    <div class="row mb-3" id="confirm-additional-charges-row">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2"><i class="fas fa-plus-circle me-2"></i> Additional Charges</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Detention:</strong> <span id="confirm-detention"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Deviation:</strong> <span id="confirm-deviation"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Misc Expense:</strong> <span id="confirm-misc-expense"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Damage Amount:</strong> <span id="confirm-damage-amount"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Shortage Amount:</strong> <span id="confirm-shortage-amount"></span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3" id="confirm-remark-row">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2"><i class="fas fa-comment-alt me-2"></i> Remarks</h6>
                            <p class="mb-1" id="confirm-remark"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-brand-primary" id="confirmUpdateBtn">
                        <i class="fas fa-check-circle me-1"></i> Confirm Update
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Step 1: Confirmation Modal -->
    <div class="modal fade" id="askMemoModal" tabindex="-1" aria-labelledby="askMemoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="askMemoModalLabel">Memo ID Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Do you want to enter a Memo ID for this delivery?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="noMemoBtn">No, Continue Without Memo</button>
                    <button type="button" class="btn btn-primary" id="yesMemoBtn">Yes, Enter Memo ID</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Memo ID Entry -->
    <div class="modal fade" id="memoIdModal" tabindex="-1" aria-labelledby="memoIdModalLabel" aria-hidden="true"
        data-bs-backdrop="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memoIdModalLabel">Enter Memo ID for Delivery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> A Memo ID is required to mark this good hire (lorry hire) as delivered.
                    </div>
                    <form id="memoIdForm">
                        <div class="mb-3">
                            <label for="deliveryMemoId" class="form-label">Memo ID *</label>
                            <input type="text" class="form-control" id="deliveryMemoId" name="memo_id" required
                                placeholder="Enter memo ID" pattern="[A-Za-z0-9\-\/]+">
                            <small class="form-text text-muted">This will be saved to the good receipt (bilty) record.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-body" id="memoModalError" style="display: none;">
                    <div class="alert alert-danger" id="memoErrorMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveMemoId()" id="saveMemoBtn">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                            style="display: none;"></span>
                        Save Memo ID
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewCancelledModal" tabindex="-1" aria-labelledby="viewCancelledModalLabel"
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
                        alert('Memo ID saved successfully. Freight booking marked as delivered.');

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

                    document.getElementById('confirm-tr-rate').textContent = '' + parseFloat(trRate)
                        .toFixed(2) + ' per ton';
                    document.getElementById('confirm-tr-amount').textContent = '' + parseFloat(trAmount)
                        .toFixed(2);
                    document.getElementById('confirm-tds-amount').textContent = '' + parseFloat(tdsAmount)
                        .toFixed(2);
                    document.getElementById('confirm-balance-amount').textContent = '' + parseFloat(
                        balanceAmount).toFixed(2);

                    const negativeAdjustedAmount = parseFloat(document.getElementById('negative_adjusted_amount')?.value || 0) || 0;
                    const negativeAdjustedWrap = document.getElementById('confirm-negative-adjusted-wrap');
                    if (negativeAdjustedWrap) {
                        negativeAdjustedWrap.style.display = negativeAdjustedAmount > 0 ? 'block' : 'none';
                        document.getElementById('confirm-negative-adjusted').textContent = '' + negativeAdjustedAmount.toFixed(2);
                    }

                    const customAmountRows = document.querySelectorAll('#custom-amounts-body .custom-amount-row');
                    const customAmountsRow = document.getElementById('confirm-custom-amounts-row');
                    const customAmountsList = document.getElementById('confirm-custom-amounts-list');
                    if (customAmountsRow && customAmountsList) {
                        if (customAmountRows.length > 0) {
                            customAmountsRow.style.display = 'flex';
                            customAmountsList.innerHTML = '';
                            let customTotal = 0;

                            customAmountRows.forEach(function(row) {
                                const description = row.querySelector('[name*="[description]"], .custom-amount-description')?.value || '';
                                const amount = parseFloat(row.querySelector('.custom-amount-value')?.value || 0) || 0;
                                customTotal += amount;

                                const item = document.createElement('p');
                                item.className = 'mb-1';
                                item.innerHTML = '<strong>' + description + ':</strong> ' + amount.toFixed(2);
                                customAmountsList.appendChild(item);
                            });

                            document.getElementById('confirm-custom-amounts-total').textContent = '' + customTotal.toFixed(2);
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
                        additionalChargesRow.style.display = 'flex';

                        const detention = document.getElementById('detention').value || '0.00';
                        const deviation = document.getElementById('deviation').value || '0.00';
                        const miscExpense = document.getElementById('misc_expense').value || '0.00';
                        const damageAmount = document.getElementById('damage_amount').value || '0.00';
                        const shortageAmount = document.getElementById('shortage_amount').value || '0.00';

                        document.getElementById('confirm-detention').textContent = '' + parseFloat(
                            detention).toFixed(2);
                        document.getElementById('confirm-deviation').textContent = '' + parseFloat(
                            deviation).toFixed(2);
                        document.getElementById('confirm-misc-expense').textContent = '' + parseFloat(
                            miscExpense).toFixed(2);
                        document.getElementById('confirm-damage-amount').textContent = '' + parseFloat(
                            damageAmount).toFixed(2);
                        document.getElementById('confirm-shortage-amount').textContent = '' + parseFloat(
                            shortageAmount).toFixed(2);
                    } else {
                        additionalChargesRow.style.display = 'none';
                    }

                    // Remarks
                    const remark = document.getElementById('remark').value;
                    const remarkRow = document.getElementById('confirm-remark-row');
                    const remarkElement = document.getElementById('confirm-remark');

                    if (remark && remark.trim() !== '') {
                        remarkRow.style.display = 'flex';
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
                    totalDisplay.textContent = total.toFixed(2);
                }

                return total;
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

                if (!addBtn || !body || !template) {
                    return;
                }

                addBtn.addEventListener('click', function() {
                    const clone = template.content.cloneNode(true);
                    body.appendChild(clone);
                    reindexCustomAmountRows();
                    getCustomAmountsTotal();
                    calculateTdsAndBalance();
                });

                body.addEventListener('click', function(event) {
                    const removeBtn = event.target.closest('.remove-custom-amount-row');
                    if (!removeBtn) {
                        return;
                    }

                    removeBtn.closest('.custom-amount-row')?.remove();
                    reindexCustomAmountRows();
                    getCustomAmountsTotal();
                    calculateTdsAndBalance();
                });

                body.addEventListener('input', function(event) {
                    if (event.target.classList.contains('custom-amount-value')) {
                        getCustomAmountsTotal();
                        calculateTdsAndBalance();
                    }
                });

                getCustomAmountsTotal();
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

                // Calculate Balance Amount
                const balance = trAmount - advanceAmount - tdsAmount + detention + deviation -
                    miscExpense - damageAmount - shortageAmount + customAmountsTotal + negativeAdjustedAmount;
                if (balanceAmountInput) {
                    balanceAmountInput.value = balance.toFixed(2);
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

            // Handle is_cancelled toggle with confirmation
            const isCancelledToggle = document.getElementById('is_cancelled');
            if (isCancelledToggle) {
                isCancelledToggle.addEventListener('change', function() {
                    if (this.checked) {
                        // Show confirmation dialog
                        const confirmed = confirm(
                            'Are you sure you want to cancel this good hire (lorry hire)?\n\n' +
                            '⚠️ WARNING: This action will:\n' +
                            '• Mark the good hire (lorry hire) as cancelled\n' +
                            '• Move the related Good Receipt (Bilty) status back to "Pending"\n' +
                            '• This change cannot be undone easily\n\n' +
                            'Click OK to proceed or Cancel to abort.'
                        );

                        if (!confirmed) {
                            // User cancelled, uncheck the toggle
                            this.checked = false;
                            return;
                        }
                    } else {
                        // Unchecking cancelled - show confirmation
                        const confirmed = confirm(
                            'Are you sure you want to uncancel this good hire (lorry hire)?\n\n' +
                            'This will restore the good hire (lorry hire) to its previous state.'
                        );

                        if (!confirmed) {
                            // User cancelled, keep it checked
                            this.checked = true;
                            return;
                        }
                    }
                });
            }
        });
    </script>
@endsection
