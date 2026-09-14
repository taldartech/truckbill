@include('dashboard.partials.hero', [
    'subtitle' => 'System-wide overview and platform health metrics.',
    'quickActions' => [],
    'showPeriodFilter' => false,
])

<!-- MasterAdmin KPI Cards -->
<div class="row g-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-success me-3 shadow-sm">
                    <i class="fa-solid fa-indian-rupee-sign text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">System Revenue</h6>
                    <h3 class="fw-bold mb-1 text-truncate">{{ number_format($systemStats['monthly_revenue'], 2) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill">Monthly Total</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-primary me-3 shadow-sm">
                    <i class="fas fa-building text-primary"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Total Companies</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($systemStats['total_companies']) }}</h3>
                    <span class="badge bg-soft-primary text-primary rounded-pill">All Registered</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-info me-3 shadow-sm">
                    <i class="fas fa-check-circle text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Active Companies</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($systemStats['active_companies']) }}</h3>
                    <span class="badge bg-soft-info text-info rounded-pill">Live Platform</span>
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
                    <h6 class="text-muted fw-semi-bold mb-1">Expired Platform</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($systemStats['expired_plans']) }}</h3>
                    <span class="badge bg-soft-danger text-danger rounded-pill">Compliance Issue</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Renewals Alert</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($systemStats['upcoming_renewals']) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Next 30 Days</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-secondary me-3 shadow-sm">
                    <i class="fas fa-users text-dark"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Global Users</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($systemStats['total_users']) }}</h3>
                    <span class="badge bg-soft-secondary text-muted text-dark rounded-pill">Platform Growth</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Priority 1: Companies with Expiring/Expired Plans (Critical) -->
@if(isset($expiringCompanies) && $expiringCompanies->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-soft-danger text-danger border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Companies with Expiring Plans (Urgent)</h5>
                    <span class="badge bg-danger text-white px-3 rounded-pill">{{ $expiringCompanies->count() }} Urgent</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4 text-nowrap">Company Name</th>
                                <th class="text-nowrap">Plan End Date</th>
                                <th class="text-nowrap">Status</th>
                                <th class="pe-4 text-nowrap">Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringCompanies->take(10) as $company)
                                @php
                                    $expiryDate = \Carbon\Carbon::parse($company->subscription?->ends_on);
                                    $today = \Carbon\Carbon::today();
                                @endphp
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><div class="text-truncate" style="max-width: 220px;" title="{{ $company->name }}">{{ $company->name }}</div></td>
                                    <td class="text-nowrap">{{ $expiryDate->format('d M, Y') }}</td>
                                    <td class="text-nowrap">
                                        @if($expiryDate->isPast())
                                            <span class="badge bg-soft-danger text-danger px-3 rounded-pill text-nowrap">Expired</span>
                                        @elseif($expiryDate->isSameDay($today))
                                            <span class="badge bg-soft-warning text-warning px-3 rounded-pill text-nowrap">Expires Today</span>
                                        @else
                                            <span class="badge bg-soft-info text-info px-3 rounded-pill text-nowrap">Expires Soon</span>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-nowrap">{{ $company->contact_person ?? 'N/A' }}</td>
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

<!-- Priority 2: Top Companies & Recent Growth -->
@if(isset($topCompaniesByRevenue) && $topCompaniesByRevenue->count() > 0)
<div class="row mt-4 mb-4 g-4">
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 py-3 mt-1">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-brand-primary me-2"></i> Top Revenue Companies</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4 text-nowrap">Company</th>
                                <th class="text-end pe-4 text-nowrap">Monthly Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topCompaniesByRevenue->take(5) as $company)
                                <tr>
                                    <td class="ps-4 fw-medium"><div class="text-truncate" style="max-width: 220px;" title="{{ $company->name }}">{{ Str::limit($company->name, 35) }}</div></td>
                                    <td class="text-end pe-4 fw-bold text-success text-nowrap">₹{{ number_format($company->monthly_revenue ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Companies -->
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 py-3 mt-1">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-building text-brand-secondary me-2"></i> Recent Onboarding</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4 text-nowrap">Company</th>
                                <th class="text-nowrap">Joined</th>
                                <th class="pe-4 text-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCompanies->take(5) as $company)
                                <tr>
                                    <td class="ps-4 fw-medium"><div class="text-truncate" style="max-width: 220px;" title="{{ $company->name }}">{{ Str::limit($company->name, 30) }}</div></td>
                                    <td class="text-nowrap">{{ $company->created_at->format('d M Y') }}</td>
                                    <td class="pe-4 text-nowrap">
                                        @if($company->is_blocked)
                                            <span class="badge bg-soft-danger text-danger px-3 rounded-pill text-nowrap">Blocked</span>
                                        @else
                                            <span class="badge bg-soft-success text-success px-3 rounded-pill text-nowrap">Active</span>
                                        @endif
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

