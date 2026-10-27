@extends('layouts.app')

@section('title', 'Summary Dashboard')

@section('content')
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold mb-1"><i class="fas fa-chart-pie text-brand-primary"></i> Summary Dashboard</h2>
            <p class="text-muted mb-0">Platform-wide financial and operational summary with filters.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="badge bg-brand-active text-brand-primary px-3 py-2 rounded-pill">
                <i class="fas fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($filters['from_date'])->format('d M, Y') }}
                – {{ \Carbon\Carbon::parse($filters['to_date'])->format('d M, Y') }}
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body">
            <form method="GET" action="{{ route('master.summary-dashboard') }}" class="row g-3 align-items-end">
                <div class="col-6 col-md-3">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" name="from_date" id="from_date" class="form-control"
                        value="{{ $filters['from_date'] }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" name="to_date" id="to_date" class="form-control"
                        value="{{ $filters['to_date'] }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="super_admin_user_id" class="form-label">Super Admin</label>
                    <select name="super_admin_user_id" id="super_admin_user_id" class="form-select"
                        @if (! $isMasterAdmin) disabled @endif>
                        <option value="">All Super Admins</option>
                        @foreach ($superAdminOptions as $superAdmin)
                            <option value="{{ $superAdmin->id }}"
                                @selected($filters['super_admin_user_id'] === $superAdmin->id)>
                                {{ $superAdmin->name }} ({{ $superAdmin->email }})
                            </option>
                        @endforeach
                    </select>
                    @if (! $isMasterAdmin)
                        <input type="hidden" name="super_admin_user_id" value="{{ auth()->id() }}">
                    @endif
                </div>
                <div class="col-6 col-md-3">
                    <label for="company_id" class="form-label">Company</label>
                    <select name="company_id" id="company_id" class="form-select">
                        <option value="">All Companies</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company->id }}" @selected($filters['company_id'] === $company->id)>
                                {{ $company->name }}@if ($company->is_blocked) (Blocked) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <a href="{{ route('master.summary-dashboard') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                    <button type="submit" class="btn btn-brand-primary">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-success me-3 shadow-sm">
                        <i class="fa-solid fa-indian-rupee-sign text-success"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">Revenue</h6>
                        <h3 class="fw-bold mb-1 text-truncate">{{ number_format($kpis['revenue'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-primary me-3 shadow-sm">
                        <i class="fas fa-file-invoice-dollar text-primary"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">Invoiced</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['total_invoiced'], 2) }}</h3>
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
                        <h6 class="text-muted fw-semi-bold mb-1">Outstanding</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['outstanding_amount'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                        <i class="fas fa-receipt text-warning"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">Voucher Paid</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['voucher_paid'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-secondary me-3 shadow-sm">
                        <i class="fas fa-file-alt text-dark"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">Total LRs</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['total_lrs']) }}</h3>
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
                        <h6 class="text-muted fw-semi-bold mb-1">Pending LRs</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['pending_lrs']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-info me-3 shadow-sm">
                        <i class="fas fa-truck text-info"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">In-Progress LRs</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['in_progress_lrs']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-success me-3 shadow-sm">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">Delivered LRs</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['delivered_lrs']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-primary me-3 shadow-sm">
                        <i class="fas fa-shipping-fast text-primary"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">Freight Bookings</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['freight_bookings']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-danger me-3 shadow-sm">
                        <i class="fas fa-route text-danger"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">Active Trips</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['active_trips']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-warning me-3 shadow-sm">
                        <i class="fas fa-file-invoice text-warning"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">LRs Pending Invoice</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['lrs_pending_invoice']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card glass-card info-box">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="icon-circle bg-soft-secondary me-3 shadow-sm">
                        <i class="fas fa-building text-dark"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semi-bold mb-1">Companies in Scope</h6>
                        <h3 class="fw-bold mb-1">{{ number_format($kpis['total_companies']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($selectedSuperAdmin)
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">Selected Super Admin</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Name:</strong> {{ $selectedSuperAdmin->name }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $selectedSuperAdmin->email }}</p>
                        <p class="mb-1"><strong>Companies:</strong> {{ $selectedSuperAdminCompanies->count() }}</p>
                        <p class="mb-0"><strong>Revenue:</strong> {{ number_format($kpis['revenue'], 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">Their Companies</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-soft-secondary">
                                    <tr>
                                        <th class="ps-4">Company</th>
                                        <th>LRs</th>
                                        <th>Delivered</th>
                                        <th class="pe-4 text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($selectedSuperAdminCompanies as $row)
                                        <tr>
                                            <td class="ps-4 fw-medium">{{ $row['name'] }}</td>
                                            <td>{{ number_format($row['total_lrs']) }}</td>
                                            <td>{{ number_format($row['delivered_lrs']) }}</td>
                                            <td class="pe-4 text-end fw-bold text-success">
                                                {{ number_format($row['revenue'], 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No companies found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-building me-2"></i> Company-wise Breakdown</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-soft-secondary">
                                <tr>
                                    <th class="ps-4">Company</th>
                                    <th>Owner</th>
                                    <th>LRs</th>
                                    <th>Pending</th>
                                    <th>Delivered</th>
                                    <th>Bookings</th>
                                    <th>Invoiced</th>
                                    <th class="pe-4 text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($companyBreakdown as $row)
                                    <tr>
                                        <td class="ps-4 fw-medium">
                                            {{ $row['name'] }}
                                            @if ($row['is_blocked'])
                                                <span class="badge bg-soft-danger text-danger ms-1">Blocked</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['owner_name'] ?? 'N/A' }}</td>
                                        <td>{{ number_format($row['total_lrs']) }}</td>
                                        <td>{{ number_format($row['pending_lrs']) }}</td>
                                        <td>{{ number_format($row['delivered_lrs']) }}</td>
                                        <td>{{ number_format($row['freight_bookings']) }}</td>
                                        <td>{{ number_format($row['total_invoiced'], 2) }}</td>
                                        <td class="pe-4 text-end fw-bold text-success">
                                            {{ number_format($row['revenue'], 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No company data for selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2"></i> Super Admin Breakdown</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-soft-secondary">
                                <tr>
                                    <th class="ps-4">Super Admin</th>
                                    <th>Companies</th>
                                    <th>LRs</th>
                                    <th>Bookings</th>
                                    <th>Invoiced</th>
                                    <th>Outstanding</th>
                                    <th class="pe-4 text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($superAdminBreakdown as $row)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-medium">{{ $row['name'] }}</div>
                                            <small class="text-muted">{{ $row['email'] }}</small>
                                        </td>
                                        <td>{{ number_format($row['company_count']) }}</td>
                                        <td>{{ number_format($row['total_lrs']) }}</td>
                                        <td>{{ number_format($row['freight_bookings']) }}</td>
                                        <td>{{ number_format($row['total_invoiced'], 2) }}</td>
                                        <td>{{ number_format($row['outstanding_amount'], 2) }}</td>
                                        <td class="pe-4 text-end fw-bold text-success">
                                            {{ number_format($row['revenue'], 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No super admin data for selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
