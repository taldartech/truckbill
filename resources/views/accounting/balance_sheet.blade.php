@extends('layouts.app')

@section('title', 'Balance Sheet - ' . $company->name)

@push('styles')
<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 10mm 10mm;
        }

        /* Hide all web application chrome, overlays, backdrops and buttons */
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
        .balance-sheet-print {
            display: block !important;
            width: 100% !important;
            color: #000000 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 8.5pt !important;
            line-height: 1.35 !important;
        }

        /* Summary Sheet (Page 1) */
        .bs-summary-sheet {
            display: block !important;
            width: 100% !important;
        }

        /* Detailed Sheet starts on a New Page */
        .bs-detailed-sheet {
            display: block !important;
            width: 100% !important;
            page-break-before: always !important;
            break-before: page !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        /* Conditional Print Controls via JavaScript */
        body.print-only-summary .bs-detailed-sheet {
            display: none !important;
        }

        body.print-only-detailed .bs-summary-sheet {
            display: none !important;
        }
        body.print-only-detailed .bs-detailed-sheet {
            page-break-before: auto !important;
            break-before: auto !important;
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

        .tally-bs-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            background: #ffffff !important;
            page-break-inside: auto !important;
            break-inside: auto !important;
        }

        .tally-bs-table thead {
            display: table-header-group !important;
        }

        .tally-bs-table tfoot {
            display: table-footer-group !important;
        }

        .tally-th-row th {
            padding: 5px 8px !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            background: #ffffff !important;
            color: #000000 !important;
        }

        .tally-col-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: baseline !important;
        }

        .tally-title-spaced {
            font-weight: 700 !important;
            font-size: 10pt !important;
            letter-spacing: 2.5px !important;
            color: #000000 !important;
        }

        .tally-date-text {
            font-weight: 400 !important;
            font-size: 9.5pt !important;
            color: #000000 !important;
            text-align: right !important;
            width: 110px !important;
        }

        .tally-col {
            width: 50% !important;
        }

        .tally-col-border {
            border-right: 1px solid #000000 !important;
        }

        .tally-row-tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-cell {
            padding: 1px 8px !important;
            vertical-align: top !important;
            background: #ffffff !important;
        }

        .tally-spacer {
            height: 8px !important;
        }

        .tally-row {
            display: flex !important;
            justify-content: space-between !important;
            align-items: baseline !important;
            line-height: 1.35 !important;
            padding: 1px 0 !important;
        }

        .tally-particulars {
            flex: 1 !important;
            min-width: 0 !important;
            color: #000000 !important;
        }

        .tally-indent {
            padding-left: 14px !important;
        }

        .tally-indent-2 {
            padding-left: 28px !important;
        }

        .tally-inner-amount {
            width: 100px !important;
            text-align: right !important;
            padding-right: 12px !important;
            white-space: nowrap !important;
            color: #000000 !important;
        }

        .tally-outer-amount {
            width: 110px !important;
            text-align: right !important;
            white-space: nowrap !important;
            color: #000000 !important;
        }

        .tally-outer-spacer {
            width: 110px !important;
        }

        .tally-underline {
            border-bottom: 1px solid #000000 !important;
        }

        .tally-total-row td {
            padding: 5px 8px !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 2px solid #000000 !important;
            background: #ffffff !important;
            color: #000000 !important;
        }

        .tally-total-flex {
            display: flex !important;
            justify-content: space-between !important;
            align-items: baseline !important;
        }

        .tally-total-label {
            font-weight: 700 !important;
            letter-spacing: 3px !important;
            font-size: 10pt !important;
            color: #000000 !important;
        }

        .tally-total-value {
            font-weight: 700 !important;
            font-size: 10pt !important;
            text-align: right !important;
            width: 110px !important;
            color: #000000 !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Screen View: Filter Bar (Hidden on Print) -->
    <div class="card shadow-sm border-0 mb-3 d-print-none" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.balance-sheet') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm w-100">
                        <i class="bi bi-filter"></i> Apply Date
                    </button>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-md-end pt-2 pt-md-0">
                    @if(abs($balanceSheet['difference']) < 0.01)
                        <div class="d-flex align-items-center px-3 py-1 rounded border" style="background: var(--brand-soft-success); border-color: rgba(34,197,94,0.3) !important;">
                            <i class="bi bi-check-circle-fill me-2" style="color: var(--brand-success);"></i>
                            <span class="fw-semibold small" style="color: var(--brand-success);">Balance Sheet Balanced (Assets = Liabilities)</span>
                        </div>
                    @else
                        <div class="d-flex align-items-center px-3 py-1 rounded border" style="background: var(--brand-soft-warning); border-color: rgba(245,158,11,0.3) !important;">
                            <i class="bi bi-exclamation-circle-fill me-2" style="color: var(--brand-warning);"></i>
                            <span class="fw-semibold small text-dark">Difference: ₹{{ number_format($balanceSheet['difference'], 2) }}</span>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Screen View: Balance Sheet 2-Column Main Card (Hidden on Print) -->
    <div class="card app-index-list-card shadow-sm d-print-none">
        <div class="card-header">
            <h3 class="card-title mb-0" id="bs-card-title">
                <i class="bi bi-briefcase me-2"></i>Statement of Assets, Liabilities &amp; Capital
            </h3>
            <div class="app-index-card-toolbar d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group" aria-label="Layout toggle">
                    <button type="button" class="btn btn-secondary btn-sm active" id="btn-view-assets-left" onclick="switchBsLayout('assets-left')" title="Assets &amp; Receivables on Left, Capital &amp; Liabilities on Right">
                        <i class="bi bi-arrow-left-right me-1"></i> Assets on Left
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-view-tally" onclick="switchBsLayout('tally')" title="Traditional Tally layout: Liabilities on Left, Assets on Right">
                        <i class="bi bi-journal-text me-1"></i> Tally Format
                    </button>
                </div>
                <button onclick="printBoth()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print Statement (Both)
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-printer me-1"></i> Print Options
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <button class="dropdown-item" onclick="printBoth()">
                                <i class="bi bi-files me-2 text-primary"></i> Print Both (Summary &amp; Detailed)
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" onclick="printSummary()">
                                <i class="bi bi-file-earmark-text me-2 text-success"></i> Print Summary Only
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" onclick="printDetailed()">
                                <i class="bi bi-card-list me-2 text-info"></i> Print Detailed Only
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="row g-0" id="bs-screen-columns">
                <!-- Assets & Receivables Column -->
                <div class="col-md-6 border-end d-flex flex-column" id="bs-col-assets">
                    <div class="p-3 border-bottom fw-bold text-uppercase small text-dark d-flex justify-content-between" style="background-color: var(--brand-table-header-bg);">
                        <span><i class="bi bi-wallet-fill me-1" style="color: var(--brand-primary);"></i>Assets &amp; Receivables</span>
                        <span>Amount (₹)</span>
                    </div>
                    <div class="p-3 flex-grow-1">
                        @forelse($balanceSheet['asset_groups'] as $pGroup)
                            <div class="mb-3 border rounded p-2" style="border-color: var(--brand-border) !important; background: #fafbfc;">
                                <div class="d-flex justify-content-between align-items-center pb-2 border-bottom fw-bold text-dark">
                                    <span class="fs-6"><i class="bi bi-folder2-open text-warning me-1"></i>{{ $pGroup['name'] }}</span>
                                    <span class="fw-bold fs-6" style="color: var(--brand-primary-dark);">₹{{ number_format($pGroup['subtotal'], 2) }}</span>
                                </div>

                                <!-- Direct ledgers in primary group -->
                                @if(!empty($pGroup['direct_ledgers']))
                                    <ul class="list-group list-group-flush mb-2 ps-2">
                                        @foreach($pGroup['direct_ledgers'] as $dl)
                                        <li class="list-group-item d-flex justify-content-between px-1 py-1 border-0 bg-transparent">
                                            <div>
                                                @if($dl['id'])
                                                    <a href="{{ route('accounting.ledger-statement', $dl['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Statement">
                                                        <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $dl['name'] }}
                                                    </a>
                                                @else
                                                    <span class="fw-bold text-dark">{{ $dl['name'] }}</span>
                                                @endif
                                                @if(!empty($dl['is_negative_cash']))
                                                    <span class="badge bg-danger ms-1">Negative Cash</span>
                                                @endif
                                            </div>
                                            <span class="fw-semibold {{ !empty($dl['is_negative_cash']) ? 'text-danger fw-bold' : 'text-dark' }}">
                                                ₹{{ number_format($dl['amount'], 2) }}
                                            </span>
                                        </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <!-- Sub groups -->
                                @foreach($pGroup['sub_groups'] as $subG)
                                    <div class="mt-2 ms-2 ps-2 border-start border-2" style="border-color: var(--brand-border) !important;">
                                        <div class="d-flex justify-content-between align-items-center py-1 fw-semibold small text-muted">
                                            <span><i class="bi bi-diagram-2 me-1"></i>{{ $subG['name'] }}</span>
                                            <span class="badge bg-light text-dark border">₹{{ number_format($subG['subtotal'], 2) }}</span>
                                        </div>
                                        <ul class="list-group list-group-flush ps-2">
                                            @foreach($subG['ledgers'] as $l)
                                            <li class="list-group-item d-flex justify-content-between px-1 py-1 border-0 bg-transparent small">
                                                <div>
                                                    <a href="{{ route('accounting.ledger-statement', $l['id']) }}" class="text-decoration-none text-dark" title="View Statement">
                                                        {{ $l['name'] }}
                                                    </a>
                                                    @if(!empty($l['is_negative_cash']))
                                                        <span class="badge bg-danger ms-1">Negative Cash</span>
                                                    @endif
                                                </div>
                                                <span class="fw-medium {{ !empty($l['is_negative_cash']) ? 'text-danger fw-bold' : 'text-dark' }}">
                                                    ₹{{ number_format($l['amount'], 2) }}
                                                </span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="text-muted small py-4 text-center">No assets recorded.</div>
                        @endforelse
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between align-items-center fw-bold fs-6 mt-auto" style="background-color: var(--brand-table-header-bg); min-height: 56px;">
                        <span class="text-uppercase small text-muted">Total Assets:</span>
                        <span style="color: var(--brand-primary-dark);">₹{{ number_format($balanceSheet['total_assets'], 2) }}</span>
                    </div>
                </div>

                <!-- Capital & Liabilities Column -->
                <div class="col-md-6 d-flex flex-column" id="bs-col-liabilities">
                    <div class="p-3 border-bottom fw-bold text-uppercase small text-dark d-flex justify-content-between" style="background-color: var(--brand-table-header-bg);">
                        <span><i class="bi bi-shield-check me-1" style="color: var(--brand-accent-blue);"></i>Capital &amp; Liabilities</span>
                        <span>Amount (₹)</span>
                    </div>
                    <div class="p-3 flex-grow-1">
                        @forelse($balanceSheet['liability_groups'] as $pGroup)
                            <div class="mb-3 border rounded p-2" style="border-color: var(--brand-border) !important; background: #fafbfc;">
                                <div class="d-flex justify-content-between align-items-center pb-2 border-bottom fw-bold text-dark">
                                    <span class="fs-6"><i class="bi bi-folder2-open text-warning me-1"></i>{{ $pGroup['name'] }}</span>
                                    <span class="fw-bold fs-6" style="color: var(--brand-primary-dark);">₹{{ number_format($pGroup['subtotal'], 2) }}</span>
                                </div>

                                <!-- Direct ledgers in primary group -->
                                @if(!empty($pGroup['direct_ledgers']))
                                    <ul class="list-group list-group-flush mb-2 ps-2">
                                        @foreach($pGroup['direct_ledgers'] as $dl)
                                        <li class="list-group-item d-flex justify-content-between px-1 py-1 border-0 bg-transparent">
                                            @if($dl['id'])
                                                <a href="{{ route('accounting.ledger-statement', $dl['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Statement">
                                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $dl['name'] }}
                                                </a>
                                            @else
                                                <span class="fw-bold text-dark"><i class="bi bi-pie-chart text-info me-1"></i>{{ $dl['name'] }}</span>
                                            @endif
                                            <span class="fw-semibold text-dark">₹{{ number_format($dl['amount'], 2) }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <!-- Sub groups -->
                                @foreach($pGroup['sub_groups'] as $subG)
                                    <div class="mt-2 ms-2 ps-2 border-start border-2" style="border-color: var(--brand-border) !important;">
                                        <div class="d-flex justify-content-between align-items-center py-1 fw-semibold small text-muted">
                                            <span><i class="bi bi-diagram-2 me-1"></i>{{ $subG['name'] }}</span>
                                            <span class="badge bg-light text-dark border">₹{{ number_format($subG['subtotal'], 2) }}</span>
                                        </div>
                                        <ul class="list-group list-group-flush ps-2">
                                            @foreach($subG['ledgers'] as $l)
                                            <li class="list-group-item d-flex justify-content-between px-1 py-1 border-0 bg-transparent small">
                                                <a href="{{ route('accounting.ledger-statement', $l['id']) }}" class="text-decoration-none text-dark" title="View Statement">
                                                    {{ $l['name'] }}
                                                </a>
                                                <span class="fw-medium text-dark">₹{{ number_format($l['amount'], 2) }}</span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="text-muted small py-4 text-center">No liabilities or equity recorded.</div>
                        @endforelse
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between align-items-center fw-bold fs-6 mt-auto" style="background-color: var(--brand-table-header-bg); min-height: 56px;">
                        <span class="text-uppercase small text-muted">Total Liabilities &amp; Equity:</span>
                        <span style="color: var(--brand-primary-dark);">₹{{ number_format($balanceSheet['total_liabilities'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DEDICATED PRINT STATEMENT (Tally Balance Sheet Format - Visible ONLY on print) -->
    @php
        $asOfCarbon = \Carbon\Carbon::parse($asOfDate);
        $fyStartCarbon = $asOfCarbon->month >= 4 
            ? \Carbon\Carbon::create($asOfCarbon->year, 4, 1) 
            : \Carbon\Carbon::create($asOfCarbon->year - 1, 4, 1);
        $fyPeriodString = $fyStartCarbon->format('j-M-y') . ' to ' . $asOfCarbon->format('j-M-y');
        $asAtString = 'as at ' . $asOfCarbon->format('j-M-y');
        $cityName = $company->city?->name ?? ($company->address ?? '');

        $formatBalance = function($num) {
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

        // -------------------------------------------------------------
        // 1. Prepare Summary Lines for Page 1
        // -------------------------------------------------------------
        $summaryLeftLines = [];
        foreach ($balanceSheet['liability_groups'] as $pGroup) {
            $summaryLeftLines[] = [
                'type' => 'header',
                'name' => $pGroup['name'],
                'amount' => $pGroup['subtotal'],
            ];

            if ($pGroup['name'] === 'Profit & Loss A/c') {
                $summaryLeftLines[] = [
                    'type' => 'sub_item',
                    'name' => 'Opening Balance',
                    'amount' => null,
                    'is_last' => false,
                    'indent' => 1,
                ];
                $summaryLeftLines[] = [
                    'type' => 'sub_item',
                    'name' => 'Current Period',
                    'amount' => $pGroup['subtotal'],
                    'is_last' => true,
                    'indent' => 1,
                ];
            } else {
                $hasDirect = !empty($pGroup['direct_ledgers']);
                $hasSubG = !empty($pGroup['sub_groups']);
                if ($hasDirect) {
                    $dlCount = count($pGroup['direct_ledgers']);
                    foreach ($pGroup['direct_ledgers'] as $idx => $dl) {
                        $summaryLeftLines[] = [
                            'type' => 'sub_item',
                            'name' => $dl['name'],
                            'amount' => $dl['amount'],
                            'is_last' => !$hasSubG && ($idx === $dlCount - 1),
                            'indent' => 1,
                        ];
                    }
                }
                if ($hasSubG) {
                    $sgCount = count($pGroup['sub_groups']);
                    foreach ($pGroup['sub_groups'] as $idx => $subG) {
                        $summaryLeftLines[] = [
                            'type' => 'sub_item',
                            'name' => $subG['name'],
                            'amount' => $subG['subtotal'],
                            'is_last' => ($idx === $sgCount - 1),
                            'indent' => 1,
                        ];
                    }
                }
            }
            $summaryLeftLines[] = ['type' => 'spacer'];
        }

        if ($balanceSheet['difference'] > 0.001) {
            $summaryLeftLines[] = [
                'type' => 'header',
                'name' => 'Suspense A/c',
                'amount' => $balanceSheet['difference'],
            ];
            $summaryLeftLines[] = [
                'type' => 'sub_item',
                'name' => 'Suspense',
                'amount' => $balanceSheet['difference'],
                'is_last' => true,
                'indent' => 1,
            ];
            $summaryLeftLines[] = ['type' => 'spacer'];
        }

        $summaryRightLines = [];
        foreach ($balanceSheet['asset_groups'] as $pGroup) {
            $summaryRightLines[] = [
                'type' => 'header',
                'name' => $pGroup['name'],
                'amount' => $pGroup['subtotal'],
            ];

            $hasDirect = !empty($pGroup['direct_ledgers']);
            $hasSubG = !empty($pGroup['sub_groups']);

            if ($hasDirect) {
                $dlCount = count($pGroup['direct_ledgers']);
                foreach ($pGroup['direct_ledgers'] as $idx => $dl) {
                    $summaryRightLines[] = [
                        'type' => 'sub_item',
                        'name' => $dl['name'] . (!empty($dl['is_negative_cash']) ? ' (Negative Cash)' : ''),
                        'amount' => $dl['amount'],
                        'is_last' => !$hasSubG && ($idx === $dlCount - 1),
                        'indent' => 1,
                    ];
                }
            }

            if ($hasSubG) {
                $sgCount = count($pGroup['sub_groups']);
                foreach ($pGroup['sub_groups'] as $idx => $subG) {
                    $summaryRightLines[] = [
                        'type' => 'sub_item',
                        'name' => $subG['name'],
                        'amount' => $subG['subtotal'],
                        'is_last' => ($idx === $sgCount - 1),
                        'indent' => 1,
                    ];
                }
            }
            $summaryRightLines[] = ['type' => 'spacer'];
        }

        if ($balanceSheet['difference'] < -0.001) {
            $summaryRightLines[] = [
                'type' => 'header',
                'name' => 'Suspense A/c',
                'amount' => abs($balanceSheet['difference']),
            ];
            $summaryRightLines[] = [
                'type' => 'sub_item',
                'name' => 'Suspense',
                'amount' => abs($balanceSheet['difference']),
                'is_last' => true,
                'indent' => 1,
            ];
            $summaryRightLines[] = ['type' => 'spacer'];
        }

        // -------------------------------------------------------------
        // 2. Prepare Detailed Lines for Page 2 (with all child ledgers)
        // -------------------------------------------------------------
        $detailedLeftLines = [];
        foreach ($balanceSheet['liability_groups'] as $pGroup) {
            $detailedLeftLines[] = [
                'type' => 'header',
                'name' => $pGroup['name'],
                'amount' => $pGroup['subtotal'],
            ];

            if ($pGroup['name'] === 'Profit & Loss A/c') {
                $detailedLeftLines[] = [
                    'type' => 'sub_item',
                    'name' => 'Opening Balance',
                    'amount' => null,
                    'is_last' => false,
                    'indent' => 1,
                ];
                $detailedLeftLines[] = [
                    'type' => 'sub_item',
                    'name' => 'Current Period Net Profit',
                    'amount' => $pGroup['subtotal'],
                    'is_last' => true,
                    'indent' => 1,
                ];
            } else {
                $hasDirect = !empty($pGroup['direct_ledgers']);
                $hasSubG = !empty($pGroup['sub_groups']);

                if ($hasDirect) {
                    $dlCount = count($pGroup['direct_ledgers']);
                    foreach ($pGroup['direct_ledgers'] as $idx => $dl) {
                        $detailedLeftLines[] = [
                            'type' => 'sub_item',
                            'name' => $dl['name'],
                            'amount' => $dl['amount'],
                            'is_last' => !$hasSubG && ($idx === $dlCount - 1),
                            'indent' => 1,
                        ];
                    }
                }

                if ($hasSubG) {
                    foreach ($pGroup['sub_groups'] as $subG) {
                        $hasLedgers = !empty($subG['ledgers']);
                        $detailedLeftLines[] = [
                            'type' => 'subgroup_header',
                            'name' => $subG['name'],
                            'amount' => $subG['subtotal'],
                            'indent' => 1,
                        ];

                        if ($hasLedgers) {
                            $lCount = count($subG['ledgers']);
                            foreach ($subG['ledgers'] as $idx => $l) {
                                $detailedLeftLines[] = [
                                    'type' => 'sub_item',
                                    'name' => $l['name'],
                                    'amount' => $l['amount'],
                                    'is_last' => ($idx === $lCount - 1),
                                    'indent' => 2,
                                ];
                            }
                        }
                    }
                }
            }
            $detailedLeftLines[] = ['type' => 'spacer'];
        }

        if ($balanceSheet['difference'] > 0.001) {
            $detailedLeftLines[] = [
                'type' => 'header',
                'name' => 'Suspense A/c',
                'amount' => $balanceSheet['difference'],
            ];
            $detailedLeftLines[] = [
                'type' => 'sub_item',
                'name' => 'Suspense',
                'amount' => $balanceSheet['difference'],
                'is_last' => true,
                'indent' => 1,
            ];
            $detailedLeftLines[] = ['type' => 'spacer'];
        }

        $detailedRightLines = [];
        foreach ($balanceSheet['asset_groups'] as $pGroup) {
            $detailedRightLines[] = [
                'type' => 'header',
                'name' => $pGroup['name'],
                'amount' => $pGroup['subtotal'],
            ];

            $hasDirect = !empty($pGroup['direct_ledgers']);
            $hasSubG = !empty($pGroup['sub_groups']);

            if ($hasDirect) {
                $dlCount = count($pGroup['direct_ledgers']);
                foreach ($pGroup['direct_ledgers'] as $idx => $dl) {
                    $detailedRightLines[] = [
                        'type' => 'sub_item',
                        'name' => $dl['name'] . (!empty($dl['is_negative_cash']) ? ' (Negative Cash)' : ''),
                        'amount' => $dl['amount'],
                        'is_last' => !$hasSubG && ($idx === $dlCount - 1),
                        'indent' => 1,
                    ];
                }
            }

            if ($hasSubG) {
                foreach ($pGroup['sub_groups'] as $subG) {
                    $hasLedgers = !empty($subG['ledgers']);
                    $detailedRightLines[] = [
                        'type' => 'subgroup_header',
                        'name' => $subG['name'],
                        'amount' => $subG['subtotal'],
                        'indent' => 1,
                    ];

                    if ($hasLedgers) {
                        $lCount = count($subG['ledgers']);
                        foreach ($subG['ledgers'] as $idx => $l) {
                            $detailedRightLines[] = [
                                'type' => 'sub_item',
                                'name' => $l['name'] . (!empty($l['is_negative_cash']) ? ' (Negative Cash)' : ''),
                                'amount' => $l['amount'],
                                'is_last' => ($idx === $lCount - 1),
                                'indent' => 2,
                            ];
                        }
                    }
                }
            }
            $detailedRightLines[] = ['type' => 'spacer'];
        }

        if ($balanceSheet['difference'] < -0.001) {
            $detailedRightLines[] = [
                'type' => 'header',
                'name' => 'Suspense A/c',
                'amount' => abs($balanceSheet['difference']),
            ];
            $detailedRightLines[] = [
                'type' => 'sub_item',
                'name' => 'Suspense',
                'amount' => abs($balanceSheet['difference']),
                'is_last' => true,
                'indent' => 1,
            ];
            $detailedRightLines[] = ['type' => 'spacer'];
        }

        $finalTotalLiabilities = $balanceSheet['total_liabilities'] + max(0, $balanceSheet['difference']);
        $finalTotalAssets = $balanceSheet['total_assets'] + max(0, -$balanceSheet['difference']);
    @endphp

    <div class="d-none d-print-block balance-sheet-print">
        <!-- ========================================================================= -->
        <!-- 1. SUMMARY BALANCE SHEET (PAGE 1)                                          -->
        <!-- ========================================================================= -->
        <div class="bs-summary-sheet">
            <div class="tally-print-header">
                <div class="tally-company-name">{{ $company->name }}</div>
                @if($cityName)
                    <div class="tally-company-city">{{ $cityName }}</div>
                @endif
                <div class="tally-report-title">Balance Sheet</div>
                <div class="tally-report-period">{{ $fyPeriodString }}</div>
            </div>

            <!-- T-Account Table: Summary (Row-by-Row Layout) -->
            <table class="tally-bs-table">
                <thead>
                    <tr class="tally-th-row">
                        <th class="tally-col tally-col-border">
                            <div class="tally-col-header">
                                <span class="tally-title-spaced">L i a b i l i t i e s</span>
                                <span class="tally-date-text">{{ $asAtString }}</span>
                            </div>
                        </th>
                        <th class="tally-col">
                            <div class="tally-col-header">
                                <span class="tally-title-spaced">A s s e t s</span>
                                <span class="tally-date-text">{{ $asAtString }}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $summaryRowCount = max(count($summaryLeftLines), count($summaryRightLines));
                    @endphp
                    @for($i = 0; $i < $summaryRowCount; $i++)
                        @php
                            $left = $summaryLeftLines[$i] ?? null;
                            $right = $summaryRightLines[$i] ?? null;
                        @endphp
                        <tr class="tally-row-tr">
                            <!-- Left Side: Liabilities -->
                            <td class="tally-col tally-col-border tally-cell">
                                @if($left)
                                    @if($left['type'] === 'spacer')
                                        <div class="tally-spacer"></div>
                                    @elseif($left['type'] === 'header')
                                        <div class="tally-row">
                                            <span class="tally-particulars fw-bold">{{ $left['name'] }}</span>
                                            <span class="tally-outer-amount fw-bold">
                                                @if(!empty($left['amount']))
                                                    {{ $formatBalance($left['amount']) }}
                                                @endif
                                            </span>
                                        </div>
                                    @elseif($left['type'] === 'sub_item')
                                        <div class="tally-row">
                                            <span class="tally-particulars tally-indent fst-italic">{{ $left['name'] }}</span>
                                            <span class="tally-inner-amount {{ !empty($left['is_last']) ? 'tally-underline' : '' }}">
                                                @if(isset($left['amount']) && $left['amount'] !== null)
                                                    {{ $formatBalance($left['amount']) }}
                                                @endif
                                            </span>
                                            <span class="tally-outer-spacer"></span>
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- Right Side: Assets -->
                            <td class="tally-col tally-cell">
                                @if($right)
                                    @if($right['type'] === 'spacer')
                                        <div class="tally-spacer"></div>
                                    @elseif($right['type'] === 'header')
                                        <div class="tally-row">
                                            <span class="tally-particulars fw-bold">{{ $right['name'] }}</span>
                                            <span class="tally-outer-amount fw-bold">
                                                @if(!empty($right['amount']))
                                                    {{ $formatBalance($right['amount']) }}
                                                @endif
                                            </span>
                                        </div>
                                    @elseif($right['type'] === 'sub_item')
                                        <div class="tally-row">
                                            <span class="tally-particulars tally-indent fst-italic">{{ $right['name'] }}</span>
                                            <span class="tally-inner-amount {{ !empty($right['is_last']) ? 'tally-underline' : '' }}">
                                                @if(isset($right['amount']) && $right['amount'] !== null)
                                                    {{ $formatBalance($right['amount']) }}
                                                @endif
                                            </span>
                                            <span class="tally-outer-spacer"></span>
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endfor
                </tbody>
                <tfoot>
                    <tr class="tally-total-row">
                        <td class="tally-col tally-col-border">
                            <div class="tally-total-flex">
                                <span class="tally-total-label">T o t a l</span>
                                <span class="tally-total-value">{{ $formatBalance($finalTotalLiabilities) }}</span>
                            </div>
                        </td>
                        <td class="tally-col">
                            <div class="tally-total-flex">
                                <span class="tally-total-label">T o t a l</span>
                                <span class="tally-total-value">{{ $formatBalance($finalTotalAssets) }}</span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. DETAILED BALANCE SHEET (PAGE 2 - STARTS ON NEW SHEET)                   -->
        <!-- ========================================================================= -->
        <div class="bs-detailed-sheet">
            <div class="tally-print-header">
                <div class="tally-company-name">{{ $company->name }}</div>
                @if($cityName)
                    <div class="tally-company-city">{{ $cityName }}</div>
                @endif
                <div class="tally-report-title">Balance Sheet (Detailed Schedule)</div>
                <div class="tally-report-period">{{ $fyPeriodString }}</div>
            </div>

            <!-- T-Account Table: Detailed Breakdown (Row-by-Row Layout Starts Right Under Heading) -->
            <table class="tally-bs-table">
                <thead>
                    <tr class="tally-th-row">
                        <th class="tally-col tally-col-border">
                            <div class="tally-col-header">
                                <span class="tally-title-spaced">L i a b i l i t i e s</span>
                                <span class="tally-date-text">{{ $asAtString }}</span>
                            </div>
                        </th>
                        <th class="tally-col">
                            <div class="tally-col-header">
                                <span class="tally-title-spaced">A s s e t s</span>
                                <span class="tally-date-text">{{ $asAtString }}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $detailedRowCount = max(count($detailedLeftLines), count($detailedRightLines));
                    @endphp
                    @for($i = 0; $i < $detailedRowCount; $i++)
                        @php
                            $left = $detailedLeftLines[$i] ?? null;
                            $right = $detailedRightLines[$i] ?? null;
                        @endphp
                        <tr class="tally-row-tr">
                            <!-- Left Side: Liabilities (Detailed) -->
                            <td class="tally-col tally-col-border tally-cell">
                                @if($left)
                                    @if($left['type'] === 'spacer')
                                        <div class="tally-spacer"></div>
                                    @elseif($left['type'] === 'header')
                                        <div class="tally-row">
                                            <span class="tally-particulars fw-bold">{{ $left['name'] }}</span>
                                            <span class="tally-outer-amount fw-bold">
                                                @if(!empty($left['amount']))
                                                    {{ $formatBalance($left['amount']) }}
                                                @endif
                                            </span>
                                        </div>
                                    @elseif($left['type'] === 'subgroup_header')
                                        <div class="tally-row">
                                            <span class="tally-particulars tally-indent fw-semibold">{{ $left['name'] }}</span>
                                            <span class="tally-inner-amount fw-semibold">
                                                @if(isset($left['amount']) && $left['amount'] !== null)
                                                    {{ $formatBalance($left['amount']) }}
                                                @endif
                                            </span>
                                            <span class="tally-outer-spacer"></span>
                                        </div>
                                    @elseif($left['type'] === 'sub_item')
                                        <div class="tally-row">
                                            <span class="tally-particulars {{ $left['indent'] === 2 ? 'tally-indent-2' : 'tally-indent' }} fst-italic">
                                                {{ $left['name'] }}
                                            </span>
                                            <span class="tally-inner-amount {{ !empty($left['is_last']) ? 'tally-underline' : '' }}">
                                                @if(isset($left['amount']) && $left['amount'] !== null)
                                                    {{ $formatBalance($left['amount']) }}
                                                @endif
                                            </span>
                                            <span class="tally-outer-spacer"></span>
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- Right Side: Assets (Detailed) -->
                            <td class="tally-col tally-cell">
                                @if($right)
                                    @if($right['type'] === 'spacer')
                                        <div class="tally-spacer"></div>
                                    @elseif($right['type'] === 'header')
                                        <div class="tally-row">
                                            <span class="tally-particulars fw-bold">{{ $right['name'] }}</span>
                                            <span class="tally-outer-amount fw-bold">
                                                @if(!empty($right['amount']))
                                                    {{ $formatBalance($right['amount']) }}
                                                @endif
                                            </span>
                                        </div>
                                    @elseif($right['type'] === 'subgroup_header')
                                        <div class="tally-row">
                                            <span class="tally-particulars tally-indent fw-semibold">{{ $right['name'] }}</span>
                                            <span class="tally-inner-amount fw-semibold">
                                                @if(isset($right['amount']) && $right['amount'] !== null)
                                                    {{ $formatBalance($right['amount']) }}
                                                @endif
                                            </span>
                                            <span class="tally-outer-spacer"></span>
                                        </div>
                                    @elseif($right['type'] === 'sub_item')
                                        <div class="tally-row">
                                            <span class="tally-particulars {{ $right['indent'] === 2 ? 'tally-indent-2' : 'tally-indent' }} fst-italic">
                                                {{ $right['name'] }}
                                            </span>
                                            <span class="tally-inner-amount {{ !empty($right['is_last']) ? 'tally-underline' : '' }}">
                                                @if(isset($right['amount']) && $right['amount'] !== null)
                                                    {{ $formatBalance($right['amount']) }}
                                                @endif
                                            </span>
                                            <span class="tally-outer-spacer"></span>
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endfor
                </tbody>
                <tfoot>
                    <tr class="tally-total-row">
                        <td class="tally-col tally-col-border">
                            <div class="tally-total-flex">
                                <span class="tally-total-label">T o t a l</span>
                                <span class="tally-total-value">{{ $formatBalance($finalTotalLiabilities) }}</span>
                            </div>
                        </td>
                        <td class="tally-col">
                            <div class="tally-total-flex">
                                <span class="tally-total-label">T o t a l</span>
                                <span class="tally-total-value">{{ $formatBalance($finalTotalAssets) }}</span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    function switchBsLayout(mode) {
        const row = document.getElementById('bs-screen-columns');
        const colAssets = document.getElementById('bs-col-assets');
        const colLiabilities = document.getElementById('bs-col-liabilities');
        const btnAssets = document.getElementById('btn-view-assets-left');
        const btnTally = document.getElementById('btn-view-tally');
        const title = document.getElementById('bs-card-title');

        if (!row || !colAssets || !colLiabilities) return;

        if (mode === 'tally') {
            row.insertBefore(colLiabilities, colAssets);
            colLiabilities.classList.add('border-end');
            colAssets.classList.remove('border-end');
            if (btnTally) {
                btnTally.classList.add('active', 'btn-secondary');
                btnTally.classList.remove('btn-outline-secondary');
            }
            if (btnAssets) {
                btnAssets.classList.remove('active', 'btn-secondary');
                btnAssets.classList.add('btn-outline-secondary');
            }
            if (title) {
                title.innerHTML = '<i class="bi bi-briefcase me-2"></i>Statement of Capital, Liabilities &amp; Assets (Tally Format)';
            }
            localStorage.setItem('tb_bs_layout', 'tally');
        } else {
            row.insertBefore(colAssets, colLiabilities);
            colAssets.classList.add('border-end');
            colLiabilities.classList.remove('border-end');
            if (btnAssets) {
                btnAssets.classList.add('active', 'btn-secondary');
                btnAssets.classList.remove('btn-outline-secondary');
            }
            if (btnTally) {
                btnTally.classList.remove('active', 'btn-secondary');
                btnTally.classList.add('btn-outline-secondary');
            }
            if (title) {
                title.innerHTML = '<i class="bi bi-briefcase me-2"></i>Statement of Assets, Liabilities &amp; Capital';
            }
            localStorage.setItem('tb_bs_layout', 'assets-left');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const savedLayout = localStorage.getItem('tb_bs_layout') || 'assets-left';
        switchBsLayout(savedLayout);
    });

    function printBoth() {
        document.body.classList.remove('print-only-summary', 'print-only-detailed');
        window.print();
    }
    function printSummary() {
        document.body.classList.add('print-only-summary');
        document.body.classList.remove('print-only-detailed');
        window.print();
    }
    function printDetailed() {
        document.body.classList.add('print-only-detailed');
        document.body.classList.remove('print-only-summary');
        window.print();
    }
    window.addEventListener('afterprint', function() {
        document.body.classList.remove('print-only-summary', 'print-only-detailed');
    });
</script>
@endsection
