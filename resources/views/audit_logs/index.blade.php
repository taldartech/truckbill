@extends('layouts.app')

@section('title', 'Audit log')

@push('styles')
    <style>
        .audit-page .app-index-list-card .audit-inner-card {
            border: 1px solid #e6edf7;
            border-radius: 12px;
            box-shadow: none;
        }

        .audit-subtitle {
            color: #6b7280;
            font-size: 0.88rem;
        }

        .audit-filter-card .form-label {
            font-size: 0.8rem;
            color: #475569;
            margin-bottom: 0.25rem;
        }

        .audit-filter-card .form-control,
        .audit-filter-card .form-select {
            border-radius: 10px;
            border: 1px solid #dbe3ef;
            height: 38px;
            font-size: 0.9rem;
        }

        .audit-stat-card {
            border-radius: 12px;
            color: #fff;
            padding: 14px 16px;
            min-height: 98px;
        }

        .audit-stat-title {
            font-size: 0.78rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .audit-stat-value {
            font-size: 1.5rem;
            line-height: 1.2;
            font-weight: 700;
            margin-top: 2px;
            margin-bottom: 2px;
        }

        .audit-timeline-wrap {
            padding: 10px 16px 20px 16px;
        }

        .audit-timeline {
            position: relative;
            margin-left: 8px;
            padding-left: 30px;
        }

        .audit-timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 9px;
            width: 2px;
            background: #dbe3ef;
        }

        .audit-item {
            position: relative;
            margin-bottom: 12px;
            border: 1px solid #e6edf7;
            border-radius: 12px;
            background: #fff;
            padding: 14px;
        }

        .audit-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 22px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2563eb;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #dbe3ef;
        }

        .audit-item-title {
            font-weight: 600;
            color: #0f172a;
        }

        .audit-meta {
            font-size: 0.82rem;
            color: #64748b;
        }

        .audit-changes-table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            background: #f8fafc;
        }

        .audit-changes-table td {
            font-size: 0.86rem;
        }
    </style>
@endpush

