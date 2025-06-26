@extends('layouts.app')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Voucher Payments</h1>
      </div>
      <div class="col-sm-6 text-end">
        <a href="{{ route('voucher_payments.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Payment</a>
      </div>
    </div>
  </div>
</div>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-body">
        @if(session('success'))
          <div class="alert alert-success mb-4">{{ session('success') }}</div>
        @endif
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
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
                  <td><strong>{{ $payment->voucher->voucher_number }}</strong></td>
                  <td>{{ ucfirst($payment->payment_type) }}</td>
                  <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</td>
                  <td>{{ $payment->transaction_id }}</td>
                  <td class="text-success fw-bold">₹{{ number_format($payment->amount, 2) }}</td>
                  <td>{!! $payment->getPaymentStatusBadge() !!}</td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <a href="{{ route('voucher_payments.edit', $payment) }}" class="btn btn-info"><i class="fas fa-edit"></i> Edit</a>
                      <form action="{{ route('voucher_payments.destroy', $payment) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted">No payments found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
          {{ $payments->links() }}
        </div>
        <div class="mt-4">
          <a href="/dashboard" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
