@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Add Bank Account</h2>
    <form action="{{ route('transporter_bank_accounts.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <select class="form-select" id="transporter_id" name="transporter_id" required>
                    <option value="">Select Transporter</option>
                    {{-- Only show transporters for user's company unless super_admin. $transporters should be filtered in controller. --}}
                    @foreach($transporters as $transporter)
                        <option value="{{ $transporter->id }}" {{ old('transporter_id') == $transporter->id ? 'selected' : '' }}>
                            {{ $transporter->name }}
                        </option>
                    @endforeach
                </select>
                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="bank_name" class="form-label">Bank Name *</label>
                <input type="text" class="form-control" id="bank_name" name="bank_name" required value="{{ old('bank_name') }}">
                @error('bank_name') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="account_number" class="form-label">Account Number *</label>
                <input type="text" class="form-control" id="account_number" name="account_number" required value="{{ old('account_number') }}">
                @error('account_number') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ifsc_code" class="form-label">IFSC Code *</label>
                <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" required value="{{ old('ifsc_code') }}">
                @error('ifsc_code') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="beneficiary_name" class="form-label">Beneficiary Name *</label>
                <input type="text" class="form-control" id="beneficiary_name" name="beneficiary_name" required value="{{ old('beneficiary_name') }}">
                @error('beneficiary_name') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="bulk_payment_id" class="form-label">Bulk Payment ID</label>
                <input type="text" class="form-control" id="bulk_payment_id" name="bulk_payment_id" value="{{ old('bulk_payment_id') }}">
                @error('bulk_payment_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('transporter_bank_accounts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
