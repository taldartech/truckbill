@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Invoice</h2>
    <form method="POST" action="{{ route('invoices.update', $invoice->id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Manual Invoice #</label>
                <input type="text" name="manual_invoice_number" class="form-control" value="{{ old('manual_invoice_number', $invoice->manual_invoice_number) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Company *</label>
                <select name="company_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $invoice->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Invoice Type *</label>
                <select name="invoice_type" class="form-select" required>
                    <option value="Forward" {{ old('invoice_type', $invoice->invoice_type) == 'Forward' ? 'selected' : '' }}>Forward</option>
                    <option value="Reverse" {{ old('invoice_type', $invoice->invoice_type) == 'Reverse' ? 'selected' : '' }}>Reverse</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Generated Date *</label>
                <input type="date" name="generated_date" class="form-control" required value="{{ old('generated_date', $invoice->generated_date) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Customer *</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($consignees as $consignee)
                        <option value="{{ $consignee->id }}" {{ old('customer_id', $invoice->customer_id) == $consignee->id ? 'selected' : '' }}>{{ $consignee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Total Amount *</label>
                <input type="number" name="total_amount" class="form-control" step="0.01" min="0" required value="{{ old('total_amount', $invoice->total_amount) }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Payment Status *</label>
                <select name="payment_status" class="form-select" required>
                    <option value="pending" {{ old('payment_status', $invoice->payment_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ old('payment_status', $invoice->payment_status) == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="paid" {{ old('payment_status', $invoice->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="col-md-8 mb-3">
                <label class="form-label">Memos</label>
                <select name="memo_ids[]" class="form-select" multiple>
                    @foreach($memos as $memo)
                        <option value="{{ $memo->memo_id }}" {{ $memo->selected ? 'selected' : '' }}>{{ $memo->memo_number }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple.</small>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('invoices.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection
