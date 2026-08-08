@include('dashboard.partials.hero', [
    'subtitle' => 'Company management and multi-company revenue overview.',
    'quickActions' => [],
    'showPeriodFilter' => false,
])

@if (! session('super_admin_company_id') && isset($ownedCompanies) && $ownedCompanies->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; border-left: 4px solid var(--bs-primary, #2FB36F) !important;">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="fas fa-door-open text-brand-primary me-2"></i>Enter Company Panel</h5>
                            <p class="text-muted small mb-0">Click a company to open its panel instantly.</p>
                        </div>
                        @if ($ownedCompanies->count() > 6)
                            <div style="min-width: 220px; max-width: 320px; flex: 1;">
                                <input type="search" id="super_admin_company_enter_filter" class="form-control form-control-sm" placeholder="Filter companies…" autocomplete="off">
                            </div>
                        @endif
                    </div>
                    <div class="row g-2" id="super_admin_company_enter_grid">
                        @foreach ($ownedCompanies as $company)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3 company-enter-item" data-company-name="{{ strtolower($company->name) }}">
                                @if ($company->is_blocked)
                                    <span class="btn btn-danger w-100 text-start py-2 px-3 disabled opacity-75" title="This company is blocked">
                                        <span class="d-flex align-items-center">
                                            <i class="fas fa-building me-2"></i>
                                            <span class="text-truncate fw-semibold">{{ $company->name }}</span>
                                        </span>
                                    </span>
                                @else
                                    <a href="{{ route('company.enter', $company) }}" class="btn btn-primary w-100 text-start py-2 px-3 company-enter-tile">
                                        <span class="d-flex align-items-center">
                                            <i class="fas fa-building me-2"></i>
                                            <span class="text-truncate fw-semibold">{{ $company->name }}</span>
                                        </span>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div id="super_admin_company_enter_empty" class="text-muted small py-2 d-none">No companies match your search.</div>
                    <div class="mt-3 text-end">
                        <a href="{{ route('company.index') }}" class="small text-muted">Manage all companies</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- SuperAdmin KPI Cards -->
<div class="row g-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card glass-card info-box">
            <div class="card-body p-0 d-flex align-items-center">
                <div class="icon-circle bg-soft-success me-3 shadow-sm">
                    <i class="fa-solid fa-indian-rupee-sign text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-semi-bold mb-1">Monthly Revenue</h6>
                    <h3 class="fw-bold mb-1 text-truncate">{{ number_format($adminStats['monthly_revenue'], 2) }}</h3>
                    <span class="badge bg-soft-success text-success rounded-pill">Total Revenue</span>
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
                    <h3 class="fw-bold mb-1">{{ number_format($adminStats['total_companies']) }}</h3>
                    <span class="badge bg-soft-primary text-primary rounded-pill">Registered</span>
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
                    <h3 class="fw-bold mb-1">{{ number_format($adminStats['active_companies']) }}</h3>
                    <span class="badge bg-soft-info text-info rounded-pill">Live Now</span>
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
                    <h6 class="text-muted fw-semi-bold mb-1">Expired Plans</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($adminStats['expired_plans']) }}</h3>
                    <span class="badge bg-soft-danger text-danger rounded-pill">Action Required</span>
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
                    <h6 class="text-muted fw-semi-bold mb-1">Upcoming Renewals</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($adminStats['upcoming_renewals']) }}</h3>
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
                    <h6 class="text-muted fw-semi-bold mb-1">Total Users</h6>
                    <h3 class="fw-bold mb-1">{{ number_format($adminStats['total_users']) }}</h3>
                    <span class="badge bg-soft-secondary text-muted text-dark rounded-pill">Across Platform</span>
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
                    <h3 class="fw-bold mb-1">{{ number_format($adminStats['outstanding_amount'], 2) }}</h3>
                    <span class="badge bg-soft-warning text-warning rounded-pill">Invoice Balance</span>
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
                    <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Expiring/Expired Plans (Urgent)</h5>
                    <span class="badge bg-danger text-white px-3 rounded-pill">{{ $expiringCompanies->count() }} Urgent</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Company Name</th>
                                <th>Plan End Date</th>
                                <th>Status</th>
                                <th class="pe-4">Contact Person</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringCompanies->take(10) as $company)
                                @php
                                    $expiryDate = \Carbon\Carbon::parse($company->subscription?->ends_on);
                                    $today = \Carbon\Carbon::today();
                                @endphp
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">{{ $company->name }}</td>
                                    <td>{{ $expiryDate->format('d M, Y') }}</td>
                                    <td>
                                        @if($expiryDate->isPast())
                                            <span class="badge bg-soft-danger text-danger px-3 rounded-pill">Expired</span>
                                        @elseif($expiryDate->isSameDay($today))
                                            <span class="badge bg-soft-warning text-warning px-3 rounded-pill">Expires Today</span>
                                        @else
                                            <span class="badge bg-soft-info text-info px-3 rounded-pill">Expires Soon</span>
                                        @endif
                                    </td>
                                    <td class="pe-4">{{ $company->contact_person ?? 'N/A' }}</td>
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

