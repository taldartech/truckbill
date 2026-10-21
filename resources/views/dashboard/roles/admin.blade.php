@php
    $period = $insights['period'];
    $receivables = $insights['receivables'];
    $unbilled = $insights['unbilled'];
    $payables = $insights['payables'];
    $margin = $insights['margin'];
    $exceptions = $insights['exceptions'];
    $growth = $insights['growth'];
    $reportRange = ['start_date' => $period->start->toDateString(), 'end_date' => $period->end->toDateString()];
    $trappedCapital = (float) ($receivables['overdue'] + $unbilled['ready_value']);

    // Progress metrics for the 4 Vital Signs
    $marginPercentVisual = min(100, max(5, round((float) $margin['margin'] * 3)));
    $totalCashFlow = (float) ($receivables['collected'] + $payables['paid_out']);
    $collectionRatio = $totalCashFlow > 0 ? round($receivables['collected'] / $totalCashFlow * 100) : 100;
    $trappedOverdueRatio = $trappedCapital > 0 ? min(100, max(10, round($receivables['overdue'] / $trappedCapital * 100))) : 0;
    $totalPayablesDues = (float) ($payables['due_now'] + $payables['running']);
    $dueNowRatio = $totalPayablesDues > 0 ? round($payables['due_now'] / $totalPayablesDues * 100) : 0;
@endphp

@include('dashboard.partials.hero', [
    'subtitle' => 'Business health for '.strtolower($period->label).', compared '.$period->comparisonLabel.'.',
    'quickActions' => [
        ['label' => 'New Bilty', 'icon' => 'bi bi-plus-lg', 'url' => route('lorry_receipts.create')],
        ['label' => 'New Hire', 'icon' => 'bi bi-truck', 'url' => route('lorry_receipts.index', ['delivery_status' => 'Pending'])],
        ['label' => 'Create Invoice', 'icon' => 'bi bi-receipt', 'url' => route('invoices.create')],
    ],
    'showPeriodFilter' => true,
    'dashboardPeriod' => $period,
])

{{-- 4 Executive Vital Signs (Compact with interactive micro-progress tracks) --}}
<div class="dashboard-kpis dashboard-kpis--executive mb-3">
    {{-- 1. Gross Profit & Margin --}}
    @include('dashboard.partials.kpi-card', [
        'title' => 'Gross profit',
        'value' => formatRupeesCompact($margin['profit']),
        'icon' => 'bi bi-graph-up-arrow',
        'tone' => $margin['profit'] < 0 ? 'danger' : 'success',
        'delta' => $growth['profit_change'],
        'comparison' => $period->comparisonLabel,
        'hint' => $margin['margin'].'% margin · '.$margin['trips'].' '.Str::plural('trip', $margin['trips']),
        'href' => route('reports.profitability', $reportRange),
        'progressPercent' => $marginPercentVisual,
        'progressTone' => $margin['profit'] < 0 ? 'danger' : 'success',
        'progressHint' => 'Margin: '.$margin['margin'].'%',
    ])

    {{-- 2. Realized Cash Collected --}}
    @include('dashboard.partials.kpi-card', [
        'title' => 'Cash collected',
        'value' => formatRupeesCompact($receivables['collected']),
        'icon' => 'bi bi-piggy-bank',
        'tone' => 'info',
        'delta' => $receivables['collected_change'],
        'comparison' => $period->comparisonLabel,
        'hint' => formatRupeesCompact($payables['paid_out']).' paid to fleet',
        'href' => route('accounting.debtors-aging'),
        'progressPercent' => $collectionRatio,
        'progressTone' => 'info',
        'progressHint' => 'Collection vs Payout Ratio: '.$collectionRatio.'%',
    ])

    {{-- 3. Trapped Capital (Overdue + Unbilled) --}}
    @include('dashboard.partials.kpi-card', [
        'title' => 'Trapped capital',
        'value' => formatRupeesCompact($trappedCapital),
        'icon' => 'bi bi-exclamation-octagon-fill',
        'tone' => $trappedCapital > 0 ? 'danger' : 'success',
        'hint' => formatRupeesCompact($receivables['overdue']).' overdue · '.formatRupeesCompact($unbilled['ready_value']).' unbilled',
        'href' => route('invoices.create'),
        'progressPercent' => $trappedOverdueRatio,
        'progressTone' => 'danger',
        'progressHint' => 'Overdue share of trapped capital: '.$trappedOverdueRatio.'%',
    ])

    {{-- 4. Transporter Payables Due Now --}}
    @include('dashboard.partials.kpi-card', [
        'title' => 'Transporter dues now',
        'value' => formatRupeesCompact($payables['due_now']),
        'icon' => 'bi bi-wallet2',
        'tone' => $payables['due_now'] > 0 ? 'warning' : 'success',
        'hint' => $payables['due_now_trips'].' delivered '.Str::plural('trip', $payables['due_now_trips']).' ready for settlement',
        'href' => route('reports.transporter-report'),
        'progressPercent' => $dueNowRatio,
        'progressTone' => 'warning',
        'progressHint' => 'Delivered dues share: '.$dueNowRatio.'%',
    ])
