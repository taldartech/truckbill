@php
    $quickActions = [
        [
            'label' => 'New Bilty',
            'icon' => 'bi bi-plus-lg',
            'url' => route('lorry_receipts.create'),
        ],
        [
            'label' => 'Invoices',
            'icon' => 'bi bi-receipt',
            'url' => route('invoices.index'),
        ],
    ];
@endphp

@include('dashboard.partials.hero', [
    'subtitle' => 'Overview of bilty, hire, invoices, and e-way compliance for '.$periodLabel.'.',
    'quickActions' => $quickActions,
    'showPeriodFilter' => $showPeriodFilter ?? true,
    'dashboardPeriod' => $dashboardPeriod ?? 'month',
])

<div class="dashboard-stats">
    @include('dashboard.partials.stat-card', [
        'title' => 'Bookings',
        'value' => number_format($monthlyStats['total_bookings']),
        'rawValue' => $monthlyStats['total_bookings'],
        'icon' => 'bi bi-truck',
        'tone' => 'primary',
        'chip' => $periodLabel,
        'chipTone' => 'success',
        'href' => route('freight_bookings.index'),
    ])
    @include('dashboard.partials.stat-card', [
        'title' => 'Active Trips',
        'value' => number_format($monthlyStats['active_trips']),
        'rawValue' => $monthlyStats['active_trips'],
        'icon' => 'bi bi-signpost-split',
        'tone' => 'info',
        'chip' => 'In progress',
        'chipTone' => 'info',
        'href' => route('freight_bookings.index'),
    ])
    @include('dashboard.partials.stat-card', [
        'title' => 'Pending LRs',
        'value' => number_format($monthlyStats['pending_lrs']),
        'rawValue' => $monthlyStats['pending_lrs'],
        'icon' => 'bi bi-hourglass-split',
        'tone' => 'warning',
        'chip' => 'Awaiting delivery',
        'chipTone' => 'warning',
        'href' => route('lorry_receipts.index'),
    ])
    @include('dashboard.partials.stat-card', [
        'title' => 'Delivered Today',
        'value' => number_format($monthlyStats['delivered_today']),
        'rawValue' => $monthlyStats['delivered_today'],
        'icon' => 'bi bi-check2-circle',
        'tone' => 'success',
        'chip' => 'Today',
        'chipTone' => 'success',
        'href' => route('lorry_receipts.index'),
    ])
    @include('dashboard.partials.stat-card', [
        'title' => 'Pending Invoice',
        'value' => number_format($monthlyStats['lrs_pending_invoice']),
        'rawValue' => $monthlyStats['lrs_pending_invoice'],
        'icon' => 'bi bi-file-earmark-text',
        'tone' => 'danger',
        'chip' => 'Not invoiced',
        'chipTone' => 'danger',
        'href' => route('invoices.index'),
    ])
    @include('dashboard.partials.stat-card', [
        'title' => 'E-Way Expiring',
        'value' => number_format($monthlyStats['eway_bills_expiring_today']),
        'rawValue' => $monthlyStats['eway_bills_expiring_today'],
        'icon' => 'bi bi-exclamation-triangle',
        'tone' => 'warning',
        'chip' => 'Today',
        'chipTone' => 'warning',
        'href' => route('freight_bookings.index'),
    ])
    @include('dashboard.partials.stat-card', [
        'title' => 'Expired E-Way',
        'value' => number_format($monthlyStats['expired_eway_bills']),
        'rawValue' => $monthlyStats['expired_eway_bills'],
        'icon' => 'bi bi-x-octagon',
        'tone' => 'danger',
        'chip' => 'Urgent',
        'chipTone' => 'danger',
        'href' => route('freight_bookings.index'),
    ])
    @include('dashboard.partials.stat-card', [
        'title' => 'Outstanding',
        'value' => '₹'.number_format($monthlyStats['outstanding_amount'], 2),
        'rawValue' => $monthlyStats['outstanding_amount'],
        'icon' => 'bi bi-currency-rupee',
        'tone' => 'warning',
        'chip' => 'Invoice balance',
        'chipTone' => 'warning',
        'href' => route('invoices.index'),
    ])
</div>

<section class="dashboard-panel" data-dashboard-analytics>
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title">
            <i class="bi bi-pie-chart"></i>
            Visual Analytics &amp; Insights
        </h2>
       <div class="dashboard-tabs" role="tablist">
    <button type="button" class="dashboard-tabs__btn is-active" data-dash-tab="operations">Operations</button>
    <button type="button" class="dashboard-tabs__btn" data-dash-tab="finance">Finance</button>
    <button type="button" class="dashboard-tabs__btn" data-dash-tab="profitability">Profitability</button>
