@php
    $actions = $actions ?? [];
    $title = $title ?? 'What needs your attention';
    $visibleCount = $visibleCount ?? 5;
    $visibleActions = array_slice($actions, 0, $visibleCount);
    $moreActions = array_slice($actions, $visibleCount);
    $modalId = 'dashboardActionsModal';
@endphp

<section class="dashboard-panel dashboard-actions" aria-label="{{ $title }}">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-lightning-charge-fill"></i> {{ $title }}</h2>
        @if (count($actions) > 0)
            <span class="dashboard-actions__count text-nowrap">{{ count($actions) }} to act on</span>
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
                <button type="button" class="dashboard-actions__more-btn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                    Show {{ count($moreActions) }} more <i class="bi bi-box-arrow-up-right"></i>
                </button>
            @endif
        @endif
    </div>
</section>

@if (count($moreActions) > 0)
    @push('scripts')
        <div class="modal fade dashboard-actions-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $modalId }}Label">
                            <i class="bi bi-lightning-charge-fill"></i> {{ $title }}
                            <span class="dashboard-actions__count text-nowrap">{{ count($moreActions) }} more</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="dashboard-actions__list">
                            @foreach ($moreActions as $action)
                                @include('dashboard.partials.action-item', ['action' => $action])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endif
