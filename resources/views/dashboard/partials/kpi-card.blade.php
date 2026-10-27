@php
    $tone = $tone ?? 'primary';
    $href = $href ?? null;
    $delta = $delta ?? null;
    $deltaSuffix = $deltaSuffix ?? '%';
    $goodWhen = $goodWhen ?? 'up';
    $hint = $hint ?? null;
    $comparison = $comparison ?? null;

    $deltaTone = 'neutral';
    if ($delta !== null && abs($delta) >= 0.05) {
        $deltaTone = (($delta > 0) === ($goodWhen === 'up')) ? 'good' : 'bad';
    }
    $deltaIcon = $delta === null || abs($delta) < 0.05 ? 'bi-dash' : ($delta > 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short');
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    class="dashboard-kpi dashboard-kpi--{{ $tone }} {{ $href ? 'dashboard-kpi--clickable' : '' }}"
>
    <div class="dashboard-kpi__head">
        <span class="dashboard-kpi__icon"><i class="{{ $icon }}"></i></span>
        <span class="dashboard-kpi__title text-truncate">{{ $title }}</span>
        @if ($href)
            <i class="bi bi-arrow-up-right dashboard-kpi__link-icon"></i>
        @endif
    </div>
    <div class="dashboard-kpi__value text-nowrap">{{ $value }}</div>
    @if (isset($progressPercent))
        <div class="dashboard-kpi__progress-track" @if(!empty($progressHint)) title="{{ $progressHint }}" @endif>
            <div class="dashboard-kpi__progress-fill dashboard-kpi__progress-fill--{{ $progressTone ?? $tone }}" style="width: {{ min(100, max(4, (float)$progressPercent)) }}%;"></div>
        </div>
    @endif
    <div class="dashboard-kpi__foot">
        @if ($delta !== null)
            <span class="dashboard-kpi__delta dashboard-kpi__delta--{{ $deltaTone }}" @if ($comparison) title="{{ $comparison }}" @endif>
                <i class="bi {{ $deltaIcon }}"></i>{{ number_format(abs($delta), 1) }}{{ $deltaSuffix }}
            </span>
        @endif
        @if ($hint)
            <span class="dashboard-kpi__hint">{{ $hint }}</span>
        @endif
    </div>
</{{ $tag }}>
