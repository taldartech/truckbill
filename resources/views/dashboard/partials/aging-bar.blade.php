@php
    $bucketLabels = [
        'not_due' => 'Not due',
        '1_30' => '1–30 days',
        '31_60' => '31–60 days',
        '61_90' => '61–90 days',
        '90_plus' => '90+ days',
    ];
    $agingTotal = array_sum($buckets);
@endphp

<div class="aging-bar">
    <div class="aging-bar__track" role="img" aria-label="Receivables by days overdue">
        @if ($agingTotal > 0)
            @foreach ($bucketLabels as $key => $label)
                @if (($buckets[$key] ?? 0) > 0)
                    <span class="aging-bar__segment aging-bar__segment--{{ $key }}"
                        style="width: {{ round($buckets[$key] / $agingTotal * 100, 2) }}%"
                        title="{{ $label }}: {{ formatRupeesCompact($buckets[$key]) }}"></span>
                @endif
            @endforeach
        @endif
    </div>
    <ul class="aging-bar__legend">
        @foreach ($bucketLabels as $key => $label)
            <li class="aging-bar__legend-item">
                <span class="aging-bar__swatch aging-bar__segment--{{ $key }}"></span>
                <span class="aging-bar__label">{{ $label }}</span>
                <strong class="aging-bar__amount">{{ formatRupeesCompact($buckets[$key] ?? 0) }}</strong>
                <span class="aging-bar__share">{{ $agingTotal > 0 ? round(($buckets[$key] ?? 0) / $agingTotal * 100) : 0 }}%</span>
            </li>
        @endforeach
    </ul>
</div>
