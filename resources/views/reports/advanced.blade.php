<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line text-primary mr-2"></i>
                    Advanced Freight Analytics
                </h5>
                <div class="btn-group">
                    <span class="badge badge-primary p-2">
                        <i class="far fa-calendar-alt mr-1"></i>
                        {{ $start_date }} to {{ $end_date }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Transporter Performance -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">Transporter Performance</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Transporter</th>
                                        <th class="text-center">Total Bookings</th>
                                        <th class="text-right">Total Freight (₹)</th>
                                        <th class="text-right">Avg. Freight (₹)</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transporterData as $transporter)
                                    <tr>
                                        <td>{{ $transporter['name'] }}</td>
                                        <td class="text-center">{{ $transporter['total_bookings'] }}</td>
                                        <td class="text-right">₹{{ number_format($transporter['total_freight'], 2) }}</td>
                                        <td class="text-right">₹{{ number_format($transporter['avg_freight'], 2) }}</td>
                                        <td>
                                            @foreach($transporter['statuses'] as $status => $count)
                                                @php
                                                    $statusClass = [
                                                        'pending' => 'warning',
                                                        'in_transit' => 'info',
                                                        'delivered' => 'success',
                                                        'cancelled' => 'danger'
                                                    ][$status] ?? 'secondary';
                                                @endphp
                                                <span class="badge badge-{{ $statusClass }} mr-1 mb-1" title="{{ ucfirst($status) }}">
                                                    {{ $count }} {{ strtoupper(substr($status, 0, 1)) }}
                                                </span>
                                            @endforeach
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No transporter data available.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="row
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold text-primary">Monthly Booking Trends</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyTrendsChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold text-primary">Status Distribution</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="statusDistributionChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Bookings Table -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Detailed Bookings</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Booking ID</th>
                                    <th>Date</th>
                                    <th>Transporter</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Truck</th>
                                    <th class="text-right">Amount (₹)</th>
                                    <th>Status</th>
                                    <th>LR No.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $index => $booking)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $booking->booking_number ?? 'N/A' }}</td>
                                    <td>{{ $booking->created_at->format('d-m-Y') }}</td>
                                    <td>{{ $booking->transporter->name ?? 'N/A' }}</td>
                                    <td>{{ $booking->sourceCity->name ?? 'N/A' }}</td>
                                    <td>{{ $booking->destinationCity->name ?? 'N/A' }}</td>
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
                                    <td>{{ $booking->lr->lr_number ?? 'N/A' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center">No bookings found for the selected criteria.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="text-right">
                <small class="text-muted">
                    Generated on {{ now()->format('d M Y h:i A') }}
                </small>
            </div>
        </div>
    </div>
</div>

@if(!request()->has('format') || request('format') === 'view')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Monthly Trends Chart
    const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
    const monthlyData = @json($monthlyData);
    const months = Object.keys(monthlyData);
    const bookingsData = months.map(m => monthlyData[m].bookings);
    const freightData = months.map(m => monthlyData[m].freight);

    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Bookings',
                    data: bookingsData,
                    backgroundColor: 'rgba(78, 115, 223, 0.5)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Freight (₹)',
                    data: freightData,
                    type: 'line',
                    borderColor: 'rgba(28, 200, 138, 1)',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                    pointRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Number of Bookings'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    title: {
                        display: true,
                        text: 'Freight (₹)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label === 'Freight (₹)') {
                                return `${label}: ₹${context.raw.toLocaleString('en-IN')}`;
                            }
                            return `${label}: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
    const statusData = @json($bookings->groupBy('status')->map->count());
    const statusLabels = Object.keys(statusData).map(status => 
        status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
    );
    const statusCounts = Object.values(statusData);
    const backgroundColors = [
        'rgba(255, 193, 7, 0.7)',   // Pending - Yellow
        'rgba(23, 162, 184, 0.7)',  // In Transit - Teal
        'rgba(40, 167, 69, 0.7)',   // Delivered - Green
        'rgba(220, 53, 69, 0.7)',   // Cancelled - Red
    ];

    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #reportContainer, #reportContainer * {
            visibility: visible;
        }
        #reportContainer {
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
            page-break-inside: avoid;
        }
        .table {
            font-size: 10px;
        }
        .table th, .table td {
            padding: 0.2rem;
        }
        .chartjs-size-monitor {
            display: none !important;
        }
        canvas {
            max-height: 300px !important;
        }
    }
</style>
@endpush
@endif
