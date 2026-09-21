@extends('layouts.app')

@section('title', 'Trial Balance - ' . $company->name)

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
        .trial-balance-print {
            display: block !important;
            width: 100% !important;
            color: #000000 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 8.5pt !important;
            line-height: 1.35 !important;
        }

        .tally-print-header {
            text-align: center !important;
            margin-bottom: 12px !important;
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
            font-size: 11pt !important;
            font-weight: 400 !important;
            color: #000000 !important;
            margin-bottom: 3px !important;
        }

        .tally-report-title {
            font-size: 12.5pt !important;
            font-weight: 700 !important;
            color: #000000 !important;
            margin-bottom: 2px !important;
        }

        .tally-report-period {
            font-size: 10pt !important;
            font-weight: 400 !important;
            color: #000000 !important;
        }

        .tally-tb-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            background: #ffffff !important;
            page-break-inside: auto !important;
            break-inside: auto !important;
        }

        .tally-tb-table thead {
            display: table-header-group !important;
        }

        .tally-tb-table tfoot {
            display: table-footer-group !important;
        }

        .tally-th-row th {
            padding: 5px 8px !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            background: #ffffff !important;
            color: #000000 !important;
            vertical-align: middle !important;
            font-size: 9pt !important;
            font-weight: 700 !important;
        }

        .tally-tb-table th:not(:last-child),
        .tally-tb-table td:not(:last-child) {
            border-right: 1px solid #dddddd !important;
        }

        .tally-row-tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-group-row td {
            background-color: #fafafa !important;
            border-top: 1px solid #e5e5e5 !important;
            font-weight: 700 !important;
            padding: 4px 8px !important;
            color: #000000 !important;
        }

        .tally-ledger-row td {
            background: #ffffff !important;
            padding: 2px 8px !important;
            color: #222222 !important;
            font-size: 8.5pt !important;
        }

        .tally-spacer-tr td {
            height: 4px !important;
            padding: 0 !important;
            border: none !important;
            background: #ffffff !important;
        }

        .tally-cell {
            padding: 3px 8px !important;
            vertical-align: top !important;
            background: #ffffff !important;
            color: #000000 !important;
        }

        .tally-indent {
            padding-left: 18px !important;
        }

        .tally-total-row td {
            border-top: 1px solid #000000 !important;
            border-bottom: 3px double #000000 !important;
            padding: 5px 8px !important;
            font-weight: 700 !important;
            color: #000000 !important;
            font-size: 9pt !important;
            background: #ffffff !important;
        }

        .tally-title-spaced {
            font-weight: 700 !important;
            font-size: 9pt !important;
            letter-spacing: 1.5px !important;
            color: #000000 !important;
            text-transform: uppercase !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-3 d-print-none" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.trial-balance') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm w-100">
                        <i class="bi bi-filter"></i> Apply Date
                    </button>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-md-end gap-2 pt-2 pt-md-0">
                    @if($trialBalance['is_balanced'])
                        <div class="d-flex align-items-center px-3 py-1 rounded border" style="background: var(--brand-soft-success); border-color: rgba(34,197,94,0.3) !important;">
                            <i class="bi bi-check-circle-fill me-2" style="color: var(--brand-success);"></i>
                            <span class="fw-semibold small" style="color: var(--brand-success);">Trial Balance Matched (Debits = Credits)</span>
                        </div>
                    @else
                        <div class="d-flex align-items-center px-3 py-1 rounded border" style="background: var(--brand-soft-danger); border-color: rgba(239,68,68,0.3) !important;">
                            <i class="bi bi-exclamation-triangle-fill me-2" style="color: var(--brand-danger);"></i>
                            <span class="fw-semibold small" style="color: var(--brand-danger);">Unbalanced Difference: ₹{{ number_format($trialBalance['difference'], 2) }}</span>
                        </div>
                    @endif
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Table Card (Screen View) -->
    <div class="card app-index-list-card shadow-sm d-print-none">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-scale me-2"></i>Trial Balance Summary (As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }})
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <button onclick="window.print()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table app-index-table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Particulars / Account Group / Ledger</th>
                            <th class="text-end" style="width: 220px;">Debit Balance (₹)</th>
                            <th class="text-end pe-4" style="width: 220px;">Credit Balance (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trialBalance['groups'] as $g)
                        <!-- Group Header Row -->
                        <tr style="background-color: var(--brand-table-header-bg);">
                            <td class="ps-4 fw-bold text-dark">
                                <i class="bi bi-folder2-open me-2" style="color: var(--brand-accent-blue);"></i>{{ $g['group_name'] }}
                                <span class="badge ms-2 small" style="background-color: var(--brand-soft-primary); color: var(--brand-primary); font-weight: 600;">
                                    {{ ucfirst($g['primary_type']) }}
                                </span>
                            </td>
                            <td class="text-end fw-bold" style="color: var(--brand-success);">
                                {{ $g['total_debit'] > 0 ? '₹' . number_format($g['total_debit'], 2) : '-' }}
                            </td>
                            <td class="text-end pe-4 fw-bold" style="color: var(--brand-danger);">
                                {{ $g['total_credit'] > 0 ? '₹' . number_format($g['total_credit'], 2) : '-' }}
                            </td>
                        </tr>

                        <!-- Child Ledgers -->
                        @foreach($g['ledgers'] as $ledger)
                        <tr>
                            <td class="ps-5">
                                <a href="{{ route('accounting.ledger-statement', $ledger['id']) }}" class="text-decoration-none fw-medium text-dark">
                                    <i class="bi bi-card-text me-2 text-muted"></i>{{ $ledger['name'] }}
                                </a>
                            </td>
                            <td class="text-end" style="color: var(--brand-success);">
                                {{ $ledger['debit'] > 0 ? '₹' . number_format($ledger['debit'], 2) : '-' }}
                            </td>
                            <td class="text-end pe-4" style="color: var(--brand-danger);">
                                {{ $ledger['credit'] > 0 ? '₹' . number_format($ledger['credit'], 2) : '-' }}
                            </td>
                        </tr>
                        @endforeach

                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                No active account balances recorded up to {{ $asOfDate }}.
                            </td>
                        </tr>
                        @endforelse

                        <!-- Grand Totals -->
                        <tr class="fw-bold fs-6" style="background-color: var(--brand-table-header-bg); border-top: 2px solid var(--brand-border);">
                            <td class="ps-4 text-uppercase small text-muted">Grand Total:</td>
                            <td class="text-end" style="color: var(--brand-success);">₹{{ number_format($trialBalance['total_debit'], 2) }}</td>
                            <td class="text-end pe-4" style="color: var(--brand-danger);">₹{{ number_format($trialBalance['total_credit'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- DEDICATED PRINT STATEMENT (Tally Trial Balance Format - Visible ONLY on print) -->
    @php
        $asOfCarbon = \Carbon\Carbon::parse($asOfDate);
        $fyStartCarbon = $asOfCarbon->month >= 4 
            ? \Carbon\Carbon::create($asOfCarbon->year, 4, 1) 
            : \Carbon\Carbon::create($asOfCarbon->year - 1, 4, 1);
        $fyPeriodString = $fyStartCarbon->format('j-M-y') . ' to ' . $asOfCarbon->format('j-M-y');
        $cityName = $company->city?->name ?? ($company->address ?? '');

        $formatAmount = function($num) {
            $isNegative = $num < -0.001;
            $absNum = abs($num);
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
            return $isNegative ? '(-)' . $formatted : $formatted;
        };

        $finalGrandTotal = max($trialBalance['total_debit'], $trialBalance['total_credit']);
    @endphp

    <div class="d-none d-print-block trial-balance-print">
        <div class="tally-print-header">
            <div class="tally-company-name">{{ $company->name }}</div>
            @if($cityName)
                <div class="tally-company-city"><u>{{ $cityName }}</u></div>
            @endif
            <div class="tally-report-title">Trial Balance</div>
            <div class="tally-report-period">{{ $fyPeriodString }}</div>
        </div>

        <table class="tally-tb-table">
            <thead>
                <tr class="tally-th-row">
                    <th style="width: 60%;">
                        <span class="tally-title-spaced">P a r t i c u l a r s</span>
                    </th>
                    <th class="text-end" style="width: 20%;">
                        <span class="tally-title-spaced">D e b i t</span>
                    </th>
                    <th class="text-end" style="width: 20%;">
                        <span class="tally-title-spaced">C r e d i t</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($trialBalance['groups'] as $g)
                    <!-- Primary Group Row -->
                    <tr class="tally-row-tr tally-group-row">
                        <td class="tally-cell fw-bold">
                            {{ $g['group_name'] }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $g['total_debit'] > 0.001 ? $formatAmount($g['total_debit']) : '-' }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $g['total_credit'] > 0.001 ? $formatAmount($g['total_credit']) : '-' }}
                        </td>
                    </tr>

                    <!-- Child Ledgers -->
                    @foreach($g['ledgers'] as $ledger)
                        <tr class="tally-row-tr tally-ledger-row">
                            <td class="tally-cell tally-indent">
                                <em>{{ $ledger['name'] }}</em>
                            </td>
                            <td class="tally-cell text-end">
                                {{ $ledger['debit'] > 0.001 ? $formatAmount($ledger['debit']) : '-' }}
                            </td>
                            <td class="tally-cell text-end">
                                {{ $ledger['credit'] > 0.001 ? $formatAmount($ledger['credit']) : '-' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="tally-spacer-tr"><td colspan="3"></td></tr>
                @empty
                    <tr class="tally-row-tr">
                        <td colspan="3" class="tally-cell text-center py-3">
                            No active account balances recorded up to {{ $asOfDate }}.
                        </td>
                    </tr>
                @endforelse

                <!-- Difference in Opening Balances / Suspense if unbalanced -->
                @if(!$trialBalance['is_balanced'] && $trialBalance['difference'] > 0.001)
                    <tr class="tally-row-tr" style="background: #fff8f8;">
                        <td class="tally-cell fw-bold" style="color: #990000;">
                            Diff. in Opening Balances / Suspense A/c
                        </td>
                        <td class="tally-cell text-end fw-bold" style="color: #990000;">
                            {{ $trialBalance['total_debit'] < $trialBalance['total_credit'] ? $formatAmount($trialBalance['difference']) : '-' }}
                        </td>
                        <td class="tally-cell text-end fw-bold" style="color: #990000;">
                            {{ $trialBalance['total_credit'] < $trialBalance['total_debit'] ? $formatAmount($trialBalance['difference']) : '-' }}
                        </td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="tally-total-row">
                    <td class="tally-cell fw-bold">
                        <span class="tally-title-spaced">T o t a l</span>
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($finalGrandTotal) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($finalGrandTotal) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
