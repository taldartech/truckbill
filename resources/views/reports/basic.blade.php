@extends('layouts.app')

@section('title', 'Basic Freight Report')

@section('content')
<div class="container-fluid" id="printable-area">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Basic Freight Report</h5>
                    <div>
                        <span class="badge badge-light">{{ $start_date }} to {{ $end_date }}</span>
                        @if(request('company_id'))
                            <span class="badge badge-info ml-2">
                                {{ \App\Models\Company::find(request('company_id'))->name ?? 'Selected Company' }}
                            </span>
                        @endif
                        @if(request('transporter_id'))
                            <span class="badge badge-secondary ml-2">
                                {{ \App\Models\Transporter::find(request('transporter_id'))->name ?? 'Selected Transporter' }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="info-box bg-gradient-info">
                                <span class="info-box-icon"><i class="fas fa-truck"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Bookings</span>
                                    <span class="info-box-number">{{ $summary['total_bookings'] }}</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">
                                        {{ $summary['delivered'] ?? 0 }} delivered, {{ $summary['in_transit'] ?? 0 }} in transit
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="info-box bg-gradient-success">
                                <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Freight</span>
                                    <span class="info-box-number">₹{{ number_format($summary['total_freight'] ?? 0, 2) }}</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">
                                        {{ ($summary['pending'] ?? 0) > 0 ? $summary['pending'] . ' pending' : 'All processed' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="info-box bg-gradient-warning">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Delivered</span>
                                    <span class="info-box-number">{{ $summary['delivered'] ?? 0 }}</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: {{ ($summary['total_bookings'] ?? 0) > 0 ? (($summary['delivered'] ?? 0) / ($summary['total_bookings'] ?? 1)) * 100 : 0 }}%"></div>
                                    </div>
                                    <span class="progress-description">
                                        {{ ($summary['total_bookings'] ?? 0) > 0 ? round((($summary['delivered'] ?? 0) / $summary['total_bookings']) * 100, 1) : 0 }}% of total
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="info-box bg-gradient-danger">
                                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">In Transit</span>
                                    <span class="info-box-number">{{ $summary['in_transit'] ?? 0 }}</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: {{ ($summary['total_bookings'] ?? 0) > 0 ? (($summary['in_transit'] ?? 0) / ($summary['total_bookings'] ?? 1)) * 100 : 0 }}%"></div>
                                    </div>
                                    <span class="progress-description">
                                        {{ ($summary['total_bookings'] ?? 0) > 0 ? round((($summary['in_transit'] ?? 0) / $summary['total_bookings']) * 100, 1) : 0 }}% of total
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                    <!-- Detailed Bookings Table -->
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Booking Details</h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                    <i class="fas fa-print mr-1"></i> Print
                                </button>
                                <a href="{{ route('reports.generate', array_merge(request()->all(), ['format' => 'pdf'])) }}" 
                                   class="btn btn-sm btn-outline-danger ml-1" target="_blank">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </a>
                                <a href="{{ route('reports.generate', array_merge(request()->all(), ['format' => 'excel'])) }}" 
                                   class="btn btn-sm btn-outline-success ml-1" target="_blank">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
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
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bookings as $index => $booking)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $booking->booking_number ?? 'N/A' }}</td>
                                            <td>{{ $booking->created_at->format('d-m-Y') }}</td>
                                            <td>{{ $booking->sourceCity->name ?? 'N/A' }}</td>
                                            <td>{{ $booking->destinationCity->name ?? 'N/A' }}</td>
                                            <td>{{ $booking->transporter->name ?? 'N/A' }}</td>
                                            <td>{{ $booking->truck->truck_number ?? 'N/A' }}</td>
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
                                                <a href="{{ route('freight_bookings.show', $booking->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No bookings found for the selected criteria.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        @if($bookings->isNotEmpty())
                                        <tr class="font-weight-bold">
                                            <td colspan="7" class="text-right">Total:</td>
                                            <td class="text-right">₹{{ number_format($bookings->sum('total_amount'), 2) }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                        @endif
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Generated on {{ now()->format('d M Y h:i A') }}
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-area, #printable-area * {
            visibility: visible;
        }
        #printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table th, .table td {
            border-color: #dee2e6 !important;
        }
    }
    .info-box {
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        border-radius: 0.25rem;
        background: #fff;
        display: -ms-flexbox;
        display: flex;
        margin-bottom: 1rem;
        min-height: 80px;
        padding: 0.5rem;
        position: relative;
        color: #fff;
    }
    .info-box .info-box-icon {
        border-radius: 0.25rem;
        -ms-flex-align: center;
        align-items: center;
        display: -ms-flexbox;
        display: flex;
        font-size: 1.875rem;
        -ms-flex-pack: center;
        justify-content: center;
        text-align: center;
        width: 70px;
    }
    .info-box .info-box-content {
        -ms-flex: 1;
        flex: 1;
        padding: 5px 10px;
    }
    .info-box .info-box-text, .info-box .progress-description {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .info-box .info-box-number {
        display: block;
        font-weight: 700;
        font-size: 1.2rem;
    }
    .info-box .progress {
        background-color: rgba(255, 255, 255, 0.3);
        height: 2px;
        margin: 5px 0;
    }
    .info-box .progress-bar {
        background-color: #fff;
    }
    .bg-gradient-primary {
        background: linear-gradient(45deg, #4e73df, #224abe) !important;
    }
    .bg-gradient-success {
        background: linear-gradient(45deg, #1cc88a, #13855c) !important;
    }
    .bg-gradient-info {
        background: linear-gradient(45deg, #36b9cc, #258391) !important;
    }
    .bg-gradient-warning {
        background: linear-gradient(45deg, #f6c23e, #dda20a) !important;
    }
    .bg-gradient-danger {
        background: linear-gradient(45deg, #e74a3b, #be2617) !important;
    }
    .table th {
        border-top: none;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    .table td {
        vertical-align: middle;
    }
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
    }
    .no-print {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table {
            font-size: 12px;
        }
        .table th, .table td {
            padding: 0.3rem;
        }
    }
</style>
@endpush
