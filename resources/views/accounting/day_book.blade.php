@extends('layouts.app')

@section('title', 'Day Book - ' . $company->name)

@section('content')
<div class="container-fluid py-3 app-index-page--standard">
    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3" style="border: 1px solid var(--brand-border) !important; border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.day-book') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Voucher Type</label>
                    <select name="voucher_type" class="form-select form-select-sm">
                        <option value="">All Voucher Types</option>
                        <option value="sales" {{ $voucherType === 'sales' ? 'selected' : '' }}>Sales (F8)</option>
                        <option value="purchase" {{ $voucherType === 'purchase' ? 'selected' : '' }}>Purchase (F9)</option>
                        <option value="payment" {{ $voucherType === 'payment' ? 'selected' : '' }}>Payment (F5)</option>
                        <option value="receipt" {{ $voucherType === 'receipt' ? 'selected' : '' }}>Receipt (F6)</option>
                        <option value="journal" {{ $voucherType === 'journal' ? 'selected' : '' }}>Journal (F7)</option>
                        <option value="contra" {{ $voucherType === 'contra' ? 'selected' : '' }}>Contra (F4)</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-brand-primary btn-sm flex-grow-1">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('accounting.day-book') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Metric / KPI Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="background: var(--brand-gradient); border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Total Vouchers</div>
                        <h3 class="fw-bold mb-0 text-white">{{ $transactions->count() }}</h3>
                    </div>
                    <i class="bi bi-receipt-cutoff fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="background: var(--brand-gradient-success); border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Turnover (Debits)</div>
                        <h3 class="fw-bold mb-0 text-white">₹{{ number_format($transactions->sum('amount'), 2) }}</h3>
                    </div>
                    <i class="bi bi-cash-stack fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="background: var(--brand-gradient-info); border-radius: 12px; color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-semibold">Period</div>
                        <h5 class="fw-bold mb-0 text-white">{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</h5>
                    </div>
                    <i class="bi bi-calendar-check fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card app-index-list-card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-journal-bookmark me-2"></i>Day Book Register ({{ $company->name }})
            </h3>
            <div class="app-index-card-toolbar d-flex gap-2">
                <a href="{{ route('accounting.vouchers.create') }}" class="btn app-index-header-btn">
                    <i class="bi bi-plus-circle"></i> New Voucher
                </a>
                <a href="{{ route('accounting.export-tally-vouchers', ['from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn app-index-header-btn">
                    <i class="bi bi-file-earmark-code"></i> Export Tally XML
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table app-index-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width: 110px;">Date</th>
                            <th style="width: 140px;">Voucher #</th>
                            <th style="width: 110px;">Type</th>
                            <th>Particulars / Opposing Ledger</th>
                            <th class="text-end" style="width: 140px;">Amount (₹)</th>
                            <th>Narration</th>
                            <th class="text-center pe-3" style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $t['date'] }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $t['voucher_number'] }}</span></td>
                            <td>
                                @php
                                    $badgeStyle = match(strtolower($t['voucher_type'])) {
                                        'sales' => 'background-color: var(--brand-soft-success); color: var(--brand-success);',
                                        'purchase' => 'background-color: var(--brand-soft-danger); color: var(--brand-danger);',
                                        'payment' => 'background-color: var(--brand-soft-warning); color: var(--brand-warning);',
                                        'receipt' => 'background-color: var(--brand-soft-info); color: var(--brand-info);',
                                        'contra' => 'background-color: var(--brand-soft-secondary); color: var(--brand-text-muted);',
                                        default => 'background-color: var(--brand-soft-primary); color: var(--brand-primary);'
                                    };
                                @endphp
                                <span class="badge border" style="{{ $badgeStyle }} font-weight: 600;">{{ $t['voucher_type'] }}</span>
                            </td>
                            <td class="fw-bold" style="color: var(--brand-text-primary);">{{ $t['particulars'] }}</td>
                            <td class="text-end fw-bold" style="color: var(--brand-primary);">₹{{ number_format($t['amount'], 2) }}</td>
                            <td class="small text-muted text-truncate" style="max-width: 250px;">{{ $t['narration'] ?? '-' }}</td>
                            <td class="text-center pe-3">
                                <a href="{{ route('accounting.vouchers.show', $t['id']) }}" class="btn btn-sm btn-secondary py-1 px-2" title="View Double-Entry Voucher">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                No accounting vouchers found for the selected period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
