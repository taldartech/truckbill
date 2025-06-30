@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Invoices</h2>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary mb-3">Add Invoice</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

<!-- Quick Stats -->
@php
    $totalInvoices = $invoices->total();
    $totalAmount = $invoices->sum('total_amount');
    $paidCount = $invoices->where('payment_status', 'paid')->count();
    $unpaidCount = $invoices->where('payment_status', 'pending')->count();
@endphp
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-bg-primary mb-2"><div class="card-body p-2">Total Invoices: <b>{{ $totalInvoices }}</b></div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success mb-2"><div class="card-body p-2">Paid: <b>{{ $paidCount }}</b></div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning mb-2"><div class="card-body p-2">Unpaid: <b>{{ $unpaidCount }}</b></div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info mb-2"><div class="card-body p-2">Total Amount: <b>&#8377;{{ number_format($totalAmount, 2) }}</b></div></div>
    </div>
</div>

<!-- Filter/Search Bar -->
<form method="GET" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <input type="text" name="manual_invoice_number" class="form-control" placeholder="Invoice #" value="{{ request('manual_invoice_number') }}">
        </div>
        <div class="col-md-2">
            <input type="text" name="customer" class="form-control" placeholder="Customer" value="{{ request('customer') }}">
        </div>
        <div class="col-md-2">
            <input type="text" name="company" class="form-control" placeholder="Company" value="{{ request('company') }}">
        </div>
        <div class="col-md-2">
            <select name="payment_status" class="form-select">
                <option value="">All Status</option>
                <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary">Filter</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary ms-1">Reset</a>
        </div>
        <div class="col-md-2 text-end">
            <!-- Column toggles -->
            <div class="dropdown">
                <button class="btn btn-light border dropdown-toggle" type="button" id="toggleColumnsBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    Columns
                </button>
                <ul class="dropdown-menu" aria-labelledby="toggleColumnsBtn" id="columnToggles">
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="id" checked> ID</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="manual_invoice_number" checked> Invoice #</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="company" checked> Company</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="invoice_type" checked> Type</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="generated_date" checked> Date</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="customer" checked> Customer</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="total_amount" checked> Total</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="payment_status" checked> Status</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="toggle-col" data-col="actions" checked> Actions</label></li>
                </ul>
            </div>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="invoicesTable">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-manual_invoice_number">Manual Invoice #</th>
                <th class="col-company">Company</th>
                <th class="col-invoice_type">Type</th>
                <th class="col-generated_date">Date</th>
                <th class="col-customer">Customer</th>
                <th class="col-total_amount">Total</th>
                <th class="col-payment_status">Status</th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td class="col-id">{{ $invoice->id }}</td>
                <td class="col-manual_invoice_number">{{ $invoice->manual_invoice_number }}</td>
                <td class="col-company">{{ $invoice->company ? $invoice->company->name : '-' }}</td>
                <td class="col-invoice_type">{{ $invoice->invoice_type }}</td>
                <td class="col-generated_date">{{ $invoice->generated_date }}</td>
                <td class="col-customer">{{ $invoice->customer ? $invoice->customer->name : '-' }}</td>
                <td class="col-total_amount">&#8377;{{ number_format($invoice->total_amount, 2) }}</td>
                <td class="col-payment_status">{{ ucfirst($invoice->payment_status) }}</td>
                <td class="col-actions">
                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this invoice?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $invoices->links() }}
<div class="mt-3 mb-4">
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
</div>

@endsection

@section('scripts')
<script>
    document.querySelectorAll('.toggle-col').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var colClass = 'col-' + this.getAttribute('data-col');
            document.querySelectorAll('.' + colClass).forEach(function(cell) {
                cell.style.display = checkbox.checked ? '' : 'none';
            }.bind(this));
        });
    });
</script>
@endsection
