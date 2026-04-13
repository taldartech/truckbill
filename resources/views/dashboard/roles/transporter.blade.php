{{-- TRANSPORTER DASHBOARD - KPI Cards --}}
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="fas fa-truck text-brand-primary"></i> Transporter Dashboard</h2>
        <p class="text-muted mb-0">Your good hires (lorry hire) and payments overview.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <div class="badge bg-brand-active text-brand-primary px-3 py-2 rounded-pill">
            <i class="fas fa-calendar-alt me-1"></i> {{ now()->format('d M, Y') }}
        </div>
    </div>
</div>

<!-- Transporter KPI Cards -->
<div class="row g-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-primary me-3 shadow-sm">
                    <i class="fas fa-truck text-primary"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Total Bookings</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($transporterStats['total_bookings'] ?? 0) }}</h3>
                    <span class="badge bg-soft-primary text-primary rounded-pill">This Month</span>
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
                    <h6 class="text-muted fw-semi-bold mb-1">Active Trips</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($transporterStats['active_trips'] ?? 0) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill">In Progress</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-info me-3 shadow-sm">
                    <i class="fas fa-file-invoice text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Pending LRs</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($transporterStats['pending_lrs'] ?? 0) }}</h3>
                    <span class="badge bg-soft-info text-info rounded-pill">Awaiting</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-check-circle text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Delivered Today</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($transporterStats['delivered_today'] ?? 0) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Completed</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-success me-3 shadow-sm">
                    <i class="fas fa-indian-rupee-sign text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Freight Received</h6>
                    <h3 class="fw-bold mb-1">₹{{ number_format($transporterStats['payments_received'] ?? 0, 2) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill">Paid</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-info me-3 shadow-sm">
                    <i class="fas fa-wallet text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Advance Received</h6>
                    <h3 class="fw-bold mb-1">₹{{ number_format($transporterStats['advance_received'] ?? 0, 2) }}</h3>
                    <span class="badge bg-soft-info text-info rounded-pill">Advance</span>
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
                    <h3 class="fw-bold mb-1">{{ number_format($transporterStats['expired_eway_bills'] ?? 0) }}</h3>
                    <span class="badge bg-soft-danger text-danger rounded-pill">Urgent</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expired Eway Bills -->
@if(isset($expiredEwayBills) && $expiredEwayBills->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-soft-danger text-danger border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Expired Eway Bills (Urgent)</h5>
                    <a href="{{ route('transporter.freight_bookings.index') }}" class="btn btn-sm btn-danger">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">LR Number</th>
                                <th>Truck & Destination</th>
                                <th>Expiry Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiredEwayBills->take(10) as $ewayBill)
                                <tr>
                                    <td class="ps-4"><span class="fw-bold text-dark">#{{ $ewayBill->lr->lr_number ?? 'N/A' }}</span></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium">{{ $ewayBill->lr->truck->truck_number ?? 'N/A' }}</span>
                                            <small class="text-muted">{{ $ewayBill->lr->destination_city ?? '-' }}</small>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-danger text-white px-3 py-2 rounded-pill"> {{ formatDate(optional($ewayBill)->eway_bill_expiry) }}</span></td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('transporter.freight_bookings.show', $ewayBill->id) }}" class="btn btn-sm btn-view"><i class="fas fa-eye"></i></a>
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

<!-- Recent Good Hires (Lorry Hire) -->
@if(isset($recentFreightBookings) && $recentFreightBookings->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-truck text-brand-primary me-2"></i> Recent Good Hires (Lorry Hire)</h5>
                <a href="{{ route('transporter.freight_bookings.index') }}" class="btn btn-sm btn-brand-primary">View All</a>
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
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentFreightBookings as $booking)
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
                                    <td class="text-end pe-4">
                                        <a href="{{ route('transporter.freight_bookings.show', $booking->id) }}" class="btn btn-sm btn-view"><i class="fas fa-eye"></i></a>
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

<!-- Recent Payments -->
@if(isset($recentPayments) && $recentPayments->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-indian-rupee-sign text-brand-primary me-2"></i> Recent Payments</h5>
                <a href="{{ route('transporter.payments.index') }}" class="btn btn-sm btn-brand-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Voucher #</th>
                                <th>LR Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPayments as $payment)
                                <tr>
                                    <td class="ps-4">{{ $payment->voucher_number ?? '-' }}</td>
                                    <td>#{{ $payment->freight_booking->lr->lr_number ?? '-' }}</td>
                                    <td>₹{{ number_format($payment->amount ?? 0, 2) }}</td>
                                    <td><span class="badge bg-{{ $payment->payment_status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($payment->payment_status ?? '-') }}</span></td>
                                    <td>{{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('d-m-Y') : '-' }}</td>
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
