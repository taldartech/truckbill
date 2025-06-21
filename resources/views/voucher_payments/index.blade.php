@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Voucher Payments</h2>
    <a href="{{ route('voucher_payments.create') }}" class="btn btn-primary mb-3">Add Voucher Payment</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label for="payment_status" class="form-label">Payment Status</label>
            <select name="payment_status" id="payment_status" class="form-select">
                <option value="">All</option>
                <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in-progress" {{ request('payment_status') == 'in-progress' ? 'selected' : '' }}>In-Progress</option>
                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </div>
    </form>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Voucher</th>
                <th>Type</th>
                <th>Mode</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->id }}</td>
                <td>{{ $payment->voucher ? $payment->voucher->id : '-' }}</td>
                <td>{{ $payment->payment_type }}</td>
                <td>{{ $payment->payment_mode }}</td>
                <td>{{ $payment->transaction_id }}</td>
                <td>{{ ucfirst($payment->payment_status) }}</td>
                <td>
                    <a href="{{ route('voucher_payments.edit', $payment->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('voucher_payments.destroy', $payment->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No payments found.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $payments->links() }}
</div>
@endsection