</div>
    </div>
    <div class="dashboard-panel__body">
        <div class="dashboard-tab-pane" data-dash-pane="operations">
            <div class="dashboard-analytics-grid">
            <div class="dashboard-analytics-card">
                    <h3 class="dashboard-analytics-card__title">Hire by Status</h3>
                    <p class="dashboard-analytics-card__hint">Date: {{ $periodLabel }}</p>
                    <div class="trip-status-grid">
                        @php
                            $created = (int) ($bookingStatuses['created'] ?? 0);
                            $running = (int) ($bookingStatuses['in_progress'] ?? 0);
                            $delivered = (int) ($bookingStatuses['delivered'] ?? 0);
                            $cancelled = (int) ($bookingStatuses['cancelled'] ?? 0);
                            $statusMax = max(1, $created, $running, $delivered, $cancelled);
                        @endphp
                        <div class="trip-status-card">
                            <span class="trip-status-card__value">{{ $created }}</span>
                            <span class="trip-status-card__label">Created</span>
                            <div class="trip-status-card__bar-wrap">
                                <div class="trip-status-card__bar trip-status-card__bar--blue" style="width: {{ round(($created / $statusMax) * 100) }}%"></div>
                            </div>
                        </div>
                        <div class="trip-status-card">
                            <span class="trip-status-card__value">{{ $running }}</span>
                            <span class="trip-status-card__label">Running</span>
                            <div class="trip-status-card__bar-wrap">
                                <div class="trip-status-card__bar trip-status-card__bar--amber" style="width: {{ round(($running / $statusMax) * 100) }}%"></div>
                            </div>
                        </div>
                        <div class="trip-status-card">
                            <span class="trip-status-card__value">{{ $delivered }}</span>
                            <span class="trip-status-card__label">Delivered</span>
                            <div class="trip-status-card__bar-wrap">
                                <div class="trip-status-card__bar trip-status-card__bar--green" style="width: {{ round(($delivered / $statusMax) * 100) }}%"></div>
                            </div>
                        </div>
                        <div class="trip-status-card">
                            <span class="trip-status-card__value">{{ $cancelled }}</span>
                            <span class="trip-status-card__label">Cancelled</span>
                            <div class="trip-status-card__bar-wrap">
                                <div class="trip-status-card__bar trip-status-card__bar--red" style="width: {{ round(($cancelled / $statusMax) * 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-analytics-card__footer">
                        <span>{{ $created + $running }} active volume</span>
                        <span>{{ $running > 0 ? round(($delivered / max(1, $delivered + $running)) * 100) : 0 }}% completion pace</span>
                    </div>
                </div>

                <div class="dashboard-analytics-card">
                    <h3 class="dashboard-analytics-card__title">Bilty Status Mix</h3>
                    <p class="dashboard-analytics-card__hint">Live delivery pipeline</p>
                    <div class="dashboard-analytics-card__chart">
                        <canvas id="dashLrStatusChart" aria-label="Bilty status chart"></canvas>
                    </div>
                </div>

                <div class="dashboard-analytics-card">
                    <h3 class="dashboard-analytics-card__title">Booking Trend</h3>
                    <p class="dashboard-analytics-card__hint">Last 6 months</p>
                    <div class="dashboard-analytics-card__chart">
                        <canvas id="dashBookingTrendChart" aria-label="Booking trend chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-tab-pane" data-dash-pane="finance" hidden>
    <div class="dashboard-analytics-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); width: 100%;">
                <div class="dashboard-analytics-card">
                    <h3 class="dashboard-analytics-card__title">Invoice Payment Status</h3>
                    <p class="dashboard-analytics-card__hint">All open ledgers</p>
                    <div class="dashboard-analytics-card__chart">
                        <canvas id="dashPaymentStatusChart" aria-label="Payment status chart"></canvas>
                    </div>
                    <div class="dashboard-analytics-card__footer">
                        <span>{{ number_format($invoicePaymentStatuses['pending'] ?? 0) }} pending</span>
                        <span>{{ number_format($invoicePaymentStatuses['paid'] ?? 0) }} paid</span>
                    </div>
                </div>

                <div class="dashboard-analytics-card">
                    <h3 class="dashboard-analytics-card__title">Revenue Trend</h3>
                    <p class="dashboard-analytics-card__hint">Invoice totals · last 6 months</p>
                    <div class="dashboard-analytics-card__chart">
                        <canvas id="dashRevenueTrendChart" aria-label="Revenue trend chart"></canvas>
                    </div>
                </div>

                <div class="dashboard-analytics-card">
                    <h3 class="dashboard-analytics-card__title">Period Snapshot</h3>
                    <p class="dashboard-analytics-card__hint">{{ $periodLabel }}</p>
                    <div class="trip-status-grid">
                        <div class="trip-status-card">
                            <span class="trip-status-card__value">₹{{ number_format($monthlyStats['period_revenue'] ?? 0, 0) }}</span>
                            <span class="trip-status-card__label">Invoiced</span>
                        </div>
                        <div class="trip-status-card">
                            <span class="trip-status-card__value">₹{{ number_format($monthlyStats['outstanding_amount'], 0) }}</span>
                            <span class="trip-status-card__label">Outstanding</span>
                        </div>
                        <div class="trip-status-card">
                            <span class="trip-status-card__value">{{ number_format($monthlyStats['total_bookings']) }}</span>
                            <span class="trip-status-card__label">Hires</span>
                        </div>
                        <div class="trip-status-card">
                            <span class="trip-status-card__value">{{ number_format($monthlyStats['lrs_pending_invoice']) }}</span>
                            <span class="trip-status-card__label">Unbilled LRs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-tab-pane" data-dash-pane="profitability" hidden>
    <div class="dashboard-analytics-grid" style="grid-template-columns: 1fr;">
       <div class="dashboard-analytics-card dashboard-analytics-card--profitability">
            <h3 class="dashboard-analytics-card__title">Profitability</h3>
            <p class="dashboard-analytics-card__hint">
                Revenue vs Cost vs Profit · {{ $periodLabel }}
            </p>

           <div class="dashboard-analytics-card__chart" style="height: 420px;">
                <canvas
                    id="dashProfitabilityTrendChart"
                    aria-label="Profitability chart">
                </canvas>
            </div>
        </div>
    </div>
