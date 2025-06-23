@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Voucher Payments</h2>
        <a href="{{ route('voucher_payments.create') }}" class="btn btn-primary">Add New Payment</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
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
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->voucher->voucher_number }}</td>
                                <td>{{ ucfirst($payment->payment_type) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</td>
                                <td>{{ $payment->transaction_id }}</td>
                                <td>₹{{ number_format($payment->amount, 2) }}</td>
                                <td>{!! $payment->getPaymentStatusBadge() !!}</td>
                                <td>
                                    <a href="{{ route('voucher_payments.edit', $payment) }}" class="btn btn-sm btn-primary">Edit</a>
                                    <form action="{{ route('voucher_payments.destroy', $payment) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
