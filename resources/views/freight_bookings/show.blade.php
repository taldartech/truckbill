@extends('layouts.app')

@section('content')
    @php
        $lr = $freightBooking->lr;
        $listingSuffix = \App\Support\ListingQueryString::currentSuffix(\App\Support\ListingQueryString::FREIGHT_BOOKING_KEYS);
        $isCombined = (bool) $freightBooking->linked_lr_id;
        $activeLinked = $isCombined
            ? $linkedFreightBookings->filter(fn ($fr) => ! $fr->is_cancelled)->values()
            : collect();

        if ($isCombined && $activeLinked->isNotEmpty()) {
            $trAmount = (float) $activeLinked->sum('tr_amount');
            $advanceAmount = (float) $activeLinked->sum('advance_amount');
            $tdsAmount = (float) $activeLinked->sum('tds_amount');
            $negativeAdjusted = (float) $activeLinked->sum('negative_adjusted_amount');
            $customTotal = (float) $activeLinked->sum(fn ($fr) => $fr->customAmountsTotal());
            $balanceAmount = (float) $activeLinked->sum('balance_amount');
            $trRate = null;
        } else {
            $trAmount = (float) ($freightBooking->tr_amount ?? 0);
            $advanceAmount = (float) ($freightBooking->advance_amount ?? 0);
            $tdsAmount = (float) ($freightBooking->tds_amount ?? 0);
            $negativeAdjusted = (float) ($freightBooking->negative_adjusted_amount ?? 0);
            $customTotal = (float) $freightBooking->customAmountsTotal();
            $balanceAmount = (float) ($freightBooking->balance_amount ?? 0);
            $trRate = (float) ($freightBooking->tr_fr_rate ?? 0);
        }

        $deductions = $tdsAmount + $advanceAmount + $negativeAdjusted;
        $barScale = max($trAmount, $deductions, 1);
        $hireBarPct = min(100, ($trAmount / $barScale) * 100);
        $deductionBarPct = min(100, ($deductions / $barScale) * 100);
        $hasBalanceDue = $balanceAmount > 0;
        $deliveryStatus = $lr?->delivery_status ?? 'Pending';
        $deliveryBadge = match ($deliveryStatus) {
            'Delivered' => 'bg-success',
            'Cancelled' => 'bg-danger',
            'In-Progress' => 'bg-info',
            default => 'bg-warning text-dark',
        };
    @endphp

    <link rel="stylesheet" href="{{ asset('css/fb-show-redesigned.css') }}?v={{ file_exists(public_path('css/fb-show-redesigned.css')) ? filemtime(public_path('css/fb-show-redesigned.css')) : time() }}">

    @if (session('success'))
        <div class="alert alert-success mt-2" style="max-width: 1200px; margin-left: auto; margin-right: auto;">
            {{ session('success') }}
        </div>
    @endif

    <div class="fb-detail-page app-index-page">
        @if ($freightBooking->is_cancelled)
            <div class="alert alert-danger mb-3" role="alert">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-x-circle-fill fs-5 mt-1"></i>
                    <div>
                        <strong>Cancelled good hire</strong>
                        <div class="small mb-0">This booking cannot be modified. Contact an administrator for any changes.</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-8 fb-detail-main">
                <div class="card">
                    <div class="card-header fb-detail-card-header">
                        <strong class="fb-detail-card-title">
                            Good Hire Information (#{{ $freightBooking->id }})
                            @if ($freightBooking->is_cancelled)
                                <span class="badge bg-danger ms-1">Cancelled</span>
                            @endif
                            @if ($isCombined)
                                <span class="badge bg-primary ms-1">Combined</span>
                            @endif
                        </strong>
                        <div class="fb-detail-actions">
                            <a href="{{ route('freight_bookings.index') }}{{ $listingSuffix }}"
                                class="btn btn-sm btn-icon btn-action-back"
                                title="Back to list">
                                <i class="bi bi-arrow-left"></i>
                            </a>

                            @if (! $freightBooking->is_cancelled)
                                @if ($freightBooking->linked_lr_id && isset($combinedTotalBalance) && $combinedTotalBalance > 0 && $linkedFreightBookings->isNotEmpty())
                                    <button type="button" class="btn btn-sm btn-icon btn-action-pay"
                                        data-bs-toggle="modal" data-bs-target="#combinedPaymentModal"
                                        title="Combined payment">
                                        <i class="bi bi-wallet2"></i>
                                    </button>
                                @elseif ($freightBooking->balance_amount > 0 && ! $freightBooking->linked_lr_id)
                                    <button type="button" class="btn btn-sm btn-icon btn-action-pay"
                                        onclick="openVoucherPaymentModal({{ $freightBooking->id }})"
                                        title="Voucher payment">
                                        <i class="bi bi-wallet2"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-icon btn-action-pay" disabled
                                        title="{{ $freightBooking->linked_lr_id ? 'Use combined payment for linked bookings' : 'No balance to pay' }}">
                                        <i class="bi bi-wallet2"></i>
                                    </button>
                                @endif

                                <a href="{{ route('freight_bookings.edit', $freightBooking->id) }}{{ $listingSuffix }}"
                                    class="btn btn-sm btn-icon btn-action-edit" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @else
                                <button type="button" class="btn btn-sm btn-icon btn-action-pay" disabled title="Cancelled">
                                    <i class="bi bi-wallet2"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-icon btn-action-edit" disabled title="Cancelled">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endif

                            @if ($lr)
                                @php
                                    $voucherPrintTitle = $freightBooking->voucher_last_printed_at
                                        ? 'Last printed: '.$freightBooking->voucher_last_printed_at->format('d-m-Y h:i A')
                                        : 'Print voucher challan';
                                @endphp
                                <a href="{{ route('reports.voucher-receipt-export', ['lr_id' => $lr->id]) }}"
                                    class="btn btn-sm btn-icon btn-action-print voucher-print-btn {{ $freightBooking->voucher_printed ? 'is-printed' : '' }}"
                                    data-freight-booking-id="{{ $freightBooking->id }}"
                                    data-mark-url="{{ route('reports.voucher-receipt-mark-printed') }}"
                                    title="{{ $voucherPrintTitle }}">
                                    <i class="bi bi-printer"></i>
                                </a>
                            @endif

                            <a href="{{ route('freight_bookings.settlement', $freightBooking->id) }}"
                                class="btn btn-sm btn-icon"
                                style="color: #0284c7; border: 1px solid #bae6fd; background-color: #f0f9ff;"
                                title="Broker Settlement Statement & Bill Verification"
                                data-bs-toggle="tooltip">
                                <i class="bi bi-file-earmark-ruled"></i>
                            </a>
                        </div>
                    </div>

                    <div class="card-body fb-info-grid">
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">LR Number</span>
                            <div class="fb-info-grid__value fb-info-grid__value--accent">{{ $lr->lr_number ?? 'N/A' }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">GR Date</span>
                            <div class="fb-info-grid__value">
                                {{ $lr && $lr->gr_date ? date('d-m-Y', strtotime($lr->gr_date)) : 'N/A' }}
                            </div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Goods</span>
                            <div class="fb-info-grid__value">{{ $freightBooking->good_type ?? ($lr->good_type ?? 'N/A') }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Weight</span>
                            <div class="fb-info-grid__value">{{ $lr ? number_format($lr->weight ?? 0, 3).' MT' : 'N/A' }}</div>
                        </div>

                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Truck</span>
                            <div class="fb-info-grid__value fb-info-grid__value--accent">
                                {{ $lr && $lr->truck ? $lr->truck->truck_number : 'N/A' }}
                            </div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Payee</span>
                            <div class="fb-info-grid__value">
                                {{ $lr && $lr->transporter ? $lr->transporter->name : 'N/A' }}
                            </div>
                            @if ($lr && $lr->transporter)
                                <div class="fb-info-grid__sub">
                                    {{ $lr->transporter->is_owner ? 'Owner' : 'Agency' }}
                                    · TDS {{ number_format($lr->transporter->tds_percentage ?? 0, 2) }}%
                                </div>
                            @endif
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">E-Way Bill</span>
                            <div class="fb-info-grid__value">{{ $freightBooking->eway_bill_number ?: '—' }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">E-Way Expiry</span>
                            <div class="fb-info-grid__value">
                                {{ $freightBooking->eway_bill_expiry ? date('d-m-Y', strtotime($freightBooking->eway_bill_expiry)) : '—' }}
                            </div>
                        </div>

                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Customer</span>
                            <div class="fb-info-grid__value">{{ $lr && $lr->customer ? $lr->customer->name : 'N/A' }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Consignor</span>
                            <div class="fb-info-grid__value">{{ $lr && $lr->consignor ? $lr->consignor->name : 'N/A' }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Consignee</span>
                            <div class="fb-info-grid__value">{{ $lr && $lr->consignee ? $lr->consignee->name : 'N/A' }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Reference</span>
                            <div class="fb-info-grid__value">{{ $lr && $lr->reference ? $lr->reference->name : '—' }}</div>
                        </div>

                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Delivered</span>
                            <div class="fb-info-grid__value">
                                @if ($freightBooking->is_delivered)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-warning text-dark">No</span>
                                @endif
                            </div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">PAN Verified</span>
                            <div class="fb-info-grid__value">
                                @if ($freightBooking->pan_verified)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-warning text-dark">No</span>
                                @endif
                            </div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Delivery Status</span>
                            <div class="fb-info-grid__value">
                                <span class="badge {{ $deliveryBadge }}">{{ $deliveryStatus }}</span>
                            </div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Cancelled</span>
                            <div class="fb-info-grid__value">
                                @if ($freightBooking->is_cancelled)
                                    <span class="badge bg-danger">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </div>
                        </div>

                        @if (filled($freightBooking->remark))
                            <div class="fb-info-grid__item fb-info-grid__item--full">
                                <span class="fb-info-grid__label">Remark</span>
                                <div class="fb-info-grid__value" style="white-space: pre-wrap;">{{ $freightBooking->remark }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($lr)
                    <div class="card mt-3">
                        <div class="card-header fb-route-header">
                            <strong>Linked Bilty &amp; Route</strong>
                            <span class="fb-route-header__totals">
                                LR ₹{{ number_format($lr->amount ?? 0, 2) }}
                                · Rate ₹{{ number_format($lr->fr_rate ?? 0, 2) }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="fb-route-preview">
                                <strong>Route:</strong>
                                {{ $lr->source_city ?? '—' }}{{ ! empty($lr->source_state) ? ', '.$lr->source_state : '' }}
                                →
                                {{ $lr->destination_city ?? '—' }}{{ ! empty($lr->destination_state) ? ', '.$lr->destination_state : '' }}
                            </div>
                            <div class="fb-route-facts">
                                <div class="fb-route-facts__item">
                                    <span class="fb-info-grid__label">From</span>
                                    <div class="fb-info-grid__value">
                                        {{ $lr->source_city ?? 'N/A' }}{{ ! empty($lr->source_state) ? ', '.$lr->source_state : '' }}
                                    </div>
                                </div>
                                <div class="fb-route-facts__item">
                                    <span class="fb-info-grid__label">To</span>
                                    <div class="fb-info-grid__value">
                                        {{ $lr->destination_city ?? 'N/A' }}{{ ! empty($lr->destination_state) ? ', '.$lr->destination_state : '' }}
                                    </div>
                                </div>
                                <div class="fb-route-facts__item">
                                    <span class="fb-info-grid__label">Customer</span>
                                    <div class="fb-info-grid__value">{{ $lr->customer->name ?? 'N/A' }}</div>
                                </div>
                                <div class="fb-route-facts__item">
                                    <span class="fb-info-grid__label">Payee</span>
                                    <div class="fb-info-grid__value">{{ $lr->transporter->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($isCombined && $activeLinked->isNotEmpty())
                    <div class="card mt-3">
                        <div class="card-header">
                            <strong>Combined Hire Breakdown</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 fb-combined-table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            @foreach ($activeLinked as $fr)
                                                <th>{{ $fr->lr->lr_number ?? ('#'.$fr->id) }}</th>
                                            @endforeach
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>TR Rate</td>
                                            @foreach ($activeLinked as $fr)
                                                <td>{{ number_format($fr->tr_fr_rate, 2) }}</td>
                                            @endforeach
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>TR Amount</td>
                                            @foreach ($activeLinked as $fr)
                                                <td>{{ number_format($fr->tr_amount, 2) }}</td>
                                            @endforeach
                                            <td>{{ number_format($trAmount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Advance</td>
                                            @foreach ($activeLinked as $fr)
                                                <td>{{ number_format($fr->advance_amount, 2) }}</td>
                                            @endforeach
                                            <td>{{ number_format($advanceAmount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>TDS</td>
                                            @foreach ($activeLinked as $fr)
                                                <td>{{ number_format($fr->tds_amount, 2) }}</td>
                                            @endforeach
                                            <td>{{ number_format($tdsAmount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Negative Adj.</td>
                                            @foreach ($activeLinked as $fr)
                                                <td>{{ number_format($fr->negative_adjusted_amount ?? 0, 2) }}</td>
                                            @endforeach
                                            <td>{{ number_format($negativeAdjusted, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Custom Total</td>
                                            @foreach ($activeLinked as $fr)
                                                <td>{{ number_format($fr->customAmountsTotal(), 2) }}</td>
                                            @endforeach
                                            <td>{{ number_format($customTotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Balance</td>
                                            @foreach ($activeLinked as $fr)
                                                <td>{{ number_format($fr->balance_amount, 2) }}</td>
                                            @endforeach
                                            <td>{{ number_format($balanceAmount, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($freightBooking->customAmounts->isNotEmpty())
                    <div class="card mt-3">
                        <div class="card-header">
                            <strong>Custom Amounts</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($freightBooking->customAmounts as $customAmount)
                                            <tr>
                                                <td>{{ $customAmount->description }}</td>
                                                <td class="text-end {{ (float) $customAmount->amount < 0 ? 'text-danger' : '' }}">
                                                    {{ number_format($customAmount->amount, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th>Custom amounts total</th>
                                            <th class="text-end">{{ number_format($freightBooking->customAmountsTotal(), 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($freightBooking->is_delivered)
                    <div class="card mt-3">
                        <div class="card-header">
                            <strong>Additional Charges</strong>
                        </div>
                        <div class="card-body fb-info-grid">
                            <div class="fb-info-grid__item">
                                <span class="fb-info-grid__label">Detention</span>
                                <div class="fb-info-grid__value">₹{{ number_format($freightBooking->detention ?? 0, 2) }}</div>
                            </div>
                            <div class="fb-info-grid__item">
                                <span class="fb-info-grid__label">Deviation</span>
                                <div class="fb-info-grid__value">₹{{ number_format($freightBooking->deviation ?? 0, 2) }}</div>
                            </div>
                            <div class="fb-info-grid__item">
                                <span class="fb-info-grid__label">Misc. Expense</span>
                                <div class="fb-info-grid__value">₹{{ number_format($freightBooking->misc_expense ?? 0, 2) }}</div>
                            </div>
                            <div class="fb-info-grid__item">
                                <span class="fb-info-grid__label">Damage</span>
                                <div class="fb-info-grid__value">₹{{ number_format($freightBooking->damage_amount ?? 0, 2) }}</div>
                            </div>
                            <div class="fb-info-grid__item">
                                <span class="fb-info-grid__label">Shortage</span>
                                <div class="fb-info-grid__value">₹{{ number_format($freightBooking->shortage_amount ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card mt-3 fb-compact-card">
                    <div class="card-header">
                        <strong>Payment History</strong>
                        @if ($payments->count() > 0)
                            <span class="fb-compact-card__count">{{ $payments->count() }}</span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if ($payments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0 fb-compact-table text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Voucher</th>
                                            <th>Amount</th>
                                            <th>Type</th>
                                            <th>Mode</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($payments as $payment)
                                            <tr>
                                                <td>
                                                    @if ($payment instanceof \App\Models\VoucherLinked)
                                                        <span class="badge bg-info">Combined</span>
                                                    @else
                                                        {{ $payment->voucher_number ?? 'N/A' }}
                                                    @endif
                                                </td>
                                                <td>₹{{ number_format($payment->amount, 2) }}</td>
                                                <td>{{ $payment->payment_type ?? 'N/A' }}</td>
                                                <td>
                                                    {{ $payment->payment_mode ?? 'N/A' }}
                                                    @if ($payment->pump)
                                                        <div class="fb-info-grid__sub">{{ $payment->pump->pump_name }}</div>
                                                    @endif
                                                </td>
                                                <td>{{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('d-m-Y') : 'N/A' }}</td>
                                                <td>
                                                    @if ($payment->payment_status === 'paid')
                                                        <span class="badge bg-success">Paid</span>
                                                    @elseif ($payment->payment_status === 'pending')
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    @elseif ($payment->payment_status === 'in-progress')
                                                        <span class="badge bg-info">In Progress</span>
                                                    @else
                                                        <span class="badge bg-secondary">Unknown</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="fb-compact-empty">No payment history found.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4 fb-detail-side">
                <div class="card fb-financial-summary">
                    <div class="fb-financial-summary__header">
                        <div>
                            <div class="fb-financial-summary__title-row d-flex align-items-center gap-2 flex-wrap">
                                <strong class="fb-financial-summary__title">Financial Summary</strong>
                                <div class="fb-financial-summary__badge {{ $hasBalanceDue ? 'is-due' : 'is-clear' }}">
                                    {{ $hasBalanceDue ? 'Balance due' : 'Cleared' }}
                                    ₹{{ number_format(abs($balanceAmount), 2) }}
                                </div>
                            </div>
                            <span class="fb-financial-summary__subtitle">
                                {{ $isCombined ? 'Combined hire vs deductions' : 'Hire vs deductions' }}
                            </span>
                        </div>
                    </div>
                    <div class="fb-financial-summary__body">
                        <div class="fb-financial-summary__stats">
                            <div class="fb-financial-summary__stat">
                                <span class="fb-financial-summary__stat-label">TR Amount</span>
                                <span class="fb-financial-summary__stat-value">₹{{ number_format($trAmount, 2) }}</span>
                            </div>
                            <div class="fb-financial-summary__stat">
                                <span class="fb-financial-summary__stat-label">Deductions</span>
                                <span class="fb-financial-summary__stat-value fb-financial-summary__stat-value--muted">
                                    ₹{{ number_format($deductions, 2) }}
                                </span>
                            </div>
                            <div class="fb-financial-summary__stat fb-financial-summary__stat--result">
                                <span class="fb-financial-summary__stat-label">Balance</span>
                                <span class="fb-financial-summary__stat-value is-clear">
                                    ₹{{ number_format($balanceAmount, 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="fb-financial-summary__bar" role="img"
                            aria-label="TR amount ₹{{ number_format($trAmount, 2) }}, deductions ₹{{ number_format($deductions, 2) }}, balance ₹{{ number_format($balanceAmount, 2) }}">
                            <div class="fb-financial-summary__bar-hire" style="width: {{ $hireBarPct }}%"></div>
                            <div class="fb-financial-summary__bar-deduction" style="width: {{ $deductionBarPct }}%"></div>
                        </div>
                        <div class="fb-financial-summary__legend">
                            <span><i class="fb-financial-summary__dot fb-financial-summary__dot--hire"></i> TR Amount</span>
                            <span><i class="fb-financial-summary__dot fb-financial-summary__dot--deduction"></i> Deductions</span>
                        </div>

                        <div class="fb-financial-summary__rows">
                            @if (! $isCombined)
                                <div class="fb-financial-summary__row">
                                    <span>TR Freight Rate</span>
                                    <strong>₹{{ number_format($trRate ?? 0, 2) }}</strong>
                                </div>
                            @endif
                            <div class="fb-financial-summary__row">
                                <span>Advance</span>
                                <strong>₹{{ number_format($advanceAmount, 2) }}</strong>
                            </div>
                            <div class="fb-financial-summary__row">
                                <span>TDS Amount</span>
                                <strong>₹{{ number_format($tdsAmount, 2) }}</strong>
                            </div>
                            <div class="fb-financial-summary__row">
                                <span>Negative Adjusted Amount</span>
                                <strong class="{{ $negativeAdjusted > 0 ? '' : 'text-muted' }}">₹{{ number_format($negativeAdjusted, 2) }}</strong>
                            </div>
                            <div class="fb-financial-summary__row">
                                <span>Custom Amounts</span>
                                <strong class="{{ $customTotal != 0 ? '' : 'text-muted' }}">₹{{ number_format($customTotal, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3 fb-side-meta">
                    <div class="card-header">
                        <strong>Record Info</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <span class="fb-side-meta__label">Created</span>
                                <div class="fb-side-meta__value">
                                    {{ $freightBooking->created_at ? $freightBooking->created_at->format('d-m-Y h:i A') : 'N/A' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <span class="fb-side-meta__label">Last Updated</span>
                                <div class="fb-side-meta__value">
                                    {{ $freightBooking->updated_at ? $freightBooking->updated_at->format('d-m-Y h:i A') : 'N/A' }}
                                </div>
                            </div>
                        </div>
                        <div class="fb-side-meta__users pt-2">
                            <div class="row g-2">
                                <div class="col-6 fb-side-meta__user">
                                    <span class="fb-side-meta__label">Created By</span>
                                    @if ($freightBooking->creator)
                                        <div class="fb-side-meta__value">{{ $freightBooking->creator->name }}</div>
                                        <div class="fb-side-meta__email">{{ $freightBooking->creator->email }}</div>
                                    @else
                                        <div class="fb-side-meta__value text-muted">Not available</div>
                                    @endif
                                </div>
                                <div class="col-6 fb-side-meta__user">
                                    <span class="fb-side-meta__label">Last Updated By</span>
                                    @if ($freightBooking->updater)
                                        <div class="fb-side-meta__value">{{ $freightBooking->updater->name }}</div>
                                        <div class="fb-side-meta__email">{{ $freightBooking->updater->email }}</div>
                                    @else
                                        <div class="fb-side-meta__value text-muted">Not available</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Voucher Payment Modal -->
    <!-- Voucher Print Confirmation -->
    <div class="modal fade fb-pay-modal" id="voucherPrintConfirmModal" tabindex="-1"
        aria-labelledby="voucherPrintConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voucherPrintConfirmModalLabel">
                        <i class="bi bi-printer"></i>
                        Print Confirmation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fb-confirm-note">
                        <i class="bi bi-info-circle"></i>
                        Did you print voucher challan successfully?
                    </div>
                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-check2-circle"></i> Mark as printed
                        </div>
                        <div class="fb-pay-panel__body">
                            <p class="mb-0" style="font-size: 0.84rem; color: #475569;">
                                Choose <strong>Yes, Printed</strong> to record this print on the booking.
                                Choose <strong>No</strong> if the print did not complete.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> No
                    </button>
                    <button type="button" class="btn btn-brand-primary" id="voucherPrintConfirmYesBtn">
                        <i class="bi bi-check-lg me-1"></i> Yes, Printed
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade fb-pay-modal" id="voucherPaymentModal" tabindex="-1" aria-labelledby="voucherPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voucherPaymentModalLabel">
                        <i class="bi bi-wallet2"></i>
                        Voucher Payment — FR #{{ $freightBooking->id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-clock-history"></i> Previous Payments
                        </div>
                        <div class="fb-pay-panel__body--flush" id="previousPaymentsTable">
                            <div class="fb-pay-loading">
                                <div class="spinner-border spinner-border-sm me-1" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading payment history...
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-truck"></i> Transporter Details
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-facts fb-pay-facts--7">
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Transporter</span>
                                    <div class="fb-pay-fact__value" id="lrTransporter">
                                        {{ $freightBooking->lr && $freightBooking->lr->transporter ? $freightBooking->lr->transporter->name : 'N/A' }}
                                    </div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Mobile</span>
                                    <div class="fb-pay-fact__value" id="transporterMobile">
                                        {{ $freightBooking->lr && $freightBooking->lr->transporter ? ($freightBooking->lr->transporter->mobile_number ?? 'N/A') : 'N/A' }}
                                    </div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Short Name</span>
                                    <div class="fb-pay-fact__value" id="transporterShortName">
                                        {{ $freightBooking->lr && $freightBooking->lr->transporter ? ($freightBooking->lr->transporter->short_name ?? 'N/A') : 'N/A' }}
                                    </div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Beneficiary ID</span>
                                    <div class="fb-pay-fact__value" id="transporterBeneficiaryId">
                                        {{ $freightBooking->lr && $freightBooking->lr->transporter ? ($freightBooking->lr->transporter->beneficiary_id ?? 'N/A') : 'N/A' }}
                                    </div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">TDS Percentage</span>
                                    <div class="fb-pay-fact__value" id="tdsPercentage">
                                        {{ $freightBooking->transporter_tds_percentage !== null ? number_format($freightBooking->transporter_tds_percentage, 2).'%' : 'N/A' }}
                                    </div>
                                </div>
                                <div class="fb-pay-fact">
                                    <span class="fb-pay-fact__label">Truck Number</span>
                                    <div class="fb-pay-fact__value fb-pay-fact__value--accent" id="truckNumber">
                                        {{ $freightBooking->lr && $freightBooking->lr->truck ? $freightBooking->lr->truck->truck_number : 'N/A' }}
                                    </div>
                                </div>
                                @php
                                    $modalAdvanceAmount = $freightBooking->lr && $freightBooking->lr->transporter
                                        ? ($freightBooking->lr->transporter->advance_amount ?? 0)
                                        : 0;
                                @endphp
                                @if ($modalAdvanceAmount > 0)
                                    <div class="fb-pay-fact">
                                        <span class="fb-pay-fact__label">Advance Amount</span>
                                        <div class="fb-pay-fact__value fb-pay-fact__value--warn" id="advanceAmount">
                                            {{ number_format($modalAdvanceAmount, 2) }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel fb-pay-panel--alloc">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-currency-rupee"></i> Payment Allocation
                        </div>
                        <div class="fb-pay-panel__body--flush">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 fb-pay-table">
                                    <thead>
                                        <tr>
                                            <th>FR No.</th>
                                            <th>LR Number</th>
                                            <th>Balance Amount</th>
                                            <th>Payment Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>#{{ $freightBooking->id }}</td>
                                            <td>{{ $freightBooking->lr ? $freightBooking->lr->lr_number : 'N/A' }}</td>
                                            <td>₹{{ number_format($freightBooking->balance_amount ?? 0, 2) }}</td>
                                            <td style="max-width: 10rem;">
                                                <input type="number" class="form-control allocated-input"
                                                    data-fb-id="{{ $freightBooking->id }}" step="0.01" min="0.01"
                                                    max="{{ $freightBooking->balance_amount ?? 0 }}"
                                                    oninput="validatePaymentAmount(this); updateTotalPayment();" onchange="updateTotalPayment()">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="fb-pay-total">
                                <span>Total Payment Amount</span>
                                <strong>₹<span id="totalPaymentAmount">0.00</span></strong>
                            </div>
                        </div>
                    </div>

                    <div class="fb-pay-panel">
                        <div class="fb-pay-panel__head">
                            <i class="bi bi-pencil-square"></i> Payment Details
                        </div>
                        <div class="fb-pay-panel__body">
                            <div class="fb-pay-fields fb-pay-fields--4">
                                <div>
                                    <label for="paymentType" class="form-label">Payment Type *</label>
                                    <select class="form-select" id="paymentType" required>
                                        <option value="">Select Payment Type</option>
                                        @foreach ($paymentTypes as $type)
                                            <option value="{{ $type->name }}" data-id="{{ $type->id }}">
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="paymentMode" class="form-label">Payment Mode *</label>
                                    <select class="form-select" id="paymentMode" required>
                                        <option value="">Select Payment Mode</option>
                                        @foreach ($paymentModes as $mode)
                                            <option value="{{ $mode->name }}" data-id="{{ $mode->id }}">
                                                {{ $mode->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="paymentDate" class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" name="date" id="paymentDate"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                                <div>
                                    <label for="paymentRemarks" class="form-label">Remarks</label>
                                    <input type="text" class="form-control" id="paymentRemarks" placeholder="Enter remarks">
                                </div>
                                <div id="pumpNameContainer" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="pumpName" class="form-label mb-0">Pump Name *</label>
                                        <a href="javascript:void(0)"
                                            class="lrf-quick-add js-open-quick-add-pump"
                                            data-pump-select="pumpName">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </a>
                                    </div>
                                    <select class="form-select" id="pumpName">
                                        <option value="">Select Pump</option>
                                        @foreach ($pumps as $pump)
                                            <option value="{{ $pump->id }}">{{ $pump->pump_name }}
                                                {{ $pump->contact_name ? '('.$pump->contact_name.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success btn-sm" onclick="submitVoucherPayment()">
                        <i class="bi bi-check-lg me-1"></i> Submit Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Function to open voucher payment modal for single freight booking
        function openVoucherPaymentModal(freightBookingId) {
            const paymentDate = document.getElementById('paymentDate');
            if (paymentDate && !paymentDate.value) {
                paymentDate.value = new Date().toISOString().slice(0, 10);
            }

            const modal = new bootstrap.Modal(document.getElementById('voucherPaymentModal'));
            modal.show();

            loadPaymentHistory([freightBookingId]);
            updatePaymentTypeFromAmounts();
            updateTotalPayment();
        }

        function todayDateInputValue() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        // Function to load payment history
        function loadPaymentHistory(freightBookingIds) {
            const tableContainer = document.getElementById('previousPaymentsTable');

            // Show loading state
            tableContainer.innerHTML = `
        <div class="fb-pay-loading">
            <div class="spinner-border spinner-border-sm me-1" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            Loading payment history...
        </div>
    `;

            // Make AJAX request to get payment history
            fetch('/freight_bookings/payment-history', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        freight_booking_ids: freightBookingIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.payments && data.payments.length > 0) {
                        // Build table HTML
                        let tableHtml = `
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 fb-pay-table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>FR No.</th>
                                <th>Voucher Number</th>
                                <th>Amount</th>
                                <th>Payment Type</th>
                                <th>Payment Mode</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

                        data.payments.forEach(payment => {
                            const statusBadge = payment.payment_status === 'paid' ? 'bg-success' :
                                payment.payment_status === 'pending' ? 'bg-warning text-dark' :
                                payment.payment_status === 'in progress' ? 'bg-info' : 'bg-secondary';
                            const pumpName = payment.pump ? (payment.pump.pump_name || '') : '';
                            const paymentModeDisplay = pumpName ?
                                `${payment.payment_mode || 'N/A'}<div class="fb-info-grid__sub">${pumpName}</div>` :
                                (payment.payment_mode || 'N/A');

                            tableHtml += `
                                <tr>
                                    <td>#${payment.id}</td>
                                    <td>#${payment.freight_booking_id}</td>
                                    <td>${payment.voucher_number || 'N/A'}</td>
                                    <td>₹${parseFloat(payment.amount || 0).toFixed(2)}</td>
                                    <td>${payment.payment_type || 'N/A'}</td>
                                    <td>${paymentModeDisplay}</td>
                                    <td>${new Date(payment.date).toLocaleDateString('en-IN')}</td>
                                    <td><span class="badge ${statusBadge}">${payment.payment_status || 'N/A'}</span></td>
                                </tr>
                            `;
                        });

                        tableHtml += `
                        </tbody>
                    </table>
                </div>
            `;

                        tableContainer.innerHTML = tableHtml;
                    } else {
                        tableContainer.innerHTML = '<div class="fb-pay-empty">No previous payments found.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading payment history:', error);
                    tableContainer.innerHTML = '<div class="fb-pay-empty text-danger">Error loading payment history.</div>';
                });
        }

        // Function to validate payment amount in real-time
        function validatePaymentAmount(input) {
            const value = parseFloat(input.value) || 0;
            const min = parseFloat(input.min) || 0;
            const max = parseFloat(input.max) || 0;

            // Remove any existing validation messages
            const existingFeedback = input.nextElementSibling;
            if (existingFeedback && existingFeedback.classList.contains('invalid-feedback')) {
                existingFeedback.remove();
            }

            // Reset validation classes
            input.classList.remove('is-invalid', 'is-valid');

            // Validate amount is greater than zero
            if (value <= 0) {
                input.classList.add('is-invalid');
                const errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = 'Amount must be greater than zero';
                input.parentNode.insertBefore(errorMsg, input.nextSibling);
                return false;
            }
            // Validate amount doesn't exceed balance
            else if (value > max) {
                input.classList.add('is-invalid');
                const errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = `Amount cannot exceed balance (${max.toFixed(2)})`;
                input.parentNode.insertBefore(errorMsg, input.nextSibling);
                return false;
            }
            // Valid amount
            else {
                input.classList.add('is-valid');
                return true;
            }
        }

        // Auto-select payment type: Final when amount equals balance, otherwise Advance.
        function updatePaymentTypeFromAmounts() {
            const paymentTypeSelect = document.getElementById('paymentType');

            if (!paymentTypeSelect) {
                return;
            }

            const allocatedInputs = document.querySelectorAll('#voucherPaymentModal .allocated-input');
            if (allocatedInputs.length === 0) {
                return;
            }

            let isFullBalancePayment = true;
            let hasPositiveAmount = false;

            allocatedInputs.forEach((input) => {
                const value = parseFloat(input.value) || 0;
                const balance = parseFloat(input.getAttribute('max')) || 0;

                if (value > 0) {
                    hasPositiveAmount = true;
                }

                // Equal to balance (within 1 paisa) => Final; anything less => Advance
                if (value <= 0 || Math.abs(value - balance) > 0.01) {
                    isFullBalancePayment = false;
                }
            });

            const targetType = (hasPositiveAmount && isFullBalancePayment) ? 'final' : 'advance';

            const match = Array.from(paymentTypeSelect.options).find((option) => {
                const optionValue = (option.value || '').trim().toLowerCase();
                const optionText = (option.textContent || '').trim().toLowerCase();

                return optionValue === targetType || optionText === targetType;
            });

            if (match) {
                paymentTypeSelect.value = match.value;
            }
        }

        // Function to update total payment amount
        function updateTotalPayment() {
            const allocatedInputs = document.querySelectorAll('.allocated-input');
            let total = 0;
            let hasError = false;

            allocatedInputs.forEach(input => {
                // Check if input has validation error
                if (input.classList.contains('is-invalid')) {
                    hasError = true;
                }
                total += parseFloat(input.value) || 0;
            });

            const totalElement = document.getElementById('totalPaymentAmount');
            if (totalElement) {
                totalElement.textContent = total.toFixed(2);
            }

            // Enable/disable submit button based on validation
            const submitBtn = document.querySelector('#voucherPaymentModal .btn-success');
            if (submitBtn) {
                submitBtn.disabled = hasError || total <= 0;
            }

            updatePaymentTypeFromAmounts();
        }

        // Function to submit voucher payment
        function submitVoucherPayment() {
            // Get form values
            const paymentTypeSelect = document.getElementById('paymentType');
            const paymentModeSelect = document.getElementById('paymentMode');
            const paymentDate = document.getElementById('paymentDate');
            const paymentType = paymentTypeSelect?.value;
            const paymentMode = paymentModeSelect?.value;
            const referenceId = document.getElementById('referenceId')?.value;
            const remarks = document.getElementById('paymentRemarks')?.value;
            const pumpId = document.getElementById('pumpName')?.value || null;

            // Validate required fields
            if (!paymentType) {
                alert('Please select a payment type');
                return;
            }

            if (!paymentMode) {
                alert('Please select a payment mode');
                return;
            }

            // Get payment type and mode IDs from the selected options
            const paymentTypeId = paymentTypeSelect.options[paymentTypeSelect.selectedIndex].dataset.id;
            const paymentModeId = paymentModeSelect.options[paymentModeSelect.selectedIndex].dataset.id;

            // Get freight booking ID and allocated amount
            const allocatedInput = document.querySelector('.allocated-input');
            if (!allocatedInput) {
                alert('Error: Payment input field not found');
                return;
            }

            const freightBookingId = allocatedInput.getAttribute('data-fb-id');
            if (!freightBookingId) {
                alert('Error: Freight booking ID not found');
                return;
            }

            const paymentAmount = parseFloat(allocatedInput.value) || 0;
            const maxBalance = parseFloat(allocatedInput.getAttribute('max')) || 0;

            // Validate payment amount is greater than zero
            if (paymentAmount <= 0) {
                alert('Payment amount must be greater than zero');
                allocatedInput.focus();
                return;
            }

            // Validate payment amount doesn't exceed balance
            if (paymentAmount > maxBalance) {
                alert(
                    `Payment amount (${paymentAmount.toFixed(2)}) cannot exceed the balance amount (${maxBalance.toFixed(2)})`
                );
                allocatedInput.focus();
                return;
            }

            // Calculate total amount
            const totalAmount = paymentAmount;

            // Prepare form data
            const formData = {
                booking_ids: [freightBookingId],
                payment_amounts: [paymentAmount],
                total_amount: totalAmount,
                payment_type_id: paymentTypeId,
                payment_mode_id: paymentModeId,
                payment_type: paymentType,
                payment_mode: paymentMode,
                reference_number: referenceId,
                notes: remarks,
                pump_id: pumpId,
                date: paymentDate.value
            };

            if (!confirm(
                    "Please review all payment details carefully before proceeding.\nOnce the voucher status is marked as PAID, it cannot be edited or changed.\nEnsure the information is correct before saving."
                )) {
                return;
            }
            // Show loading state
            const submitBtn = document.querySelector('#voucherPaymentModal .btn-success');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Processing...';
            submitBtn.disabled = true;

            // Submit payment
            fetch('/freight_bookings/voucher-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let successText = 'Payment submitted successfully!';
                        if (data.adjust_in_advance && data.remaining_advance_balance !== undefined) {
                            const name = data.transporter_name || 'Transporter';
                            const bal = parseFloat(data.remaining_advance_balance).toFixed(2);
                            successText += '\n\nRemaining advance balance (' + name + '):  ' + bal;
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: successText,
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                bootstrap.Modal.getInstance(document.getElementById('voucherPaymentModal')).hide();
                                window.location.reload();
                            });
                        } else {
                            alert(successText);
                            bootstrap.Modal.getInstance(document.getElementById('voucherPaymentModal')).hide();
                            window.location.reload();
                        }
                    } else {
                        alert('Error: ' + (data.message || 'Failed to submit payment'));
                    }
                })
                .catch(error => {
                    console.error('Error submitting payment:', error);
                    alert('Error submitting payment. Please try again.');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }
    </script>

    <script>
        function togglePumpName() {
            const paymentModeDropdown = document.getElementById("paymentMode");
            if (!paymentModeDropdown) return;

            const selectedText = paymentModeDropdown.options[paymentModeDropdown.selectedIndex]?.text.toLowerCase() || "";
            const pumpNameContainer = document.getElementById("pumpNameContainer");

            if (selectedText.includes("fuel")) {
                pumpNameContainer.style.display = "block";
            } else {
                pumpNameContainer.style.display = "none";
                document.getElementById("pumpName").value = ""; // reset
            }
        }

        // Run when dropdown changes
        document.addEventListener("change", function(e) {
            if (e.target && e.target.id === "paymentMode") {
                togglePumpName();
            }
        });

        // ✅ Run once on page load (or when modal opens)
        document.addEventListener("DOMContentLoaded", togglePumpName);

        // ✅ If this modal is loaded dynamically via Bootstrap, run on modal show
        $('#voucherPaymentModal').on('shown.bs.modal', function() {
            const paymentDate = document.getElementById('paymentDate');
            if (paymentDate) {
                paymentDate.value = todayDateInputValue();
            }
            togglePumpName();
            updatePaymentTypeFromAmounts();
            updateTotalPayment();
        });
    </script>

    @if ($freightBooking->linked_lr_id && $linkedFreightBookings->isNotEmpty())
    <!-- Combined Payment Modal -->
    <div class="modal fade fb-pay-modal" id="combinedPaymentModal" tabindex="-1" aria-labelledby="combinedPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="combinedPaymentModalLabel">
                        <i class="bi bi-wallet2"></i> Combined Payment — Linked Bookings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="combinedPaymentForm" method="POST" action="{{ route('freight_bookings.store_combined_payment') }}">
                        @csrf
                        @php
                            $firstLinked = $linkedFreightBookings->first();
                            $firstLr = $firstLinked->lr ?? null;
                        @endphp
                        <div class="fb-pay-panel">
                            <div class="fb-pay-panel__head">
                                <i class="bi bi-truck"></i> Transporter Details
                            </div>
                            <div class="fb-pay-panel__body">
                                <div class="fb-pay-facts fb-pay-facts--7">
                                    <div class="fb-pay-fact">
                                        <span class="fb-pay-fact__label">Transporter</span>
                                        <div class="fb-pay-fact__value">{{ $firstLr && $firstLr->transporter ? $firstLr->transporter->name : ($firstLinked->transporter_name ?? 'N/A') }}</div>
                                    </div>
                                    <div class="fb-pay-fact">
                                        <span class="fb-pay-fact__label">Mobile</span>
                                        <div class="fb-pay-fact__value">{{ $firstLr && $firstLr->transporter ? $firstLr->transporter->mobile_number : 'N/A' }}</div>
                                    </div>
                                    <div class="fb-pay-fact">
                                        <span class="fb-pay-fact__label">Short Name</span>
                                        <div class="fb-pay-fact__value">{{ $firstLr && $firstLr->transporter ? $firstLr->transporter->short_name : 'N/A' }}</div>
                                    </div>
                                    <div class="fb-pay-fact">
                                        <span class="fb-pay-fact__label">Beneficiary ID</span>
                                        <div class="fb-pay-fact__value">{{ $firstLr && $firstLr->transporter ? $firstLr->transporter->beneficiary_id : 'N/A' }}</div>
                                    </div>
                                    <div class="fb-pay-fact">
                                        <span class="fb-pay-fact__label">TDS %</span>
                                        <div class="fb-pay-fact__value">{{ $firstLr && $firstLr->transporter ? $firstLr->transporter->tds_percentage : 'N/A' }}%</div>
                                    </div>
                                    <div class="fb-pay-fact">
                                        <span class="fb-pay-fact__label">Truck</span>
                                        <div class="fb-pay-fact__value fb-pay-fact__value--accent">{{ $firstLr && $firstLr->truck ? $firstLr->truck->truck_number : 'N/A' }}</div>
                                    </div>
                                    <div class="fb-pay-fact">
                                        <span class="fb-pay-fact__label">Advance</span>
                                        <div class="fb-pay-fact__value fb-pay-fact__value--warn">{{ $firstLr && $firstLr->transporter ? ($firstLr->transporter->advance_amount ?? 'N/A') : 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fb-pay-panel fb-pay-panel--alloc">
                            <div class="fb-pay-panel__head">
                                <i class="bi bi-currency-rupee"></i> Payment Allocation
                            </div>
                            <div class="fb-pay-panel__body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <span class="fb-pay-fact__label">Total Balance</span>
                                        <div class="fb-pay-fact__value fb-pay-fact__value--warn">₹{{ number_format($combinedTotalBalance, 2) }}</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 ms-auto">
                                        <label class="form-label mb-0 fw-semibold text-nowrap" for="combinedAmountInput" style="font-size: 0.78rem;">Combined Amount *</label>
                                        <input style="width: 120px;" type="number" class="form-control form-control-sm text-end fw-bold" id="combinedAmountInput" name="combined_amount"
                                            step="0.01" min="0.01" max="{{ $combinedTotalBalance }}" required>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0 fb-pay-table">
                                        <thead>
                                            <tr>
                                                <th>LR Number</th>
                                                <th>Shortage Amount</th>
                                                <th>Damage Amount</th>
                                                <th>Balance</th>
                                                <th>Allocated</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($linkedFreightBookings as $fb)
                                                <tr>
                                                    <td>{{ $fb->lr ? $fb->lr->lr_number : 'N/A' }}</td>
                                                    <td>₹{{ number_format($fb->shortage_amount ?? 0, 2) }}</td>
                                                    <td>₹{{ number_format($fb->damage_amount ?? 0, 2) }}</td>
                                                    <td>₹{{ number_format($fb->balance_amount ?? 0, 2) }}</td>
                                                    <td style="max-width: 8rem;">
                                                        <input type="number" class="form-control form-control-sm allocated-input"
                                                            name="payment_amounts[]" data-fb-id="{{ $fb->id }}"
                                                            data-balance="{{ $fb->balance_amount ?? 0 }}"
                                                            step="0.01" min="0" max="{{ $fb->balance_amount ?? 0 }}" readonly>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        @foreach ($linkedFreightBookings as $fb)
                            <input type="hidden" name="booking_ids[]" value="{{ $fb->id }}">
                        @endforeach

                        <div class="fb-pay-panel">
                            <div class="fb-pay-panel__head">
                                <i class="bi bi-pencil-square"></i> Payment Details
                            </div>
                            <div class="fb-pay-panel__body">
                                <div class="fb-pay-fields">
                                    <div>
                                        <label for="combinedPaymentType" class="form-label">Payment Type *</label>
                                        <select class="form-select" id="combinedPaymentType" name="payment_type" required>
                                            <option value="">Select Payment Type</option>
                                            @foreach ($paymentTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="combinedPaymentMode" class="form-label">Payment Mode *</label>
                                        <select class="form-select" id="combinedPaymentMode" name="payment_mode" required>
                                            <option value="">Select Payment Mode</option>
                                            @foreach ($paymentModes as $mode)
                                                <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="combinedPumpContainer" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label for="combinedPumpName" class="form-label mb-0">Pump Name *</label>
                                            <a href="javascript:void(0)"
                                                class="lrf-quick-add js-open-quick-add-pump"
                                                data-pump-select="combinedPumpName">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </a>
                                        </div>
                                        <select class="form-select" id="combinedPumpName" name="pump_id">
                                            <option value="">Select Pump</option>
                                            @foreach ($pumps as $pump)
                                                <option value="{{ $pump->id }}">{{ $pump->pump_name }} {{ $pump->contact_name ? '('.$pump->contact_name.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="combinedPaymentDate" class="form-label">Payment Date</label>
                                        <input type="date" class="form-control" id="combinedPaymentDate" name="payment_date"
                                            value="{{ date('Y-m-d') }}">
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <label for="combinedNotes" class="form-label">Notes</label>
                                        <input type="text" class="form-control" id="combinedNotes" name="notes" placeholder="Enter notes...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success btn-sm" id="combinedPaymentSubmitBtn">
                        <i class="bi bi-check-lg me-1"></i> Submit Combined Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const totalBalance = {{ $combinedTotalBalance }};
            const amountInput = document.getElementById('combinedAmountInput');
            const allocatedInputs = document.querySelectorAll('#combinedPaymentForm .allocated-input');

            function allocateCombinedAmount() {
                const totalAmount = parseFloat(amountInput?.value) || 0;
                if (totalAmount <= 0 || totalBalance <= 0) return;

                let totalAlloc = 0;
                const allocs = [];
                allocatedInputs.forEach(input => {
                    const bal = parseFloat(input.dataset.balance) || 0;
                    const pct = totalBalance > 0 ? (bal / totalBalance) : 0;
                    let a = Math.min(totalAmount * pct, bal);
                    allocs.push({ input, a });
                    totalAlloc += a;
                });
                const diff = totalAmount - totalAlloc;
                if (Math.abs(diff) > 0.01 && allocs.length > 0) {
                    for (const item of allocs) {
                        const bal = parseFloat(item.input.dataset.balance) || 0;
                        if (bal - item.a > 0 && diff > 0) {
                            item.a += Math.min(bal - item.a, diff);
                            break;
                        }
                    }
                }
                allocs.forEach(({ input, a }) => { input.value = a.toFixed(2); });

                updateCombinedPaymentType();
            }

            function updateCombinedPaymentType() {
                const paymentTypeSelect = document.getElementById('combinedPaymentType');
                if (!paymentTypeSelect) return;

                const totalAmount = parseFloat(amountInput?.value) || 0;
                const isFullAmount = totalAmount > 0 && Math.abs(totalAmount - totalBalance) <= 0.01;
                const targetType = isFullAmount ? 'final' : 'advance';
                const match = Array.from(paymentTypeSelect.options).find(
                    (option) => option.textContent.trim().toLowerCase() === targetType
                );

                if (match) {
                    paymentTypeSelect.value = match.value;
                }
            }

            if (amountInput) {
                amountInput.addEventListener('input', allocateCombinedAmount);
            }

            updateCombinedPaymentType();

            function toggleCombinedPump() {
                const sel = document.getElementById('combinedPaymentMode');
                const pump = document.getElementById('combinedPumpContainer');
                if (!sel || !pump) return;
                const txt = sel.options[sel.selectedIndex]?.text?.toLowerCase() || '';
                pump.style.display = txt.includes('fuel') ? 'block' : 'none';
                if (!txt.includes('fuel')) document.getElementById('combinedPumpName').value = '';
            }
            document.getElementById('combinedPaymentMode')?.addEventListener('change', toggleCombinedPump);
            document.getElementById('combinedPaymentModal')?.addEventListener('shown.bs.modal', toggleCombinedPump);

            document.getElementById('combinedPaymentSubmitBtn')?.addEventListener('click', function() {
                const btn = this;
                const form = document.getElementById('combinedPaymentForm');
                if (!form) return;

                // Validate the combined amount
                const combinedAmount = parseFloat(document.getElementById('combinedAmountInput').value) || 0;
                if (combinedAmount <= 0 || combinedAmount > totalBalance) {
                    alert('Please enter a valid combined amount between 0.01 and ' + totalBalance);
                    return;
                }

                // Validate the payment type
                const paymentType = document.getElementById('combinedPaymentType').value;
                if (!paymentType) {
                    alert('Please select a payment type');
                    return;
                }

                // Validate the payment mode
                const paymentMode = document.getElementById('combinedPaymentMode').value;
                if (!paymentMode) {
                    alert('Please select a payment mode');
                    return;
                }

                // Validate the payment date
                const paymentDate = document.getElementById('combinedPaymentDate').value;
                if (!paymentDate) {
                    alert('Please select a payment date');
                    return;
                }

                if (!confirm('Please review all payment details carefully before proceeding. Once the voucher status is marked as PAID, it cannot be edited or changed.')) return;

                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';

                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: formData
                })
                .then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || data.success === false) {
                        if (data.warning && data.duplicate_details) {
                            const d = data.duplicate_details;
                            return (typeof Swal !== 'undefined' ? Swal.fire({
                                icon: 'warning',
                                title: 'Duplicate Payment Detected',
                                html: `<p>A similar payment already exists:</p><ul><li>Date: ${d.date}</li><li>Amount: ${d.amount}</li><li>Payment Type: ${d.payment_type}</li><li>Payment Mode: ${d.payment_mode}</li></ul><p>Proceed anyway?</p>`,
                                showCancelButton: true,
                                confirmButtonText: 'Yes, Proceed',
                                cancelButtonText: 'Cancel'
                            }) : Promise.resolve({ isConfirmed: confirm('Duplicate payment detected. Proceed?') })).then(r => {
                                if (r?.isConfirmed) {
                                    formData.append('confirm_duplicate', '1');
                                    return fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: formData });
                                }
                                throw new Error('Cancelled');
                            }).then(r => r.json());
                        }
                        throw new Error(data.message || 'Failed to process combined payment');
                    }
                    return data;
                })
                .then(data => {
                    bootstrap.Modal.getInstance(document.getElementById('combinedPaymentModal'))?.hide();
                    let successText = data.message || 'Combined payment processed successfully';
                    if (data.adjust_in_advance && data.remaining_advance_balance !== undefined) {
                        const name = data.transporter_name || 'Transporter';
                        const bal = parseFloat(data.remaining_advance_balance).toFixed(2);
                        successText += '\n\nRemaining advance balance (' + name + '):  ' + bal;
                    }
                    (typeof Swal !== 'undefined' ? Swal.fire({ icon: 'success', title: 'Success', text: successText }) : Promise.resolve())
                        .then(() => { window.location.reload(); });
                })
                .catch(err => {
                    if (err.message !== 'Cancelled') {
                        (typeof Swal !== 'undefined' ? Swal.fire({ icon: 'error', title: 'Error', text: err.message }) : alert(err.message));
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            });
        })();
    </script>
    @endif

    <script>
        (function() {
            let pendingPrintButton = null;
            const confirmModalElement = document.getElementById('voucherPrintConfirmModal');
            const confirmYesBtn = document.getElementById('voucherPrintConfirmYesBtn');

            document.addEventListener('click', function(event) {
                const printButton = event.target.closest('.voucher-print-btn');
                if (!printButton) {
                    return;
                }

                event.preventDefault();
                const printUrl = printButton.getAttribute('href');
                window.open(printUrl, '_blank');

                pendingPrintButton = printButton;
                if (confirmModalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(confirmModalElement).show();
                }
            });

            if (confirmModalElement) {
                confirmModalElement.addEventListener('shown.bs.modal', function() {
                    if (confirmYesBtn) {
                        confirmYesBtn.focus();
                    }
                });

                confirmModalElement.addEventListener('hidden.bs.modal', function() {
                    pendingPrintButton = null;
                    if (confirmYesBtn) {
                        confirmYesBtn.disabled = false;
                    }
                });
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' && confirmModalElement && confirmModalElement.classList.contains('show')) {
                    event.preventDefault();
                    if (confirmYesBtn && !confirmYesBtn.disabled) {
                        confirmYesBtn.click();
                    }
                }
            });

            if (confirmYesBtn) {
                confirmYesBtn.addEventListener('click', function() {
                    if (confirmYesBtn.disabled) {
                        return;
                    }
                    confirmYesBtn.disabled = true;

                    const printButton = pendingPrintButton;
                    pendingPrintButton = null;

                    if (confirmModalElement && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getOrCreateInstance(confirmModalElement).hide();
                    }

                    if (!printButton) {
                        confirmYesBtn.disabled = false;
                        return;
                    }

                    fetch(printButton.dataset.markUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                freight_booking_id: printButton.dataset.freightBookingId
                            }),
                        })
                        .then(async (response) => {
                            const data = await response.json();
                            if (!response.ok) {
                                throw new Error(data.message || 'Failed to update print status.');
                            }
                            return data;
                        })
                        .then((data) => {
                            (data.updated_booking_ids || [printButton.dataset.freightBookingId]).forEach((id) => {
                                document.querySelectorAll(`.voucher-print-btn[data-freight-booking-id="${id}"]`).forEach((btn) => {
                                    btn.classList.add('is-printed');
                                    btn.title = `Last printed: ${data.voucher_last_printed_at}`;
                                });
                            });
                        })
                        .catch((error) => {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Update Failed',
                                    text: error.message || 'Could not update print status.',
                                });
                            } else {
                                alert(error.message || 'Could not update print status.');
                            }
                        })
                        .finally(() => {
                            confirmYesBtn.disabled = false;
                        });
                });
            }
        })();
    </script>

    @include('freight_bookings.partials.quick-add-pump')
@endsection
