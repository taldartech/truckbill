@php
    $period = $insights['period'];
    $receivables = $insights['receivables'];
    $unbilled = $insights['unbilled'];
    $payables = $insights['payables'];
    $margin = $insights['margin'];
    $exceptions = $insights['exceptions'];
    $growth = $insights['growth'];
    $reportRange = ['start_date' => $period->start->toDateString(), 'end_date' => $period->end->toDateString()];
@endphp

@include('dashboard.partials.hero', [
    'subtitle' => 'Business health for '.strtolower($period->label).', compared '.$period->comparisonLabel.'.',
    'quickActions' => [
        ['label' => 'New Bilty', 'icon' => 'bi bi-plus-lg', 'url' => route('lorry_receipts.create')],
        ['label' => 'New Hire', 'icon' => 'bi bi-truck', 'url' => route('freight_bookings.create')],
        ['label' => 'Create Invoice', 'icon' => 'bi bi-receipt', 'url' => route('invoices.create')],
    ],
    'showPeriodFilter' => true,
    'dashboardPeriod' => $period,
])

@include('dashboard.partials.action-center', ['actions' => $actions])

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
        'href' => route('reports.profitability', $reportRange),
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
        'href' => route('invoices.index'),
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
        'title' => 'Transporter dues now',
        'value' => formatRupeesCompact($payables['due_now']),
        'icon' => 'bi bi-wallet2',
        'tone' => 'warning',
        'hint' => $payables['due_now_trips'].' delivered '.Str::plural('trip', $payables['due_now_trips']),
        'href' => route('reports.transporter-report'),
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

<div class="dashboard-grid dashboard-grid--2">
    @include('dashboard.partials.receivables-panel', ['agingHref' => route('accounting.debtors-aging')])
    @include('dashboard.partials.payables-panel', ['payablesHref' => route('reports.transporter-report')])
</div>

@include('dashboard.partials.profitability-panel', ['profitabilityHref' => route('reports.profitability', $reportRange)])

@include('dashboard.partials.watchlist-panel', ['linkTrips' => true, 'ewayHref' => route('reports.eway-bill')])

@include('dashboard.partials.growth-panel')

<script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
