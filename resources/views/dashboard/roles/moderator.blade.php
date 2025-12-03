{{-- MODERATOR DASHBOARD --}}
<div class="row mb-3">
    <div class="col-12">
        <h2 class="mb-0"><i class="fas fa-tasks text-info"></i> Moderator Dashboard</h2>
        <p class="text-muted">Operational overview and booking management</p>
    </div>
</div>

<!-- Operational Statistics -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-info">
                <i class="fas fa-truck"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Bookings</span>
                <span class="info-box-number">{{ number_format($operationalStats['total_bookings']) }}</span>
                <small>This Month</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-primary">
                <i class="fas fa-truck-loading"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Active Trucks</span>
                <span class="info-box-number">{{ number_format($operationalStats['active_trucks']) }}</span>
                <small>Total Trucks</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-success">
                <i class="fas fa-users"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Active Transporters</span>
                <span class="info-box-number">{{ number_format($operationalStats['active_transporters']) }}</span>
                <small>Currently Active</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-warning">
                <i class="fas fa-clock"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Bookings</span>
                <span class="info-box-number">{{ number_format($operationalStats['pending_bookings']) }}</span>
                <small>Require Action</small>
            </div>
        </div>
    </div>
</div>

<!-- Booking Status -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar text-primary"></i> Booking Status Overview</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <h3 class="text-success">{{ number_format($bookingStatuses['delivered']) }}</h3>
                        <p class="mb-0"><strong>Delivered</strong></p>
                    </div>
                    <div class="col-md-4 text-center">
                        <h3 class="text-info">{{ number_format($bookingStatuses['in_progress']) }}</h3>
                        <p class="mb-0"><strong>In Progress</strong></p>
                    </div>
                    <div class="col-md-4 text-center">
                        <h3 class="text-danger">{{ number_format($bookingStatuses['cancelled']) }}</h3>
                        <p class="mb-0"><strong>Cancelled</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list text-info"></i> Recent Bookings</h3>
                <div class="card-tools">
                    <a href="{{ route('freight_bookings.index') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Consignor</th>
                                <th>Consignee</th>
                                <th>Truck Number</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBookings as $booking)
                                <tr>
                                    <td>#{{ $booking->id }}</td>
                                    <td>{{ $booking->lr->consignor->name ?? 'N/A' }}</td>
                                    <td>{{ $booking->lr->consignee->name ?? 'N/A' }}</td>
                                    <td>{{ $booking->lr->truck->truck_number ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $statusClass = [
                                                'Pending' => 'warning',
                                                'In-Progress' => 'info',
                                                'Delivered' => 'success',
                                                'Cancelled' => 'danger',
                                            ][$booking->lr->delivery_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ str_replace('_', ' ', ucfirst($booking->lr->delivery_status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('freight_bookings.show', $booking->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No recent bookings</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Lists -->
<div class="row mt-4">
    <!-- Top Trucks -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-truck text-primary"></i> Top 5 Trucks</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Truck Number</th>
                                <th class="text-end">LR Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topTrucks as $truck)
                                <tr>
                                    <td>{{ $truck->truck_number ?? 'N/A' }}</td>
                                    <td class="text-end">{{ $truck->lr_count ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Transporters -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users text-success"></i> Top 5 Transporters</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-end">Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topTransporters as $transporter)
                                <tr>
                                    <td>{{ Str::limit($transporter->name, 20) }}</td>
                                    <td class="text-end">{{ $transporter->lorry_receipts_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expired Eway Bills -->
@if(isset($expiredEwayBills) && $expiredEwayBills->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle text-danger"></i> Expired Eway Bills</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>SR no.</th>
                                <th>LR Number</th>
                                <th>Truck Number</th>
                                <th>Driver Mobile</th>
                                <th>Destination</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiredEwayBills as $ewayBill)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $ewayBill->lr->lr_number }}</td>
                                    <td>{{ $ewayBill->lr->truck->truck_number ?? 'N/A' }}</td>
                                    <td>{{ $ewayBill->lr?->truck?->driver?->mobile ?? '-' }}</td>
                                    <td>{{ $ewayBill->lr->destination_city }}</td>
                                    @php
                                        $expiryDate = \Carbon\Carbon::parse($ewayBill->eway_bill_expiry);
                                        $today = \Carbon\Carbon::today();
                                    @endphp
                                    <td
                                        @if ($expiryDate->isSameDay($today) || $expiryDate->lessThan($today))
                                            style="background-color: #FF0000; color: white;"
                                        @elseif ($expiryDate->isSameDay($today->copy()->addDay()))
                                            style="background-color: #FFFF00;"
                                        @else
                                            @if($expiryDate->isSameDay($today->copy()->addDays(2)))
                                            style="background-color: #00FF00;"
                                            @endif
                                        @endif
                                    >
                                        {{ $ewayBill->eway_bill_expiry }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

