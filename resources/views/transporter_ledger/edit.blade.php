@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Ledger Entry</h2>
    <form action="{{ route('transporter_ledger.update', $ledger) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <select class="form-select" id="transporter_id" name="transporter_id" required>
                    <option value="">Select Transporter</option>
                    @foreach($transporters as $transporter)
                        <option value="{{ $transporter->id }}" {{ old('transporter_id', $ledger->transporter_id) == $transporter->id ? 'selected' : '' }}>
                            {{ $transporter->name }}
                        </option>
                    @endforeach
                </select>
                @error('transporter_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="amount" class="form-label">Amount *</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required value="{{ old('amount', $ledger->amount) }}">
                </div>
                @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="transaction_type" class="form-label">Transaction Type *</label>
                <select class="form-select" id="transaction_type" name="transaction_type" required>
                    <option value="">Select Type</option>
                    <option value="debit" {{ old('transaction_type', $ledger->transaction_type) == 'debit' ? 'selected' : '' }}>Debit</option>
                    <option value="credit" {{ old('transaction_type', $ledger->transaction_type) == 'credit' ? 'selected' : '' }}>Credit</option>
                </select>
                @error('transaction_type') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="remark" class="form-label">Remark</label>
                <textarea class="form-control" id="remark" name="remark" rows="3">{{ old('remark', $ledger->remark) }}</textarea>
                @error('remark') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('transporter_ledger.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
