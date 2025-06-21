<!-- Payments Summary -->
@php
    $totalPayments = $voucher->payments->count();
    $paidPayments = $voucher->payments->where('payment_status', 'paid')->count();
    $pendingPayments = $voucher->payments->where('payment_status', 'pending')->count();
    $inProgressPayments = $voucher->payments->where('payment_status', 'in-progress')->count();
    $totalAmount = $voucher->payments->sum('amount');
    $paidAmount = $voucher->payments->where('payment_status', 'paid')->sum('amount');
    $dueAmount = $voucher->freightBooking ? $voucher->freightBooking->tr_amount : 0;
    $balanceAmount = $dueAmount - $paidAmount;
@endphp
@if($balanceAmount == 0 && $dueAmount > 0)
    <div class="alert alert-success">This voucher is fully paid.</div>
@elseif($balanceAmount < 0)
    <div class="alert alert-danger">Warning: This voucher is overpaid by <b>&#8377;{{ number_format(abs($balanceAmount), 2) }}</b>.</div>
@endif
<div class="row mb-3">
    <div class="col-md-4">
        <div class="alert alert-primary p-2 mb-1">Due Amount: <b>&#8377;{{ number_format($dueAmount, 2) }}</b></div>
    </div>
    <div class="col-md-4">
        <div class="alert alert-success p-2 mb-1">Paid Amount: <b>&#8377;{{ number_format($paidAmount, 2) }}</b></div>
    </div>
    <div class="col-md-4">
        <div class="alert alert-warning p-2 mb-1">
            Balance: <b>&#8377;{{ number_format($balanceAmount, 2) }}</b>
            @if($balanceAmount == 0 && $dueAmount > 0)
                <span class="badge bg-success ms-2">Fully Paid</span>
            @elseif($balanceAmount < 0)
                <span class="badge bg-danger ms-2">Overpaid</span>
            @elseif($balanceAmount > 0 && $paidAmount > 0)
                <span class="badge bg-warning text-dark ms-2">Due</span>
            @endif
        </div>
    </div>
</div>
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
    
<!-- Payments Table -->
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Type</th>
            <th>Mode</th>
            <th>Amount</th>
            <th>Transaction ID</th>
            <th>Status</th>
            <th>Remark</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($voucher->payments as $payment)
        <tr>
            <td>{{ $payment->id }}</td>
            <td>{{ $payment->payment_type }}</td>
            <td>{{ $payment->payment_mode }}</td>
            <td>&#8377;{{ number_format($payment->amount, 2) }}</td>
            <td>{{ $payment->transaction_id }}</td>
            <td>{{ ucfirst($payment->payment_status) }}</td>
            <td>{{ $payment->remark }}</td>
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
        <tr><td colspan="7" class="text-center">No payments found for this voucher.</td></tr>
        @endforelse
    </tbody>
</table>
