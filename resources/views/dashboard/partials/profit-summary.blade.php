{{-- totals: ['revenue' => float, 'cost' => float, 'profit' => float, 'margin' => float, 'profit_per_trip' => float] --}}
<div class="profit-summary">
    <div class="profit-summary__item">
        <span>Revenue</span><strong>{{ formatRupeesCompact($totals['revenue']) }}</strong>
    </div>
    <div class="profit-summary__item">
        <span>Trip cost</span><strong>{{ formatRupeesCompact($totals['cost']) }}</strong>
    </div>
    <div class="profit-summary__item">
        <span>Gross profit</span><strong class="{{ $totals['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ formatRupeesCompact($totals['profit']) }}</strong>
    </div>
    <div class="profit-summary__item">
        <span>Margin</span><strong>{{ $totals['margin'] }}%</strong>
    </div>
    <div class="profit-summary__item">
        <span>Profit / trip</span><strong>{{ formatRupeesCompact($totals['profit_per_trip']) }}</strong>
    </div>
</div>
