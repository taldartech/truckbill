@extends('layouts.app')

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
                <input type="text" class="form-control" name="payment_type" id="payment_type" value="{{ old('payment_type') }}" required>
                @error('payment_type') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="payment_mode" class="form-label">Payment Mode *</label>
                <input type="text" class="form-control" name="payment_mode" id="payment_mode" value="{{ old('payment_mode') }}" required>
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
