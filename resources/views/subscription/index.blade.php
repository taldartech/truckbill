@extends('layouts.app')

@section('content')
@php
    $activeCount = $companies->filter(fn ($c) => $c->subscription?->isActive())->count();
    $expiredCount = $companies->count() - $activeCount;
@endphp
<div class="container mt-4 app-index-page app-index-page--standard">
    @if(session('success'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
            </span>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="app-index-stats">
        <div class="app-index-stat app-index-stat--success">
            <span class="app-index-stat__icon"><i class="bi bi-check-circle"></i></span>
            <div>
                <p class="app-index-stat__label">Active plans</p>
                <div class="app-index-stat__value">{{ $activeCount }}</div>
            </div>
        </div>
        <div class="app-index-stat app-index-stat--danger">
            <span class="app-index-stat__icon"><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <p class="app-index-stat__label">Needs attention</p>
                <div class="app-index-stat__value">{{ $expiredCount }}</div>
            </div>
        </div>
        <div class="app-index-stat app-index-stat--info">
            <span class="app-index-stat__icon"><i class="bi bi-buildings"></i></span>
            <div>
                <p class="app-index-stat__label">Total companies</p>
                <div class="app-index-stat__value">{{ $companies->count() }}</div>
            </div>
        </div>
    </div>

    <div class="card app-index-list-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Subscriptions</h3>
            <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('subscription.history') }}" class="btn app-index-header-btn" aria-label="Payment history">
                    <i class="bi bi-receipt"></i> Payment history
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Valid until</th>
                            <th>LR credits</th>
                            <th class="app-index-actions-cell text-end" style="width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            @php
                                $sub = $company->subscription;
                                $status = $sub?->displayStatus() ?? 'none';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold text-truncate" style="max-width: 280px;" title="{{ $company->name }}">
                                        {{ $company->name }}
                                    </div>
                                </td>
                                <td>
                                    <span class="app-status-badge app-status-badge-{{ $status }}">
                                        {{ str_replace('_', ' ', $status) }}
                                    </span>
                                </td>
                                <td>{{ $sub?->ends_on?->format('d M Y') ?? '—' }}</td>
                                <td>{{ number_format($sub?->lr_credits_remaining ?? 0) }}</td>
                                <td class="app-index-actions-cell text-end">
                                    <a href="{{ route('subscription.show', ['company_id' => $company->id]) }}" class="btn btn-sm btn-brand-primary">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr class="app-index-empty-row">
                                <td colspan="5" class="text-center text-muted py-4">No companies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
