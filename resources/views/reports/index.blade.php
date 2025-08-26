@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Report Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Freight Report</h1>
        <div class="d-flex">
            <form id="dateRangeForm" class="form-inline mr-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    </div>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start_date }}">
                    <div class="input-group-append">
                        <span class="input-group-text">to</span>
                    </div>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end_date }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt mr-1"></i> Update
                        </button>
                    </div>
                </div>
            </form>
            <div class="btn-group">
                <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-download mr-1"></i> Export
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="exportPdf"><i class="fas fa-file-pdf text-danger mr-2"></i>PDF</a>
                    <a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel text-success mr-2"></i>Excel</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Bookings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total_bookings']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Freight Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($summary['total_freight'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Delivered</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $summary['delivered'] }}</div>
                                </div>
                                <div class="col">
                                    @if($summary['total_bookings'] > 0)
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: {{ ($summary['delivered'] / $summary['total_bookings']) * 100 }}%" 
                                             aria-valuenow="{{ $summary['delivered'] }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="{{ $summary['total_bookings'] }}">
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                In Transit</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summary['in_transit'] }}</div>
                            <div class="text-xs mt-1">
                                {{ $summary['pending'] }} Pending | {{ $summary['cancelled'] ?? 0 }} Cancelled
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
            <div>
                <a href="{{ route('freight_bookings.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus mr-1"></i> New Booking
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>LR No.</th>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Transporter</th>
                            <th>Vehicle No.</th>
                            <th class="text-right">Amount (₹)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $booking->lr_no }}</td>
                            <td>{{ $booking->date ? $booking->date->format('d-M-Y') : 'N/A' }}</td>
                            <td>{{ optional($booking->sourceCity)->name }}</td>
                            <td>{{ optional($booking->destinationCity)->name }}</td>
                            <td>{{ optional($booking->transporter)->name }}</td>
                            <td>{{ optional($booking->truck)->vehicle_number ?? 'N/A' }}</td>
                            <td class="text-right">₹{{ number_format($booking->total_amount, 2) }}</td>
                            <td>
                                @php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'in_transit' => 'info',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ][$booking->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('freight_bookings.show', $booking->id) }}" 
                                   class="btn btn-sm btn-primary" 
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No bookings found for the selected period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($bookings->hasPages())
            <div class="mt-3">
                {{ $bookings->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .card-header {
        border-radius: 0.5rem 0.5rem 0 0 !important;
    }
    .summary-card {
        border-left: 4px solid #4e73df;
    }
    .summary-card .card-body {
        padding: 1rem;
    }
    .summary-card h5 {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #4e73df;
        margin-bottom: 0.25rem;
    }
    .summary-card h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0;
    }
    .select2-container--default .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
        border: 1px solid #d1d3e2;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Handle date range form submission
    $('#dateRangeForm').on('submit', function(e) {
        e.preventDefault();
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();
        
        // Reload the page with new date range
        window.location.href = `{{ route('reports.index') }}?start_date=${startDate}&end_date=${endDate}`;
    });

    // Handle export buttons
    $('#exportPdf').on('click', function(e) {
        e.preventDefault();
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();
        window.location.href = `{{ route('reports.export.pdf') }}?start_date=${startDate}&end_date=${endDate}`;
    });

    $('#exportExcel').on('click', function(e) {
        e.preventDefault();
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();
        window.location.href = `{{ route('reports.export.excel') }}?start_date=${startDate}&end_date=${endDate}`;
    });
});
</script>
@endpush
