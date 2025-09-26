@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h2>Edit Invoice</h2>
        <form method="POST" action="{{ route('invoices.update', $invoice->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="company" class="form-label">Company *</label>
                    <input type="text" class="form-control" disabled id="company"
                        value="{{ $invoice->company ? $invoice->company->name : 'N/A' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="invoice_type" class="form-label">Invoice Type *</label>
                    <input type="text" class="form-control" disabled id="invoice_type"
                        value="{{ ucfirst($invoice->invoice_type) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="generated_date" class="form-label">Generated Date *</label>
                    <input type="date" id="generated_date" class="form-control" disabled
                        value="{{ old('generated_date', $invoice->generated_date) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="customer" class="form-label">Customer *</label>
                    <input type="text" class="form-control" disabled id="customer"
                        value="{{ $invoice->customer_id ? $invoice->consignee->name : 'N/A' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="total_amount" class="form-label">Total Amount *</label>
                    <input type="number" id="total_amount" class="form-control" disabled
                        value="{{ $invoice->total_amount }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="memos" class="form-label">Memos</label>
                    <input type="text" id="memos" class="form-control" disabled
                        value="{{ implode(',', $invoice->memo_ids) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="manual_invoice_number" class="form-label">Manual Invoice #</label>
                    <input type="text" name="manual_invoice_number" class="form-control" id="manual_invoice_number"
                        @if ($invoice->payment_status != 'pending') disabled @endif
                        value="{{ old('manual_invoice_number', $invoice->manual_invoice_number) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="generated_date" class="form-label">Generated Date *</label>
                    <input type="date" name="generated_date" class="form-control" required id="generated_date"
                        @if ($invoice->payment_status != 'pending') disabled @endif
                        value="{{ old('generated_date', $invoice->generated_date) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="payment_status" class="form-label">Payment Status *</label>
                    <select name="payment_status" class="form-select" required id="payment_status">
                        @if ($invoice->payment_status == 'pending')
                        <option value="pending"
                            {{ old('payment_status', $invoice->payment_status) == 'pending' ? 'selected' : '' }}>Pending
                        </option>
                        @endif
                        @if ($invoice->payment_status != 'pending')
                        <option value="partial"
                            {{ old('payment_status', $invoice->payment_status) == 'partial' ? 'selected' : '' }}>Partial
                        </option>
                        @endif
                        <option value="paid"
                            {{ old('payment_status', $invoice->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Update</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary ms-2">Back to List</a>
        </form>
    </div>
@endsection
