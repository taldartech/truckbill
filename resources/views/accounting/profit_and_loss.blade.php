@extends('layouts.app')

@section('title', 'Profit & Loss Statement - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.profit-and-loss') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm flex-grow-1">
                        <i class="bi bi-search"></i> Generate P&L
                    </button>
                    <a href="{{ route('accounting.profit-and-loss') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- P&L Metric Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="background: var(--brand-gradient); border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Freight Revenue (Direct)</div>
                        <h3 class="fw-bold mb-0 text-white">₹{{ number_format($pnl['total_direct_income'], 2) }}</h3>
                    </div>
                    <i class="bi bi-truck fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="background: var(--brand-gradient-info); border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Gross Transport Profit</div>
                        <h3 class="fw-bold mb-0 text-white">₹{{ number_format($pnl['gross_profit'], 2) }}</h3>
                    </div>
                    <i class="bi bi-bar-chart-line fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @php
                $netGrad = $pnl['net_profit'] >= 0 ? 'var(--brand-gradient-success)' : 'var(--brand-gradient-danger)';
            @endphp
            <div class="card border-0 shadow-sm p-3" style="background: {{ $netGrad }}; border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Net Operating {{ $pnl['net_profit'] >= 0 ? 'Profit' : 'Loss' }}</div>
                        <h3 class="fw-bold mb-0 text-white">₹{{ number_format($pnl['net_profit'], 2) }}</h3>
                    </div>
                    <i class="bi bi-wallet2 fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- P&L Double Column Statement Card -->
    <div class="card app-index-list-card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-graph-up me-2"></i>Trading & Operating Profit and Loss Account
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <button onclick="window.print()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print Statement
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- 1. TRADING ACCOUNT SECTION -->
            <div class="row g-0 border-bottom">
                <!-- Left: Direct Expenses (Dr) -->
                <div class="col-md-6 border-end">
                    <div class="p-3 border-bottom fw-bold text-uppercase small" style="background-color: var(--brand-table-header-bg); color: var(--brand-danger);">
                        <i class="bi bi-dash-circle me-1"></i>Trading Account - Direct Costs (Dr)
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush mb-2">
                            @forelse($pnl['direct_expenses'] as $exp)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <a href="{{ route('accounting.ledger-statement', $exp['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Ledger">
                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $exp['name'] }}
                                </a>
                                <span class="fw-semibold text-dark">₹{{ number_format($exp['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No direct expenses recorded.</li>
                            @endforelse

                            @if($pnl['gross_profit'] >= 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mt-2" style="background: var(--brand-soft-success); color: var(--brand-success);">
                                <span><i class="bi bi-arrow-down-right-circle me-1"></i>To Gross Profit c/d</span>
                                <span>₹{{ number_format($pnl['gross_profit'], 2) }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Trading Total (Dr):</span>
                        <span style="color: var(--brand-text-primary);">₹{{ number_format($pnl['trading_total'], 2) }}</span>
                    </div>
                </div>

                <!-- Right: Direct Incomes (Cr) -->
                <div class="col-md-6">
                    <div class="p-3 border-bottom fw-bold text-uppercase small" style="background-color: var(--brand-table-header-bg); color: var(--brand-success);">
                        <i class="bi bi-plus-circle me-1"></i>Trading Account - Direct Revenue (Cr)
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush mb-2">
                            @forelse($pnl['direct_incomes'] as $inc)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <a href="{{ route('accounting.ledger-statement', $inc['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Ledger">
                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $inc['name'] }}
                                </a>
                                <span class="fw-semibold text-dark">₹{{ number_format($inc['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No direct revenue recorded.</li>
                            @endforelse

                            @if($pnl['gross_profit'] < 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mt-2" style="background: var(--brand-soft-danger); color: var(--brand-danger);">
                                <span><i class="bi bi-arrow-down-right-circle me-1"></i>By Gross Loss c/d</span>
                                <span>₹{{ number_format(abs($pnl['gross_profit']), 2) }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Trading Total (Cr):</span>
                        <span style="color: var(--brand-text-primary);">₹{{ number_format($pnl['trading_total'], 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. PROFIT & LOSS ACCOUNT SECTION -->
            <div class="row g-0">
                <!-- Left: Indirect Expenses & Net Profit (Dr) -->
                <div class="col-md-6 border-end">
                    <div class="p-3 border-bottom fw-bold text-uppercase small" style="background-color: var(--brand-table-header-bg); color: var(--brand-danger);">
                        <i class="bi bi-receipt me-1"></i>Profit & Loss - Overheads & Net Profit (Dr)
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush mb-2">
                            @if($pnl['gross_profit'] < 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mb-2" style="background: var(--brand-soft-danger); color: var(--brand-danger);">
                                <span><i class="bi bi-arrow-return-right me-1"></i>To Gross Loss b/d</span>
                                <span>₹{{ number_format(abs($pnl['gross_profit']), 2) }}</span>
                            </li>
                            @endif

                            @forelse($pnl['indirect_expenses'] as $exp)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <a href="{{ route('accounting.ledger-statement', $exp['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Ledger">
                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $exp['name'] }}
                                </a>
                                <span class="fw-semibold text-dark">₹{{ number_format($exp['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No indirect expenses recorded.</li>
                            @endforelse

                            @if($pnl['net_profit'] >= 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mt-3" style="background: var(--brand-soft-success); color: var(--brand-success); border: 1px solid rgba(34,197,94,0.3) !important;">
                                <span><i class="bi bi-check2-circle me-1"></i>To Net Profit (Transferred to Capital A/c)</span>
                                <span>₹{{ number_format($pnl['net_profit'], 2) }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Total (Dr):</span>
                        <span style="color: var(--brand-primary-dark);">₹{{ number_format($pnl['pnl_total'], 2) }}</span>
                    </div>
                </div>

                <!-- Right: Indirect Incomes & Net Loss (Cr) -->
                <div class="col-md-6">
                    <div class="p-3 border-bottom fw-bold text-uppercase small" style="background-color: var(--brand-table-header-bg); color: var(--brand-success);">
                        <i class="bi bi-piggy-bank me-1"></i>Profit & Loss - Incomes & Net Loss (Cr)
                    </div>
                    <div class="p-3">
                        <ul class="list-group list-group-flush mb-2">
                            @if($pnl['gross_profit'] >= 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mb-2" style="background: var(--brand-soft-success); color: var(--brand-success);">
                                <span><i class="bi bi-arrow-return-right me-1"></i>By Gross Profit b/d</span>
                                <span>₹{{ number_format($pnl['gross_profit'], 2) }}</span>
                            </li>
                            @endif

                            @forelse($pnl['indirect_incomes'] as $inc)
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <a href="{{ route('accounting.ledger-statement', $inc['id']) }}" class="text-decoration-none text-dark fw-medium" title="View Ledger">
                                    <i class="bi bi-file-earmark-text text-muted me-1 small"></i>{{ $inc['name'] }}
                                </a>
                                <span class="fw-semibold text-dark">₹{{ number_format($inc['amount'], 2) }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted small border-0">No indirect income recorded.</li>
                            @endforelse

                            @if($pnl['net_profit'] < 0)
                            <li class="list-group-item d-flex justify-content-between px-2 py-2 fw-bold rounded mt-3" style="background: var(--brand-soft-danger); color: var(--brand-danger); border: 1px solid rgba(239,68,68,0.3) !important;">
                                <span><i class="bi bi-exclamation-circle me-1"></i>By Net Loss (Transferred to Capital A/c)</span>
                                <span>₹{{ number_format(abs($pnl['net_profit']), 2) }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-between fw-bold fs-6" style="background-color: var(--brand-table-header-bg);">
                        <span class="text-uppercase small text-muted">Total (Cr):</span>
                        <span style="color: var(--brand-primary-dark);">₹{{ number_format($pnl['pnl_total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
