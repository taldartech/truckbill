{{-- SUPER ADMIN DASHBOARD --}}
<div class="row mb-3">
    <div class="col-12">
        <h2 class="mb-0"><i class="fas fa-user-shield text-brand-primary"></i> Super Admin Dashboard</h2>
        <p class="text-muted">Manage your companies and users</p>
    </div>
</div>

<!-- Admin Statistics -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-primary">
                <i class="fas fa-building"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Companies</span>
                <span class="info-box-number">{{ number_format($adminStats['total_companies']) }}</span>
                <small>Your Companies</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-secondary">
                <i class="fas fa-truck"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Bookings</span>
                <span class="info-box-number">{{ number_format($adminStats['total_bookings']) }}</span>
                <small>This Month</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-primary">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Revenue</span>
                <span class="info-box-number">₹{{ number_format($adminStats['total_revenue'], 2) }}</span>
                <small>This Month</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-warning">
                <i class="fas fa-users"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Users</span>
                <span class="info-box-number">{{ number_format($adminStats['total_users']) }}</span>
                <small>Across Companies</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Expiring Plans</span>
                <span class="info-box-number">{{ number_format($adminStats['expiring_plans']) }}</span>
                <small>Next 7 Days</small>
            </div>
        </div>
    </div>
</div>

<!-- Companies with Expiring Plans -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle text-brand-warning"></i> Companies with Expiring/Expired Plans</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Plan End Date</th>
                                <th>Status</th>
                                <th>Contact Person</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiringCompanies as $company)
                                <tr>
                                    <td>{{ $company->name }}</td>
                                    <td>{{ $company->plan_end_date }}</td>
                                    <td>
                                        @php
                                            $expiryDate = \Carbon\Carbon::parse($company->plan_end_date);
                                            $today = \Carbon\Carbon::today();
                                        @endphp
                                        @if($expiryDate->isPast())
                                            <span class="badge bg-danger">Expired</span>
                                        @elseif($expiryDate->isSameDay($today))
                                            <span class="badge bg-warning">Expires Today</span>
                                        @else
                                            <span class="badge bg-info">Expires Soon</span>
                                        @endif
                                    </td>
                                    <td>{{ $company->contact_person ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No companies with expiring plans</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue by Company -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line text-brand-primary"></i> Revenue by Company</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th class="text-end">Monthly Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenueByCompany as $company)
                                <tr>
                                    <td>{{ $company->name }}</td>
                                    <td class="text-end">₹{{ number_format($company->monthly_revenue ?? 0, 2) }}</td>
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

    <!-- Recent Bookings -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list text-brand-secondary"></i> Recent Bookings</h3>
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
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBookings as $booking)
                                <tr>
                                    <td>#{{ $booking->id }}</td>
                                    <td>{{ $booking->lr->consignor->name ?? 'N/A' }}</td>
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
                                        <a href="{{ route('freight_bookings.show', $booking->id) }}" class="btn btn-sm btn-view">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No recent bookings</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

