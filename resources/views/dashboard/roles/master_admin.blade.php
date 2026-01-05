{{-- MASTER ADMIN DASHBOARD --}}
<div class="row mb-3">
    <div class="col-12">
        <h2 class="mb-0"><i class="fas fa-crown text-brand-warning"></i> Master Admin Dashboard</h2>
        <p class="text-muted">System-wide overview and platform metrics</p>
    </div>
</div>

<!-- System Statistics -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-secondary">
                <i class="fas fa-building"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Companies</span>
                <span class="info-box-number">{{ number_format($systemStats['total_companies']) }}</span>
                <small>All Companies</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-primary">
                <i class="fas fa-check-circle"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Active Companies</span>
                <span class="info-box-number">{{ number_format($systemStats['active_companies']) }}</span>
                <small>Currently Active</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-danger">
                <i class="fas fa-ban"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Blocked Companies</span>
                <span class="info-box-number">{{ number_format($systemStats['blocked_companies']) }}</span>
                <small>Blocked</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-secondary">
                <i class="fas fa-users"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Users</span>
                <span class="info-box-number">{{ number_format($systemStats['total_users']) }}</span>
                <small>All Users</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-secondary">
                <i class="fas fa-truck"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Bookings</span>
                <span class="info-box-number">{{ number_format($systemStats['total_bookings']) }}</span>
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
                <span class="info-box-number">₹{{ number_format($systemStats['total_revenue'], 2) }}</span>
                <small>This Month</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-brand-warning">
                <i class="fas fa-exclamation-triangle"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Expiring Plans</span>
                <span class="info-box-number">{{ number_format($systemStats['expiring_plans']) }}</span>
                <small>Next 7 Days</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-danger">
                <i class="fas fa-times-circle"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Expired Plans</span>
                <span class="info-box-number">{{ number_format($systemStats['expired_plans']) }}</span>
                <small>Require Attention</small>
            </div>
        </div>
    </div>
</div>

<!-- Expiring Companies -->
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

<!-- Top Companies by Revenue -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line text-brand-primary"></i> Top Companies by Revenue</h3>
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
                            @forelse($topCompaniesByRevenue as $company)
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

    <!-- Recent Companies -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building text-brand-secondary"></i> Recent Companies</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Created Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCompanies as $company)
                                <tr>
                                    <td>{{ $company->name }}</td>
                                    <td>{{ $company->created_at->format('d M Y') }}</td>
                                    <td>
                                        @if($company->is_blocked)
                                            <span class="badge bg-danger">Blocked</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No recent companies</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

