@extends('layouts.app')

@section('content')
@include('subscription.partials.styles')

@php
    $totalPaid = $payments->sum('total_amount');
@endphp

<div class="sub-page">
    <a href="{{ route('subscription.index') }}" class="sub-back">
        <i class="fas fa-arrow-left"></i> Subscriptions
    </a>

    <div class="sub-hero">
        <div>
            <h1 class="sub-hero-title"><i class="fas fa-receipt text-brand-primary me-2"></i>Payment history</h1>
            <p class="sub-hero-subtitle">Review past subscription payments and their status.</p>
        </div>
        <span class="sub-pill"><i class="fas fa-indian-rupee-sign"></i> ₹{{ number_format($totalPaid, 2) }} total</span>
    </div>

    <div class="sub-card">
        <div class="sub-card-header">
            <div class="sub-card-label">Transactions</div>
            <h2 class="sub-card-title mb-0">All payments</h2>
        </div>
        <div class="sub-table-wrap">
            @if ($payments->isEmpty())
                <div class="sub-empty">
                    <div><i class="fas fa-receipt"></i></div>
                    <p class="mb-0">No payments recorded yet.</p>
                </div>
            @else
                <table class="sub-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            @php
                                $statusClass = match ($payment->status) {
                                    'paid', 'success', 'completed' => 'sub-badge-active',
                                    'failed', 'cancelled' => 'sub-badge-expired',
                                    default => 'sub-badge-none',
                                };
                            @endphp
                            <tr>
                                <td>{{ $payment->created_at->format('d M Y') }}</td>
                                <td><span class="sub-company-name">{{ $payment->company->name }}</span></td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $payment->type) }}</td>
                                <td>
                                    <span class="sub-badge {{ $statusClass }}">
                                        {{ str_replace('_', ' ', $payment->status) }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold">₹{{ number_format($payment->total_amount, 2, '.', ',') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if ($payments->hasPages())
            <div class="sub-card-body pt-0">
                {{ $payments->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
