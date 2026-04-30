@extends('layouts.app')

@section('title', 'My Lorry Receipts')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h2 class="fw-bold mb-1"><i class="fas fa-file-invoice text-brand-primary"></i> My Lorry Receipts</h2>
                <p class="text-muted mb-0">Shipment visibility across all linked customer accounts.</p>
            </div>
        </div>

        <div class="card glass-card mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
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
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-brand-primary">Filter</button>
                        <a href="{{ route('customer.lorry-receipts.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">LR Number</th>
                                <th>Company</th>
                                <th>GR Date</th>
                                <th>Route</th>
                                <th>Transporter</th>
                                <th>Truck</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lorryReceipts as $lr)
                                <tr>
                                    <td class="ps-4">#{{ $lr->lr_number }}</td>
                                    <td>{{ $lr->customer->company->name ?? '-' }}</td>
                                    <td>{{ $lr->gr_date ? \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $lr->source_city ?? '-' }} to {{ $lr->destination_city ?? '-' }}</td>
                                    <td>{{ $lr->transporter->name ?? '-' }}</td>
                                    <td>{{ $lr->truck->truck_number ?? '-' }}</td>
                                    <td><span class="badge bg-info">{{ $lr->delivery_status ?? 'Pending' }}</span></td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('customer.lorry-receipts.show', $lr) }}" class="btn btn-sm btn-view"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No lorry receipts found for linked customer accounts.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($lorryReceipts->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $lorryReceipts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
