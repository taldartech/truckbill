@php
    $margin = $insights['margin'];
    $period = $insights['period'];
    $sixMonths = $insights['trend']['totals'];
    $sixMonthsFrom = $insights['trend']['labels'][0] ?? '';
    $profitabilityHref = $profitabilityHref ?? null;
@endphp

<section class="dashboard-panel dashboard-profitability" data-dashboard-analytics>
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-graph-up-arrow"></i> Profitability</h2>
        <div class="dashboard-tabs" role="tablist">
            <button type="button" class="dashboard-tabs__btn is-active" data-dash-tab="period">{{ $period->label }}</button>
            <button type="button" class="dashboard-tabs__btn" data-dash-tab="months">Last 6 months</button>
        </div>
    </div>
    <div class="dashboard-panel__body">
        <div class="dashboard-tab-pane" data-dash-pane="period">
            @include('dashboard.partials.profit-summary', ['totals' => $margin])
            <div class="dashboard-chart">
                <canvas id="dashProfitTrendChart" aria-label="Revenue, cost and profit for the selected period"></canvas>
            </div>
        </div>
        <div class="dashboard-tab-pane" data-dash-pane="months" hidden>
            @include('dashboard.partials.profit-summary', ['totals' => $sixMonths])
            <div class="dashboard-chart">
                <canvas id="dashMonthlyPnlChart" aria-label="Monthly revenue, cost, profit and collections"></canvas>
            </div>
        </div>

        <div class="dashboard-panel__footer">
            <span data-dash-pane="period">{{ $margin['trips'] }} {{ Str::plural('trip', $margin['trips']) }} · {{ $margin['loss_trips'] }} at a loss · {{ $margin['low_margin_trips'] }} below {{ \App\Services\Dashboard\BusinessInsightsService::LOW_MARGIN_PERCENT }}% margin</span>
            <span data-dash-pane="months" hidden>{{ $sixMonths['trips'] }} {{ Str::plural('trip', $sixMonths['trips']) }} · {{ formatRupeesCompact($sixMonths['collected']) }} collected since {{ $sixMonthsFrom }}</span>
            @if ($profitabilityHref)
                <a href="{{ $profitabilityHref }}" class="dashboard-link">Profitability report <i class="bi bi-arrow-right-short"></i></a>
            @endif
        </div>
    </div>
</section>
