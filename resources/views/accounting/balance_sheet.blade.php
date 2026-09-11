@extends('layouts.app')

@section('title', 'Balance Sheet - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.balance-sheet') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm w-100">
                        <i class="bi bi-filter"></i> Apply Date
                    </button>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-md-end pt-2 pt-md-0">
                    @if(abs($balanceSheet['difference']) < 0.01)
                        <div class="d-flex align-items-center px-3 py-1 rounded border" style="background: var(--brand-soft-success); border-color: rgba(34,197,94,0.3) !important;">
                            <i class="bi bi-check-circle-fill me-2" style="color: var(--brand-success);"></i>
                            <span class="fw-semibold small" style="color: var(--brand-success);">Balance Sheet Balanced (Assets = Liabilities)</span>
                        </div>
                    @else
                        <div class="d-flex align-items-center px-3 py-1 rounded border" style="background: var(--brand-soft-warning); border-color: rgba(245,158,11,0.3) !important;">
                            <i class="bi bi-exclamation-circle-fill me-2" style="color: var(--brand-warning);"></i>
                            <span class="fw-semibold small text-dark">Difference: ₹{{ number_format($balanceSheet['difference'], 2) }}</span>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Balance Sheet 2-Column Main Card -->
    <div class="card app-index-list-card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-briefcase me-2"></i>Statement of Capital, Liabilities & Assets
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <button onclick="window.print()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print Statement
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="row g-0">
                <!-- Left: Liabilities & Equity -->
                <div class="col-md-6 border-end">
                    <div class="p-3 border-bottom fw-bold text-uppercase small text-dark d-flex justify-content-between" style="background-color: var(--brand-table-header-bg);">
                        <span><i class="bi bi-shield-check me-1" style="color: var(--brand-accent-blue);"></i>Capital & Liabilities</span>
                        <span>Amount (₹)</span>
                    </div>
                    <div class="p-4">
                        <ul class="list-group list-group-flush mb-3">
                            @forelse($balanceSheet['liabilities'] as $liab)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <span class="fw-medium text-dark">{{ $liab['name'] }}</span>
                                <span class="fw-semibold text-dark">₹{{ number_format($liab['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No liabilities or equity recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Total Liabilities & Equity:</span>
                        <span style="color: var(--brand-primary-dark);">₹{{ number_format($balanceSheet['total_liabilities'], 2) }}</span>
                    </div>
                </div>

                <!-- Right: Assets -->
                <div class="col-md-6">
                    <div class="p-3 border-bottom fw-bold text-uppercase small text-dark d-flex justify-content-between" style="background-color: var(--brand-table-header-bg);">
                        <span><i class="bi bi-wallet-fill me-1" style="color: var(--brand-primary);"></i>Assets & Receivables</span>
                        <span>Amount (₹)</span>
                    </div>
                    <div class="p-4">
                        <ul class="list-group list-group-flush mb-3">
                            @forelse($balanceSheet['assets'] as $asset)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <span class="fw-medium text-dark">{{ $asset['name'] }}</span>
                                <span class="fw-semibold text-dark">₹{{ number_format($asset['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No assets recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Total Assets:</span>
                        <span style="color: var(--brand-primary-dark);">₹{{ number_format($balanceSheet['total_assets'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
