@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Voucher Payments</h2>
    <a href="{{ route('voucher_payments.create') }}" class="btn btn-primary mb-3">Add New Payment</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
   
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Voucher #</th>
                        <th>Payment Type</th>
                        <th>Payment Mode</th>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->voucher->voucher_number }}</td>
                            <td>{{ ucfirst($payment->payment_type) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</td>
                            <td>{{ $payment->transaction_id }}</td>
                            <td>₹{{ number_format($payment->amount, 2) }}</td>
                            <td>{!! $payment->getPaymentStatusBadge() !!}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('voucher_payments.edit', $payment) }}" class="btn btn-sm btn-primary">Edit</a>
                                    <form action="{{ route('voucher_payments.destroy', $payment) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $payments->links() }}
        
    
    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
@endsection
