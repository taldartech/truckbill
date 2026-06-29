@extends('layouts.app')

@section('content')
@include('subscription.partials.styles')

@php
    $status = $subscription?->displayStatus() ?? 'none';
    $catalogData = $catalog['catalog'] ?? [];
    $maxBranches = $subscription?->maxBranches() ?? 0;
    $maxUsersPerBranch = $subscription?->maxUsersInBranch() ?? ($catalogData['users_per_branch'] ?? 0);
    $maxUsersInBusiestBranch = $maxUsersPerBranch + (int) ($subscription?->addon_users ?? 0);
    $maxCompanyUsers = $subscription?->maxCompanyUsers() ?? 0;

    $branchUsagePercent = $maxBranches > 0 ? min(100, round(($usage['branches'] / $maxBranches) * 100)) : 0;
    $branchUserUsagePercent = $maxUsersInBusiestBranch > 0 ? min(100, round(($usage['max_users_in_any_branch'] / $maxUsersInBusiestBranch) * 100)) : 0;
    $totalUserUsagePercent = $maxCompanyUsers > 0 ? min(100, round(($usage['total_users'] / $maxCompanyUsers) * 100)) : 0;

    $maxLrCredits = (int) ($subscription?->included_lr_credits ?? 0) + (int) ($subscription?->addon_lr_credits ?? 0);
    $lrCreditsRemaining = (int) ($usage['lr_credits_remaining'] ?? 0);
    $lrCreditsUsagePercent = $maxLrCredits > 0 ? min(100, round(($lrCreditsRemaining / $maxLrCredits) * 100)) : 0;

    $usageBarClass = fn (int $percent) => $percent >= 90 ? 'danger' : ($percent >= 75 ? 'warning' : '');
    $lrCreditsBarClass = $lrCreditsUsagePercent <= 10 ? 'danger' : ($lrCreditsUsagePercent <= 25 ? 'warning' : '');
@endphp

