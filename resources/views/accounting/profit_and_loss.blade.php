@extends('layouts.app')

@section('title', 'Profit & Loss Statement - ' . $company->name)

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
        .pnl-print {
            display: block !important;
            width: 100% !important;
            color: #000000 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 8.5pt !important;
            line-height: 1.35 !important;
        }

        /* Summary Sheet (Page 1) */
        .pnl-summary-sheet {
            display: block !important;
            width: 100% !important;
        }

        /* Detailed Sheet starts on a New Page */
        .pnl-detailed-sheet {
            display: block !important;
            width: 100% !important;
            page-break-before: always !important;
            break-before: page !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        /* Conditional Print Controls via JavaScript */
        body.print-only-summary .pnl-detailed-sheet {
            display: none !important;
        }

        body.print-only-detailed .pnl-summary-sheet {
            display: none !important;
        }
        body.print-only-detailed .pnl-detailed-sheet {
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

        .tally-pnl-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            background: #ffffff !important;
            page-break-inside: auto !important;
            break-inside: auto !important;
        }

        .tally-pnl-table thead {
            display: table-header-group !important;
        }

        .tally-pnl-table tfoot {
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
            white-space: nowrap !important;
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
            border-bottom: 3px double #000000 !important;
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
    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-3 d-print-none" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.profit-and-loss') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm flex-grow-1">
                        <i class="bi bi-search"></i> Generate P&L
                    </button>
                    <a href="{{ route('accounting.profit-and-loss') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- P&L Metric Cards -->
    <div class="row g-3 mb-3 d-print-none">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="background: var(--brand-gradient); border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Freight Revenue (Direct)</div>
                        <h3 class="fw-bold mb-0 text-white">₹{{ number_format($pnl['total_direct_income'], 2) }}</h3>
                    </div>
                    <i class="bi bi-truck fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="background: var(--brand-gradient-info); border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Gross Transport Profit</div>
                        <h3 class="fw-bold mb-0 text-white">₹{{ number_format($pnl['gross_profit'], 2) }}</h3>
                    </div>
                    <i class="bi bi-bar-chart-line fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @php
                $netGrad = $pnl['net_profit'] >= 0 ? 'var(--brand-gradient-success)' : 'var(--brand-gradient-danger)';
            @endphp
            <div class="card border-0 shadow-sm p-3" style="background: {{ $netGrad }}; border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Net Operating {{ $pnl['net_profit'] >= 0 ? 'Profit' : 'Loss' }}</div>
                        <h3 class="fw-bold mb-0 text-white">₹{{ number_format($pnl['net_profit'], 2) }}</h3>
                    </div>
                    <i class="bi bi-wallet2 fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- P&L Double Column Statement Card -->
    <div class="card app-index-list-card shadow-sm d-print-none">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-graph-up me-2"></i>Trading & Operating Profit and Loss Account
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
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
            <!-- 1. TRADING ACCOUNT SECTION -->
            <div class="row g-0 border-bottom">
                <!-- Left: Direct Expenses (Dr) -->
                <div class="col-md-6 border-end">
                    <div class="p-3 border-bottom fw-bold text-uppercase small" style="background-color: var(--brand-table-header-bg); color: var(--brand-danger);">
                        <i class="bi bi-dash-circle me-1"></i>Trading Account - Direct Costs (Dr)
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush mb-2">
                            @forelse($pnl['direct_expenses'] as $exp)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <a href="{{ route('accounting.ledger-statement', $exp['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Ledger">
                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $exp['name'] }}
                                </a>
                                <span class="fw-semibold text-dark">₹{{ number_format($exp['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No direct expenses recorded.</li>
                            @endforelse

                            @if($pnl['gross_profit'] >= 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mt-2" style="background: var(--brand-soft-success); color: var(--brand-success);">
                                <span><i class="bi bi-arrow-down-right-circle me-1"></i>To Gross Profit c/d</span>
                                <span>₹{{ number_format($pnl['gross_profit'], 2) }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Trading Total (Dr):</span>
                        <span style="color: var(--brand-text-primary);">₹{{ number_format($pnl['trading_total'], 2) }}</span>
                    </div>
                </div>

                <!-- Right: Direct Incomes (Cr) -->
                <div class="col-md-6">
                    <div class="p-3 border-bottom fw-bold text-uppercase small" style="background-color: var(--brand-table-header-bg); color: var(--brand-success);">
                        <i class="bi bi-plus-circle me-1"></i>Trading Account - Direct Revenue (Cr)
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush mb-2">
                            @forelse($pnl['direct_incomes'] as $inc)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <a href="{{ route('accounting.ledger-statement', $inc['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Ledger">
                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $inc['name'] }}
                                </a>
                                <span class="fw-semibold text-dark">₹{{ number_format($inc['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No direct revenue recorded.</li>
                            @endforelse

                            @if($pnl['gross_profit'] < 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mt-2" style="background: var(--brand-soft-danger); color: var(--brand-danger);">
                                <span><i class="bi bi-arrow-down-right-circle me-1"></i>By Gross Loss c/d</span>
                                <span>₹{{ number_format(abs($pnl['gross_profit']), 2) }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Trading Total (Cr):</span>
                        <span style="color: var(--brand-text-primary);">₹{{ number_format($pnl['trading_total'], 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. PROFIT & LOSS ACCOUNT SECTION -->
            <div class="row g-0">
                <!-- Left: Indirect Expenses & Net Profit (Dr) -->
                <div class="col-md-6 border-end">
                    <div class="p-3 border-bottom fw-bold text-uppercase small" style="background-color: var(--brand-table-header-bg); color: var(--brand-danger);">
                        <i class="bi bi-receipt me-1"></i>Profit & Loss - Overheads & Net Profit (Dr)
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush mb-2">
                            @if($pnl['gross_profit'] < 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mb-2" style="background: var(--brand-soft-danger); color: var(--brand-danger);">
                                <span><i class="bi bi-arrow-return-right me-1"></i>To Gross Loss b/d</span>
                                <span>₹{{ number_format(abs($pnl['gross_profit']), 2) }}</span>
                            </li>
                            @endif

                            @forelse($pnl['indirect_expenses'] as $exp)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <a href="{{ route('accounting.ledger-statement', $exp['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Ledger">
                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $exp['name'] }}
                                </a>
                                <span class="fw-semibold text-dark">₹{{ number_format($exp['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No indirect expenses recorded.</li>
                            @endforelse

                            @if($pnl['net_profit'] >= 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mt-3" style="background: var(--brand-soft-success); color: var(--brand-success); border: 1px solid rgba(34,197,94,0.3) !important;">
                                <span><i class="bi bi-check2-circle me-1"></i>To Net Profit (Transferred to Capital A/c)</span>
                                <span>₹{{ number_format($pnl['net_profit'], 2) }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Total (Dr):</span>
                        <span style="color: var(--brand-primary-dark);">₹{{ number_format($pnl['pnl_total'], 2) }}</span>
                    </div>
                </div>

                <!-- Right: Indirect Incomes & Net Loss (Cr) -->
                <div class="col-md-6">
                    <div class="p-3 border-bottom fw-bold text-uppercase small" style="background-color: var(--brand-table-header-bg); color: var(--brand-success);">
                        <i class="bi bi-piggy-bank me-1"></i>Profit & Loss - Incomes & Net Loss (Cr)
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush mb-2">
                            @if($pnl['gross_profit'] >= 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mb-2" style="background: var(--brand-soft-success); color: var(--brand-success);">
                                <span><i class="bi bi-arrow-return-right me-1"></i>By Gross Profit b/d</span>
                                <span>₹{{ number_format($pnl['gross_profit'], 2) }}</span>
                            </li>
                            @endif

                            @forelse($pnl['indirect_incomes'] as $inc)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <a href="{{ route('accounting.ledger-statement', $inc['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Ledger">
                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $inc['name'] }}
                                </a>
                                <span class="fw-semibold text-dark">₹{{ number_format($inc['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No indirect income recorded.</li>
                            @endforelse

                            @if($pnl['net_profit'] < 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mt-3" style="background: var(--brand-soft-danger); color: var(--brand-danger); border: 1px solid rgba(239,68,68,0.3) !important;">
                                <span><i class="bi bi-exclamation-circle me-1"></i>By Net Loss (Transferred to Capital A/c)</span>
                                <span>₹{{ number_format(abs($pnl['net_profit']), 2) }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Total (Cr):</span>
                        <span style="color: var(--brand-primary-dark);">₹{{ number_format($pnl['pnl_total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DEDICATED PRINT STATEMENT (Tally Profit & Loss Format - Visible ONLY on print) -->
    @php
        $fromCarbon = \Carbon\Carbon::parse($fromDate);
        $toCarbon = \Carbon\Carbon::parse($toDate);
        $periodString = $fromCarbon->format('j-M-y') . ' to ' . $toCarbon->format('j-M-y');
        $cityName = $company->city?->name ?? ($company->address ?? '');

        $formatBalance = function($num) {
            if ($num === null || $num === '') {
                return '';
            }
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

            return ($isNegative ? '-' : '') . $formatted . $decPart;
        };

        // -------------------------------------------------------------
        // 1. Prepare Summary Lines for Page 1
        // -------------------------------------------------------------
        $summaryLeftLines = [];
        foreach ($pnl['expense_groups'] as $pGroup) {
            $summaryLeftLines[] = [
                'type' => 'header',
                'name' => $pGroup['name'],
                'amount' => $pGroup['subtotal'] > 0.001 ? $pGroup['subtotal'] : null,
            ];
            $summaryLeftLines[] = ['type' => 'spacer'];
        }

        if ($pnl['net_profit'] >= 0) {
            $summaryLeftLines[] = [
                'type' => 'header',
                'name' => 'Nett Profit',
                'amount' => $pnl['net_profit'],
            ];
            $summaryLeftLines[] = ['type' => 'spacer'];
        }

        $summaryRightLines = [];
        foreach ($pnl['income_groups'] as $pGroup) {
            $summaryRightLines[] = [
                'type' => 'header',
                'name' => $pGroup['name'],
                'amount' => $pGroup['subtotal'] > 0.001 ? $pGroup['subtotal'] : null,
            ];
            $summaryRightLines[] = ['type' => 'spacer'];
        }

        if ($pnl['net_profit'] < 0) {
            $summaryRightLines[] = [
                'type' => 'header',
                'name' => 'Nett Loss',
                'amount' => abs($pnl['net_profit']),
            ];
            $summaryRightLines[] = ['type' => 'spacer'];
        }

        // -------------------------------------------------------------
        // 2. Prepare Detailed Lines for Page 2 (with child ledgers)
        // -------------------------------------------------------------
        $detailedLeftLines = [];
        foreach ($pnl['expense_groups'] as $pGroup) {
            $detailedLeftLines[] = [
                'type' => 'header',
                'name' => $pGroup['name'],
                'amount' => $pGroup['subtotal'] > 0.001 ? $pGroup['subtotal'] : null,
            ];

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

            $detailedLeftLines[] = ['type' => 'spacer'];
        }

        if ($pnl['net_profit'] >= 0) {
            $detailedLeftLines[] = [
                'type' => 'header',
                'name' => 'Nett Profit',
                'amount' => $pnl['net_profit'],
            ];
            $detailedLeftLines[] = ['type' => 'spacer'];
        }

        $detailedRightLines = [];
        foreach ($pnl['income_groups'] as $pGroup) {
            $detailedRightLines[] = [
                'type' => 'header',
                'name' => $pGroup['name'],
                'amount' => $pGroup['subtotal'] > 0.001 ? $pGroup['subtotal'] : null,
            ];

            $hasDirect = !empty($pGroup['direct_ledgers']);
            $hasSubG = !empty($pGroup['sub_groups']);

            if ($hasDirect) {
                $dlCount = count($pGroup['direct_ledgers']);
                foreach ($pGroup['direct_ledgers'] as $idx => $dl) {
                    $detailedRightLines[] = [
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
                                'name' => $l['name'],
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

        if ($pnl['net_profit'] < 0) {
            $detailedRightLines[] = [
                'type' => 'header',
                'name' => 'Nett Loss',
                'amount' => abs($pnl['net_profit']),
            ];
            $detailedRightLines[] = ['type' => 'spacer'];
        }

        $finalGrandTotal = $pnl['grand_total'] ?? max($pnl['total_expense'] + max(0, $pnl['net_profit']), $pnl['total_income'] + max(0, -$pnl['net_profit']));
    @endphp

    <div class="d-none d-print-block pnl-print">
        <!-- ========================================================================= -->
        <!-- 1. SUMMARY PROFIT & LOSS ACCOUNT (PAGE 1)                                  -->
        <!-- ========================================================================= -->
        <div class="pnl-summary-sheet">
            <div class="tally-print-header">
                <div class="tally-company-name">{{ $company->name }}</div>
                @if($cityName)
                    <div class="tally-company-city"><u>{{ $cityName }}</u></div>
                @endif
                <div class="tally-report-title">Profit & Loss A/c</div>
                <div class="tally-report-period">{{ $periodString }}</div>
            </div>

            <!-- T-Account Table: Summary (Row-by-Row Layout) -->
            <table class="tally-pnl-table">
                <thead>
                    <tr class="tally-th-row">
                        <th class="tally-col tally-col-border">
                            <div class="tally-col-header">
                                <span class="tally-title-spaced">P a r t i c u l a r s</span>
                                <span class="tally-date-text">{{ $periodString }}</span>
                            </div>
                        </th>
                        <th class="tally-col">
                            <div class="tally-col-header">
                                <span class="tally-title-spaced">P a r t i c u l a r s</span>
                                <span class="tally-date-text">{{ $periodString }}</span>
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
                            <!-- Left Side: Expenses -->
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
                                    @endif
                                @endif
                            </td>

                            <!-- Right Side: Incomes -->
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
                                <span class="tally-total-value">{{ $formatBalance($finalGrandTotal) }}</span>
                            </div>
                        </td>
                        <td class="tally-col">
                            <div class="tally-total-flex">
                                <span class="tally-total-label">T o t a l</span>
                                <span class="tally-total-value">{{ $formatBalance($finalGrandTotal) }}</span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. DETAILED PROFIT & LOSS ACCOUNT (PAGE 2 - REFERENCE FORMAT)             -->
        <!-- ========================================================================= -->
        <div class="pnl-detailed-sheet">
            <div class="tally-print-header">
                <div class="tally-company-name">{{ $company->name }}</div>
                @if($cityName)
                    <div class="tally-company-city"><u>{{ $cityName }}</u></div>
                @endif
                <div class="tally-report-title">Profit & Loss A/c</div>
                <div class="tally-report-period">{{ $periodString }}</div>
            </div>

            <!-- T-Account Table: Detailed Breakdown -->
            <table class="tally-pnl-table">
                <thead>
                    <tr class="tally-th-row">
                        <th class="tally-col tally-col-border">
                            <div class="tally-col-header">
                                <span class="tally-title-spaced">P a r t i c u l a r s</span>
                                <span class="tally-date-text">{{ $periodString }}</span>
                            </div>
                        </th>
                        <th class="tally-col">
                            <div class="tally-col-header">
                                <span class="tally-title-spaced">P a r t i c u l a r s</span>
                                <span class="tally-date-text">{{ $periodString }}</span>
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
                            <!-- Left Side: Expenses (Detailed) -->
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

                            <!-- Right Side: Incomes (Detailed) -->
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
                                <span class="tally-total-value">{{ $formatBalance($finalGrandTotal) }}</span>
                            </div>
                        </td>
                        <td class="tally-col">
                            <div class="tally-total-flex">
                                <span class="tally-total-label">T o t a l</span>
                                <span class="tally-total-value">{{ $formatBalance($finalGrandTotal) }}</span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
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
