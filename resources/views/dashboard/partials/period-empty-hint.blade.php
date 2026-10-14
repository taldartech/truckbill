{{-- period: DashboardPeriod, lastTripDate: ?Carbon --}}
@php
    $lastTripDate = $lastTripDate ?? null;
    $wideKey = '30d';
    $wideWindow = \App\Services\Dashboard\DashboardPeriod::fromKey($wideKey);
    $canWiden = $period->key !== $wideKey && $lastTripDate && $lastTripDate->gte($wideWindow->start->copy()->startOfDay());
    $range = $period->start->isSameDay($period->end)
        ? $period->start->format('j M Y')
        : $period->start->format('j M').' – '.$period->end->format('j M Y');
@endphp

<div class="dashboard-period-hint">
    <i class="bi bi-calendar-x"></i>
    <div class="dashboard-period-hint__text">
        No trips with a lorry hire dated {{ $range }} ({{ Str::lower($period->label) }}).
        @if ($lastTripDate)
            Last trip was on {{ $lastTripDate->format('j M Y') }}.
        @else
            No trips have been booked yet.
        @endif
    </div>
    @if ($canWiden)
        <a href="{{ route('dashboard', ['period' => $wideKey]) }}" class="dashboard-link text-nowrap">
            Show {{ Str::lower(\App\Services\Dashboard\DashboardPeriod::LABELS[$wideKey]) }} <i class="bi bi-arrow-right-short"></i>
        </a>
    @endif
</div>
