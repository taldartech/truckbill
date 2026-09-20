@extends('layouts.app')

@section('title', 'GSTR-1 Return Preparation - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
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
    <div class="row g-3 mb-3">
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
    <ul class="nav nav-pills mb-3 gap-2" id="gstr1Tabs" role="tablist">
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

    <div class="tab-content" id="gstr1TabContent">
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
</div>
@endsection
