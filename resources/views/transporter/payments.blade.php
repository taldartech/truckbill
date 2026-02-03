@extends('layouts.app')

@section('title', 'My Payments')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold mb-1"><i class="fas fa-money-bill-wave text-brand-primary"></i> My Payments</h2>
                <p class="text-muted mb-0">View your freight and advance payments.</p>
            </div>
        </div>

        <div class="card glass-card mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-receipt me-2"></i> Freight Payments (Voucher)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Voucher #</th>
                                <th>LR Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($voucherPayments ?? [] as $payment)
                                <tr>
                                    <td class="ps-4">{{ $payment->voucher_number ?? '-' }}</td>
                                    <td>#{{ $payment->freight_booking->lr->lr_number ?? '-' }}</td>
                                    <td>₹{{ number_format($payment->amount ?? 0, 2) }}</td>
                                    <td><span class="badge bg-{{ $payment->payment_status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($payment->payment_status ?? '-') }}</span></td>
                                    <td>{{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('d-m-Y') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No freight payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if(isset($voucherPayments) && $voucherPayments->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $voucherPayments->links() }}
                </div>
            @endif
        </div>

        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-wallet me-2"></i> Advance Payments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Amount</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($advancePayments ?? [] as $payment)
                                <tr>
                                    <td class="ps-4">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '-' }}</td>
                                    <td>₹{{ number_format($payment->advance_amount ?? 0, 2) }}</td>
                                    <td>{{ $payment->remark ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No advance payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if(isset($advancePayments) && $advancePayments->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $advancePayments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
