@php
    $actions = $actions ?? [];
    $title = $title ?? 'What needs your attention';
    $visibleCount = $visibleCount ?? 5;
    $visibleActions = array_slice($actions, 0, $visibleCount);
    $moreActions = array_slice($actions, $visibleCount);
@endphp

<section class="dashboard-panel dashboard-actions" aria-label="{{ $title }}">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-lightning-charge-fill"></i> {{ $title }}</h2>
        @if (count($actions) > 0)
            <span class="dashboard-actions__count">{{ count($actions) }} to act on</span>
        @endif
    </div>
    <div class="dashboard-panel__body">
        @if (count($actions) === 0)
            <div class="dashboard-all-clear">
                <i class="bi bi-check2-circle"></i>
                <div>
                    <strong>All clear.</strong>
                    Nothing overdue, unbilled or at risk right now.
                </div>
            </div>
        @else
            <div class="dashboard-actions__list">
                @foreach ($visibleActions as $action)
                    @include('dashboard.partials.action-item', ['action' => $action])
                @endforeach
            </div>
            @if (count($moreActions) > 0)
                <details class="dashboard-actions__more">
                    <summary>Show {{ count($moreActions) }} more</summary>
                    <div class="dashboard-actions__list">
                        @foreach ($moreActions as $action)
                            @include('dashboard.partials.action-item', ['action' => $action])
                        @endforeach
                    </div>
                </details>
            @endif
        @endif
    </div>
</section>
