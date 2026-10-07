@use('App\Services\Dashboard\PlatformInsightsService')

@php
    $renewals = collect($platform['expired'])->concat($platform['expiring_soon']);
    $churnRisk = collect($platform['inactive'])->concat($platform['slowing_down'])->take(PlatformInsightsService::LIST_SIZE);
    $maxCompanyLrs = collect($platform['top_companies'])->max('lrs_30d') ?: 1;
@endphp

@include('dashboard.partials.hero', [
    'subtitle' => 'Renewals, churn risk and platform usage.',
    'quickActions' => [
        ['label' => 'Companies', 'icon' => 'bi bi-buildings', 'url' => route('company.index')],
        ['label' => 'Usage Summary', 'icon' => 'bi bi-bar-chart', 'url' => route('master.summary-dashboard')],
    ],
    'showPeriodFilter' => false,
])

@include('dashboard.partials.action-center', ['actions' => $platform['actions'], 'title' => 'Revenue at risk'])

<div class="dashboard-kpis">
    @include('dashboard.partials.kpi-card', [
        'title' => 'Subscription revenue',
        'value' => formatRupeesCompact($platform['subscription_revenue']),
        'icon' => 'bi bi-currency-rupee',
        'tone' => 'primary',
        'delta' => $platform['subscription_revenue_change'],
        'comparison' => 'vs same days last month',
        'hint' => 'This month',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Paying companies',
        'value' => number_format($platform['companies_paying']),
        'icon' => 'bi bi-patch-check',
        'tone' => 'success',
        'hint' => $platform['companies_trial'].' on trial · '.$platform['signups_this_month'].' new this month',
        'href' => route('company.index'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Actively using',
        'value' => number_format($platform['companies_active_usage']).' / '.number_format($platform['companies_live']),
        'icon' => 'bi bi-activity',
        'tone' => 'info',
        'hint' => 'Created a bilty in the last '.PlatformInsightsService::USAGE_WINDOW_DAYS.' days',
        'href' => route('master.summary-dashboard'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Bilties (30 days)',
        'value' => number_format($platform['lrs_30d']),
        'icon' => 'bi bi-file-earmark-text',
        'tone' => 'info',
        'delta' => $platform['lrs_change'],
        'comparison' => 'vs previous 30 days',
        'hint' => formatRupeesCompact($platform['gmv_30d']).' freight handled',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Renewals due (30 days)',
        'value' => number_format(count($platform['expiring_soon'])),
        'icon' => 'bi bi-calendar-event',
        'tone' => $platform['expiring_urgent_count'] > 0 ? 'warning' : 'info',
        'hint' => $platform['expiring_urgent_count'].' within '.PlatformInsightsService::RENEWAL_URGENT_DAYS.' days',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Expired, not renewed',
        'value' => number_format(count($platform['expired'])),
        'icon' => 'bi bi-x-octagon',
        'tone' => count($platform['expired']) > 0 ? 'danger' : 'success',
        'hint' => 'Win-back list',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Low on LR credits',
        'value' => number_format(count($platform['low_credit'])),
        'icon' => 'bi bi-battery-half',
        'tone' => count($platform['low_credit']) > 0 ? 'warning' : 'success',
        'hint' => 'Upsell opportunity',
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Churn risk',
        'value' => number_format($churnRisk->count()),
        'icon' => 'bi bi-moon',
        'tone' => $churnRisk->isNotEmpty() ? 'warning' : 'success',
        'hint' => 'Inactive or usage halved',
    ])
</div>

<section class="dashboard-panel">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-arrow-repeat"></i> Renewal pipeline</h2>
        <span class="dashboard-panel__aside">Expired or ending within {{ PlatformInsightsService::RENEWAL_WINDOW_DAYS }} days, with no renewal booked</span>
    </div>
    <div class="dashboard-panel__body dashboard-panel__body--flush">
        @if ($renewals->isEmpty())
            <div class="dashboard-empty">No renewals pending in the next {{ PlatformInsightsService::RENEWAL_WINDOW_DAYS }} days.</div>
        @else
            <div class="table-responsive">
                <table class="table dashboard-table mb-0">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Plan</th>
                            <th>Ends on</th>
                            <th>Status</th>
                            <th class="text-end">Bilties (30d)</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($renewals as $row)
                            <tr>
                                <td><div class="fw-semibold text-truncate" style="max-width: 220px;" title="{{ $row['name'] }}">{{ $row['name'] }}</div></td>
                                <td class="text-nowrap text-capitalize">{{ $row['plan_status'] ?? '—' }}</td>
                                <td class="text-nowrap">{{ formatDate($row['ends_on']) }}</td>
                                <td class="text-nowrap">
                                    @if ($row['days_to_expiry'] < 0)
                                        <span class="dashboard-pill dashboard-pill--danger">Expired {{ abs($row['days_to_expiry']) }}d ago</span>
                                    @elseif ($row['days_to_expiry'] === 0)
                                        <span class="dashboard-pill dashboard-pill--danger">Ends today</span>
                                    @elseif ($row['days_to_expiry'] <= PlatformInsightsService::RENEWAL_URGENT_DAYS)
                                        <span class="dashboard-pill dashboard-pill--warning">{{ $row['days_to_expiry'] }} days left</span>
                                    @else
                                        <span class="dashboard-pill dashboard-pill--info">{{ $row['days_to_expiry'] }} days left</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($row['lrs_30d']) }}</td>
                                <td class="text-nowrap">{{ $row['contact'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<div class="dashboard-grid dashboard-grid--2">
    <section class="dashboard-panel h-100">
        <div class="dashboard-panel__header">
            <h2 class="dashboard-panel__title"><i class="bi bi-moon"></i> Churn risk</h2>
            <span class="dashboard-panel__aside">Active plan, little or no recent usage</span>
        </div>
        <div class="dashboard-panel__body">
            @include('dashboard.partials.ranked-list', [
                'rows' => $churnRisk->map(fn (array $row): array => [
                    'label' => $row['name'],
                    'value' => $row['days_since_last_lr'] === null ? 'Never used' : $row['days_since_last_lr'].'d idle',
                    'meta' => $row['lrs_30d'].' bilties in last 30 days vs '.$row['lrs_prev_30d'].' before'.($row['ends_on'] ? ' · plan ends '.formatDate($row['ends_on']) : ''),
                    'tone' => $row['days_since_last_lr'] === null || $row['days_since_last_lr'] >= 30 ? 'danger' : 'warning',
                ])->values()->all(),
                'emptyText' => 'Every paying company is actively using the platform.',
            ])
        </div>
    </section>

    <section class="dashboard-panel h-100">
        <div class="dashboard-panel__header">
            <h2 class="dashboard-panel__title"><i class="bi bi-battery-half"></i> Running out of LR credits</h2>
            <span class="dashboard-panel__aside">Offer an add-on pack</span>
        </div>
        <div class="dashboard-panel__body">
            @include('dashboard.partials.ranked-list', [
                'rows' => collect($platform['low_credit'])->take(PlatformInsightsService::LIST_SIZE)->map(fn (array $row): array => [
                    'label' => $row['name'],
                    'value' => number_format($row['credits_remaining']).' left',
                    'meta' => 'of '.number_format($row['credit_pool']).' · '.$row['lrs_30d'].' bilties in last 30 days',
                    'percent' => $row['credit_pool'] > 0 ? $row['credits_remaining'] / $row['credit_pool'] * 100 : 0,
                    'tone' => $row['credits_remaining'] <= 0 ? 'danger' : 'warning',
                ])->all(),
                'emptyText' => 'No company is close to running out of credits.',
            ])
        </div>
    </section>
</div>

<div class="dashboard-grid dashboard-grid--2">
    <section class="dashboard-panel h-100">
        <div class="dashboard-panel__header">
            <h2 class="dashboard-panel__title"><i class="bi bi-bar-chart-line"></i> Platform trend</h2>
            <span class="dashboard-panel__aside">Last 6 months</span>
        </div>
        <div class="dashboard-panel__body">
            <div class="dashboard-chart">
                <canvas id="dashPlatformTrendChart" aria-label="Subscription revenue, bilties and signups per month"></canvas>
            </div>
        </div>
    </section>

    <section class="dashboard-panel h-100">
        <div class="dashboard-panel__header">
            <h2 class="dashboard-panel__title"><i class="bi bi-trophy"></i> Most active companies</h2>
            <span class="dashboard-panel__aside">Bilties in last {{ PlatformInsightsService::USAGE_WINDOW_DAYS }} days</span>
        </div>
        <div class="dashboard-panel__body">
            @include('dashboard.partials.ranked-list', [
                'rows' => collect($platform['top_companies'])->map(fn (array $row): array => [
                    'label' => $row['name'],
                    'value' => number_format($row['lrs_30d']),
                    'meta' => formatRupeesCompact($row['gmv_30d']).' freight'.($row['ends_on'] ? ' · plan ends '.formatDate($row['ends_on']) : ''),
                    'percent' => $row['lrs_30d'] / $maxCompanyLrs * 100,
                    'tone' => 'success',
                ])->all(),
                'emptyText' => 'No bilties created in the last 30 days.',
            ])
        </div>
    </section>
</div>

<script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
