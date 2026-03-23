{{-- ADMIN DASHBOARD - KPI Cards --}}
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="fas fa-grid-2 text-brand-primary"></i> Dashboard</h2>
        <p class="text-muted mb-0">Welcome back! Here's what's happening today.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <div class="badge bg-brand-active text-brand-primary px-3 py-2 rounded-pill">
            <i class="fas fa-calendar-alt me-1"></i> {{ now()->format('d M, Y') }}
        </div>
    </div>
</div>

<!-- Admin KPI Cards -->
<div class="row g-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-primary me-3 shadow-sm">
                    <i class="fas fa-truck text-primary"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Total Bookings</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($monthlyStats['total_bookings']) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill"><i class="fas fa-arrow-up me-1"></i> Monthly</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-success me-3 shadow-sm">
                    <i class="fas fa-route text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Active Trips</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($monthlyStats['active_trips']) }}</h3>
                    <span class="badge bg-soft-info text-info rounded-pill">In Progress</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-clock text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Pending LRs</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($monthlyStats['pending_lrs']) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Pending</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-info me-3 shadow-sm">
                    <i class="fas fa-check-circle text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Delivered Today</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($monthlyStats['delivered_today']) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill">Today</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-danger me-3 shadow-sm">
                    <i class="fas fa-file-invoice text-danger"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Pending Invoice</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($monthlyStats['lrs_pending_invoice']) }}</h3>
                    <span class="badge bg-soft-danger text-danger rounded-pill">Not Invoiced</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">E-Way Expiring</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($monthlyStats['eway_bills_expiring_today']) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Expiring Today</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-danger me-3 shadow-sm">
                    <i class="fas fa-times-circle text-danger"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Expired E-Way</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($monthlyStats['expired_eway_bills']) }}</h3>
                    <span class="badge bg-soft-danger text-danger rounded-pill">Urgent Action</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-money-bill-wave text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Outstanding Amount</h6>
                    <h3 class="fw-bold mb-1">₹{{ number_format($monthlyStats['outstanding_amount'], 2) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Invoice Balance</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Priority 1: Expired Eway Bills (Critical Alert) -->
@if(isset($expiredEwayBills) && $expiredEwayBills->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-soft-danger text-danger border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Expired Eway Bills (Urgent)</h5>
                    <span class="badge bg-danger text-white px-3 rounded-pill">{{ $expiredEwayBills->count() }} Urgent</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">LR Number</th>
                                <th>Truck & Destination</th>
                                <th>Driver Mobile</th>
                                <th>Expiry Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiredEwayBills->take(10) as $ewayBill)
                                @php
                                    $expiryDate = \Carbon\Carbon::parse($ewayBill->eway_bill_expiry)->startOfDay();
                                    $today = \Carbon\Carbon::today()->startOfDay();
                                    $tomorrow = $today->copy()->addDay();

                                    if ($expiryDate->isPast() || $expiryDate->isSameDay($today)) {
                                        $expiryClass = 'text-danger';
                                        $bgClass = 'bg-soft-danger';
                                    } elseif ($expiryDate->isSameDay($tomorrow)) {
                                        $expiryClass = 'text-warning';
                                        $bgClass = 'bg-soft-warning';
                                    } else {
                                        $expiryClass = 'text-muted';
                                        $bgClass = '';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4"><span class="fw-bold text-dark">#{{ $ewayBill->lr->lr_number }}</span></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium">{{ $ewayBill->lr->truck->truck_number ?? 'N/A' }}</span>
                                            <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> {{ $ewayBill->lr->destination_city }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $ewayBill->lr?->truck?->driver?->mobile ?? '-' }}</td>
                                    <td><span class="badge {{ $bgClass }} {{ $expiryClass }} px-3 py-2 rounded-pill fw-bold"> {{ formatDate(optional($ewayBill)->eway_bill_expiry) }}</span></td>
                                    <td class="text-end pe-4 align-middle">
                                        <a href="{{ route('freight_bookings.show', $ewayBill->id) }}" class="btn btn-icon btn-view rounded-circle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Priority 2: Pending Invoices -->
@if(isset($pendingInvoices) && $pendingInvoices->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 py-3 mt-1">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-file-invoice-dollar text-primary me-2"></i> Pending Invoices</h5>
                    {{-- <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-brand-secondary rounded-pill px-3">View All</a> --}}
                    <a href="{{ route('invoices.index') }}" 
                        class="btn btn-sm btn-brand-secondary rounded-pill px-3"
                        style="background-color:#fff !important; color:#43b32f !important; border:1px solid #43b32f !important;">
                        View All
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Customer</th>
                                <th class="text-end">Balance / Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingInvoices as $invoice)
                                @php
                                    $paidAmount = $invoice->paid_amount ?? 0;
                                    $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
                                    $remainingAmount = $amountToCollect - $paidAmount;

                                    if ($paidAmount >= $amountToCollect) {
                                        $statusClass = 'bg-soft-success text-success';
                                        $statusText = 'Paid';
                                    } elseif ($paidAmount > 0) {
                                        $statusClass = 'bg-soft-info text-info';
                                        $statusText = 'Partial';
                                    } else {
                                        $statusClass = 'bg-soft-warning text-warning';
                                        $statusText = 'Pending';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4"><span class="fw-bold text-dark">#{{ $invoice->invoice_number
                                        ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '')
                                        : ($invoice->manual_invoice_number ?? $invoice->id) }}</span></td>
                                    <td>
                                        <div class="fw-medium text-dark">{{ Str::limit($invoice->consignee->name ?? 'N/A', 25) }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-danger">₹{{ number_format($remainingAmount, 2) }}</span>
                                            <small class="text-muted">of ₹{{ number_format($amountToCollect, 2) }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusClass }} px-3 py-2 rounded-pill">{{ $statusText }}</span>
                                    </td>
                                    <td>{{ $invoice->generated_date ? \Carbon\Carbon::parse($invoice->generated_date)->format('d M Y') : '-' }}</td>
                                    <td class="text-end pe-4 align-middle">
                                        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-icon btn-view rounded-circle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Priority 3: Payment Ledger Status Overview -->
@if(isset($invoicePaymentStatuses))
<div class="row mt-4 mb-4">
    <div class="col-md-12">
        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-pie text-brand-secondary me-2"></i> Payment Status Overview</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4 border-end">
                        <div class="p-3">
                            <h2 class="text-warning fw-bold mb-1">{{ number_format($invoicePaymentStatuses['pending']) }}</h2>
                            <p class="text-muted mb-0 fw-medium">Pending</p>
                            <div class="progress mt-2" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 border-end">
                        <div class="p-3">
                            <h2 class="text-info fw-bold mb-1">{{ number_format($invoicePaymentStatuses['partial']) }}</h2>
                            <p class="text-muted mb-0 fw-medium">Partial</p>
                            <div class="progress mt-2" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <h2 class="text-success fw-bold mb-1">{{ number_format($invoicePaymentStatuses['paid']) }}</h2>
                            <p class="text-muted mb-0 fw-medium">Paid</p>
                            <div class="progress mt-2" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif


