@php
    $period = $insights['period'];
    $receivables = $insights['receivables'];
    $unbilled = $insights['unbilled'];
    $payables = $insights['payables'];
    $margin = $insights['margin'];
    $growth = $insights['growth'];
@endphp

@include('dashboard.partials.hero', [
    'subtitle' => 'Health of all your companies for '.strtolower($period->label).', compared '.$period->comparisonLabel.'.',
    'quickActions' => [],
    'showPeriodFilter' => true,
    'dashboardPeriod' => $period,
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
                                    <span class="btn btn-danger w-100 text-start py-2 px-3 disabled opacity-75 company-enter-tile" title="{{ $company->name }}">
                                        <span class="company-enter-tile__inner">
                                            <i class="fas fa-building me-2 flex-shrink-0"></i>
                                            <span class="company-enter-tile__name fw-semibold">{{ $company->name }}</span>
                                        </span>
                                    </span>
                                @else
                                    <a href="{{ route('company.enter', $company) }}" class="btn btn-primary w-100 text-start py-2 px-3 company-enter-tile" title="{{ $company->name }}">
                                        <span class="company-enter-tile__inner">
                                            <i class="fas fa-building me-2 flex-shrink-0"></i>
                                            <span class="company-enter-tile__name fw-semibold">{{ $company->name }}</span>
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

@include('dashboard.partials.action-center', ['actions' => $actions, 'title' => 'Across your companies'])

<div class="dashboard-kpis">
    @include('dashboard.partials.kpi-card', [
        'title' => 'Booked revenue',
        'value' => formatRupeesCompact($margin['revenue']),
        'icon' => 'bi bi-currency-rupee',
        'tone' => 'primary',
        'delta' => $growth['revenue_change'],
        'comparison' => $period->comparisonLabel,
        'hint' => $margin['trips'].' '.Str::plural('trip', $margin['trips']),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Gross profit',
        'value' => formatRupeesCompact($margin['profit']),
        'icon' => 'bi bi-graph-up-arrow',
        'tone' => $margin['profit'] < 0 ? 'danger' : 'success',
        'delta' => $growth['profit_change'],
        'comparison' => $period->comparisonLabel,
        'hint' => $margin['margin'].'% margin',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Cash collected',
        'value' => formatRupeesCompact($receivables['collected']),
        'icon' => 'bi bi-piggy-bank',
        'tone' => 'info',
        'delta' => $receivables['collected_change'],
        'comparison' => $period->comparisonLabel,
        'hint' => formatRupeesCompact($payables['paid_out']).' paid to transporters',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Overdue receivables',
        'value' => formatRupeesCompact($receivables['overdue']),
        'icon' => 'bi bi-exclamation-octagon',
        'tone' => $receivables['overdue'] > 0 ? 'danger' : 'success',
        'hint' => $receivables['overdue_share'].'% of '.formatRupeesCompact($receivables['outstanding']).' outstanding',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Delivered, not invoiced',
        'value' => formatRupeesCompact($unbilled['ready_value'] + $unbilled['pod_pending_value']),
        'icon' => 'bi bi-receipt-cutoff',
        'tone' => 'warning',
        'hint' => $unbilled['ready_count'].' ready · '.$unbilled['pod_pending_count'].' awaiting memo',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Transporter dues now',
        'value' => formatRupeesCompact($payables['due_now']),
        'icon' => 'bi bi-wallet2',
        'tone' => 'warning',
        'hint' => $payables['due_now_trips'].' delivered '.Str::plural('trip', $payables['due_now_trips']),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Loss-making trips',
        'value' => number_format($margin['loss_trips']),
        'icon' => 'bi bi-graph-down-arrow',
        'tone' => $margin['loss_trips'] > 0 ? 'danger' : 'success',
        'hint' => $margin['loss_trips'] > 0 ? formatRupeesCompact($margin['loss_amount']).' lost' : 'Every trip made money',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Companies',
        'value' => number_format($ownedCompanies->count()),
        'icon' => 'bi bi-buildings',
        'tone' => 'info',
        'hint' => collect($scorecards)->filter(fn (array $card): bool => count($card['flags']) > 0)->count().' need attention',
        'href' => route('company.index'),
    ])
</div>

<section class="dashboard-panel">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-clipboard-data"></i> Company scorecards</h2>
        <span class="dashboard-panel__aside">Companies that need attention are listed first</span>
    </div>
    <div class="dashboard-panel__body dashboard-panel__body--flush">
        @if (count($scorecards) === 0)
            <div class="dashboard-empty">You have no companies yet. <a href="{{ route('company.create') }}">Create one</a>.</div>
        @else
            <div class="table-responsive">
                <table class="table dashboard-table mb-0">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Needs attention</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Profit</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end">Overdue</th>
                            <th class="text-end">Unbilled</th>
                            <th class="text-end">Dues now</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($scorecards as $card)
                            <tr>
                                <td><div class="fw-semibold text-truncate" style="max-width: 220px;" title="{{ $card['name'] }}">{{ $card['name'] }}</div></td>
                                <td>
                                    <div class="dashboard-pills">
                                        @forelse ($card['flags'] as $flag)
                                            <span class="dashboard-pill dashboard-pill--{{ $flag['tone'] }}">{{ $flag['label'] }}</span>
                                        @empty
                                            <span class="dashboard-pill dashboard-pill--success">Healthy</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="text-end text-nowrap">{{ formatRupeesCompact($card['revenue']) }}</td>
                                <td class="text-end text-nowrap {{ $card['profit'] < 0 ? 'text-danger' : '' }}">
                                    {{ formatRupeesCompact($card['profit']) }}
                                    @if ($card['trips'] > 0)
                                        <span class="text-muted small">({{ $card['margin'] }}%)</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">{{ formatRupeesCompact($card['outstanding']) }}</td>
                                <td class="text-end text-nowrap {{ $card['overdue'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ formatRupeesCompact($card['overdue']) }}</td>
                                <td class="text-end text-nowrap">{{ formatRupeesCompact($card['unbilled']) }}</td>
                                <td class="text-end text-nowrap">{{ formatRupeesCompact($card['payable_due']) }}</td>
                                <td class="text-end text-nowrap">
                                    @unless ($card['is_blocked'])
                                        <a href="{{ route('company.enter', $card['id']) }}" class="dashboard-link">Open <i class="bi bi-arrow-right-short"></i></a>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<div class="dashboard-grid dashboard-grid--2">
    @include('dashboard.partials.receivables-panel')
    @include('dashboard.partials.payables-panel')
</div>

@include('dashboard.partials.profitability-panel')

@include('dashboard.partials.growth-panel')

<script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>

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
