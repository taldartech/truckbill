{{-- ACCOUNTANT DASHBOARD - KPI Cards --}}
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="fas fa-calculator text-brand-primary"></i> Accountant Dashboard</h2>
        <p class="text-muted mb-0">Financial overview and payment ledger monitoring.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <div class="badge bg-brand-active text-brand-primary px-3 py-2 rounded-pill">
            <i class="fas fa-calendar-alt me-1"></i> {{ now()->format('d M, Y') }}
        </div>
    </div>
</div>

<!-- Accountant KPI Cards -->
<div class="row g-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-success me-3 shadow-sm">
                    <i class="fa-solid fa-indian-rupee-sign text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Total Revenue</h6>
                    <h3 class="fw-bold mb-1">₹{{ number_format($financialStats['total_revenue'], 2) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill">Total Earnings</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-primary me-3 shadow-sm">
                    <i class="fas fa-file-invoice text-primary"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Total Invoiced</h6>
                    <h3 class="fw-bold mb-1">₹{{ number_format($financialStats['total_invoiced'], 2) }}</h3>
                    <span class="badge bg-soft-primary text-primary rounded-pill">Monthly</span>
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
                    <h6 class="text-muted fw-semi-bold mb-1">Pending Payments</h6>
                    <h3 class="fw-bold mb-1">₹{{ number_format($financialStats['pending_payments'], 2) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Awaiting</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-info me-3 shadow-sm">
                    <i class="fas fa-indian-rupee-sign text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Total Paid</h6>
                    <h3 class="fw-bold mb-1">₹{{ number_format($financialStats['total_paid'], 2) }}</h3>
                    <span class="badge bg-soft-info text-info rounded-pill">Received</span>
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
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Overdue Invoices</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($financialStats['overdue_invoices']) }}</h3>
                    <span class="badge bg-soft-danger text-danger rounded-pill">Action Required</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Priority 1: Pending Invoices - Payment Ledger -->
@if(isset($pendingInvoices) && $pendingInvoices->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-soft-danger text-danger border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Pending Invoices - Payment Ledger (High Priority)</h5>
                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-danger text-white fw-bold rounded-pill px-3">View All</a>
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
                                <th>Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingInvoices as $invoice)
                                <tr>
                                    <td class="ps-4"><span class="fw-bold text-dark">#{{ $invoice->invoice_number
                                        ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '')
                                        : ($invoice->manual_invoice_number ?? $invoice->id) }}</span></td>
                                    <td>
                                        <div class="fw-medium text-dark">{{ Str::limit($invoice->consignee->name ?? 'N/A', 25) }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column">
                                            @php $amountToCollect = $invoice->net_amount ?? $invoice->total_amount; @endphp
                                            <span class="fw-bold text-danger">₹{{ number_format($invoice->remaining_amount, 2) }}</span>
                                            <small class="text-muted">of ₹{{ number_format($amountToCollect, 2) }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $invoice->generated_date ? \Carbon\Carbon::parse($invoice->generated_date)->format('d M Y') : '-' }}</td>
                                    <td class="text-end pe-4 align-middle">
                                        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-icon btn-view rounded-circle" title="View Ledger">
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

<!-- Priority 2: Recent Payment Ledger Entries -->
@if(isset($recentInvoicePayments) && $recentInvoicePayments->count() > 0)
<div class="row mt-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 py-3 mt-1">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-indian-rupee-sign text-success me-2"></i> Recent Payment Ledger Entries</h5>
                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-brand-secondary rounded-pill px-3">View All Invoices</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Customer</th>
                                <th class="text-end">Amount Paid</th>
                                <th>Mode</th>
                                <th class="pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentInvoicePayments as $payment)
                                <tr>
                                    <td class="ps-4">#{{ $payment->invoice->invoice_number
                                        ? $payment->invoice->invoice_number . ($payment->invoice->manual_invoice_number ? ' (' . $payment->invoice->manual_invoice_number . ')' : '')
                                        : ($payment->invoice->manual_invoice_number ?? $payment->invoice->id) }}</td>
                                    <td>{{ Str::limit($payment->invoice->consignee->name ?? 'N/A', 25) }}</td>
                                    <td class="text-end fw-bold text-success">₹{{ number_format($payment->amount, 2) }}</td>
                                    <td><span class="badge bg-soft-info text-info rounded-pill px-3">{{ ucfirst($payment->mode) }}</span></td>
                                    <td class="pe-4">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : $payment->created_at->format('d M Y') }}</td>
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

