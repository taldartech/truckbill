@php
    $growth = $insights['growth'];
    $period = $insights['period'];
    $maxTopRevenue = collect($growth['top_customers'])->max('revenue') ?: 1;
@endphp

<section class="dashboard-panel">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-rocket-takeoff"></i> Growth</h2>
        <div class="dashboard-pills">
            <span class="dashboard-pill dashboard-pill--info">{{ $growth['new_customers'] }} new {{ Str::plural('customer', $growth['new_customers']) }}</span>
            <span class="dashboard-pill {{ $growth['top_customer_share'] >= \App\Services\Dashboard\BusinessInsightsService::CUSTOMER_CONCENTRATION_PERCENT ? 'dashboard-pill--warning' : '' }}">
                Top customer = {{ $growth['top_customer_share'] }}% of revenue
            </span>
            <span class="dashboard-pill">Invoiced {{ formatRupeesCompact($growth['invoiced']) }}</span>
        </div>
    </div>
    <div class="dashboard-panel__body">
        @if ($insights['margin']['trips'] === 0)
            @include('dashboard.partials.period-empty-hint', ['period' => $period, 'lastTripDate' => $insights['margin']['last_trip_date']])
        @endif

        <div class="dashboard-grid dashboard-grid--2 {{ $insights['margin']['trips'] === 0 ? 'mt-3' : '' }}">
            <div>
                <h3 class="dashboard-subhead">Top customers {{ $period->comparisonLabel }}</h3>
                @include('dashboard.partials.ranked-list', [
                    'rows' => collect($growth['top_customers'])->map(fn (array $c): array => [
                        'label' => $c['customer'],
                        'value' => formatRupeesCompact($c['revenue']),
                        'meta' => $c['change'] === null
                            ? 'New this period · '.$c['trips'].' '.Str::plural('trip', $c['trips'])
                            : ($c['change'] >= 0 ? '▲ ' : '▼ ').abs($c['change']).'% · '.$c['trips'].' '.Str::plural('trip', $c['trips']),
                        'percent' => $c['revenue'] / $maxTopRevenue * 100,
                        'tone' => $c['change'] !== null && $c['change'] < 0 ? 'warning' : 'success',
                    ])->all(),
                    'emptyText' => 'No bookings in this period yet.',
                ])
            </div>
            <div>
                <h3 class="dashboard-subhead">Slipping customers (down 30% or more)</h3>
                @include('dashboard.partials.ranked-list', [
                    'rows' => collect($growth['declining_customers'])->map(fn (array $c): array => [
                        'label' => $c['customer'],
                        'value' => '▼ '.abs($c['change']).'%',
                        'meta' => formatRupeesCompact($c['current_revenue']).' now vs '.formatRupeesCompact($c['revenue']).' before',
                        'tone' => 'danger',
                    ])->all(),
                    'emptyText' => 'No major customer has slowed down.',
                ])
            </div>
        </div>
    </div>
</section>
