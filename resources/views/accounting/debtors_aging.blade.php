@extends('layouts.app')

@section('title', 'Trade Receivables (Debtors) Aging - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
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
    <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-3 py-2.5 px-3" style="border-radius: 10px; background-color: #fef2f2; border: 1px solid #fecaca !important;">
        <i class="bi bi-exclamation-triangle-fill fs-4 text-danger me-3"></i>
        <div class="flex-grow-1">
            <strong class="text-danger">MSME Section 43B(h) Overdue Alert:</strong>
            <span class="text-dark">
                <strong>{{ $report['totals']['msme_alert_customers_count'] }} debtor(s)</strong> have outstanding invoices pending beyond <strong>45 days</strong>, totaling <strong>₹{{ number_format($report['totals']['total_msme_overdue'], 2) }}</strong>. Under Section 43B(h) of the Income Tax Act, delayed payments to registered MSME suppliers attract mandatory tax disallowance.
            </span>
        </div>
    </div>
    @endif

    <!-- KPI Aging Buckets -->
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-2.5 h-100" style="border-radius: 10px; border: 1px solid var(--brand-border) !important;">
                <span class="small fw-semibold text-muted text-uppercase" style="font-size: 0.72rem;">Total Receivables</span>
                <h5 class="fw-bold mb-0 text-dark mt-1">₹{{ number_format($report['totals']['total_receivables'], 2) }}</h5>
                <span class="small text-muted" style="font-size: 0.75rem;">{{ $report['totals']['total_debtors_count'] }} Customers</span>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-2.5 h-100" style="border-radius: 10px; border: 1px solid rgba(34,197,94,0.3) !important; background: #f0fdf4;">
                <span class="small fw-semibold text-success text-uppercase" style="font-size: 0.72rem;">0 - 30 Days</span>
                <h5 class="fw-bold mb-0 text-success mt-1">₹{{ number_format($report['totals']['total_0_30'], 2) }}</h5>
                <span class="small text-muted" style="font-size: 0.75rem;">Current / Normal</span>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-2.5 h-100" style="border-radius: 10px; border: 1px solid rgba(59,130,246,0.3) !important; background: #eff6ff;">
                <span class="small fw-semibold text-primary text-uppercase" style="font-size: 0.72rem;">31 - 60 Days</span>
                <h5 class="fw-bold mb-0 text-primary mt-1">₹{{ number_format($report['totals']['total_31_60'], 2) }}</h5>
                <span class="small text-muted" style="font-size: 0.75rem;">Follow-up due</span>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-2.5 h-100" style="border-radius: 10px; border: 1px solid rgba(245,158,11,0.3) !important; background: #fffbeb;">
                <span class="small fw-semibold text-warning text-uppercase" style="font-size: 0.72rem;">61 - 90 Days</span>
                <h5 class="fw-bold mb-0 text-warning mt-1">₹{{ number_format($report['totals']['total_61_90'], 2) }}</h5>
                <span class="small text-muted" style="font-size: 0.75rem;">Overdue</span>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-2.5 h-100" style="border-radius: 10px; border: 1px solid rgba(239,68,68,0.3) !important; background: #fef2f2;">
                <span class="small fw-semibold text-danger text-uppercase" style="font-size: 0.72rem;">> 90 Days</span>
                <h5 class="fw-bold mb-0 text-danger mt-1">₹{{ number_format($report['totals']['total_over_90'], 2) }}</h5>
                <span class="small text-muted" style="font-size: 0.75rem;">Critical Risk</span>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm p-2.5 h-100" style="border-radius: 10px; border: 1px solid rgba(185,28,28,0.4) !important; background: #fff1f2;">
                <span class="small fw-semibold text-danger text-uppercase" style="font-size: 0.72rem;"><i class="bi bi-shield-exclamation me-1"></i>MSME > 45d Alert</span>
                <h5 class="fw-bold mb-0 text-danger mt-1">₹{{ number_format($report['totals']['total_msme_overdue'], 2) }}</h5>
                <span class="small text-muted" style="font-size: 0.75rem;">Sec 43B(h) overdue</span>
            </div>
        </div>
    </div>

    <!-- Aging Schedule Table Card -->
    <div class="card app-index-list-card shadow-sm">
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
</div>
@endsection
