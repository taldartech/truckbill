@extends('layouts.app')

@section('content')
    @php
        $lr = $lorry_receipt;
        $tdsPct = $lr->transporter->tds_percentage ?? 0;
        $trRate = (float) ($formData['tr_fr_rate'] ?? 0);
        $trAmount = (float) ($formData['tr_amount'] ?? 0);
        $tdsAmount = (float) ($formData['tds_amount'] ?? 0);
        $balanceAmount = (float) ($formData['balance_amount'] ?? 0);
        $isFixed = ! empty($formData['is_fixed_amount']);
    @endphp

    <link rel="stylesheet" href="{{ asset('css/lr-form-redesigned.css') }}?v={{ config('app.ASSET_VERSION') }}">

    <div class="lrf-page lrf-page--confirm app-index-page">
        <div class="lrf-page__toolbar">
            <div>
                <h1 class="lrf-page__toolbar-title">Confirm Good Hire</h1>
                <p class="lrf-page__toolbar-sub">Review linked bilty and hire charges before submitting.</p>
            </div>
            <div class="lrf-page__toolbar-actions">
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        {{-- Linked Bilty --}}
        <div class="lrf-section lrf-linked">
            <div class="lrf-linked__head">
                <div class="lrf-linked__head-main">
                    <div class="lrf-linked__eyebrow">Linked Bilty</div>
                    <div class="lrf-linked__title-row">
                        <span class="lrf-linked__lr" title="Good Receipt number">{{ $lr->lr_number }}</span>
                        @if ($lr->transporter)
                            <span class="lrf-chip {{ $lr->transporter->is_owner ? 'lrf-chip--info' : 'lrf-chip--ok' }}">
                                {{ $lr->transporter->is_owner ? 'Owner payee' : 'Agency payee' }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="lrf-linked__route-pill" title="Route">
                    <span>{{ $lr->source_city ?? '—' }}</span>
                    <i class="bi bi-arrow-right"></i>
                    <span>{{ $lr->destination_city ?? '—' }}</span>
                </div>
            </div>

            <div class="lrf-section__body">
                <div class="lrf-linked__kpis">
                    <div class="lrf-linked__kpi">
                        <span class="lrf-linked__kpi-label">Goods</span>
                        <span class="lrf-linked__kpi-value">{{ $lr->good_type ?? '—' }}</span>
                    </div>
                    <div class="lrf-linked__kpi">
                        <span class="lrf-linked__kpi-label">Weight</span>
                        <span class="lrf-linked__kpi-value">{{ number_format($lr->weight ?? 0, 3) }} <small>MT</small></span>
                    </div>
                    <div class="lrf-linked__kpi">
                        <span class="lrf-linked__kpi-label">LR Rate</span>
                        <span class="lrf-linked__kpi-value">₹{{ number_format($lr->fr_rate ?? 0, 2) }}</span>
                    </div>
                    <div class="lrf-linked__kpi lrf-linked__kpi--accent">
                        <span class="lrf-linked__kpi-label">LR Amount</span>
                        <span class="lrf-linked__kpi-value">₹{{ number_format($lr->amount ?? 0, 2) }}</span>
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
                                <span class="lrf-linked__fact-value">{{ $lr->customer->name ?? 'N/A' }}</span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">Payee</span>
                                <span class="lrf-linked__fact-value">
                                    {{ $lr->transporter->name ?? 'N/A' }}
                                    @if ($lr->transporter)
                                        <span class="lrf-chip {{ $lr->transporter->is_owner ? 'lrf-chip--info' : 'lrf-chip--ok' }}">
                                            {{ $lr->transporter->is_owner ? 'Owner' : 'Agency' }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">Consignor</span>
                                <span class="lrf-linked__fact-value">{{ $lr->consignor->name ?? 'N/A' }}</span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">Consignee</span>
                                <span class="lrf-linked__fact-value">{{ $lr->consignee->name ?? 'N/A' }}</span>
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
                                <span class="lrf-linked__fact-value">{{ $lr->truck->truck_number ?? 'N/A' }}</span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">TDS %</span>
                                <span class="lrf-linked__fact-value">{{ number_format($tdsPct, 2) }}%</span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">From</span>
                                <span class="lrf-linked__fact-value">
                                    {{ $lr->source_city ?? 'N/A' }}{{ ! empty($lr->source_state) ? ', '.$lr->source_state : '' }}
                                </span>
                            </div>
                            <div class="lrf-linked__fact">
                                <span class="lrf-linked__fact-label">To</span>
                                <span class="lrf-linked__fact-value">
                                    {{ $lr->destination_city ?? 'N/A' }}{{ ! empty($lr->destination_state) ? ', '.$lr->destination_state : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hire + optional in one review card --}}
        <div class="lrf-section">
            <div class="lrf-section__head lrf-section__head--summary">
                <div class="lrf-section__head-left">
                    <div>
                        <h2 class="lrf-section__title">Hire Charges</h2>
                        <p class="lrf-section__desc">Amounts that will be saved with this good hire</p>
                    </div>
                </div>
                @if ($isFixed)
                    <span class="lrf-chip lrf-chip--warn">Fixed amount</span>
                @endif
            </div>
            <div class="lrf-section__body">
                <div class="lrf-confirm-hire">
                    <div class="lrf-linked__kpis lrf-confirm-hire__kpis">
                        <div class="lrf-linked__kpi">
                            <span class="lrf-linked__kpi-label">TR Freight Rate</span>
                            <span class="lrf-linked__kpi-value">₹{{ number_format($trRate, 2) }}</span>
                        </div>
                        <div class="lrf-linked__kpi">
                            <span class="lrf-linked__kpi-label">TR Amount</span>
                            <span class="lrf-linked__kpi-value">₹{{ number_format($trAmount, 2) }}</span>
                        </div>
                        <div class="lrf-linked__kpi">
                            <span class="lrf-linked__kpi-label">TDS Amount</span>
                            <span class="lrf-linked__kpi-value">₹{{ number_format($tdsAmount, 2) }}</span>
                        </div>
                        <div class="lrf-linked__kpi lrf-linked__kpi--accent">
                            <span class="lrf-linked__kpi-label">Balance</span>
                            <span class="lrf-linked__kpi-value">₹{{ number_format($balanceAmount, 2) }}</span>
                        </div>
                    </div>

                    <div class="lrf-confirm-summary">
                        <div class="lrf-confirm-summary__row">
                            <span>TR Amount</span>
                            <strong>₹{{ number_format($trAmount, 2) }}</strong>
                        </div>
                        <div class="lrf-confirm-summary__row">
                            <span>TDS Amount</span>
                            <strong class="text-danger">− ₹{{ number_format($tdsAmount, 2) }}</strong>
                        </div>
                        <div class="lrf-confirm-summary__row lrf-confirm-summary__row--total">
                            <span>Balance Amount</span>
                            <strong>₹{{ number_format($balanceAmount, 2) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="lrf-confirm-optional">
                    <div class="lrf-confirm-optional__label">Optional details</div>
                    <div class="lrf-linked__facts lrf-linked__facts--4">
                        <div class="lrf-linked__fact">
                            <span class="lrf-linked__fact-label">E-Way Bill Number</span>
                            <span class="lrf-linked__fact-value">{{ $formData['eway_bill_number'] ?: 'Not provided' }}</span>
                        </div>
                        <div class="lrf-linked__fact">
                            <span class="lrf-linked__fact-label">E-Way Bill Expiry</span>
                            <span class="lrf-linked__fact-value">
                                {{ ! empty($formData['eway_bill_expiry']) ? date('d-m-Y', strtotime($formData['eway_bill_expiry'])) : 'Not provided' }}
                            </span>
                        </div>
                        <div class="lrf-linked__fact">
                            <span class="lrf-linked__fact-label">PAN Verified</span>
                            <span class="lrf-linked__fact-value">
                                @if (! empty($formData['pan_verified']))
                                    <span class="lrf-chip lrf-chip--ok">Yes</span>
                                @else
                                    <span class="lrf-chip lrf-chip--bad">No</span>
                                @endif
                            </span>
                        </div>
                        <div class="lrf-linked__fact">
                            <span class="lrf-linked__fact-label">Remark</span>
                            <span class="lrf-linked__fact-value">{{ $formData['remark'] ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('freight_bookings.store') }}">
            @csrf
            @foreach ($formData as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="confirmed" value="1">

            <div class="lrf-actions">
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Back to Edit
                </a>
                <button type="submit" name="action" value="save" class="btn btn-success btn-sm">
                    <i class="bi bi-check-lg me-1"></i> Confirm &amp; Submit
                </button>
                <button type="submit" name="action" value="save_and_view" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-wallet2 me-1"></i> Save &amp; do payment
                </button>
            </div>
        </form>
    </div>
@endsection
