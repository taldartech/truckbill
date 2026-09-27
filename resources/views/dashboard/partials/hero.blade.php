@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $userName = auth()->user()->name ?? '';
    $period = $dashboardPeriod ?? request('period', 'month');
    $periods = [
        '7d' => '7 days',
        '30d' => '30 days',
        'month' => 'This month',
    ];
@endphp

<header class="dashboard-page__hero">
    <div class="dashboard-page__hero-content">
        <div class="dashboard-page__live-badge">
            <span class="dashboard-page__live-dot"></span>
            <span class="dashboard-page__live-text">System Live</span>
        </div>
        <h1 class="dashboard-page__title">
            {{ $greeting }}{{ $userName ? ', '.$userName : '' }}!
        </h1>
        <p class="dashboard-page__subtitle">
            {{ $subtitle ?? 'Overview of bilty, hire, invoices, and compliance alerts.' }}
        </p>
    </div>

    <div class="dashboard-page__hero-actions">
        @if (! empty($quickActions))
            <div class="dashboard-page__quick-actions">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['url'] }}" class="dashboard-page__quick-action text-nowrap">
                        <i class="{{ $action['icon'] }}"></i>
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        @if (! empty($showPeriodFilter))
            <div class="dashboard-page__period" role="group" aria-label="Date period">
                @foreach ($periods as $key => $label)
                    <a href="{{ route('dashboard', ['period' => $key]) }}"
                        class="dashboard-page__period-btn text-nowrap {{ $period === $key ? 'is-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        @else
            <div class="dashboard-page__today text-nowrap">
                <i class="bi bi-calendar3"></i>
                {{ now()->format('d M, Y') }}
            </div>
        @endif
    </div>
</header>