</div>
    </div>
</section>

@if (isset($expiredEwayBills) && $expiredEwayBills->count() > 0)
    <section class="dashboard-panel">
        <div class="dashboard-panel__header">
            <h2 class="dashboard-panel__title text-danger">
                <i class="bi bi-exclamation-triangle text-danger"></i>
                E-Way Alerts
            </h2>
            <span class="dashboard-stat-card__chip dashboard-stat-card__chip--danger">
                {{ $expiredEwayBills->count() }} urgent
            </span>
        </div>
        <div class="dashboard-panel__body--flush">
            <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>LR Number</th>
                            <th>Truck &amp; Destination</th>
                            <th>Driver Mobile</th>
                            <th>Expiry</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expiredEwayBills->take(10) as $ewayBill)
                            @php
                                $expiryDate = \Carbon\Carbon::parse($ewayBill->eway_bill_expiry)->startOfDay();
                                $isPastOrToday = $expiryDate->isPast() || $expiryDate->isToday();
                            @endphp
                            <tr>
                                <td><strong>#{{ $ewayBill->lr->lr_number ?? '—' }}</strong></td>
                                <td>
                                    <div>{{ $ewayBill->lr->truck->truck_number ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $ewayBill->lr->destination_city ?? '—' }}</small>
                                </td>
                                <td>{{ $ewayBill->lr?->truck?->driver?->mobile ?? '—' }}</td>
                                <td>
                                    <span class="dashboard-stat-card__chip {{ $isPastOrToday ? 'dashboard-stat-card__chip--danger' : 'dashboard-stat-card__chip--warning' }}">
                                        {{ formatDate(optional($ewayBill)->eway_bill_expiry) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('freight_bookings.show', $ewayBill->id) }}"
                                        class="btn btn-sm btn-icon btn-action-view" title="View hire">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if (isset($pendingInvoices) && $pendingInvoices->count() > 0)
    <section class="dashboard-panel">
        <div class="dashboard-panel__header">
            <h2 class="dashboard-panel__title">
                <i class="bi bi-receipt"></i>
                Pending Invoices
            </h2>
            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-success rounded-pill px-3">View all</a>
        </div>
        <div class="dashboard-panel__body--flush">
            <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th class="text-end">Balance / Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingInvoices as $invoice)
                            @php
                                $paidAmount = $invoice->paid_amount ?? 0;
                                $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
                                $remainingAmount = $amountToCollect - $paidAmount;
                                if ($paidAmount > 0) {
                                    $statusClass = 'dashboard-stat-card__chip--info';
                                    $statusText = 'Partial';
                                } else {
                                    $statusClass = 'dashboard-stat-card__chip--warning';
                                    $statusText = 'Pending';
                                }
                            @endphp
                            <tr>
                                <td>
                                    <strong>
                                        #{{ $invoice->invoice_number
                                            ? $invoice->invoice_number.($invoice->manual_invoice_number ? ' ('.$invoice->manual_invoice_number.')' : '')
                                            : ($invoice->manual_invoice_number ?? $invoice->id) }}
                                    </strong>
                                </td>
                                <td>{{ Str::limit($invoice->consignee->name ?? 'N/A', 28) }}</td>
                                <td class="text-end">
                                    <div class="fw-bold text-danger">₹{{ number_format($remainingAmount, 2) }}</div>
                                    <small class="text-muted">of ₹{{ number_format($amountToCollect, 2) }}</small>
                                </td>
                                <td><span class="dashboard-stat-card__chip {{ $statusClass }}">{{ $statusText }}</span></td>
                                <td>{{ $invoice->generated_date ? \Carbon\Carbon::parse($invoice->generated_date)->format('d M Y') : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('invoices.show', $invoice->id) }}"
                                        class="btn btn-sm btn-icon btn-action-view" title="View invoice">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

<script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
