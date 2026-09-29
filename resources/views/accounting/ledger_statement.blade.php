@extends('layouts.app')

@section('title', 'Ledger Statement (Account Book) - ' . $company->name)

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            border-color: var(--brand-border) !important;
            font-size: 0.875rem !important;
            min-height: 38px;
        }

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
            .ledger-statement-print {
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

            .tally-company-tax-info {
                font-size: 8.5pt !important;
                color: #333333 !important;
                margin-bottom: 4px !important;
            }

            .tally-report-title {
                font-size: 12.5pt !important;
                font-weight: 700 !important;
                color: #000000 !important;
                margin-bottom: 2px !important;
            }

            .tally-report-subtitle {
                font-size: 9.5pt !important;
                font-weight: 600 !important;
                color: #222222 !important;
                margin-bottom: 2px !important;
            }

            .tally-report-period {
                font-size: 10pt !important;
                font-weight: 400 !important;
                color: #000000 !important;
            }

            .tally-statement-table {
                width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                background: #ffffff !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }

            .tally-statement-table thead {
                display: table-header-group !important;
            }

            .tally-statement-table tfoot {
                display: table-footer-group !important;
            }

            .tally-th-row th {
                padding: 5px 6px !important;
                border-top: 1px solid #000000 !important;
                border-bottom: 1px solid #000000 !important;
                background: #ffffff !important;
                color: #000000 !important;
                vertical-align: middle !important;
                font-size: 8.5pt !important;
                font-weight: 700 !important;
            }

            .tally-statement-table th:not(:last-child),
            .tally-statement-table td:not(:last-child) {
                border-right: 1px solid #dddddd !important;
            }

            .tally-row-tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .tally-cell {
                padding: 4px 6px !important;
                vertical-align: top !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 8.5pt !important;
                line-height: 1.3 !important;
            }

            .tally-title-spaced {
                font-weight: 700 !important;
                font-size: 9pt !important;
                letter-spacing: 2px !important;
                color: #000000 !important;
            }

            .tally-op-row td {
                background-color: #fafafa !important;
                border-bottom: 1px solid #e5e5e5 !important;
                font-weight: 600 !important;
            }

            .tally-current-total-row td {
                padding: 4px 6px !important;
                border-top: 1px solid #000000 !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-weight: 700 !important;
                font-size: 8.5pt !important;
            }

            .tally-closing-row td {
                padding: 4px 6px !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-weight: 700 !important;
                font-size: 8.5pt !important;
            }

            .tally-total-row td {
                padding: 5px 6px !important;
                border-top: 1px solid #000000 !important;
                border-bottom: 3px double #000000 !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-weight: 700 !important;
                font-size: 9pt !important;
            }

            .tally-signatures-row {
                margin-top: 30px !important;
                display: flex !important;
                justify-content: space-between !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .tally-sign-box {
                width: 28% !important;
                text-align: center !important;
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
                margin-top: 15px !important;
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
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Card (Hidden on print) -->
    <div class="card shadow-sm border-0 mb-3 d-print-none" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.ledger-statement') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Select Ledger Account <span class="text-danger">*</span></label>
                    <select name="ledger_id" class="form-select form-select-sm select2" required>
                        <option value="">-- Choose Ledger Account --</option>
                        @foreach($ledgers as $l)
                            <option value="{{ $l->id }}" {{ (string)$selectedLedgerId === (string)$l->id ? 'selected' : '' }}>
                                {{ $l->name }} ({{ $l->getGroupHeadDisplay() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Search LR / Truck / Vch</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="e.g. MH12, LR-101" value="{{ $searchQuery ?? '' }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm flex-grow-1">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('accounting.ledger-statement') }}" class="btn btn-secondary btn-sm" title="Reset All Filters">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($statement)
    <!-- Summary Header Cards -->
    <div class="card shadow-sm border-0 mb-3 d-print-none" style="border-radius: 12px; border: 1px solid var(--brand-border) !important; background: #fff;">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge" style="background-color: var(--brand-soft-primary); color: var(--brand-primary); font-weight: 600;">
                            <i class="bi bi-diagram-3 me-1"></i>Group Head: {{ $statement['ledger']->getGroupHeadDisplay() }}
                        </span>
                    </div>
                    <h4 class="fw-bold mb-1" style="color: var(--brand-text-primary);">{{ $statement['ledger']->name }}</h4>
                    <div class="small text-muted">
                        @if($statement['ledger']->gstin) GSTIN: <code>{{ $statement['ledger']->gstin }}</code> @endif
                        @if($statement['ledger']->pan_number) | PAN: <code>{{ $statement['ledger']->pan_number }}</code> @endif
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="row text-center g-2">
                        <div class="col-3 col-sm-3">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block small">Opening Balance</small>
                                <span class="fw-bold text-dark">{{ $statement['opening_balance']['formatted'] }}</span>
                                @if(!empty($statement['opening_balance']['reference']))
                                    <small class="badge bg-white text-secondary border d-block mt-1 text-truncate" title="Opening Balance Reference: {{ $statement['opening_balance']['reference'] }}">
                                        Ref: {{ $statement['opening_balance']['reference'] }}
                                    </small>
                                @endif
                            </div>
                        </div>
                        <div class="col-3 col-sm-3">
                            <div class="border rounded p-2" style="background: var(--brand-soft-success);">
                                <small class="text-muted d-block small">Period Debits (+)</small>
                                <span class="fw-bold" style="color: var(--brand-success);">₹{{ number_format($statement['total_debit'], 2) }}</span>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3">
                            <div class="border rounded p-2" style="background: var(--brand-soft-danger);">
                                <small class="text-muted d-block small">Period Credits (-)</small>
                                <span class="fw-bold" style="color: var(--brand-danger);">₹{{ number_format($statement['total_credit'], 2) }}</span>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3">
                            @if(!empty($statement['closing_balance']['is_negative_cash']))
                                <div class="border rounded p-2" style="background: var(--brand-soft-danger); border-color: var(--brand-danger) !important;">
                                    <small class="text-danger fw-bold d-block small"><i class="bi bi-exclamation-triangle-fill me-1"></i>Closing (Overdrawn)</small>
                                    <span class="fw-bold text-danger">{{ $statement['closing_balance']['formatted'] }}</span>
                                </div>
                            @else
                                <div class="border rounded p-2" style="background: var(--brand-active-bg); border-color: var(--brand-primary) !important;">
                                    <small class="text-muted d-block small">Closing Balance</small>
                                    <span class="fw-bold" style="color: var(--brand-primary-dark);">{{ $statement['closing_balance']['formatted'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill-to-Bill Outstandings Section (Item 6 & 7) -->
    @if($outstandings)
    <div class="card shadow-sm border-0 mb-3 d-print-none" style="border-radius: 12px; border: 1px solid var(--brand-border) !important;">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-2.5 gap-2">
            <h6 class="mb-0 fw-bold" style="color: var(--brand-text-primary);">
                <i class="bi bi-card-checklist me-2" style="color: var(--brand-primary);"></i>Bill-to-Bill Outstandings & Matched Payments ({{ ucfirst($outstandings['entity_type'] ?? '') }})
            </h6>
            <!-- Filter Pills -->
            <div class="btn-group btn-group-sm d-print-none">
                @php
                    $routeParams = ['ledger_id' => $selectedLedgerId, 'from_date' => $fromDate, 'to_date' => $toDate, 'search' => $searchQuery];
                @endphp
                <a href="{{ route('accounting.ledger-statement', array_merge($routeParams, ['bill_filter' => null])) }}" class="btn {{ empty($billFilter) ? 'btn-brand-primary' : 'btn-outline-secondary' }}">
                    All ({{ $outstandings['unfiltered_count'] ?? $outstandings['total_count'] ?? 0 }})
                </a>
                <a href="{{ route('accounting.ledger-statement', array_merge($routeParams, ['bill_filter' => 'pending'])) }}" class="btn {{ $billFilter === 'pending' ? 'btn-danger' : 'btn-outline-danger' }}">
                    Pending
                </a>
                <a href="{{ route('accounting.ledger-statement', array_merge($routeParams, ['bill_filter' => 'partial'])) }}" class="btn {{ $billFilter === 'partial' ? 'btn-warning' : 'btn-outline-warning' }}">
                    Partially Paid
                </a>
                <a href="{{ route('accounting.ledger-statement', array_merge($routeParams, ['bill_filter' => 'settled'])) }}" class="btn {{ $billFilter === 'settled' ? 'btn-success' : 'btn-outline-success' }}">
                    Settled
                </a>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 mb-3 text-center">
                <div class="col-4 col-md-4">
                    <div class="p-2 border rounded bg-light">
                        <small class="text-muted d-block small">Total Invoiced / Trip Freight</small>
                        <span class="fw-bold text-dark fs-6">₹{{ number_format($outstandings['total_billed'], 2) }}</span>
                    </div>
                </div>
                <div class="col-4 col-md-4">
                    <div class="p-2 border rounded" style="background: var(--brand-soft-success);">
                        <small class="text-muted d-block small">Total Settled / Cleared</small>
                        <span class="fw-bold fs-6" style="color: var(--brand-success);">₹{{ number_format($outstandings['total_cleared'], 2) }}</span>
                    </div>
                </div>
                <div class="col-4 col-md-4">
                    <div class="p-2 border rounded" style="background: var(--brand-soft-danger);">
                        <small class="text-muted d-block small">Total Pending / Outstanding</small>
                        <span class="fw-bold fs-6" style="color: var(--brand-danger);">₹{{ number_format($outstandings['total_pending'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead style="background-color: var(--brand-table-header-bg);">
                        <tr>
                            <th class="ps-2">Bill / Inv / Ref</th>
                            <th>Date</th>
                            @if(strcasecmp($outstandings['entity_type'] ?? '', 'Customer') !== 0)
                                <th>Truck No / LR No</th>
                            @endif
                            <th class="text-end">Total Amount (₹)</th>
                            <th class="text-end">Cleared (₹)</th>
                            <th class="text-end">Pending (₹)</th>
                            <th class="text-center">Status</th>
                            <th>Matched Payments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outstandings['bills'] as $bill)
                        <tr>
                            <td class="ps-2 fw-semibold">
                                @if($bill['type'] === 'invoice')
                                    <a href="{{ route('invoices.show', $bill['id']) }}" class="text-decoration-none">
                                        {{ $bill['bill_no'] }}
                                    </a>
                                @else
                                    <a href="{{ route('freight_bookings.show', $bill['id']) }}" class="text-decoration-none">
                                        {{ $bill['bill_no'] }}
                                    </a>
                                @endif
                            </td>
                            <td class="small">{{ $bill['bill_date'] }}</td>
                            @if(strcasecmp($outstandings['entity_type'] ?? '', 'Customer') !== 0)
                                <td>
                                    <span class="badge border bg-light text-dark">{{ $bill['truck_no'] ?? '-' }}</span>
                                    <span class="badge border bg-light text-primary">LR: {{ $bill['lr_no'] ?? '-' }}</span>
                                </td>
                            @endif
                            <td class="text-end fw-semibold">₹{{ number_format($bill['total_amount'], 2) }}</td>
                            <td class="text-end text-success fw-semibold">₹{{ number_format($bill['cleared_amount'], 2) }}</td>
                            <td class="text-end fw-bold {{ $bill['pending_amount'] > 0 ? 'text-danger' : 'text-muted' }}">
                                ₹{{ number_format($bill['pending_amount'], 2) }}
                            </td>
                            <td class="text-center">
                                @if($bill['status'] === 'settled')
                                    <span class="badge bg-success">Settled</span>
                                @elseif($bill['status'] === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($bill['payments']) && count($bill['payments']) > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($bill['payments'] as $p)
                                            <span class="badge bg-light text-dark border small" title="{{ $p['remark'] ?? '' }}">
                                                {{ $p['date'] }}: ₹{{ number_format($p['amount'], 2) }} ({{ $p['mode'] }})
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">No payment recorded</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ strcasecmp($outstandings['entity_type'] ?? '', 'Customer') !== 0 ? 8 : 7 }}" class="text-center py-3 text-muted small">
                                No bills found for the selected filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Table Card -->
    <div class="card app-index-list-card shadow-sm d-print-none">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-book-open me-2"></i>Account Statement Transactions
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <button onclick="window.print()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print Statement
                </button>
            </div>
        </div>

        @if(!empty($statement['is_filtered']))
            <div class="alert alert-info py-2 px-3 mx-3 my-2 d-flex justify-content-between align-items-center small" style="border-radius: 8px;">
                <span>
                    <i class="bi bi-funnel-fill me-1"></i> Filter active: <strong>"{{ $statement['search_query'] }}"</strong>
                    (Showing {{ count($statement['rows']) }} of {{ $statement['unfiltered_count'] }} transactions)
                </span>
                <a href="{{ route('accounting.ledger-statement', ['ledger_id' => $selectedLedgerId, 'from_date' => $fromDate, 'to_date' => $toDate, 'bill_filter' => $billFilter]) }}" class="btn btn-outline-primary btn-sm py-0 px-2 d-print-none">
                    Clear Search
                </a>
            </div>
        @endif

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table app-index-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3 text-center" style="width: 100px;">Date</th>
                            <th style="width: 100px;">Vch Type</th>
                            <th style="width: 140px;">Vch No</th>
                            <th>Particulars / Opposing Ledger</th>
                            <th class="text-end" style="width: 140px;">Debit (₹)</th>
                            <th class="text-end" style="width: 140px;">Credit (₹)</th>
                            <th class="text-end pe-3" style="width: 170px;">Running Balance (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Opening Balance Row -->
                        <tr style="background-color: var(--brand-table-header-bg);">
                            <td class="ps-3 text-center small text-muted">{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                            <td colspan="3"><span class="fw-bold text-dark">*** Opening Balance Brought Forward ***</span></td>
                            <td class="text-end fw-semibold" style="color: var(--brand-success);">
                                {{ $statement['opening_balance']['type'] === 'Dr' ? number_format($statement['opening_balance']['amount'], 2) : '-' }}
                            </td>
                            <td class="text-end fw-semibold" style="color: var(--brand-danger);">
                                {{ $statement['opening_balance']['type'] === 'Cr' ? number_format($statement['opening_balance']['amount'], 2) : '-' }}
                            </td>
                            <td class="text-end pe-3 fw-bold" style="color: var(--brand-primary);">
                                {{ $statement['opening_balance']['formatted'] }}
                            </td>
                        </tr>

                        <!-- Period Transactions -->
                        @forelse($statement['rows'] as $row)
                        <tr>
                            <td class="ps-3 text-center small">{{ $row['date'] }}</td>
                            <td>
                                @php
                                    $badgeStyle = match(strtolower($row['voucher_type'])) {
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
                                <span class="badge border" style="{{ $badgeStyle }} font-weight: 600;">{{ ucwords(str_replace('_', ' ', $row['voucher_type'])) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('accounting.vouchers.show', $row['voucher_id']) }}" class="text-decoration-none fw-semibold">
                                    {{ $row['voucher_number'] }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $row['particulars'] }}</div>
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    @if(!empty($row['truck_number']))
                                        <span class="badge border small" style="background-color: var(--brand-soft-info); color: var(--brand-info);"><i class="bi bi-truck me-1"></i>{{ $row['truck_number'] }}</span>
                                    @endif
                                    @if(!empty($row['lr_number']))
                                        <span class="badge border small" style="background-color: var(--brand-soft-primary); color: var(--brand-primary);"><i class="bi bi-file-earmark-text me-1"></i>LR: {{ $row['lr_number'] }}</span>
                                    @endif
                                    @if(!empty($row['reference_number']))
                                        <span class="badge bg-light text-secondary border small">Ref: {{ $row['reference_number'] }}</span>
                                    @endif
                                </div>
                                @if($row['narration'])
                                    <small class="text-muted d-block text-truncate mt-1" style="max-width: 350px;">{{ $row['narration'] }}</small>
                                @endif
                                @if($row['cost_centre'])
                                    <span class="badge border small mt-1" style="background-color: var(--brand-soft-info); color: var(--brand-info);"><i class="bi bi-truck me-1"></i>{{ $row['cost_centre'] }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold" style="color: var(--brand-success);">
                                {{ $row['debit'] ? '₹'.number_format($row['debit'], 2) : '-' }}
                            </td>
                            <td class="text-end fw-semibold" style="color: var(--brand-danger);">
                                {{ $row['credit'] ? '₹'.number_format($row['credit'], 2) : '-' }}
                            </td>
                            <td class="text-end pe-3 fw-bold" style="color: var(--brand-primary);">
                                ₹{{ number_format($row['running_balance'], 2) }} {{ $row['running_balance_type'] }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No transactions recorded for this ledger during the selected period.
                            </td>
                        </tr>
                        @endforelse

                        <!-- Totals & Closing Balance Row -->
                        <tr class="fw-bold" style="background-color: var(--brand-table-header-bg); border-top: 2px solid var(--brand-border);">
                            <td colspan="4" class="text-end pe-3 text-uppercase small">Total Period Activity:</td>
                            <td class="text-end" style="color: var(--brand-success);">₹{{ number_format($statement['total_debit'], 2) }}</td>
                            <td class="text-end" style="color: var(--brand-danger);">₹{{ number_format($statement['total_credit'], 2) }}</td>
                            <td class="text-end pe-3 fw-bold {{ !empty($statement['closing_balance']['is_negative_cash']) ? 'text-danger' : '' }}" style="{{ empty($statement['closing_balance']['is_negative_cash']) ? 'color: var(--brand-primary-dark);' : '' }}">
                                {{ $statement['closing_balance']['formatted'] }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $cityName = $company->city?->name ?? ($company->address ?? '');

        $formatAmount = function ($val, $showDecimals = true) {
            if ($val === null || $val === '' || (float)$val == 0.0) {
                return '-';
            }
            $isNegative = (float)$val < -0.001;
            $val = abs((float) $val);
            $parts = explode('.', number_format($val, 2, '.', ''));
            $intPart = $parts[0];
            $decPart = $showDecimals ? '.' . $parts[1] : '';

            if (strlen($intPart) > 3) {
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

        $formatAmountZero = function ($val, $showDecimals = true) {
            if ($val === null || $val === '') {
                return '0.00';
            }
            $isNegative = (float)$val < -0.001;
            $val = abs((float) $val);
            $parts = explode('.', number_format($val, 2, '.', ''));
            $intPart = $parts[0];
            $decPart = $showDecimals ? '.' . $parts[1] : '';

            if (strlen($intPart) > 3) {
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

        $opDebit = $statement['opening_balance']['type'] === 'Dr' ? (float)$statement['opening_balance']['amount'] : 0.0;
        $opCredit = $statement['opening_balance']['type'] === 'Cr' ? (float)$statement['opening_balance']['amount'] : 0.0;
        $totalDebitsWithOp = $opDebit + (float)$statement['total_debit'];
        $totalCreditsWithOp = $opCredit + (float)$statement['total_credit'];
        $tallyGrandTotal = max($totalDebitsWithOp, $totalCreditsWithOp);
    @endphp

    <!-- Dedicated Tally Print Block -->
    <div class="d-none d-print-block ledger-statement-print">
        <div class="tally-print-header">
            <div class="tally-company-name">{{ $company->name }}</div>
            @if($cityName)
                <div class="tally-company-city"><u>{{ $cityName }}</u></div>
            @elseif($company->address)
                <div class="tally-company-city">{{ $company->address }}</div>
            @endif
            @if($company->gst_number || $company->pan_number || $company->contact_number)
                <div class="tally-company-tax-info">
                    @if($company->gst_number) <span><strong>GSTIN:</strong> {{ $company->gst_number }}</span> @endif
                    @if($company->pan_number) <span style="margin-left: 14px;"><strong>PAN:</strong> {{ $company->pan_number }}</span> @endif
                    @if($company->contact_number) <span style="margin-left: 14px;"><strong>Phone:</strong> {{ $company->contact_number }}</span> @endif
                </div>
            @endif
            <div class="tally-report-title">{{ $statement['ledger']->name }}</div>
            <div class="tally-report-subtitle">
                Ledger Account &bull; {{ $statement['ledger']->getGroupHeadDisplay() }}
                @if($statement['ledger']->gstin) &bull; GSTIN: {{ $statement['ledger']->gstin }} @endif
                @if($statement['ledger']->pan_number) &bull; PAN: {{ $statement['ledger']->pan_number }} @endif
            </div>
            <div class="tally-report-period">
                Statement of Account: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}
            </div>
            @if(!empty($statement['is_filtered']))
                <div style="font-size: 8pt; font-style: italic; margin-top: 3px; color: #444444;">
                    Filtered by keyword: "{{ $statement['search_query'] }}" (Showing {{ count($statement['rows']) }} of {{ $statement['unfiltered_count'] }} transactions)
                </div>
            @endif
        </div>

        <!-- Ledger Transactions Table -->
        <table class="tally-statement-table">
            <thead>
                <tr class="tally-th-row">
                    <th style="width: 11%; text-align: center;">
                        <span class="tally-title-spaced">Date</span>
                    </th>
                    <th style="width: 35%;">
                        <span class="tally-title-spaced">Particulars</span>
                    </th>
                    <th style="width: 10%; text-align: center;">
                        <span class="tally-title-spaced">Vch Type</span>
                    </th>
                    <th style="width: 12%; text-align: center;">
                        <span class="tally-title-spaced">Vch No.</span>
                    </th>
                    <th class="text-end" style="width: 11%; text-align: right;">
                        <span class="tally-title-spaced">Debit (₹)</span>
                    </th>
                    <th class="text-end" style="width: 11%; text-align: right;">
                        <span class="tally-title-spaced">Credit (₹)</span>
                    </th>
                    <th class="text-end" style="width: 10%; text-align: right;">
                        <span class="tally-title-spaced">Balance (₹)</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr class="tally-row-tr tally-op-row">
                    <td class="tally-cell text-center" style="font-weight: 600;">
                        {{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}
                    </td>
                    <td class="tally-cell" colspan="3">
                        <span style="font-weight: 700;">Opening Balance</span>
                        @if(!empty($statement['opening_balance']['reference']))
                            <span style="font-size: 7.5pt; color: #555555; font-weight: normal; margin-left: 6px;">
                                (Ref: {{ $statement['opening_balance']['reference'] }})
                            </span>
                        @endif
                    </td>
                    <td class="tally-cell text-end" style="font-weight: 600;">
                        {{ $statement['opening_balance']['type'] === 'Dr' ? $formatAmount($statement['opening_balance']['amount']) : '-' }}
                    </td>
                    <td class="tally-cell text-end" style="font-weight: 600;">
                        {{ $statement['opening_balance']['type'] === 'Cr' ? $formatAmount($statement['opening_balance']['amount']) : '-' }}
                    </td>
                    <td class="tally-cell text-end" style="font-weight: 700;">
                        {{ $statement['opening_balance']['formatted'] }}
                    </td>
                </tr>

                <!-- Period Transactions -->
                @forelse($statement['rows'] as $row)
                    <tr class="tally-row-tr">
                        <td class="tally-cell text-center">
                            {{ $row['date'] }}
                        </td>
                        <td class="tally-cell">
                            <div style="font-weight: 600; color: #000000;">{{ $row['particulars'] }}</div>
                            @php
                                $metaParts = [];
                                if (!empty($row['truck_number'])) $metaParts[] = 'Truck: ' . $row['truck_number'];
                                if (!empty($row['lr_number'])) $metaParts[] = 'LR: ' . $row['lr_number'];
                                if (!empty($row['reference_number'])) $metaParts[] = 'Ref: ' . $row['reference_number'];
                                if (!empty($row['cost_centre']) && $row['cost_centre'] !== $row['truck_number']) $metaParts[] = 'CC: ' . $row['cost_centre'];
                            @endphp
                            @if(count($metaParts) > 0)
                                <div style="font-size: 7.5pt; color: #333333; margin-top: 1px;">
                                    {{ implode(' | ', $metaParts) }}
                                </div>
                            @endif
                            @if(!empty($row['narration']))
                                <div style="font-size: 7.5pt; color: #555555; font-style: italic; margin-top: 1px;">
                                    {{ $row['narration'] }}
                                </div>
                            @endif
                        </td>
                        <td class="tally-cell text-center" style="font-size: 8pt;">
                            {{ ucwords(str_replace('_', ' ', $row['voucher_type'])) }}
                        </td>
                        <td class="tally-cell text-center" style="font-size: 8pt; font-weight: 500;">
                            {{ $row['voucher_number'] }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $row['debit'] ? $formatAmount($row['debit']) : '-' }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $row['credit'] ? $formatAmount($row['credit']) : '-' }}
                        </td>
                        <td class="tally-cell text-end" style="font-weight: 600;">
                            {{ $formatAmount($row['running_balance']) }} {{ $row['running_balance_type'] }}
                        </td>
                    </tr>
                @empty
                    <tr class="tally-row-tr">
                        <td colspan="7" class="tally-cell text-center py-4 text-muted">
                            No transactions recorded for this ledger during the selected period.
                        </td>
                    </tr>
                @endforelse

                <!-- Current Period Total -->
                <tr class="tally-row-tr tally-current-total-row">
                    <td class="tally-cell"></td>
                    <td class="tally-cell fw-bold" colspan="3">
                        Current Period Total:
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($statement['total_debit']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($statement['total_credit']) }}
                    </td>
                    <td class="tally-cell text-end"></td>
                </tr>

                <!-- Closing Balance Row -->
                <tr class="tally-row-tr tally-closing-row">
                    <td class="tally-cell text-center small">
                        {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}
                    </td>
                    <td class="tally-cell fw-bold" colspan="3">
                        Closing Balance {{ !empty($statement['closing_balance']['is_negative_cash']) ? '(Overdrawn)' : '' }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $statement['closing_balance']['type'] === 'Cr' ? $formatAmount($statement['closing_balance']['amount']) : '-' }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $statement['closing_balance']['type'] === 'Dr' ? $formatAmount($statement['closing_balance']['amount']) : '-' }}
                    </td>
                    <td class="tally-cell text-end fw-bold {{ !empty($statement['closing_balance']['is_negative_cash']) ? 'text-danger' : '' }}">
                        {{ $statement['closing_balance']['formatted'] }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="tally-total-row">
                    <td class="tally-cell"></td>
                    <td class="tally-cell fw-bold" colspan="3">
                        <span class="tally-title-spaced">T o t a l</span>
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmountZero($tallyGrandTotal) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmountZero($tallyGrandTotal) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $statement['closing_balance']['formatted'] }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Bill-to-Bill Outstandings Schedule in Print (if available) -->
        @if($outstandings && count($outstandings['bills']) > 0)
            <div style="margin-top: 25px; page-break-inside: auto; break-inside: auto;">
                <div style="font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #000000; padding-bottom: 3px; margin-bottom: 8px;">
                    Bill-to-Bill Outstandings &amp; Matched Payments ({{ ucfirst($outstandings['entity_type'] ?? '') }})
                </div>
                <table class="tally-statement-table" style="font-size: 8pt !important;">
                    <thead>
                        <tr class="tally-th-row">
                            <th style="width: 14%;"><span class="tally-title-spaced">Bill / Ref #</span></th>
                            <th style="width: 11%; text-align: center;"><span class="tally-title-spaced">Date</span></th>
                            @if(strcasecmp($outstandings['entity_type'] ?? '', 'Customer') !== 0)
                                <th style="width: 18%;"><span class="tally-title-spaced">Truck / LR No</span></th>
                            @endif
                            <th class="text-end" style="width: 14%; text-align: right;"><span class="tally-title-spaced">Billed (₹)</span></th>
                            <th class="text-end" style="width: 14%; text-align: right;"><span class="tally-title-spaced">Cleared (₹)</span></th>
                            <th class="text-end" style="width: 14%; text-align: right;"><span class="tally-title-spaced">Pending (₹)</span></th>
                            <th class="text-center" style="width: 15%; text-align: center;"><span class="tally-title-spaced">Status</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outstandings['bills'] as $b)
                            <tr class="tally-row-tr">
                                <td class="tally-cell fw-bold">{{ $b['bill_no'] }}</td>
                                <td class="tally-cell text-center">{{ $b['bill_date'] }}</td>
                                @if(strcasecmp($outstandings['entity_type'] ?? '', 'Customer') !== 0)
                                    <td class="tally-cell">{{ $b['truck_no'] ?? '-' }} {{ !empty($b['lr_no']) ? '(LR: ' . $b['lr_no'] . ')' : '' }}</td>
                                @endif
                                <td class="tally-cell text-end">{{ $formatAmount($b['total_amount']) }}</td>
                                <td class="tally-cell text-end">{{ $formatAmount($b['cleared_amount']) }}</td>
                                <td class="tally-cell text-end fw-bold">{{ $formatAmount($b['pending_amount']) }}</td>
                                <td class="tally-cell text-center" style="font-weight: 600;">
                                    {{ ucfirst($b['status']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="tally-total-row">
                            <td class="tally-cell fw-bold" colspan="{{ strcasecmp($outstandings['entity_type'] ?? '', 'Customer') !== 0 ? 3 : 2 }}">
                                <span class="tally-title-spaced">T o t a l</span>
                            </td>
                            <td class="tally-cell text-end fw-bold">{{ $formatAmountZero($outstandings['total_billed']) }}</td>
                            <td class="tally-cell text-end fw-bold">{{ $formatAmountZero($outstandings['total_cleared']) }}</td>
                            <td class="tally-cell text-end fw-bold">{{ $formatAmountZero($outstandings['total_pending']) }}</td>
                            <td class="tally-cell"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        <!-- Signatures Row -->
        <div class="tally-signatures-row">
            <div class="tally-sign-box">
                <div class="tally-sign-line"></div>
                <div class="tally-sign-title">Prepared by</div>
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
            <span>This is a computer generated account statement.</span>
            <span>Printed on: {{ now()->format('d-M-Y h:i A') }}</span>
        </div>
    </div>
    @else
    <div class="card shadow-sm border-0 py-5 text-center text-muted d-print-none" style="border-radius: 12px; border: 1px solid var(--brand-border) !important;">
        <i class="bi bi-search fs-1 mb-2 text-muted"></i>
        <h5 class="fw-semibold text-dark">Select a ledger account above to view its statement.</h5>
        <small class="text-muted">You can inspect Customer ledgers, Transporter accounts, Fuel Pumps, Bank registers, or Expenses.</small>
    </div>
    @endif
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
    </script>
@endpush
