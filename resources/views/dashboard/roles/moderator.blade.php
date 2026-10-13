@php
    $period = $insights['period'];
    $unbilled = $insights['unbilled'];
    $margin = $insights['margin'];
    $exceptions = $insights['exceptions'];
    $growth = $insights['growth'];
    $reportRange = ['start_date' => $period->start->toDateString(), 'end_date' => $period->end->toDateString()];
    $maxReadyValue = collect($unbilled['ready_by_customer'])->max('value') ?: 1;
    $maxTransporterTrips = collect($margin['transporters'])->max('trips') ?: 1;
@endphp

@include('dashboard.partials.hero', [
    'subtitle' => 'Trips to unblock, bilties to bill and hires to fix for '.strtolower($period->label).'.',
    'quickActions' => [
        ['label' => 'New Bilty', 'icon' => 'bi bi-plus-lg', 'url' => route('lorry_receipts.create')],
        ['label' => 'New Hire', 'icon' => 'bi bi-truck', 'url' => route('lorry_receipts.index', ['delivery_status' => 'Pending'])],
        ['label' => 'Pending Memo / POD', 'icon' => 'bi bi-clipboard-check', 'url' => route('freight_bookings.index', ['pending_memo' => 1])],
    ],
    'showPeriodFilter' => true,
    'dashboardPeriod' => $period,
])

<div class="dashboard-grid dashboard-grid--split">
    @include('dashboard.partials.action-center', ['actions' => $actions, 'title' => 'Today\'s operations to-do'])
    @include('dashboard.partials.profitability-panel', ['profitabilityHref' => route('reports.profitability', $reportRange)])
</div>

<div class="dashboard-kpis">
    @include('dashboard.partials.kpi-card', [
        'title' => 'Trips booked',
        'value' => number_format($margin['trips']),
        'icon' => 'bi bi-truck',
        'tone' => 'primary',
        'delta' => $growth['trips_change'],
        'comparison' => $period->comparisonLabel,
        'hint' => $period->label,
        'href' => route('freight_bookings.index'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'In transit',
        'value' => number_format($unbilled['in_transit_count']),
        'icon' => 'bi bi-signpost-split',
        'tone' => 'info',
        'hint' => $exceptions['long_running'].' running '.\App\Services\Dashboard\BusinessInsightsService::LONG_RUNNING_TRIP_DAYS.'+ days',
        'href' => route('freight_bookings.index'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Awaiting memo / POD',
        'value' => number_format($unbilled['pod_pending_count']),
        'icon' => 'bi bi-clipboard-check',
        'tone' => $unbilled['pod_pending_count'] > 0 ? 'warning' : 'success',
        'hint' => formatRupeesCompact($unbilled['pod_pending_value']).' blocked from billing',
        'href' => route('freight_bookings.index', ['pending_memo' => 1]),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Ready to invoice',
        'value' => number_format($unbilled['ready_count']),
        'icon' => 'bi bi-receipt-cutoff',
        'tone' => $unbilled['ready_count'] > 0 ? 'warning' : 'success',
        'hint' => formatRupeesCompact($unbilled['ready_value']).($unbilled['ready_count'] > 0 ? ' · oldest '.$unbilled['ready_oldest_days'].'d' : ''),
        'href' => route('invoices.create'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'E-way alerts',
        'value' => number_format($exceptions['eway_expired'] + $exceptions['eway_expiring']),
        'icon' => 'bi bi-shield-exclamation',
        'tone' => $exceptions['eway_expired'] > 0 ? 'danger' : ($exceptions['eway_expiring'] > 0 ? 'warning' : 'success'),
        'hint' => $exceptions['eway_expired'].' expired · '.$exceptions['eway_expiring'].' expiring',
        'href' => route('reports.eway-bill'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Bilties without hire',
        'value' => number_format($exceptions['without_hire']),
        'icon' => 'bi bi-link-45deg',
        'tone' => $exceptions['without_hire'] > 0 ? 'warning' : 'success',
        'hint' => 'Last 90 days',
        'href' => route('lorry_receipts.index', ['delivery_status' => 'Pending']),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Loss-making trips',
        'value' => number_format($margin['loss_trips']),
        'icon' => 'bi bi-graph-down-arrow',
        'tone' => $margin['loss_trips'] > 0 ? 'danger' : 'success',
        'hint' => $margin['low_margin_trips'].' more below '.\App\Services\Dashboard\BusinessInsightsService::LOW_MARGIN_PERCENT.'% margin',
        'href' => route('reports.profitability', $reportRange + ['profit_loss' => 'loss']),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Cancelled hires',
        'value' => number_format($exceptions['cancelled']),
        'icon' => 'bi bi-x-circle',
        'tone' => $exceptions['cancellation_rate'] >= 10 ? 'danger' : 'info',
        'hint' => $exceptions['cancellation_rate'].'% of hires '.strtolower($period->label),
    ])
</div>

@include('dashboard.partials.watchlist-panel', ['linkTrips' => true, 'ewayHref' => route('reports.eway-bill')])

<div class="dashboard-grid dashboard-grid--2">
    <section class="dashboard-panel h-100">
        <div class="dashboard-panel__header">
            <h2 class="dashboard-panel__title"><i class="bi bi-receipt-cutoff"></i> Ready to bill by customer</h2>
            <a href="{{ route('invoices.create') }}" class="dashboard-link">Create invoice <i class="bi bi-arrow-right-short"></i></a>
        </div>
        <div class="dashboard-panel__body">
            @include('dashboard.partials.ranked-list', [
                'rows' => collect($unbilled['ready_by_customer'])->map(fn (array $row): array => [
                    'label' => $row['customer'],
                    'value' => formatRupeesCompact($row['value']),
                    'meta' => $row['trips'].' '.Str::plural('bilty', $row['trips']).' · oldest '.$row['oldest_days'].' days',
                    'percent' => $row['value'] / $maxReadyValue * 100,
                    'tone' => $row['oldest_days'] > 15 ? 'danger' : 'warning',
                ])->all(),
                'emptyText' => 'Every delivered bilty with a memo has been invoiced.',
            ])
        </div>
    </section>

    <section class="dashboard-panel h-100">
        <div class="dashboard-panel__header">
            <h2 class="dashboard-panel__title"><i class="bi bi-people"></i> Transporters used {{ strtolower($period->label) }}</h2>
            <a href="{{ route('reports.transporter-report') }}" class="dashboard-link">Transporter report <i class="bi bi-arrow-right-short"></i></a>
        </div>
        <div class="dashboard-panel__body">
            @include('dashboard.partials.ranked-list', [
                'rows' => collect($margin['transporters'])->map(fn (array $row): array => [
                    'label' => $row['transporter'],
                    'value' => $row['trips'].' '.Str::plural('trip', $row['trips']),
                    'meta' => $row['margin'].'% margin on these trips',
                    'percent' => $row['trips'] / $maxTransporterTrips * 100,
                    'tone' => $row['margin'] < \App\Services\Dashboard\BusinessInsightsService::LOW_MARGIN_PERCENT ? 'warning' : 'info',
                ])->all(),
                'emptyText' => 'No hires in this period yet.',
            ])
        </div>
    </section>
</div>

@include('dashboard.partials.profit-drivers-panel', ['profitabilityHref' => route('reports.profitability', $reportRange)])

<script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
