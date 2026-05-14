@extends('layouts.app')

@section('title', 'Associated Companies')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h2 class="fw-bold mb-1"><i class="fas fa-building text-brand-primary"></i> Associated Companies</h2>
                <p class="text-muted mb-0">Company-wise LR summary across all linked customer accounts.</p>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Company</th>
                                <th>Total LRs</th>
                                <th>Delivered</th>
                                <th>Pending</th>
                                <th>Cancelled</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summaryRows as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->company_name ?? '-' }}</td>
                                    <td>{{ number_format((int) $row->total_lrs) }}</td>
                                    <td>{{ number_format((int) $row->delivered_lrs) }}</td>
                                    <td>{{ number_format((int) $row->pending_lrs) }}</td>
                                    <td>{{ number_format((int) $row->cancelled_lrs) }}</td>
                                    <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No company summary available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