<!-- Priority 2: Upcoming Plan Expiry List -->
@if(isset($upcomingPlanExpiry) && $upcomingPlanExpiry->count() > 0)
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-soft-warning text-warning border-0 py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i> Upcoming Plan Expiry (Next 30 Days)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Company Name</th>
                                <th>Plan End Date</th>
                                <th>Time Remaining</th>
                                <th>Status</th>
                                <th class="pe-4">Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingPlanExpiry as $company)
                                @php
                                    $expiryDate = \Carbon\Carbon::parse($company->subscription?->ends_on);
                                    $today = \Carbon\Carbon::today();
                                    $daysRemaining = $today->diffInDays($expiryDate, false);

                                    if ($daysRemaining <= 1) {
                                        $badgeClass = 'bg-soft-warning text-warning';
                                        $statusText = 'Expires Tomorrow';
                                    } elseif ($daysRemaining <= 7) {
                                        $badgeClass = 'bg-soft-info text-info';
                                        $statusText = 'Final Week';
                                    } else {
                                        $badgeClass = 'bg-soft-success text-success';
                                        $statusText = 'Active';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4 fw-medium text-dark">{{ $company->name }}</td>
                                    <td>{{ $expiryDate->format('d M Y') }}</td>
                                    <td>
                                        @if($daysRemaining == 0)
                                            <span class="text-danger fw-bold">Today</span>
                                        @elseif($daysRemaining == 1)
                                            <span class="text-warning fw-bold">1 Day</span>
                                        @else
                                            <span class="text-info fw-semi-bold">{{ $daysRemaining }} Days</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }} px-3 rounded-pill">{{ $statusText }}</span>
                                    </td>
                                    <td class="pe-4">{{ $company->contact_person ?? '-' }}</td>
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

<!-- Priority 3: Revenue by Company -->
@if(isset($revenueByCompany) && $revenueByCompany->count() > 0)
<div class="row mt-4 mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 py-3 mt-1">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-brand-primary me-2"></i> Revenue Overview (Top 10)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-soft-secondary">
                            <tr>
                                <th class="ps-4">Company Name</th>
                                <th class="text-end pe-4">Monthly Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($revenueByCompany->take(10) as $company)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ Str::limit($company->name, 40) }}</td>
                                    <td class="text-end pe-4 fw-bold text-success">{{ number_format($company->monthly_revenue ?? 0, 2) }}</td>
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

@push('scripts')
    @if (! session('super_admin_company_id') && isset($ownedCompanies) && $ownedCompanies->count() > 6)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const filter = document.getElementById('super_admin_company_enter_filter');
                const items = document.querySelectorAll('.company-enter-item');
                const empty = document.getElementById('super_admin_company_enter_empty');
                if (!filter || !items.length) {
                    return;
                }

                filter.addEventListener('input', function () {
                    const term = filter.value.trim().toLowerCase();
                    let visible = 0;

                    items.forEach(function (item) {
                        const name = item.getAttribute('data-company-name') || '';
                        const show = term === '' || name.includes(term);
                        item.classList.toggle('d-none', !show);
                        if (show) {
                            visible++;
                        }
                    });

                    if (empty) {
                        empty.classList.toggle('d-none', visible > 0);
                    }
                });
            });
        </script>
    @endif
@endpush

