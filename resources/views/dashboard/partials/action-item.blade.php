<article class="dashboard-action dashboard-action--{{ $action['tone'] }}">
    <span class="dashboard-action__icon"><i class="{{ $action['icon'] }}"></i></span>
    <div class="dashboard-action__content">
        <h3 class="dashboard-action__title">{{ $action['title'] }}</h3>
        <p class="dashboard-action__detail">{{ $action['detail'] }}</p>
    </div>
    @if (! empty($action['url']))
        <a href="{{ $action['url'] }}" class="dashboard-action__cta text-nowrap">
            {{ $action['cta'] }} <i class="bi bi-arrow-right-short"></i>
        </a>
    @endif
</article>
