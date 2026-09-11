@extends('layouts.app')

@section('title', 'Trial Balance - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.trial-balance') }}" class="row g-2 align-items-end">
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
                    @if($trialBalance['is_balanced'])
                        <div class="d-flex align-items-center px-3 py-1 rounded border" style="background: var(--brand-soft-success); border-color: rgba(34,197,94,0.3) !important;">
                            <i class="bi bi-check-circle-fill me-2" style="color: var(--brand-success);"></i>
                            <span class="fw-semibold small" style="color: var(--brand-success);">Trial Balance Matched (Debits = Credits)</span>
                        </div>
                    @else
                        <div class="d-flex align-items-center px-3 py-1 rounded border" style="background: var(--brand-soft-danger); border-color: rgba(239,68,68,0.3) !important;">
                            <i class="bi bi-exclamation-triangle-fill me-2" style="color: var(--brand-danger);"></i>
                            <span class="fw-semibold small" style="color: var(--brand-danger);">Unbalanced Difference: ₹{{ number_format($trialBalance['difference'], 2) }}</span>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card app-index-list-card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-scale me-2"></i>Trial Balance Summary (As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }})
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <button onclick="window.print()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table app-index-table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Particulars / Account Group / Ledger</th>
                            <th class="text-end" style="width: 220px;">Debit Balance (₹)</th>
                            <th class="text-end pe-4" style="width: 220px;">Credit Balance (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trialBalance['groups'] as $g)
                        <!-- Group Header Row -->
                        <tr style="background-color: var(--brand-table-header-bg);">
                            <td class="ps-4 fw-bold text-dark">
                                <i class="bi bi-folder2-open me-2" style="color: var(--brand-accent-blue);"></i>{{ $g['group_name'] }}
                                <span class="badge ms-2 small" style="background-color: var(--brand-soft-primary); color: var(--brand-primary); font-weight: 600;">
                                    {{ ucfirst($g['primary_type']) }}
                                </span>
                            </td>
                            <td class="text-end fw-bold" style="color: var(--brand-success);">
                                {{ $g['total_debit'] > 0 ? '₹' . number_format($g['total_debit'], 2) : '-' }}
                            </td>
                            <td class="text-end pe-4 fw-bold" style="color: var(--brand-danger);">
                                {{ $g['total_credit'] > 0 ? '₹' . number_format($g['total_credit'], 2) : '-' }}
                            </td>
                        </tr>

                        <!-- Child Ledgers -->
                        @foreach($g['ledgers'] as $ledger)
                        <tr>
                            <td class="ps-5">
                                <a href="{{ route('accounting.ledger-statement', $ledger['id']) }}" class="text-decoration-none fw-medium text-dark">
                                    <i class="bi bi-card-text me-2 text-muted"></i>{{ $ledger['name'] }}
                                </a>
                            </td>
                            <td class="text-end" style="color: var(--brand-success);">
                                {{ $ledger['debit'] > 0 ? '₹' . number_format($ledger['debit'], 2) : '-' }}
                            </td>
                            <td class="text-end pe-4" style="color: var(--brand-danger);">
                                {{ $ledger['credit'] > 0 ? '₹' . number_format($ledger['credit'], 2) : '-' }}
                            </td>
                        </tr>
                        @endforeach

                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                No active account balances recorded up to {{ $asOfDate }}.
                            </td>
                        </tr>
                        @endforelse

                        <!-- Grand Totals -->
                        <tr class="fw-bold fs-6" style="background-color: var(--brand-table-header-bg); border-top: 2px solid var(--brand-border);">
                            <td class="ps-4 text-uppercase small text-muted">Grand Total:</td>
                            <td class="text-end" style="color: var(--brand-success);">₹{{ number_format($trialBalance['total_debit'], 2) }}</td>
                            <td class="text-end pe-4" style="color: var(--brand-danger);">₹{{ number_format($trialBalance['total_credit'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
