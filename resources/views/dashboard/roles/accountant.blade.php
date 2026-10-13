@php
    $period = $insights['period'];
    $receivables = $insights['receivables'];
    $unbilled = $insights['unbilled'];
    $payables = $insights['payables'];
    $growth = $insights['growth'];
    $backlogRange = ['start_date' => now()->subMonths(3)->toDateString(), 'end_date' => now()->toDateString()];
@endphp

@include('dashboard.partials.hero', [
    'subtitle' => 'Collections, dues and cash position for '.strtolower($period->label).'.',
    'quickActions' => [
        ['label' => 'Debtors Aging', 'icon' => 'bi bi-hourglass-split', 'url' => route('accounting.debtors-aging')],
        ['label' => 'Day Book', 'icon' => 'bi bi-journal-text', 'url' => route('accounting.day-book')],
        ['label' => 'New Voucher', 'icon' => 'bi bi-plus-lg', 'url' => route('accounting.vouchers.create')],
    ],
    'showPeriodFilter' => true,
    'dashboardPeriod' => $period,
])

@include('dashboard.partials.action-center', ['actions' => $actions, 'title' => 'Finance to-do'])

<div class="dashboard-kpis">
    @include('dashboard.partials.kpi-card', [
        'title' => 'Outstanding receivables',
        'value' => formatRupeesCompact($receivables['outstanding']),
        'icon' => 'bi bi-cash-stack',
        'tone' => 'primary',
        'hint' => $receivables['open_count'].' unpaid '.Str::plural('invoice', $receivables['open_count']),
        'href' => route('accounting.debtors-aging'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Overdue',
        'value' => formatRupeesCompact($receivables['overdue']),
        'icon' => 'bi bi-exclamation-octagon',
        'tone' => $receivables['overdue'] > 0 ? 'danger' : 'success',
        'hint' => $receivables['overdue_share'].'% of outstanding · '.$receivables['overdue_count'].' '.Str::plural('invoice', $receivables['overdue_count']),
        'href' => route('accounting.debtors-aging'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Due in next '.\App\Services\Dashboard\BusinessInsightsService::DUE_SOON_DAYS.' days',
        'value' => formatRupeesCompact($receivables['due_soon']),
        'icon' => 'bi bi-bell',
        'tone' => 'info',
        'hint' => $receivables['due_soon_count'].' '.Str::plural('invoice', $receivables['due_soon_count']).' to remind',
        'href' => route('reports.party-pending-invoice'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Collected',
        'value' => formatRupeesCompact($receivables['collected']),
        'icon' => 'bi bi-piggy-bank',
        'tone' => 'success',
        'delta' => $receivables['collected_change'],
        'comparison' => $period->comparisonLabel,
        'hint' => $period->label,
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Invoiced',
        'value' => formatRupeesCompact($growth['invoiced']),
        'icon' => 'bi bi-receipt',
        'tone' => 'primary',
        'delta' => $growth['invoiced_change'],
        'comparison' => $period->comparisonLabel,
        'hint' => $period->label,
        'href' => route('reports.invoice'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Delivered, not invoiced',
        'value' => formatRupeesCompact($unbilled['ready_value'] + $unbilled['pod_pending_value']),
        'icon' => 'bi bi-receipt-cutoff',
        'tone' => ($unbilled['ready_count'] + $unbilled['pod_pending_count']) > 0 ? 'warning' : 'success',
        'hint' => $unbilled['ready_count'].' ready · '.$unbilled['pod_pending_count'].' awaiting memo',
        'href' => route('reports.pending-invoice', $backlogRange),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Transporter dues now',
        'value' => formatRupeesCompact($payables['due_now']),
        'icon' => 'bi bi-wallet2',
        'tone' => 'warning',
        'hint' => $payables['due_now_transporters'].' '.Str::plural('transporter', $payables['due_now_transporters']).' · '.formatRupeesCompact($payables['total']).' total',
        'href' => route('reports.transporter-report'),
    ])
    @include('dashboard.partials.kpi-card', [
        'title' => 'Paid to transporters',
        'value' => formatRupeesCompact($payables['paid_out']),
        'icon' => 'bi bi-send-check',
        'tone' => 'info',
        'delta' => $payables['paid_out_change'],
        'goodWhen' => 'down',
        'comparison' => $period->comparisonLabel,
        'hint' => $period->label,
    ])
</div>

<div class="dashboard-grid dashboard-grid--2">
    @include('dashboard.partials.receivables-panel', ['agingHref' => route('accounting.debtors-aging')])
    @include('dashboard.partials.payables-panel', ['payablesHref' => route('reports.transporter-report')])
</div>

<section class="dashboard-panel">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-alarm"></i> Most overdue invoices</h2>
        <span class="dashboard-panel__aside">Due date is the date set on the invoice, or {{ \App\Models\Invoice::DEFAULT_CREDIT_DAYS }} days after the invoice date</span>
    </div>
    <div class="dashboard-panel__body dashboard-panel__body--flush">
        @if (count($receivables['overdue_invoices']) === 0)
            <div class="dashboard-empty">No overdue invoices.</div>
        @else
            <div class="table-responsive">
                <table class="table dashboard-table mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Invoice date</th>
                            <th>Due date</th>
                            <th class="text-end">Days overdue</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receivables['overdue_invoices'] as $invoice)
                            <tr>
                                <td class="fw-semibold text-nowrap">{{ $invoice['number'] }}</td>
                                <td><div class="text-truncate" style="max-width: 220px;" title="{{ $invoice['customer'] }}">{{ $invoice['customer'] }}</div></td>
                                <td class="text-nowrap">{{ formatDate($invoice['invoice_date']) }}</td>
                                <td class="text-nowrap">
                                    {{ formatDate($invoice['due_date']) }}
                                    @unless ($invoice['has_due_date'])
                                        <span class="text-muted small" title="No due date set on the invoice">(default)</span>
                                    @endunless
                                </td>
                                <td class="text-end fw-bold {{ $invoice['days_overdue'] > 60 ? 'text-danger' : 'text-warning' }}">{{ $invoice['days_overdue'] }}</td>
                                <td class="text-end fw-bold text-nowrap">{{ formatRupeesCompact($invoice['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<section class="dashboard-panel">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-bar-chart-line"></i> Revenue, trip cost and collections</h2>
        <span class="dashboard-panel__aside">Last 6 months</span>
    </div>
    <div class="dashboard-panel__body">
        <div class="dashboard-chart">
            <canvas id="dashMonthlyPnlChart" aria-label="Monthly revenue, cost, profit and collections"></canvas>
        </div>
    </div>
</section>

<script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
