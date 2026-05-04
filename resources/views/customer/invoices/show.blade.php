@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="card glass-card mb-4">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Invoice {{ $invoice->invoice_number ?? $invoice->manual_invoice_number ?? '#'.$invoice->id }}</h5>
                <a href="{{ route('customer.invoices.index') }}" class="btn btn-back">Back</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Date:</strong> {{ $invoice->generated_date ? \Carbon\Carbon::parse($invoice->generated_date)->format('d-m-Y') : '-' }}</div>
                    <div class="col-md-4"><strong>Invoice Type:</strong> {{ $invoice->invoice_type ?? '-' }}</div>
                    <div class="col-md-4"><strong>Status:</strong> {{ ucfirst($invoice->payment_status ?? 'pending') }}</div>
                    <div class="col-md-4"><strong>Base Amount:</strong> ₹{{ number_format((float) ($invoice->base_amount ?? 0), 2) }}</div>
                    <div class="col-md-4"><strong>Net Amount:</strong> ₹{{ number_format((float) ($invoice->net_amount ?? $invoice->total_amount), 2) }}</div>
                    <div class="col-md-4"><strong>Outstanding:</strong> ₹{{ number_format((float) $invoice->remaining_amount, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-wallet text-brand-primary me-2"></i> Payment History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Mode</th>
                                <th>Amount</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->payments as $payment)
                                <tr>
                                    <td class="ps-4">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '-' }}</td>
                                    <td>{{ ucfirst($payment->mode ?? '-') }}</td>
                                    <td>₹{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td>{{ $payment->remark ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
