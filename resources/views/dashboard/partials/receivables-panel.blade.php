@php
    $receivables = $insights['receivables'];
    $agingHref = $agingHref ?? null;
    $maxDebtor = collect($receivables['debtors'])->max('outstanding') ?: 1;
@endphp

<section class="dashboard-panel h-100">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-cash-coin"></i> Receivables</h2>
        <span class="dashboard-panel__aside">
            {{ formatRupeesCompact($receivables['outstanding']) }} outstanding · {{ $receivables['open_count'] }} {{ Str::plural('invoice', $receivables['open_count']) }}
        </span>
    </div>
    <div class="dashboard-panel__body">
        @include('dashboard.partials.aging-bar', ['buckets' => $receivables['buckets']])

        <h3 class="dashboard-subhead">Call first: biggest overdue customers</h3>
        @include('dashboard.partials.ranked-list', [
            'rows' => collect($receivables['debtors'])->take(5)->map(fn (array $debtor): array => [
                'label' => $debtor['customer'],
                'value' => formatRupeesCompact($debtor['overdue'] > 0 ? $debtor['overdue'] : $debtor['outstanding']),
                'meta' => $debtor['overdue'] > 0
                    ? "Overdue · oldest {$debtor['max_days_overdue']} days · ".formatRupeesCompact($debtor['outstanding']).' total'
                    : 'Not due yet · '.$debtor['invoices'].' '.Str::plural('invoice', $debtor['invoices']),
                'percent' => $debtor['outstanding'] / $maxDebtor * 100,
                'tone' => $debtor['overdue'] > 0 ? 'danger' : 'primary',
            ])->all(),
            'emptyText' => 'No unpaid invoices. Every invoice is settled.',
        ])

        <div class="dashboard-panel__footer">
            <span>Collected {{ strtolower($insights['period']->label) }}: <strong>{{ formatRupeesCompact($receivables['collected']) }}</strong></span>
            @if ($agingHref)
                <a href="{{ $agingHref }}" class="dashboard-link">Debtors aging <i class="bi bi-arrow-right-short"></i></a>
            @endif
        </div>
    </div>
</section>
