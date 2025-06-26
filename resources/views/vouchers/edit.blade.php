@extends('layouts.app')
@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Edit Voucher</h1>
      </div>
      <div class="col-sm-6 text-end">
        <a href="{{ route('vouchers.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>
  </div>
</div>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <form action="{{ route('vouchers.update', $voucher) }}" method="POST">
          @csrf
          @method('PUT')
          <!-- Add your voucher fields here, using AdminLTE form-group and form-control classes -->
          <!-- Example: -->
          <div class="form-group mb-3">
            <label for="voucher_number">Voucher Number</label>
            <input type="text" name="voucher_number" id="voucher_number" class="form-control" value="{{ old('voucher_number', $voucher->voucher_number) }}" required>
          </div>
          <!-- Add more fields as needed -->
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Voucher</button>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection


@section('content')
<div class="container mt-4">
    <h2>Edit Voucher</h2>
    <form method="POST" action="{{ route('vouchers.update', $voucher->id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="freight_booking_id" class="form-label">Freight Booking *</label>
                <select name="freight_booking_id" id="freight_booking_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($freightBookings as $fb)
                        <option value="{{ $fb->id }}" {{ old('freight_booking_id', $voucher->freight_booking_id) == $fb->id ? 'selected' : '' }}>{{ $fb->id }}</option>
                    @endforeach
                </select>
                @error('freight_booking_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="truck_id" class="form-label">Truck *</label>
                <select name="truck_id" id="truck_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}" {{ old('truck_id', $voucher->truck_id) == $truck->id ? 'selected' : '' }}>{{ $truck->truck_number }}</option>
                    @endforeach
                </select>
                @error('truck_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <select name="transporter_id" id="transporter_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($transporters as $transporter)
                        <option value="{{ $transporter->id }}" {{ old('transporter_id', $voucher->transporter_id) == $transporter->id ? 'selected' : '' }}>{{ $transporter->name }}</option>
                    @endforeach
                </select>
                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="pan_verified" class="form-label">PAN Verified?</label>
                <select name="pan_verified" id="pan_verified" class="form-select">
                    <option value="0" {{ old('pan_verified', $voucher->pan_verified) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('pan_verified', $voucher->pan_verified) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="status" class="form-label">Status *</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="pending" {{ old('status', $voucher->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ old('status', $voucher->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
                @error('status') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('vouchers.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>

<hr>
<h4 class="mt-5">Voucher Payments</h4>
<!-- Add Payment Form -->
<form method="POST" action="{{ route('voucher_payments.store') }}" class="border p-3 mb-4 bg-light">
    @csrf
    <input type="hidden" name="voucher_id" value="{{ $voucher->id }}">
    <div class="row">
        <div class="col-md-2 mb-2">
            <input type="text" name="payment_type" class="form-control" placeholder="Payment Type" required value="{{ old('payment_type') }}">
        </div>
        <div class="col-md-2 mb-2">
            <input type="text" name="payment_mode" class="form-control" placeholder="Payment Mode" required value="{{ old('payment_mode') }}">
        </div>
        <div class="col-md-2 mb-2">
            <input type="number" name="amount" class="form-control" placeholder="Amount" step="0.01" min="0" required value="{{ old('amount') }}">
        </div>
        <div class="col-md-2 mb-2">
            <input type="text" name="transaction_id" class="form-control" placeholder="Transaction ID" required value="{{ old('transaction_id') }}">
        </div>
        <div class="col-md-2 mb-2">
            <select name="payment_status" class="form-select" required>
                <option value="pending">Pending</option>
                <option value="in-progress">In-Progress</option>
                <option value="paid">Paid</option>
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <input type="text" name="remark" class="form-control" placeholder="Remark" value="{{ old('remark') }}">
        </div>
        <div class="col-md-1 mb-2">
            <button type="submit" class="btn btn-primary w-100">Add</button>
        </div>
    </div>
    @if($errors->any())
        <div class="alert alert-danger mt-2 mb-0 p-2">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</form>
<div id="payments-section">
    @include('vouchers._payments_section', ['voucher' => $voucher])
</div>

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
</div>

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    $('form[action="{{ route('voucher_payments.store') }}"]').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();
        form.find('button[type=submit]').prop('disabled', true);
        $.post(url, data)
            .done(function() {
                // Reload the payments section (table + summary)
                // Reload only the payments section via AJAX
$.get("/vouchers/{{ $voucher->id }}/payments-section", function(html) {
    $('#payments-section').html(html);
    form[0].reset();
});
            })
            .fail(function(xhr) {
                var errors = xhr.responseJSON.errors;
                var html = '<div class="alert alert-danger mt-2 mb-0 p-2"><ul class="mb-0">';
                $.each(errors, function(k, v) {
                    html += '<li>' + v[0] + '</li>';
                });
                html += '</ul></div>';
                form.find('.alert-danger').remove();
                form.append(html);
            })
            .always(function() {
                form.find('button[type=submit]').prop('disabled', false);
            });
    });
});
</script>
@endsection
