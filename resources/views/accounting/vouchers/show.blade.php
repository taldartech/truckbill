@extends('layouts.app')

@section('title', 'Voucher #' . $voucher->voucher_number . ' - ' . $company->name)

@push('styles')
<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 10mm 10mm;
        }

        /* Hide all web application chrome, overlays, backdrops, banners, and buttons */
        .sidebar-overlay,
        .modal-backdrop,
        .loading-overlay,
        .overlay,
        [class*="overlay"],
        [class*="backdrop"],
        .app-sidebar,
        .main-sidebar,
        .sidebar-wrapper,
        .app-header,
        .app-footer,
        .app-index-card-toolbar,
        .btn,
        .btn-group,
        .dropdown,
        .d-print-none,
        .no-print,
        #subscription-access-banner,
        .alert {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            background: transparent !important;
            width: 0 !important;
            height: 0 !important;
            pointer-events: none !important;
        }

        /* Force pure white background on all page wrappers */
        html,
        body,
        body.layout-fixed,
        body.bg-body-tertiary,
        body.sidebar-open,
        .app-wrapper,
        .app-main,
        .app-content,
        .app-content-header,
        .container-fluid,
        .app-index-page--standard {
            background: #ffffff !important;
            background-color: #ffffff !important;
            background-image: none !important;
            color: #000000 !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        /* Dedicated Tally Print Container */
        .voucher-print {
            display: block !important;
            width: 100% !important;
            color: #000000 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 9pt !important;
            line-height: 1.35 !important;
        }

        .tally-print-header {
            text-align: center !important;
            margin-bottom: 10px !important;
            color: #000000 !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-company-name {
            font-size: 14pt !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            color: #000000 !important;
            margin-bottom: 2px !important;
        }

        .tally-company-city {
            font-size: 10pt !important;
            font-weight: 400 !important;
            color: #000000 !important;
            margin-bottom: 3px !important;
        }

        .tally-company-tax-info {
            font-size: 8.5pt !important;
            color: #333333 !important;
            margin-bottom: 4px !important;
        }

        .tally-report-title {
            font-size: 12pt !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            color: #000000 !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            padding: 3px 0 !important;
            margin-top: 4px !important;
            margin-bottom: 10px !important;
        }

        /* Voucher Meta Info Box */
        .tally-meta-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 10px !important;
            font-size: 9pt !important;
            border: 1px solid #000000 !important;
        }

        .tally-meta-table td {
            padding: 4px 8px !important;
            vertical-align: top !important;
        }

        /* Particulars Table */
        .tally-voucher-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            background: #ffffff !important;
            margin-bottom: 10px !important;
            page-break-inside: auto !important;
            break-inside: auto !important;
            border: 1px solid #000000 !important;
        }

        .tally-voucher-table thead {
            display: table-header-group !important;
        }

        .tally-voucher-table tfoot {
            display: table-footer-group !important;
        }

        .tally-th-row th {
            padding: 6px 8px !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            background: #ffffff !important;
            color: #000000 !important;
            font-size: 9pt !important;
            font-weight: 700 !important;
        }

        .tally-title-spaced {
            letter-spacing: 2px !important;
            font-weight: 700 !important;
        }

        .tally-voucher-table th:not(:last-child),
        .tally-voucher-table td:not(:last-child) {
            border-right: 1px solid #cccccc !important;
        }

        .tally-row-tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-row-tr td {
            padding: 5px 8px !important;
            vertical-align: top !important;
            background: #ffffff !important;
            color: #000000 !important;
            border-bottom: 1px solid #e5e5e5 !important;
        }

        .tally-total-tr td {
            padding: 6px 8px !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 3px double #000000 !important;
            font-weight: 700 !important;
            background: #ffffff !important;
            color: #000000 !important;
        }

        /* Amount in Words & Narration */
        .tally-box-section {
            border: 1px solid #000000 !important;
            padding: 6px 8px !important;
            margin-bottom: 8px !important;
            font-size: 9pt !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-box-title {
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 8pt !important;
            color: #333333 !important;
            margin-bottom: 2px !important;
        }

        .tally-box-content {
            color: #000000 !important;
        }

        /* ITNS 281 Challan Table */
        .tally-challan-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 8.5pt !important;
        }

        .tally-challan-table td {
            padding: 2px 4px !important;
        }

        /* Signature block */
        .tally-signatures-row {
            display: flex !important;
            justify-content: space-between !important;
            margin-top: 45px !important;
            padding-top: 5px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-sign-box {
            text-align: center !important;
            width: 28% !important;
        }

        .tally-sign-line {
            border-top: 1px solid #000000 !important;
            margin-bottom: 4px !important;
        }

        .tally-sign-title {
            font-size: 8.5pt !important;
            font-weight: 600 !important;
            color: #000000 !important;
        }

        .tally-print-footer {
            margin-top: 25px !important;
            display: flex !important;
            justify-content: space-between !important;
            font-size: 7.5pt !important;
            color: #666666 !important;
            border-top: 1px dashed #cccccc !important;
            padding-top: 4px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $totalDebit = (float) $voucher->items->where('type', 'debit')->sum('amount');
    $totalCredit = (float) $voucher->items->where('type', 'credit')->sum('amount');
    $cityName = $company->city?->name ?? ($company->address ?? '');

    $formatAmount = function($num) {
        $absNum = abs((float) $num);
        $parts = explode('.', sprintf("%.2f", $absNum));
        $intPart = $parts[0];
        $decPart = '.' . $parts[1];

        $len = strlen($intPart);
        if ($len > 3) {
            $lastThree = substr($intPart, -3);
            $remaining = substr($intPart, 0, -3);
            $reversed = strrev($remaining);
            $chunks = str_split($reversed, 2);
            $formattedRemaining = strrev(implode(',', $chunks));
            $formatted = $formattedRemaining . ',' . $lastThree;
        } else {
            $formatted = $intPart;
        }

        $formatted .= $decPart;
        return $formatted;
    };

    $amountInWords = function_exists('getIndianCurrency') ? trim(getIndianCurrency($totalDebit)) : '';
@endphp

<!-- ON-SCREEN INTERACTIVE CONTAINER (Hidden on Print) -->
<div class="container-fluid py-3 app-index-page--standard d-print-none">
    <!-- Main Card -->
    <div class="card app-index-list-card shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-receipt me-2"></i>Accounting Voucher #{{ $voucher->voucher_number }}
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                @if(!$voucher->isReversed() && $voucher->source_type !== \App\Models\AccountingVoucher::class)
                    <form method="POST" action="{{ route('accounting.vouchers.reverse', $voucher) }}" onsubmit="return confirm('Are you sure you want to reverse voucher #{{ $voucher->voucher_number }}? This will post an offsetting double-entry Journal Voucher in compliance with accounting audit standards.');" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger app-index-header-btn">
                            <i class="bi bi-arrow-counterclockwise"></i> Reverse Voucher
                        </button>
                    </form>
                @endif
                <button onclick="window.print()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print Voucher
                </button>
                <a href="{{ route('accounting.vouchers.index') }}" class="btn app-index-header-btn">
                    <i class="bi bi-arrow-left"></i> Back to Vouchers
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            @if($voucher->isReversed())
                @php $reversal = $voucher->reversalVouchers->first(); @endphp
                <div class="alert alert-danger d-flex align-items-center mb-3">
                    <i class="bi bi-exclamation-octagon-fill fs-4 me-2"></i>
                    <div>
                        <strong>Voucher Reversed:</strong> This voucher was formally reversed by offsetting Journal Voucher
                        @if($reversal)
                            <a href="{{ route('accounting.vouchers.show', $reversal->id) }}" class="fw-bold text-danger text-decoration-underline">#{{ $reversal->voucher_number }}</a>
                            on {{ $reversal->voucher_date->format('d-m-Y') }}.
                        @endif
                    </div>
                </div>
            @elseif($voucher->source_type === \App\Models\AccountingVoucher::class && $voucher->source_id)
                <div class="alert alert-warning d-flex align-items-center mb-3">
                    <i class="bi bi-arrow-counterclockwise fs-4 me-2"></i>
                    <div>
                        <strong>Offsetting Reversal Voucher:</strong> This is a formal double-entry reversal entry for Voucher
                        <a href="{{ route('accounting.vouchers.show', $voucher->source_id) }}" class="fw-bold text-dark text-decoration-underline">#{{ $voucher->reference_number ?: $voucher->source_id }}</a>.
                    </div>
                </div>
            @endif

            <!-- Summary Info Row -->
            <div class="row g-3 p-3 rounded mb-4" style="background-color: var(--brand-bg); border: 1px solid var(--brand-border);">
                <div class="col-md-3 col-sm-6">
                    <small class="text-muted text-uppercase fw-semibold d-block">Voucher Type</small>
                    @php
                        $badgeStyle = match(strtolower($voucher->voucher_type)) {
                            'sales' => 'background-color: var(--brand-soft-success); color: var(--brand-success);',
                            'purchase' => 'background-color: var(--brand-soft-danger); color: var(--brand-danger);',
                            'payment' => 'background-color: var(--brand-soft-warning); color: var(--brand-warning);',
                            'receipt' => 'background-color: var(--brand-soft-info); color: var(--brand-info);',
                            'contra' => 'background-color: var(--brand-soft-secondary); color: var(--brand-text-muted);',
                            'credit_note' => 'background-color: #fee2e2; color: #dc2626;',
                            'debit_note' => 'background-color: #fef3c7; color: #b45309;',
                            default => 'background-color: var(--brand-soft-primary); color: var(--brand-primary);'
                        };
                    @endphp
                    <span class="badge border mt-1" style="{{ $badgeStyle }} font-size: 0.875rem; font-weight: 600;">
                        {{ ucwords(str_replace('_', ' ', $voucher->voucher_type)) }}
                    </span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-muted text-uppercase fw-semibold d-block">Voucher Date</small>
                    <span class="fw-bold text-dark fs-6">{{ $voucher->voucher_date->format('d-m-Y') }}</span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-muted text-uppercase fw-semibold d-block">Reference Number</small>
                    <span class="fw-semibold text-dark fs-6">{{ $voucher->reference_number ?: '-' }}</span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-muted text-uppercase fw-semibold d-block">Entered By</small>
                    <span class="fw-semibold text-dark fs-6">{{ $voucher->user?->name ?: 'System Auto-Post' }}</span>
                </div>
            </div>

            @if($voucher->challan_bsr_code || $voucher->challan_serial_no)
            <!-- Statutory TDS Challan ITNS 281 Banner -->
            <div class="p-3 rounded mb-4 border" style="background-color: #eff6ff; border-color: #bfdbfe !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-primary"><i class="bi bi-file-earmark-check me-1"></i>Govt TDS Challan ITNS 281 Details</span>
                    <span class="badge bg-primary text-white">Form 26Q & 3CD Audit</span>
                </div>
                <div class="row g-3 small">
                    <div class="col-md-3">
                        <span class="text-muted d-block">BSR Code:</span>
                        <strong class="text-dark">{{ $voucher->challan_bsr_code ?: '-' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted d-block">Challan Serial No:</span>
                        <strong class="text-dark">{{ $voucher->challan_serial_no ?: '-' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted d-block">Tender Date:</span>
                        <strong class="text-dark">{{ $voucher->challan_tender_date ? $voucher->challan_tender_date->format('d-m-Y') : '-' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted d-block">Major Head:</span>
                        <strong class="text-dark">{{ $voucher->challan_head ?: '0021 - Non-Company Deductees' }}</strong>
                    </div>
                </div>
            </div>
            @endif

            <!-- Double-Entry Particulars Table -->
            <div class="table-responsive mb-4">
                <table class="table app-index-table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;" class="text-center">Dr / Cr</th>
                            <th>Particulars / General Ledger Account</th>
                            <th style="width: 200px;">Cost Centre</th>
                            <th>Line Memo</th>
                            <th style="width: 170px;" class="text-end">Debit (₹)</th>
                            <th style="width: 170px;" class="text-end">Credit (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucher->items as $item)
                        <tr>
                            <td class="text-center fw-bold fs-6 {{ $item->type === 'debit' ? 'text-success' : 'text-danger' }}">
                                {{ $item->type === 'debit' ? 'Dr' : 'Cr' }}
                            </td>
                            <td>
                                <a href="{{ route('accounting.ledger-statement', $item->ledger_id) }}" class="fw-bold text-decoration-none" style="color: var(--brand-text-primary);">
                                    {{ $item->ledger?->name }}
                                </a>
                                <small class="text-muted d-block">{{ $item->ledger?->accountGroup?->name }}</small>
                            </td>
                            <td>
                                @if($item->costCentre)
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-truck me-1"></i>{{ $item->costCentre->name }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $item->particulars_note ?: '-' }}</td>
                            <td class="text-end fw-bold" style="color: var(--brand-success);">
                                {{ $item->type === 'debit' ? '₹' . number_format($item->amount, 2) : '-' }}
                            </td>
                            <td class="text-end fw-bold" style="color: var(--brand-danger);">
                                {{ $item->type === 'credit' ? '₹' . number_format($item->amount, 2) : '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light fw-bold fs-6">
                        <tr>
                            <td colspan="4" class="text-end pe-3">Voucher Total:</td>
                            <td class="text-end" style="color: var(--brand-success);">
                                ₹{{ number_format($totalDebit, 2) }}
                            </td>
                            <td class="text-end" style="color: var(--brand-danger);">
                                ₹{{ number_format($totalCredit, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Narration Box -->
            @if($voucher->narration)
            <div class="border rounded p-3" style="background-color: var(--brand-bg); border-color: var(--brand-border) !important;">
                <small class="fw-bold text-uppercase text-muted d-block mb-1">Narration / Remarks</small>
                <div class="text-dark">{{ $voucher->narration }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- DEDICATED PRINT CONTAINER (Standard Tally Format - Visible ONLY on Print) -->
<div class="d-none d-print-block voucher-print">
    <!-- Header -->
    <div class="tally-print-header">
        <div class="tally-company-name">{{ $company->name }}</div>
        @if($company->address || $cityName)
            <div class="tally-company-city">
                <u>{{ $company->address ?: $cityName }}</u>
            </div>
        @endif
        @if($company->gst_number || $company->pan_number)
            <div class="tally-company-tax-info">
                @if($company->gst_number) GSTIN: <strong>{{ $company->gst_number }}</strong> @endif
                @if($company->gst_number && $company->pan_number) | @endif
                @if($company->pan_number) PAN: <strong>{{ $company->pan_number }}</strong> @endif
                @if($company->contact_number) | Ph: {{ $company->contact_number }} @endif
            </div>
        @endif
        <div class="tally-report-title">
            {{ strtoupper(str_replace('_', ' ', $voucher->voucher_type)) }} VOUCHER
        </div>
    </div>

    <!-- Voucher Details Meta Header Table -->
    <table class="tally-meta-table">
        <tr>
            <td style="width: 55%;">
                <strong>Voucher No. :</strong> <span style="font-size: 10pt; font-weight: 700;">{{ $voucher->voucher_number }}</span>
            </td>
            <td style="width: 45%; text-align: right;">
                <strong>Date :</strong> <span style="font-size: 10pt; font-weight: 700;">{{ $voucher->voucher_date->format('d-M-Y') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <strong>Ref. / Cheque No. :</strong> {{ $voucher->reference_number ?: '-' }}
            </td>
            <td style="text-align: right;">
                <strong>Entered By :</strong> {{ $voucher->user?->name ?: 'System Auto-Post' }}
            </td>
        </tr>
    </table>

    <!-- Particulars Table -->
    <table class="tally-voucher-table">
        <thead>
            <tr class="tally-th-row">
                <th style="width: 50px;" class="text-center">
                    <span class="tally-title-spaced">Type</span>
                </th>
                <th>
                    <span class="tally-title-spaced">P a r t i c u l a r s</span>
                </th>
                <th style="width: 150px;">
                    <span class="tally-title-spaced">Cost Centre</span>
                </th>
                <th class="text-end" style="width: 120px;">
                    <span class="tally-title-spaced">Debit (₹)</span>
                </th>
                <th class="text-end" style="width: 120px;">
                    <span class="tally-title-spaced">Credit (₹)</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($voucher->items as $item)
            <tr class="tally-row-tr">
                <td class="text-center fw-bold">
                    {{ $item->type === 'debit' ? 'Dr' : 'Cr' }}
                </td>
                <td>
                    <div style="font-weight: 700; font-size: 9.5pt;">{{ $item->ledger?->name }}</div>
                    <div style="font-size: 8pt; color: #555555;">{{ $item->ledger?->accountGroup?->name }}</div>
                    @if($item->particulars_note)
                        <div style="font-size: 8pt; font-style: italic; color: #333333; margin-top: 2px;">
                            Note: {{ $item->particulars_note }}
                        </div>
                    @endif
                </td>
                <td>
                    {{ $item->costCentre?->name ?: '-' }}
                </td>
                <td class="text-end fw-bold">
                    {{ $item->type === 'debit' ? $formatAmount($item->amount) : '-' }}
                </td>
                <td class="text-end fw-bold">
                    {{ $item->type === 'credit' ? $formatAmount($item->amount) : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tally-total-tr">
                <td colspan="3" class="text-end pe-3">
                    <span class="tally-title-spaced">T o t a l :</span>
                </td>
                <td class="text-end fw-bold">
                    ₹{{ $formatAmount($totalDebit) }}
                </td>
                <td class="text-end fw-bold">
                    ₹{{ $formatAmount($totalCredit) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Amount in Words Box -->
    <div class="tally-box-section">
        <div class="tally-box-title">Amount (in words):</div>
        <div class="tally-box-content fw-bold">
            INR {{ $amountInWords ?: $formatAmount($totalDebit) }} Only
        </div>
    </div>

    <!-- Narration Box -->
    @if($voucher->narration)
    <div class="tally-box-section">
        <div class="tally-box-title">Narration / Remarks:</div>
        <div class="tally-box-content">
            {{ $voucher->narration }}
        </div>
    </div>
    @endif

    <!-- Statutory TDS ITNS 281 Challan (if applicable) -->
    @if($voucher->challan_bsr_code || $voucher->challan_serial_no)
    <div class="tally-box-section" style="background: #fafafa !important;">
        <div class="tally-box-title">Govt TDS Challan (ITNS 281) Details (Form 26Q / 3CD):</div>
        <table class="tally-challan-table">
            <tr>
                <td style="width: 25%;"><strong>BSR Code:</strong> {{ $voucher->challan_bsr_code ?: '-' }}</td>
                <td style="width: 25%;"><strong>Challan No.:</strong> {{ $voucher->challan_serial_no ?: '-' }}</td>
                <td style="width: 25%;"><strong>Tender Date:</strong> {{ $voucher->challan_tender_date ? $voucher->challan_tender_date->format('d-M-Y') : '-' }}</td>
                <td style="width: 25%;"><strong>Major Head:</strong> {{ $voucher->challan_head ?: '0021 - Non-Company Deductees' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Signatures Row -->
    <div class="tally-signatures-row">
        <div class="tally-sign-box">
            <div class="tally-sign-line"></div>
            <div class="tally-sign-title">Prepared by: {{ $voucher->user?->name ?: 'Administrator' }}</div>
        </div>
        <div class="tally-sign-box">
            <div class="tally-sign-line"></div>
            <div class="tally-sign-title">Verified / Checked by</div>
        </div>
        <div class="tally-sign-box">
            <div class="tally-sign-line"></div>
            <div class="tally-sign-title">Authorised Signatory</div>
        </div>
    </div>

    <!-- Print Footer -->
    <div class="tally-print-footer">
        <span>This is a computer generated accounting voucher.</span>
        <span>Printed on: {{ now()->format('d-M-Y h:i A') }}</span>
    </div>
</div>
@endsection
