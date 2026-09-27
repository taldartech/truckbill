@include('dashboard.partials.hero', [
    'subtitle' => 'Operational overview and priority tasks for your branch.',
    'quickActions' => [
        ['label' => 'New Bilty', 'icon' => 'bi bi-plus-lg', 'url' => route('lorry_receipts.create')],
        ['label' => 'Hires', 'icon' => 'bi bi-truck', 'url' => route('freight_bookings.index')],
    ],
    'showPeriodFilter' => false,
])

<!-- Moderator KPI Cards -->
<div class="row g-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-primary me-3 shadow-sm">
                    <i class="fas fa-truck text-primary"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Incoming Bookings</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($moderatorStats['incoming_bookings']) }}</h3>
                    <span class="badge bg-soft-primary text-primary rounded-pill">Pending Assignment</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-success me-3 shadow-sm">
                    <i class="fas fa-route text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Loading In Progress</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($moderatorStats['loading_in_progress']) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill">At Warehouse</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-info me-3 shadow-sm">
                    <i class="fas fa-shipping-fast text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">En-Route</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($moderatorStats['en_route']) }}</h3>
                    <span class="badge bg-soft-info text-info rounded-pill">On Road</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-clock text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Delayed Trips</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($moderatorStats['delayed_trips']) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Check Required</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-danger me-3 shadow-sm">
                    <i class="fas fa-file-invoice text-danger"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Pending Invoice</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($operationalStats['lrs_pending_invoice']) }}</h3>
                    <span class="badge bg-soft-danger text-danger rounded-pill">Not Invoiced</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">E-Way Expiring</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($operationalStats['eway_bills_expiring_today']) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Expiring Today</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-danger me-3 shadow-sm">
                    <i class="fas fa-times-circle text-danger"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Expired E-Way</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($operationalStats['expired_eway_bills']) }}</h3>
                    <span class="badge bg-soft-danger text-danger rounded-pill">Urgent Action</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-indian-rupee-sign text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Outstanding Amount</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($operationalStats['outstanding_amount'], 2) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Invoice Balance</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Priority 1: Expired Eway Bills (Critical Alert) -->
@if(isset($expiredEwayBills) && $expiredEwayBills->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-soft-danger text-danger border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Expired Eway Bills (Urgent)</h5>
                    <span class="badge bg-danger text-white px-3 rounded-pill">{{ $expiredEwayBills->count() }} Urgent</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4 text-nowrap">LR Number</th>
                                <th class="text-nowrap">Truck & Destination</th>
                                <th class="text-nowrap">Driver Mobile</th>
                                <th class="text-nowrap">Expiry Date</th>
                                <th class="text-end pe-4 text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiredEwayBills->take(10) as $ewayBill)
                                @php
                                    $expiryDate = \Carbon\Carbon::parse($ewayBill->eway_bill_expiry)->startOfDay();
                                    $today = \Carbon\Carbon::today()->startOfDay();
                                    $tomorrow = $today->copy()->addDay();

                                    if ($expiryDate->isPast() || $expiryDate->isSameDay($today)) {
                                        $expiryClass = 'text-danger';
                                        $bgClass = 'bg-soft-danger';
                                    } elseif ($expiryDate->isSameDay($tomorrow)) {
                                        $expiryClass = 'text-warning';
                                        $bgClass = 'bg-soft-warning';
                                    } else {
                                        $expiryClass = 'text-muted';
                                        $bgClass = '';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4 text-nowrap"><span class="fw-bold text-dark">#{{ $ewayBill->freightBooking?->lr?->lr_number }}</span></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-nowrap">{{ $ewayBill->freightBooking?->lr?->truck?->truck_number ?? 'N/A' }}</span>
                                            <small class="text-muted text-truncate" style="max-width: 160px;"><i class="fas fa-map-marker-alt me-1"></i> {{ $ewayBill->freightBooking?->lr?->destination_city }}</small>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">{{ $ewayBill->freightBooking?->lr?->truck?->driver?->mobile ?? '-' }}</td>
                                    <td class="text-nowrap"><span class="badge {{ $bgClass }} {{ $expiryClass }} px-3 py-2 rounded-pill fw-bold text-nowrap"> {{ formatDate(optional($ewayBill)->eway_bill_expiry) }}</span></td>
                                    <td class="text-end pe-4 align-middle text-nowrap">
                                        <a href="{{ route('freight_bookings.show', $ewayBill->freight_booking_id) }}" class="btn btn-icon btn-view rounded-circle" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

<!-- Priority 2: Booking Status Overview -->
@if(isset($bookingStatuses))
<div class="row mt-4 mb-4">
    <div class="col-md-12">
        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-bar text-brand-primary me-2"></i> Booking Status Overview</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4 border-end">
                        <div class="p-3">
                            <h2 class="text-success fw-bold mb-1">{{ number_format($bookingStatuses['delivered']) }}</h2>
                            <p class="text-muted mb-0 fw-medium">Delivered</p>
                            <div class="progress mt-2" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 border-end">
                        <div class="p-3">
                            <h2 class="text-info fw-bold mb-1">{{ number_format($bookingStatuses['in_progress']) }}</h2>
                            <p class="text-muted mb-0 fw-medium">In Progress</p>
                            <div class="progress mt-2" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <h2 class="text-danger fw-bold mb-1">{{ number_format($bookingStatuses['cancelled']) }}</h2>
                            <p class="text-muted mb-0 fw-medium">Cancelled</p>
                            <div class="progress mt-2" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