<div class="sub-page sub-page--manage">
    <a href="{{ route('subscription.index') }}" class="sub-back">
        <i class="fas fa-arrow-left"></i> All companies
    </a>

    <div class="sub-hero">
        <div>
            <h1 class="sub-hero-title">{{ $company->name }}</h1>
            <p class="sub-hero-subtitle">Current plan details, usage, and purchase history.</p>
        </div>
        <div class="sub-hero-actions">
            <span class="sub-badge sub-badge-{{ $status }}">
                <i class="fas fa-circle" style="font-size: 0.45rem;"></i>
                {{ str_replace('_', ' ', $status) }}
            </span>
            <a href="{{ route('subscription.checkout.form', ['company_id' => $company->id, 'purchase_type' => 'renewal']) }}" class="sub-action-btn sub-action-btn-primary">
                <i class="fas fa-sync-alt"></i> Renew
            </a>
            @if ($isExpired)
                <span class="sub-action-btn sub-action-btn-secondary disabled" title="Add-ons require an active subscription">
                    <i class="fas fa-plus-circle"></i> Add-ons
                </span>
            @else
                <a href="{{ route('subscription.checkout.form', ['company_id' => $company->id, 'purchase_type' => 'addon']) }}" class="sub-action-btn sub-action-btn-secondary">
                    <i class="fas fa-plus-circle"></i> Add-ons
                </a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="sub-alert sub-alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="sub-alert sub-alert-danger">{{ session('error') }}</div>
    @endif
    @if ($catalog === null)
        <div class="sub-alert sub-alert-warning">Unable to load catalog pricing from admin. Plan details below reflect your subscription record only.</div>
    @endif

    <div class="sub-manage-grid">
        <div class="sub-card">
            <div class="sub-card-header sub-card-header-gradient">
                <div class="sub-card-label">Current plan</div>
                <h2 class="sub-card-title">TruckBill Broker</h2>
                <p class="sub-plan-meta mb-0">
                    {{ $subscription?->starts_on?->format('d M Y') ?? '—' }}
                    –
                    {{ $subscription?->ends_on?->format('d M Y') ?? '—' }}
                </p>
            </div>
            <div class="sub-card-body">
                <div class="sub-plan-grid">
                    <div class="sub-plan-item">
                        <div class="label">Included branches</div>
                        <div class="value">{{ $subscription?->included_branches ?? 0 }}</div>
                    </div>
                    <div class="sub-plan-item">
                        <div class="label">Add-on branches</div>
                        <div class="value">{{ $subscription?->addon_branches ?? 0 }}</div>
                    </div>
                    <div class="sub-plan-item">
                        <div class="label">Users per branch</div>
                        <div class="value">{{ $subscription?->users_per_branch ?? ($catalogData['users_per_branch'] ?? 0) }}</div>
                    </div>
                    <div class="sub-plan-item">
                        <div class="label">Add-on users</div>
                        <div class="value">{{ $subscription?->addon_users ?? 0 }}</div>
                    </div>
                    <div class="sub-plan-item">
                        <div class="label">LR credits remaining</div>
                        <div class="value">{{ number_format($subscription?->lr_credits_remaining ?? 0) }}</div>
                    </div>
                    <div class="sub-plan-item">
                        <div class="label">Included LR credits</div>
                        <div class="value">{{ number_format($subscription?->included_lr_credits ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sub-card">
            <div class="sub-card-header">
                <div class="sub-card-label">Live usage</div>
                <h2 class="sub-card-title mb-0">Against your limits</h2>
            </div>
            <div class="sub-card-body">
                <div class="sub-usage-list">
                    <div>
                        <div class="sub-usage-item-head">
                            <strong>Branches</strong>
                            <span>{{ $usage['branches'] }} / {{ $maxBranches ?: '—' }}</span>
                        </div>
                        <div class="sub-usage-bar">
                            <div class="sub-usage-bar-fill {{ $usageBarClass($branchUsagePercent) }}" style="width: {{ $branchUsagePercent }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="sub-usage-item-head">
                            <strong>Users in busiest branch</strong>
                            <span>{{ $usage['max_users_in_any_branch'] }} / {{ $maxUsersInBusiestBranch ?: '—' }}</span>
                        </div>
                        <div class="sub-usage-bar">
                            <div class="sub-usage-bar-fill {{ $usageBarClass($branchUserUsagePercent) }}" style="width: {{ $branchUserUsagePercent }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="sub-usage-item-head">
                            <strong>Total company users</strong>
                            <span>{{ $usage['total_users'] }} / {{ $maxCompanyUsers ?: '—' }}</span>
                        </div>
                        <div class="sub-usage-bar">
                            <div class="sub-usage-bar-fill {{ $usageBarClass($totalUserUsagePercent) }}" style="width: {{ $totalUserUsagePercent }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="sub-usage-item-head">
                            <strong>LR credits remaining</strong>
                            <span>{{ number_format($lrCreditsRemaining) }} / {{ $maxLrCredits ? number_format($maxLrCredits) : '—' }}</span>
                        </div>
                        <div class="sub-usage-bar">
                            <div class="sub-usage-bar-fill {{ $lrCreditsBarClass }}" style="width: {{ $lrCreditsUsagePercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($upcoming)
            <div class="sub-card sub-manage-span-2">
                <div class="sub-card-header">
                    <div class="sub-card-label">Scheduled</div>
                    <h2 class="sub-card-title mb-0">Upcoming renewal</h2>
                </div>
                <div class="sub-card-body">
                    <p class="sub-plan-meta mb-3">
                        Paid renewal queued to activate automatically when the current plan ends
                        @if ($subscription?->ends_on)
                            ({{ $subscription->ends_on->format('d M Y') }}).
                        @else
                            .
                        @endif
                        The new term will run for one year from that date.
                    </p>
                    <div class="sub-plan-grid">
                        <div class="sub-plan-item">
                            <div class="label">Branches</div>
                            <div class="value">{{ $upcoming->included_branches + $upcoming->addon_branches }}</div>
                        </div>
                        <div class="sub-plan-item">
                            <div class="label">Add-on users</div>
                            <div class="value">{{ $upcoming->addon_users }}</div>
                        </div>
                        <div class="sub-plan-item">
                            <div class="label">LR credits</div>
                            <div class="value">{{ number_format($upcoming->included_lr_credits + $upcoming->addon_lr_credits) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="sub-card sub-manage-span-2">
            <div class="sub-card-header d-flex align-items-center justify-content-between">
                <div>
                    <div class="sub-card-label">Billing</div>
                    <h2 class="sub-card-title mb-0">Purchase history</h2>
                </div>
                <a href="{{ route('subscription.history', ['company_id' => $company->id]) }}" class="sub-btn-outline">
                    View all
                </a>
            </div>
            <div class="sub-table-wrap">
                @if ($payments->isEmpty())
                    <div class="sub-empty">
                        <div><i class="fas fa-receipt"></i></div>
                        <p class="mb-0">No completed payments yet.</p>
                    </div>
                @else
                    <table class="sub-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>{{ $payment->created_at->format('d M Y') }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $payment->type) }}</td>
                                    <td class="text-end fw-bold">₹{{ number_format($payment->total_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
