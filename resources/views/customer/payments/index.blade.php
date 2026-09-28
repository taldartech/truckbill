@extends('layouts.app')

@section('title', 'Invoice Payments')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h2 class="fw-bold mb-1"><i class="fas fa-wallet text-brand-primary"></i> Invoice Payments</h2>
                <p class="text-muted mb-0">Payments received against invoices across all linked customer accounts.</p>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">All Companies</option>
                            @foreach($companyOptions as $companyOption)
                                <option value="{{ $companyOption['id'] }}" {{ (string) request('company_id') === (string) $companyOption['id'] ? 'selected' : '' }}>
                                    {{ $companyOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-brand-primary">Filter</button>
                        <a href="{{ route('customer.payments.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Company</th>
                                <th>Date</th>
                                <th>Mode</th>
                                <th class="text-end">Remitted (₹)</th>
                                <th class="text-end">TDS 194C (₹)</th>
                                <th class="text-end">Deductions (₹)</th>
                                <th class="text-end pe-4">Total Settled (₹)</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="ps-4 fw-semibold">{{ $payment->invoice->invoice_number ?? $payment->invoice->manual_invoice_number ?? '-' }}</td>
                                    <td>{{ $payment->invoice->company->name ?? '-' }}</td>
                                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '-' }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ ucfirst($payment->mode ?? '-') }}</span></td>
                                    <td class="text-end fw-semibold text-dark">&#8377;{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="text-end text-primary">
                                        {{ (float)($payment->tds_amount ?? 0) > 0 ? '₹'.number_format((float)$payment->tds_amount, 2) : '-' }}
                                    </td>
                                    <td class="text-end text-danger">
                                        @if((float)($payment->deduction_amount ?? 0) > 0)
                                            <span>₹{{ number_format((float)$payment->deduction_amount, 2) }}</span>
                                            @if($payment->deduction_reason)
                                                <small class="text-muted d-block text-xs">({{ $payment->deduction_reason }})</small>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-success">
                                        &#8377;{{ number_format((float)$payment->amount + (float)($payment->tds_amount ?? 0) + (float)($payment->deduction_amount ?? 0), 2) }}
                                    </td>
                                    <td>{{ $payment->remark ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No invoice payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($payments->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
