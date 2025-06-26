@extends('layouts.app')
@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Add Voucher Payment</h1>
      </div>
      <div class="col-sm-6 text-end">
        <a href="{{ route('voucher_payments.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
        <form action="{{ route('voucher_payments.store') }}" method="POST">
          @csrf
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="form-group">
                <label for="payment_type">Payment Type</label>
                <input type="text" name="payment_type" id="payment_type" class="form-control" value="{{ old('payment_type') }}" required>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="form-group">
                <label for="payment_mode">Payment Mode</label>
                <input type="text" name="payment_mode" id="payment_mode" class="form-control" value="{{ old('payment_mode') }}" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="form-group">
                <label for="transaction_id">Transaction ID</label>
                <input type="text" name="transaction_id" id="transaction_id" class="form-control" value="{{ old('transaction_id') }}">
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required step="0.01">
              </div>
            </div>
          </div>
          <div class="form-group mb-3">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control">
              <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
              <option value="failed" {{ old('status') == 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Payment</button>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection


@section('content')
<div class="container mt-4">
    <h2>Add Voucher Payment</h2>
    <form method="POST" action="{{ route('voucher_payments.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="voucher_id" class="form-label">Voucher *</label>
                <select name="voucher_id" id="voucher_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($vouchers as $voucher)
                        <option value="{{ $voucher->id }}" {{ old('voucher_id') == $voucher->id ? 'selected' : '' }}>{{ $voucher->id }}</option>
                    @endforeach
                </select>
                @error('voucher_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="payment_type" class="form-label">Payment Type *</label>
                <select name="payment_type" id="payment_type" class="form-select" required>
                    <option value="">Select Payment Type</option>
                    @foreach($paymentTypes as $paymentType)
                        <option value="{{ $paymentType->name }}" {{ old('payment_type') == $paymentType->name ? 'selected' : '' }}>
                            {{ $paymentType->name }}
                        </option>
                    @endforeach
                </select>
                @error('payment_type') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="payment_mode" class="form-label">Payment Mode *</label>
                <select name="payment_mode" id="payment_mode" class="form-select" required>
                    <option value="">Select Payment Mode</option>
                    @foreach($paymentModes as $paymentMode)
                        <option value="{{ $paymentMode->name }}" {{ old('payment_mode') == $paymentMode->name ? 'selected' : '' }}>
                            {{ $paymentMode->name }}
                        </option>
                    @endforeach
                </select>
                @error('payment_mode') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="transaction_id" class="form-label">Transaction ID *</label>
                <input type="text" class="form-control" name="transaction_id" id="transaction_id" value="{{ old('transaction_id') }}" required>
                @error('transaction_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="remark" class="form-label">Remark</label>
                <textarea name="remark" id="remark" class="form-control">{{ old('remark') }}</textarea>
                @error('remark') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="payment_status" class="form-label">Payment Status *</label>
                <select name="payment_status" id="payment_status" class="form-select" required>
                    <option value="pending" {{ old('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in-progress" {{ old('payment_status') == 'in-progress' ? 'selected' : '' }}>In-Progress</option>
                    <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
                @error('payment_status') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('voucher_payments.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
