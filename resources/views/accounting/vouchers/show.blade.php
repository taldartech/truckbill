@extends('layouts.app')

@section('title', 'Voucher #' . $voucher->voucher_number . ' - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Main Card -->
    <div class="card app-index-list-card shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-receipt me-2"></i>Accounting Voucher #{{ $voucher->voucher_number }}
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <button onclick="window.print()" class="btn app-index-header-btn">
                    <i class="bi bi-printer"></i> Print Voucher
                </button>
                <a href="{{ route('accounting.vouchers.index') }}" class="btn app-index-header-btn">
                    <i class="bi bi-arrow-left"></i> Back to Vouchers
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <!-- Summary Info Row -->
            <div class="row g-3 p-3 rounded mb-4" style="background-color: var(--brand-bg); border: 1px solid var(--brand-border);">
                <div class="col-md-3 col-sm-6">
                    <small class="text-muted text-uppercase fw-semibold d-block">Voucher Type</small>
                    @php
                        $badgeStyle = match(strtolower($voucher->voucher_type)) {
                            'sales' => 'background-color: var(--brand-soft-success); color: var(--brand-success);',
                            'purchase' => 'background-color: var(--brand-soft-danger); color: var(--brand-danger);',
                            'payment' => 'background-color: var(--brand-soft-warning); color: var(--brand-warning);',
                            'receipt' => 'background-color: var(--brand-soft-info); color: var(--brand-info);',
                            'contra' => 'background-color: var(--brand-soft-secondary); color: var(--brand-text-muted);',
                            default => 'background-color: var(--brand-soft-primary); color: var(--brand-primary);'
                        };
                    @endphp
                    <span class="badge border mt-1" style="{{ $badgeStyle }} font-size: 0.875rem; font-weight: 600;">
                        {{ ucfirst($voucher->voucher_type) }}
                    </span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-muted text-uppercase fw-semibold d-block">Voucher Date</small>
                    <span class="fw-bold text-dark fs-6">{{ $voucher->voucher_date->format('d-m-Y') }}</span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-muted text-uppercase fw-semibold d-block">Reference Number</small>
                    <span class="fw-semibold text-dark fs-6">{{ $voucher->reference_number ?: '-' }}</span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-muted text-uppercase fw-semibold d-block">Entered By</small>
                    <span class="fw-semibold text-dark fs-6">{{ $voucher->user?->name ?: 'System Auto-Post' }}</span>
                </div>
            </div>

            <!-- Double-Entry Particulars Table -->
            <div class="table-responsive mb-4">
                <table class="table app-index-table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;" class="text-center">Dr / Cr</th>
                            <th>Particulars / General Ledger Account</th>
                            <th style="width: 200px;">Cost Centre</th>
                            <th>Line Memo</th>
                            <th style="width: 170px;" class="text-end">Debit (₹)</th>
                            <th style="width: 170px;" class="text-end">Credit (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucher->items as $item)
                        <tr>
                            <td class="text-center fw-bold fs-6 {{ $item->type === 'debit' ? 'text-success' : 'text-danger' }}">
                                {{ $item->type === 'debit' ? 'Dr' : 'Cr' }}
                            </td>
                            <td>
                                <a href="{{ route('accounting.ledger-statement', $item->ledger_id) }}" class="fw-bold text-decoration-none" style="color: var(--brand-text-primary);">
                                    {{ $item->ledger?->name }}
                                </a>
                                <small class="text-muted d-block">{{ $item->ledger?->accountGroup?->name }}</small>
                            </td>
                            <td>
                                @if($item->costCentre)
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-truck me-1"></i>{{ $item->costCentre->name }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $item->particulars_note ?: '-' }}</td>
                            <td class="text-end fw-bold" style="color: var(--brand-success);">
                                {{ $item->type === 'debit' ? '₹' . number_format($item->amount, 2) : '-' }}
                            </td>
                            <td class="text-end fw-bold" style="color: var(--brand-danger);">
                                {{ $item->type === 'credit' ? '₹' . number_format($item->amount, 2) : '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light fw-bold fs-6">
                        <tr>
                            <td colspan="4" class="text-end pe-3">Voucher Total:</td>
                            <td class="text-end" style="color: var(--brand-success);">
                                ₹{{ number_format($voucher->items->where('type', 'debit')->sum('amount'), 2) }}
                            </td>
                            <td class="text-end" style="color: var(--brand-danger);">
                                ₹{{ number_format($voucher->items->where('type', 'credit')->sum('amount'), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Narration Box -->
            @if($voucher->narration)
            <div class="border rounded p-3" style="background-color: var(--brand-bg); border-color: var(--brand-border) !important;">
                <small class="fw-bold text-uppercase text-muted d-block mb-1">Narration / Remarks</small>
                <div class="text-dark">{{ $voucher->narration }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
@media print {
    .app-sidebar, .main-sidebar, .app-header, .app-index-card-toolbar, .btn, footer {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        background: transparent !important;
        color: #000 !important;
        border-bottom: 2px solid #333 !important;
        padding-left: 0 !important;
    }
    .card-header .card-title {
        color: #000 !important;
    }
    body {
        background: #fff !important;
    }
}
</style>
@endsection
