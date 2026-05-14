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
                                <th>Amount</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="ps-4">{{ $payment->invoice->invoice_number ?? $payment->invoice->manual_invoice_number ?? '-' }}</td>
                                    <td>{{ $payment->invoice->company->name ?? '-' }}</td>
                                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '-' }}</td>
                                    <td>{{ ucfirst($payment->mode ?? '-') }}</td>
                                    <td>{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td>{{ $payment->remark ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No invoice payments found.</td>
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
