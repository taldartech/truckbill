@extends('layouts.app')

@section('title', 'Good Hire (Lorry Hire) Details')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <a href="{{ route('transporter.freight_bookings.index') }}" class="btn btn-secondary mb-2"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
                <h2 class="fw-bold mb-1"><i class="fas fa-truck text-brand-primary"></i> Good Hire (Lorry Hire) #{{ $freightBooking->id }}</h2>
                @if ($freightBooking->is_cancelled)
                    <span class="badge bg-danger">Cancelled</span>
                @elseif ($freightBooking->is_delivered)
                    <span class="badge bg-success">Delivered</span>
                @else
                    <span class="badge bg-info">In Progress</span>
                @endif
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header bg-transparent py-3">
                <h5 class="mb-0 fw-bold">Booking Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>LR Number:</strong> #{{ $freightBooking->lr->lr_number ?? '-' }}</p>
                        <p><strong>Consignor:</strong> {{ $freightBooking->lr->consignor->name ?? '-' }}</p>
                        <p><strong>Consignee:</strong> {{ $freightBooking->lr->consignee->name ?? '-' }}</p>
                        <p><strong>Truck:</strong> {{ $freightBooking->lr->truck->truck_number ?? '-' }}</p>
                        <p><strong>GR Date:</strong> {{ $freightBooking->lr->gr_date ? \Carbon\Carbon::parse($freightBooking->lr->gr_date)->format('d-m-Y') : '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Freight Amount:</strong> Rs.{{ number_format($freightBooking->tr_amount ?? 0, 2) }}</p>
                        <p><strong>Advance Amount:</strong> Rs.{{ number_format($freightBooking->advance_amount ?? 0, 2) }}</p>
                        <p><strong>Balance Amount:</strong> Rs.{{ number_format($freightBooking->balance_amount ?? 0, 2) }}</p>
                        <p><strong>TDS Amount:</strong> Rs.{{ number_format($freightBooking->tds_amount ?? 0, 2) }}</p>
                        <p><strong>E-Way Bill:</strong> {{ $freightBooking->eway_bill_number ?? '-' }}</p>
                        <p><strong>E-Way Expiry:</strong> {{ $freightBooking->eway_bill_expiry ? \Carbon\Carbon::parse($freightBooking->eway_bill_expiry)->format('d-m-Y') : '-' }}</p>
                    </div>
                </div>

                @if($freightBooking->payments && $freightBooking->payments->count() > 0)
                <hr>
                <h6 class="fw-bold mb-3">Payment History</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Voucher #</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($freightBooking->payments as $payment)
                                <tr>
                                    <td>{{ $payment->voucher_number ?? '-' }}</td>
                                    <td>Rs.{{ number_format($payment->amount ?? 0, 2) }}</td>
                                    <td><span class="badge bg-{{ $payment->payment_status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($payment->payment_status ?? '-') }}</span></td>
                                    <td>{{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('d-m-Y') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
