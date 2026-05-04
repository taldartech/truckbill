@extends('layouts.app')

@section('title', 'My Invoices')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h2 class="fw-bold mb-1"><i class="fas fa-file-invoice-dollar text-brand-primary"></i> My Invoices</h2>
                <p class="text-muted mb-0">Invoices across all linked customer accounts.</p>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">All Companies</option>
                            @foreach($companyOptions as $companyOption)
                                <option value="{{ $companyOption['id'] }}" {{ (string) request('company_id') === (string) $companyOption['id'] ? 'selected' : '' }}>
                                    {{ $companyOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All</option>
                            @foreach(['pending' => 'Pending', 'partial' => 'Partial', 'paid' => 'Paid'] as $value => $label)
                                <option value="{{ $value }}" {{ request('payment_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-brand-primary">Filter</button>
                        <a href="{{ route('customer.invoices.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Company</th>
                                <th>Date</th>
                                <th>Net Amount</th>
                                <th>Paid</th>
                                <th>Remaining</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td class="ps-4">{{ $invoice->invoice_number ?? $invoice->manual_invoice_number ?? '-' }}</td>
                                    <td>{{ $invoice->company->name ?? '-' }}</td>
                                    <td>{{ $invoice->generated_date ? \Carbon\Carbon::parse($invoice->generated_date)->format('d-m-Y') : '-' }}</td>
                                    <td>₹{{ number_format((float) ($invoice->net_amount ?? $invoice->total_amount), 2) }}</td>
                                    <td>₹{{ number_format((float) $invoice->paid_amount, 2) }}</td>
                                    <td>₹{{ number_format((float) $invoice->remaining_amount, 2) }}</td>
                                    <td><span class="badge bg-{{ $invoice->payment_status === 'paid' ? 'success' : ($invoice->payment_status === 'partial' ? 'warning' : 'secondary') }}">{{ ucfirst($invoice->payment_status ?? 'pending') }}</span></td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('customer.invoices.show', $invoice) }}" class="btn btn-sm btn-view"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($invoices->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
