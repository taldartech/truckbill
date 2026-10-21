@php
    $payables = $insights['payables'];
    $receivables = $insights['receivables'];
    $maxTransporter = collect($payables['by_transporter'])->max('total') ?: 1;
    $cashCover = $receivables['due_soon'] + $receivables['overdue'];
@endphp

<section class="dashboard-panel h-100">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-wallet2"></i> Transporter dues</h2>
        <span class="dashboard-panel__aside">{{ formatRupeesCompact($payables['total']) }} total balance</span>
    </div>
    <div class="dashboard-panel__body">
        <div class="cash-compare">
            <div class="cash-compare__item">
                <span class="cash-compare__label">Due now (delivered trips)</span>
                <strong class="cash-compare__value text-warning">{{ formatRupeesCompact($payables['due_now']) }}</strong>
            </div>
            <div class="cash-compare__item">
                <span class="cash-compare__label">Still running</span>
                <strong class="cash-compare__value">{{ formatRupeesCompact($payables['running']) }}</strong>
            </div>
            <div class="cash-compare__item">
                <span class="cash-compare__label">Collectable (overdue + next {{ \App\Services\Dashboard\BusinessInsightsService::DUE_SOON_DAYS }} days)</span>
                <strong class="cash-compare__value {{ $cashCover >= $payables['due_now'] ? 'text-success' : 'text-danger' }}">{{ formatRupeesCompact($cashCover) }}</strong>
            </div>
        </div>

        <h3 class="dashboard-subhead">Largest balances by transporter</h3>
        @include('dashboard.partials.ranked-list', [
            'rows' => collect($payables['by_transporter'])->map(fn (array $row): array => [
                'label' => $row['transporter'],
                'value' => formatRupeesCompact($row['total']),
                'meta' => $row['trips'].' '.Str::plural('trip', $row['trips']).($row['due_now'] > 0 ? ' · '.formatRupeesCompact($row['due_now']).' due now' : ' · all running'),
                'percent' => $row['total'] / $maxTransporter * 100,
                'tone' => $row['due_now'] > 0 ? 'warning' : 'info',
            ])->all(),
            'emptyText' => 'No pending transporter balances.',
        ])

        <div class="dashboard-panel__footer">
            <span>Paid out {{ strtolower($insights['period']->label) }}: <strong>{{ formatRupeesCompact($payables['paid_out']) }}</strong></span>
            @if (! empty($payablesHref))
                <a href="{{ $payablesHref }}" class="dashboard-link">Transporter report <i class="bi bi-arrow-right-short"></i></a>
            @endif
        </div>
    </div>
</section>
