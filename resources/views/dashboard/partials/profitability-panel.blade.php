@php
    $margin = $insights['margin'];
    $period = $insights['period'];
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
            <div class="profit-summary">
                <div class="profit-summary__item">
                    <span>Revenue</span><strong>{{ formatRupeesCompact($margin['revenue']) }}</strong>
                </div>
                <div class="profit-summary__item">
                    <span>Trip cost</span><strong>{{ formatRupeesCompact($margin['cost']) }}</strong>
                </div>
                <div class="profit-summary__item">
                    <span>Gross profit</span><strong class="{{ $margin['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ formatRupeesCompact($margin['profit']) }}</strong>
                </div>
                <div class="profit-summary__item">
                    <span>Margin</span><strong>{{ $margin['margin'] }}%</strong>
                </div>
                <div class="profit-summary__item">
                    <span>Profit / trip</span><strong>{{ formatRupeesCompact($margin['profit_per_trip']) }}</strong>
                </div>
            </div>
            <div class="dashboard-chart">
                <canvas id="dashProfitTrendChart" aria-label="Revenue, cost and profit for the selected period"></canvas>
            </div>
        </div>
        <div class="dashboard-tab-pane" data-dash-pane="months" hidden>
            <div class="dashboard-chart">
                <canvas id="dashMonthlyPnlChart" aria-label="Monthly revenue, cost, profit and collections"></canvas>
            </div>
        </div>

        <div class="dashboard-panel__footer">
            <span>{{ $margin['trips'] }} {{ Str::plural('trip', $margin['trips']) }} · {{ $margin['loss_trips'] }} at a loss · {{ $margin['low_margin_trips'] }} below {{ \App\Services\Dashboard\BusinessInsightsService::LOW_MARGIN_PERCENT }}% margin</span>
            @if ($profitabilityHref)
                <a href="{{ $profitabilityHref }}" class="dashboard-link">Profitability report <i class="bi bi-arrow-right-short"></i></a>
            @endif
        </div>
    </div>
</section>
