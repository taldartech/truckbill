@extends('layouts.app')

@section('title', 'Broker Settlement Statement #' . $freightBooking->id . ' - ' . $company->name)

@php
    $lr = $freightBooking->lr;
    $transporter = $lr?->transporter;
    $truck = $lr?->truck;
    $driver = $truck?->driver;
    $sourceCity = $lr?->consignor?->city?->name ?? '-';
    $destCity = $lr?->consignee?->city?->name ?? '-';
    $weight = (float) ($lr?->weight ?? 0);
    $rate = (float) ($freightBooking->tr_fr_rate ?? 0);
    $netPayableInWords = function_exists('getIndianCurrency') ? trim(getIndianCurrency($effectivePayable)) : '';
@endphp

@push('styles')
<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }

        /* Hide app chrome */
        .app-sidebar,
        .main-sidebar,
        .sidebar-wrapper,
        .app-header,
        .app-footer,
        .btn,
        .d-print-none,
        .no-print,
        .alert,
        #subscription-access-banner {
            display: none !important;
            visibility: hidden !important;
        }

        html, body, .app-wrapper, .app-main, .container-fluid {
            background: #ffffff !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 8.5pt !important;
        }

        .settlement-print {
            display: block !important;
            width: 100% !important;
            color: #000000 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #1f2937 !important;
            padding: 4px 6px !important;
            font-size: 8.5pt !important;
            vertical-align: middle;
        }

        .print-table th {
            background-color: #f3f4f6 !important;
            font-weight: 700 !important;
            color: #000000 !important;
        }

        .print-section-header {
            background-color: #e5e7eb !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 8.5pt !important;
            letter-spacing: 0.5px;
            padding: 4px 6px !important;
            border: 1px solid #1f2937 !important;
        }
    }

    /* Web Styles */
    .settlement-card-metric {
        border-radius: 10px;
        border: 1px solid var(--brand-border);
        background: var(--brand-card-bg, #ffffff);
        padding: 1rem 1.25rem;
        transition: transform 0.15s ease;
    }

    .settlement-table th {
        background-color: var(--brand-bg, #f8fafc);
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 600;
        color: var(--brand-text-muted, #64748b);
    }

    .settlement-table td {
        font-size: 0.85rem;
        vertical-align: middle;
    }

    .settlement-print {
        display: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 app-index-page--standard d-print-none">

    <!-- Top Card Toolbar -->
    <div class="card shadow-sm border-0 mb-4" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0 fw-bold" style="color: var(--brand-text-primary);">
                        <i class="bi bi-file-earmark-ruled text-primary me-1"></i>Trip Settlement Statement
                    </h4>
                    <span class="badge bg-light text-dark border">Hire #{{ $freightBooking->id }}</span>
                    @if($isCombined)
                        <span class="badge bg-primary">Combined Trip</span>
                    @endif
                    @if($balanceDue <= 0.01)
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Settled in Full</span>
                    @else
                        <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Pending Settlement</span>
                    @endif
                </div>
                <small class="text-muted">
                    Transporter: <strong>{{ $transporter?->transporterMaster?->name ?? $transporter?->name ?? 'Unknown Transporter' }}</strong>
                    &nbsp;|&nbsp; Truck: <strong>{{ $truck?->truck_number ?? '-' }}</strong>
                    &nbsp;|&nbsp; Date: <strong>{{ now()->format('d-m-Y') }}</strong>
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm px-3 fw-semibold">
                    <i class="bi bi-printer me-1"></i> Print Statement
                </button>
                <a href="{{ route('freight_bookings.show', $freightBooking->id) }}" class="btn btn-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Hire
                </a>
            </div>
        </div>
    </div>

    <!-- 4 KPI Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="settlement-card-metric shadow-sm">
                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Total Gross Freight</small>
                <h4 class="mb-0 fw-bold mt-1 text-primary">₹{{ number_format($totalEarnings, 2) }}</h4>
                <small class="text-muted" style="font-size: 0.72rem;">Base freight & approved additions</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="settlement-card-metric shadow-sm">
                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Total Deductions</small>
                <h4 class="mb-0 fw-bold mt-1 text-danger">₹{{ number_format($totalDeductions, 2) }}</h4>
                <small class="text-muted" style="font-size: 0.72rem;">Advances, diesel, claims & TDS</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="settlement-card-metric shadow-sm">
                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Net Payable Settled</small>
                <h4 class="mb-0 fw-bold mt-1 text-success">₹{{ number_format($effectivePayable, 2) }}</h4>
                <small class="text-muted" style="font-size: 0.72rem;">Contracted net freight payable</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="settlement-card-metric shadow-sm" style="border-left: 4px solid {{ $balanceDue > 0.01 ? '#f59e0b' : '#10b981' }} !important;">
                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Balance Due to Vendor</small>
                <h4 class="mb-0 fw-bold mt-1 {{ $balanceDue > 0.01 ? 'text-warning' : 'text-success' }}">
                    ₹{{ number_format($balanceDue, 2) }}
                </h4>
                <small class="text-muted" style="font-size: 0.72rem;">Paid: ₹{{ number_format($totalPaid, 2) }}</small>
            </div>
        </div>
    </div>

    <!-- Trip & Transporter Details Card -->
    <div class="card shadow-sm border-0 mb-4" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-truck me-2 text-primary"></i>Trip & Transporter Information</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">LR / Bilty Number:</span>
                    <strong class="text-dark">{{ $lr?->lr_number ?: '-' }}</strong>
                    @if($lr?->gr_date)
                        <div class="text-muted small">Date: {{ date('d-m-Y', strtotime($lr->gr_date)) }}</div>
                    @endif
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Route:</span>
                    <strong class="text-dark">{{ $sourceCity }} <i class="bi bi-arrow-right text-muted"></i> {{ $destCity }}</strong>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Truck & Driver:</span>
                    <strong class="text-dark">{{ $truck?->truck_number ?: '-' }}</strong>
                    @if($driver)
                        <div class="text-muted small">{{ $driver->name }} ({{ $driver->contact_number ?: '-' }})</div>
                    @endif
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Goods & Weight:</span>
                    <strong class="text-dark">{{ $freightBooking->good_type ?: ($lr?->good_type ?: '-') }}</strong>
                    <div class="text-muted small">{{ number_format($weight, 3) }} MT</div>
                </div>

                <div class="col-12"><hr class="my-1 border-light"></div>

                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Transporter / Broker:</span>
                    <strong class="text-dark">{{ $transporter?->transporterMaster?->name ?? $transporter?->name ?? '-' }}</strong>
                    @if($transporter?->short_name)
                        <span class="badge bg-light text-dark border ms-1">{{ $transporter->short_name }}</span>
                    @endif
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Transporter PAN:</span>
                    <strong class="text-dark">{{ $transporter?->transporterMaster?->pan_number ?: 'Not Set' }}</strong>
                    @if($transporter?->declaration_given)
                        <span class="badge bg-info text-white ms-1" title="194C(6) Declaration Given">194C(6) Declared</span>
                    @endif
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Beneficiary / Bank ID:</span>
                    <strong class="text-dark">{{ $transporter?->beneficiary_id ?: 'Not Set' }}</strong>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Delivery Status:</span>
                    <span class="badge {{ $freightBooking->is_delivered ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ $freightBooking->is_delivered ? 'Delivered' : ($lr?->delivery_status ?? 'In Transit') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement Details Row (Earnings & Deductions) -->
    <div class="row g-4 mb-4">

        <!-- Column 1: Gross Freight & Additions -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-success"><i class="bi bi-plus-circle me-2"></i>1. Freight & Approved Additions</h6>
                    <span class="badge bg-success-subtle text-success border">Credits to Vendor</span>
                </div>
                <div class="card-body p-0">
                    <table class="table settlement-table mb-0">
                        <thead>
                            <tr>
                                <th>Item / Description</th>
                                <th class="text-center">Rate / Basis</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Base Contracted Freight</strong>
                                    <small class="text-muted d-block">{{ $freightBooking->is_fixed_amount ? 'Fixed Lumpsum Rate' : 'Weight-Based Rate' }}</small>
                                </td>
                                <td class="text-center text-muted">
                                    {{ $freightBooking->is_fixed_amount ? 'Fixed' : '₹' . number_format($rate, 2) . '/MT' }}
                                </td>
                                <td class="text-end fw-semibold text-dark">₹{{ number_format($grossFreight, 2) }}</td>
                            </tr>
                            @if($detention > 0)
                            <tr>
                                <td>
                                    <strong>Detention / Halting Charges</strong>
                                    <small class="text-muted d-block">Vehicle halting compensation</small>
                                </td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-end fw-semibold text-dark">₹{{ number_format($detention, 2) }}</td>
                            </tr>
                            @endif
                            @if($deviation > 0)
                            <tr>
                                <td>
                                    <strong>Route Deviation Expense</strong>
                                    <small class="text-muted d-block">Approved route extension</small>
                                </td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-end fw-semibold text-dark">₹{{ number_format($deviation, 2) }}</td>
                            </tr>
                            @endif
                            @if($customAdditions > 0)
                            <tr>
                                <td>
                                    <strong>Custom Extra Additions</strong>
                                    <small class="text-muted d-block">Loading, unloading, or other additions</small>
                                </td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-end fw-semibold text-dark">₹{{ number_format($customAdditions, 2) }}</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot class="border-top" style="background-color: #f8fafc;">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-uppercase text-dark">Total Gross Freight:</td>
                                <td class="text-end text-success fs-6">₹{{ number_format($totalEarnings, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Column 2: Advances & Deductions -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-dash-circle me-2"></i>2. Advances & Deductions Schedule</h6>
                    <span class="badge bg-danger-subtle text-danger border">Debits to Vendor</span>
                </div>
                <div class="card-body p-0">
                    <table class="table settlement-table mb-0">
                        <thead>
                            <tr>
                                <th>Deduction Head</th>
                                <th class="text-center">Reference / Details</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($advanceDeduction > 0)
                            <tr>
                                <td>
                                    <strong>Loading Cash / Bank Advance</strong>
                                    <small class="text-muted d-block">Disbursed to driver at loading</small>
                                </td>
                                <td class="text-center text-muted">Adjusted in Hire</td>
                                <td class="text-end fw-semibold text-danger">₹{{ number_format($advanceDeduction, 2) }}</td>
                            </tr>
                            @endif
                            @if($shortageDeduction > 0)
                            <tr>
                                <td>
                                    <strong>Shortage Claim Deduction</strong>
                                    <small class="text-muted d-block">{{ $freightBooking->shortage_qty ? $freightBooking->shortage_qty . ' Units Short' : 'Transit Shortage' }}</small>
                                </td>
                                <td class="text-center text-muted">{{ $freightBooking->shortage_rate ? '₹' . number_format($freightBooking->shortage_rate, 2) . '/unit' : '-' }}</td>
                                <td class="text-end fw-semibold text-danger">₹{{ number_format($shortageDeduction, 2) }}</td>
                            </tr>
                            @endif
                            @if($damageDeduction > 0)
                            <tr>
                                <td>
                                    <strong>Goods Damage Deduction</strong>
                                    <small class="text-muted d-block">{{ $freightBooking->damage_qty ? $freightBooking->damage_qty . ' Units Damaged' : 'Transit Damage' }}</small>
                                </td>
                                <td class="text-center text-muted">{{ $freightBooking->damage_rate ? '₹' . number_format($freightBooking->damage_rate, 2) . '/unit' : '-' }}</td>
                                <td class="text-end fw-semibold text-danger">₹{{ number_format($damageDeduction, 2) }}</td>
                            </tr>
                            @endif
                            @if($miscExpense > 0)
                            <tr>
                                <td>
                                    <strong>Miscellaneous Deductions / Penalty</strong>
                                    <small class="text-muted d-block">Late arrival or transit penalty</small>
                                </td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-end fw-semibold text-danger">₹{{ number_format($miscExpense, 2) }}</td>
                            </tr>
                            @endif
                            @if($tdsDeduction > 0)
                            <tr>
                                <td>
                                    <strong>TDS Deduction (Income Tax u/s 194C)</strong>
                                    <small class="text-muted d-block">Statutory TDS @ {{ number_format($freightBooking->transporter_tds_percentage ?? 1.0, 2) }}%</small>
                                </td>
                                <td class="text-center text-muted">PAN: {{ $transporter?->transporterMaster?->pan_number ?: 'Not Set' }}</td>
                                <td class="text-end fw-semibold text-danger">₹{{ number_format($tdsDeduction, 2) }}</td>
                            </tr>
                            @endif
                            @if($negativeAdjusted > 0)
                            <tr>
                                <td>
                                    <strong>Previous Debit Adjustment</strong>
                                    <small class="text-muted d-block">Prior trip negative carryover</small>
                                </td>
                                <td class="text-center text-muted">Carryover</td>
                                <td class="text-end fw-semibold text-danger">₹{{ number_format($negativeAdjusted, 2) }}</td>
                            </tr>
                            @endif
                            @if($totalDeductions == 0)
                            <tr>
                                <td colspan="3" class="text-center py-3 text-muted">
                                    <i class="bi bi-shield-check text-success fs-5 d-block mb-1"></i>
                                    No deductions recorded against this trip.
                                </td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot class="border-top" style="background-color: #f8fafc;">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-uppercase text-dark">Total Deductions:</td>
                                <td class="text-end text-danger fs-6">₹{{ number_format($totalDeductions, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement Reconciliation Summary Bar -->
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <span class="text-uppercase small fw-bold text-muted d-block">Net Trip Settlement Amount (After Deductions)</span>
                    <h3 class="fw-bold text-dark mb-1">₹{{ number_format($effectivePayable, 2) }}</h3>
                    <small class="text-muted d-block">
                        Amount in Words: <em>INR {{ $netPayableInWords ?: number_format($effectivePayable, 2) }} Only</em>
                    </small>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <div class="d-inline-block text-start p-3 bg-white rounded border shadow-sm" style="min-width: 240px;">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total Paid / Disbursed:</span>
                            <strong class="text-success">₹{{ number_format($totalPaid, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between pt-1 border-top fw-bold">
                            <span class="text-dark">Net Balance Due:</span>
                            <span class="{{ $balanceDue > 0.01 ? 'text-warning' : 'text-success' }} fs-6">
                                ₹{{ number_format($balanceDue, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Disbursement & Payment History Table -->
    <div class="card shadow-sm border-0 mb-4" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-wallet2 me-2 text-primary"></i>Payment Disbursement History</h6>
            <span class="badge bg-light text-dark border">{{ $payments->count() }} Payments Recorded</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table settlement-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Voucher #</th>
                            <th>Payment Mode</th>
                            <th>Bank / Pump Reference</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th class="text-end pe-3">Disbursed (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $p)
                        <tr>
                            <td class="ps-3 fw-semibold text-dark">{{ $p->date ? date('d-m-Y', strtotime($p->date)) : date('d-m-Y', strtotime($p->created_at)) }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $p->voucher_number ?: ('VP-#' . $p->id) }}</span></td>
                            <td>
                                @php
                                    $mode = strtolower($p->payment_mode ?? '');
                                    $modeBadge = match(true) {
                                        str_contains($mode, 'fuel') => 'bg-info text-white',
                                        str_contains($mode, 'cash') => 'bg-secondary text-white',
                                        str_contains($mode, 'bank') || str_contains($mode, 'neft') => 'bg-primary text-white',
                                        str_contains($mode, 'advance') => 'bg-warning text-dark',
                                        default => 'bg-light text-dark border'
                                    };
                                @endphp
                                <span class="badge {{ $modeBadge }}">{{ ucwords($p->payment_mode ?? 'General') }}</span>
                            </td>
                            <td>
                                @if($p->pump)
                                    <strong><i class="bi bi-fuel-pump me-1"></i>{{ $p->pump->pump_name }}</strong>
                                @elseif($p->reference_transaction_id)
                                    <span class="text-dark font-monospace small">UTR: {{ $p->reference_transaction_id }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $status = strtolower($p->payment_status ?? 'paid');
                                    $statusBadge = match($status) {
                                        'paid' => 'bg-success',
                                        'in-progress' => 'bg-info',
                                        default => 'bg-warning text-dark'
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ ucfirst($status) }}</span>
                            </td>
                            <td class="small text-muted">{{ $p->remark ?: '-' }}</td>
                            <td class="text-end pe-3 fw-bold text-dark">₹{{ number_format($p->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-wallet2 fs-2 d-block mb-1 text-muted"></i>
                                No disbursements recorded yet for this good hire.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($payments->isNotEmpty())
                    <tfoot class="border-top bg-light fw-bold">
                        <tr>
                            <td colspan="6" class="text-end pe-3">Total Amount Disbursed:</td>
                            <td class="text-end pe-3 text-success fs-6">₹{{ number_format($totalPaid, 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================== -->
<!-- DEDICATED PRINT CONTAINER (Standard Logistics A4 Settlement Slip) -->
<!-- ============================================================== -->
<div class="settlement-print">

    <!-- Print Header -->
    <table style="width: 100%; border-bottom: 2px solid #000; margin-bottom: 8px;">
        <tr>
            <td style="width: 70%; vertical-align: top; padding-bottom: 6px;">
                <h2 style="margin: 0; font-size: 16pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                    {{ $company->name }}
                </h2>
                <div style="font-size: 8.5pt; margin-top: 2px;">{{ $company->address }}</div>
                <div style="font-size: 8pt; margin-top: 2px;">
                    <strong>GSTIN:</strong> {{ $company->gst_number ?: 'N/A' }} |
                    <strong>PAN:</strong> {{ $company->pan_number ?: 'N/A' }} |
                    <strong>Phone:</strong> {{ $company->contact_number ?: 'N/A' }}
                </div>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top; padding-bottom: 6px;">
                <div style="border: 2px solid #000; padding: 4px 8px; display: inline-block; text-align: center;">
                    <strong style="font-size: 9.5pt; text-transform: uppercase;">SETTLEMENT SLIP</strong><br>
                    <span style="font-size: 8pt;">Voucher #{{ $freightBooking->voucher_number ?: ('HB-' . $freightBooking->id) }}</span>
                </div>
                <div style="font-size: 8pt; margin-top: 4px;"><strong>Date:</strong> {{ now()->format('d-m-Y') }}</div>
            </td>
        </tr>
    </table>

    <!-- Trip & Transporter Particulars -->
    <table class="print-table">
        <tr>
            <th colspan="4" class="print-section-header">TRIP & TRANSPORTER PARTICULARS</th>
        </tr>
        <tr>
            <td style="width: 25%;"><strong>LR / Bilty No:</strong> {{ $lr?->lr_number ?: '-' }}</td>
            <td style="width: 25%;"><strong>GR Date:</strong> {{ $lr?->gr_date ? date('d-m-Y', strtotime($lr->gr_date)) : '-' }}</td>
            <td style="width: 25%;"><strong>Route:</strong> {{ $sourceCity }} to {{ $destCity }}</td>
            <td style="width: 25%;"><strong>Weight / Unit:</strong> {{ number_format($weight, 3) }} MT</td>
        </tr>
        <tr>
            <td><strong>Truck Number:</strong> {{ $truck?->truck_number ?: '-' }}</td>
            <td><strong>Driver:</strong> {{ $driver?->name ?: '-' }}</td>
            <td colspan="2"><strong>Transporter:</strong> {{ $transporter?->transporterMaster?->name ?? $transporter?->name ?? '-' }} (PAN: {{ $transporter?->transporterMaster?->pan_number ?: '-' }})</td>
        </tr>
        <tr>
            <td><strong>Bank / Beneficiary:</strong> {{ $transporter?->beneficiary_id ?: 'Not Set' }}</td>
            <td><strong>TDS Rate:</strong> {{ number_format($freightBooking->transporter_tds_percentage ?? 1.0, 2) }}%</td>
            <td colspan="2"><strong>Customer / Consignor:</strong> {{ $lr?->customer?->name ?: ($lr?->consignor?->name ?: '-') }}</td>
        </tr>
    </table>

    <!-- Dual Table: Earnings vs Deductions -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
        <tr>
            <!-- Earnings Table -->
            <td style="width: 50%; vertical-align: top; padding-right: 4px;">
                <table class="print-table">
                    <tr>
                        <th colspan="2" class="print-section-header" style="background-color: #dcfce7 !important;">1. GROSS FREIGHT & ADDITIONS</th>
                    </tr>
                    <tr>
                        <td>Base Freight ({{ $freightBooking->is_fixed_amount ? 'Fixed' : 'Rate: ₹' . number_format($rate, 2) }})</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($grossFreight, 2) }}</td>
                    </tr>
                    @if($detention > 0)
                    <tr>
                        <td>Detention / Halting Charges</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($detention, 2) }}</td>
                    </tr>
                    @endif
                    @if($deviation > 0)
                    <tr>
                        <td>Route Deviation Charges</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($deviation, 2) }}</td>
                    </tr>
                    @endif
                    @if($customAdditions > 0)
                    <tr>
                        <td>Other Approved Additions</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($customAdditions, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="background-color: #f3f4f6; font-weight: bold;">
                        <td>TOTAL GROSS FREIGHT (A)</td>
                        <td style="text-align: right; font-size: 9pt;">₹{{ number_format($totalEarnings, 2) }}</td>
                    </tr>
                </table>
            </td>

            <!-- Deductions Table -->
            <td style="width: 50%; vertical-align: top; padding-left: 4px;">
                <table class="print-table">
                    <tr>
                        <th colspan="2" class="print-section-header" style="background-color: #fee2e2 !important;">2. ADVANCES & DEDUCTIONS</th>
                    </tr>
                    <tr>
                        <td>Loading Cash Advance</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($advanceDeduction, 2) }}</td>
                    </tr>
                    @if($shortageDeduction > 0)
                    <tr>
                        <td>Shortage Deduction ({{ $freightBooking->shortage_qty ?: '-' }} Qty)</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($shortageDeduction, 2) }}</td>
                    </tr>
                    @endif
                    @if($damageDeduction > 0)
                    <tr>
                        <td>Damage Claim Deduction</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($damageDeduction, 2) }}</td>
                    </tr>
                    @endif
                    @if($miscExpense > 0)
                    <tr>
                        <td>Transit Expense / Penalty</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($miscExpense, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>TDS u/s 194C (@ {{ number_format($freightBooking->transporter_tds_percentage ?? 1.0, 2) }}%)</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($tdsDeduction, 2) }}</td>
                    </tr>
                    @if($negativeAdjusted > 0)
                    <tr>
                        <td>Previous Debit Adjustment</td>
                        <td style="text-align: right; font-weight: bold;">₹{{ number_format($negativeAdjusted, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="background-color: #f3f4f6; font-weight: bold;">
                        <td>TOTAL DEDUCTIONS (B)</td>
                        <td style="text-align: right; font-size: 9pt;">₹{{ number_format($totalDeductions, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Net Settlement Line Box -->
    <table class="print-table" style="margin-bottom: 8px;">
        <tr style="background-color: #f3f4f6;">
            <td style="width: 70%; font-size: 9.5pt; font-weight: bold;">
                NET PAYABLE FREIGHT (A - B):<br>
                <span style="font-size: 8pt; font-weight: normal;">Amount in Words: <em>INR {{ $netPayableInWords ?: number_format($effectivePayable, 2) }} Only</em></span>
            </td>
            <td style="width: 30%; text-align: right; font-size: 11pt; font-weight: 800;">
                ₹{{ number_format($effectivePayable, 2) }}
            </td>
        </tr>
    </table>

    <!-- Disbursement & Payments Record -->
    @if($payments->isNotEmpty())
    <table class="print-table" style="margin-bottom: 12px;">
        <tr>
            <th colspan="5" class="print-section-header">DISBURSEMENT & PAYMENT HISTORY</th>
        </tr>
        <tr>
            <th style="width: 15%;">Date</th>
            <th style="width: 20%;">Voucher No</th>
            <th style="width: 20%;">Mode</th>
            <th>Reference / Pump</th>
            <th style="width: 20%; text-align: right;">Amount (₹)</th>
        </tr>
        @foreach($payments as $p)
        <tr>
            <td>{{ $p->date ? date('d-m-Y', strtotime($p->date)) : date('d-m-Y', strtotime($p->created_at)) }}</td>
            <td>{{ $p->voucher_number ?: ('VP-' . $p->id) }}</td>
            <td>{{ ucwords($p->payment_mode ?? 'General') }}</td>
            <td>{{ $p->pump?->pump_name ?: ($p->reference_transaction_id ?: '-') }}</td>
            <td style="text-align: right; font-weight: bold;">₹{{ number_format($p->amount, 2) }}</td>
        </tr>
        @endforeach
        <tr style="font-weight: bold; background-color: #f3f4f6;">
            <td colspan="4" style="text-align: right;">Total Disbursed:</td>
            <td style="text-align: right;">₹{{ number_format($totalPaid, 2) }}</td>
        </tr>
        <tr style="font-weight: 800; font-size: 9.5pt;">
            <td colspan="4" style="text-align: right;">FINAL BALANCE DUE TO PAY:</td>
            <td style="text-align: right; color: #000;">₹{{ number_format($balanceDue, 2) }}</td>
        </tr>
    </table>
    @endif

    <!-- Legal Settlement Declaration & Signatures -->
    <div style="font-size: 7.5pt; text-align: justify; margin-bottom: 20px; line-height: 1.25; border: 1px dashed #666; padding: 5px;">
        <strong>Settlement Undertaking:</strong> This statement constitutes full and final settlement of freight, detention, and verified transit deductions for the aforementioned vehicle and trip. The transporter/truck owner certifies that all diesel advances and cash advances have been correctly accounted for, and no further claims shall be made against the company.
    </div>

    <table style="width: 100%; margin-top: 35px; border-collapse: collapse;">
        <tr>
            <td style="width: 33%; text-align: center; vertical-align: top;">
                <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 4px;">
                    <strong>Prepared By</strong><br>
                    <span style="font-size: 7.5pt;">Dispatch & Accounts</span>
                </div>
            </td>
            <td style="width: 33%; text-align: center; vertical-align: top;">
                <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 4px;">
                    <strong>Verified & Approved By</strong><br>
                    <span style="font-size: 7.5pt;">Authorized Signatory</span>
                </div>
            </td>
            <td style="width: 33%; text-align: center; vertical-align: top;">
                <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 4px;">
                    <strong>Transporter / Driver Signature</strong><br>
                    <span style="font-size: 7.5pt;">Acknowledged with Stamp</span>
                </div>
            </td>
        </tr>
    </table>

</div>
@endsection
