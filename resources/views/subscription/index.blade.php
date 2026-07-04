@extends('layouts.app')

@section('content')
@include('subscription.partials.styles')

@php
    $activeCount = $companies->filter(fn ($c) => $c->subscription?->isActive())->count();
    $expiredCount = $companies->count() - $activeCount;
@endphp

<div class="sub-page">
    <div class="sub-hero">
        <div>
            <h1 class="sub-hero-title"><i class="fas fa-crown text-brand-primary me-2"></i>Subscriptions</h1>
            <p class="sub-hero-subtitle">Manage plans, renewals, and add-ons across your companies.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="sub-pill"><i class="fas fa-building"></i> {{ $companies->count() }} companies</span>
            <a href="{{ route('subscription.history') }}" class="sub-btn-outline">
                <i class="fas fa-receipt"></i> Payment history
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="sub-alert sub-alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="sub-alert sub-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="sub-summary-cards">
        <div class="sub-summary-card">
            <div class="label">Active plans</div>
            <div class="value text-success">{{ $activeCount }}</div>
        </div>
        <div class="sub-summary-card">
            <div class="label">Needs attention</div>
            <div class="value" style="color: var(--brand-danger);">{{ $expiredCount }}</div>
        </div>
        <div class="sub-summary-card">
            <div class="label">Total companies</div>
            <div class="value">{{ $companies->count() }}</div>
        </div>
    </div>

    <div class="sub-card">
        <div class="sub-card-header d-flex align-items-center justify-content-between">
            <div>
                <div class="sub-card-label">Your portfolio</div>
                <h2 class="sub-card-title mb-0">Company subscriptions</h2>
            </div>
        </div>
        <div class="sub-table-wrap">
            @if ($companies->isEmpty())
                <div class="sub-empty">
                    <div><i class="fas fa-building"></i></div>
                    <p class="mb-0">No companies found.</p>
                </div>
            @else
                <table class="sub-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Valid until</th>
                            <th>LR credits</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            @php
                                $sub = $company->subscription;
                                $status = $sub?->displayStatus() ?? 'none';
                            @endphp
                            <tr>
                                <td>
                                    <div class="sub-company-name">{{ $company->name }}</div>
                                </td>
                                <td>
                                    <span class="sub-badge sub-badge-{{ $status }}">
                                        <i class="fas fa-circle" style="font-size: 0.45rem;"></i>
                                        {{ str_replace('_', ' ', $status) }}
                                    </span>
                                </td>
                                <td>{{ $sub?->ends_on?->format('d M Y') ?? '—' }}</td>
                                <td>{{ number_format($sub?->lr_credits_remaining ?? 0) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('subscription.show', ['company_id' => $company->id]) }}" class="sub-btn-outline">
                                        Manage <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
