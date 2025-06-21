@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Voucher Details</h2>
    <div class="mb-3">
        <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">Back to List</a>
        <a href="{{ route('vouchers.edit', $voucher->id) }}" class="btn btn-warning ms-2">Edit Voucher</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Voucher #{{ $voucher->id }}</h5>
            <p class="card-text">
                <b>Freight Booking:</b> {{ $voucher->freight_booking_id }}<br>
                <b>Truck:</b> {{ $voucher->truck ? $voucher->truck->truck_number : '-' }}<br>
                <b>Transporter:</b> {{ $voucher->transporter ? $voucher->transporter->name : '-' }}<br>
                <b>PAN Verified:</b> {{ $voucher->pan_verified ? 'Yes' : 'No' }}<br>
                <b>Status:</b> {{ ucfirst($voucher->status) }}<br>
            </p>
        </div>
    </div>

    @php
        $totalPayments = $voucher->payments->count();
        $paidPayments = $voucher->payments->where('payment_status', 'paid')->count();
        $pendingPayments = $voucher->payments->where('payment_status', 'pending')->count();
        $inProgressPayments = $voucher->payments->where('payment_status', 'in-progress')->count();
    @endphp
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="alert alert-info p-2 mb-1">Total Payments: <b>{{ $totalPayments }}</b></div>
        </div>
        <div class="col-md-3">
            <div class="alert alert-success p-2 mb-1">Paid: <b>{{ $paidPayments }}</b></div>
        </div>
        <div class="col-md-3">
            <div class="alert alert-warning p-2 mb-1">In-Progress: <b>{{ $inProgressPayments }}</b></div>
        </div>
        <div class="col-md-3">
            <div class="alert alert-secondary p-2 mb-1">Pending: <b>{{ $pendingPayments }}</b></div>
        </div>
    </div>

    <h4>Voucher Payments</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Mode</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @forelse($voucher->payments as $payment)
            <tr>
                <td>{{ $payment->id }}</td>
                <td>{{ $payment->payment_type }}</td>
                <td>{{ $payment->payment_mode }}</td>
                <td>{{ $payment->transaction_id }}</td>
                <td>{{ ucfirst($payment->payment_status) }}</td>
                <td>{{ $payment->remark }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center">No payments found for this voucher.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