</div>

{{-- Executive Tabs Control Bar --}}
<div class="dashboard-tabs-bar mb-3" data-dashboard-main-tabs>
    <div class="dashboard-tabs dashboard-tabs--executive" role="tablist">
        <button type="button" class="dashboard-tabs__btn is-active" data-main-tab="action_center">
            <i class="bi bi-lightning-charge-fill text-warning"></i>
            <span>Action Center & Financial Pulse</span>
            @if (count($actions) > 0)
                <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem; padding: 0.15rem 0.45rem;">{{ count($actions) }}</span>
            @endif
        </button>
        <button type="button" class="dashboard-tabs__btn" data-main-tab="intelligence">
            <i class="bi bi-bar-chart-line-fill text-primary"></i>
            <span>Deep Analytics & Growth Drivers</span>
        </button>
    </div>
    <div class="dashboard-tabs-bar__hint d-none d-md-flex">
        <span class="badge bg-light text-muted border py-1 px-2"><i class="bi bi-shield-check me-1 text-success"></i>Focus Mode</span>
    </div>
</div>

<div data-dashboard-main-tabs-content>
    {{-- TAB 1: ACTION CENTER & FINANCIAL PULSE (DEFAULT ACTIVE) --}}
    <div data-main-pane="action_center">
        {{-- Row 1: Split Grid - Urgent Action Feed on Left + Interactive Chart on Right --}}
        <div class="dashboard-grid dashboard-grid--split mb-3">
            @include('dashboard.partials.action-center', [
                'actions' => $actions,
                'title' => 'What needs your attention today',
                'visibleCount' => 5,
            ])
            @include('dashboard.partials.profitability-panel', [
                'profitabilityHref' => route('reports.profitability', $reportRange)
            ])
        </div>

        {{-- Row 2: 2-Column Working Capital & Fleet Exceptions Desk --}}
        <div class="dashboard-grid dashboard-grid--2 mb-3">
            <div>
                @include('dashboard.partials.receivables-panel', ['agingHref' => route('accounting.debtors-aging')])
            </div>
            <div>
                @include('dashboard.partials.watchlist-panel', ['linkTrips' => true, 'ewayHref' => route('reports.eway-bill')])
            </div>
        </div>

        {{-- Row 3: Transporter Dues & Settlement Desk --}}
        <div class="mb-3">
            @include('dashboard.partials.payables-panel', ['payablesHref' => route('reports.transporter-report')])
        </div>
    </div>

    {{-- TAB 2: DEEP ANALYTICS & GROWTH DRIVERS --}}
    <div data-main-pane="intelligence" hidden>
        {{-- Secondary Volume KPIs --}}
        <div class="dashboard-kpis mb-3">
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
                'title' => 'Ready to invoice',
                'value' => formatRupeesCompact($unbilled['ready_value']),
                'icon' => 'bi bi-receipt-cutoff',
                'tone' => $unbilled['ready_count'] > 0 ? 'warning' : 'success',
                'hint' => $unbilled['ready_count'].' '.Str::plural('bilty', $unbilled['ready_count']).($unbilled['ready_count'] > 0 ? ' · oldest '.$unbilled['ready_oldest_days'].'d' : ''),
                'href' => route('invoices.create'),
            ])
            @include('dashboard.partials.kpi-card', [
                'title' => 'In transit',
                'value' => formatRupeesCompact($unbilled['in_transit_value']),
                'icon' => 'bi bi-signpost-split',
                'tone' => 'info',
                'hint' => $unbilled['in_transit_count'].' running · '.$exceptions['long_running'].' delayed',
                'href' => route('freight_bookings.index'),
            ])
            @include('dashboard.partials.kpi-card', [
                'title' => 'Loss-making trips',
                'value' => number_format($margin['loss_trips']),
                'icon' => 'bi bi-graph-down-arrow',
                'tone' => $margin['loss_trips'] > 0 ? 'danger' : 'success',
                'hint' => $margin['loss_trips'] > 0 ? formatRupeesCompact($margin['loss_amount']).' lost' : 'Every trip made money',
                'href' => route('reports.profitability', $reportRange + ['profit_loss' => 'loss']),
            ])
        </div>

        {{-- Profit Drivers (Best/worst clients, top routes, loss trips table) --}}
        <div class="mb-3">
            @include('dashboard.partials.profit-drivers-panel', ['profitabilityHref' => route('reports.profitability', $reportRange)])
        </div>

        {{-- Growth & Client Concentration --}}
        <div class="mb-3">
            @include('dashboard.partials.growth-panel')
        </div>
    </div>
</div>

<script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
