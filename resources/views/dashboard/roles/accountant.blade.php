{{-- ACCOUNTANT DASHBOARD --}}
<div class="row mb-3">
    <div class="col-12">
        <h2 class="mb-0"><i class="fas fa-calculator text-brand-primary"></i> Accountant Dashboard</h2>
        <p class="text-muted">Financial overview and payment management</p>
    </div>
</div>

<!-- Financial Statistics -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-primary">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Revenue</span>
                <span class="info-box-number">₹{{ number_format($financialStats['total_revenue'], 2) }}</span>
                <small>This Month</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-primary">
                <i class="fas fa-check-circle"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Paid Amount</span>
                <span class="info-box-number">₹{{ number_format($financialStats['paid_amount'], 2) }}</span>
                <small>This Month</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-warning">
                <i class="fas fa-clock"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Payments</span>
                <span class="info-box-number">{{ number_format($financialStats['pending_payments']) }}</span>
                <small>Require Attention</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-danger">
                <i class="fas fa-exclamation-circle"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Amount</span>
                <span class="info-box-number">₹{{ number_format($financialStats['pending_amount'], 2) }}</span>
                <small>Outstanding</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-secondary">
                <i class="fas fa-file-invoice"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Invoices</span>
                <span class="info-box-number">{{ number_format($financialStats['total_invoices']) }}</span>
                <small>This Month</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-warning">
                <i class="fas fa-file-invoice-dollar"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Invoices</span>
                <span class="info-box-number">{{ number_format($financialStats['pending_invoices']) }}</span>
                <small>Require Action</small>
            </div>
        </div>
    </div>
</div>

<!-- Payment Status Breakdown -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie text-brand-secondary"></i> Payment Status Breakdown</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <h3 class="text-warning">{{ number_format($paymentStatuses['pending']) }}</h3>
                        <p class="mb-0"><strong>Pending</strong></p>
                    </div>
                    <div class="col-md-4 text-center">
                        <h3 class="text-info">{{ number_format($paymentStatuses['in_progress']) }}</h3>
                        <p class="mb-0"><strong>In Progress</strong></p>
                    </div>
                    <div class="col-md-4 text-center">
                        <h3 class="text-success">{{ number_format($paymentStatuses['paid']) }}</h3>
                        <p class="mb-0"><strong>Paid</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Payments (High Priority) -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle text-brand-danger"></i> Pending Payments (High Priority)</h3>
                <div class="card-tools">
                    <a href="{{ route('voucher_payments.index') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Voucher #</th>
                                <th>LR Number</th>
                                <th>Amount</th>
                                <th>Payment Type</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingPayments as $payment)
                                <tr>
                                    <td>#{{ $payment->voucher_number ?? $payment->id }}</td>
                                    <td>{{ $payment->freight_booking->lr->lr_number ?? 'N/A' }}</td>
                                    <td>₹{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->payment_type ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-warning">{{ ucfirst($payment->payment_status) }}</span>
                                    </td>
                                    <td>{{ $payment->date ?? $payment->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No pending payments</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payments and Invoices -->
<div class="row mt-4">
    <!-- Recent Payments -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave text-brand-primary"></i> Recent Payments</h3>
                <div class="card-tools">
                    <a href="{{ route('voucher_payments.index') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Voucher #</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td>#{{ $payment->voucher_number ?? $payment->id }}</td>
                                    <td>₹{{ number_format($payment->amount, 2) }}</td>
                                    <td>
                                        @php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'in progress' => 'info',
                                                'paid' => 'success',
                                            ][strtolower($payment->payment_status)] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst($payment->payment_status) }}</span>
                                    </td>
                                    <td>{{ $payment->date ?? $payment->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No recent payments</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice text-brand-secondary"></i> Recent Invoices</h3>
                <div class="card-tools">
                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $invoice)
                                <tr>
                                    <td>#{{ $invoice->manual_invoice_number ?? $invoice->id }}</td>
                                    <td>{{ Str::limit($invoice->consignee->name ?? 'N/A', 20) }}</td>
                                    <td class="text-end">₹{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>
                                        @php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'paid' => 'success',
                                            ][strtolower($invoice->payment_status)] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst($invoice->payment_status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No recent invoices</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Payers -->
@if(isset($topPayers) && $topPayers->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trophy text-brand-warning"></i> Top 5 Payers (This Month)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th class="text-end">Total Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topPayers as $payer)
                                <tr>
                                    <td>{{ $payer->name }}</td>
                                    <td class="text-end">₹{{ number_format($payer->total_paid ?? 0, 2) }}</td>
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

