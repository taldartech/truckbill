@php
    $margin = $insights['margin'];
    $period = $insights['period'];
    $maxCustomerProfit = collect($margin['best_customers'])->max('profit') ?: 1;
    $maxRouteProfit = collect($margin['routes'])->max('profit') ?: 1;
    $profitabilityHref = $profitabilityHref ?? null;
@endphp

<section class="dashboard-panel">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-bullseye"></i> Profit drivers</h2>
        @if ($profitabilityHref)
            <a href="{{ $profitabilityHref }}" class="dashboard-link">Profitability report <i class="bi bi-arrow-right-short"></i></a>
        @else
            <span class="dashboard-panel__aside">{{ $period->label }}</span>
        @endif
    </div>
    <div class="dashboard-panel__body">
        @if ($margin['trips'] === 0)
            @include('dashboard.partials.period-empty-hint', ['period' => $period, 'lastTripDate' => $margin['last_trip_date']])
        @endif

        <div class="dashboard-grid dashboard-grid--3 {{ $margin['trips'] === 0 ? 'mt-3' : '' }}">
            <div>
                <h3 class="dashboard-subhead">Most profitable customers</h3>
                @include('dashboard.partials.ranked-list', [
                    'rows' => collect($margin['best_customers'])->filter(fn (array $c): bool => $c['profit'] > 0)->map(fn (array $c): array => [
                        'label' => $c['customer'],
                        'value' => formatRupeesCompact($c['profit']),
                        'meta' => "{$c['trips']} ".Str::plural('trip', $c['trips'])." · {$c['margin']}% margin",
                        'percent' => $c['profit'] / $maxCustomerProfit * 100,
                        'tone' => 'success',
                    ])->values()->all(),
                    'emptyText' => 'No profitable trips in this period yet.',
                ])
            </div>
            <div>
                <h3 class="dashboard-subhead">Lowest margin customers: review rates</h3>
                @include('dashboard.partials.ranked-list', [
                    'rows' => collect($margin['weak_customers'])->map(fn (array $c): array => [
                        'label' => $c['customer'],
                        'value' => $c['margin'].'%',
                        'meta' => "{$c['trips']} ".Str::plural('trip', $c['trips']).' · '.formatRupeesCompact($c['profit']).' profit',
                        'tone' => $c['margin'] < 0 ? 'danger' : ($c['margin'] < \App\Services\Dashboard\BusinessInsightsService::LOW_MARGIN_PERCENT ? 'warning' : 'primary'),
                    ])->all(),
                    'emptyText' => 'No trips in this period yet.',
                ])
            </div>
            <div>
                <h3 class="dashboard-subhead">Best routes by profit</h3>
                @include('dashboard.partials.ranked-list', [
                    'rows' => collect($margin['routes'])->map(fn (array $r): array => [
                        'label' => $r['route'],
                        'value' => formatRupeesCompact($r['profit']),
                        'meta' => "{$r['trips']} ".Str::plural('trip', $r['trips'])." · {$r['margin']}% margin",
                        'percent' => $r['profit'] > 0 ? $r['profit'] / $maxRouteProfit * 100 : 0,
                        'tone' => $r['profit'] < 0 ? 'danger' : 'info',
                    ])->all(),
                    'emptyText' => 'No trips in this period yet.',
                ])
            </div>
        </div>

        @if ($margin['loss_trips'] > 0)
            <h3 class="dashboard-subhead mt-3">Loss-making trips</h3>
            <div class="table-responsive">
                <table class="table dashboard-table mb-0">
                    <thead>
                        <tr>
                            <th>Bilty</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Route</th>
                            <th class="text-end">Freight</th>
                            <th class="text-end">Hire cost</th>
                            <th class="text-end">Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($margin['worst_trips'] as $trip)
                            <tr>
                                <td class="fw-semibold text-nowrap">{{ $trip['lr_number'] }}</td>
                                <td class="text-nowrap">{{ formatDate($trip['gr_date']) }}</td>
                                <td><div class="text-truncate" style="max-width: 180px;" title="{{ $trip['customer'] }}">{{ $trip['customer'] }}</div></td>
                                <td class="text-nowrap">{{ $trip['route'] }}</td>
                                <td class="text-end text-nowrap">{{ formatRupeesCompact($trip['revenue']) }}</td>
                                <td class="text-end text-nowrap">{{ formatRupeesCompact($trip['cost']) }}</td>
                                <td class="text-end text-nowrap text-danger fw-bold">{{ formatRupeesCompact(abs($trip['profit'])) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
