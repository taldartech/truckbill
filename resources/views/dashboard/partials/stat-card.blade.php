@php
    $tone = $tone ?? 'primary';
    $href = $href ?? null;
    $chipClass = match ($chipTone ?? null) {
        'success' => 'dashboard-stat-card__chip--success',
        'warning' => 'dashboard-stat-card__chip--warning',
        'danger' => 'dashboard-stat-card__chip--danger',
        'info' => 'dashboard-stat-card__chip--info',
        default => '',
    };
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    class="dashboard-stat-card dashboard-stat-card--{{ $tone }} {{ $href ? 'dashboard-stat-card--clickable' : '' }}"
>
    <div class="dashboard-stat-card__top">
        <div class="dashboard-stat-card__icon">
            <i class="{{ $icon }}"></i>
        </div>
        <div class="flex-grow-1 min-w-0">
            <h3 class="dashboard-stat-card__title text-truncate">{{ $title }}</h3>
            <div class="dashboard-stat-card__value text-nowrap" data-count-up="{{ $rawValue ?? $value }}">{{ $value }}</div>
        </div>
        @if ($href)
            <i class="bi bi-box-arrow-up-right dashboard-stat-card__action flex-shrink-0"></i>
        @endif
    </div>
    @if (! empty($chip))
        <div class="dashboard-stat-card__meta">
            <span class="dashboard-stat-card__chip text-nowrap {{ $chipClass }}">{{ $chip }}</span>
        </div>
    @endif
</{{ $tag }}>
