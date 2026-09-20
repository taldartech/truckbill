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
                    <div class="p-3">
                        @forelse($balanceSheet['liability_groups'] as $pGroup)
                            <div class="mb-3 border rounded p-2" style="border-color: var(--brand-border) !important; background: #fafbfc;">
                                <div class="d-flex justify-content-between align-items-center pb-2 border-bottom fw-bold text-dark">
                                    <span class="fs-6"><i class="bi bi-folder2-open text-warning me-1"></i>{{ $pGroup['name'] }}</span>
                                    <span class="fw-bold fs-6" style="color: var(--brand-primary-dark);">₹{{ number_format($pGroup['subtotal'], 2) }}</span>
                                </div>

                                <!-- Direct ledgers in primary group -->
                                @if(!empty($pGroup['direct_ledgers']))
                                    <ul class="list-group list-group-flush mb-2 ps-2">
                                        @foreach($pGroup['direct_ledgers'] as $dl)
                                        <li class="list-group-item d-flex justify-content-between px-1 py-1 border-0 bg-transparent">
                                            @if($dl['id'])
                                                <a href="{{ route('accounting.ledger-statement', $dl['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Statement">
                                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $dl['name'] }}
                                                </a>
                                            @else
                                                <span class="fw-bold text-dark"><i class="bi bi-pie-chart text-info me-1"></i>{{ $dl['name'] }}</span>
                                            @endif
                                            <span class="fw-semibold text-dark">₹{{ number_format($dl['amount'], 2) }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <!-- Sub groups -->
                                @foreach($pGroup['sub_groups'] as $subG)
                                    <div class="mt-2 ms-2 ps-2 border-start border-2" style="border-color: var(--brand-border) !important;">
                                        <div class="d-flex justify-content-between align-items-center py-1 fw-semibold small text-muted">
                                            <span><i class="bi bi-diagram-2 me-1"></i>{{ $subG['name'] }}</span>
                                            <span class="badge bg-light text-dark border">₹{{ number_format($subG['subtotal'], 2) }}</span>
                                        </div>
                                        <ul class="list-group list-group-flush ps-2">
                                            @foreach($subG['ledgers'] as $l)
                                            <li class="list-group-item d-flex justify-content-between px-1 py-1 border-0 bg-transparent small">
                                                <a href="{{ route('accounting.ledger-statement', $l['id']) }}" class="text-decoration-none text-dark" title="View Statement">
                                                    {{ $l['name'] }}
                                                </a>
                                                <span class="fw-medium text-dark">₹{{ number_format($l['amount'], 2) }}</span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="text-muted small py-4 text-center">No liabilities or equity recorded.</div>
                        @endforelse
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
                    <div class="p-3">
                        @forelse($balanceSheet['asset_groups'] as $pGroup)
                            <div class="mb-3 border rounded p-2" style="border-color: var(--brand-border) !important; background: #fafbfc;">
                                <div class="d-flex justify-content-between align-items-center pb-2 border-bottom fw-bold text-dark">
                                    <span class="fs-6"><i class="bi bi-folder2-open text-warning me-1"></i>{{ $pGroup['name'] }}</span>
                                    <span class="fw-bold fs-6" style="color: var(--brand-primary-dark);">₹{{ number_format($pGroup['subtotal'], 2) }}</span>
                                </div>

                                <!-- Direct ledgers in primary group -->
                                @if(!empty($pGroup['direct_ledgers']))
                                    <ul class="list-group list-group-flush mb-2 ps-2">
                                        @foreach($pGroup['direct_ledgers'] as $dl)
                                        <li class="list-group-item d-flex justify-content-between px-1 py-1 border-0 bg-transparent">
                                            <div>
                                                @if($dl['id'])
                                                    <a href="{{ route('accounting.ledger-statement', $dl['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Statement">
                                                        <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $dl['name'] }}
                                                    </a>
                                                @else
                                                    <span class="fw-bold text-dark">{{ $dl['name'] }}</span>
                                                @endif
                                                @if(!empty($dl['is_negative_cash']))
                                                    <span class="badge bg-danger ms-1">Negative Cash</span>
                                                @endif
                                            </div>
                                            <span class="fw-semibold {{ !empty($dl['is_negative_cash']) ? 'text-danger fw-bold' : 'text-dark' }}">
                                                ₹{{ number_format($dl['amount'], 2) }}
                                            </span>
                                        </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <!-- Sub groups -->
                                @foreach($pGroup['sub_groups'] as $subG)
                                    <div class="mt-2 ms-2 ps-2 border-start border-2" style="border-color: var(--brand-border) !important;">
                                        <div class="d-flex justify-content-between align-items-center py-1 fw-semibold small text-muted">
                                            <span><i class="bi bi-diagram-2 me-1"></i>{{ $subG['name'] }}</span>
                                            <span class="badge bg-light text-dark border">₹{{ number_format($subG['subtotal'], 2) }}</span>
                                        </div>
                                        <ul class="list-group list-group-flush ps-2">
                                            @foreach($subG['ledgers'] as $l)
                                            <li class="list-group-item d-flex justify-content-between px-1 py-1 border-0 bg-transparent small">
                                                <div>
                                                    <a href="{{ route('accounting.ledger-statement', $l['id']) }}" class="text-decoration-none text-dark" title="View Statement">
                                                        {{ $l['name'] }}
                                                    </a>
                                                    @if(!empty($l['is_negative_cash']))
                                                        <span class="badge bg-danger ms-1">Negative Cash</span>
                                                    @endif
                                                </div>
                                                <span class="fw-medium {{ !empty($l['is_negative_cash']) ? 'text-danger fw-bold' : 'text-dark' }}">
                                                    ₹{{ number_format($l['amount'], 2) }}
                                                </span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="text-muted small py-4 text-center">No assets recorded.</div>
                        @endforelse
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
