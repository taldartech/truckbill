<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="fas fa-user text-brand-primary"></i> Customer Dashboard</h2>
        <p class="text-muted mb-0">
            Overview across all linked customer accounts.
        </p>
    </div>
    <div class="col-md-4 text-md-end">
        <div class="badge bg-brand-active text-brand-primary px-3 py-2 rounded-pill">
            <i class="fas fa-calendar-alt me-1"></i> {{ now()->format('d M, Y') }}
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-primary me-3 shadow-sm">
                    <i class="fas fa-file-invoice text-primary"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Lorry Receipts</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($customerStats['total_lorry_receipts'] ?? 0) }}</h3>
                    <span class="badge bg-soft-primary text-primary rounded-pill">This Month</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-info me-3 shadow-sm">
                    <i class="fas fa-clock text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Pending LRs</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($customerStats['pending_lorry_receipts'] ?? 0) }}</h3>
                    <span class="badge bg-soft-info text-info rounded-pill">In Progress</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-success me-3 shadow-sm">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Delivered LRs</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($customerStats['delivered_lorry_receipts'] ?? 0) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill">Completed</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-receipt text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Pending Invoices</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($customerStats['pending_invoices'] ?? 0) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Payment Due</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 col-md-4">
        <div class="card glass-card">
            <div class="card-body d-flex align-items-center">
                <div class="icon-circle bg-soft-danger me-3 shadow-sm">
                    <i class="fas fa-indian-rupee-sign text-danger"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Outstanding Amount</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($customerStats['outstanding_amount'] ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($recentLorryReceipts) && $recentLorryReceipts->count() > 0)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card glass-card">
                <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-file-invoice text-brand-primary me-2"></i> Recent Lorry Receipts</h5>
                    <a href="{{ route('customer.lorry-receipts.index') }}" class="btn btn-sm btn-brand-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-soft-secondary">
                                <tr>
                                    <th class="ps-4">LR Number</th>
                                    <th>Date</th>
                                    <th>Route</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLorryReceipts as $lr)
                                    <tr>
                                        <td class="ps-4">#{{ $lr->lr_number }}</td>
                                        <td>{{ $lr->gr_date ? \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $lr->source_city ?? '-' }} to {{ $lr->destination_city ?? '-' }}</td>
                                        <td><span class="badge bg-info">{{ $lr->delivery_status ?? 'Pending' }}</span></td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('customer.lorry-receipts.show', $lr) }}" class="btn btn-sm btn-view"><i class="fas fa-eye"></i></a>
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

@if(isset($recentInvoicePayments) && $recentInvoicePayments->count() > 0)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card glass-card">
                <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-wallet text-brand-primary me-2"></i> Recent Invoice Payments</h5>
                    <a href="{{ route('customer.payments.index') }}" class="btn btn-sm btn-brand-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-soft-secondary">
                                <tr>
                                    <th class="ps-4">Invoice</th>
                                    <th>Mode</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentInvoicePayments as $payment)
                                    <tr>
                                        <td class="ps-4">{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                        <td>{{ ucfirst($payment->mode ?? '-') }}</td>
                                        <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ number_format($payment->amount ?? 0, 2) }}</td>
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
