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
                margin: 8mm 8mm 8mm 8mm;
            }

            /* Completely hide all overlays, backdrops, sidebars, headers, footers, and interactive buttons */
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

            /* Force pure crisp white background on all page wrappers and remove all overlays/filters */
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
            .app-index-page--standard,
            .card,
            .app-index-list-card,
            .card-body {
                background: #ffffff !important;
                background-color: #ffffff !important;
                background-image: none !important;
                color: #0f172a !important;
                font-size: 9pt !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }

            .container-fluid.app-index-page--standard {
                padding: 0 !important;
            }

            /* Clean white cards with subtle border */
            .card,
            .app-index-list-card {
                border: 1px solid #cbd5e1 !important;
                border-radius: 4px !important;
                box-shadow: none !important;
                margin-bottom: 10px !important;
                overflow: visible !important;
                page-break-inside: auto !important;
                background: #ffffff !important;
            }

            /* Clean card header with no dark gradient */
            .card-header,
            .app-index-list-card > .card-header {
                background: #f8fafc !important;
                background-color: #f8fafc !important;
                background-image: none !important;
                border-bottom: 1px solid #cbd5e1 !important;
                color: #0f172a !important;
                padding: 6px 10px !important;
            }

            .card-header .card-title,
            .card-header h3.card-title,
            .card-header h6 {
                color: #0f172a !important;
                font-size: 10pt !important;
                font-weight: 700 !important;
            }

            .card-body {
                padding: 8px !important;
                overflow: visible !important;
                background: #ffffff !important;
            }

            /* Strip out colored background fills from summary stat boxes for clean white printing */
            .border.rounded,
            .bg-light,
            div[style*="background"] {
                background: #ffffff !important;
                background-color: #ffffff !important;
                background-image: none !important;
                border-color: #cbd5e1 !important;
                box-shadow: none !important;
            }

            .table-responsive {
                overflow: visible !important;
                display: block !important;
            }

            table.app-index-table,
            table.table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 8.5pt !important;
                background: #ffffff !important;
            }

            table.app-index-table th,
            table.app-index-table td,
            table.table th,
            table.table td {
                padding: 4px 6px !important;
                border: 1px solid #cbd5e1 !important;
                vertical-align: middle !important;
                background: #ffffff !important;
            }

            table.app-index-table thead th,
            table.table thead th {
                background: #f1f5f9 !important;
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                font-weight: 700 !important;
                border-bottom: 2px solid #64748b !important;
            }

            table.app-index-table thead,
            table.table thead {
                display: table-header-group !important;
            }

            table tr {
                page-break-inside: avoid !important;
                background: #ffffff !important;
            }

            tr[style*="background-color"] {
                background: #ffffff !important;
                background-color: #ffffff !important;
            }

            .badge {
                border: 1px solid #94a3b8 !important;
                background: #ffffff !important;
                background-color: #ffffff !important;
                color: #334155 !important;
                font-weight: 600 !important;
                font-size: 7.5pt !important;
                padding: 2px 4px !important;
            }

            a {
                text-decoration: none !important;
                color: #0f172a !important;
            }

            .app-index-card-toolbar,
            .btn,
            .btn-group,
            .d-print-none {
                display: none !important;
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
                                {{ $l->name }} ({{ $l->accountGroup?->name ?? 'Primary' }})
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
    <!-- Dedicated Print Header (Visible ONLY on print) -->
    <div class="d-none d-print-block mb-3 pb-2 border-bottom">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 class="fw-bold mb-1 text-dark" style="font-size: 1.4rem;">{{ $company->name }}</h3>
                @if($company->address)
                    <div class="text-secondary small mb-1">{{ $company->address }}</div>
                @endif
                <div class="text-secondary small">
                    @if($company->gst_number) <span><strong>GSTIN:</strong> {{ $company->gst_number }}</span> @endif
                    @if($company->pan_number) <span class="ms-3"><strong>PAN:</strong> {{ $company->pan_number }}</span> @endif
                    @if($company->contact_number) <span class="ms-3"><strong>Phone:</strong> {{ $company->contact_number }}</span> @endif
                </div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-uppercase text-dark mb-1" style="font-size: 1.15rem; letter-spacing: 0.5px;">Statement of Account</h4>
                <div class="text-secondary small">
                    <strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}
                </div>
                <div class="text-muted small">
                    Printed on: {{ now()->format('d-m-Y h:i A') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Header Cards -->
    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; border: 1px solid var(--brand-border) !important; background: #fff;">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <span class="badge mb-1" style="background-color: var(--brand-soft-primary); color: var(--brand-primary); font-weight: 600;">
                        {{ $statement['ledger']->accountGroup?->name ?? 'General Ledger' }}
                    </span>
                    <h4 class="fw-bold mb-1" style="color: var(--brand-text-primary);">{{ $statement['ledger']->name }}</h4>
                    <div class="small text-muted">
                        @if($statement['ledger']->gstin) GSTIN: <code>{{ $statement['ledger']->gstin }}</code> @endif
                        @if($statement['ledger']->pan_number) | PAN: <code>{{ $statement['ledger']->pan_number }}</code> @endif
                    </div>
                </div>
                <div class="col-md-8">
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
    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; border: 1px solid var(--brand-border) !important;">
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
                                    <a href="{{ route('freight-bookings.show', $bill['id']) }}" class="text-decoration-none">
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
    <div class="card app-index-list-card shadow-sm">
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
    @else
    <div class="card shadow-sm border-0 py-5 text-center text-muted" style="border-radius: 12px; border: 1px solid var(--brand-border) !important;">
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
