@extends('layouts.app')

@section('title', 'Trade Receivables (Debtors) Aging - ' . $company->name)

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
        .aging-print {
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

        .tally-aging-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            background: #ffffff !important;
            page-break-inside: auto !important;
            break-inside: auto !important;
        }

        .tally-aging-table thead {
            display: table-header-group !important;
        }

        .tally-aging-table tfoot {
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

        .tally-aging-table th,
        .tally-aging-table td {
            border-right: 1px solid #dddddd !important;
        }

        .tally-aging-table th:last-child,
        .tally-aging-table td:last-child {
            border-right: none !important;
        }

        .tally-row-tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-debtor-row td {
            background-color: #fafafa !important;
            border-top: 1px solid #e2e8f0 !important;
            font-weight: 700 !important;
            padding: 4px 6px !important;
            color: #000000 !important;
        }

        .tally-inv-row td {
            background: #ffffff !important;
            padding: 2px 6px !important;
            color: #222222 !important;
            font-size: 8pt !important;
        }

        .tally-spacer-tr td {
            height: 4px !important;
            padding: 0 !important;
            border: none !important;
            background: #ffffff !important;
        }

        .tally-cell {
            padding: 3px 6px !important;
            vertical-align: top !important;
            background: #ffffff !important;
            color: #000000 !important;
        }

        .tally-indent {
            padding-left: 14px !important;
        }

        .tally-total-row td {
            border-top: 1px solid #000000 !important;
            border-bottom: 3px double #000000 !important;
            padding: 5px 6px !important;
            font-weight: 700 !important;
            color: #000000 !important;
            font-size: 8.5pt !important;
            background: #ffffff !important;
        }

        .tally-title-spaced {
            font-weight: 700 !important;
            font-size: 8.5pt !important;
            letter-spacing: 1px !important;
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
            <form method="GET" action="{{ route('accounting.debtors-aging') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Aging As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm w-100">
                        <i class="bi bi-filter"></i> Recalculate Aging
                    </button>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-md-end gap-2 pt-2 pt-md-0">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i> Print Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MSME Alert Banner if any invoices > 45 days -->
    @if($report['totals']['msme_alert_customers_count'] > 0)
    <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-3 p-3 d-print-none" style="border-radius: 12px; background-color: #fef2f2; border: 1px solid #fecaca !important;">
        <i class="bi bi-exclamation-triangle-fill fs-4 text-danger me-3"></i>
        <div class="flex-grow-1">
            <strong class="text-danger">MSME Section 43B(h) Overdue Alert:</strong>
            <span class="text-dark">
                <strong>{{ $report['totals']['msme_alert_customers_count'] }} debtor(s)</strong> have outstanding invoices pending beyond <strong>45 days</strong>, totaling <strong>₹{{ number_format($report['totals']['total_msme_overdue'], 2) }}</strong>.
                <div class="mt-1 small text-muted">
                    <i class="bi bi-info-circle me-1"></i><strong>Statutory Note on Sec 43B(h):</strong> Delayed payments beyond 45 days attract mandatory income tax disallowance for <u>our Sundry Debtors / Customers</u> (buyers), not to our company. You can cite Section 43B(h) in follow-up reminders to accelerate payment collections. (Conversely, delayed payments by our company to registered <u>MSME Sundry Creditors / Transporters</u> will attract disallowance to <u>us</u>).
                </div>
            </span>
        </div>
    </div>
    @endif

    <!-- KPI Aging Buckets -->
    <div class="row g-3 mb-3 d-print-none">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid var(--brand-border) !important; background: #ffffff;">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Receivables</span>
                        <i class="bi bi-wallet2 text-muted fs-6"></i>
                    </div>
                    <div class="my-1">
                        <h4 class="fw-bold mb-0 text-dark" style="font-size: 1.25rem;">₹{{ number_format($report['totals']['total_receivables'], 2) }}</h4>
                    </div>
                    <div class="mt-2">
                        <span class="small text-muted" style="font-size: 0.78rem;"><i class="bi bi-people me-1"></i>{{ $report['totals']['total_debtors_count'] }} Customers</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid rgba(34,197,94,0.3) !important; background: #f0fdf4;">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-success text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">0 - 30 Days</span>
                        <i class="bi bi-check-circle-fill text-success fs-6"></i>
                    </div>
                    <div class="my-1">
                        <h4 class="fw-bold mb-0 text-success" style="font-size: 1.25rem;">₹{{ number_format($report['totals']['total_0_30'], 2) }}</h4>
                    </div>
                    <div class="mt-2">
                        <span class="small text-success fw-medium" style="font-size: 0.78rem;">Current / Normal</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid rgba(59,130,246,0.3) !important; background: #eff6ff;">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-primary text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">31 - 60 Days</span>
                        <i class="bi bi-clock-history text-primary fs-6"></i>
                    </div>
                    <div class="my-1">
                        <h4 class="fw-bold mb-0 text-primary" style="font-size: 1.25rem;">₹{{ number_format($report['totals']['total_31_60'], 2) }}</h4>
                    </div>
                    <div class="mt-2">
                        <span class="small text-primary fw-medium" style="font-size: 0.78rem;">Follow-up due</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid rgba(245,158,11,0.35) !important; background: #fffbeb;">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px; color: #b45309 !important;">61 - 90 Days</span>
                        <i class="bi bi-exclamation-circle fs-6" style="color: #b45309;"></i>
                    </div>
                    <div class="my-1">
                        <h4 class="fw-bold mb-0" style="font-size: 1.25rem; color: #b45309;">₹{{ number_format($report['totals']['total_61_90'], 2) }}</h4>
                    </div>
                    <div class="mt-2">
                        <span class="small fw-medium" style="font-size: 0.78rem; color: #b45309;">Overdue</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid rgba(239,68,68,0.3) !important; background: #fef2f2;">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-danger text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">&gt; 90 Days</span>
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-6"></i>
                    </div>
                    <div class="my-1">
                        <h4 class="fw-bold mb-0 text-danger" style="font-size: 1.25rem;">₹{{ number_format($report['totals']['total_over_90'], 2) }}</h4>
                    </div>
                    <div class="mt-2">
                        <span class="small text-danger fw-medium" style="font-size: 0.78rem;">Critical Risk</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; border: 1px solid rgba(220,38,38,0.4) !important; background: #fff1f2;">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-danger text-uppercase text-truncate me-1" style="font-size: 0.72rem; letter-spacing: 0.5px;"><i class="bi bi-shield-exclamation me-1"></i>MSME &gt; 45d Alert</span>
                        <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">43B(h)</span>
                    </div>
                    <div class="my-1">
                        <h4 class="fw-bold mb-0 text-danger" style="font-size: 1.25rem;">₹{{ number_format($report['totals']['total_msme_overdue'], 2) }}</h4>
                    </div>
                    <div class="mt-2">
                        <span class="small text-danger fw-medium" style="font-size: 0.78rem;">Sec 43B(h) overdue</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aging Schedule Table Card -->
    <div class="card app-index-list-card shadow-sm d-print-none">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="bi bi-hourglass-split me-2 text-primary"></i>Sundry Debtors Aging Schedule (As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }})
            </h3>
            <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill">
                {{ $report['customers']->count() }} Active Debtors
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table app-index-table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Customer / Sundry Debtor</th>
                            <th style="width: 150px;">GSTIN / Mobile</th>
                            <th class="text-end" style="width: 140px;">Total Due (₹)</th>
                            <th class="text-end" style="width: 120px;">0 - 30 Days</th>
                            <th class="text-end" style="width: 120px;">31 - 60 Days</th>
                            <th class="text-end" style="width: 120px;">61 - 90 Days</th>
                            <th class="text-end" style="width: 120px;">> 90 Days</th>
                            <th class="text-center" style="width: 120px;">MSME Status</th>
                            <th class="text-center pe-3" style="width: 90px;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['customers'] as $idx => $cust)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark">{{ $cust['customer_name'] }}</div>
                                <div class="small text-muted">{{ count($cust['invoices']) }} pending invoice(s)</div>
                            </td>
                            <td>
                                <div><code>{{ $cust['gst_number'] }}</code></div>
                                <div class="small text-muted">{{ $cust['contact_number'] }}</div>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                ₹{{ number_format($cust['total_pending'], 2) }}
                            </td>
                            <td class="text-end {{ $cust['bucket_0_30'] > 0 ? 'fw-semibold text-success' : 'text-muted' }}">
                                {{ $cust['bucket_0_30'] > 0 ? '₹'.number_format($cust['bucket_0_30'], 2) : '-' }}
                            </td>
                            <td class="text-end {{ $cust['bucket_31_60'] > 0 ? 'fw-semibold text-primary' : 'text-muted' }}">
                                {{ $cust['bucket_31_60'] > 0 ? '₹'.number_format($cust['bucket_31_60'], 2) : '-' }}
                            </td>
                            <td class="text-end {{ $cust['bucket_61_90'] > 0 ? 'fw-semibold text-warning' : 'text-muted' }}">
                                {{ $cust['bucket_61_90'] > 0 ? '₹'.number_format($cust['bucket_61_90'], 2) : '-' }}
                            </td>
                            <td class="text-end {{ $cust['bucket_over_90'] > 0 ? 'fw-bold text-danger' : 'text-muted' }}">
                                {{ $cust['bucket_over_90'] > 0 ? '₹'.number_format($cust['bucket_over_90'], 2) : '-' }}
                            </td>
                            <td class="text-center">
                                @if($cust['has_msme_alert'])
                                    <span class="badge bg-danger px-2 py-1" title="Outstanding > 45 days: ₹{{ number_format($cust['msme_overdue_amount'], 2) }}">
                                        <i class="bi bi-exclamation-octagon me-1"></i>> 45d Overdue
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle me-1"></i>Compliant
                                    </span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <button class="btn btn-xs btn-outline-primary py-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#cust_inv_{{ $idx }}" aria-expanded="false">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Collapsible Invoices Sub-row -->
                        <tr class="collapse bg-light" id="cust_inv_{{ $idx }}">
                            <td colspan="9" class="p-3">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-header py-1.5 px-3 bg-white d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-muted">Unsettled Invoices for {{ $cust['customer_name'] }}</span>
                                        <a href="{{ route('accounting.ledger-statement', ['ledger_id' => '', 'search' => $cust['customer_name']]) }}" class="small text-decoration-none">
                                            Open Ledger Book <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-bordered mb-0 align-middle" style="font-size: 0.85rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Invoice #</th>
                                                    <th>Invoice Date</th>
                                                    <th class="text-center">Age (Days)</th>
                                                    <th class="text-end">Invoice Amount</th>
                                                    <th class="text-end">Paid / Settled</th>
                                                    <th class="text-end">Pending Balance</th>
                                                    <th class="text-center">MSME Flag</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($cust['invoices'] as $inv)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('invoices.show', $inv['id']) }}" class="fw-semibold text-decoration-none">
                                                            {{ $inv['invoice_number'] }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $inv['date'] }}</td>
                                                    <td class="text-center">
                                                        <span class="badge {{ $inv['age_days'] > 45 ? 'bg-danger' : ($inv['age_days'] > 30 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                            {{ $inv['age_days'] }} days
                                                        </span>
                                                    </td>
                                                    <td class="text-end">₹{{ number_format($inv['total_amount'], 2) }}</td>
                                                    <td class="text-end text-success">₹{{ number_format($inv['paid_amount'], 2) }}</td>
                                                    <td class="text-end fw-bold text-dark">₹{{ number_format($inv['pending_amount'], 2) }}</td>
                                                    <td class="text-center">
                                                        @if($inv['is_msme_overdue'])
                                                            <span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Over 45 Days</span>
                                                        @else
                                                            <span class="text-muted">Within 45 Days</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-check2-circle fs-3 text-success d-block mb-2"></i>
                                Excellent! No outstanding Sundry Debtors receivables as of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr style="background-color: var(--brand-table-header-bg);" class="fw-bold">
                            <td colspan="2" class="ps-3 text-uppercase">Total ({{ $report['totals']['total_debtors_count'] }} Debtors)</td>
                            <td class="text-end text-dark">₹{{ number_format($report['totals']['total_receivables'], 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($report['totals']['total_0_30'], 2) }}</td>
                            <td class="text-end text-primary">₹{{ number_format($report['totals']['total_31_60'], 2) }}</td>
                            <td class="text-end text-warning">₹{{ number_format($report['totals']['total_61_90'], 2) }}</td>
                            <td class="text-end text-danger">₹{{ number_format($report['totals']['total_over_90'], 2) }}</td>
                            <td colspan="2" class="text-center">
                                <span class="badge bg-danger">{{ $report['totals']['msme_alert_customers_count'] }} MSME Alert(s)</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- DEDICATED PRINT STATEMENT (Tally Sundry Debtors Aging Format - Visible ONLY on print) -->
    @php
        $asOfCarbon = \Carbon\Carbon::parse($asOfDate);
        $asAtString = 'as at ' . $asOfCarbon->format('j-M-y');
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
    @endphp

    <div class="d-none d-print-block aging-print">
        <div class="tally-print-header">
            <div class="tally-company-name">{{ $company->name }}</div>
            @if($cityName)
                <div class="tally-company-city"><u>{{ $cityName }}</u></div>
            @endif
            <div class="tally-report-title">Sundry Debtors Aging Schedule</div>
            <div class="tally-report-period">{{ $asAtString }}</div>
        </div>

        <table class="tally-aging-table">
            <thead>
                <tr class="tally-th-row">
                    <th style="width: 26%;">
                        <span class="tally-title-spaced">Particulars</span>
                    </th>
                    <th style="width: 14%;">
                        <span class="tally-title-spaced">GSTIN / Date</span>
                    </th>
                    <th class="text-end" style="width: 12%;">
                        <span class="tally-title-spaced">Total Due</span>
                    </th>
                    <th class="text-end" style="width: 10%;">
                        <span class="tally-title-spaced">0 - 30 Days</span>
                    </th>
                    <th class="text-end" style="width: 10%;">
                        <span class="tally-title-spaced">31 - 60 Days</span>
                    </th>
                    <th class="text-end" style="width: 10%;">
                        <span class="tally-title-spaced">61 - 90 Days</span>
                    </th>
                    <th class="text-end" style="width: 10%;">
                        <span class="tally-title-spaced">&gt; 90 Days</span>
                    </th>
                    <th class="text-center" style="width: 8%;">
                        <span class="tally-title-spaced">MSME</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['customers'] as $cust)
                    <!-- Customer Row -->
                    <tr class="tally-row-tr tally-debtor-row">
                        <td class="tally-cell fw-bold">
                            {{ $cust['customer_name'] }}
                        </td>
                        <td class="tally-cell">
                            {{ (!empty($cust['gst_number']) && $cust['gst_number'] !== '-') ? $cust['gst_number'] : ($cust['contact_number'] ?? '-') }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $formatAmount($cust['total_pending']) }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $cust['bucket_0_30'] > 0.001 ? $formatAmount($cust['bucket_0_30']) : '-' }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $cust['bucket_31_60'] > 0.001 ? $formatAmount($cust['bucket_31_60']) : '-' }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $cust['bucket_61_90'] > 0.001 ? $formatAmount($cust['bucket_61_90']) : '-' }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $cust['bucket_over_90'] > 0.001 ? $formatAmount($cust['bucket_over_90']) : '-' }}
                        </td>
                        <td class="tally-cell text-center">
                            @if($cust['has_msme_alert'])
                                <strong>&gt; 45d Overdue</strong>
                            @else
                                Compliant
                            @endif
                        </td>
                    </tr>

                    <!-- Invoices Sub-rows -->
                    @foreach($cust['invoices'] as $inv)
                        <tr class="tally-row-tr tally-inv-row">
                            <td class="tally-cell tally-indent">
                                <em>Inv #{{ $inv['invoice_number'] }}</em>
                            </td>
                            <td class="tally-cell fst-italic">
                                {{ $inv['date'] }} ({{ $inv['age_days'] }}d)
                            </td>
                            <td class="tally-cell text-end fst-italic">
                                {{ $formatAmount($inv['pending_amount']) }}
                            </td>
                            <td class="tally-cell text-end fst-italic">
                                {{ $inv['age_days'] <= 30 ? $formatAmount($inv['pending_amount']) : '-' }}
                            </td>
                            <td class="tally-cell text-end fst-italic">
                                {{ ($inv['age_days'] > 30 && $inv['age_days'] <= 60) ? $formatAmount($inv['pending_amount']) : '-' }}
                            </td>
                            <td class="tally-cell text-end fst-italic">
                                {{ ($inv['age_days'] > 60 && $inv['age_days'] <= 90) ? $formatAmount($inv['pending_amount']) : '-' }}
                            </td>
                            <td class="tally-cell text-end fst-italic">
                                {{ $inv['age_days'] > 90 ? $formatAmount($inv['pending_amount']) : '-' }}
                            </td>
                            <td class="tally-cell text-center fst-italic">
                                @if($inv['is_msme_overdue'])
                                    <strong>* &gt; 45d</strong>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="tally-spacer-tr"><td colspan="8"></td></tr>
                @empty
                    <tr class="tally-row-tr">
                        <td colspan="8" class="tally-cell text-center py-3">
                            No outstanding sundry debtors receivables as of {{ $asAtString }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="tally-total-row">
                    <td colspan="2" class="tally-cell fw-bold">
                        <span class="tally-title-spaced">T o t a l</span>
                        <span style="font-weight: 400; font-size: 8pt; margin-left: 6px;">({{ $report['totals']['total_debtors_count'] }} Debtors)</span>
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['totals']['total_receivables']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['totals']['total_0_30']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['totals']['total_31_60']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['totals']['total_61_90']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['totals']['total_over_90']) }}
                    </td>
                    <td class="tally-cell text-center fw-bold">
                        @if($report['totals']['msme_alert_customers_count'] > 0)
                            {{ $report['totals']['msme_alert_customers_count'] }} Alert(s)
                        @else
                            Compliant
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>

        @if($report['totals']['msme_alert_customers_count'] > 0)
            <div style="margin-top: 10px; font-size: 7.5pt; color: #333333; line-height: 1.3;">
                * <strong>MSME Section 43B(h) Notice:</strong> Total overdue receivables beyond 45 days: ₹{{ $formatAmount($report['totals']['total_msme_overdue']) }} across {{ $report['totals']['msme_alert_customers_count'] }} debtor(s). Note: Section 43B(h) statutory tax disallowance applies to the debtor/buyer for delayed payments to MSME suppliers, not to our company.
            </div>
        @endif
    </div>
</div>
@endsection
