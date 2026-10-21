@extends('layouts.app')

@section('content')
    @php
        $lr = $freightBooking?->lr;
        $backUrl = route('voucher_payments.index') . (request()->getQueryString() ? '?' . request()->getQueryString() : '');
        $trRate = (float) ($freightBooking?->tr_fr_rate ?? 0);
        $trAmount = (float) ($freightBooking?->tr_amount ?? 0);
        $advanceAmount = (float) ($freightBooking?->advance_amount ?? 0);
        $tdsAmount = (float) ($freightBooking?->tds_amount ?? 0);
        $balanceAmount = (float) ($freightBooking?->balance_amount ?? 0);
        $hasBalanceDue = $balanceAmount > 0;
        $routeFrom = $lr?->source_city
            ?? ($lr?->consignor?->city?->name ?? null);
        $routeTo = $lr?->destination_city
            ?? ($lr?->consignee?->city?->name ?? null);
        $canManage = auth()->check() && auth()->user()->canActAsAdmin();
    @endphp
    <link rel="stylesheet" href="{{ asset('css/fb-show-redesigned.css') }}?v={{ file_exists(public_path('css/fb-show-redesigned.css')) ? filemtime(public_path('css/fb-show-redesigned.css')) : time() }}">

    <style>
        .fb-compact-table th,
        .fb-compact-table td,
        .fb-compact-table thead th,
        .fb-compact-table tbody td,
        .fb-compact-table .fb-info-grid__sub {
            white-space: nowrap !important;
            vertical-align: middle;
        }

        .fb-detail-page .table-responsive {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 991.98px) {
            .fb-info-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.45rem 0.65rem !important;
                padding: 0.65rem 0.75rem !important;
            }

            .fb-info-grid__item--span-2 {
                grid-column: span 2 !important;
            }

            .fb-info-grid__label {
                font-size: 0.65rem !important;
                margin-bottom: 2px !important;
            }

            .fb-info-grid__value {
                font-size: 0.78rem !important;
                line-height: 1.25 !important;
            }

            .fb-route-preview {
                margin-bottom: 0.55rem !important;
                padding: 0.45rem 0.65rem !important;
                font-size: 0.78rem !important;
                border-radius: 8px !important;
            }

            .fb-route-facts {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.45rem 0.65rem !important;
            }

            .fb-detail-page .card-body {
                padding: 0.65rem 0.75rem !important;
            }

            .fb-route-header {
                padding: 0.6rem 0.75rem !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 0.4rem !important;
            }

            .fb-route-header strong {
                font-size: 0.84rem !important;
            }

            .fb-route-header__totals {
                font-size: 0.72rem !important;
                text-align: right;
            }

            .fb-detail-actions .btn-icon {
                width: 30px !important;
                height: 30px !important;
                font-size: 0.75rem !important;
                border-radius: 6px !important;
            }

            /* Financial summary 2 per line on mobile */
            .fb-financial-summary__stats {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.5rem !important;
                margin-bottom: 0.65rem !important;
            }

            .fb-financial-summary__stat {
                padding: 0.45rem 0.55rem !important;
                border-radius: 8px !important;
                margin: 0 !important;
            }

            .fb-financial-summary__stat--result,
            .fb-financial-summary__stats > .fb-financial-summary__stat:nth-child(3) {
                grid-column: span 2 !important;
            }

            .fb-financial-summary__stat-label {
                font-size: 0.64rem !important;
                margin-bottom: 2px !important;
            }

            .fb-financial-summary__stat-value {
                font-size: 0.85rem !important;
            }

            .fb-financial-summary__rows {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.45rem 0.55rem !important;
            }

            .fb-financial-summary__row {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                justify-content: center !important;
                background: #fff !important;
                padding: 0.42rem 0.55rem !important;
                border-radius: 8px !important;
                border: 1px solid #d8e9df !important;
                min-height: 46px !important;
                margin: 0 !important;
            }

            .fb-financial-summary__row span {
                font-size: 0.62rem !important;
                color: #64748b !important;
                line-height: 1.2 !important;
                margin-bottom: 2px !important;
            }

            .fb-financial-summary__row strong {
                font-size: 0.82rem !important;
                color: #0f172a !important;
                line-height: 1.2 !important;
            }

            .fb-financial-summary__row--total {
                grid-column: span 2 !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%) !important;
                border: 1px solid #86efac !important;
                padding: 0.5rem 0.75rem !important;
                margin-top: 0.2rem !important;
            }

            .fb-financial-summary__row--total span {
                font-size: 0.75rem !important;
                font-weight: 700 !important;
                color: #166534 !important;
                margin-bottom: 0 !important;
            }

            .fb-financial-summary__row--total strong {
                font-size: 0.95rem !important;
                font-weight: 800 !important;
                color: #166534 !important;
            }

            .fb-compact-table {
                font-size: 0.70rem !important;
            }

            .fb-compact-table thead th {
                font-size: 0.62rem !important;
                padding: 0.3rem 0.45rem !important;
            }

            .fb-compact-table tbody td {
                font-size: 0.69rem !important;
                padding: 0.3rem 0.45rem !important;
            }

            .fb-compact-table .badge {
                font-size: 0.62rem !important;
                padding: 0.15rem 0.35rem !important;
            }

            .fb-compact-table .fb-info-grid__sub {
                font-size: 0.62rem !important;
            }

            .fb-compact-table td .btn-icon {
                width: 24px !important;
                height: 24px !important;
                font-size: 0.68rem !important;
            }
        }
    </style>
    @if (session('success'))
        <div class="alert alert-success mt-2" style="max-width: 1200px; margin-left: auto; margin-right: auto;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger mt-2" style="max-width: 1200px; margin-left: auto; margin-right: auto;">
            {{ session('error') }}
        </div>
    @endif

    <div class="fb-detail-page app-index-page">
        <div class="row g-3">
            <div class="col-lg-8 fb-detail-main">
                <div class="card">
                    <div class="card-header fb-detail-card-header">
                        <strong class="fb-detail-card-title">
                            Voucher Details
                            <span class="text-muted fw-semibold">#{{ $voucherNumber }}</span>
                        </strong>
                        <div class="fb-detail-actions ms-auto">
                            <a href="{{ $backUrl }}" class="btn btn-sm btn-icon btn-action-back" title="Back to List">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                            @if ($freightBooking)
                                <a href="{{ route('freight_bookings.show', $freightBooking->id) }}"
                                    class="btn btn-sm btn-icon btn-action-view" title="View good hire">
                                    <i class="bi bi-eye"></i>
                                </a>
                            @endif
                            @if ($lr)
                                <a href="{{ route('reports.voucher-receipt-export', ['lr_id' => $lr->id]) }}"
                                    class="btn btn-sm btn-icon btn-action-print" title="Print voucher challan"
                                    target="_blank">
                                    <i class="bi bi-printer"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="card-body fb-info-grid">
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Voucher Number</span>
                            <div class="fb-info-grid__value fb-info-grid__value--accent">{{ $voucherNumber }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">FR Number</span>
                            <div class="fb-info-grid__value">
                                @if ($freightBooking)
                                    <a href="{{ route('freight_bookings.show', $freightBooking->id) }}" class="text-decoration-none">
                                        #{{ $freightBooking->id }}
                                    </a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">LR Number</span>
                            <div class="fb-info-grid__value fb-info-grid__value--accent">{{ $lr->lr_number ?? '—' }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">GR Date</span>
                            <div class="fb-info-grid__value">
                                {{ $lr && $lr->gr_date ? date('d-m-Y', strtotime($lr->gr_date)) : '—' }}
                            </div>
                        </div>

                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Company</span>
                            <div class="fb-info-grid__value">{{ $freightBooking?->company?->name ?? '—' }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Customer</span>
                            <div class="fb-info-grid__value">{{ $lr?->customer?->name ?? '—' }}</div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Truck</span>
                            <div class="fb-info-grid__value fb-info-grid__value--accent">
                                {{ $lr?->truck?->truck_number ?? '—' }}
                            </div>
                        </div>
                        <div class="fb-info-grid__item">
                            <span class="fb-info-grid__label">Payments</span>
                            <div class="fb-info-grid__value">{{ $payments->count() }}</div>
                        </div>

                        <div class="fb-info-grid__item fb-info-grid__item--span-2">
                            <span class="fb-info-grid__label">Route</span>
                            <div class="fb-info-grid__value">
                                @if ($routeFrom || $routeTo)
                                    {{ $routeFrom ?: '—' }} → {{ $routeTo ?: '—' }}
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="fb-info-grid__item fb-info-grid__item--span-2">
                            <span class="fb-info-grid__label">Total Paid on Voucher</span>
                            <div class="fb-info-grid__value fb-info-grid__value--accent">
                                ₹{{ number_format($totalAmount, 2) }}
                            </div>
                        </div>
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
                                {{ $routeFrom ?: '—' }} → {{ $routeTo ?: '—' }}
                            </div>
                            <div class="fb-route-facts">
                                <div class="fb-route-facts__item">
                                    <span class="fb-info-grid__label">From</span>
                                    <div class="fb-info-grid__value">{{ $routeFrom ?: '—' }}</div>
                                </div>
                                <div class="fb-route-facts__item">
                                    <span class="fb-info-grid__label">To</span>
                                    <div class="fb-info-grid__value">{{ $routeTo ?: '—' }}</div>
                                </div>
                                <div class="fb-route-facts__item">
                                    <span class="fb-info-grid__label">Customer</span>
                                    <div class="fb-info-grid__value">{{ $lr->customer->name ?? '—' }}</div>
                                </div>
                                <div class="fb-route-facts__item">
                                    <span class="fb-info-grid__label">Payee</span>
                                    <div class="fb-info-grid__value">{{ $lr->transporter->name ?? '—' }}</div>
                                </div>
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
                                            <th>ID</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Mode</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Remark</th>
                                            <th>Created By</th>
                                            <th>Updated By</th>
                                            @if ($canManage)
                                                <th class="text-end">Actions</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($payments as $payment)
                                            <tr>
                                                <td>{{ $payment->id }}</td>
                                                <td>
                                                    {{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('d-m-Y') : '—' }}
                                                </td>
                                                <td>{{ ucfirst($payment->payment_type ?? '—') }}</td>
                                                <td>
                                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_mode ?? '—')) }}
                                                    @if ($payment->pump)
                                                        <div class="fb-info-grid__sub">{{ $payment->pump->pump_name }}</div>
                                                    @endif
                                                </td>
                                                <td class="fw-bold text-success">
                                                    ₹{{ number_format($payment->amount, 2) }}
                                                </td>
                                                <td>{!! $payment->getPaymentStatusBadge() !!}</td>
                                                <td>{!! $payment->remark ?: '—' !!}</td>
                                                <td>
                                                    @if ($payment->creator)
                                                        {{ $payment->creator->name }}
                                                        @if ($payment->creator->branch)
                                                            <div class="fb-info-grid__sub">
                                                                {{ $payment->creator->branch->branch_name }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($payment->updater)
                                                        {{ $payment->updater->name }}
                                                        @if ($payment->updater->branch)
                                                            <div class="fb-info-grid__sub">
                                                                {{ $payment->updater->branch->branch_name }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                @if ($canManage)
                                                    <td class="text-end">
                                                        <div class="d-inline-flex gap-1">
                                                            <a href="{{ route('voucher_payments.edit', $payment) }}?lorry_receipt_id={{ $payment->freight_booking?->lr_id }}&freight_booking_id={{ $payment->freight_booking_id }}"
                                                                class="btn btn-sm btn-icon btn-action-edit"
                                                                title="Edit payment">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <form action="{{ route('voucher_payments.destroy', $payment) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-icon btn-action-delete"
                                                                    title="Delete payment"
                                                                    onclick="return confirm('Are you sure you want to delete this payment?')">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="fb-compact-empty">No payment records found.</div>
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
                            <span class="fb-financial-summary__subtitle">Hire vs payments on this voucher</span>
                        </div>
                    </div>
                    <div class="fb-financial-summary__body">
                        <div class="fb-financial-summary__stats">
                            <div class="fb-financial-summary__stat">
                                <span class="fb-financial-summary__stat-label">TR Amount</span>
                                <span class="fb-financial-summary__stat-value">₹{{ number_format($trAmount, 2) }}</span>
                            </div>
                            <div class="fb-financial-summary__stat">
                                <span class="fb-financial-summary__stat-label">Voucher Paid</span>
                                <span class="fb-financial-summary__stat-value fb-financial-summary__stat-value--muted">
                                    ₹{{ number_format($totalAmount, 2) }}
                                </span>
                            </div>
                            <div class="fb-financial-summary__stat fb-financial-summary__stat--result">
                                <span class="fb-financial-summary__stat-label">Balance</span>
                                <span class="fb-financial-summary__stat-value is-clear">
                                    ₹{{ number_format($balanceAmount, 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="fb-financial-summary__rows">
                            <div class="fb-financial-summary__row">
                                <span>TR Freight Rate</span>
                                <strong>₹{{ number_format($trRate, 2) }}</strong>
                            </div>
                            <div class="fb-financial-summary__row">
                                <span>Advance</span>
                                <strong>₹{{ number_format($advanceAmount, 2) }}</strong>
                            </div>
                            <div class="fb-financial-summary__row">
                                <span>TDS Amount</span>
                                <strong>₹{{ number_format($tdsAmount, 2) }}</strong>
                            </div>
                            <div class="fb-financial-summary__row">
                                <span>Payments on voucher</span>
                                <strong>{{ $payments->count() }}</strong>
                            </div>
                            <div class="fb-financial-summary__row fb-financial-summary__row--total">
                                <span>Total paid</span>
                                <strong>₹{{ number_format($totalAmount, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
