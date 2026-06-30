@if (!empty($subscriptionAccess) && in_array($subscriptionAccess['access_mode'] ?? '', ['grace', 'locked'], true))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        @if (($subscriptionAccess['access_mode'] ?? '') === 'grace')
            Your subscription has expired.
            @if (!empty($subscriptionAccess['grace_ends_at']))
                You have view-only access until {{ \Carbon\Carbon::parse($subscriptionAccess['grace_ends_at'])->format('d M, Y') }}.
            @else
                You have view-only access during the grace period.
            @endif
            Please renew to restore full access.
        @else
            Your subscription has expired. Please renew to continue using the application.
            @if (auth()->user()?->userRole?->name === 'super_admin')
                <a href="{{ route('subscription.index') }}" class="alert-link">Renew subscription</a>
            @endif
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