@section('content')
    <div class="content audit-page">
        <div class="container-fluid mt-4 app-index-page app-index-page--standard">
            <div class="card app-index-list-card mb-4">
                <div class="card-header">
                    <div>
                        <h3 class="card-title mb-0">Audit Log</h3>
                    </div>
                </div>

                <div class="app-index-dt-toolbar audit-filter-card">
                    <p class="audit-subtitle mb-2">Detailed timeline of system changes. Filter and inspect each event with old and new values.</p>
                    @if (! empty($auditScopeAllCompanies))
                        <p class="small text-muted mb-2">
                            <i class="far fa-building me-1"></i>
                            <span class="text-dark">All your companies</span>
                            <span class="badge bg-light text-dark border ms-1">combined view</span>
                        </p>
                    @elseif (! empty($currentCompany))
                        <p class="small text-muted mb-2">
                            <i class="far fa-building me-1"></i>
                            <span class="text-dark">{{ $currentCompany->name }}</span>
                            @if (! empty($currentCompany->short_name))
                                <span class="badge bg-light text-dark border ms-1">{{ $currentCompany->short_name }}</span>
                            @endif
                        </p>
                    @endif
                    <form method="get" action="{{ route('audit-logs.index') }}" class="row g-3 align-items-end">
                        @if ($showCompanyPicker)
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Company</label>
                                <select name="company_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">— All companies —</option>
                                    @foreach ($ownedCompanies as $c)
                                        <option value="{{ $c->id }}" {{ (string) ($auditScopeCompanyId ?? '') === (string) $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}@if (! empty($c->short_name)) ({{ $c->short_name }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Module</label>
                            <select name="subject_type" class="form-select">
                                <option value="">All modules</option>
                                @foreach ($subjectTypeOptions as $class => $label)
                                    <option value="{{ $class }}" {{ ($filters['subject_type'] ?? '') === $class ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">User</label>
                            <select name="user_id" class="form-select">
                                <option value="">All users</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">LR Number</label>
                            <input type="text" name="lr_number" class="form-control" placeholder="Ex: LR-1024" value="{{ $filters['lr_number'] ?? '' }}">
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" name="q" class="form-control" placeholder="Search by summary or reference" value="{{ $filters['q'] ?? '' }}">
                        </div>
                        <div class="col-lg-2 col-md-6 d-grid">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                        <div class="col-lg-2 col-md-6 d-grid">
                            <a href="{{ route('audit-logs.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="row g-3 p-3 mb-0">
                        <div class="col-lg-3 col-md-6">
                            <div class="audit-stat-card" style="background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%);">
                                <div class="audit-stat-title">Total Events</div>
                                <div class="audit-stat-value">{{ $insights['total_events'] }}</div>
                                <div class="small">Audit entries after filters</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="audit-stat-card" style="background: linear-gradient(135deg, #16a34a 0%, #059669 100%);">
                                <div class="audit-stat-title">Last 7 Days</div>
                                <div class="audit-stat-value">{{ $insights['last_7_days'] }}</div>
                                <div class="small">Recent change activity</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="audit-stat-card" style="background: linear-gradient(135deg, {{ $insights['trend_percent'] >= 0 ? '#f59e0b 0%, #f97316 100%' : '#ef4444 0%, #dc2626 100%' }});">
                                <div class="audit-stat-title">Trend</div>
                                <div class="audit-stat-value">{{ $insights['trend_percent'] >= 0 ? '+' : '' }}{{ $insights['trend_percent'] }}%</div>
                                <div class="small">Vs previous 7 days</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="audit-stat-card" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                                <div class="audit-stat-title">Updated Events</div>
                                <div class="audit-stat-value">{{ $insights['action_stats']->get('updated', 0) }}</div>
                                <div class="small">Records modified</div>
                            </div>
                        </div>
                    </div>

                    <div class="px-3 pb-2">
                        <h5 class="mb-1" style="font-size: 1rem;">Timeline View</h5>
                        <p class="audit-subtitle mb-0">Newest activity on top. Expand each event to see old and new values.</p>
                    </div>
                    <div class="audit-timeline-wrap">
                        @if ($ownedCompanies->isEmpty())
                            <div class="alert alert-warning border rounded-3 mb-3">
                                <strong>No companies found</strong> for your account. Create one under
                                <a href="{{ route('company.index') }}" class="alert-link">Company Management</a>.
                            </div>
                        @elseif (! $hasActiveFilters)
                            <div class="alert alert-light border rounded-3 mb-3">
                                <strong>No data by default.</strong> Apply filters (LR Number / date / user / module) to load timeline.
                            </div>
                        @endif

                        <div class="audit-timeline">
                            @forelse ($logs as $log)
                                @php
                                    $action = $log->action;
                                    $badgeClass = $action === 'created' ? 'bg-success' : ($action === 'updated' ? 'bg-info' : ($action === 'deleted' ? 'bg-danger' : 'bg-secondary'));
                                @endphp
                                <div class="audit-item">
                                    <div class="d-flex flex-wrap align-items-start gap-2 mb-2">
                                        <span class="badge {{ $badgeClass }} text-uppercase">{{ $action }}</span>
                                        <span class="badge bg-light text-dark border">{{ $log->subjectTypeDisplayName() }}</span>
                                    </div>

                                    <div class="audit-item-title mb-1">{{ $log->displaySummary() }}</div>

                                    <div class="audit-meta mb-2">
                                        <i class="far fa-clock me-1"></i>{{ $log->occurred_at->timezone(config('app.timezone'))->format('d M Y, h:i:s A') }}
                                        @if ($log->company && ! empty($log->company->short_name))
                                            <span class="mx-2">|</span>
                                            <span class="badge bg-light text-dark border">{{ $log->company->short_name }}</span>
                                        @endif
                                        <span class="mx-2">|</span>
                                        <i class="far fa-user me-1"></i>
                                        @if ($log->user)
                                            {{ $log->user->name }}
                                            <span class="text-muted">({{ $log->user->email }})</span>
                                        @else
                                            System
                                        @endif
                                    </div>

                                    @if ($log->fieldChanges->isNotEmpty())
                                        <details>
                                            <summary class="text-primary" style="cursor: pointer;">View field changes</summary>
                                            <div class="table-responsive mt-2">
                                                <table class="table table-sm table-bordered audit-changes-table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th width="30%">Field</th>
                                                            <th width="35%">Before</th>
                                                            <th width="35%">After</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($log->fieldChanges as $row)
                                                            <tr>
                                                                <td>{{ $row->field_label ?: $row->attribute_name }}</td>
                                                                <td>{{ $row->old_value ?? '—' }}</td>
                                                                <td>{{ $row->new_value ?? '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    @if ($ownedCompanies->isEmpty())
                                        Add a company first, then apply filters.
                                    @else
                                        {{ $hasActiveFilters ? 'No activity found for selected filters.' : 'Apply filters to view the timeline.' }}
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </div>
                    @if ($logs->hasPages())
                        <div class="app-index-partload-pagination">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
