@extends('layouts.app')

@section('title', 'GSTR-1 Return Preparation - ' . $company->name)

@push('styles')
<style>
    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 8mm 8mm;
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
        .alert,
        .nav-pills {
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
        .gstr1-print {
            display: block !important;
            width: 100% !important;
            color: #000000 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 8pt !important;
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
            font-size: 13.5pt !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            color: #000000 !important;
            margin-bottom: 2px !important;
        }

        .tally-company-city {
            font-size: 10.5pt !important;
            font-weight: 400 !important;
            color: #000000 !important;
            margin-bottom: 2px !important;
        }

        .tally-company-gstin {
            font-size: 9pt !important;
            font-weight: 600 !important;
            color: #000000 !important;
            margin-bottom: 3px !important;
        }

        .tally-report-title {
            font-size: 12pt !important;
            font-weight: 700 !important;
            color: #000000 !important;
            margin-bottom: 2px !important;
        }

        .tally-report-period {
            font-size: 9.5pt !important;
            font-weight: 400 !important;
            color: #000000 !important;
        }

        .tally-section-heading {
            font-size: 9.5pt !important;
            font-weight: 700 !important;
            color: #000000 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            margin-top: 10px !important;
            margin-bottom: 4px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-gstr1-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            background: #ffffff !important;
            page-break-inside: auto !important;
            break-inside: auto !important;
            margin-bottom: 12px !important;
        }

        .tally-gstr1-table thead {
            display: table-header-group !important;
        }

        .tally-gstr1-table tfoot {
            display: table-footer-group !important;
        }

        .tally-th-row th {
            padding: 4px 5px !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            background: #ffffff !important;
            color: #000000 !important;
            vertical-align: middle !important;
            font-size: 8pt !important;
            font-weight: 700 !important;
        }

        .tally-gstr1-table th,
        .tally-gstr1-table td {
            border-right: 1px solid #dddddd !important;
        }

        .tally-gstr1-table th:last-child,
        .tally-gstr1-table td:last-child {
            border-right: none !important;
        }

        .tally-row-tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .tally-cell {
            padding: 3px 5px !important;
            vertical-align: top !important;
            background: #ffffff !important;
            color: #000000 !important;
            font-size: 7.5pt !important;
        }

        .tally-total-row td {
            border-top: 1px solid #000000 !important;
            border-bottom: 3px double #000000 !important;
            padding: 4px 5px !important;
            font-weight: 700 !important;
            color: #000000 !important;
            font-size: 8pt !important;
            background: #ffffff !important;
        }

        .tally-title-spaced {
            font-weight: 700 !important;
            font-size: 7.8pt !important;
            letter-spacing: 0.5px !important;
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
            <form method="GET" action="{{ route('accounting.gstr1') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm w-100">
                        <i class="bi bi-filter"></i> Apply Period
                    </button>
                </div>
                <div class="col-md-4 d-flex align-items-center justify-content-md-end gap-2 pt-2 pt-md-0">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i> Print / PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-3 d-print-none">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ffffff; border: 1px solid var(--brand-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-muted text-uppercase">Total Invoices</span>
                    <span class="badge rounded-pill bg-primary px-2 py-1">{{ $report['overall_totals']['count'] }}</span>
                </div>
                <h4 class="fw-bold mb-0 text-dark">₹{{ number_format($report['overall_totals']['invoice_value'], 2) }}</h4>
                <div class="small text-muted mt-1">Taxable: ₹{{ number_format($report['overall_totals']['taxable_value'], 2) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ffffff; border: 1px solid var(--brand-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-success text-uppercase">Table 4A (FCM)</span>
                    <span class="badge rounded-pill bg-success px-2 py-1">{{ $report['table_4a_totals']['count'] }} Invoices</span>
                </div>
                <h4 class="fw-bold mb-0 text-success">₹{{ number_format($report['table_4a_totals']['invoice_value'], 2) }}</h4>
                <div class="small text-muted mt-1">Tax: ₹{{ number_format($report['table_4a_totals']['total_tax'], 2) }} (Forward Charge)</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ffffff; border: 1px solid var(--brand-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-warning text-uppercase">Table 4B (RCM)</span>
                    <span class="badge rounded-pill bg-warning text-dark px-2 py-1">{{ $report['table_4b_totals']['count'] }} Invoices</span>
                </div>
                <h4 class="fw-bold mb-0 text-warning">₹{{ number_format($report['table_4b_totals']['invoice_value'], 2) }}</h4>
                <div class="small text-muted mt-1">Reverse Charge: YES (Tax paid by recipient)</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ffffff; border: 1px solid var(--brand-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-info text-uppercase">Total Output GST</span>
                    <i class="bi bi-receipt text-info fs-5"></i>
                </div>
                <h4 class="fw-bold mb-0 text-info">₹{{ number_format($report['table_4a_totals']['total_tax'], 2) }}</h4>
                <div class="small text-muted mt-1">CGST: ₹{{ number_format($report['table_4a_totals']['cgst'], 2) }} | SGST: ₹{{ number_format($report['table_4a_totals']['sgst'], 2) }} | IGST: ₹{{ number_format($report['table_4a_totals']['igst'], 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-3 gap-2 d-print-none" id="gstr1Tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-3 py-1.5 fw-semibold" id="tab-4a-btn" data-bs-toggle="pill" data-bs-target="#tab-4a" type="button" role="tab">
                <i class="bi bi-arrow-up-right-circle me-1 text-success"></i> Table 4A: Forward Charge ({{ $report['table_4a']->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-3 py-1.5 fw-semibold" id="tab-4b-btn" data-bs-toggle="pill" data-bs-target="#tab-4b" type="button" role="tab">
                <i class="bi bi-arrow-repeat me-1 text-warning"></i> Table 4B: Reverse Charge (RCM) ({{ $report['table_4b']->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content d-print-none" id="gstr1TabContent">
        <!-- Table 4A Tab: Forward Charge -->
        <div class="tab-pane fade show active" id="tab-4a" role="tabpanel">
            <div class="card app-index-list-card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="bi bi-table me-2 text-success"></i>Table 4A - Taxable Outward Supplies (Forward Charge / FCM)
                        </h3>
                        <p class="small text-muted mb-0 mt-1">Supplies where Goods Transport Agency (GTA) charges and pays GST to Government</p>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill">
                        Total Value: ₹{{ number_format($report['table_4a_totals']['invoice_value'], 2) }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table app-index-table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="width: 140px;">Invoice #</th>
                                    <th style="width: 110px;">Date</th>
                                    <th>Recipient / Customer</th>
                                    <th style="width: 150px;">GSTIN</th>
                                    <th style="width: 140px;">Place of Supply</th>
                                    <th class="text-end" style="width: 130px;">Taxable (₹)</th>
                                    <th class="text-end" style="width: 100px;">CGST (₹)</th>
                                    <th class="text-end" style="width: 100px;">SGST (₹)</th>
                                    <th class="text-end" style="width: 100px;">IGST (₹)</th>
                                    <th class="text-end" style="width: 110px;">Tax Total (₹)</th>
                                    <th class="text-end pe-3" style="width: 140px;">Invoice Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['table_4a'] as $row)
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <a href="{{ route('invoices.show', $row['id']) }}" class="text-decoration-none">
                                            {{ $row['invoice_number'] }}
                                        </a>
                                    </td>
                                    <td>{{ $row['invoice_date'] }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $row['customer_name'] }}</div>
                                    </td>
                                    <td><code>{{ $row['customer_gstin'] }}</code></td>
                                    <td>{{ $row['place_of_supply'] }}</td>
                                    <td class="text-end fw-semibold">₹{{ number_format($row['taxable_value'], 2) }}</td>
                                    <td class="text-end text-muted">{{ $row['cgst'] > 0 ? '₹'.number_format($row['cgst'], 2) : '-' }}</td>
                                    <td class="text-end text-muted">{{ $row['sgst'] > 0 ? '₹'.number_format($row['sgst'], 2) : '-' }}</td>
                                    <td class="text-end text-muted">{{ $row['igst'] > 0 ? '₹'.number_format($row['igst'], 2) : '-' }}</td>
                                    <td class="text-end fw-bold text-success">{{ $row['total_tax'] > 0 ? '₹'.number_format($row['total_tax'], 2) : '-' }}</td>
                                    <td class="text-end pe-3 fw-bold text-dark">₹{{ number_format($row['invoice_value'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No Forward Charge invoices found for selected date range.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background-color: var(--brand-table-header-bg);" class="fw-bold">
                                    <td colspan="5" class="ps-3 text-uppercase">Table 4A Total ({{ $report['table_4a_totals']['count'] }} records)</td>
                                    <td class="text-end">₹{{ number_format($report['table_4a_totals']['taxable_value'], 2) }}</td>
                                    <td class="text-end">₹{{ number_format($report['table_4a_totals']['cgst'], 2) }}</td>
                                    <td class="text-end">₹{{ number_format($report['table_4a_totals']['sgst'], 2) }}</td>
                                    <td class="text-end">₹{{ number_format($report['table_4a_totals']['igst'], 2) }}</td>
                                    <td class="text-end text-success">₹{{ number_format($report['table_4a_totals']['total_tax'], 2) }}</td>
                                    <td class="text-end pe-3 text-dark">₹{{ number_format($report['table_4a_totals']['invoice_value'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table 4B Tab: Reverse Charge -->
        <div class="tab-pane fade" id="tab-4b" role="tabpanel">
            <div class="card app-index-list-card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="bi bi-arrow-repeat me-2 text-warning"></i>Table 4B - Supplies Attracting Reverse Charge (GTA RCM)
                        </h3>
                        <p class="small text-muted mb-0 mt-1">Tax payable by Recipient / Customer u/s 9(3) of CGST Act. GTA reports with Reverse Charge = 'Y'</p>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1.5 rounded-pill">
                        Total Value: ₹{{ number_format($report['table_4b_totals']['invoice_value'], 2) }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="alert alert-warning border-0 rounded-0 m-0 py-2.5 px-3 small d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                        <div>
                            <strong>Statutory GTA Rule:</strong> On these consignments, tax is not charged by the transporter. The consignee/recipient pays GST directly to the Government under RCM. These are reported in GSTR-1 Table 4B with <strong>Reverse Charge: YES</strong>.
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table app-index-table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="width: 140px;">Invoice #</th>
                                    <th style="width: 110px;">Date</th>
                                    <th>Recipient / Consignee</th>
                                    <th style="width: 150px;">GSTIN</th>
                                    <th style="width: 140px;">Place of Supply</th>
                                    <th class="text-center" style="width: 130px;">Reverse Charge</th>
                                    <th class="text-end" style="width: 140px;">Taxable Value (₹)</th>
                                    <th class="text-end" style="width: 100px;">CGST (₹)</th>
                                    <th class="text-end" style="width: 100px;">SGST (₹)</th>
                                    <th class="text-end" style="width: 100px;">IGST (₹)</th>
                                    <th class="text-end pe-3" style="width: 140px;">Invoice Value (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['table_4b'] as $row)
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <a href="{{ route('invoices.show', $row['id']) }}" class="text-decoration-none">
                                            {{ $row['invoice_number'] }}
                                        </a>
                                    </td>
                                    <td>{{ $row['invoice_date'] }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $row['customer_name'] }}</div>
                                    </td>
                                    <td><code>{{ $row['customer_gstin'] }}</code></td>
                                    <td>{{ $row['place_of_supply'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                            <i class="bi bi-shield-check me-1"></i>YES (RCM)
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">₹{{ number_format($row['taxable_value'], 2) }}</td>
                                    <td class="text-end text-muted">{{ $row['cgst'] > 0 ? '₹'.number_format($row['cgst'], 2) : '-' }}</td>
                                    <td class="text-end text-muted">{{ $row['sgst'] > 0 ? '₹'.number_format($row['sgst'], 2) : '-' }}</td>
                                    <td class="text-end text-muted">{{ $row['igst'] > 0 ? '₹'.number_format($row['igst'], 2) : '-' }}</td>
                                    <td class="text-end pe-3 fw-bold text-dark">₹{{ number_format($row['invoice_value'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No Reverse Charge (RCM) invoices found for selected date range.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background-color: var(--brand-table-header-bg);" class="fw-bold">
                                    <td colspan="6" class="ps-3 text-uppercase">Table 4B Total ({{ $report['table_4b_totals']['count'] }} records)</td>
                                    <td class="text-end">₹{{ number_format($report['table_4b_totals']['taxable_value'], 2) }}</td>
                                    <td class="text-end">₹{{ number_format($report['table_4b_totals']['cgst'], 2) }}</td>
                                    <td class="text-end">₹{{ number_format($report['table_4b_totals']['sgst'], 2) }}</td>
                                    <td class="text-end">₹{{ number_format($report['table_4b_totals']['igst'], 2) }}</td>
                                    <td class="text-end pe-3 text-dark">₹{{ number_format($report['table_4b_totals']['invoice_value'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DEDICATED PRINT STATEMENT (Standard GSTR-1 Return Format - Visible ONLY on print) -->
    @php
        $fromCarbon = \Carbon\Carbon::parse($fromDate);
        $toCarbon = \Carbon\Carbon::parse($toDate);
        $periodString = $fromCarbon->format('j-M-y') . ' to ' . $toCarbon->format('j-M-y');
        $cityName = $company->city?->name ?? ($company->address ?? '');
        $companyGstin = $company->gst_number ?? '';

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

    <div class="d-none d-print-block gstr1-print">
        <!-- Header matching Tally/Accounting authentic style -->
        <div class="tally-print-header">
            <div class="tally-company-name">{{ $company->name }}</div>
            @if($cityName)
                <div class="tally-company-city"><u>{{ $cityName }}</u></div>
            @endif
            @if($companyGstin)
                <div class="tally-company-gstin">GSTIN: <strong>{{ $companyGstin }}</strong></div>
            @endif
            <div class="tally-report-title">GSTR-1 Return Statement (Outward Supplies)</div>
            <div class="tally-report-period">{{ $periodString }}</div>
        </div>

        <!-- 1. GSTR-1 Return Summary Table -->
        <div class="tally-section-heading">
            1. Return Summary of Outward Supplies
        </div>
        <table class="tally-gstr1-table">
            <thead>
                <tr class="tally-th-row">
                    <th style="width: 32%;">
                        <span class="tally-title-spaced">Table / Outward Supply Category</span>
                    </th>
                    <th class="text-center" style="width: 8%;">
                        <span class="tally-title-spaced">Count</span>
                    </th>
                    <th class="text-end" style="width: 12%;">
                        <span class="tally-title-spaced">Taxable Value</span>
                    </th>
                    <th class="text-end" style="width: 10%;">
                        <span class="tally-title-spaced">CGST</span>
                    </th>
                    <th class="text-end" style="width: 10%;">
                        <span class="tally-title-spaced">SGST</span>
                    </th>
                    <th class="text-end" style="width: 10%;">
                        <span class="tally-title-spaced">IGST</span>
                    </th>
                    <th class="text-end" style="width: 9%;">
                        <span class="tally-title-spaced">Total Tax</span>
                    </th>
                    <th class="text-end" style="width: 9%;">
                        <span class="tally-title-spaced">Invoice Total</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="tally-row-tr">
                    <td class="tally-cell fw-bold">
                        Table 4A - Taxable Outward Supplies (Forward Charge / GTA FCM)
                    </td>
                    <td class="tally-cell text-center">
                        {{ $report['table_4a_totals']['count'] }}
                    </td>
                    <td class="tally-cell text-end">
                        {{ $formatAmount($report['table_4a_totals']['taxable_value']) }}
                    </td>
                    <td class="tally-cell text-end">
                        {{ $report['table_4a_totals']['cgst'] > 0.001 ? $formatAmount($report['table_4a_totals']['cgst']) : '-' }}
                    </td>
                    <td class="tally-cell text-end">
                        {{ $report['table_4a_totals']['sgst'] > 0.001 ? $formatAmount($report['table_4a_totals']['sgst']) : '-' }}
                    </td>
                    <td class="tally-cell text-end">
                        {{ $report['table_4a_totals']['igst'] > 0.001 ? $formatAmount($report['table_4a_totals']['igst']) : '-' }}
                    </td>
                    <td class="tally-cell text-end fw-semibold">
                        {{ $report['table_4a_totals']['total_tax'] > 0.001 ? $formatAmount($report['table_4a_totals']['total_tax']) : '-' }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4a_totals']['invoice_value']) }}
                    </td>
                </tr>
                <tr class="tally-row-tr">
                    <td class="tally-cell fw-bold">
                        Table 4B - Supplies Attracting Reverse Charge (GTA RCM u/s 9(3))
                    </td>
                    <td class="tally-cell text-center">
                        {{ $report['table_4b_totals']['count'] }}
                    </td>
                    <td class="tally-cell text-end">
                        {{ $formatAmount($report['table_4b_totals']['taxable_value']) }}
                    </td>
                    <td class="tally-cell text-end">
                        {{ $report['table_4b_totals']['cgst'] > 0.001 ? $formatAmount($report['table_4b_totals']['cgst']) : '-' }}
                    </td>
                    <td class="tally-cell text-end">
                        {{ $report['table_4b_totals']['sgst'] > 0.001 ? $formatAmount($report['table_4b_totals']['sgst']) : '-' }}
                    </td>
                    <td class="tally-cell text-end">
                        {{ $report['table_4b_totals']['igst'] > 0.001 ? $formatAmount($report['table_4b_totals']['igst']) : '-' }}
                    </td>
                    <td class="tally-cell text-end fw-semibold">
                        {{ $report['table_4b_totals']['total_tax'] > 0.001 ? $formatAmount($report['table_4b_totals']['total_tax']) : '-' }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4b_totals']['invoice_value']) }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="tally-total-row">
                    <td class="tally-cell fw-bold">
                        <span class="tally-title-spaced">Total Outward Supplies</span>
                    </td>
                    <td class="tally-cell text-center fw-bold">
                        {{ $report['overall_totals']['count'] }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['overall_totals']['taxable_value']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['overall_totals']['cgst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['overall_totals']['sgst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['overall_totals']['igst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['overall_totals']['total_tax']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['overall_totals']['invoice_value']) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- 2. Detailed Table 4A: Forward Charge -->
        <div class="tally-section-heading" style="margin-top: 16px;">
            2. Table 4A - Taxable Outward Supplies (Forward Charge / GTA FCM)
        </div>
        <table class="tally-gstr1-table">
            <thead>
                <tr class="tally-th-row">
                    <th style="width: 10%;">
                        <span class="tally-title-spaced">Invoice #</span>
                    </th>
                    <th style="width: 7%;">
                        <span class="tally-title-spaced">Date</span>
                    </th>
                    <th style="width: 21%;">
                        <span class="tally-title-spaced">Recipient / Customer</span>
                    </th>
                    <th style="width: 12%;">
                        <span class="tally-title-spaced">GSTIN</span>
                    </th>
                    <th style="width: 8%;">
                        <span class="tally-title-spaced">POS</span>
                    </th>
                    <th class="text-end" style="width: 9%;">
                        <span class="tally-title-spaced">Taxable (₹)</span>
                    </th>
                    <th class="text-end" style="width: 7%;">
                        <span class="tally-title-spaced">CGST</span>
                    </th>
                    <th class="text-end" style="width: 7%;">
                        <span class="tally-title-spaced">SGST</span>
                    </th>
                    <th class="text-end" style="width: 7%;">
                        <span class="tally-title-spaced">IGST</span>
                    </th>
                    <th class="text-end" style="width: 6%;">
                        <span class="tally-title-spaced">Tax Total</span>
                    </th>
                    <th class="text-end" style="width: 6%;">
                        <span class="tally-title-spaced">Invoice Total</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['table_4a'] as $row)
                    <tr class="tally-row-tr">
                        <td class="tally-cell fw-semibold">
                            {{ $row['invoice_number'] }}
                        </td>
                        <td class="tally-cell">
                            {{ $row['invoice_date'] }}
                        </td>
                        <td class="tally-cell">
                            {{ $row['customer_name'] }}
                        </td>
                        <td class="tally-cell">
                            {{ $row['customer_gstin'] }}
                        </td>
                        <td class="tally-cell">
                            {{ $row['place_of_supply'] }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $formatAmount($row['taxable_value']) }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $row['cgst'] > 0.001 ? $formatAmount($row['cgst']) : '-' }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $row['sgst'] > 0.001 ? $formatAmount($row['sgst']) : '-' }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $row['igst'] > 0.001 ? $formatAmount($row['igst']) : '-' }}
                        </td>
                        <td class="tally-cell text-end fw-semibold">
                            {{ $row['total_tax'] > 0.001 ? $formatAmount($row['total_tax']) : '-' }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $formatAmount($row['invoice_value']) }}
                        </td>
                    </tr>
                @empty
                    <tr class="tally-row-tr">
                        <td colspan="11" class="tally-cell text-center py-2 text-muted">
                            No Forward Charge (FCM) invoices recorded for selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="tally-total-row">
                    <td colspan="5" class="tally-cell fw-bold">
                        <span class="tally-title-spaced">Table 4A Total</span>
                        <span style="font-weight: 400; font-size: 7.5pt; margin-left: 5px;">({{ $report['table_4a_totals']['count'] }} Invoices)</span>
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4a_totals']['taxable_value']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4a_totals']['cgst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4a_totals']['sgst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4a_totals']['igst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4a_totals']['total_tax']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4a_totals']['invoice_value']) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- 3. Detailed Table 4B: Reverse Charge (RCM) -->
        <div class="tally-section-heading" style="margin-top: 16px;">
            3. Table 4B - Supplies Attracting Reverse Charge (GTA RCM u/s 9(3))
        </div>
        <table class="tally-gstr1-table">
            <thead>
                <tr class="tally-th-row">
                    <th style="width: 10%;">
                        <span class="tally-title-spaced">Invoice #</span>
                    </th>
                    <th style="width: 7%;">
                        <span class="tally-title-spaced">Date</span>
                    </th>
                    <th style="width: 21%;">
                        <span class="tally-title-spaced">Recipient / Consignee</span>
                    </th>
                    <th style="width: 12%;">
                        <span class="tally-title-spaced">GSTIN</span>
                    </th>
                    <th style="width: 8%;">
                        <span class="tally-title-spaced">POS</span>
                    </th>
                    <th class="text-center" style="width: 5%;">
                        <span class="tally-title-spaced">RCM</span>
                    </th>
                    <th class="text-end" style="width: 10%;">
                        <span class="tally-title-spaced">Taxable (₹)</span>
                    </th>
                    <th class="text-end" style="width: 7%;">
                        <span class="tally-title-spaced">CGST</span>
                    </th>
                    <th class="text-end" style="width: 7%;">
                        <span class="tally-title-spaced">SGST</span>
                    </th>
                    <th class="text-end" style="width: 7%;">
                        <span class="tally-title-spaced">IGST</span>
                    </th>
                    <th class="text-end" style="width: 6%;">
                        <span class="tally-title-spaced">Invoice Total</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['table_4b'] as $row)
                    <tr class="tally-row-tr">
                        <td class="tally-cell fw-semibold">
                            {{ $row['invoice_number'] }}
                        </td>
                        <td class="tally-cell">
                            {{ $row['invoice_date'] }}
                        </td>
                        <td class="tally-cell">
                            {{ $row['customer_name'] }}
                        </td>
                        <td class="tally-cell">
                            {{ $row['customer_gstin'] }}
                        </td>
                        <td class="tally-cell">
                            {{ $row['place_of_supply'] }}
                        </td>
                        <td class="tally-cell text-center fw-bold">
                            Y
                        </td>
                        <td class="tally-cell text-end">
                            {{ $formatAmount($row['taxable_value']) }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $row['cgst'] > 0.001 ? $formatAmount($row['cgst']) : '-' }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $row['sgst'] > 0.001 ? $formatAmount($row['sgst']) : '-' }}
                        </td>
                        <td class="tally-cell text-end">
                            {{ $row['igst'] > 0.001 ? $formatAmount($row['igst']) : '-' }}
                        </td>
                        <td class="tally-cell text-end fw-bold">
                            {{ $formatAmount($row['invoice_value']) }}
                        </td>
                    </tr>
                @empty
                    <tr class="tally-row-tr">
                        <td colspan="11" class="tally-cell text-center py-2 text-muted">
                            No Reverse Charge (RCM) invoices recorded for selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="tally-total-row">
                    <td colspan="6" class="tally-cell fw-bold">
                        <span class="tally-title-spaced">Table 4B Total</span>
                        <span style="font-weight: 400; font-size: 7.5pt; margin-left: 5px;">({{ $report['table_4b_totals']['count'] }} Invoices)</span>
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4b_totals']['taxable_value']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4b_totals']['cgst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4b_totals']['sgst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4b_totals']['igst']) }}
                    </td>
                    <td class="tally-cell text-end fw-bold">
                        {{ $formatAmount($report['table_4b_totals']['invoice_value']) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 10px; font-size: 7.5pt; color: #444444; line-height: 1.35;">
            * <strong>Statutory GTA Declaration:</strong> Tax on supplies reported under Table 4B is payable by the recipient of service under Reverse Charge Mechanism (RCM) as per Notification No. 13/2017-Central Tax (Rate).
        </div>
    </div>
</div>
@endsection
