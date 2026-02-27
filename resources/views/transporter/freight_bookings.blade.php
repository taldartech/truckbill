@extends('layouts.app')

@section('title', 'My Good Hires (Lorry Hire)')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold mb-1"><i class="fas fa-truck text-brand-primary"></i> My Good Hires (Lorry Hire)</h2>
                <p class="text-muted mb-0">View your good hires (lorry hire) across all companies.</p>
            </div>
        </div>

        <div class="card glass-card mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-brand-primary">Filter</button>
                        <a href="{{ route('transporter.freight_bookings.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">LR Number</th>
                                <th>Consignor / Consignee</th>
                                <th>Truck</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>GR Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings ?? [] as $booking)
                                <tr>
                                    <td class="ps-4">#{{ $booking->lr->lr_number ?? '-' }}</td>
                                    <td>
                                        <small>{{ $booking->lr->consignor->name ?? '-' }}</small><br>
                                        <small class="text-muted">{{ $booking->lr->consignee->name ?? '-' }}</small>
                                    </td>
                                    <td>{{ $booking->lr->truck->truck_number ?? '-' }}</td>
                                    <td>₹{{ number_format($booking->tr_amount ?? 0, 2) }}</td>
                                    <td>
                                        @if($booking->is_delivered)
                                            <span class="badge bg-success">Delivered</span>
                                        @elseif($booking->is_cancelled)
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-info">In Progress</span>
                                        @endif
                                    </td>
                                    <td>{{ $booking->lr->gr_date ? \Carbon\Carbon::parse($booking->lr->gr_date)->format('d-m-Y') : '-' }}</td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('transporter.freight_bookings.show', $booking) }}" class="btn btn-sm btn-view"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No good hires (lorry hire) found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if(isset($bookings) && $bookings->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
